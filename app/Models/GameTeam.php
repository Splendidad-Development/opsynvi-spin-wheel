<?php

namespace App\Models;

use Database\Factories\GameTeamFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $game_session_id
 * @property int $position
 * @property string $name
 * @property int $score
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['game_session_id', 'position', 'name', 'score'])]
class GameTeam extends Model
{
    /** @use HasFactory<GameTeamFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<GameSession, $this>
     */
    public function gameSession(): BelongsTo
    {
        return $this->belongsTo(GameSession::class);
    }

    /**
     * @return HasMany<GameTurn, $this>
     */
    public function turns(): HasMany
    {
        return $this->hasMany(GameTurn::class)->orderBy('team_turn_number');
    }
}
