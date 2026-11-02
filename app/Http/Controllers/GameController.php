<?php

namespace App\Http\Controllers;

use App\Http\Resources\GameSessionResource;
use App\Services\GameService;
use Inertia\Inertia;
use Inertia\Response;

class GameController extends Controller
{
    /**
     * Render the game screen, restoring the current session if there is one.
     *
     * Loading the page never creates or changes a session.
     */
    public function __invoke(GameService $game): Response
    {
        $session = $game->currentSession();

        return Inertia::render('Game', [
            'game' => $session ? GameSessionResource::make($session)->resolve() : null,
        ]);
    }
}
