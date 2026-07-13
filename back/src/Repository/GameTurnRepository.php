<?php

namespace App\Repository;

use App\Entity\GameTurn;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<GameTurn> */
class GameTurnRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, GameTurn::class);
    }

    public function findActiveTurn(int $sessionId, int $playerId): ?GameTurn
    {
        return $this->createQueryBuilder('gt')
            ->where('gt.session = :sessionId')
            ->andWhere('gt.player = :playerId')
            ->andWhere('gt.isFinished = false')
            ->setParameter('sessionId', $sessionId)
            ->setParameter('playerId', $playerId)
            ->orderBy('gt.createdAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
