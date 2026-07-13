<?php

namespace App\Entity;

use App\Repository\GameTurnRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: GameTurnRepository::class)]
#[ORM\Table(name: 'game_turn')]
class GameTurn
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

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Player $player;

    #[ORM\Column]
    private int $round = 1;

    #[ORM\Column]
    private bool $isFinished = false;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    /** @var Collection<int, TurnLog> */
    #[ORM\OneToMany(mappedBy: 'gameTurn', targetEntity: TurnLog::class, cascade: ['persist'])]
    private Collection $turnLogs;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->turnLogs = new ArrayCollection();
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

    public function isFinished(): bool
    {
        return $this->isFinished;
    }

    public function setIsFinished(bool $isFinished): static
    {
        $this->isFinished = $isFinished;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    /** @return Collection<int, TurnLog> */
    public function getTurnLogs(): Collection
    {
        return $this->turnLogs;
    }

    public function addTurnLog(TurnLog $turnLog): static
    {
        if (!$this->turnLogs->contains($turnLog)) {
            $this->turnLogs->add($turnLog);
            $turnLog->setGameTurn($this);
        }

        return $this;
    }
}
