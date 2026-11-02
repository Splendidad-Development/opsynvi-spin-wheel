<?php

namespace Database\Factories;

use App\Models\GameSession;
use App\Models\GameTeam;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GameTeam>
 */
class GameTeamFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'game_session_id' => GameSession::factory(),
            'position' => fake()->numberBetween(1, 3),
            'name' => fake()->unique()->word(),
            'score' => 0,
        ];
    }
}
