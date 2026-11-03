<?php

namespace App\Services;

use App\Enums\ChallengeOutcome;
use App\Enums\GameSessionStatus;
use App\Enums\TurnStatus;
use App\Exceptions\InvalidGameActionException;
use App\Models\Challenge;
use App\Models\GameSession;
use App\Models\GameTeam;
use App\Models\GameTurn;
use App\Models\Question;
use Closure;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Owns every game rule and state transition. Controllers only translate HTTP to these calls.
 *
 * Each mutation runs in a transaction that locks the session row, and every status change is a
 * compare-and-set update (`WHERE status = <expected>`). Together with the unique indexes on
 * game_turns this keeps duplicate clicks, retries and concurrent requests from drawing a
 * question twice, skipping a team or awarding points more than once.
 */
class GameService
{
    public function currentSession(string $playerId): ?GameSession
    {
        return GameSession::ownedBy($playerId)->current()->first();
    }

    /**
     * Return the player's current session, creating one only when none exists.
     *
     * Safe to call repeatedly (page reloads, double clicks, remounts): it never creates a
     * second session while one is current.
     */
    public function startOrResume(string $playerId): GameSession
    {
        if ($session = $this->currentSession($playerId)) {
            return $session;
        }

        try {
            return DB::transaction(fn (): GameSession => $this->createSession($playerId));
        } catch (UniqueConstraintViolationException) {
            // Another request created the current session first; use that one.
            return GameSession::ownedBy($playerId)->current()->firstOrFail();
        }
    }

    /**
     * Replace the given session with a fresh one. The old session is kept for history.
     *
     * If the given session is no longer current (e.g. Restart was clicked twice), the session
     * that replaced it is returned instead of creating yet another one.
     */
    public function restart(GameSession $session): GameSession
    {
        try {
            return DB::transaction(function () use ($session): GameSession {
                $session = $this->lockSession($session);

                if (! $session->is_current) {
                    return GameSession::ownedBy($session->player_id)->current()->firstOrFail();
                }

                $session->update([
                    'is_current' => null,
                    'status' => $session->isInProgress() ? GameSessionStatus::Abandoned : $session->status,
                ]);

                return $this->createSession($session->player_id);
            });
        } catch (UniqueConstraintViolationException) {
            return GameSession::ownedBy($session->player_id)->current()->firstOrFail();
        }
    }

    /**
     * Draw a random, not yet used question for the team whose turn it is.
     */
    public function spinQuestion(GameSession $session, int $teamId): GameTurn
    {
        return $this->mutate($session, function (GameSession $session) use ($teamId): GameTurn {
            $this->ensureInProgress($session);

            $latestTurn = $session->turns()->reorder('turn_number', 'desc')->first();

            if ($latestTurn && ! $latestTurn->status->isResolved()) {
                throw new InvalidGameActionException(
                    "Question {$latestTurn->question->number} must be resolved before the next spin.",
                    $session,
                );
            }

            $turnNumber = ($latestTurn->turn_number ?? 0) + 1;

            if ($turnNumber > self::totalTurns()) {
                throw new InvalidGameActionException('All questions have already been played.', $session);
            }

            $team = $this->teamForTurn($session, $turnNumber);

            if ($team->id !== $teamId) {
                throw new InvalidGameActionException("It is Team {$team->name}'s turn.", $session);
            }

            $question = Question::query()
                ->whereNotIn('id', $session->turns()->reorder()->select('question_id'))
                ->inRandomOrder()
                ->first();

            if (! $question) {
                throw new InvalidGameActionException('There are no questions left to draw.', $session);
            }

            return $session->turns()->create([
                'game_team_id' => $team->id,
                'turn_number' => $turnNumber,
                'team_turn_number' => $team->turns()->count() + 1,
                'question_id' => $question->id,
                'status' => TurnStatus::QuestionOpened,
            ]);
        });
    }

    /**
     * Record the team's answer. Correctness and points are decided here, never by the client.
     */
    public function answer(GameSession $session, GameTurn $turn, int $optionId): GameTurn
    {
        return $this->mutate($session, function (GameSession $session) use ($turn, $optionId): GameTurn {
            $turn = $this->activeTurn($session, $turn);

            $option = $turn->question->options()->find($optionId);

            if (! $option) {
                throw ValidationException::withMessages([
                    'option_id' => 'The selected answer does not belong to this question.',
                ]);
            }

            $points = $option->is_correct ? self::pointsPerQuestion() : 0;

            $this->transition($session, $turn, [TurnStatus::QuestionOpened], [
                'status' => TurnStatus::Answered,
                'selected_option_id' => $option->id,
                'is_correct' => $option->is_correct,
                'points' => $points,
                'answered_at' => now(),
            ], 'This question has already been answered.');

            $this->awardPoints($turn, $points);

            return $turn->refresh();
        });
    }

    /**
     * Draw a random, not yet used challenge for a team that could not answer its question.
     */
    public function openChallenge(GameSession $session, GameTurn $turn): GameTurn
    {
        return $this->mutate($session, function (GameSession $session) use ($turn): GameTurn {
            $turn = $this->activeTurn($session, $turn);

            if (! $turn->canTakeChallenge()) {
                throw new InvalidGameActionException(
                    $turn->status === TurnStatus::ChallengePending
                        ? 'A challenge is already open for this question.'
                        : 'A challenge is not available for this question.',
                    $session,
                );
            }

            $challenge = Challenge::query()
                ->whereNotIn('id', $session->turns()->reorder()->whereNotNull('challenge_id')->select('challenge_id'))
                ->inRandomOrder()
                ->first();

            if (! $challenge) {
                throw new InvalidGameActionException('There are no challenges left to draw.', $session);
            }

            $this->transition($session, $turn, [TurnStatus::QuestionOpened, TurnStatus::Answered], [
                'status' => TurnStatus::ChallengePending,
                'challenge_id' => $challenge->id,
                'challenge_opened_at' => now(),
            ], 'A challenge is already open for this question.');

            return $turn->refresh();
        });
    }

