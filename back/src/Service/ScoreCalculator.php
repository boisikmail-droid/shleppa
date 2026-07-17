<?php

namespace App\Service;

use App\Entity\TurnLog;

class ScoreCalculator
{
    /**
     * @param TurnLog[] $turnLogs
     * @param array<int, array{word_id: int, checked: bool}> $corrections keyed by word_id
     * @param int $skipPenalty баллы за пропуск (0 = без штрафа)
     */
    public function calculateScoreChange(array $turnLogs, array $corrections, int $skipPenalty = 2): int
    {
        $scoreChange = 0;

        foreach ($turnLogs as $log) {
            $wordId = $log->getWord()->getId();
            $correction = $corrections[$wordId] ?? null;
            if ($correction === null) {
                continue;
            }

            $checked = (bool) $correction['checked'];
            $status = $log->getStatus();

            if ($status === TurnLog::STATUS_GUESSED && $checked) {
                $scoreChange += 1;
            } elseif ($status === TurnLog::STATUS_GUESSED && !$checked) {
                $scoreChange += 0;
            } elseif ($status === TurnLog::STATUS_SKIPPED && !$checked) {
                $scoreChange -= $skipPenalty;
            } elseif ($status === TurnLog::STATUS_SKIPPED && $checked) {
                $scoreChange += 1;
            }
        }

        return $scoreChange;
    }

    /**
     * @param TurnLog[] $turnLogs
     * @param array<int, array{word_id: int, checked: bool}> $corrections
     * @return TurnLog[] logs that need difficulty update on correction
     */
    public function getLogsNeedingDifficultyUpdate(array $turnLogs, array $corrections): array
    {
        $needsUpdate = [];

        foreach ($turnLogs as $log) {
            $wordId = $log->getWord()->getId();
            $correction = $corrections[$wordId] ?? null;
            if ($correction === null) {
                continue;
            }

            if ($log->getStatus() === TurnLog::STATUS_SKIPPED && (bool) $correction['checked']) {
                $needsUpdate[] = $log;
            }
        }

        return $needsUpdate;
    }

    /**
     * @param TurnLog[] $turnLogs
     * @param array<int, array{word_id: int, checked: bool}> $corrections
     */
    public function wasStatusChanged(TurnLog $log, array $corrections): bool
    {
        $wordId = $log->getWord()->getId();
        $correction = $corrections[$wordId] ?? null;
        if ($correction === null) {
            return false;
        }

        $checked = (bool) $correction['checked'];
        $status = $log->getStatus();

        if ($status === TurnLog::STATUS_GUESSED && !$checked) {
            return true;
        }

        if ($status === TurnLog::STATUS_SKIPPED && $checked) {
            return true;
        }

        return false;
    }
}
