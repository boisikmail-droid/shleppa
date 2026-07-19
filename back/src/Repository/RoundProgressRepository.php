<?php

namespace App\Repository;

use App\Config\DifficultyConfig;
use App\Entity\RoundProgress;
use App\Entity\Word;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<RoundProgress> */
class RoundProgressRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RoundProgress::class);
    }

    public function findUnguessedWordByDifficulty(int $sessionId, int $round, int $difficulty): ?Word
    {
        $progress = $this->createQueryBuilder('rp')
            ->join('rp.word', 'w')
            ->where('rp.session = :sessionId')
            ->andWhere('rp.round = :round')
            ->andWhere('rp.isGuessedInThisRound = false')
            ->andWhere('w.difficulty = :difficulty')
            ->andWhere('w.id IN (
                SELECT IDENTITY(rp2.word) FROM App\Entity\RoundProgress rp2
                JOIN rp2.session s WHERE s.id = :sessionId
            )')
            ->setParameter('sessionId', $sessionId)
            ->setParameter('round', $round)
            ->setParameter('difficulty', $difficulty)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        return $progress?->getWord();
    }

    /** @param int[] $wordIds @param int[] $excludeWordIds */
    public function findUnguessedWordByDifficultyInSession(
        int $sessionId,
        int $round,
        int $difficulty,
        array $wordIds,
        array $excludeWordIds = [],
    ): ?Word {
        if ($wordIds === []) {
            return null;
        }

        $qb = $this->createQueryBuilder('rp')
            ->join('rp.word', 'w')
            ->addSelect('w')
            ->where('rp.session = :sessionId')
            ->andWhere('rp.round = :round')
            ->andWhere('rp.isGuessedInThisRound = false')
            ->andWhere('w.difficulty = :difficulty')
            ->andWhere('w.id IN (:wordIds)')
            ->setParameter('sessionId', $sessionId)
            ->setParameter('round', $round)
            ->setParameter('difficulty', $difficulty)
            ->setParameter('wordIds', $wordIds);

        if ($excludeWordIds !== []) {
            $qb->andWhere('w.id NOT IN (:excludeWordIds)')
                ->setParameter('excludeWordIds', $excludeWordIds);
        }

        $results = $qb->getQuery()->getResult();

        if ($results === []) {
            return null;
        }

        /** @var RoundProgress $picked */
        $picked = $results[array_rand($results)];

        return $picked->getWord();
    }

    /**
     * Неотгаданные слова от minDifficulty и выше, исключая уже показанные в ходе.
     *
     * @param int[] $wordIds
     * @param int[] $excludeWordIds
     *
     * @return Word[]
     */
    public function findAvailableWordsFromDifficulty(
        int $sessionId,
        int $round,
        int $minDifficulty,
        array $wordIds,
        array $excludeWordIds = [],
    ): array {
        if ($wordIds === [] || $minDifficulty > DifficultyConfig::MAX_LEVEL) {
            return [];
        }

        $qb = $this->createQueryBuilder('rp')
            ->join('rp.word', 'w')
            ->addSelect('w')
            ->where('rp.session = :sessionId')
            ->andWhere('rp.round = :round')
            ->andWhere('rp.isGuessedInThisRound = false')
            ->andWhere('w.difficulty >= :minDifficulty')
            ->andWhere('w.id IN (:wordIds)')
            ->setParameter('sessionId', $sessionId)
            ->setParameter('round', $round)
            ->setParameter('minDifficulty', $minDifficulty)
            ->setParameter('wordIds', $wordIds);

        if ($excludeWordIds !== []) {
            $qb->andWhere('w.id NOT IN (:excludeWordIds)')
                ->setParameter('excludeWordIds', $excludeWordIds);
        }

        $results = $qb->getQuery()->getResult();

        return array_map(fn (RoundProgress $rp) => $rp->getWord(), $results);
    }

    public function countAvailableWordsFromDifficulty(
        int $sessionId,
        int $round,
        int $minDifficulty,
        array $wordIds,
        array $excludeWordIds = [],
    ): int {
        return count($this->findAvailableWordsFromDifficulty(
            $sessionId,
            $round,
            $minDifficulty,
            $wordIds,
            $excludeWordIds
        ));
    }

    public function resetRound(int $sessionId, int $round): void
    {
        $this->createQueryBuilder('rp')
            ->update()
            ->set('rp.isGuessedInThisRound', ':false')
            ->where('rp.session = :sessionId')
            ->andWhere('rp.round = :round')
            ->setParameter('false', false)
            ->setParameter('sessionId', $sessionId)
            ->setParameter('round', $round)
            ->getQuery()
            ->execute();
    }

    public function countGuessedInRound(int $sessionId, int $round): int
    {
        return (int) $this->createQueryBuilder('rp')
            ->select('COUNT(rp.id)')
            ->where('rp.session = :sessionId')
            ->andWhere('rp.round = :round')
            ->andWhere('rp.isGuessedInThisRound = true')
            ->setParameter('sessionId', $sessionId)
            ->setParameter('round', $round)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /** @param int[] $wordIds */
    public function countUnguessedInRound(int $sessionId, int $round, array $wordIds): int
    {
        if ($wordIds === []) {
            return 0;
        }

        return (int) $this->createQueryBuilder('rp')
            ->select('COUNT(rp.id)')
            ->where('rp.session = :sessionId')
            ->andWhere('rp.round = :round')
            ->andWhere('rp.isGuessedInThisRound = false')
            ->andWhere('IDENTITY(rp.word) IN (:wordIds)')
            ->setParameter('sessionId', $sessionId)
            ->setParameter('round', $round)
            ->setParameter('wordIds', $wordIds)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findBySessionWordRound(int $sessionId, int $wordId, int $round): ?RoundProgress
    {
        return $this->createQueryBuilder('rp')
            ->where('rp.session = :sessionId')
            ->andWhere('rp.word = :wordId')
            ->andWhere('rp.round = :round')
            ->setParameter('sessionId', $sessionId)
            ->setParameter('wordId', $wordId)
            ->setParameter('round', $round)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Неотгаданные слова раунда для клиентского хода.
     *
     * @param int[] $wordIds
     *
     * @return list<array{id: int, text: string, difficulty: int, category: string}>
     */
    public function findUnguessedWordSnapshots(int $sessionId, int $round, array $wordIds): array
    {
        if ($wordIds === []) {
            return [];
        }

        $results = $this->createQueryBuilder('rp')
            ->join('rp.word', 'w')
            ->addSelect('w')
            ->where('rp.session = :sessionId')
            ->andWhere('rp.round = :round')
            ->andWhere('rp.isGuessedInThisRound = false')
            ->andWhere('w.id IN (:wordIds)')
            ->setParameter('sessionId', $sessionId)
            ->setParameter('round', $round)
            ->setParameter('wordIds', $wordIds)
            ->getQuery()
            ->getResult();

        $out = [];
        foreach ($results as $rp) {
            /** @var RoundProgress $rp */
            $word = $rp->getWord();
            $out[] = [
                'id' => (int) $word->getId(),
                'text' => $word->getText(),
                'difficulty' => $word->getDifficulty(),
                'category' => $word->getCategory(),
            ];
        }

        return $out;
    }
}
