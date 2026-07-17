#!/usr/bin/env python3
"""
Build PHP word files for app:import-words.

Run from repo root:
  python back/scripts/build_dictionary.py
"""
from __future__ import annotations

import re
import sys
from collections import defaultdict
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
OUT = ROOT / "src" / "Command" / "Data"
WORDS_DIR = Path(__file__).resolve().parent / "words"

TARGET_TOTAL = 13_600

TARGETS: dict[str, int] = {
    "everyday": 1800,
    "food": 1600,
    "animals": 1300,
    "nature": 1300,
    "movies": 1200,
    "school": 1100,
    "profession": 900,
    "feelings": 900,
    "sport": 800,
    "tech": 800,
    "transport": 700,
    "places": 700,
    "clothes": 600,
    "furniture": 600,
    "celebrities": 500,
    "phrases": 550,
    "random_phrases": 650,
}

MAX_LEVEL: dict[str, int] = {
    "clothes": 3,
    "furniture": 3,
    "celebrities": 3,
    "sport": 4,
    "places": 4,
    "transport": 4,
    "animals": 5,
    "nature": 5,
    "movies": 5,
    "profession": 5,
    "feelings": 5,
    "phrases": 5,
    "random_phrases": 5,
}

DEFAULT_MAX = 6

# При дубликате слово остаётся в более «узкой» категории (не в everyday/food).
CATEGORY_PRIORITY: dict[str, int] = {
    "phrases": 0,
    "random_phrases": 1,
    "celebrities": 2,
    "movies": 3,
    "sport": 4,
    "profession": 5,
    "school": 6,
    "tech": 7,
    "transport": 8,
    "places": 9,
    "clothes": 10,
    "furniture": 11,
    "animals": 12,
    "nature": 13,
    "feelings": 14,
    "food": 15,
    "everyday": 16,
}

WEIGHTS: dict[int, list[float]] = {
    3: [0.18, 0.32, 0.50],
    4: [0.12, 0.24, 0.32, 0.32],
    5: [0.08, 0.18, 0.28, 0.28, 0.18],
    6: [0.06, 0.16, 0.26, 0.26, 0.18, 0.08],
}

LATIN_RE = re.compile(r"[a-zA-Z]")
JUNK_RE = re.compile(
    r"(ечек|юшка|кечек|кюшка|каечек|кочек|"
    r"[а-яё]{4,}(ик|ок|ка|ец)(ик|ок|ка|ец|ечек|юшка|очек|ушка))"
)
# Слишком искусственные «склеенные» уменьшительные из автогенерации
JUNK_SUFFIXES = ("ечек", "юшка", "кечек", "кюшка", "каечек", "кочек", "кка", "икик", "окок")
STACKED_DIM = re.compile(
    r"[а-яё]{4,}(?:ка|ок|ик|ец|ник|очек|ушка|чик)(?:ик|ок|ка|ёк|ушка|очек|чик)$"
)
JUNK_TAIL = re.compile(r"(кик|как|окок|икик|ёкик|чикик|кокок)$")
FAKE_DIM = re.compile(r"[а-яё]{2,}(?:ка|ок|ик|ец|ник|очек|ушка|чик)(?:ик|ок|ка|ёк)$")
ALLOWED_STACKED = frozenset({
    "блинчик", "сосиска", "колбаска", "котлета", "булочка", "баранка", "ватрушка", "корзинка",
    "бутылочка", "тарелочка", "чашечка", "оладья", "вареники", "пельмени", "сосиски", "блины",
    "огурчик", "помидорчик", "яблочко", "грушка", "морковка", "картошка", "луковица",
    "мальчик", "пальчик", "кузнечик", "мячик", "шарик", "дождик", "снежок", "лужок",
    "избушка", "избушка", "картошка", "подушка", "ватрушка", "ватрушка", "чашка", "ложка",
    "вилка", "тарелка", "бутылка", "коробка", "сумка", "шапка", "куртка", "майка",
})
# Реальные слова на -ище (остальное — летоище, мореище и т.п.)
ALLOWED_ISHCHE = frozenset({
    "кладбище", "пастбище", "училище", "голенище", "жилище", "убежище",
    "вместилище", "хранилище", "водохранилище", "книгохранилище",
    "зернохранилище", "профтехучилище", "побоище", "пожарище",
    "страшилище", "чудище", "позорище", "поприще", "сборище", "скопище",
})
# Реальные топонимы на -ик/-чик (остальные «Анадырик», «гаражик» — мусор)
ALLOWED_PLACE_IK = frozenset({
    "нальчик", "геленджик", "биробиджан", "махачкала",
})

