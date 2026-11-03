<?php

namespace App\Models;

use App\Enums\GameSessionStatus;
use Database\Factories\GameSessionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string|null $player_id
 * @property GameSessionStatus $status
 * @property bool|null $is_current
 * @property Carbon|null $completed_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['player_id', 'status', 'is_current', 'completed_at'])]
class GameSession extends Model
{
    /** @use HasFactory<GameSessionFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => GameSessionStatus::class,
            'is_current' => 'boolean',
            'completed_at' => 'datetime',
        ];
    }

    /**
     * @return HasMany<GameTeam, $this>
     */
    public function teams(): HasMany
    {
        return $this->hasMany(GameTeam::class)->orderBy('position');
    }

    /**
     * @return HasMany<GameTurn, $this>
     */
    public function turns(): HasMany
    {
        return $this->hasMany(GameTurn::class)->orderBy('turn_number');
    }

    /**
     * Scope the query to the session the game screen should restore.
     *
     * @param  Builder<GameSession>  $query
     */
    public function scopeCurrent(Builder $query): void
    {
        $query->where('is_current', true);
    }

    /**
     * Scope the query to the sessions played on the given device.
     *
     * @param  Builder<GameSession>  $query
     */
    public function scopeOwnedBy(Builder $query, string $playerId): void
    {
        $query->where('player_id', $playerId);
    }

    public function isInProgress(): bool
    {
        return $this->status === GameSessionStatus::InProgress;
    }
}
