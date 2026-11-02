<?php

namespace App\Http\Resources;

use App\Enums\GameSessionStatus;
use App\Enums\TurnStatus;
use App\Models\GameSession;
use App\Models\GameTeam;
use App\Models\GameTurn;
use App\Models\QuestionOption;
use App\Services\GameService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

/**
 * The full, authoritative game state the client renders from.
 *
 * The correct answer and its explanation are only included once a turn has been answered,
 * so they never reach the browser before the team commits to an answer.
 *
 * @mixin GameSession
 */
class GameSessionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $this->resource->loadMissing(['teams', 'turns.question.options', 'turns.challenge']);

        /** @var Collection<int, GameTurn> $turns */
        $turns = $this->turns;
        $activeTurn = $turns->last(fn (GameTurn $turn) => ! $turn->status->isResolved());
        $isInProgress = $this->status === GameSessionStatus::InProgress;
        $nextTurnNumber = $turns->count() + 1;

        $currentTeam = match (true) {
            $activeTurn !== null => $this->teams->firstWhere('id', $activeTurn->game_team_id),
            $isInProgress && $this->teams->isNotEmpty() && $nextTurnNumber <= GameService::totalTurns() => $this->teams[($nextTurnNumber - 1) % $this->teams->count()],
            default => null,
        };

        return [
            'id' => $this->id,
            'status' => $this->status->value,
            'is_completed' => $this->status === GameSessionStatus::Completed,
            'total_questions' => GameService::totalTurns(),
            'questions_per_team' => (int) config('game.questions_per_team'),
            'points_per_question' => GameService::pointsPerQuestion(),
            'completed_turns' => $turns->where('status', TurnStatus::Completed)->count(),
            'current_team_id' => $currentTeam?->id,
            'active_turn_id' => $activeTurn?->id,
            'teams' => $this->teams->map(fn (GameTeam $team) => [
                'id' => $team->id,
                'name' => $team->name,
                'position' => $team->position,
                'score' => $team->score,
            ])->values(),
            'turns' => $turns->map(fn (GameTurn $turn) => $this->turn($turn))->values(),
            'leaderboard' => $this->leaderboard(),
            'started_at' => $this->created_at?->toIso8601String(),
            'completed_at' => $this->completed_at?->toIso8601String(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function turn(GameTurn $turn): array
    {
        $isRevealed = $turn->answered_at !== null;
        $correctOption = $isRevealed ? $turn->question->options->firstWhere('is_correct', true) : null;

        return [
            'id' => $turn->id,
            'turn_number' => $turn->turn_number,
            'team_id' => $turn->game_team_id,
            'team_turn_number' => $turn->team_turn_number,
            'status' => $turn->status->value,
            'points' => $turn->points,
            'can_take_challenge' => $turn->canTakeChallenge(),
            'question' => [
                'number' => $turn->question->number,
                'prompt' => $turn->question->prompt,
                'options' => $turn->question->options->map(fn (QuestionOption $option) => [
                    'id' => $option->id,
                    'letter' => $option->letter,
                    'text' => $option->text,
                ])->values(),
            ],
            'selected_option_id' => $turn->selected_option_id,
            'is_correct' => $turn->is_correct,
            'correct_option_id' => $correctOption?->id,
            'explanation' => $isRevealed ? $turn->question->explanation : null,
            'challenge' => $turn->challenge ? [
                'number' => $turn->challenge->number,
                'description' => $turn->challenge->description,
            ] : null,
            'challenge_outcome' => $turn->challenge_outcome?->value,
        ];
    }

    /**
     * Teams ordered by score. Tied teams share a rank (1, 1, 3), so no winner is picked arbitrarily.
     *
     * @return list<array{team_id: int, name: string, score: int, rank: int, is_tied: bool}>
     */
    private function leaderboard(): array
    {
        $scores = $this->teams->pluck('score');

        return array_values($this->teams
            ->sortBy([['score', 'desc'], ['position', 'asc']])
            ->map(fn (GameTeam $team) => [
                'team_id' => $team->id,
                'name' => $team->name,
                'score' => $team->score,
                'rank' => $scores->filter(fn (int $score) => $score > $team->score)->count() + 1,
                'is_tied' => $scores->filter(fn (int $score) => $score === $team->score)->count() > 1,
            ])
            ->all());
    }
}
