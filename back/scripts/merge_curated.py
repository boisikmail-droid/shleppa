#!/usr/bin/env python3
"""Merge curated + supplemental vocabulary into back/scripts/words/*.py banks."""
from __future__ import annotations

import importlib.util
import re
import sys
from pathlib import Path

SCRIPTS = Path(__file__).resolve().parent
WORDS_DIR = SCRIPTS / "words"
sys.path.insert(0, str(SCRIPTS))
from build_dictionary import clean_word, TARGETS, MAX_LEVEL, DEFAULT_MAX  # noqa: E402


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


def _load_mega_everyday() -> dict[int, list[str]]:
    path = WORDS_DIR / "mega_everyday.py"
    spec = importlib.util.spec_from_file_location("mega_everyday", path)
    if spec is None or spec.loader is None:
        return {}
    mod = importlib.util.module_from_spec(spec)
    spec.loader.exec_module(mod)
    mapping = {3: "MEGA_E3", 4: "MEGA_E4", 5: "MEGA_E5", 6: "MEGA_E6"}
    out: dict[int, list[str]] = {}
    for lvl, attr in mapping.items():
        raw = getattr(mod, attr, "")
        if isinstance(raw, str):
            out[lvl] = _w(raw)
    return out


def _load_food_pool() -> dict[int, list[str]]:
    path = WORDS_DIR / "word_pools.py"
    spec = importlib.util.spec_from_file_location("word_pools", path)
    if spec is None or spec.loader is None:
        return {}
    mod = importlib.util.module_from_spec(spec)
    spec.loader.exec_module(mod)
    pool = getattr(mod, "FOOD_POOL", {})
    return {int(k): list(v) for k, v in pool.items() if isinstance(v, list)}


def _load_gen_words() -> tuple[dict[int, list[str]], dict[int, list[str]]]:
    path = WORDS_DIR / "gen_words.py"
    spec = importlib.util.spec_from_file_location("gen_words", path)
    if spec is None or spec.loader is None:
        return {}, {}
    mod = importlib.util.module_from_spec(spec)
    spec.loader.exec_module(mod)
    everyday = mod.dedupe_global(mod.build_everyday())
    food = mod.dedupe_global(mod.build_food())
    return (
        {int(k): list(v) for k, v in everyday.items()},
        {int(k): list(v) for k, v in food.items()},
    )


