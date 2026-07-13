<?php

namespace App\Repository;

use App\Entity\Word;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<Word> */
class WordRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Word::class);
    }

    public function truncateAll(): void
    {
        $this->createQueryBuilder('w')
            ->delete()
            ->getQuery()
            ->execute();
    }

    /** @return Word[] */
    public function findRandomByDifficulty(int $difficulty, int $limit): array
    {
        $ids = $this->createQueryBuilder('w')
            ->select('w.id')
            ->where('w.difficulty = :d')
            ->setParameter('d', $difficulty)
            ->getQuery()
            ->getScalarResult();

        $idList = array_column($ids, 'id');
        shuffle($idList);
        $selected = array_slice($idList, 0, $limit);

        if ($selected === []) {
            return [];
        }

        return $this->createQueryBuilder('w')
            ->where('w.id IN (:ids)')
            ->setParameter('ids', $selected)
            ->getQuery()
            ->getResult();
    }
}
