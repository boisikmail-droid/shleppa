# -*- coding: utf-8 -*-
"""
Pack current bank words into levels 1–5 (by difficulty score),
append curated hard level 6 from hard_l6.py, then ready for build_dictionary.
"""
from __future__ import annotations

import importlib.util
import re
import sys
from pathlib import Path

SCRIPTS = Path(__file__).resolve().parent
WORDS = SCRIPTS / "words"
sys.path.insert(0, str(SCRIPTS))

from build_dictionary import (  # noqa: E402
    TARGETS,
    clean_word,
    title_case_ru,
    LATIN_RE,
)

# All gameplay categories go to 6 after relevel.
NEW_MAX = {cat: 6 for cat in TARGETS}

# Share of packed words across L1–5 (L6 is separate hard bank).
PACK_WEIGHTS = [0.14, 0.22, 0.26, 0.22, 0.16]


def _load_hard() -> dict[str, list[str]]:
    path = WORDS / "hard_l6.py"
    spec = importlib.util.spec_from_file_location("hard_l6", path)
    assert spec and spec.loader
    mod = importlib.util.module_from_spec(spec)
    spec.loader.exec_module(mod)
    return getattr(mod, "HARD", {})


def _score(word: str, old_lvl: int, category: str) -> float:
    """Higher = harder → higher packed level."""
    s = float(old_lvl) * 40.0
    parts = word.split()
    if category in ("phrases", "random_phrases"):
        s += sum(len(p) for p in parts) * 1.5
        s += max(0, len(parts) - 3) * 8
        avg = sum(len(p) for p in parts) / max(len(parts), 1)
        s += max(0, avg - 6) * 3
    else:
        bare = word.replace("-", "").replace(" ", "")
        s += len(bare) * 2.2
        if LATIN_RE.search(word):
            s += 80
        # rare morphology hints
        low = word.lower()
        for suf in ("ция", "изм", "огия", "ация", "енция", "офбия", "ория"):
            if low.endswith(suf):
                s += 12
        if "-" in word:
            s += 8
        if " " in word:
            s += 15 * (len(parts) - 1)
    return s


def _alloc(n: int) -> dict[int, int]:
    raw = [n * w for w in PACK_WEIGHTS]
    floors = [int(x) for x in raw]
    rem = n - sum(floors)
    order = sorted(range(5), key=lambda i: raw[i] - floors[i], reverse=True)
    for i in order:
        if rem <= 0:
            break
        floors[i] += 1
        rem -= 1
    return {lvl + 1: floors[lvl] for lvl in range(5)}


def _write_bank(category: str, levels: dict[int, list[str]]) -> None:
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


def relevel_category(category: str, hard: list[str]) -> tuple[int, int, dict[int, int]]:
    path = WORDS / f"{category}.py"
    ns: dict = {}
    exec(path.read_text(encoding="utf-8"), ns)
    old: dict = ns.get("LEVELS", {})

    scored: list[tuple[float, str]] = []
    force_l6: list[str] = []
    seen: set[str] = set()

    for lvl, words in old.items():
        for w in words:
            w = str(w).strip()
            if not w:
                continue
            key = w.casefold()
            if key in seen:
                continue
            seen.add(key)
            # Latin tech / brand tokens stay on hard tier
            if category == "tech" and LATIN_RE.search(w):
                force_l6.append(w)
                continue
            scored.append((_score(w, int(lvl), category), w))

    scored.sort(key=lambda x: (x[0], x[1].casefold()))
    packed = [w for _, w in scored]
    quotas = _alloc(len(packed))

    levels: dict[int, list[str]] = {i: [] for i in range(1, 7)}
    idx = 0
    for lvl in range(1, 6):
        take = quotas.get(lvl, 0)
        chunk = packed[idx : idx + take]
        idx += take
        # filter with clean_word at target level; overflow soft-push up
        for w in chunk:
            c = clean_word(w, lvl, category)
            if c:
                levels[lvl].append(c)
            else:
                # try higher levels
                placed = False
                for alt in range(lvl + 1, 6):
                    c2 = clean_word(w, alt, category)
                    if c2:
                        levels[alt].append(c2)
                        placed = True
                        break
                if not placed:
                    c6 = clean_word(w, 6, category)
                    if c6:
                        force_l6.append(c6)

    # Hard L6
    l6_seen = {w.casefold() for lst in levels.values() for w in lst}
    l6_seen |= {w.casefold() for w in force_l6}
    out6: list[str] = []
    for w in force_l6 + hard:
        w = str(w).strip()
        if not w:
            continue
        if category in ("celebrities", "movies") and " " in w:
            w = title_case_ru(w)
        elif category in ("celebrities", "movies"):
            w = title_case_ru(w)
        c = clean_word(w, 6, category)
        if not c:
            continue
        k = c.casefold()
        if k in l6_seen:
            continue
        l6_seen.add(k)
        out6.append(c)
    levels[6] = out6

    # drop empties but keep structure 1..6
    final = {lvl: levels[lvl] for lvl in range(1, 7) if levels[lvl]}
    _write_bank(category, final)
    counts = {lvl: len(final.get(lvl, [])) for lvl in range(1, 7)}
    return sum(counts.values()), counts.get(6, 0), counts


def main() -> None:
    hard_map = _load_hard()
    print("Relevel -> L1-5 + curated L6")
    total = 0
    for cat in TARGETS:
        n, n6, counts = relevel_category(cat, hard_map.get(cat, []))
        total += n
        parts = " ".join(f"L{l}:{counts.get(l, 0)}" for l in range(1, 7))
        print(f"  {cat}: {n} ({parts})")
    print(f"Total bank words: {total}")


if __name__ == "__main__":
    main()
