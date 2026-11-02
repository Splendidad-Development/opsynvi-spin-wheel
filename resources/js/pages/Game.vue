<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import ChallengeModal from '@/components/game/ChallengeModal.vue';
import GameStage from '@/components/game/GameStage.vue';
import GlassButton from '@/components/game/GlassButton.vue';
import PrimaryButton from '@/components/game/PrimaryButton.vue';
import QuestionModal from '@/components/game/QuestionModal.vue';
import RestartModal from '@/components/game/RestartModal.vue';
import ResultsModal from '@/components/game/ResultsModal.vue';
import ScoreSheetModal from '@/components/game/ScoreSheetModal.vue';
import SpinWheel from '@/components/game/SpinWheel.vue';
import TurnModal from '@/components/game/TurnModal.vue';
import { useGame } from '@/composables/useGame';
import { cn } from '@/lib/utils';
import type { ChallengeOutcome, GameState, GameTurn } from '@/types';

const props = defineProps<{ game: GameState | null }>();

const {
    game,
    isBusy,
    errorMessage,
    activeTurn,
    currentTeam,
    apply,
    clearError,
    refresh,
    start,
    restart,
    spinQuestion,
    answer,
    spinChallenge,
    resolveChallenge,
    completeTurn,
} = useGame(props.game);

type TurnModalName = 'turn' | 'question' | 'challenge';
type InfoModalName = 'scores' | 'results' | 'restart';

const turnModal = ref<TurnModalName | null>(null);
const infoModal = ref<InfoModalName | null>(null);
const spinningWheel = ref<'question' | 'challenge' | null>(null);
const pendingOptionId = ref<number | null>(null);
const isChallengeArmed = ref(false);

const questionWheel = ref<InstanceType<typeof SpinWheel> | null>(null);
const challengeWheel = ref<InstanceType<typeof SpinWheel> | null>(null);

const isLocked = computed(() => isBusy.value || spinningWheel.value !== null);
const isInProgress = computed(() => game.value?.status === 'in_progress');

const isQuestionPhase = computed(
    () =>
        activeTurn.value?.status === 'question_opened' ||
        activeTurn.value?.status === 'answered',
);
const isChallengePending = computed(
    () => activeTurn.value?.status === 'challenge_pending',
);

const latestTurn = computed<GameTurn | null>(
    () => game.value?.turns.at(-1) ?? null,
);
const usedQuestionNumbers = computed(
    () => game.value?.turns.map((turn) => turn.question.number) ?? [],
);
const usedChallengeNumbers = computed(
    () =>
        game.value?.turns.flatMap((turn) =>
            turn.challenge ? [turn.challenge.number] : [],
        ) ?? [],
);
const latestChallengeNumber = computed(
    () =>
        game.value?.turns.findLast((turn) => turn.challenge)?.challenge
            ?.number ?? null,
);

const activeTeamName = computed(
    () =>
        game.value?.teams.find((team) => team.id === activeTurn.value?.team_id)
            ?.name ?? '',
);

const canSpinQuestion = computed(
    () =>
        isInProgress.value &&
        !isLocked.value &&
        (activeTurn.value === null || isQuestionPhase.value),
);
const canSpinChallenge = computed(
    () =>
        isInProgress.value &&
        !isLocked.value &&
        (isChallengePending.value ||
            activeTurn.value?.can_take_challenge === true),
);

const statusText = computed(() => {
    if (!game.value) {
        return '';
    }

    if (game.value.is_completed) {
        return 'Game over · All 15 questions played';
    }

    const played = `${game.value.completed_turns} / ${game.value.total_questions} questions played`;

    if (!currentTeam.value) {
        return played;
    }

    if (!activeTurn.value) {
        return `Team ${currentTeam.value.name}: spin the wheel · ${played}`;
    }

    return isChallengePending.value
        ? `Team ${currentTeam.value.name} is taking challenge ${activeTurn.value.challenge?.number} · ${played}`
        : `Team ${currentTeam.value.name} is playing question ${activeTurn.value.question.number} · ${played}`;
});

/**
 * The modal that matches the server state: used on load (session restore) and
 * whenever the state changes underneath the screen (conflict, other tab).
 */
function modalForState(): TurnModalName | null {
    if (isChallengePending.value) {
        return 'challenge';
    }

    return isQuestionPhase.value ? 'question' : null;
}

function reconcileModals(): void {
    const expected = modalForState();

    if (turnModal.value === 'turn' && expected === 'question') {
        return;
    }

    if (turnModal.value !== null && turnModal.value !== expected) {
        turnModal.value = expected;
    }

    if (expected !== 'challenge') {
        isChallengeArmed.value = false;
    }
}

watch(game, reconcileModals);

function openInitialModals(): void {
    turnModal.value = modalForState();

    if (game.value?.is_completed) {
        infoModal.value = 'results';
    }
}

