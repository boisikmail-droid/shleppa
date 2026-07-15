<?php

namespace App\Service;

use App\Entity\GameSession;
use App\Entity\Team;
use App\Entity\Word;
use App\Repository\RoundProgressRepository;
use App\Repository\TeamDifficultyStateRepository;

class WordSelector
{
    public function __construct(
        private RoundProgressRepository $roundProgressRepository,
        private TeamDifficultyStateRepository $teamDifficultyStateRepository,
    ) {
    }

    /**
     * @param int[] $excludeWordIds Слова, уже показанные в текущем ходе
     *
     * @return array{word: Word, remaining_words: int}|null
     */
    public function getNextWordData(GameSession $session, Team $team, int $round, array $excludeWordIds = []): ?array
    {
        $state = $this->teamDifficultyStateRepository->findBySessionTeamRound(
            $session->getId(),
            $team->getId(),
            $round
        );

        if (!$state) {
            return null;
        }

        $wordIds = $session->getWordsData();
        $allowed = $session->getSelectedDifficulties();
        $startDifficulty = $state->getCurrentDifficulty();

        // Сначала текущая сложность, затем следующие из выбранных
        $ordered = $this->difficultyFallbackOrder($allowed, $startDifficulty);

        $word = null;
        foreach ($ordered as $difficulty) {
            $word = $this->roundProgressRepository->findUnguessedWordByDifficultyInSession(
                $session->getId(),
                $round,
                $difficulty,
                $wordIds,
                $excludeWordIds
            );

            if ($word) {
                break;
            }
        }

        if (!$word) {
            return null;
        }

        $unguessedInRound = $this->roundProgressRepository->countUnguessedInRound(
            $session->getId(),
            $round,
            $wordIds
        );

        return [
            'word' => $word,
            'remaining_words' => $unguessedInRound,
        ];
    }

    public function countRemaining(GameSession $session, int $round): int
    {
        return $this->roundProgressRepository->countUnguessedInRound(
            $session->getId(),
            $round,
            $session->getWordsData()
        );
    }

    /** @param int[] $excludeWordIds */
    public function getNextWord(GameSession $session, Team $team, int $round, array $excludeWordIds = []): ?Word
    {
        $data = $this->getNextWordData($session, $team, $round, $excludeWordIds);

        return $data['word'] ?? null;
    }

    /**
     * @param int[] $allowed
     *
     * @return int[]
     */
    private function difficultyFallbackOrder(array $allowed, int $start): array
    {
        $allowed = array_values(array_unique(array_map('intval', $allowed)));
        sort($allowed);

        if ($allowed === []) {
            return [$start];
        }

        $idx = array_search($start, $allowed, true);
        if ($idx === false) {
            $idx = 0;
            foreach ($allowed as $i => $d) {
                if ($d >= $start) {
                    $idx = $i;
                    break;
                }
            }
        }

        $ordered = [];
        for ($i = $idx; $i < count($allowed); ++$i) {
            $ordered[] = $allowed[$i];
        }
        for ($i = 0; $i < $idx; ++$i) {
            $ordered[] = $allowed[$i];
        }

        return $ordered;
    }
}