CURATED: dict[str, dict[int, list[str]]] = {
    "sport": {
        2: _w("""
            полузащитник нападающий защитник вратарь либеро форвард вингер опорный атакующий
            ракетка сетка разметка свисток фальстарт эстафета подача приём удар пас передача
            навес прострел пенальти штрафной угловой аут вбрасывание офсайд фол прессинг
            контратака бросок шайба перекладина буллит овертайм сет гейм тайбрейк эйс дюс
            смэш дропшот лоб блок атака защита дриблинг ведение обводка подбор отскок
            захват бросок удержание подсечка туше укол выпад парирование клинч нокдаун нокаут
            мишень выстрел попадание промах квалификация разминка заминка растяжка спарринг
            жеребьёвка посев протокол заявка допуск расписание период тайм таймаут замена
            аутсайдер фаворит андердог лидер аутсайдер претендент соискатель участник
            полуфиналист финалист чемпион вицечемпион призёр медалист рекордсмен
            """),
        3: _w("""
            четвертьфинал полуфинал плейофф суперкубок дерби реванш переигровка разгром камбэк
            апелляция дисквалификация видеопросмотр фотофиниш тактика схема расстановка
            катеначчо тикитака гегенпрессинг полупространство штрафная автогол сухойматч
            хеттрик дубль покер супергол суперсейв буллит дополнительноевремя эйсбрейк
            матчпоинт супертайбрейк крученаяподача доигровщик связующий диагнональный
            допингконтроль офсайдловушка зональнаязащита персональнаязащита контрпрессинг
            плеймейкер диспетчер разрушитель бокстубокс позиционнаяигра вертикальныйпас
            быстрыйпереход медленныйпереход фланговаяатака золотойгол серияпенальти
            чемпионскийтай единогласноерешение рассечённоерешение техническаяничья
            аутсайдер фаворит андердог лидер претендент соискатель участник полуфиналист
            финалист вицечемпион медалист рекордсмен олимпиец паралимпиец юниор ветеран
            """),
        4: _w("""
            гегенпрессинг контрпрессинг зональнаязащита персональнаязащита офсайдловушка
            плеймейкер диспетчер разрушитель бокстубокс позиционнаяигра вертикальныйпас
            быстрыйпереход медленныйпереход фланговаяатака золотойгол серияпенальти
            чемпионскийтай единогласноерешение рассечённоерешение техническаяничья
            допингконтроль видеоассистент судейство делегат инспектор комиссар дисциплина
            """),
    },
    "school": {
        3: _w("""
            грамматика орфография пунктуация морфология синтаксис лексика фразеология
            стилистика риторика литературоведение поэтика проза драматургия комедия трагедия
            повесть очерк эссе фельетон памфлет аллегория метафора эпитет олицетворение
            гипербола аллитерация ассонанс рифма строфа сонет баллада эпос лирика пародия
            алгебра геометрия тригонометрия стереометрия производная интеграл предел функция
            вектор матрица определитель прогрессия механика кинематика динамика оптика акустика
            термодинамика электродинамика молекула катализ концентрация изомер полимер
            клетка ткань организм эволюция генетика экология биосфера экосистема популяция
            """),
        4: _w("""
            философия логика этика эстетика диалектика социология политология экономика
            менеджмент маркетинг правоведение конституция парламент федерация демократия
            алгоритм компилятор интерпретатор операционка базаданных протокол архитектура
            психология восприятие внимание мотивация темперамент интеллект креативность
            педагогика дидактика методика социализация
            """),
        5: _w("""
            аксиоматика теорема лемма королларий индукция дедукция постулат формализация
            топология биекция инъекция суръекция композиция градиент якобиан гессиан
            лапласиан диффур криволинейный поверхностный комплексное мнимая модуль аргумент
            квантовая волноваяфункция оператор гамильтониан спин
            """),
        6: _w("""
            эпистемология феноменология экзистенциализм прагматизм структурализм постмодернизм
            деконструкция герменевтика семиотика
            """),
    },
    "profession": {
        1: _w("""
            сантехник электрик сварщик монтажник каменщик плотник кровельщик штукатур облицовщик
            плиточник стекольщик паркетчик садовник дворник клинер горничная гувернантка сиделка
            фельдшер акушерка гинеколог терапевт хирург стоматолог окулист кардиолог невролог
            педиатр психиатр психотерапевт дефектолог соцработник юрист адвокат нотариус судья
            прокурор следователь детектив телохранитель ревизор финансист аудитор брокер трейдер
            аналитик рекламщик иллюстратор аниматор монтажёр звукорежиссёр композитор корреспондент
            обозреватель критик архивариус экскурсовод гид лингвист филолог археолог антрополог
            картограф астроном штурман бортпроводник диспетчер проводник грузчик кладовщик
            комплектовщик упаковщик малярщик токарь фрезеровщик наладчик шлифовщик
            """),
        2: _w("""
            нейрохирург кардиохирург онколог эндокринолог гастроэнтеролог нефролог уролог
            ортопед травматолог реаниматолог анестезиолог рентгенолог патологоанатом эпидемиолог
            микробиолог биохимик фармацевт провизор фармаколог токсиколог криминалист судмедэксперт
            юристконсульт арбитр медиатор дознаватель оперативник налоговыйинспектор таможенник
            андеррайтер актуарий рискменеджер венчурныйинвестор
            """),
        3: _w("""
            трансплантолог репродуктолог клиническийгенетик иммунолог аллерголог ревматолог
            пульмонолог гематолог онкогематолог радиолог физиотерапевт реабилитолог эрготерапевт
            нутрициолог диетолог остеопат криптограф кибернетик разработчик тестировщик
            градостроитель урбанист ландшафтныйдизайнер
            """),
        4: _w("""
            нейроофтальмолог нейроотолог психоаналитик психофармаколог клиническийфармаколог
            криптограф квантовыйфизик астрофизик теоретик экспериментатор
            """),
        5: _w("""
            квантовыйфизик космолог физикэлементарныхчастиц клиническийгенетик трансплантолог
            """),
    },
    "tech": {
        2: _w("""
            блокпитания кулер радиатор термопаста звуковаякарта сетеваякарта точкадоступа репитер
            файрвол сервер стойка патчкорд кросс оптика интерактивнаядоска лазерный струйный
            матричный термопринтер этикеточный планшетный протяжной трёхмерныйпринтер
            """),
        3: _w("""
            драйвер прошивка биос уефи загрузчик ядро файловаясистема форматирование дефрагментация
            архивация резервноекопирование синхронизация облако локальнаясеть беспроводнаясеть
            маршрутизатор брандмауэр проксисервер виртуализация контейнер образ реестр оркестратор
            кластер балансировщик
            """),
        4: _w("""
            микросервис контейнеризация оркестрация непрерывнаяинтеграция непрерывнаядоставка
            конвейер развёртывание мониторинг логирование трассировка метрики алертинг дашборд
            машинноеобучение нейросеть признак модель
            """),
        5: _w("""
            квантовыйкомпьютер кубит запутанность суперпозиция декогеренция блокчейн реестр
            консенсус смартконтракт токен криптография шифрование хеш подпись сертификат
            """),
        6: _w("""
            Docker Kubernetes NVMe SSD GPU CPU API SDK JSON HTML CSS JavaScript Python Linux Windows
            Bluetooth WiFi USB HDMI GitHub nginx Apache MySQL PostgreSQL Redis MongoDB Elasticsearch
            Kafka RabbitMQ gRPC REST GraphQL OAuth JWT DevOps Terraform Ansible Prometheus Grafana
            Sentry microservice container orchestration machine learning neural network deep learning
            transformer LLM GPT
            """),
    },
    "feelings": {
        2: _w("""
            восторг умиление нежность трепет волнение беспокойство тревога паника ужас испуг робость
            застенчивость смущение стыд вина сожаление обида раздражительность ярость бешенство
            негодование презрение ненависть отвращение антипатия равнодушие апатия скука тоска
            меланхолия уныние отчаяние безнадёжность
            """),
        3: _w("""
            эйфория катарсис экстаз транс нирвана просветление озарение инсайт эмпатия сострадание
            сочувствие сопереживание благодарность признательность ревность зависть недоверие
            подозрительность паранойя тревожность
            """),
    },
    "furniture": {
        1: _w("""
            тумба прикроватная журнальный обеденный письменный компьютерный книжный буфет сервант
            витрина этажерка стеллаж рейл обувница тумбочка
            """),
    },
}


