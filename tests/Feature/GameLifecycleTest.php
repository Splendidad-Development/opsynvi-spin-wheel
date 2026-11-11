<?php

use App\Enums\GameSessionStatus;
use App\Enums\TurnStatus;
use App\Http\Middleware\IdentifyGamePlayer;
use App\Models\GameSession;
use App\Models\GameTurn;
use App\Models\Question;
use App\Models\QuestionOption;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);

    // Every request in a test comes from the same device unless the test switches players.
    $this->withCredentials()->withCookie(IdentifyGamePlayer::COOKIE, (string) Str::uuid());
});

/**
 * @return array<string, mixed>
 */
function startGame(): array
{
    return test()->postJson(route('game.sessions.store'))->assertSuccessful()->json('data');
}

/**
 * Spin for the given team, or else for the team in play, or else for the first team still free to pick.
 *
 * @param  array<string, mixed>  $game
 */
function spin(array $game, ?int $teamId = null): TestResponse
{
    return test()->postJson(route('game.turns.store', $game['id']), [
        'team_id' => $teamId ?? $game['current_team_id'] ?? $game['selectable_team_ids'][0] ?? null,
    ]);
}

/**
 * @param  array<string, mixed>  $game
 * @return array<string, mixed>
 */
function activeTurn(array $game): array
{
    return collect($game['turns'])->firstWhere('id', $game['active_turn_id']);
}

/**
 * @param  array<string, mixed>  $game
 */
function answer(array $game, bool $correctly): TestResponse
{
    $turn = activeTurn($game);
    $question = Question::where('number', $turn['question']['number'])->firstOrFail();
    $option = $question->options()->where('is_correct', $correctly)->firstOrFail();

    return test()->postJson(route('game.turns.answer', [$game['id'], $turn['id']]), [
        'option_id' => $option->id,
    ]);
}

/**
 * @param  array<string, mixed>  $game
 */
function finishTurn(array $game): TestResponse
{
    return test()->postJson(route('game.turns.complete', [$game['id'], $game['active_turn_id']]));
}

/**
 * Play one full turn and return the updated game state.
 *
 * @param  array<string, mixed>  $game
 * @return array<string, mixed>
 */
function playTurn(array $game, bool $correctly = true): array
{
    $game = spin($game)->assertOk()->json('data');
    $game = answer($game, $correctly)->assertOk()->json('data');

    return finishTurn($game)->assertOk()->json('data');
}

test('a new game has three teams with zero scores and all 15 questions available', function () {
    $game = startGame();

    expect($game['status'])->toBe('in_progress')
        ->and($game['teams'])->toHaveCount(3)
        ->and(collect($game['teams'])->pluck('name')->all())->toBe(['Symphony', 'Harmony', 'Orchestra'])
        ->and(collect($game['teams'])->pluck('score')->all())->toBe([0, 0, 0])
        ->and($game['turns'])->toBeEmpty()
        ->and($game['total_questions'])->toBe(15)
        ->and($game['current_team_id'])->toBeNull()
        ->and($game['selectable_team_ids'])->toBe(collect($game['teams'])->pluck('id')->all());
});

test('a full game assigns every question exactly once, with each team playing its five in a row', function () {
    $game = startGame();

    for ($i = 0; $i < 15; $i++) {
        $game = playTurn($game);
    }

    $turns = collect($game['turns']);
    $teamIds = collect($game['teams'])->pluck('id');

    expect($turns->pluck('question.number')->sort()->values()->all())->toBe(range(1, 15))
        ->and($turns->pluck('team_id')->all())->toBe($teamIds->flatMap(fn (int $id) => array_fill(0, 5, $id))->all())
        ->and($turns->countBy('team_id')->values()->all())->toBe([5, 5, 5])
        ->and($game['status'])->toBe('completed')
        ->and($game['is_completed'])->toBeTrue()
        ->and($game['current_team_id'])->toBeNull()
        ->and($game['selectable_team_ids'])->toBe([])
        ->and(collect($game['teams'])->pluck('score')->all())->toBe([50, 50, 50]);
});

