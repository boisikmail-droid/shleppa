<?php

namespace App\Entity;

use App\Repository\TurnLogRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TurnLogRepository::class)]
#[ORM\Table(name: 'turn_log')]
#[ORM\Index(name: 'idx_session_player_created', columns: ['session_id', 'player_id', 'created_at'])]
class TurnLog
{
    public const STATUS_GUESSED = 'guessed';
    public const STATUS_SKIPPED = 'skipped';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private GameSession $session;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private Team $team;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Player $player;

    #[ORM\Column]
    private int $round = 1;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private Word $word;

    #[ORM\Column(length: 20)]
    private string $status = self::STATUS_GUESSED;

    #[ORM\Column]
    private bool $wasCorrected = false;

    #[ORM\ManyToOne(inversedBy: 'turnLogs')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private GameTurn $gameTurn;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

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

    public function getPlayer(): Player
    {
        return $this->player;
    }

    public function setPlayer(Player $player): static
    {
        $this->player = $player;

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

    public function getWord(): Word
    {
        return $this->word;
    }

    public function setWord(Word $word): static
    {
        $this->word = $word;

        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function wasCorrected(): bool
    {
        return $this->wasCorrected;
    }

    public function setWasCorrected(bool $wasCorrected): static
    {
        $this->wasCorrected = $wasCorrected;

        return $this;
    }

    public function getGameTurn(): GameTurn
    {
        return $this->gameTurn;
    }

    public function setGameTurn(GameTurn $gameTurn): static
    {
        $this->gameTurn = $gameTurn;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
