<?php

namespace Database\Factories;

use App\Models\Question;
use App\Models\QuestionOption;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Question>
 */
class QuestionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'number' => fake()->unique()->numberBetween(1, 255),
            'prompt' => fake()->sentence().'?',
            'explanation' => fake()->sentence(),
        ];
    }

    /**
     * Give the question options A–D, with the given letter marked correct.
     */
    public function withOptions(string $correctLetter = 'A'): static
    {
        return $this->afterCreating(function (Question $question) use ($correctLetter): void {
            foreach (['A', 'B', 'C', 'D'] as $letter) {
                QuestionOption::factory()->for($question)->create([
                    'letter' => $letter,
                    'is_correct' => $letter === $correctLetter,
                ]);
            }
        });
    }
}
