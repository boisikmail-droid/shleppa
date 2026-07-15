<?php

namespace App\Config;

/**
 * Сложности 1–6 и веса Гаусса для пула / цикла хода.
 */
final class DifficultyConfig
{
    public const MAX_LEVEL = 6;

    public const MIN_WORDS = 40;
    public const MAX_WORDS = 100;
    public const MIN_TIME = 20;
    public const MAX_TIME = 90;

    /**
     * @var array<int, array{id: int, label: string, examples: string}>
     */
    public const LEVELS = [
        1 => ['id' => 1, 'label' => 'Очень простые', 'examples' => 'кот, стол, дом'],
        2 => ['id' => 2, 'label' => 'Простые', 'examples' => 'шкаф, куртка, врач'],
        3 => ['id' => 3, 'label' => 'Простые, но уже не для идиотов', 'examples' => 'эскалатор, фломастер'],
        4 => ['id' => 4, 'label' => 'Средние', 'examples' => 'амфитеатр, гироскоп'],
        5 => ['id' => 5, 'label' => 'Сложные', 'examples' => 'консенсус, квантор'],
        6 => ['id' => 6, 'label' => 'ЖОСКИЕ', 'examples' => 'эпистемология, олигополия'],
    ];

    /** @return int[] */
    public static function allLevelIds(): array
    {
        return array_keys(self::LEVELS);
    }

    /**
     * Дискретные веса нормального распределения по выбранным сложностям.
     * Сумма весов = 1.0.
     *
     * @param int[] $selected отсортированные уникальные уровни 1–6
     *
     * @return array<int, float> difficulty => weight
     */
    public static function gaussianWeights(array $selected): array
    {
        $selected = array_values(array_unique(array_map('intval', $selected)));
        sort($selected);

        if ($selected === []) {
            $selected = self::allLevelIds();
        }

        $n = count($selected);
        if ($n === 1) {
            return [$selected[0] => 1.0];
        }

        $center = ($n - 1) / 2.0;
        $sigma = max(0.75, $n / 3.5);
        $raw = [];
        $sum = 0.0;

        foreach ($selected as $i => $diff) {
            $x = $i - $center;
            $w = exp(-0.5 * ($x / $sigma) * ($x / $sigma));
            $raw[$diff] = $w;
            $sum += $w;
        }

        $weights = [];
        foreach ($raw as $diff => $w) {
            $weights[$diff] = $w / $sum;
        }

        return $weights;
    }

    /**
     * Целые квоты суммой $total (largest remainder method).
     *
     * @param array<int, float> $weights
     *
     * @return array<int, int>
     */
    public static function allocateCounts(array $weights, int $total): array
    {
        if ($total <= 0 || $weights === []) {
            return [];
        }

        $floors = [];
        $remainders = [];
        $assigned = 0;

        foreach ($weights as $key => $w) {
            $exact = $w * $total;
            $floor = (int) floor($exact);
            $floors[$key] = $floor;
            $remainders[$key] = $exact - $floor;
            $assigned += $floor;
        }

        $left = $total - $assigned;
        arsort($remainders);
        foreach (array_keys($remainders) as $key) {
            if ($left <= 0) {
                break;
            }
            ++$floors[$key];
            --$left;
        }

        return $floors;
    }

    /**
     * Цикл хода длины ~10 с «горбом» по выбранным сложностям (легче → чаще середина).
     *
     * @param int[] $selected
     *
     * @return int[]
     */
    public static function buildCycleSequence(array $selected): array
    {
        $selected = array_values(array_unique(array_map('intval', $selected)));
        sort($selected);

        if ($selected === []) {
            $selected = self::allLevelIds();
        }

        $targetLen = max(count($selected), 10);
        $weights = self::gaussianWeights($selected);
        $counts = self::allocateCounts($weights, $targetLen);

        $sequence = [];
        foreach ($counts as $diff => $count) {
            for ($k = 0; $k < $count; ++$k) {
                $sequence[] = (int) $diff;
            }
        }

        sort($sequence);

        return $sequence !== [] ? $sequence : [$selected[0]];
    }

    /**
     * @param int[] $cycle
     */
    public static function difficultyForCycleIndex(array $cycle, int $wordsGuessedInCycle): int
    {
        if ($cycle === []) {
            return 1;
        }

        $index = $wordsGuessedInCycle % count($cycle);

        return $cycle[$index];
    }
}
