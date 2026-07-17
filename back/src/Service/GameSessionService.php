<?php

namespace App\Service;

use App\Config\CategoryConfig;
use App\Config\DifficultyConfig;
use App\Entity\GameSession;
use App\Entity\GameTurn;
use App\Entity\Player;
use App\Entity\RoundProgress;
use App\Entity\Team;
use App\Entity\TeamDifficultyState;
use App\Entity\TurnLog;
use App\Entity\Word;
use App\Repository\GameSessionRepository;
use App\Repository\GameTurnRepository;
use App\Repository\RoundProgressRepository;
use App\Repository\TeamDifficultyStateRepository;
use App\Repository\TurnLogRepository;
use App\Repository\WordRepository;
use Doctrine\ORM\EntityManagerInterface;

class GameSessionService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private GameSessionRepository $sessionRepository,
        private WordRepository $wordRepository,
        private RoundProgressRepository $roundProgressRepository,
        private TeamDifficultyStateRepository $teamDifficultyStateRepository,
        private GameTurnRepository $gameTurnRepository,
        private TurnLogRepository $turnLogRepository,
        private NextPlayerCalculator $nextPlayerCalculator,
        private WordSelector $wordSelector,
        private ScoreCalculator $scoreCalculator,
        private RoundTransitionManager $roundTransitionManager,
    ) {
    }

    /**
     * @param list<array{name: string, players: list<string>, hat_id?: string}> $teams
     * @param int[]                                                              $difficulties
     * @param string[]                                                           $categories
     */
    public function createSession(
        array $teams,
        int $totalWords,
        int $timeLimit,
        array $difficulties,
        array $categories,
        int $skipPenalty = 2,
        bool $lastWordCommon = true,
    ): GameSession {
        $difficulties = array_values(array_unique(array_map('intval', $difficulties)));
        sort($difficulties);
        if ($difficulties === []) {
            $difficulties = DifficultyConfig::allLevelIds();
        }

        $categories = array_values(array_unique(array_map('strval', $categories)));
        $categories = array_values(array_filter($categories, static fn (string $c) => CategoryConfig::isValid($c)));
        if ($categories === []) {
            $categories = CategoryConfig::allSlugs();
        }

        $cycle = DifficultyConfig::buildCycleSequence($difficulties);
        $startDifficulty = $cycle[0] ?? $difficulties[0];

        $session = new GameSession();
        $session->setStatus(GameSession::STATUS_LOBBY);
        $session->setTotalWordsCount($totalWords);
        $session->setTurnTimeLimit($timeLimit);
        $session->setSettings([
            'difficulties' => $difficulties,
            'categories' => $categories,
            'cycle' => $cycle,
            'skip_penalty' => max(0, $skipPenalty),
            'last_word_common' => $lastWordCommon,
        ]);

        $createdTeams = [];
        foreach ($teams as $teamData) {
            $createdTeams[] = $this->createTeam(
                $session,
                (string) $teamData['name'],
                array_map('strval', $teamData['players'] ?? []),
                isset($teamData['hat_id']) ? (string) $teamData['hat_id'] : 'tophat',
            );
        }

        $wordIds = $this->selectWords($totalWords, $difficulties, $categories);
        if (count($wordIds) < 20) {
            throw new \InvalidArgumentException('Недостаточно слов в словаре для выбранных фильтров.');
        }
        $session->setWordsData($wordIds);
        $session->setTotalWordsCount(count($wordIds));

        foreach ($wordIds as $wordId) {
            $word = $this->entityManager->getReference(Word::class, $wordId);
            for ($round = 1; $round <= 3; ++$round) {
                $progress = new RoundProgress();
                $progress->setSession($session);
                $progress->setWord($word);
                $progress->setRound($round);
                $this->entityManager->persist($progress);
            }
        }

        foreach ($createdTeams as $team) {
            for ($round = 1; $round <= 3; ++$round) {
                $state = new TeamDifficultyState();
                $state->setSession($session);
                $state->setTeam($team);
                $state->setRound($round);
                $state->setCurrentDifficulty($startDifficulty);
                $state->setWordsGuessedInCycle(0);
                $this->entityManager->persist($state);
            }
        }

        $firstTeam = $createdTeams[0];
        $firstPlayer = $firstTeam->getPlayers()->first();
        $session->setStatus(GameSession::STATUS_ROUND1);
        $session->setCurrentTeam($firstTeam);
        $session->setCurrentPlayer($firstPlayer);
        $session->setRoundStartTeam($firstTeam);
        $session->setRoundStartPlayer($firstPlayer);

        $this->entityManager->persist($session);
        $this->entityManager->flush();

        return $session;
    }

    /** @param string[] $playerNames */
    private function createTeam(GameSession $session, string $name, array $playerNames, string $hatId = 'tophat'): Team
    {
        $team = new Team();
        $team->setSession($session);
        $team->setName(trim($name));
        $team->setHatId($hatId !== '' ? $hatId : 'tophat');
        $session->addTeam($team);

        foreach ($playerNames as $index => $playerName) {
            $player = new Player();
            $player->setTeam($team);
            $player->setName(trim($playerName));
            $player->setOrderIndex($index);
            $team->addPlayer($player);
            $this->entityManager->persist($player);
        }

        $this->entityManager->persist($team);

        return $team;
    }

    /**
     * @param int[]    $difficulties
     * @param string[] $categories
     *
     * @return int[]
     */
    private function selectWords(int $totalWords, array $difficulties, array $categories): array
    {
        $weights = DifficultyConfig::gaussianWeights($difficulties);
        $quotas = DifficultyConfig::allocateCounts($weights, $totalWords);
        $selectedIds = [];
        $catCount = count($categories);

        foreach ($quotas as $difficulty => $need) {
            if ($need <= 0) {
                continue;
            }

            $perCat = intdiv($need, $catCount);
            $extra = $need % $catCount;
            $got = [];

            foreach ($categories as $i => $category) {
                $take = $perCat + ($i < $extra ? 1 : 0);
                if ($take <= 0) {
                    continue;
                }
                $words = $this->wordRepository->findRandomByDifficultyAndCategories(
                    (int) $difficulty,
                    [$category],
                    $take,
                    $selectedIds
                );
                foreach ($words as $word) {
                    $got[] = $word->getId();
                    $selectedIds[] = $word->getId();
                }
            }

            $shortfall = $need - count($got);
            if ($shortfall > 0) {
                $words = $this->wordRepository->findRandomByDifficultyAndCategories(
                    (int) $difficulty,
                    $categories,
                    $shortfall,
                    $selectedIds
                );
                foreach ($words as $word) {
                    $selectedIds[] = $word->getId();
                }
            }
        }

        return array_values(array_unique($selectedIds));
    }

    public function startTurn(GameSession $session, int $playerId): GameTurn
    {
        $player = $this->entityManager->find(Player::class, $playerId);
        if (!$player || $session->getCurrentPlayer()?->getId() !== $playerId) {
            throw new \InvalidArgumentException('Not this player\'s turn');
        }

        $active = $this->gameTurnRepository->findActiveTurn($session->getId(), $playerId);
        if ($active) {
            return $active;
        }

        $turn = new GameTurn();
        $turn->setSession($session);
        $turn->setTeam($player->getTeam());
        $turn->setPlayer($player);
        $turn->setRound($session->getRoundNumber());
        $this->entityManager->persist($turn);
        $this->entityManager->flush();

        return $turn;
    }

    public function processAction(
        GameSession $session,
        int $playerId,
        int $wordId,
        string $action,
        ?int $turnId = null,
    ): TurnLog {
        if ($session->getStatus() === GameSession::STATUS_FINISHED) {
            throw new \InvalidArgumentException('Game is finished');
        }

        if ($session->getCurrentPlayer()?->getId() !== $playerId) {
            throw new \InvalidArgumentException('Not this player\'s turn');
        }

        $player = $this->entityManager->find(Player::class, $playerId);
        $word = $this->entityManager->find(Word::class, $wordId);
        if (!$player || !$word) {
            throw new \InvalidArgumentException('Player or word not found');
        }

        $turn = $turnId
            ? $this->gameTurnRepository->find($turnId)
            : $this->gameTurnRepository->findActiveTurn($session->getId(), $playerId);

        if (!$turn || $turn->isFinished()) {
            throw new \InvalidArgumentException('Turn is not active');
        }

        $round = $session->getRoundNumber();
        $status = $action === 'guess' ? TurnLog::STATUS_GUESSED : TurnLog::STATUS_SKIPPED;

        $log = new TurnLog();
        $log->setSession($session);
        $log->setTeam($player->getTeam());
        $log->setPlayer($player);
        $log->setRound($round);
        $log->setWord($word);
        $log->setStatus($status);
        $log->setGameTurn($turn);
        $turn->addTurnLog($log);
        $this->entityManager->persist($log);

        if ($action === 'guess') {
            $progress = $this->roundProgressRepository->findBySessionWordRound(
                $session->getId(),
                $wordId,
                $round
            );
            if ($progress) {
                $progress->setIsGuessedInThisRound(true);
            }

            $state = $this->teamDifficultyStateRepository->findBySessionTeamRound(
                $session->getId(),
                $player->getTeam()->getId(),
                $round
            );
            $state?->applyGuessDifficultyUpdate();
        }

        $this->entityManager->flush();

        return $log;
    }

    /**
     * Last word after the timer: open to all teams.
     * If $awardTeamId is set — mark guessed and +1 to that team.
     * If null — word stays in the hat.
     *
     * @return array{awarded: bool, awarded_team_id: int|null, remaining_words: int}
     */
    public function resolveLastWord(
        GameSession $session,
        int $turnId,
        int $wordId,
        ?int $awardTeamId,
    ): array {
        if ($session->getStatus() === GameSession::STATUS_FINISHED) {
            throw new \InvalidArgumentException('Game is finished');
        }

        $turn = $this->gameTurnRepository->find($turnId);
        if (!$turn || $turn->getSession()->getId() !== $session->getId() || $turn->isFinished()) {
            throw new \InvalidArgumentException('Turn is not active');
        }

        $word = $this->entityManager->find(Word::class, $wordId);
        if (!$word) {
            throw new \InvalidArgumentException('Word not found');
        }

        $round = $session->getRoundNumber();
        $progress = $this->roundProgressRepository->findBySessionWordRound(
            $session->getId(),
            $wordId,
            $round
        );
        if (!$progress) {
            throw new \InvalidArgumentException('Word is not in this round');
        }

        $awardedTeamId = null;

        if ($awardTeamId !== null) {
            $awardTeam = $this->entityManager->find(Team::class, $awardTeamId);
            if (!$awardTeam || $awardTeam->getSession()?->getId() !== $session->getId()) {
                throw new \InvalidArgumentException('Invalid team');
            }

            if (!$progress->isGuessedInThisRound()) {
                $progress->setIsGuessedInThisRound(true);
                $awardTeam->setScore($awardTeam->getScore() + 1);
                $awardedTeamId = $awardTeam->getId();

                $state = $this->teamDifficultyStateRepository->findBySessionTeamRound(
                    $session->getId(),
                    $awardTeam->getId(),
                    $round
                );
                $state?->applyGuessDifficultyUpdate();
            } else {
                $awardedTeamId = $awardTeamId;
            }
        }

        $this->entityManager->flush();

        $remaining = $this->roundProgressRepository->countUnguessedInRound(
            $session->getId(),
            $round,
            $session->getWordsData()
        );

        return [
            'awarded' => $awardedTeamId !== null,
            'awarded_team_id' => $awardedTeamId,
            'remaining_words' => $remaining,
        ];
    }

    /**
     * @param array<int, array{word_id: int, checked: bool}> $correctionsList
     */
    public function finishTurn(GameSession $session, int $turnId, array $correctionsList): array
    {
        $turnData = $this->turnLogRepository->findTurnByIdWithWords($turnId);
        if (!$turnData) {
            throw new \InvalidArgumentException('Turn not found');
        }

        $turn = $turnData['turn'];
        $logs = $turnData['logs'];

        if ($turn->getSession()->getId() !== $session->getId()) {
            throw new \InvalidArgumentException('Turn does not belong to session');
        }

        if ($turn->isFinished()) {
            throw new \InvalidArgumentException('Turn already finished');
        }

        $corrections = [];
        foreach ($correctionsList as $item) {
            $corrections[(int) $item['word_id']] = $item;
        }

        $team = $turn->getTeam();
        $round = $session->getRoundNumber();
        $skipPenalty = (int) ($session->getSettings()['skip_penalty'] ?? 2);
        $scoreChange = $this->scoreCalculator->calculateScoreChange($logs, $corrections, $skipPenalty);
        $team->setScore($team->getScore() + $scoreChange);

        $state = $this->teamDifficultyStateRepository->findBySessionTeamRound(
            $session->getId(),
            $team->getId(),
            $round
        );

        foreach ($this->scoreCalculator->getLogsNeedingDifficultyUpdate($logs, $corrections) as $log) {
            $state?->applyGuessDifficultyUpdate();

            $progress = $this->roundProgressRepository->findBySessionWordRound(
                $session->getId(),
                $log->getWord()->getId(),
                $round
            );
            $progress?->setIsGuessedInThisRound(true);
        }

        foreach ($logs as $log) {
            if ($this->scoreCalculator->wasStatusChanged($log, $corrections)) {
                $log->setWasCorrected(true);
            }

            // Откат «угадали» на коррекции → слово снова в шляпе
            $wordId = $log->getWord()->getId();
            $correction = $corrections[$wordId] ?? null;
            if (
                $correction !== null
                && $log->getStatus() === TurnLog::STATUS_GUESSED
                && !(bool) $correction['checked']
            ) {
                $progress = $this->roundProgressRepository->findBySessionWordRound(
                    $session->getId(),
                    $wordId,
                    $round
                );
                $progress?->setIsGuessedInThisRound(false);
            }
        }

        $turn->setIsFinished(true);
        $this->entityManager->flush();

        $unguessed = $this->roundProgressRepository->countUnguessedInRound(
            $session->getId(),
            $round,
            $session->getWordsData()
        );

        [$nextTeam, $nextPlayer] = $this->nextPlayerCalculator->getNextPlayer($session);
        $roundFinished = $this->nextPlayerCalculator->isHatEmpty($session, $unguessed);
        $gameFinished = false;

        if ($roundFinished && $session->getStatus() !== GameSession::STATUS_ROUND3) {
            $this->roundTransitionManager->advanceToNextRound($session);
            $roundFinished = true;
            $nextTeam = $session->getCurrentTeam();
            $nextPlayer = $session->getCurrentPlayer();
        } elseif ($roundFinished && $session->getStatus() === GameSession::STATUS_ROUND3) {
            $session->setStatus(GameSession::STATUS_FINISHED);
            $gameFinished = true;
            $this->entityManager->flush();
        } else {
            $session->setCurrentTeam($nextTeam);
            $session->setCurrentPlayer($nextPlayer);
            $this->entityManager->flush();
        }

        return [
            'score_change' => $scoreChange,
            'new_team_score' => $team->getScore(),
            'next_team' => ['id' => $nextTeam?->getId(), 'name' => $nextTeam?->getName()],
            'next_player' => ['id' => $nextPlayer?->getId(), 'name' => $nextPlayer?->getName()],
            'round_finished' => $roundFinished,
            'game_finished' => $gameFinished,
            'remaining_words' => $roundFinished ? 0 : $unguessed,
        ];
    }

    public function getState(GameSession $session): array
    {
        $round = $session->getRoundNumber();
        $currentTeam = $session->getCurrentTeam();
        $currentPlayer = $session->getCurrentPlayer();
        $cycle = $session->getDifficultyCycle();

        $difficultyState = null;
        if ($currentTeam && $round > 0) {
            $state = $this->teamDifficultyStateRepository->findBySessionTeamRound(
                $session->getId(),
                $currentTeam->getId(),
                $round
            );
            if ($state) {
                $difficultyState = [
                    'current_difficulty' => $state->getCurrentDifficulty(),
                    'words_guessed_in_cycle' => $state->getWordsGuessedInCycle(),
                    'next_reset_at' => count($cycle),
                ];
            }
        }

        $teams = [];
        foreach ($session->getTeams() as $team) {
            $teams[] = [
                'id' => $team->getId(),
                'name' => $team->getName(),
                'score' => $team->getScore(),
                'hat_id' => $team->getHatId(),
            ];
        }

        $remainingWords = 0;
        if ($round > 0) {
            $remainingWords = $this->roundProgressRepository->countUnguessedInRound(
                $session->getId(),
                $round,
                $session->getWordsData()
            );
        }

        return [
            'session_id' => $session->getId(),
            'status' => $session->getStatus(),
            'round' => $round,
            'current_team' => $currentTeam ? [
                'id' => $currentTeam->getId(),
                'name' => $currentTeam->getName(),
                'score' => $currentTeam->getScore(),
                'hat_id' => $currentTeam->getHatId(),
            ] : null,
            'current_player' => $currentPlayer ? [
                'id' => $currentPlayer->getId(),
                'name' => $currentPlayer->getName(),
            ] : null,
            'team_difficulty_state' => $difficultyState,
            'next_players' => $this->nextPlayerCalculator->peekNextPlayers($session),
            'time_limit' => $session->getTurnTimeLimit(),
            'is_turn_active' => false,
            'turn_time_remaining' => null,
            'teams' => $teams,
            'remaining_words' => $remainingWords,
            'settings' => [
                'difficulties' => $session->getSelectedDifficulties(),
                'categories' => $session->getSettings()['categories'] ?? CategoryConfig::allSlugs(),
                'max_difficulty' => DifficultyConfig::MAX_LEVEL,
                'skip_penalty' => (int) ($session->getSettings()['skip_penalty'] ?? 2),
                'last_word_common' => (bool) ($session->getSettings()['last_word_common'] ?? true),
            ],
        ];
    }

    /**
     * Post-game summary from TurnLogs (after corrections via wasCorrected).
     *
     * @return array{
     *   session_id: int|null,
     *   teams: list<array{id: int|null, name: string, score: int, hat_id: string}>,
     *   players: list<array{id: int|null, name: string, team_id: int|null, team_name: string, guessed: int, skipped: int, net: int}>,
     *   rounds: list<array{round: int, guessed: int, skipped: int}>,
     *   highlights: list<array{word: string, team: string, player: string, round: int, outcome: string}>
     * }
     */
    public function getRecap(GameSession $session): array
    {
        $logs = $this->turnLogRepository->findAllForSession((int) $session->getId());
        $skipPenalty = (int) ($session->getSettings()['skip_penalty'] ?? 2);

        $teamsOut = [];
        foreach ($session->getTeams() as $team) {
            $teamsOut[] = [
                'id' => $team->getId(),
                'name' => $team->getName(),
                'score' => $team->getScore(),
                'hat_id' => $team->getHatId(),
            ];
        }

        /** @var array<int, array{id: int|null, name: string, team_id: int|null, team_name: string, guessed: int, skipped: int, net: int}> */
        $players = [];
        /** @var array<int, array{round: int, guessed: int, skipped: int}> */
        $rounds = [];
        $highlights = [];

        foreach ($logs as $log) {
            $round = $log->getRound();
            if (!isset($rounds[$round])) {
                $rounds[$round] = ['round' => $round, 'guessed' => 0, 'skipped' => 0];
            }

            $player = $log->getPlayer();
            $team = $log->getTeam();
            $pid = (int) $player->getId();
            if (!isset($players[$pid])) {
                $players[$pid] = [
                    'id' => $player->getId(),
                    'name' => $player->getName(),
                    'team_id' => $team->getId(),
                    'team_name' => $team->getName(),
                    'guessed' => 0,
                    'skipped' => 0,
                    'net' => 0,
                ];
            }

            $status = $log->getStatus();
            $corrected = $log->wasCorrected();

            // Effective outcome after correction screen
            $effectiveGuess = ($status === TurnLog::STATUS_GUESSED && !$corrected)
                || ($status === TurnLog::STATUS_SKIPPED && $corrected);
            $effectiveSkip = ($status === TurnLog::STATUS_SKIPPED && !$corrected);

            if ($effectiveGuess) {
                ++$players[$pid]['guessed'];
                ++$rounds[$round]['guessed'];
                $players[$pid]['net'] += 1;
                if (count($highlights) < 12) {
                    $highlights[] = [
                        'word' => $log->getWord()->getText(),
                        'team' => $team->getName(),
                        'player' => $player->getName(),
                        'round' => $round,
                        'outcome' => 'guessed',
                    ];
                }
            } elseif ($effectiveSkip) {
                ++$players[$pid]['skipped'];
                ++$rounds[$round]['skipped'];
                $players[$pid]['net'] -= $skipPenalty;
            }
        }

        usort($players, static fn ($a, $b) => $b['guessed'] <=> $a['guessed'] ?: $b['net'] <=> $a['net']);
        ksort($rounds);

        return [
            'session_id' => $session->getId(),
            'teams' => $teamsOut,
            'players' => array_values($players),
            'rounds' => array_values($rounds),
            'highlights' => $highlights,
        ];
    }
}