test('questions are drawn in a random order', function () {
    $orders = collect(range(1, 2))->map(function () {
        $game = startGame();

        for ($i = 0; $i < 15; $i++) {
            $game = playTurn($game);
        }

        test()->postJson(route('game.sessions.restart', $game['id']))->assertCreated();

        return collect($game['turns'])->pluck('question.number')->implode(',');
    });

    // Two identical 15-question orders by chance has a probability of 1 in 15! (~1.3 × 10¹²).
    expect($orders[0])->not->toBe($orders[1]);
});

test('a correct answer awards 10 points and a wrong answer awards none', function () {
    $game = startGame();
    $symphony = $game['teams'][0]['id'];

    $game = playTurn($game, correctly: true);
    $game = playTurn($game, correctly: false);

    $scores = collect($game['teams'])->pluck('score', 'id');

    expect($scores[$symphony])->toBe(10)
        ->and($game['turns'][0]['points'])->toBe(10)
        ->and($game['turns'][1]['points'])->toBe(0)
        ->and($game['turns'][1]['is_correct'])->toBeFalse();
});

test('scores and correctness sent by the client are ignored', function () {
    $game = spin(startGame())->json('data');
    $turn = activeTurn($game);
    $wrongOption = QuestionOption::whereHas('question', fn ($query) => $query->where('number', $turn['question']['number']))
        ->where('is_correct', false)
        ->firstOrFail();

    $game = $this->postJson(route('game.turns.answer', [$game['id'], $turn['id']]), [
        'option_id' => $wrongOption->id,
        'is_correct' => true,
        'points' => 50,
    ])->assertOk()->json('data');

    expect($game['teams'][0]['score'])->toBe(0)
        ->and(activeTurn($game)['is_correct'])->toBeFalse();
});

test('the correct answer is hidden until the team answers', function () {
    $game = spin(startGame())->json('data');

    expect(activeTurn($game)['correct_option_id'])->toBeNull()
        ->and(activeTurn($game)['explanation'])->toBeNull();

    $game = answer($game, correctly: false)->json('data');

    expect(activeTurn($game)['correct_option_id'])->not->toBeNull()
        ->and(activeTurn($game)['explanation'])->not->toBeNull();
});

test('a question cannot be answered twice and points are awarded only once', function () {
    $game = spin(startGame())->json('data');

    answer($game, correctly: true)->assertOk();
    answer($game, correctly: true)->assertConflict()->assertJsonPath('data.teams.0.score', 10);
    answer($game, correctly: false)->assertConflict();

    expect(GameSession::first()->teams()->first()->score)->toBe(10);
});

test('an answer must be one of the drawn question options', function () {
    $game = spin(startGame())->json('data');
    $otherQuestion = Question::where('number', '!=', activeTurn($game)['question']['number'])->firstOrFail();

    $this->postJson(route('game.turns.answer', [$game['id'], $game['active_turn_id']]), [
        'option_id' => $otherQuestion->options()->where('is_correct', true)->value('id'),
    ])->assertUnprocessable()->assertJsonValidationErrors('option_id');
});

test('the host can start with any team', function () {
    $game = startGame();
    $orchestra = $game['teams'][2]['id'];

    $game = spin($game, teamId: $orchestra)->assertOk()->json('data');

    expect(activeTurn($game)['team_id'])->toBe($orchestra)
        ->and($game['current_team_id'])->toBe($orchestra)
        ->and($game['selectable_team_ids'])->toBe([]);
});

test('a team keeps playing until all five of its questions are done', function () {
    $game = startGame();
    $harmony = $game['teams'][1]['id'];

    $game = spin($game, teamId: $harmony)->assertOk()->json('data');
    $game = answer($game, correctly: true)->json('data');
    $game = finishTurn($game)->assertOk()->json('data');

    expect($game['current_team_id'])->toBe($harmony);

    spin($game, teamId: $game['teams'][0]['id'])
        ->assertConflict()
        ->assertJsonPath('message', 'It is Team Harmony\'s turn.');

    expect(GameTurn::count())->toBe(1);
});

