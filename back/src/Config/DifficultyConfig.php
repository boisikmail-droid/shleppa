<?php

namespace App\Config;

final class DifficultyConfig
{
    public const MAX_LEVEL = 7;

    public const CYCLE_LENGTH = 10;

    /** Количество слов в игре должно делиться на это число. */
    public const DISTRIBUTION_DIVISOR = 10;

    /**
     * Последовательность уровней в одном цикле (10 угадываний).
     * Уровни 3, 5 и 6 встречаются по два раза.
     *
     * @var int[]
     */
    public const CYCLE_SEQUENCE = [1, 2, 3, 3, 4, 5, 5, 6, 6, 7];

    /**
     * Вес уровня при формировании пула игры (сумма = 10).
     *
     * @var array<int, int>
     */
    public const POOL_WEIGHTS = [
        1 => 1,
        2 => 1,
        3 => 2,
        4 => 1,
        5 => 2,
        6 => 2,
        7 => 1,
    ];

    public static function difficultyForCycleIndex(int $wordsGuessedInCycle): int
    {
        $index = $wordsGuessedInCycle % self::CYCLE_LENGTH;

        return self::CYCLE_SEQUENCE[$index];
    }

    public static function cycleLength(): int
    {
        return self::CYCLE_LENGTH;
    }
}