function finishTurn(): void {
    turnModal.value = null;
    isChallengeArmed.value = false;

    if (game.value?.is_completed) {
        infoModal.value = 'results';
    }
}

async function onStart(): Promise<void> {
    if (apply(await start())) {
        openInitialModals();
    }
}

async function onQuestionWheel(): Promise<void> {
    if (isQuestionPhase.value) {
        turnModal.value = 'question';

        return;
    }

    spinningWheel.value = 'question';

    const state = await spinQuestion();
    const turn = state?.turns.find((item) => item.id === state.active_turn_id);

    if (state && turn) {
        await questionWheel.value?.spinTo(turn.question.number);
        apply(state);
        turnModal.value = 'turn';
    }

    spinningWheel.value = null;
}

async function onAnswer(optionId: number): Promise<void> {
    if (!activeTurn.value) {
        return;
    }

    pendingOptionId.value = optionId;
    apply(await answer(activeTurn.value.id, optionId));
    pendingOptionId.value = null;
}

async function onDone(): Promise<void> {
    if (activeTurn.value && apply(await completeTurn(activeTurn.value.id))) {
        finishTurn();
    }
}

function onTakeChallenge(): void {
    turnModal.value = null;
    isChallengeArmed.value = true;
}

async function onChallengeWheel(): Promise<void> {
    if (!activeTurn.value) {
        return;
    }

    if (isChallengePending.value) {
        turnModal.value = 'challenge';

        return;
    }

    spinningWheel.value = 'challenge';
    turnModal.value = null;

    const state = await spinChallenge(activeTurn.value.id);
    const turn = state?.turns.find((item) => item.id === state.active_turn_id);

    if (state && turn?.challenge) {
        await challengeWheel.value?.spinTo(turn.challenge.number);
        apply(state);
        isChallengeArmed.value = false;
        turnModal.value = 'challenge';
    }

    spinningWheel.value = null;
}

async function onResolveChallenge(outcome: ChallengeOutcome): Promise<void> {
    if (
        activeTurn.value &&
        apply(await resolveChallenge(activeTurn.value.id, outcome))
    ) {
        finishTurn();
    }
}

async function onRestart(): Promise<void> {
    if (apply(await restart())) {
        questionWheel.value?.reset();
        challengeWheel.value?.reset();
        turnModal.value = null;
        infoModal.value = null;
        isChallengeArmed.value = false;
    }
}

/** Pick up changes made elsewhere (another tab or device) when the screen regains focus. */
async function onVisibilityChange(): Promise<void> {
    if (
        document.visibilityState === 'visible' &&
        game.value &&
        !isLocked.value
    ) {
        await refresh();
    }
}

let errorTimer: number | undefined;

watch(errorMessage, (message) => {
    window.clearTimeout(errorTimer);

    if (message) {
        errorTimer = window.setTimeout(clearError, 6000);
    }
});

onMounted(() => {
    openInitialModals();
    document.addEventListener('visibilitychange', onVisibilityChange);
});

onBeforeUnmount(() => {
    window.clearTimeout(errorTimer);
    document.removeEventListener('visibilitychange', onVisibilityChange);
});
</script>

