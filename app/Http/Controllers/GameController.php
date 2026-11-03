<?php

namespace App\Http\Controllers;

use App\Http\Middleware\IdentifyGamePlayer;
use App\Http\Resources\GameSessionResource;
use App\Services\GameService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class GameController extends Controller
{
    /**
     * Render the game screen, restoring this device's current session if there is one.
     *
     * Loading the page never creates or changes a session.
     */
    public function __invoke(Request $request, GameService $game): Response
    {
        $session = $game->currentSession(IdentifyGamePlayer::playerId($request));

        return Inertia::render('Game', [
            'game' => $session ? GameSessionResource::make($session)->resolve() : null,
        ]);
    }
}