test('after a team finishes, the host picks the next team from those that have not played', function () {
    $game = startGame();
    [$symphony, $harmony, $orchestra] = collect($game['teams'])->pluck('id')->all();

    $game = spin($game, teamId: $harmony)->json('data');
    $game = answer($game, correctly: true)->json('data');
    $game = finishTurn($game)->json('data');

    for ($i = 0; $i < 4; $i++) {
        $game = playTurn($game);
    }

    expect($game['current_team_id'])->toBeNull()
        ->and($game['selectable_team_ids'])->toBe([$symphony, $orchestra])
        ->and(collect($game['turns'])->where('team_id', $harmony)->pluck('team_turn_number')->all())->toBe([1, 2, 3, 4, 5]);

    spin($game, teamId: $harmony)
        ->assertConflict()
        ->assertJsonPath('message', 'Team Harmony has already played all its questions.');

    $game = spin($game, teamId: $orchestra)->assertOk()->json('data');

    expect(activeTurn($game)['team_id'])->toBe($orchestra);
});

test('a team that is not part of the game cannot be picked', function () {
    $game = startGame();

    spin($game, teamId: 999_999)
        ->assertConflict()
        ->assertJsonPath('message', 'Choose one of the game teams.');

    expect(GameTurn::count())->toBe(0);
});

test('the next team cannot spin until the current turn is resolved', function () {
    $game = spin(startGame())->assertOk()->json('data');

    spin($game, teamId: $game['teams'][1]['id'])->assertConflict();
    spin($game, teamId: $game['teams'][0]['id'])->assertConflict();

    expect(GameTurn::count())->toBe(1);
});

test('a turn cannot be finished without an answer or a challenge', function () {
    $game = spin(startGame())->json('data');

    finishTurn($game)->assertConflict();

    expect(GameTurn::first()->status)->toBe(TurnStatus::QuestionOpened);
});

test('an accepted challenge awards 10 points and ends the turn', function () {
    $game = spin(startGame())->json('data');
    $questionNumber = activeTurn($game)['question']['number'];

    $game = $this->postJson(route('game.turns.challenge', [$game['id'], $game['active_turn_id']]))
        ->assertOk()
        ->json('data');

    $turn = activeTurn($game);

    expect($turn['status'])->toBe('challenge_pending')
        ->and($turn['challenge']['number'])->toBeBetween(1, 15);

    $game = $this->postJson(route('game.turns.challenge.resolve', [$game['id'], $turn['id']]), ['outcome' => 'accepted'])
        ->assertOk()
        ->json('data');

    expect($game['teams'][0]['score'])->toBe(10)
        ->and($game['turns'][0]['status'])->toBe('completed')
        ->and($game['turns'][0]['challenge_outcome'])->toBe('accepted')
        ->and($game['active_turn_id'])->toBeNull()
        ->and($game['current_team_id'])->toBe($game['teams'][0]['id']);

    // The challenged question stays used for the rest of the game.
    for ($i = 0; $i < 14; $i++) {
        $game = playTurn($game);
    }

    expect(collect($game['turns'])->pluck('question.number')->filter(fn ($number) => $number === $questionNumber))->toHaveCount(1);
});

test('a declined challenge awards no points', function () {
    $game = spin(startGame())->json('data');
    $game = answer($game, correctly: false)->json('data');

    $game = $this->postJson(route('game.turns.challenge', [$game['id'], $game['active_turn_id']]))->assertOk()->json('data');
    $game = $this->postJson(route('game.turns.challenge.resolve', [$game['id'], $game['active_turn_id']]), ['outcome' => 'declined'])
        ->assertOk()
        ->json('data');

    expect($game['teams'][0]['score'])->toBe(0)
        ->and($game['turns'][0]['challenge_outcome'])->toBe('declined');
});

test('a challenge outcome is recorded only once', function () {
    $game = spin(startGame())->json('data');
    $game = $this->postJson(route('game.turns.challenge', [$game['id'], $game['active_turn_id']]))->json('data');
    $turnId = $game['active_turn_id'];

    $this->postJson(route('game.turns.challenge.resolve', [$game['id'], $turnId]), ['outcome' => 'accepted'])->assertOk();
    $this->postJson(route('game.turns.challenge.resolve', [$game['id'], $turnId]), ['outcome' => 'accepted'])->assertConflict();
    $this->postJson(route('game.turns.challenge.resolve', [$game['id'], $turnId]), ['outcome' => 'declined'])->assertConflict();

    expect(GameSession::first()->teams()->first()->score)->toBe(10);
});

