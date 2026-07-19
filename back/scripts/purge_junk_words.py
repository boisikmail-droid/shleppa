# -*- coding: utf-8 -*-
"""Remove leftover junk single words from category banks."""
from __future__ import annotations

import re
import sys
from pathlib import Path

SCRIPTS = Path(__file__).resolve().parent
WORDS = SCRIPTS / "words"
sys.path.insert(0, str(SCRIPTS))
from build_dictionary import TARGETS, clean_word, MAX_LEVEL, DEFAULT_MAX  # noqa: E402

# Explicitly bad / unplayable leftovers
DROP = frozenset({
    "наду", "секретарь",  # animals nonsense from hard_l6
    "гренландский", "ломбард", "меекат", "выездка", "анорак",
    "категория",  # too generic for hard everyday
    "пропускная", "пропускнаяспособность", "бандвагон", "рейскондишн",
    "судитель", "бразильское", "лыжное", "академическая", "вольная",
    "пляжный", "греко-римская",  # orphan adjectives in sport
    "зависимых", "зависимостей",  # orphan
    "литосферная",  # orphan adj
    "СВ",  # too short abbreviation alone
    "дока",  # wrong form
    "ямъ", "ямб",
})

DROP_RE = re.compile(
    r"(ишка$|ёнокёнок|оконок|"
    r"ничек$|ечек$|"
    r"^(мох|лес|луг)(ёк|ушка)$)",
    re.I,
)


def write_bank(category: str, levels: dict[int, list[str]]) -> None:
    path = WORDS / f"{category}.py"
    lines = ["# -*- coding: utf-8 -*-", "", "LEVELS = {"]
    for lvl in sorted(levels):
        lines.append(f"    {lvl}: [")
        for w in levels[lvl]:
            esc = w.replace("\\", "\\\\").replace("'", "\\'")
            lines.append(f"        '{esc}',")
        lines.append("    ],")
    lines.append("}")
    lines.append("")
    path.write_text("\n".join(lines), encoding="utf-8")


def main() -> None:
    report: list[str] = []
    for cat in TARGETS:
        if cat in ("phrases", "random_phrases"):
            continue
        path = WORDS / f"{cat}.py"
        ns: dict = {}
        exec(path.read_text(encoding="utf-8"), ns)
        old = ns.get("LEVELS", {})
        max_lvl = MAX_LEVEL.get(cat, DEFAULT_MAX)
        new: dict[int, list[str]] = {}
        seen: set[str] = set()
        dropped = 0
        for lvl in range(1, max_lvl + 1):
            out = []
            for w in old.get(lvl, []):
                w = str(w).strip()
                low = w.casefold()
                if low in DROP or DROP_RE.search(low):
                    dropped += 1
                    report.append(f"{cat}/L{lvl}: {w}")
                    continue
                c = clean_word(w, lvl, cat)
                if not c:
                    dropped += 1
                    report.append(f"{cat}/L{lvl}: {w} [clean]")
                    continue
                k = c.casefold()
                if k in seen:
                    continue
                seen.add(k)
                out.append(c)
            if out:
                new[lvl] = out
        write_bank(cat, new)
        print(f"{cat}: dropped {dropped}, kept {sum(len(v) for v in new.values())}")
    (SCRIPTS / "_adequacy_dropped.txt").write_text("\n".join(report), encoding="utf-8")
    print(f"Dropped total: {len(report)} -> _adequacy_dropped.txt")


if __name__ == "__main__":
    main()
