#!/usr/bin/env python3
"""Remove junk words from back/scripts/words/*.py banks (in-place)."""
from __future__ import annotations

import ast
import re
import sys
from pathlib import Path

WORDS_DIR = Path(__file__).parent / "words"
SKIP = {"seeds.py", "fix_seeds.py", "__init__.py"}

sys.path.insert(0, str(Path(__file__).parent))
from build_dictionary import clean_word, TARGETS, MAX_LEVEL, DEFAULT_MAX  # noqa: E402


def sanitize_file(path: Path) -> tuple[int, int]:
    text = path.read_text(encoding="utf-8")
    ns: dict = {}
    exec(text, ns)
    levels: dict = ns.get("LEVELS")
    if not isinstance(levels, dict):
        return 0, 0

    category = path.stem
    max_lvl = MAX_LEVEL.get(category, DEFAULT_MAX)
    before = sum(len(v) for v in levels.values() if isinstance(v, list))
    cleaned: dict[int, list[str]] = {}
    seen: set[str] = set()

    for lvl, words in sorted(levels.items(), key=lambda x: int(x[0])):
        level = int(lvl)
        if level > max_lvl:
            continue
        out: list[str] = []
        for w in words:
            w = str(w).strip()
            c = clean_word(w, level, category)
            if not c:
                continue
            k = c.lower()
            if k in seen:
                continue
            seen.add(k)
            out.append(c)
        if out:
            cleaned[level] = out

    after = sum(len(v) for v in cleaned.values())
    if after == 0:
        return before, after

    # Preserve _u() helper if present
    header = ""
    if "def _u(" in text:
        m = re.search(r"^.*?(?=LEVELS\s*=)", text, re.S | re.M)
        if m:
            header = m.group(0).rstrip() + "\n\n"

    lines = [header.rstrip(), "LEVELS = {"]
    for lvl in sorted(cleaned.keys()):
        lines.append(f"    {lvl}: [")
        for w in cleaned[lvl]:
            esc = w.replace("\\", "\\\\").replace("'", "\\'")
            lines.append(f"        '{esc}',")
        lines.append("    ],")
    lines.append("}")
    lines.append("")
    path.write_text("\n".join(lines), encoding="utf-8")
    return before, after


def main() -> int:
    total_b = total_a = 0
    for path in sorted(WORDS_DIR.glob("*.py")):
        if path.name in SKIP or path.name.startswith("_"):
            continue
        if path.stem not in TARGETS:
            continue
        b, a = sanitize_file(path)
        print(f"{path.name}: {b} -> {a} ({b - a} removed)")
        total_b += b
        total_a += a
    print(f"Total: {total_b} -> {total_a}")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