# Служебные слова в середине названий (не капитализируем)
TITLE_PARTICLES = frozenset({
    "и", "в", "на", "с", "со", "по", "к", "ко", "у", "о", "об", "от", "из", "за",
    "для", "без", "под", "над", "при", "про", "через", "де", "дель", "фон", "ван",
    "да", "ди", "ла", "ле", "аль", "ибн", "бин",
})


def title_case_ru(word: str) -> str:
    """Имена / названия: каждое значимое слово с заглавной."""
    parts = word.split()
    out: list[str] = []
    for i, part in enumerate(parts):
        low = part.casefold()
        if i > 0 and low in TITLE_PARTICLES:
            out.append(low)
            continue
        segs = part.split("-")
        capped: list[str] = []
        for seg in segs:
            if not seg:
                capped.append(seg)
                continue
            capped.append(seg[0].upper() + seg[1:])
        out.append("-".join(capped))
    return " ".join(out)
# Автоуменьшительные: гаражик, лифтик, соусничек, столовочка
FAKE_DIM_END = re.compile(
    r"(чик|ничок|очек|ечек|ичек|ничек|онька|енька|ишко|очка|ечка|ёчек|ачок|ячок|енек)$"
)
# козаик, лошадьик, попугайёк, кошкаушка — суффикс прилеплен к целой основе
FAKE_APPEND_DIM = re.compile(r"[аеёиоуыэюяьй](ик|ок|ёк|ушка|юшка)$")
# белкаушка, галкаушка (а/я + ушка); не трогаем подушка (согласная + ушка)
FAKE_USHKA_STACK = re.compile(r"[аяьй]ушка$")
# склеенные слова без дефиса: кикбоксингклуб
GLUED_JUNK = re.compile(r"(клуб|спорт|игра|дом|зал)$")
L1_FORBIDDEN_END = re.compile(r"(ик|ок|ка|ёк|чик|ушка|очек|ник|ечек|юшка)$")
L2_FORBIDDEN_END = re.compile(r"(ечек|юшка|кечек|кюшка|каечек|икик|окок|кик|как|чик)$")

BANNED_LEVEL_1_5 = {
    "docker", "nvme", "bluetooth", "wifi", "wi-fi", "usb", "hdmi", "ssd", "gpu", "cpu",
    "api", "sdk", "json", "html", "css", "javascript", "python", "linux", "windows",
    "iphone", "android", "google", "youtube", "instagram", "tiktok", "netflix",
}

# Латиница только в tech/6
BANNED_EVERYWHERE_EXCEPT_TECH6 = {
    "extender", "gateway", "hub", "matter", "mesh", "repeater", "router", "thread",
    "zwave", "zigbee", "bluetooth", "wifi", "wi-fi",
}


def _load_banned_nonexistent() -> frozenset[str]:
    path = WORDS_DIR / "_banned_nonexistent.py"
    if not path.is_file():
        return frozenset()
    ns: dict = {}
    try:
        exec(path.read_text(encoding="utf-8"), ns)
    except Exception:
        return frozenset()
    raw = ns.get("BANNED", ())
    return frozenset(str(x).casefold().replace("ё", "е") for x in raw)


BANNED_NONEXISTENT = _load_banned_nonexistent()


def allocate(total: int, max_level: int) -> dict[int, int]:
    weights = WEIGHTS[max_level]
    raw = [w * total for w in weights]
    floors = [int(x) for x in raw]
    rem = [x - f for x, f in zip(raw, floors)]
    assigned = sum(floors)
    left = total - assigned
    order = sorted(range(len(rem)), key=lambda i: rem[i], reverse=True)
    for i in order:
        if left <= 0:
            break
        floors[i] += 1
        left -= 1
    return {lvl + 1: floors[lvl] for lvl in range(max_level)}


