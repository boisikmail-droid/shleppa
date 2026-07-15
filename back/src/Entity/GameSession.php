<?php

namespace App\Entity;

use App\Config\DifficultyConfig;
use App\Repository\GameSessionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: GameSessionRepository::class)]
#[ORM\Table(name: 'game_session')]
#[ORM\Index(name: 'idx_session_status', columns: ['status'])]
class GameSession
{
    public const STATUS_LOBBY = 'lobby';
    public const STATUS_ROUND1 = 'round1_words';
    public const STATUS_ROUND2 = 'round2_gestures';
    public const STATUS_ROUND3 = 'round3_oneword';
    public const STATUS_FINISHED = 'finished';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    private string $status = self::STATUS_LOBBY;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Team $currentTeam = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Player $currentPlayer = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Team $roundStartTeam = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Player $roundStartPlayer = null;

    #[ORM\Column]
    private int $totalWordsCount = 60;

    #[ORM\Column]
    private int $turnTimeLimit = 60;

    /** @var int[] */
    #[ORM\Column(type: Types::JSON)]
    private array $wordsData = [];

    /**
     * Выбранные сложности, категории и цикл хода.
     *
     * @var array{difficulties?: int[], categories?: string[], cycle?: int[]}
     */
    #[ORM\Column(type: Types::JSON)]
    private array $settings = [];

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    /** @var Collection<int, Team> */
    #[ORM\OneToMany(mappedBy: 'session', targetEntity: Team::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['id' => 'ASC'])]
    private Collection $teams;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->teams = new ArrayCollection();
        $this->settings = [];
        $this->wordsData = [];
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getCurrentTeam(): ?Team
    {
        return $this->currentTeam;
    }

    public function setCurrentTeam(?Team $currentTeam): static
    {
        $this->currentTeam = $currentTeam;

        return $this;
    }

    public function getCurrentPlayer(): ?Player
    {
        return $this->currentPlayer;
    }

    public function setCurrentPlayer(?Player $currentPlayer): static
    {
        $this->currentPlayer = $currentPlayer;

        return $this;
    }

    public function getRoundStartTeam(): ?Team
    {
        return $this->roundStartTeam;
    }

    public function setRoundStartTeam(?Team $roundStartTeam): static
    {
        $this->roundStartTeam = $roundStartTeam;

        return $this;
    }

    public function getRoundStartPlayer(): ?Player
    {
        return $this->roundStartPlayer;
    }

    public function setRoundStartPlayer(?Player $roundStartPlayer): static
    {
        $this->roundStartPlayer = $roundStartPlayer;

        return $this;
    }

    public function getTotalWordsCount(): int
    {
        return $this->totalWordsCount;
    }

    public function setTotalWordsCount(int $totalWordsCount): static
    {
        $this->totalWordsCount = $totalWordsCount;

        return $this;
    }

    public function getTurnTimeLimit(): int
    {
        return $this->turnTimeLimit;
    }

    public function setTurnTimeLimit(int $turnTimeLimit): static
    {
        $this->turnTimeLimit = $turnTimeLimit;

        return $this;
    }

    /** @return int[] */
    public function getWordsData(): array
    {
        return $this->wordsData;
    }

    /** @param int[] $wordsData */
    public function setWordsData(array $wordsData): static
    {
        $this->wordsData = $wordsData;

        return $this;
    }

    /**
     * @return array{difficulties?: int[], categories?: string[], cycle?: int[]}
     */
    public function getSettings(): array
    {
        return $this->settings;
    }

    /**
     * @param array{difficulties?: int[], categories?: string[], cycle?: int[]} $settings
     */
    public function setSettings(array $settings): static
    {
        $this->settings = $settings;

        return $this;
    }

    /** @return int[] */
    public function getSelectedDifficulties(): array
    {
        $diffs = $this->settings['difficulties'] ?? DifficultyConfig::allLevelIds();
        $diffs = array_values(array_unique(array_map('intval', $diffs)));
        sort($diffs);

        return $diffs !== [] ? $diffs : DifficultyConfig::allLevelIds();
    }

    /** @return int[] */
    public function getDifficultyCycle(): array
    {
        $cycle = $this->settings['cycle'] ?? [];
        if ($cycle === []) {
            return DifficultyConfig::buildCycleSequence($this->getSelectedDifficulties());
        }

        return array_values(array_map('intval', $cycle));
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    /** @return Collection<int, Team> */
    public function getTeams(): Collection
    {
        return $this->teams;
    }

    public function addTeam(Team $team): static
    {
        if (!$this->teams->contains($team)) {
            $this->teams->add($team);
            $team->setSession($this);
        }

        return $this;
    }

    public function getRoundNumber(): int
    {
        return match ($this->status) {
            self::STATUS_ROUND1 => 1,
            self::STATUS_ROUND2 => 2,
            self::STATUS_ROUND3 => 3,
            default => 0,
        };
    }
}
