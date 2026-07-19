<?php

namespace App\DTO;

use App\Config\CategoryConfig;
use App\Config\DifficultyConfig;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class StartSessionRequest
{
    /**
     * @var list<array{name?: string, players?: list<string|array{name?: string, avatar_id?: string}>, hat_id?: string}>
     */
    #[Assert\Count(min: 2, max: 4)]
    public array $teams = [];

    #[Assert\Range(min: DifficultyConfig::MIN_WORDS, max: DifficultyConfig::MAX_WORDS)]
    public int $totalWords = 60;

    #[Assert\Range(min: DifficultyConfig::MIN_TIME, max: DifficultyConfig::MAX_TIME)]
    public int $timeLimit = 60;

    /** @var int[] */
    #[Assert\Count(min: 1)]
    public array $difficulties = [];

    /** @var string[] */
    public array $categories = [];

    /** 0 = штраф отключён */
    #[Assert\Range(min: 0, max: 5)]
    public int $skipPenalty = 2;

    public bool $lastWordCommon = true;

    #[Assert\Callback]
    public function validateTeams(ExecutionContextInterface $context): void
    {
        foreach ($this->teams as $i => $team) {
            $name = trim((string) ($team['name'] ?? ''));
            if ($name === '' || mb_strlen($name) > 100) {
                $context->buildViolation('Укажите название каждой команды (до 100 символов).')
                    ->atPath('teams['.$i.'].name')
                    ->addViolation();
            }

            $players = $team['players'] ?? [];
            if (!is_array($players) || count($players) < 1 || count($players) > 10) {
                $context->buildViolation('В каждой команде должно быть от 1 до 10 игроков.')
                    ->atPath('teams['.$i.'].players')
                    ->addViolation();
                continue;
            }

            foreach ($players as $p) {
                if (is_array($p)) {
                    $playerName = trim((string) ($p['name'] ?? ''));
                } else {
                    $playerName = trim((string) $p);
                }
                if ($playerName === '' || mb_strlen($playerName) > 100) {
                    $context->buildViolation('Имена игроков не должны быть пустыми.')
                        ->atPath('teams['.$i.'].players')
                        ->addViolation();
                    break;
                }
            }
        }
    }

    #[Assert\Callback]
    public function validateFilters(ExecutionContextInterface $context): void
    {
        $validDiffs = DifficultyConfig::allLevelIds();
        foreach ($this->difficulties as $d) {
            if (!in_array((int) $d, $validDiffs, true)) {
                $context->buildViolation('Некорректный уровень сложности.')
                    ->atPath('difficulties')
                    ->addViolation();
                break;
            }
        }

        foreach ($this->categories as $c) {
            if (!CategoryConfig::isValid((string) $c)) {
                $context->buildViolation('Некорректная категория слов.')
                    ->atPath('categories')
                    ->addViolation();
                break;
            }
        }

        if ($this->categories === []) {
            $context->buildViolation('Выберите хотя бы одну категорию слов.')
                ->atPath('categories')
                ->addViolation();
        }
    }
}
