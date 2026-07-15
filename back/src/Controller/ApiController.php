<?php

namespace App\Controller;

use App\DTO\StartSessionRequest;
use App\Entity\GameSession;
use App\Entity\Team;
use App\Repository\GameSessionRepository;
use App\Repository\TeamRepository;
use App\Service\GameSessionService;
use App\Service\RoundTransitionManager;
use App\Service\WordSelector;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api')]
class ApiController extends AbstractController
{
    public function __construct(
        private GameSessionService $gameSessionService,
        private GameSessionRepository $sessionRepository,
        private TeamRepository $teamRepository,
        private WordSelector $wordSelector,
        private RoundTransitionManager $roundTransitionManager,
        private ValidatorInterface $validator,
    ) {
    }

    #[Route('/session/start', name: 'session_start', methods: ['POST'])]
    public function startSession(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];
        $dto = new StartSessionRequest();

        // Новый формат: teams[]
        if (isset($data['teams']) && is_array($data['teams'])) {
            $dto->teams = $data['teams'];
        } else {
            // Обратная совместимость со старым payload
            $dto->teams = [
                [
                    'name' => $data['team1_name'] ?? '',
                    'players' => $data['team1_players'] ?? [],
                ],
                [
                    'name' => $data['team2_name'] ?? '',
                    'players' => $data['team2_players'] ?? [],
                ],
            ];
        }

        $dto->totalWords = (int) ($data['total_words'] ?? 60);
        $dto->timeLimit = (int) ($data['time_limit'] ?? 60);
        $rawDifficulties = $data['difficulties'] ?? \App\Config\DifficultyConfig::allLevelIds();
        $rawCategories = $data['categories'] ?? \App\Config\CategoryConfig::allSlugs();
        $dto->difficulties = is_array($rawDifficulties)
            ? array_values(array_map('intval', $rawDifficulties))
            : \App\Config\DifficultyConfig::allLevelIds();
        $dto->categories = is_array($rawCategories)
            ? array_values(array_map(
                static fn ($c) => is_scalar($c) ? (string) $c : '',
                $rawCategories
            ))
            : \App\Config\CategoryConfig::allSlugs();

        $errors = $this->validator->validate($dto);
        if (count($errors) > 0) {
            $messages = [];
            foreach ($errors as $error) {
                $messages[] = $error->getMessage();
            }

            return $this->json(['errors' => $messages], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $normalizedTeams = [];
        foreach ($dto->teams as $team) {
            $normalizedTeams[] = [
                'name' => trim((string) $team['name']),
                'players' => array_values(array_map(
                    static fn ($p) => trim((string) $p),
                    $team['players'] ?? []
                )),
            ];
        }

        try {
            $session = $this->gameSessionService->createSession(
                $normalizedTeams,
                $dto->totalWords,
                $dto->timeLimit,
                $dto->difficulties,
                $dto->categories,
            );
        } catch (\InvalidArgumentException $e) {
            return $this->json(['errors' => [$e->getMessage()]], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return $this->json([
            'session_id' => $session->getId(),
            'redirect_url' => '/game/'.$session->getId(),
        ], Response::HTTP_CREATED);
    }

    #[Route('/session/{id}/state', name: 'session_state', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function getState(int $id): JsonResponse
    {
        $session = $this->sessionRepository->find($id);
        if (!$session) {
            return $this->json(['error' => 'Session not found'], Response::HTTP_NOT_FOUND);
        }

        return $this->json($this->gameSessionService->getState($session));
    }

    #[Route('/game/next-word', name: 'game_next_word', methods: ['GET'])]
    public function nextWord(Request $request): JsonResponse
    {
        $sessionId = (int) $request->query->get('session_id');
        $teamId = (int) $request->query->get('team_id');
        $round = (int) $request->query->get('round');
        $excludeRaw = $request->query->get('exclude_word_ids', '');
        $excludeWordIds = array_values(array_filter(array_map(
            'intval',
            explode(',', (string) $excludeRaw)
        )));

        $session = $this->sessionRepository->find($sessionId);
        $team = $this->teamRepository->find($teamId);

        if (!$session || !$team) {
            return $this->json(['error' => 'Not found'], Response::HTTP_NOT_FOUND);
        }

        $wordData = $this->wordSelector->getNextWordData($session, $team, $round, $excludeWordIds);
        if (!$wordData) {
            $remaining = $this->wordSelector->countRemaining($session, $round);

            return $this->json([
                'finished' => true,
                'message' => $remaining > 0
                    ? 'No more unused words this turn'
                    : 'No more words in this round',
                'remaining_words' => $remaining,
            ]);
        }

        $word = $wordData['word'];

        return $this->json([
            'word_id' => $word->getId(),
            'word_text' => $word->getText(),
            'difficulty' => $word->getDifficulty(),
            'finished' => false,
            'remaining_words' => $wordData['remaining_words'],
        ]);
    }

    #[Route('/game/turn/start', name: 'game_turn_start', methods: ['POST'])]
    public function startTurn(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];
        $sessionId = (int) ($data['session_id'] ?? 0);
        $playerId = (int) ($data['player_id'] ?? 0);

        $session = $this->sessionRepository->find($sessionId);
        if (!$session) {
            return $this->json(['error' => 'Session not found'], Response::HTTP_NOT_FOUND);
        }

        try {
            $turn = $this->gameSessionService->startTurn($session, $playerId);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        return $this->json(['turn_id' => $turn->getId()]);
    }

    #[Route('/game/action', name: 'game_action', methods: ['POST'])]
    public function gameAction(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];
        $sessionId = (int) ($data['session_id'] ?? 0);
        $playerId = (int) ($data['player_id'] ?? 0);
        $wordId = (int) ($data['word_id'] ?? 0);
        $action = $data['action'] ?? '';
        $turnId = isset($data['turn_id']) ? (int) $data['turn_id'] : null;

        if (!in_array($action, ['guess', 'skip'], true)) {
            return $this->json(['error' => 'Invalid action'], Response::HTTP_BAD_REQUEST);
        }

        $session = $this->sessionRepository->find($sessionId);
        if (!$session) {
            return $this->json(['error' => 'Session not found'], Response::HTTP_NOT_FOUND);
        }

        try {
            $this->gameSessionService->processAction($session, $playerId, $wordId, $action, $turnId);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        return $this->json([
            'success' => true,
            'word_id' => $wordId,
            'action' => $action,
        ]);
    }

    #[Route('/game/turn/finish', name: 'game_turn_finish', methods: ['POST'])]
    public function finishTurn(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];
        $sessionId = (int) ($data['session_id'] ?? 0);
        $turnId = (int) ($data['turn_id'] ?? 0);
        $corrections = $data['corrections'] ?? [];

        $session = $this->sessionRepository->find($sessionId);
        if (!$session) {
            return $this->json(['error' => 'Session not found'], Response::HTTP_NOT_FOUND);
        }

        try {
            $result = $this->gameSessionService->finishTurn($session, $turnId, $corrections);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        return $this->json(array_merge(['success' => true], $result));
    }

    #[Route('/round/next', name: 'round_next', methods: ['POST'])]
    public function nextRound(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];
        $sessionId = (int) ($data['session_id'] ?? 0);

        $session = $this->sessionRepository->find($sessionId);
        if (!$session) {
            return $this->json(['error' => 'Session not found'], Response::HTTP_NOT_FOUND);
        }

        try {
            $session = $this->roundTransitionManager->advanceToNextRound($session);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        return $this->json([
            'success' => true,
            'new_status' => $session->getStatus(),
        ]);
    }
}
