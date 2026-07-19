# -*- coding: utf-8 -*-
import re
import sys

sys.stdout.reconfigure(encoding="utf-8")

text = open(r"C:\vibe\hat1\reviews_utf8.txt", encoding="utf-8").read()
chunks = [c.strip() for c in re.split(r"---\d+---\n", text)[1:]]
if chunks[0].lower().startswith("комментарий"):
    chunks = chunks[1:]


def n(s):
    s = s.lower().replace("&#34;", '"').replace("\n", " ")
    return re.sub(r"\s+", " ", s)


checks = {
    "app mention": lambda t: any(x in t for x in ["приложен", "сайт", "лк", "личн"]),
    "app/site + bad": lambda t: any(x in t for x in ["приложен", "сайт", "лк", "личн"])
    and any(
        x in t
        for x in [
            "завис", "висит", "глюч", "вылет", "сбо", "ошиб", "лага", "тормоз",
            "не работ", "плох", "ужас", "отврат", "говн", "крив", "выкид",
        ]
    ),
    "neudob": lambda t: ("неудоб" in t) or ("не удоб" in t),
    "docs+time": lambda t: any(x in t for x in ["документ", "соглас", "правк", "пакет"])
    and any(x in t for x in ["долг", "недел", "месяц", "час", "ждал", "ждали", "72"]),
    "positive short": lambda t: len(t) <= 50
    and any(
        x in t
        for x in [
            "удобно", "отлично", "супер", "хорошо", "спасибо", "ок", "понятно",
            "быстро", "понравил", "гуд", "норм",
        ]
    )
    and not any(
        x in t
        for x in ["ужас", "отврат", "плох", "завис", "глюч", "навяз", "неудоб"]
    ),
    "govno/uzhas app": lambda t: any(x in t for x in ["приложен", "сайт"])
    and any(x in t for x in ["говн", "ужас", "отврат", "крив", "кошмар"]),
    "keys/cell": lambda t: any(x in t for x in ["ключ", "ячейк", "ящик", "постомат", "постамат"]),
    "navyaz": lambda t: any(x in t for x in ["навяз", "страхов", "оценк"]),
}

for name, fn in checks.items():
    hits = [c for c in chunks if fn(n(c))]
    print(f"{name}: {len(hits)}")

print("total", len(chunks))
