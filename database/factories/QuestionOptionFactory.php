<?php

namespace Database\Factories;

use App\Models\Question;
use App\Models\QuestionOption;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QuestionOption>
 */
class QuestionOptionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'question_id' => Question::factory(),
            'letter' => fake()->randomElement(['A', 'B', 'C', 'D']),
            'text' => fake()->sentence(),
            'is_correct' => false,
        ];
    }

    /**
     * Mark the option as the correct answer.
     */
    public function correct(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_correct' => true,
        ]);
    }
}
