<?php

namespace App\DTO;

use App\Config\DifficultyConfig;
use Symfony\Component\Validator\Constraints as Assert;

class StartSessionRequest
{
    #[Assert\NotBlank]
    #[Assert\Length(min: 1, max: 100)]
    public string $team1Name = '';

    /** @var string[] */
    #[Assert\Count(min: 1, max: 10)]
    #[Assert\All([
        new Assert\NotBlank(),
        new Assert\Length(min: 1, max: 100),
    ])]
    public array $team1Players = [];

    #[Assert\NotBlank]
    #[Assert\Length(min: 1, max: 100)]
    public string $team2Name = '';

    /** @var string[] */
    #[Assert\Count(min: 1, max: 10)]
    #[Assert\All([
        new Assert\NotBlank(),
        new Assert\Length(min: 1, max: 100),
    ])]
    public array $team2Players = [];

    #[Assert\Range(min: 30, max: 150)]
    #[Assert\DivisibleBy(DifficultyConfig::DISTRIBUTION_DIVISOR)]
    public int $totalWords = 0;

    #[Assert\Range(min: 45, max: 80)]
    public int $timeLimit = 0;
}
