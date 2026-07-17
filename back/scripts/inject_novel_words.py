#!/usr/bin/env python3
"""Inject globally novel words from novel_ru.py into category banks."""
from __future__ import annotations

import importlib.util
import re
import sys
from pathlib import Path

SCRIPTS = Path(__file__).resolve().parent
WORDS_DIR = SCRIPTS / "words"
sys.path.insert(0, str(SCRIPTS))
from build_dictionary import clean_word, assign_words, TARGETS, MAX_LEVEL, DEFAULT_MAX  # noqa: E402


def _w(text: str) -> list[str]:
    out: list[str] = []
    seen: set[str] = set()
    for x in re.split(r"[\s,;\n]+", text):
        x = x.strip()
        if not x:
            continue
        k = x.casefold()
        if k in seen:
            continue
        seen.add(k)
        out.append(x)
    return out


def _global_existing() -> set[str]:
    grouped = assign_words()
    seen: set[str] = set()
    for cat in grouped:
        for lvl in grouped[cat]:
            seen.update(w.lower() for w in grouped[cat][lvl])
    return seen


def _load_novel() -> dict[str, dict[int, list[str]]]:
    out: dict[str, dict[int, list[str]]] = {}
    for fname in ("novel_ru.py", "novel_ru2.py", "novel_ru3.py"):
        path = WORDS_DIR / fname
        if not path.is_file():
            continue
        spec = importlib.util.spec_from_file_location(fname, path)
        if spec is None or spec.loader is None:
            continue
        mod = importlib.util.module_from_spec(spec)
        spec.loader.exec_module(mod)
        for key in ("NOVEL", "NOVEL2", "NOVEL3"):
            raw = getattr(mod, key, {})
            if not isinstance(raw, dict):
                continue
            for cat, levels in raw.items():
                if cat not in TARGETS or not isinstance(levels, dict):
                    continue
                out.setdefault(cat, {})
                for lvl, text in levels.items():
                    level = int(lvl)
                    if isinstance(text, str):
                        out[cat].setdefault(level, []).extend(_w(text))
                    elif isinstance(text, list):
                        out[cat].setdefault(level, []).extend(str(x) for x in text)
    return out


def inject(category: str, novel: dict[int, list[str]], existing: set[str]) -> tuple[int, int]:
    path = WORDS_DIR / f"{category}.py"
    ns: dict = {}
    exec(path.read_text(encoding="utf-8"), ns)
    levels: dict = ns.get("LEVELS", {})
    max_lvl = MAX_LEVEL.get(category, DEFAULT_MAX)
    seen: set[str] = set()
    merged: dict[int, list[str]] = {}
    added = 0

    for lvl in range(1, max_lvl + 1):
        out: list[str] = []
        for w in levels.get(lvl, []):
            c = clean_word(str(w).strip(), lvl, category)
            if not c:
                continue
            k = c.lower()
            if k in seen:
                continue
            seen.add(k)
            out.append(c)
        for w in novel.get(lvl, []):
            c = clean_word(str(w).strip(), lvl, category)
            if not c:
                continue
            k = c.lower()
            if k in seen or k in existing:
                continue
            seen.add(k)
            existing.add(k)
            out.append(c)
            added += 1
        if out:
            merged[lvl] = out

    before = sum(len(v) for v in levels.values() if isinstance(v, list))
    lines = ["", "LEVELS = {"]
    for lvl in sorted(merged.keys()):
        lines.append(f"    {lvl}: [")
        for w in merged[lvl]:
            esc = w.replace("\\", "\\\\").replace("'", "\\'")
            lines.append(f"        '{esc}',")
        lines.append("    ],")
    lines.append("}")
    lines.append("")
    path.write_text("\n".join(lines), encoding="utf-8")
    return before, before + added


def main() -> int:
    novel = _load_novel()
    existing = _global_existing()
    print(f"Existing global words: {len(existing)}")
    total_added = 0
    for cat in sorted(novel.keys()):
        b, a = inject(cat, novel[cat], existing)
        print(f"{cat}: +{a - b} novel words")
        total_added += a - b
    print(f"Total added: {total_added}")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
