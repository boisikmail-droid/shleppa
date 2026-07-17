# -*- coding: utf-8 -*-
"""Verify dictionary words against external Russian wordlists.

Sources:
  - danakt/russian-words (morphological forms, cp1251)
  - hingston/russian 100k frequency list (utf-8)
  - danakt surnames (for celebrities)

Categories celebrities/movies: proper nouns — not required in general lexicon.
phrases/random_phrases: each content token must exist.
"""
from __future__ import annotations

import re
import sys
from collections import defaultdict
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
EXT = Path(__file__).resolve().parent / "ext"
DATA = ROOT / "src" / "Command" / "Data"
WORDS = Path(__file__).resolve().parent / "words"
sys.path.insert(0, str(Path(__file__).resolve().parent))

from build_dictionary import clean_word  # noqa: E402

STOP = frozenset({
    "в", "на", "с", "и", "по", "у", "из", "к", "за", "до", "от", "о", "об",
    "со", "ко", "во", "для", "без", "под", "над", "при", "про", "через",
    "а", "но", "или", "не", "ни", "то", "это", "как", "что",
})

PROPER_CATS = frozenset({"celebrities", "movies"})
PHRASE_CATS = frozenset({"phrases", "random_phrases"})


def norm(w: str) -> str:
    return w.casefold().replace("ё", "е")


def load_cp1251(path: Path) -> set[str]:
    raw = path.read_bytes()
    text = raw.decode("cp1251", errors="ignore")
    out: set[str] = set()
    for line in text.splitlines():
        w = line.strip().lstrip("-")
        if not w or " " in w:
            continue
        if not re.fullmatch(r"[а-яёА-ЯЁ\-]+", w):
            continue
        out.add(norm(w))
    return out


def load_utf8_words(path: Path) -> set[str]:
    out: set[str] = set()
    for line in path.read_text(encoding="utf-8", errors="ignore").splitlines():
        w = line.strip()
        if not w or " " in w:
            continue
        if not re.fullmatch(r"[а-яёА-ЯЁ\-]+", w):
            continue
        out.add(norm(w))
    return out


def load_php_words() -> list[tuple[str, int, str]]:
    rows: list[tuple[str, int, str]] = []
    for f in sorted(DATA.rglob("level_*.php")):
        if "extra" in f.parts:
            continue
        cat = f.parent.name
        level = int(f.stem.split("_")[1])
        text = f.read_text(encoding="utf-8")
        for m in re.finditer(r"'((?:\\'|[^'])*)'", text):
            w = m.group(1).replace("\\'", "'")
            if len(w) >= 2:
                rows.append((cat, level, w))
    return rows


def tokens_of(phrase: str) -> list[str]:
    return [t for t in phrase.split() if norm(t) not in STOP and re.search(r"[а-яё]", t, re.I)]


def main() -> int:
    print("Loading external dictionaries...")
    lex = load_cp1251(EXT / "russian_cp1251.txt")
    print(f"  danakt forms: {len(lex)}")
    freq = load_utf8_words(EXT / "russian_100k.txt")
    print(f"  leeds 100k: {len(freq)}")
    known = lex | freq

    surnames: set[str] = set()
    sp = EXT / "russian_surnames_cp1251.txt"
    if sp.is_file() and sp.stat().st_size > 1000:
        surnames = load_cp1251(sp)
        print(f"  surnames: {len(surnames)}")
        known |= surnames

    rows = load_php_words()
    print(f"Dictionary entries: {len(rows)}")

    missing_by_cat: dict[str, list[tuple[int, str]]] = defaultdict(list)
    ok = miss = skip = 0

    for cat, level, word in rows:
        if cat in PROPER_CATS:
            # Proper nouns: accept if any token is in lexicon/surnames OR multi-word title
            toks = tokens_of(word)
            if not toks:
                skip += 1
                continue
            # Accept celebrity/movie entries wholesale (curated proper nouns)
            # but flag single tokens that look like fake dim junk
            bad = False
            for t in toks:
                n = norm(t)
                if n.endswith(("ище", "ичек", "ничек")) and n not in known:
                    bad = True
                    break
            if bad:
                missing_by_cat[cat].append((level, word))
                miss += 1
            else:
                ok += 1
                skip += 1  # counted as skipped-from-strict
            continue

        if cat in PHRASE_CATS or " " in word:
            toks = tokens_of(word)
            bad_toks = [t for t in toks if norm(t) not in known]
            if bad_toks:
                missing_by_cat[cat].append((level, f"{word}  << {bad_toks}"))
                miss += 1
            else:
                ok += 1
            continue

        # single common word
        if norm(word) in known:
            ok += 1
        else:
            missing_by_cat[cat].append((level, word))
            miss += 1

    report = Path(__file__).resolve().parent / "_existence_report.txt"
    lines = [
        f"known lexicon size: {len(known)}",
        f"ok={ok} missing={miss} proper_skipped≈{skip}",
        "",
    ]
    total_miss = 0
    for cat in sorted(missing_by_cat):
        items = sorted(set(missing_by_cat[cat]), key=lambda x: (x[0], x[1].casefold()))
        total_miss += len(items)
        lines.append(f"=== {cat}: {len(items)} ===")
        for lvl, w in items[:100]:
            lines.append(f"  L{lvl}: {w}")
        if len(items) > 100:
            lines.append(f"  ... +{len(items) - 100} more")
        lines.append("")

    report.write_text("\n".join(lines), encoding="utf-8")
    print(f"Wrote {report}")
    print(f"Missing unique entries: {total_miss}")
    for cat in sorted(missing_by_cat):
        print(f"  {cat}: {len(set(missing_by_cat[cat]))}")

    # Also write a machine-readable drop list for single-word misses (not phrases detail)
    drop_path = Path(__file__).resolve().parent / "_nonexistent_words.txt"
    drops: set[str] = set()
    for cat, items in missing_by_cat.items():
        if cat in PROPER_CATS:
            continue
        for _lvl, w in items:
            if "<<" in w:
                # phrase: extract bad tokens
                _, bad = w.split("<<", 1)
                for t in re.findall(r"'([^']+)'|(\S+)", bad):
                    tok = t[0] or t[1]
                    tok = tok.strip("[]', ")
                    if tok and norm(tok) not in STOP:
                        drops.add(norm(tok))
            else:
                drops.add(norm(w))
    drop_path.write_text("\n".join(sorted(drops)), encoding="utf-8")
    print(f"Drop tokens: {len(drops)} -> {drop_path}")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
