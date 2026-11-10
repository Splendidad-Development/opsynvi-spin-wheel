<?php

use App\Models\Challenge;
use App\Models\Question;
use App\Models\QuestionOption;
use Database\Seeders\DatabaseSeeder;

test('the seeders load the 15 Figma questions with exactly one correct answer each', function () {
    $this->seed(DatabaseSeeder::class);

    expect(Question::count())->toBe(15)
        ->and(Question::pluck('number')->sort()->values()->all())->toBe(range(1, 15))
        ->and(QuestionOption::count())->toBe(60)
        ->and(Challenge::count())->toBe(15);

    Question::with('options')->each(function (Question $question) {
        expect($question->options->pluck('letter')->all())->toBe(['A', 'B', 'C', 'D'])
            ->and($question->options->where('is_correct', true))->toHaveCount(1)
            ->and($question->explanation)->not->toBeEmpty();
    });

    $first = Question::where('number', 1)->firstOrFail();

    expect($first->prompt)->toBe('What are the two active pharmaceutical ingredients combined in Opsynvi and their respective pharmacological classes?')
        ->and($first->options()->where('is_correct', true)->value('letter'))->toBe('B');
});

test('the seeders can be run repeatedly without duplicating content', function () {
    $this->seed(DatabaseSeeder::class);
    $this->seed(DatabaseSeeder::class);

    expect(Question::count())->toBe(15)
        ->and(QuestionOption::count())->toBe(60)
        ->and(Challenge::count())->toBe(15);
});

test('the seeders load the 15 challenges with a title and an instruction each', function () {
    $this->seed(DatabaseSeeder::class);

    expect(Challenge::pluck('number')->sort()->values()->all())->toBe(range(1, 15));

    Challenge::all()->each(function (Challenge $challenge) {
        expect($challenge->title)->not->toBeEmpty()
            ->and($challenge->description)->not->toBeEmpty();
    });

    $fourth = Challenge::where('number', 4)->firstOrFail();

    expect($fourth->title)->toBe('Air Orchestra')
        ->and($fourth->description)->toBe('Conduct an imaginary orchestra for 10 seconds while your team follows your movements.');
});
