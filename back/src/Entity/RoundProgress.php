<?php

namespace App\Entity;

use App\Repository\RoundProgressRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RoundProgressRepository::class)]
#[ORM\Table(name: 'round_progress')]
#[ORM\UniqueConstraint(name: 'uniq_session_word_round', columns: ['session_id', 'word_id', 'round'])]
#[ORM\Index(name: 'idx_session_round_guessed', columns: ['session_id', 'round', 'is_guessed_in_this_round'])]
class RoundProgress
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private GameSession $session;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private Word $word;

    #[ORM\Column]
    private int $round = 1;

    #[ORM\Column]
    private bool $isGuessedInThisRound = false;

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

    public function getWord(): Word
    {
        return $this->word;
    }

    public function setWord(Word $word): static
    {
        $this->word = $word;

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

    public function isGuessedInThisRound(): bool
    {
        return $this->isGuessedInThisRound;
    }

    public function setIsGuessedInThisRound(bool $isGuessedInThisRound): static
    {
        $this->isGuessedInThisRound = $isGuessedInThisRound;

        return $this;
    }
}
