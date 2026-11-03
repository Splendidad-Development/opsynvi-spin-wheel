<?php

namespace App\Exceptions;

use App\Http\Resources\GameSessionResource;
use App\Models\GameSession;
use Exception;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Thrown when an action is not allowed in the game's current state (wrong team, question
 * already answered, duplicate request…). Responds with 409 and the authoritative game state
 * so the client can resynchronise instead of guessing.
 */
class InvalidGameActionException extends Exception
{
    public function __construct(string $message, public readonly ?GameSession $gameSession = null)
    {
        parent::__construct($message);
    }

    /**
     * Render the exception as an HTTP response.
     */
    public function render(): JsonResponse
    {
        $gameSession = $this->gameSession?->fresh();

        return response()->json([
            'message' => $this->getMessage(),
            'data' => $gameSession ? GameSessionResource::make($gameSession)->resolve() : null,
        ], Response::HTTP_CONFLICT);
    }
}