test('a challenge cannot be opened twice or after a correct answer', function () {
    $game = spin(startGame())->json('data');
    $route = route('game.turns.challenge', [$game['id'], $game['active_turn_id']]);

    $this->postJson($route)->assertOk();
    $this->postJson($route)->assertConflict();

    $game = $this->postJson(route('game.turns.challenge.resolve', [$game['id'], $game['active_turn_id']]), ['outcome' => 'declined'])->json('data');
    $game = spin($game)->json('data');
    $game = answer($game, correctly: true)->json('data');

    $this->postJson(route('game.turns.challenge', [$game['id'], $game['active_turn_id']]))->assertConflict();

    expect(GameTurn::whereNotNull('challenge_id')->count())->toBe(1);
});

test('a challenge outcome must be accepted or declined', function () {
    $game = spin(startGame())->json('data');
    $game = $this->postJson(route('game.turns.challenge', [$game['id'], $game['active_turn_id']]))->json('data');

    $this->postJson(route('game.turns.challenge.resolve', [$game['id'], $game['active_turn_id']]), ['outcome' => 'maybe'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('outcome');
});

test('finished turns cannot be acted on again', function () {
    $game = playTurn(startGame());
    $finishedTurnId = $game['turns'][0]['id'];

    $this->postJson(route('game.turns.complete', [$game['id'], $finishedTurnId]))->assertConflict();
    $this->postJson(route('game.turns.challenge', [$game['id'], $finishedTurnId]))->assertConflict();
});

test('a turn from another session cannot be used', function () {
    $game = spin(startGame())->json('data');
    $otherSession = GameSession::factory()->current()->create();

    $this->postJson(route('game.turns.complete', [$otherSession->id, $game['active_turn_id']]))->assertNotFound();
});

test('no actions are allowed once the game is completed', function () {
    $game = startGame();

    for ($i = 0; $i < 15; $i++) {
        $game = playTurn($game);
    }

    spin($game, teamId: $game['teams'][0]['id'])->assertConflict()->assertJsonPath('message', 'This game is already finished.');
    $this->postJson(route('game.turns.challenge', [$game['id'], $game['turns'][14]['id']]))->assertConflict();
});

test('the leaderboard ranks teams by score and shares ranks on ties', function () {
    $game = startGame();

    // Teams play their five in a row: Symphony 5 correct, Harmony 0 correct, Orchestra 0 correct.
    for ($i = 0; $i < 15; $i++) {
        $game = playTurn($game, correctly: $i < 5);
    }

    expect($game['leaderboard'])->toBe([
        ['team_id' => $game['teams'][0]['id'], 'name' => 'Symphony', 'score' => 50, 'rank' => 1, 'is_tied' => false],
        ['team_id' => $game['teams'][1]['id'], 'name' => 'Harmony', 'score' => 0, 'rank' => 2, 'is_tied' => true],
        ['team_id' => $game['teams'][2]['id'], 'name' => 'Orchestra', 'score' => 0, 'rank' => 2, 'is_tied' => true],
    ]);
});

test('a three-way tie gives every team first place', function () {
    $game = startGame();

    for ($i = 0; $i < 15; $i++) {
        // The first question of each team's set is the only correct one.
        $game = playTurn($game, correctly: $i % 5 === 0);
    }

    expect(collect($game['leaderboard'])->pluck('rank')->all())->toBe([1, 1, 1]);
});

test('loading the game page never creates a session', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('Game')->where('game', null));

    $this->get(route('home'))->assertOk();

    expect(GameSession::count())->toBe(0);
});

