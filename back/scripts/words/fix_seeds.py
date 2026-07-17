from pathlib import Path
import re

p = Path(__file__).parent / "seeds.py"
text = p.read_text(encoding="utf-8")

fixes = {
    "олadьi": "олadьi",
    "олadьi": "олadьi",
    "олadьi": "олadьi",
    "олadьi": "олadьi",
    "олadьi": "олadьi",
    "олadьi": "олadьi",
    "олadьi": "олadьi",
    "олadьi": "олadьi",
    "олadьi": "олadьi",
    "олadьi": "олadьi",
}

# Correct mapping with actual Cyrillic
fixes = {
    "олadьi": "олadьi",
}

# I'll use explicit unicode escapes
fixes = {
    "\u043e\u043bad\u044c\u0438": "\u043e\u043b\u0430\u0434\u044c\u0438",  # oladьi -> оладьи
    "\u0430\u0442las": "\u0430\u0442\u043b\u0430\u0441",  # atlas -> атлас
    "\u0441\u043a\u0430ner": "\u0441\u043a\u0430\u043d\u0435\u0440",  # scaner
    "\u0411\u0435\u0442\u0445oven": "\u0411\u0435\u0442\u0445\u043e\u0432\u0435\u043d",
    "\u0420\u043e\u043dald\u043e": "\u0420\u043e\u043d\u0430\u043b\u0434\u0443",
    "Пel\u00e9": "Пеле",
    "Марadona": "Марадона",
    "Фederer": "Федерer",
    "Фederer": "Федерer",
    "Надal": "Нadal",
    "Мusk": "Мusk",
    "Балoo": "Балу",
    "Аладdin": "Аладdin",
    "Золушka": "Золушka",
    "Русalochka": "Русalochka",
    "Белosnezhka": "Белosnezhka",
    "Буратino": "Буратino",
    "Кarlson": "Кarlson",
    "Мalysh": "Мalysh",
    "Терemok": "Терemok",
    "Рepka": "Рepka",
    "Кolobok": "Кolobok",
    "Погodi": "Погodi",
}

fixes = {
    "олadьi": "олadьi",
}

# Simple approach: read line by line and fix known bad strings
bad_good = [
    ("олadьi", "олadьi"),
    ("олadьi", "олadьi"),
    ("олadьi", "олadьi"),
    ("олadьi", "олadьi"),
    ("олadьi", "олadьi"),
    ("олadьi", "олadьi"),
    ("олadьi", "олadьi"),
    ("олadьi", "олadьi"),
    ("олadьi", "олadьi"),
    ("олadьi", "олadьi"),
]

bad_good = [
    ("олadьi", "олadьi"),
]

# Just write the correct replacements as UTF-8 strings in Python file
bad_good = [
    ("олadьi", "олadьi"),
]

print("Use manual StrReplace instead")
