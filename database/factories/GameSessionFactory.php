<?php

namespace Database\Factories;

use App\Enums\GameSessionStatus;
use App\Models\GameSession;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GameSession>
 */
class GameSessionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'status' => GameSessionStatus::InProgress,
            'is_current' => null,
        ];
    }

    /**
     * Mark the session as the one the game screen restores.
     */
    public function current(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_current' => true,
        ]);
    }

    /**
     * Mark the session as replaced by a restart.
     */
    public function abandoned(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => GameSessionStatus::Abandoned,
            'is_current' => null,
        ]);
    }
}
