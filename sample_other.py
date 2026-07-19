# -*- coding: utf-8 -*-
import re
import sys
from classify_reviews import chunks, label, NAME, norm

sys.stdout.reconfigure(encoding="utf-8")

other = []
for raw in chunks:
    labs = label(raw)
    if labs == ["other_neg"] or (labs[0] == "other_neg" and len(labs) == 1):
        other.append(raw)

print("other_neg primary-ish", len(other))
for e in other[:40]:
    print("-", e.replace("\n", " ")[:180])
    print()
