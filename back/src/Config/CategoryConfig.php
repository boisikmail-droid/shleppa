<?php

namespace App\Config;

final class CategoryConfig
{
    /**
     * @var array<string, string> slug => label
     */
    public const CATEGORIES = [
        'clothes' => 'Одежда',
        'furniture' => 'Мебель',
        'profession' => 'Профессия',
        'animals' => 'Животные',
        'school' => 'Школьная программа (5–11)',
        'celebrities' => 'Знаменитости',
        'movies' => 'Мультфильмы и кино',
        'feelings' => 'Чувства и ощущения',
        'food' => 'Еда и напитки',
        'sport' => 'Спорт',
        'tech' => 'Техника и гаджеты',
        'nature' => 'Природа и погода',
    ];

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