def load_category_words(category: str) -> dict[int, list[str]]:
    path = WORDS_DIR / f"{category}.py"
    if not path.exists():
        raise FileNotFoundError(path)
    ns: dict = {}
    exec(path.read_text(encoding="utf-8"), ns)
    levels: dict[int, list[str]] = ns.get("LEVELS", {})
    out: dict[int, list[str]] = {}
    for lvl, items in levels.items():
        out[int(lvl)] = [str(w).strip() for w in items if str(w).strip()]
    return out


def clean_word(word: str, level: int, category: str) -> str | None:
    word = re.sub(r"\s+", " ", word.strip())
    if not word or len(word) > 80:
        return None
    if " " in word and category not in ("celebrities", "movies", "phrases", "random_phrases", "sport"):
        return None
    # Фразы: 3–4 слова, без лишней пунктуации
    if category in ("phrases", "random_phrases"):
        parts = word.split()
        if len(parts) < 3 or len(parts) > 4:
            return None
        if any(LATIN_RE.search(p) for p in parts):
            return None
        if len(word) > 80:
            return None
        if level <= 5 and word.lower() in BANNED_LEVEL_1_5:
            return None
        # каждый знаменательный токен должен существовать
        for p in parts:
            pn = p.casefold().replace("ё", "е")
            if pn in BANNED_NONEXISTENT:
                return None
        return word
    low = word.lower()
    low_norm = low.replace("ё", "е")
    if low_norm in BANNED_NONEXISTENT or low in BANNED_NONEXISTENT:
        return None
    if any(s in low for s in JUNK_SUFFIXES):
        return None
    if JUNK_RE.search(low):
        return None
    if STACKED_DIM.search(low) and low not in ALLOWED_STACKED:
        return None
    if JUNK_TAIL.search(low):
        return None
    if FAKE_DIM.search(low) and low not in ALLOWED_STACKED:
        return None
    # «Анадырик», «гаражик», «столовочка» и прочий автомусор
    if FAKE_DIM_END.search(low) and low not in ALLOWED_STACKED and low not in ALLOWED_PLACE_IK:
        return None
    # соусничек / салатничек / кофейничек — уменьшительные от -ник
    if re.search(r"(ичек|ничек)$", low) and low not in ALLOWED_STACKED:
        return None
    # сахарничка / перечничка от -ница (кроме коротких естественных)
    if len(low) >= 9 and re.search(r"ничка$", low) and low not in ALLOWED_STACKED:
        return None
    # летоище / мореище / самоварище — фейковые увеличительные
    if low.endswith("ище") and low not in ALLOWED_ISHCHE:
        return None
    # автомусор: козаёнок, котёнокёнок, лошадьишка
    if re.search(
        r"(?:ёнокёнок|оконок|икишка|иконок|икёнок)$|"
        r"[аяь](?:ёнок|онок|ишка)$|"
        r"(?:ёнок|онок|ишка)(?:ёнок|онок|ишка)$",
        low,
    ):
        return None
    # козаик / лошадьик / кошкаушка
    if FAKE_APPEND_DIM.search(low) and low not in ALLOWED_STACKED:
        return None
    if FAKE_USHKA_STACK.search(low) and low not in ALLOWED_STACKED:
        return None
    # котикушка, щенокушка, пёсикушка, жучокушка
    if re.search(r"(ик|ок|ёк)ушка$", low):
        return None
    # лесушка / мохёк / лугёк / дубёк — короткая основа + суффикс
    if re.search(r"^(мох|лес|луг|дуб|лук|песок|ветк|год)(ёк|ушка|ик|ок)$", low):
        return None
    if low in {"рулёк", "брижик", "палтушка", "мохушка", "какуми", "офсайдловушка"}:
        return None
    # кикбоксингклуб и подобное
    if len(low) >= 12 and category == "sport" and "клуб" in low and " " not in low:
        if low not in ("яхтклуб", "спортклуб"):
            return None
    # Фейковые -жик/-дик у длинных слов (гаражик), во всех игровых категориях
    if len(low) >= 7 and re.search(r"(жик|дик)$", low) and low not in ALLOWED_STACKED:
        if low not in ("кузнечик", "чижик", "стрижик"):
            return None
    if category == "places":
        if low.endswith(("ик", "чик")) and low not in ALLOWED_PLACE_IK:
            return None
        if low.endswith(("ок", "ёк")) and len(low) >= 6 and low not in ALLOWED_STACKED:
            return None
        if re.search(r"(ушка|юшка|очка|ечка|онька|енька)$", low) and low not in ALLOWED_STACKED:
            return None
    if level <= 2 and len(low) > 7 and re.search(r"(кок|ушка|очек|ёк)$", low):
        if low not in ALLOWED_STACKED:
            return None
    if level == 1 and len(low) > 10:
        return None
    if level == 2 and len(low) > 14:
        return None
    if level <= 5 and low in BANNED_LEVEL_1_5:
        return None
    if low in BANNED_EVERYWHERE_EXCEPT_TECH6 and not (category == "tech" and level == 6):
        return None
    if level == 1:
        if len(low) > 12:
            return None
        if L1_FORBIDDEN_END.search(low) and low not in ALLOWED_STACKED:
            return None
    if level == 2:
        if len(low) > 16:
            return None
        if L2_FORBIDDEN_END.search(low) and low not in ALLOWED_STACKED:
            return None
    if level <= 5 and category != "tech" and LATIN_RE.search(word):
        return None
    if category != "tech" and LATIN_RE.search(word):
        return None
    if level <= 4 and category not in ("tech", "school") and LATIN_RE.search(word):
        return None
    # Имена, фамилии, фильмы, произведения — с заглавной
    if category in ("celebrities", "movies"):
        word = title_case_ru(word)
    return word