    /**
     * Record the host's verdict on the open challenge and end the turn.
     */
    public function resolveChallenge(GameSession $session, GameTurn $turn, ChallengeOutcome $outcome): GameTurn
    {
        return $this->mutate($session, function (GameSession $session) use ($turn, $outcome): GameTurn {
            $turn = $this->activeTurn($session, $turn);

            $points = $outcome === ChallengeOutcome::Accepted ? self::pointsPerQuestion() : 0;

            $this->transition($session, $turn, [TurnStatus::ChallengePending], [
                'status' => TurnStatus::Completed,
                'challenge_outcome' => $outcome,
                'points' => $points,
                'completed_at' => now(),
            ], 'There is no open challenge to resolve.');

            $this->awardPoints($turn, $points);
            $this->completeSessionIfFinished($session);

            return $turn->refresh();
        });
    }

    /**
     * End an answered turn ("Done") so the next team can spin.
     */
    public function completeTurn(GameSession $session, GameTurn $turn): GameTurn
    {
        return $this->mutate($session, function (GameSession $session) use ($turn): GameTurn {
            $turn = $this->activeTurn($session, $turn);

            $this->transition($session, $turn, [TurnStatus::Answered], [
                'status' => TurnStatus::Completed,
                'completed_at' => now(),
            ], $turn->status === TurnStatus::QuestionOpened
                ? 'Answer the question or take a challenge before finishing the turn.'
                : 'This turn cannot be finished right now.');

            $this->completeSessionIfFinished($session);

            return $turn->refresh();
        });
    }

    public static function totalTurns(): int
    {
        return count(config('game.teams')) * (int) config('game.questions_per_team');
    }

    public static function pointsPerQuestion(): int
    {
        return (int) config('game.points_per_question');
    }

    /**
     * The team that plays the given turn: teams rotate in position order (1 → 2 → 3 → 1 …).
     */
    public function teamForTurn(GameSession $session, int $turnNumber): GameTeam
    {
        $teams = $session->teams()->get();

        return $teams[($turnNumber - 1) % $teams->count()];
    }

    private function createSession(string $playerId): GameSession
    {
        if (Question::count() < self::totalTurns() || Challenge::count() < 1) {
            throw new InvalidGameActionException('The game content has not been seeded. Run the database seeders first.');
        }

        $session = GameSession::create([
            'player_id' => $playerId,
            'status' => GameSessionStatus::InProgress,
            'is_current' => true,
        ]);

        foreach (config('game.teams') as $index => $name) {
            $session->teams()->create([
                'position' => $index + 1,
                'name' => $name,
            ]);
        }

        return $session;
    }

    /**
     * Run a state change on a locked session, translating unique-index races into a 409.
     *
     * @template TResult
     *
     * @param  Closure(GameSession): TResult  $callback
     * @return TResult
     */
    private function mutate(GameSession $session, Closure $callback): mixed
    {
        try {
            return DB::transaction(fn () => $callback($this->lockSession($session)));
        } catch (UniqueConstraintViolationException) {
            throw new InvalidGameActionException(
                'This action was already performed. The game has been refreshed.',
                $session->fresh(),
            );
        }
    }

    private function lockSession(GameSession $session): GameSession
    {
        return GameSession::query()->lockForUpdate()->findOrFail($session->id);
    }

    private function ensureInProgress(GameSession $session): void
    {
        if (! $session->is_current) {
            throw new InvalidGameActionException('This game was replaced by a newer one.', $session);
        }

        if (! $session->isInProgress()) {
            throw new InvalidGameActionException('This game is already finished.', $session);
        }
    }

    /**
     * Reload the turn under lock and make sure it is the session's latest turn.
     */
    private function activeTurn(GameSession $session, GameTurn $turn): GameTurn
    {
        $turn = $session->turns()->lockForUpdate()->findOrFail($turn->id);

        $this->ensureInProgress($session);

        $latestTurnNumber = $session->turns()->max('turn_number');

        if ($turn->turn_number !== (int) $latestTurnNumber || $turn->status->isResolved()) {
            throw new InvalidGameActionException('This turn is already finished.', $session);
        }

        return $turn;
    }

    /**
     * Move the turn to a new status only if it is still in one of the expected statuses.
     *
     * @param  list<TurnStatus>  $expected
     * @param  array<string, mixed>  $attributes
     */
    private function transition(GameSession $session, GameTurn $turn, array $expected, array $attributes, string $conflictMessage): void
    {
        if (! in_array($turn->status, $expected, true)) {
            throw new InvalidGameActionException($conflictMessage, $session);
        }

        $updated = GameTurn::query()
            ->whereKey($turn->id)
            ->whereIn('status', $expected)
            ->update($attributes + ['updated_at' => now()]);

        if ($updated !== 1) {
            throw new InvalidGameActionException($conflictMessage, $session);
        }
    }

    private function awardPoints(GameTurn $turn, int $points): void
    {
        if ($points > 0) {
            GameTeam::query()->whereKey($turn->game_team_id)->increment('score', $points);
        }
    }

    private function completeSessionIfFinished(GameSession $session): void
    {
        $completedTurns = $session->turns()->where('status', TurnStatus::Completed)->count();

        if ($completedTurns >= self::totalTurns()) {
            GameSession::query()
                ->whereKey($session->id)
                ->where('status', GameSessionStatus::InProgress)
                ->update(['status' => GameSessionStatus::Completed, 'completed_at' => now()]);
        }
    }
}
