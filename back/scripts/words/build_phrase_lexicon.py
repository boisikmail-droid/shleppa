# -*- coding: utf-8 -*-
"""Expand phrase vocab from existing word banks; patch gen_phrases themes at runtime."""
from __future__ import annotations

import re
from pathlib import Path

WORDS = Path(__file__).resolve().parent
SKIP = {
    "gen_phrases.py", "phrases.py", "random_phrases.py", "seeds.py",
    "build.py", "finalize.py", "create_gen_words.py", "_generate.py",
    "_gen_data.py",
}


def load_all() -> list[str]:
    all_w: set[str] = set()
    for f in WORDS.glob("*.py"):
        if f.name in SKIP or f.name.startswith("_"):
            continue
        text = f.read_text(encoding="utf-8")
        for m in re.finditer(r"'([^']{3,24})'", text):
            w = m.group(1)
            if " " in w or re.search(r"[A-Za-z]", w):
                continue
            if not re.fullmatch(r"[а-яёА-ЯЁ\-]+", w):
                continue
            all_w.add(w.lower())
    return sorted(all_w)


def classify(words: list[str]) -> tuple[list[str], list[str], list[str], list[str]]:
    adj, verb, adv, noun = [], [], [], []
    for w in words:
        if w.endswith(("ый", "ий", "ой", "ая", "ое", "ые", "ийся", "аяся")):
            adj.append(w)
        elif w.endswith(("ать", "ять", "еть", "ить", "уть", "ыть", "ти", "чь", "ться", "иться", "аться")):
            verb.append(w)
        elif w.endswith(("но", "то", "ко", "о", "ом", "ем")) and len(w) <= 10:
            # weak adverb heuristic — skip most
            if w.endswith(("но", "ко")) and not w.endswith(("ость", "ение", "ание")):
                adv.append(w)
            else:
                noun.append(w)
        else:
            noun.append(w)
    return adj, noun, verb, adv


def main() -> None:
    words = load_all()
    adj, noun, verb, adv = classify(words)
    print(f"total={len(words)} adj={len(adj)} noun={len(noun)} verb={len(verb)} adv={len(adv)}")
    out = WORDS / "_phrase_lexicon.py"
    lines = [
        "# -*- coding: utf-8 -*-",
        "# Auto-generated lexicon for gen_phrases.py",
        "ADJ = [",
    ]
    for w in adj:
        lines.append(f"    {w!r},")
    lines.append("]")
    lines.append("NOUN = [")
    for w in noun:
        lines.append(f"    {w!r},")
    lines.append("]")
    lines.append("VERB = [")
    for w in verb:
        lines.append(f"    {w!r},")
    lines.append("]")
    lines.append("ADV = [")
    for w in adv:
        lines.append(f"    {w!r},")
    lines.append("]")
    lines.append("")
    out.write_text("\n".join(lines), encoding="utf-8")
    print(f"wrote {out}")


if __name__ == "__main__":
    main()