def strip_auto_diminutives(
    candidates: dict[str, tuple[str, int, str, int]],
) -> dict[str, tuple[str, int, str, int]]:
    """Убрать Xик/Xок/Xёк/Xка/Xичек, если в пуле уже есть основа X / Xь / Xа / Xик."""
    keys = set(candidates.keys())
    drop: set[str] = set()
    for key in keys:
        if key in ALLOWED_STACKED or key in ALLOWED_PLACE_IK:
            continue
        # соусничек ← соусник; летоище ← лето
        if key.endswith("ище") and key not in ALLOWED_ISHCHE:
            drop.add(key)
            continue
        if key.endswith("ичек") and len(key) > 6:
            base_nik = key[:-4] + "ик"
            if base_nik in keys:
                drop.add(key)
                continue
        for suf in ("ище", "ничек", "ичек", "чик", "ушка", "юшка", "ик", "ок", "ёк", "ка", "ничка"):
            if key in ALLOWED_ISHCHE and suf == "ище":
                continue
            if not key.endswith(suf) or len(key) - len(suf) < 3:
                continue
            stem = key[: -len(suf)]
            for base in (stem, stem + "ь", stem + "а", stem + "я", stem + "о", stem + "е", stem + "й", stem + "ик"):
                if base in keys and base != key:
                    if key in ALLOWED_STACKED or key in ALLOWED_ISHCHE:
                        break
                    drop.add(key)
                    break
            if key in drop:
                break
    return {k: v for k, v in candidates.items() if k not in drop}


# Категории, где автогенерация раздувает одну эмоцию в прил./нареч./глагол
MORPH_FAMILY_CATEGORIES = frozenset({"feelings"})

# Производные формы: тоскливый / тоскливо / тосковать при наличии «тоска»
MORPH_INFLATION_SUFFIXES = (
    "оваться",
    "ироваться",
    "ываться",
    "иваться",
    "овать",
    "ировать",
    "аться",
    "иться",
    "ить",
    "ать",
    "ять",
    "еть",
    "уть",
    "ыть",
    "чь",
    "ливый",
    "ливо",
    "ливость",
    "ивый",
    "иво",
    "ивость",
    "чный",
    "енный",
    "ный",
    "ной",
    "ский",
    "ская",
    "ное",
    "ные",
    "ный",
    "ая",
    "ое",
    "ые",
    "ий",
    "ой",
    "ый",
    "но",
    "ено",
    "ато",
    "ость",
    "ность",
    "ение",
    "ание",
    "изм",
    "ство",
)


