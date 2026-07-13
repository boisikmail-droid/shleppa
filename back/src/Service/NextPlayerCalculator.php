<?php

namespace App\Service;

use App\Entity\GameSession;
use App\Entity\Player;
use App\Entity\Team;

class NextPlayerCalculator
{
    /** @return array{0: Team, 1: Player} */
    public function getNextPlayer(GameSession $session): array
    {
        $turnOrder = $this->buildTurnOrder($session);

        if ($turnOrder === []) {
            throw new \RuntimeException('No players in session');
        }

        $currentTeam = $session->getCurrentTeam();
        $currentPlayer = $session->getCurrentPlayer();

        if (!$currentTeam || !$currentPlayer) {
            return $turnOrder[0];
        }

        $currentIndex = $this->findTurnIndex($turnOrder, $currentTeam, $currentPlayer);
        if ($currentIndex === null || !isset($turnOrder[$currentIndex + 1])) {
            return $turnOrder[0];
        }

        return $turnOrder[$currentIndex + 1];
    }

    /**
     * Чередование: T1P0 → T2P0 → T1P1 → T2P1 → ...
     *
     * @return array<int, array{0: Team, 1: Player}>
     */
    public function buildTurnOrder(GameSession $session): array
    {
        $teams = $session->getTeams()->toArray();
        if (count($teams) < 2) {
            throw new \RuntimeException('Need at least 2 teams');
        }

        usort($teams, fn (Team $a, Team $b) => $a->getId() <=> $b->getId());

        $team1 = $teams[0];
        $team2 = $teams[1];
        $players1 = $team1->getPlayers()->toArray();
        $players2 = $team2->getPlayers()->toArray();
        $maxIndex = max(count($players1), count($players2)) - 1;

        $order = [];
        for ($i = 0; $i <= $maxIndex; ++$i) {
            if (isset($players1[$i])) {
                $order[] = [$team1, $players1[$i]];
            }
            if (isset($players2[$i])) {
                $order[] = [$team2, $players2[$i]];
            }
        }

        return $order;
    }

    /** @param array<int, array{0: Team, 1: Player}> $turnOrder */
    private function findTurnIndex(array $turnOrder, Team $team, Player $player): ?int
    {
        foreach ($turnOrder as $index => [$t, $p]) {
            if ($t->getId() === $team->getId() && $p->getId() === $player->getId()) {
                return $index;
            }
        }

        return null;
    }

    /** @return array{0: Team, 1: Player} */
    public function getNextPlayerFrom(Team $currentTeam, Player $currentPlayer, GameSession $session): array
    {
        $savedTeam = $session->getCurrentTeam();
        $savedPlayer = $session->getCurrentPlayer();
        $session->setCurrentTeam($currentTeam);
        $session->setCurrentPlayer($currentPlayer);
        $result = $this->getNextPlayer($session);
        $session->setCurrentTeam($savedTeam);
        $session->setCurrentPlayer($savedPlayer);

        return $result;
    }

    /** @return array<int, array{team_name: string, player_name: string}> */
    public function peekNextPlayers(GameSession $session, int $count = 4): array
    {
        $result = [];
        $simTeam = $session->getCurrentTeam();
        $simPlayer = $session->getCurrentPlayer();

        if (!$simTeam || !$simPlayer) {
            return $result;
        }

        for ($i = 0; $i < $count; $i++) {
            [$nextTeam, $nextPlayer] = $this->getNextPlayerFrom($simTeam, $simPlayer, $session);
            $result[] = [
                'team_name' => $nextTeam->getName(),
                'player_name' => $nextPlayer->getName(),
            ];
            $simTeam = $nextTeam;
            $simPlayer = $nextPlayer;
        }

        return $result;
    }

    public function isRoundComplete(GameSession $session, Team $nextTeam, Player $nextPlayer): bool
    {
        $startTeam = $session->getRoundStartTeam();
        $startPlayer = $session->getRoundStartPlayer();

        if (!$startTeam || !$startPlayer) {
            return false;
        }

        return $nextTeam->getId() === $startTeam->getId()
            && $nextPlayer->getId() === $startPlayer->getId();
    }
}