test('reloading restores the exact state of the current session', function () {
    $game = playTurn(startGame());
    $game = spin($game)->json('data');
    $game = answer($game, correctly: true)->json('data');

    $this->get(route('home'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('Game')
            ->where('game.id', $game['id'])
            ->where('game.active_turn_id', $game['active_turn_id'])
            ->where('game.turns.1.question.number', $game['turns'][1]['question']['number'])
            ->where('game.turns.1.status', 'answered')
            ->where('game.teams.0.score', 20)
            ->where('game.teams.1.score', 0)
        );

    $this->getJson(route('game.current'))->assertOk()->assertJsonPath('data', $game);

    expect(GameSession::count())->toBe(1)
        ->and(GameTurn::count())->toBe(2);
});

test('starting a game twice resumes the current session instead of creating another', function () {
    $first = $this->postJson(route('game.sessions.store'))->assertCreated()->json('data');
    $second = $this->postJson(route('game.sessions.store'))->assertOk()->json('data');

    expect($second['id'])->toBe($first['id'])
        ->and(GameSession::count())->toBe(1);
});

test('restart creates a fresh session and keeps the previous one for history', function () {
    $game = playTurn(startGame());

    $fresh = $this->postJson(route('game.sessions.restart', $game['id']))->assertCreated()->json('data');

    expect($fresh['id'])->not->toBe($game['id'])
        ->and($fresh['turns'])->toBeEmpty()
        ->and(collect($fresh['teams'])->pluck('score')->all())->toBe([0, 0, 0]);

    $previous = GameSession::find($game['id']);

    expect($previous->status)->toBe(GameSessionStatus::Abandoned)
        ->and($previous->is_current)->toBeNull()
        ->and($previous->turns()->count())->toBe(1)
        ->and(GameSession::current()->sole()->id)->toBe($fresh['id']);
});

test('repeating a restart request does not create extra sessions', function () {
    $game = startGame();

    $first = $this->postJson(route('game.sessions.restart', $game['id']))->json('data');
    $second = $this->postJson(route('game.sessions.restart', $game['id']))->json('data');

    expect($second['id'])->toBe($first['id'])
        ->and(GameSession::count())->toBe(2);
});

test('a completed game stays completed when restarted', function () {
    $game = startGame();

    for ($i = 0; $i < 15; $i++) {
        $game = playTurn($game);
    }

    $this->postJson(route('game.sessions.restart', $game['id']))->assertCreated();

    expect(GameSession::find($game['id'])->status)->toBe(GameSessionStatus::Completed);
});

test('a replaced session rejects further actions', function () {
    $game = startGame();
    $this->postJson(route('game.sessions.restart', $game['id']));

    spin($game)->assertConflict()->assertJsonPath('message', 'This game was replaced by a newer one.');
});

test('a first visit gives the device its own player cookie', function () {
    $this->defaultCookies = [];

    $this->get(route('home'))
        ->assertOk()
        ->assertCookie(IdentifyGamePlayer::COOKIE);
});

test('a game started on one device is not restored on another device', function () {
    $game = playTurn(startGame());

    $this->withCookie(IdentifyGamePlayer::COOKIE, (string) Str::uuid());

    $this->get(route('home'))
        ->assertInertia(fn (Assert $page) => $page->component('Game')->where('game', null));
    $this->getJson(route('game.current'))->assertOk()->assertJsonPath('data', null);

    $otherGame = $this->postJson(route('game.sessions.store'))->assertCreated()->json('data');

    expect($otherGame['id'])->not->toBe($game['id'])
        ->and($otherGame['turns'])->toBeEmpty()
        ->and(GameSession::current()->count())->toBe(2);
});

test('a device cannot act on or restart a game that belongs to another device', function () {
    $game = spin(startGame())->json('data');

    $this->withCookie(IdentifyGamePlayer::COOKIE, (string) Str::uuid());

    spin($game)->assertNotFound();
    answer($game, correctly: true)->assertNotFound();
    $this->postJson(route('game.sessions.restart', $game['id']))->assertNotFound();

    expect(GameSession::find($game['id'])->is_current)->toBeTrue()
        ->and(GameTurn::find($game['active_turn_id'])->status)->toBe(TurnStatus::QuestionOpened);
});

test('restarting on one device leaves the other devices game untouched', function () {
    $otherPlayer = (string) Str::uuid();
    $otherGame = GameSession::factory()->current()->create(['player_id' => $otherPlayer]);

    $game = startGame();
    $this->postJson(route('game.sessions.restart', $game['id']))->assertCreated();

    expect($otherGame->fresh()->is_current)->toBeTrue()
        ->and(GameSession::ownedBy($otherPlayer)->current()->sole()->id)->toBe($otherGame->id);
});