def all_extra(category: str) -> dict[int, list[str]]:
    extra: dict[int, list[str]] = {}
    for lvl, words in CURATED.get(category, {}).items():
        extra.setdefault(lvl, []).extend(words)
    gen_e, gen_f = _load_gen_words()
    if category == "everyday":
        for lvl, words in _load_mega_everyday().items():
            extra.setdefault(lvl, []).extend(words)
        for lvl, words in gen_e.items():
            extra.setdefault(lvl, []).extend(words)
    if category == "food":
        for lvl, words in _load_food_pool().items():
            extra.setdefault(lvl, []).extend(words)
        for lvl, words in gen_f.items():
            extra.setdefault(lvl, []).extend(words)
    return extra


def merge_into_bank(category: str) -> tuple[int, int]:
    path = WORDS_DIR / f"{category}.py"
    if not path.exists():
        return 0, 0
    ns: dict = {}
    exec(path.read_text(encoding="utf-8"), ns)
    levels: dict = ns.get("LEVELS", {})
    if not isinstance(levels, dict):
        return 0, 0

    max_lvl = MAX_LEVEL.get(category, DEFAULT_MAX)
    extra = all_extra(category)
    seen: set[str] = set()
    merged: dict[int, list[str]] = {}

    for lvl in range(1, max_lvl + 1):
        out: list[str] = []
        for source in (levels.get(lvl, []), extra.get(lvl, [])):
            for w in source:
                c = clean_word(str(w).strip(), lvl, category)
                if not c:
                    continue
                k = c.lower()
                if k in seen:
                    continue
                seen.add(k)
                out.append(c)
        if out:
            merged[lvl] = out

    before = sum(len(v) for v in levels.values() if isinstance(v, list))
    after = sum(len(v) for v in merged.values())

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
    return before, after


def main() -> int:
    categories = sorted(set(CURATED.keys()) | {"everyday", "food"})
    total_b = total_a = 0
    for cat in categories:
        if cat not in TARGETS:
            continue
        b, a = merge_into_bank(cat)
        print(f"{cat}: {b} -> {a} (+{a - b})")
        total_b += b
        total_a += a
    print(f"Total: {total_b} -> {total_a}")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
