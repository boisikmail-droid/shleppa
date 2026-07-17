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
    public const MAX_LEVEL_BY_CATEGORY = [
        'clothes' => 3,
        'furniture' => 3,
        'celebrities' => 3,
        'sport' => 4,
        'places' => 4,
        'transport' => 4,
        'animals' => 5,
        'nature' => 5,
        'movies' => 5,
        'profession' => 5,
        'feelings' => 5,
        'everyday' => 6,
        'food' => 6,
        'school' => 6,
        'tech' => 6,
        'phrases' => 5,
        'random_phrases' => 5,
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
