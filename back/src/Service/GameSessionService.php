<?php

namespace App\Service;

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

    /** @param string[] $team1Players @param string[] $team2Players */
    public function createSession(
        string $team1Name,
        array $team1Players,
        string $team2Name,
        array $team2Players,
        int $totalWords,
        int $timeLimit,
    ): GameSession {
        $session = new GameSession();
        $session->setStatus(GameSession::STATUS_LOBBY);
        $session->setTotalWordsCount($totalWords);
        $session->setTurnTimeLimit($timeLimit);

        $team1 = $this->createTeam($session, $team1Name, $team1Players);
        $team2 = $this->createTeam($session, $team2Name, $team2Players);

        $wordIds = $this->selectWords($totalWords);
        $session->setWordsData($wordIds);

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

        foreach ([$team1, $team2] as $team) {
            for ($round = 1; $round <= 3; ++$round) {
                $state = new TeamDifficultyState();
                $state->setSession($session);
                $state->setTeam($team);
                $state->setRound($round);
                $this->entityManager->persist($state);
            }
        }

        $firstPlayer = $team1->getPlayers()->first();
        $session->setStatus(GameSession::STATUS_ROUND1);
        $session->setCurrentTeam($team1);
        $session->setCurrentPlayer($firstPlayer);
        $session->setRoundStartTeam($team1);
        $session->setRoundStartPlayer($firstPlayer);

        $this->entityManager->persist($session);
        $this->entityManager->flush();

        return $session;
    }

    /** @param string[] $playerNames */
    private function createTeam(GameSession $session, string $name, array $playerNames): Team
    {
        $team = new Team();
        $team->setSession($session);
        $team->setName($name);
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

    /** @return int[] */
    private function selectWords(int $totalWords): array
    {
        $unit = intdiv($totalWords, DifficultyConfig::DISTRIBUTION_DIVISOR);
        $selectedIds = [];

        foreach (DifficultyConfig::POOL_WEIGHTS as $difficulty => $weight) {
            $words = $this->wordRepository->findRandomByDifficulty($difficulty, $unit * $weight);
            foreach ($words as $word) {
                $selectedIds[] = $word->getId();
            }
        }

        return $selectedIds;
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
        $scoreChange = $this->scoreCalculator->calculateScoreChange($logs, $corrections);
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
        }

        $turn->setIsFinished(true);
        $this->entityManager->flush();

        [$nextTeam, $nextPlayer] = $this->nextPlayerCalculator->getNextPlayer($session);
        $roundFinished = $this->nextPlayerCalculator->isRoundComplete($session, $nextTeam, $nextPlayer);
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
        ];
    }

    public function getState(GameSession $session): array
    {
        $round = $session->getRoundNumber();
        $currentTeam = $session->getCurrentTeam();
        $currentPlayer = $session->getCurrentPlayer();

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
                    'next_reset_at' => DifficultyConfig::cycleLength(),
                ];
            }
        }

        $teams = [];
        foreach ($session->getTeams() as $team) {
            $teams[] = [
                'id' => $team->getId(),
                'name' => $team->getName(),
                'score' => $team->getScore(),
            ];
        }

        return [
            'session_id' => $session->getId(),
            'status' => $session->getStatus(),
            'round' => $round,
            'current_team' => $currentTeam ? [
                'id' => $currentTeam->getId(),
                'name' => $currentTeam->getName(),
                'score' => $currentTeam->getScore(),
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
        ];
    }
}
