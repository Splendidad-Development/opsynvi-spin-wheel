<?php

namespace App\Http\Controllers;

use App\Enums\ChallengeOutcome;
use App\Http\Requests\AnswerQuestionRequest;
use App\Http\Requests\ResolveChallengeRequest;
use App\Http\Requests\SpinQuestionRequest;
use App\Http\Resources\GameSessionResource;
use App\Models\GameSession;
use App\Models\GameTurn;
use App\Services\GameService;

class GameTurnController extends Controller
{
    public function __construct(private GameService $game) {}

    /**
     * Spin the question wheel for the team whose turn it is.
     */
    public function store(SpinQuestionRequest $request, GameSession $gameSession): GameSessionResource
    {
        $this->game->spinQuestion($gameSession, $request->integer('team_id'));

        return $this->state($gameSession);
    }

    public function answer(AnswerQuestionRequest $request, GameSession $gameSession, GameTurn $gameTurn): GameSessionResource
    {
        $this->game->answer($gameSession, $gameTurn, $request->integer('option_id'));

        return $this->state($gameSession);
    }

    /**
     * Spin the challenge wheel for a team that could not answer its question.
     */
    public function challenge(GameSession $gameSession, GameTurn $gameTurn): GameSessionResource
    {
        $this->game->openChallenge($gameSession, $gameTurn);

        return $this->state($gameSession);
    }

    public function resolveChallenge(ResolveChallengeRequest $request, GameSession $gameSession, GameTurn $gameTurn): GameSessionResource
    {
        $this->game->resolveChallenge($gameSession, $gameTurn, $request->enum('outcome', ChallengeOutcome::class));

        return $this->state($gameSession);
    }

    /**
     * Finish an answered turn ("Done").
     */
    public function complete(GameSession $gameSession, GameTurn $gameTurn): GameSessionResource
    {
        $this->game->completeTurn($gameSession, $gameTurn);

        return $this->state($gameSession);
    }

    private function state(GameSession $gameSession): GameSessionResource
    {
        return GameSessionResource::make($gameSession->fresh());
    }
}
