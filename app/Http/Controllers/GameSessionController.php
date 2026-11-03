<?php

namespace App\Http\Controllers;

use App\Http\Middleware\IdentifyGamePlayer;
use App\Http\Resources\GameSessionResource;
use App\Models\GameSession;
use App\Services\GameService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GameSessionController extends Controller
{
    public function __construct(private GameService $game) {}

    /**
     * Return the current session's state, or null when no game has been started.
     */
    public function show(Request $request): JsonResponse
    {
        $session = $this->game->currentSession(IdentifyGamePlayer::playerId($request));

        return response()->json([
            'data' => $session ? GameSessionResource::make($session)->resolve() : null,
        ]);
    }

    /**
     * Start a game ("Get started"), or resume the current one if it already exists.
     */
    public function store(Request $request): GameSessionResource
    {
        return GameSessionResource::make($this->game->startOrResume(IdentifyGamePlayer::playerId($request)));
    }

    /**
     * Replace the given session with a fresh game ("Restart").
     */
    public function restart(GameSession $gameSession): GameSessionResource
    {
        return GameSessionResource::make($this->game->restart($gameSession));
    }
}
