<?php

namespace App\Repository;

use App\Entity\TurnLog;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<TurnLog> */
class TurnLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TurnLog::class);
    }

    public function findLastTurnByPlayer(int $playerId, int $sessionId): ?array
    {
        $turn = $this->getEntityManager()->getRepository(\App\Entity\GameTurn::class)
            ->createQueryBuilder('gt')
            ->where('gt.player = :playerId')
            ->andWhere('gt.session = :sessionId')
            ->setParameter('playerId', $playerId)
            ->setParameter('sessionId', $sessionId)
            ->orderBy('gt.createdAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        if (!$turn) {
            return null;
        }

        return $this->findTurnByIdWithWords($turn->getId());
    }

    /** @return array{turn: \App\Entity\GameTurn, logs: TurnLog[]}|null */
    public function findTurnByIdWithWords(int $turnId): ?array
    {
        $turn = $this->getEntityManager()->getRepository(\App\Entity\GameTurn::class)->find($turnId);
        if (!$turn) {
            return null;
        }

        $logs = $this->createQueryBuilder('tl')
            ->join('tl.word', 'w')
            ->addSelect('w')
            ->where('tl.gameTurn = :turnId')
            ->setParameter('turnId', $turnId)
            ->orderBy('tl.createdAt', 'ASC')
            ->getQuery()
            ->getResult();

        return ['turn' => $turn, 'logs' => $logs];
    }
}
