<?php

namespace App\Controller;

use App\DTO\StartSessionRequest;
use App\Repository\GameSessionRepository;
use App\Service\GameSessionService;
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
        $rawCategories = $data['categories'] ?? [];
        $dto->difficulties = is_array($rawDifficulties)
            ? array_values(array_map('intval', $rawDifficulties))
            : \App\Config\DifficultyConfig::allLevelIds();
        $dto->categories = is_array($rawCategories)
            ? array_values(array_filter(array_map(
                static fn ($c) => is_scalar($c) ? (string) $c : '',
                $rawCategories
            )))
            : [];
        if ($dto->categories === []) {
            $dto->categories = \App\Config\CategoryConfig::allSlugs();
        }

        $dto->skipPenalty = max(0, min(5, (int) ($data['skip_penalty'] ?? 2)));
        $dto->lastWordCommon = (bool) ($data['last_word_common'] ?? true);

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
            $players = [];
            foreach ($team['players'] ?? [] as $p) {
                if (is_array($p)) {
                    $players[] = [
                        'name' => trim((string) ($p['name'] ?? '')),
                        'avatar_id' => (string) ($p['avatar_id'] ?? 'm01'),
                    ];
                } else {
                    $players[] = [
                        'name' => trim((string) $p),
                        'avatar_id' => 'm01',
                    ];
                }
            }
            $normalizedTeams[] = [
                'name' => trim((string) $team['name']),
                'players' => array_values($players),
                'hat_id' => isset($team['hat_id']) ? (string) $team['hat_id'] : 'tophat',
            ];
        }

        try {
            $session = $this->gameSessionService->createSession(
                $normalizedTeams,
                $dto->totalWords,
                $dto->timeLimit,
                $dto->difficulties,
                $dto->categories,
                $dto->skipPenalty,
                $dto->lastWordCommon,
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

    #[Route('/session/{id}/recap', name: 'session_recap', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function getRecap(int $id): JsonResponse
    {
        $session = $this->sessionRepository->find($id);
        if (!$session) {
            return $this->json(['error' => 'Session not found'], Response::HTTP_NOT_FOUND);
        }

        return $this->json($this->gameSessionService->getRecap($session));
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
            $payload = $this->gameSessionService->getTurnStartPayload($session, $turn);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        return $this->json($payload);
    }

    #[Route('/game/turn/finish', name: 'game_turn_finish', methods: ['POST'])]
    public function finishTurn(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];
        $sessionId = (int) ($data['session_id'] ?? 0);
        $turnId = (int) ($data['turn_id'] ?? 0);
        $corrections = $data['corrections'] ?? [];
        $actions = $data['actions'] ?? [];
        $lastWord = null;
        if (isset($data['last_word']) && is_array($data['last_word'])) {
            $lw = $data['last_word'];
            $lastWord = [
                'word_id' => (int) ($lw['word_id'] ?? 0),
                'award_team_id' => array_key_exists('award_team_id', $lw) && $lw['award_team_id'] !== null
                    ? (int) $lw['award_team_id']
                    : null,
            ];
            if ($lastWord['word_id'] <= 0) {
                $lastWord = null;
            }
        }

        if (!is_array($corrections)) {
            $corrections = [];
        }
        if (!is_array($actions)) {
            $actions = [];
        }

        $normalizedActions = [];
        foreach ($actions as $item) {
            if (!is_array($item)) {
                continue;
            }
            $normalizedActions[] = [
                'word_id' => (int) ($item['word_id'] ?? 0),
                'action' => (string) ($item['action'] ?? ''),
            ];
        }

        $session = $this->sessionRepository->find($sessionId);
        if (!$session) {
            return $this->json(['error' => 'Session not found'], Response::HTTP_NOT_FOUND);
        }

        try {
            $result = $this->gameSessionService->finishTurn(
                $session,
                $turnId,
                $corrections,
                $normalizedActions,
                $lastWord,
            );
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        return $this->json(array_merge(['success' => true], $result));
    }
}
