#!/usr/bin/env python3
"""Write PHP word files from curated banks. Dedupes within category, skips junk."""
from __future__ import annotations

import re
from pathlib import Path

from word_banks_ru import BANKS, CATEGORIES

ROOT = Path(__file__).resolve().parent.parent
OUT = ROOT / "back" / "src" / "Command" / "Data"

# Reject leftover fake patterns if any slip in
JUNK_RE = re.compile(
    r"(-\d+$)|(\bв лесу\b)|(\bна работе\b)|(\bглубинн)|(-культов)|(-тема$)|(-игра$)|(-слово$)",
    re.IGNORECASE,
)


def to_php(arr: list[str]) -> str:
    lines = [f"    '{w.replace(chr(92), chr(92)*2).replace(chr(39), chr(92)+chr(39))}'," for w in arr]
    return "<?php\n\nreturn [\n" + "\n".join(lines) + "\n];\n"


def main() -> None:
    total = 0
    for cat in CATEGORIES:
        if cat not in BANKS:
            raise SystemExit(f"Missing category: {cat}")
        dest = OUT / cat
        dest.mkdir(parents=True, exist_ok=True)
        seen: set[str] = set()
        for level in range(1, 7):
            words: list[str] = []
            for w in BANKS[cat][level]:
                w = " ".join(w.strip().split())
                if not w or len(w) > 80:
                    continue
                if JUNK_RE.search(w):
                    continue
                key = w.casefold()
                if key in seen:
                    continue
                seen.add(key)
                words.append(w)
            if len(words) < 20:
                raise SystemExit(f"{cat}/{level}: only {len(words)} unique words (need >= 20)")
            (dest / f"level_{level}.php").write_text(to_php(words), encoding="utf-8")
            total += len(words)
            print(f"wrote {cat}/level_{level}.php ({len(words)})")
    print(f"Done. Total unique words: {total}")


if __name__ == "__main__":
    main()