<template>
    <Head title="Spin the wheel" />

    <GameStage>
        <div :class="cn('size-full', { 'cursor-progress': isBusy })">
            <img
                src="/images/game/logo.svg"
                alt="Opsynvi macitentan/tadalafil"
                width="210"
                height="131"
                class="absolute top-9 left-[79px] h-[130.41px] w-[210px]"
            />

            <!-- Start screen -->
            <template v-if="!game">
                <img
                    src="/images/game/tagline.svg"
                    alt="A duet in one tablet for your PAH patients"
                    width="424"
                    height="241"
                    class="absolute top-[30.68px] left-[1498.68px] h-[241px] w-[424px]"
                />
                <div
                    class="absolute top-[780px] left-1/2 flex w-[323px] -translate-x-1/2 flex-col items-center gap-4 rounded-[48px] bg-[rgba(10,31,68,0.95)] px-6 pt-5 pb-6"
                >
                    <p class="text-[36px] font-bold text-gold">
                        Spin the wheel
                    </p>
                    <PrimaryButton :disabled="isBusy" @click="onStart">
                        {{ isBusy ? 'Starting…' : 'Get started' }}
                    </PrimaryButton>
                </div>
            </template>

            <!-- Game screen -->
            <template v-else>
                <img
                    src="/images/game/tagline.svg"
                    alt="A duet in one tablet for your PAH patients"
                    width="424"
                    height="241"
                    class="absolute top-[33.07px] left-[1681.77px] h-[132.67px] w-[233.42px]"
                />

                <div class="absolute top-[52px] right-[259.44px] flex gap-3">
                    <GlassButton
                        tone="dark"
                        :disabled="isLocked"
                        @click="infoModal = 'restart'"
                    >
                        Restart
                    </GlassButton>
                    <GlassButton
                        tone="dark"
                        :disabled="spinningWheel !== null"
                        @click="infoModal = 'results'"
                    >
                        Result
                    </GlassButton>
                    <GlassButton
                        tone="dark"
                        :disabled="spinningWheel !== null"
                        @click="infoModal = 'scores'"
                    >
                        Score sheet
                    </GlassButton>
                </div>

                <div
                    class="absolute top-[181px] left-[284px] flex items-center gap-[139px]"
                >
                    <section
                        class="flex w-[610px] flex-col items-center gap-5 rounded-[60px] bg-[rgba(10,31,68,0.6)] px-10 py-5"
                    >
                        <p class="text-[23px] text-white">
                            Spin the wheel to get a
                            <span class="font-bold">Question</span>
                        </p>
                        <SpinWheel
                            ref="questionWheel"
                            :used-numbers="usedQuestionNumbers"
                            :active-number="activeTurn?.question.number"
                            :resting-number="latestTurn?.question.number"
                        />
                        <GlassButton
                            emphasis
                            :disabled="!canSpinQuestion"
                            @click="onQuestionWheel"
                        >
                            {{
                                spinningWheel === 'question'
                                    ? 'Spinning…'
                                    : isQuestionPhase
                                      ? 'Resume'
                                      : 'Spin'
                            }}
                        </GlassButton>
                    </section>

                    <section
                        class="flex w-[610px] flex-col items-center gap-5 rounded-[60px] bg-[rgba(10,31,68,0.6)] px-10 py-5"
                    >
                        <p class="text-[23px] text-white">
                            Spin the wheel to get a
                            <span class="font-bold">Challenge</span>
                        </p>
                        <SpinWheel
                            ref="challengeWheel"
                            :used-numbers="usedChallengeNumbers"
                            :active-number="activeTurn?.challenge?.number"
                            :resting-number="latestChallengeNumber"
                        />
                        <GlassButton
                            emphasis
                            :disabled="!canSpinChallenge"
                            :class="
                                cn({
                                    'animate-pulse ring-4 ring-gold':
                                        isChallengeArmed && canSpinChallenge,
                                })
                            "
                            @click="onChallengeWheel"
                        >
                            {{
                                spinningWheel === 'challenge'
                                    ? 'Spinning…'
                                    : isChallengePending
                                      ? 'Resume'
                                      : 'Spin'
                            }}
                        </GlassButton>
                    </section>
                </div>

                <p
                    class="absolute top-[890px] left-1/2 -translate-x-1/2 rounded-[60px] bg-[rgba(10,31,68,0.8)] px-8 py-3 text-[22px] font-semibold whitespace-nowrap text-white"
                    aria-live="polite"
                >
                    {{ statusText }}
                </p>

                <template v-if="activeTurn && !infoModal">
                    <TurnModal
                        v-if="turnModal === 'turn'"
                        :team-name="activeTeamName"
                        :question-number="activeTurn.question.number"
                        @continue="turnModal = 'question'"
                    />
                    <QuestionModal
                        v-else-if="turnModal === 'question'"
                        :turn="activeTurn"
                        :team-name="activeTeamName"
                        :is-busy="isBusy"
                        :pending-option-id="pendingOptionId"
                        @answer="onAnswer"
                        @done="onDone"
                        @challenge="onTakeChallenge"
                        @close="turnModal = null"
                    />
                    <ChallengeModal
                        v-else-if="turnModal === 'challenge'"
                        :turn="activeTurn"
                        :is-busy="isBusy"
                        @resolve="onResolveChallenge"
                        @close="turnModal = null"
                    />
                </template>

                <ScoreSheetModal
                    v-if="infoModal === 'scores'"
                    :game="game"
                    @close="infoModal = null"
                />
                <ResultsModal
                    v-else-if="infoModal === 'results'"
                    :game="game"
                    @close="infoModal = null"
                    @restart="infoModal = 'restart'"
                />
                <RestartModal
                    v-else-if="infoModal === 'restart'"
                    :is-busy="isBusy"
                    @confirm="onRestart"
                    @close="infoModal = null"
                />
            </template>

            <div
                v-if="errorMessage"
                class="absolute top-[200px] left-1/2 z-50 flex -translate-x-1/2 items-center gap-4 rounded-[60px] border-2 border-danger bg-navy px-8 py-4 text-[22px] font-semibold text-white shadow-2xl"
                role="alert"
            >
                {{ errorMessage }}
                <button
                    type="button"
                    class="text-white/70 hover:text-white"
                    aria-label="Dismiss"
                    @click="clearError"
                >
                    ✕
                </button>
            </div>
        </div>
    </GameStage>
</template>
