<?php

namespace App\Service;

use App\Entity\GameSession;
use App\Repository\RoundProgressRepository;
use App\Repository\TeamDifficultyStateRepository;
use Doctrine\ORM\EntityManagerInterface;

class RoundTransitionManager
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private RoundProgressRepository $roundProgressRepository,
        private TeamDifficultyStateRepository $teamDifficultyStateRepository,
        private NextPlayerCalculator $nextPlayerCalculator,
    ) {
    }

    public function advanceToNextRound(GameSession $session): GameSession
    {
        $newRound = match ($session->getStatus()) {
            GameSession::STATUS_ROUND1 => 2,
            GameSession::STATUS_ROUND2 => 3,
            GameSession::STATUS_ROUND3 => 0,
            default => throw new \InvalidArgumentException('Cannot advance from status: '.$session->getStatus()),
        };

        $newStatus = match ($session->getStatus()) {
            GameSession::STATUS_ROUND1 => GameSession::STATUS_ROUND2,
            GameSession::STATUS_ROUND2 => GameSession::STATUS_ROUND3,
            GameSession::STATUS_ROUND3 => GameSession::STATUS_FINISHED,
            default => throw new \InvalidArgumentException('Invalid status'),
        };

        $session->setStatus($newStatus);

        if ($newRound > 0) {
            $cycle = $session->getDifficultyCycle();
            $startDifficulty = $cycle[0] ?? 1;
            $this->roundProgressRepository->resetRound($session->getId(), $newRound);
            $this->teamDifficultyStateRepository->resetForNewRound(
                $session->getId(),
                $newRound,
                $startDifficulty
            );
        }

        if ($newStatus !== GameSession::STATUS_FINISHED) {
            $firstTeam = $session->getTeams()->first();
            $firstPlayer = $firstTeam?->getPlayers()->first();
            if ($firstTeam && $firstPlayer) {
                $session->setCurrentTeam($firstTeam);
                $session->setCurrentPlayer($firstPlayer);
                $session->setRoundStartTeam($firstTeam);
                $session->setRoundStartPlayer($firstPlayer);
            }
        }

        $this->entityManager->flush();

        return $session;
    }
}
