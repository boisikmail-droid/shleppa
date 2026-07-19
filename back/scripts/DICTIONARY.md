# Словарь «Шляпа»

## Полный цикл пересборки

```bash
python back/scripts/words/gen_phrases.py
python back/scripts/sanitize_banks.py
python back/scripts/merge_curated.py
python back/scripts/inject_novel_words.py
python back/scripts/relevel_banks.py   # L1–5 из текущего пула + curated L6
python back/scripts/build_dictionary.py
php bin/console app:import-words
```

Уровни: **1–5** — весь существующий словарь, переразмеченный по сложности; **6** — отдельный жёсткий curated-банк (`words/hard_l6.py`).
Все категории поддерживают уровни 1–6.

### Категории фраз (3–4 слова)

| Slug | Название | Суть |
|------|----------|------|
| `phrases` | Адекватные словосочетания | Реальные бытовые / рабочие / академические фразы |
| `random_phrases` | Случайные словосочетания | Абсурдные, но согласованные по роду сцены (без склейки словарей) |

Уровни 1–6. Генератор адекватных: `gen_phrases.py`. Случайных: `rewrite_random_phrases.py` (только внутритематические пулы + согласование).
Ограничение: знаменательное слово на уровне ≤2–3 раза.
`phrases` — только осмысленные curated + семантические шаблоны.
Предлоги `в/на/с/...` не считаются.

### Отдельные шаги

| Скрипт | Назначение |
|--------|------------|
| `sanitize_banks.py` | Чистит исходники `words/*.py` фильтрами из `build_dictionary.py` |
| `merge_curated.py` | Подмешивает `mega_everyday`, `gen_words`, пулы и curated-списки |
| `inject_novel_words.py` | Добавляет уникальные слова из `novel_ru*.py` |
| `build_novel_ru3.py` | Пересобирает `novel_ru3.py` из кандидатов (по необходимости) |
| `build_dictionary.py` | Генерирует PHP в `back/src/Command/Data/` |

## Проверка существования слов

Банлист: `back/scripts/words/_banned_nonexistent.py` (подключается в `clean_word`).

Для повторной сверки скачай словари в `back/scripts/ext/` (папка в `.gitignore`):

- [danakt/russian-words](https://github.com/danakt/russian-words) → `russian_cp1251.txt`
- [hingston/russian](https://github.com/hingston/russian) → `russian_100k.txt`

```bash
python back/scripts/verify_existence.py
```

Категории `celebrities` / `movies` — имена собственные, в общем словаре не требуются.
`tech` с латиницей (Docker, NVMe…) допускается на высоких уровнях.

```bash
php bin/console app:import-words
```

Команда **очищает** `word_pool` и заливает словарь заново.  
Также сбрасывает `turn_log` и `round_progress` — делайте только когда нет нужных активных игр.

## Дополнительные слова (без вайпа)

```bash
php bin/console app:import-extra-words
```

Файлы: `back/src/Command/Data/extra/{category}/level_N.php`

## Параметры словаря

- **~13 600** уникальных слов (цель расширения — до 15 000)
- Распределение по уровням — «горб» на 2–4
- **Одежда, мебель, знаменитости** — только уровни 1–3
- **Спорт, места, транспорт** — до 4
- **ЖОСКИЕ (6)** — латиница и Docker/NVMe только в `tech/level_6`
- Фильтры убирают склеенные уменьшительные (`батоник`, `вафлка`) и латиницу в простых уровнях

## Категории

| Slug | Название | Макс. уровень |
|------|----------|---------------|
| everyday | Повседневность | 6 |
| food | Еда и напитки | 6 |
| animals | Животные | 5 |
| nature | Природа и погода | 5 |
| movies | Кино и мультики | 5 |
| school | Школьная программа | 6 |
| profession | Профессия | 5 |
| feelings | Чувства | 5 |
| sport | Спорт | 4 |
| tech | Техника | 6 |
| transport | Транспорт | 4 |
| places | Места | 4 |
| clothes | Одежда | 3 |
| furniture | Мебель | 3 |
| celebrities | Знаменитости | 3 |
| phrases | Адекватные словосочетания | 5 |
| random_phrases | Случайные словосочетания | 5 |

## Редактирование

Исходники: `back/scripts/words/{category}.py` — словарь `LEVELS = {1: [...], ...}`.  
Якорные простые слова: `back/scripts/words/seeds.py` — попадают в словарь первыми.

После правок снова запустите цикл пересборки, затем `app:import-words`.
