<?php

namespace Database\Factories;

use App\Enums\TurnStatus;
use App\Models\GameSession;
use App\Models\GameTeam;
use App\Models\GameTurn;
use App\Models\Question;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GameTurn>
 */
class GameTurnFactory extends Factory
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
            'game_team_id' => fn (array $attributes) => GameTeam::factory()->state([
                'game_session_id' => $attributes['game_session_id'],
            ]),
            'turn_number' => 1,
            'team_turn_number' => 1,
            'question_id' => Question::factory()->withOptions(),
            'status' => TurnStatus::QuestionOpened,
            'points' => 0,
        ];
    }
}
