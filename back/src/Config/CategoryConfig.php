<?php

namespace App\Config;

final class CategoryConfig
{
    /**
     * @var array<string, string> slug => label
     */
    public const CATEGORIES = [
        'everyday' => 'Повседневность',
        'food' => 'Еда и напитки',
        'animals' => 'Животные',
        'nature' => 'Природа и погода',
        'movies' => 'Мультфильмы и кино',
        'places' => 'Места',
        'transport' => 'Транспорт',
        'clothes' => 'Одежда',
        'furniture' => 'Мебель',
        'profession' => 'Профессия',
        'school' => 'Школьная программа (5–11)',
        'celebrities' => 'Знаменитости',
        'feelings' => 'Чувства и ощущения',
        'sport' => 'Спорт',
        'tech' => 'Техника и гаджеты',
        'phrases' => 'Адекватные словосочетания',
        'random_phrases' => 'Случайные словосочетания',
    ];

    /**
     * Максимальный уровень сложности по категории (остальные файлы не нужны).
     *
     * @var array<string, int>
     */
    /** Все категории: 1–5 базовый пул, 6 — жёсткий. */
    public const MAX_LEVEL_BY_CATEGORY = [
        'everyday' => 6,
        'food' => 6,
        'animals' => 6,
        'nature' => 6,
        'movies' => 6,
        'places' => 6,
        'transport' => 6,
        'clothes' => 6,
        'furniture' => 6,
        'profession' => 6,
        'school' => 6,
        'celebrities' => 6,
        'feelings' => 6,
        'sport' => 6,
        'tech' => 6,
        'phrases' => 6,
        'random_phrases' => 6,
    ];

    public const MAX_LEVEL = 6;

    public static function maxLevelFor(string $slug): int
    {
        return self::MAX_LEVEL_BY_CATEGORY[$slug] ?? self::MAX_LEVEL;
    }

    /** @return string[] */
    public static function allSlugs(): array
    {
        return array_keys(self::CATEGORIES);
    }

    public static function isValid(string $slug): bool
    {
        return isset(self::CATEGORIES[$slug]);
    }
}
