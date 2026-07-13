<?php

namespace App\Service;

use App\Config\DifficultyConfig;
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
        $startDifficulty = $state->getCurrentDifficulty();

        // Сначала текущая сложность, потом +1, +2… (как в ТЗ)
        $word = null;
        $difficulty = $startDifficulty;
        while ($difficulty <= DifficultyConfig::MAX_LEVEL) {
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

            ++$difficulty;
        }

        if (!$word) {
            return null;
        }

        // Все неотгаданные слова в раунде (общий пул игры)
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

    /** @param int[] $excludeWordIds */
    public function getNextWord(GameSession $session, Team $team, int $round, array $excludeWordIds = []): ?Word
    {
        $data = $this->getNextWordData($session, $team, $round, $excludeWordIds);

        return $data['word'] ?? null;
    }
}