def _morph_stems(word: str) -> set[str]:
    """Набор возможных основ для сопоставления семейства."""
    low = word.lower()
    stems: set[str] = {low}
    cur = low
    for _ in range(4):
        stripped = False
        for suf in sorted(MORPH_INFLATION_SUFFIXES, key=len, reverse=True):
            if cur.endswith(suf) and len(cur) - len(suf) >= 3:
                cur = cur[: -len(suf)]
                stems.add(cur)
                stripped = True
                break
        if not stripped:
            break
    extra: set[str] = set()
    for stem in stems:
        trimmed = stem.rstrip("ьъйеёиуюяаоыэ")
        if len(trimmed) >= 3:
            extra.add(trimmed)
        if len(stem) >= 4:
            extra.add(stem[:4])
        if len(stem) >= 5:
            extra.add(stem[:5])
    return stems | extra


def _morph_related(a: str, b: str) -> bool:
    if a == b:
        return False
    for sa in _morph_stems(a):
        for sb in _morph_stems(b):
            if sa == sb:
                return True
            common = 0
            for ca, cb in zip(sa, sb):
                if ca != cb:
                    break
                common += 1
            if common >= 4:
                return True
    return False


def _is_morph_inflation(word: str) -> bool:
    low = word.lower()
    return any(
        low.endswith(suf) and len(low) - len(suf) >= 4 for suf in MORPH_INFLATION_SUFFIXES
    )


def _morph_keep_rank(word: str) -> int:
    low = word.lower()
    if any(low.endswith(s) for s in ("но", "ато", "ено")) and not low.endswith(("ность", "ство")):
        return 1
    if any(
        low.endswith(s)
        for s in ("ный", "ной", "ивый", "ливый", "чный", "енный", "ый", "ий", "ой", "ая", "ое", "ые")
    ):
        return 2
    if any(
        low.endswith(s)
        for s in ("овать", "оваться", "аться", "иться", "ить", "ать", "ять", "еть", "уть", "чь", "ыть")
    ):
        return 3
    if any(low.endswith(s) for s in ("ливость", "ивость", "ость", "ность", "ение", "ание")):
        return 4
    return 10


def strip_morph_families(
    candidates: dict[str, tuple[str, int, str, int]],
) -> dict[str, tuple[str, int, str, int]]:
    """Убрать прил./нареч./глаголы, если в категории уже есть базовое слово той же семьи."""
    by_cat: dict[str, list[str]] = defaultdict(list)
    for key, (cat, _lvl, _word, _prio) in candidates.items():
        if cat in MORPH_FAMILY_CATEGORIES:
            by_cat[cat].append(key)

    drop: set[str] = set()
    for keys in by_cat.values():
        for i, k1 in enumerate(keys):
            if k1 in drop:
                continue
            for k2 in keys[i + 1 :]:
                if k2 in drop or not _morph_related(k1, k2):
                    continue
                r1, r2 = _morph_keep_rank(k1), _morph_keep_rank(k2)
                if r1 > r2:
                    drop.add(k2)
                elif r2 > r1:
                    drop.add(k1)
                elif len(k1) > len(k2):
                    drop.add(k1)
                else:
                    drop.add(k2)

        # Явно сбрасываем производные, если рядом есть якорное слово
        anchors = [k for k in keys if k not in drop and not _is_morph_inflation(k)]
        for key in keys:
            if key in drop or not _is_morph_inflation(key):
                continue
            if any(_morph_related(key, anchor) for anchor in anchors):
                drop.add(key)

    return {k: v for k, v in candidates.items() if k not in drop}


def write_php(path: Path, words: list[str]) -> None:
    path.parent.mkdir(parents=True, exist_ok=True)
    lines = ["<?php", "", "return ["]
    for w in words:
        esc = w.replace("\\", "\\\\").replace("'", "\\'")
        lines.append(f"    '{esc}',")
    lines.append("];")
    lines.append("")
    path.write_text("\n".join(lines), encoding="utf-8")


def load_seeds() -> dict[str, dict[int, list[str]]]:
    path = WORDS_DIR / "seeds.py"
    if not path.is_file():
        return {}
    ns: dict = {}
    exec(path.read_text(encoding="utf-8"), ns)
    raw = ns.get("SEEDS", {})
    return raw if isinstance(raw, dict) else {}


