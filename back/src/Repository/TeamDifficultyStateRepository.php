<?php

namespace App\Repository;

use App\Entity\TeamDifficultyState;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<TeamDifficultyState> */
class TeamDifficultyStateRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TeamDifficultyState::class);
    }

    public function findByTeamAndRound(int $teamId, int $round): ?TeamDifficultyState
    {
        return $this->createQueryBuilder('tds')
            ->where('tds.team = :teamId')
            ->andWhere('tds.round = :round')
            ->setParameter('teamId', $teamId)
            ->setParameter('round', $round)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findBySessionTeamRound(int $sessionId, int $teamId, int $round): ?TeamDifficultyState
    {
        return $this->createQueryBuilder('tds')
            ->where('tds.session = :sessionId')
            ->andWhere('tds.team = :teamId')
            ->andWhere('tds.round = :round')
            ->setParameter('sessionId', $sessionId)
            ->setParameter('teamId', $teamId)
            ->setParameter('round', $round)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function resetForNewRound(int $sessionId, int $round): void
    {
        $this->createQueryBuilder('tds')
            ->update()
            ->set('tds.currentDifficulty', 1)
            ->set('tds.wordsGuessedInCycle', 0)
            ->where('tds.session = :sessionId')
            ->andWhere('tds.round = :round')
            ->setParameter('sessionId', $sessionId)
            ->setParameter('round', $round)
            ->getQuery()
            ->execute();
    }
}
