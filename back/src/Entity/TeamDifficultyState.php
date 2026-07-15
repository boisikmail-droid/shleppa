<?php

namespace App\Entity;

use App\Config\DifficultyConfig;
use App\Repository\TeamDifficultyStateRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TeamDifficultyStateRepository::class)]
#[ORM\Table(name: 'team_difficulty_state')]
#[ORM\UniqueConstraint(name: 'uniq_session_team_round', columns: ['session_id', 'team_id', 'round'])]
class TeamDifficultyState
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private GameSession $session;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Team $team;

    #[ORM\Column]
    private int $round = 1;

    #[ORM\Column]
    private int $currentDifficulty = 1;

    #[ORM\Column]
    private int $wordsGuessedInCycle = 0;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSession(): GameSession
    {
        return $this->session;
    }

    public function setSession(GameSession $session): static
    {
        $this->session = $session;

        return $this;
    }

    public function getTeam(): Team
    {
        return $this->team;
    }

    public function setTeam(Team $team): static
    {
        $this->team = $team;

        return $this;
    }

    public function getRound(): int
    {
        return $this->round;
    }

    public function setRound(int $round): static
    {
        $this->round = $round;

        return $this;
    }

    public function getCurrentDifficulty(): int
    {
        return $this->currentDifficulty;
    }

    public function setCurrentDifficulty(int $currentDifficulty): static
    {
        $this->currentDifficulty = $currentDifficulty;

        return $this;
    }

    public function getWordsGuessedInCycle(): int
    {
        return $this->wordsGuessedInCycle;
    }

    public function setWordsGuessedInCycle(int $wordsGuessedInCycle): static
    {
        $this->wordsGuessedInCycle = $wordsGuessedInCycle;

        return $this;
    }

    public function applyGuessDifficultyUpdate(): void
    {
        $cycle = $this->session->getDifficultyCycle();
        $len = max(1, count($cycle));

        $this->wordsGuessedInCycle++;

        if ($this->wordsGuessedInCycle >= $len) {
            $this->wordsGuessedInCycle = 0;
        }

        $this->currentDifficulty = DifficultyConfig::difficultyForCycleIndex(
            $cycle,
            $this->wordsGuessedInCycle
        );
    }
}