def assign_words() -> dict[str, dict[int, list[str]]]:
    """Global dedup with category priority; returns cat -> level -> [words]."""
    candidates: dict[str, tuple[str, int, str, int]] = {}

    def add_candidate(category: str, level: int, raw: str, prio: int) -> None:
        cleaned = clean_word(raw, level, category)
        if not cleaned:
            return
        key = cleaned.lower()
        prev = candidates.get(key)
        if prev is None:
            candidates[key] = (category, level, cleaned, prio)
            return
        _pc, pl, _pw, pp = prev
        if level < pl or (level == pl and prio < pp):
            candidates[key] = (category, level, cleaned, prio)

    for category, levels in load_seeds().items():
        if category not in TARGETS:
            continue
        max_lvl = MAX_LEVEL.get(category, DEFAULT_MAX)
        if not isinstance(levels, dict):
            continue
        for level, words in levels.items():
            level = int(level)
            if level > max_lvl or not isinstance(words, list):
                continue
            for raw in words:
                add_candidate(category, level, str(raw), -1)

    for category in TARGETS:
        max_lvl = MAX_LEVEL.get(category, DEFAULT_MAX)
        source = load_category_words(category)
        prio = CATEGORY_PRIORITY.get(category, 50)
        for level in range(1, max_lvl + 1):
            for raw in source.get(level, []):
                add_candidate(category, level, raw, prio)

    candidates = strip_auto_diminutives(candidates)
    candidates = strip_morph_families(candidates)

    grouped: dict[str, dict[int, list[str]]] = defaultdict(lambda: defaultdict(list))
    for _key, (cat, lvl, word, _p) in candidates.items():
        grouped[cat][lvl].append(word)

    for cat in grouped:
        for lvl in grouped[cat]:
            grouped[cat][lvl].sort(key=str.lower)

    return grouped


def pick_for_quotas(grouped: dict[str, dict[int, list[str]]]) -> tuple[dict[str, dict[int, list[str]]], int]:
    out: dict[str, dict[int, list[str]]] = {}
    total = 0
    global_seen: set[str] = set()

    for category, target in TARGETS.items():
        max_lvl = MAX_LEVEL.get(category, DEFAULT_MAX)
        quotas = allocate(target, max_lvl)
        out[category] = {}
        for level in range(1, max_lvl + 1):
            need = quotas.get(level, 0)
            pool = grouped.get(category, {}).get(level, [])
            picked: list[str] = []
            for w in pool:
                k = w.lower()
                if k in global_seen:
                    continue
                global_seen.add(k)
                picked.append(w)
                if len(picked) >= need:
                    break
            out[category][level] = picked
            total += len(picked)

    # Добираем до TARGET_TOTAL из остатка (нижние уровни в приоритете)
    if total < TARGET_TOTAL:
        overflow: list[tuple[int, str, str]] = []
        for category in TARGETS:
            max_lvl = MAX_LEVEL.get(category, DEFAULT_MAX)
            for level in range(1, max_lvl + 1):
                for w in grouped.get(category, {}).get(level, []):
                    if w.lower() not in global_seen:
                        overflow.append((level, category, w))
        overflow.sort(key=lambda x: (x[0], CATEGORY_PRIORITY.get(x[1], 50)))
        for level, category, w in overflow:
            if total >= TARGET_TOTAL:
                break
            k = w.lower()
            if k in global_seen:
                continue
            global_seen.add(k)
            out[category][level].append(w)
            total += 1

    return out, total


def main() -> int:
    grouped = assign_words()
    selected, total = pick_for_quotas(grouped)
    stats: list[str] = []

    for category in TARGETS:
        max_lvl = MAX_LEVEL.get(category, DEFAULT_MAX)
        cat_dir = OUT / category
        if cat_dir.exists():
            for old in cat_dir.glob("level_*.php"):
                n = int(old.stem.split("_")[1])
                if n > max_lvl:
                    old.unlink()
        quotas = allocate(TARGETS[category], max_lvl)
        for level in range(1, max_lvl + 1):
            words = selected.get(category, {}).get(level, [])
            write_php(cat_dir / f"level_{level}.php", words)
            need = quotas.get(level, 0)
            status = "OK" if len(words) >= max(20, need - 10) else "LOW"
            stats.append(f"  {category}/{level}: {len(words)}/{need} [{status}]")

    print("Dictionary build complete\n")
    print("\n".join(stats))
    print(f"\nTotal unique words: {total}")
    if total < 10_000:
        print("WARNING: below 10000 — expand back/scripts/words/*.py")
        return 1
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
