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

    /**
     * @param string[] $categories
     * @param int[]    $excludeIds
     *
     * @return Word[]
     */
    public function findRandomByDifficultyAndCategories(
        int $difficulty,
        array $categories,
        int $limit,
        array $excludeIds = [],
    ): array {
        if ($limit <= 0 || $categories === []) {
            return [];
        }

        $qb = $this->createQueryBuilder('w')
            ->select('w.id')
            ->where('w.difficulty = :d')
            ->andWhere('w.category IN (:cats)')
            ->setParameter('d', $difficulty)
            ->setParameter('cats', $categories);

        if ($excludeIds !== []) {
            $qb->andWhere('w.id NOT IN (:exclude)')
                ->setParameter('exclude', $excludeIds);
        }

        $ids = $qb->getQuery()->getScalarResult();
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

    /** @deprecated kept for compatibility */
    public function findRandomByDifficulty(int $difficulty, int $limit): array
    {
        return $this->findRandomByDifficultyAndCategories(
            $difficulty,
            \App\Config\CategoryConfig::allSlugs(),
            $limit
        );
    }
}
