import { http } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import {
    restart as restartRoute,
    show as currentRoute,
    store as startRoute,
} from '@/actions/App/Http/Controllers/GameSessionController';
import {
    answer as answerRoute,
    challenge as challengeRoute,
    complete as completeRoute,
    resolveChallenge as resolveChallengeRoute,
    store as spinRoute,
} from '@/actions/App/Http/Controllers/GameTurnController';
import type { ChallengeOutcome, GameState } from '@/types';
import type { RouteDefinition } from '@/wayfinder';

type GameResponse = {
    data: GameState | null;
    message?: string;
    errors?: Record<string, string[]>;
};

/**
 * Thrown when the server rejects an action. `game` carries the authoritative
 * state returned with a 409 so the screen can resynchronise.
 */
export class GameActionError extends Error {
    constructor(
        message: string,
        public readonly game: GameState | null = null,
        public readonly isConflict = false,
    ) {
        super(message);
    }
}

const NETWORK_ERROR_MESSAGE =
    'Connection problem. Check the network and try again.';

async function send(
    route: RouteDefinition<'get' | 'post'>,
    data?: Record<string, unknown>,
): Promise<GameState | null> {
    try {
        const response = await http.getClient().request({
            method: route.method,
            url: route.url,
            data,
            headers: { Accept: 'application/json' },
        });

        return (JSON.parse(response.data) as GameResponse).data;
    } catch (error) {
        const response = (
            error as { response?: { status: number; data: string } }
        ).response;

        if (!response) {
            throw new GameActionError(NETWORK_ERROR_MESSAGE);
        }

        let body: GameResponse | null = null;

        try {
            body = JSON.parse(response.data) as GameResponse;
        } catch {
            // Non-JSON error page; fall through to the generic message.
        }

        const firstValidationError = Object.values(body?.errors ?? {})[0]?.[0];

        throw new GameActionError(
            firstValidationError ??
                body?.message ??
                'Something went wrong. Please try again.',
            response.status === 409 ? (body?.data ?? null) : null,
            response.status === 409,
        );
    }
}

/**
 * Holds the game state received from the server and exposes every game action.
 * The server is the source of truth: each action replaces the local state with
 * the state it returns, and nothing is calculated optimistically.
 */
export function useGame(initialGame: GameState | null) {
    const game = ref<GameState | null>(initialGame);
    const isBusy = ref(false);
    const errorMessage = ref<string | null>(null);

    const activeTurn = computed(
        () =>
            game.value?.turns.find(
                (turn) => turn.id === game.value?.active_turn_id,
            ) ?? null,
    );

    const currentTeam = computed(
        () =>
            game.value?.teams.find(
                (team) => team.id === game.value?.current_team_id,
            ) ?? null,
    );

    /**
     * Run a request while blocking other actions. Returns the new state without
     * applying it, so callers can finish an animation first; conflicts are
     * applied immediately because they describe what really happened.
     */
    async function run(
        route: RouteDefinition<'get' | 'post'>,
        data?: Record<string, unknown>,
    ): Promise<GameState | null | undefined> {
        if (isBusy.value) {
            return undefined;
        }

        isBusy.value = true;
        errorMessage.value = null;

        try {
            return await send(route, data);
        } catch (error) {
            const actionError = error as GameActionError;

            errorMessage.value = actionError.message;

            if (actionError.isConflict && actionError.game) {
                game.value = actionError.game;
            }

            return undefined;
        } finally {
            isBusy.value = false;
        }
    }

    function apply(state: GameState | null | undefined): boolean {
        if (state === undefined) {
            return false;
        }

        game.value = state;

        return true;
    }

    return {
        game,
        isBusy,
        errorMessage,
        activeTurn,
        currentTeam,
        apply,
        clearError: () => (errorMessage.value = null),

        refresh: async () => apply(await run(currentRoute())),
        start: () => run(startRoute()),
        restart: () =>
            game.value ? run(restartRoute(game.value.id)) : run(startRoute()),
        spinQuestion: (teamId: number) =>
            game.value
                ? run(spinRoute(game.value.id), { team_id: teamId })
                : Promise.resolve(undefined),
        answer: (turnId: number, optionId: number) =>
            game.value
                ? run(
                      answerRoute({
                          gameSession: game.value.id,
                          gameTurn: turnId,
                      }),
                      { option_id: optionId },
                  )
                : Promise.resolve(undefined),
        spinChallenge: (turnId: number) =>
            game.value
                ? run(
                      challengeRoute({
                          gameSession: game.value.id,
                          gameTurn: turnId,
                      }),
                  )
                : Promise.resolve(undefined),
        resolveChallenge: (turnId: number, outcome: ChallengeOutcome) =>
            game.value
                ? run(
                      resolveChallengeRoute({
                          gameSession: game.value.id,
                          gameTurn: turnId,
                      }),
                      { outcome },
                  )
                : Promise.resolve(undefined),
        completeTurn: (turnId: number) =>
            game.value
                ? run(
                      completeRoute({
                          gameSession: game.value.id,
                          gameTurn: turnId,
                      }),
                  )
                : Promise.resolve(undefined),
    };
}
