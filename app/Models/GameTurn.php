<?php

namespace App\Models;

use App\Enums\ChallengeOutcome;
use App\Enums\TurnStatus;
use Database\Factories\GameTurnFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $game_session_id
 * @property int $game_team_id
 * @property int $turn_number
 * @property int $team_turn_number
 * @property int $question_id
 * @property TurnStatus $status
 * @property int|null $selected_option_id
 * @property bool|null $is_correct
 * @property int|null $challenge_id
 * @property ChallengeOutcome|null $challenge_outcome
 * @property int $points
 * @property Carbon|null $answered_at
 * @property Carbon|null $challenge_opened_at
 * @property Carbon|null $completed_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'game_session_id',
    'game_team_id',
    'turn_number',
    'team_turn_number',
    'question_id',
    'status',
    'selected_option_id',
    'is_correct',
    'challenge_id',
    'challenge_outcome',
    'points',
    'answered_at',
    'challenge_opened_at',
    'completed_at',
])]
class GameTurn extends Model
{
    /** @use HasFactory<GameTurnFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => TurnStatus::class,
            'is_correct' => 'boolean',
            'challenge_outcome' => ChallengeOutcome::class,
            'answered_at' => 'datetime',
            'challenge_opened_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<GameSession, $this>
     */
    public function gameSession(): BelongsTo
    {
        return $this->belongsTo(GameSession::class);
    }

    /**
     * @return BelongsTo<GameTeam, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(GameTeam::class, 'game_team_id');
    }

    /**
     * @return BelongsTo<Question, $this>
     */
    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }

    /**
     * @return BelongsTo<QuestionOption, $this>
     */
    public function selectedOption(): BelongsTo
    {
        return $this->belongsTo(QuestionOption::class, 'selected_option_id');
    }

    /**
     * @return BelongsTo<Challenge, $this>
     */
    public function challenge(): BelongsTo
    {
        return $this->belongsTo(Challenge::class);
    }

    /**
     * A challenge may replace an unanswered question or follow a wrong answer,
     * but never a correct one (the team already holds the question's points).
     */
    public function canTakeChallenge(): bool
    {
        return $this->status === TurnStatus::QuestionOpened
            || ($this->status === TurnStatus::Answered && $this->is_correct === false);
    }
}
