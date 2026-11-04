<script setup lang="ts">
import { computed } from 'vue';
import GameModal from '@/components/game/GameModal.vue';
import GlassButton from '@/components/game/GlassButton.vue';
import PrimaryButton from '@/components/game/PrimaryButton.vue';
import { referenceMarker } from '@/lib/references';
import { cn } from '@/lib/utils';
import type { GameTurn, QuestionOption } from '@/types';

const props = defineProps<{
    turn: GameTurn;
    teamName: string;
    isBusy: boolean;
    pendingOptionId: number | null;
}>();

const emit = defineEmits<{
    answer: [optionId: number];
    done: [];
    challenge: [];
    close: [];
}>();

const isAnswered = computed(() => props.turn.status !== 'question_opened');
const marker = computed(() => referenceMarker(props.turn.question.number));

function optionState(
    option: QuestionOption,
): 'correct' | 'wrong' | 'pending' | 'idle' {
    if (option.id === props.turn.correct_option_id) {
        return 'correct';
    }

    if (isAnswered.value && option.id === props.turn.selected_option_id) {
        return 'wrong';
    }

    return option.id === props.pendingOptionId ? 'pending' : 'idle';
}
</script>

<template>
    <GameModal
        class="w-[1100px] gap-5 bg-navy px-12 py-8"
        @close="emit('close')"
    >
        <p class="text-[26px] font-bold text-gold">
            Question {{ turn.question.number }}
        </p>
        <p class="w-full text-center text-[26px] leading-[1.35] font-semibold">
            {{ turn.question.prompt }}
        </p>

        <div class="flex w-full flex-col gap-3">
            <button
                v-for="option in turn.question.options"
                :key="option.id"
                type="button"
                :disabled="isAnswered || isBusy"
                :class="
                    cn(
                        'flex w-full gap-4 rounded-2xl px-6 py-3.5 text-left text-[22px] transition',
                        {
                            'items-start border-2 border-success bg-success/22':
                                optionState(option) === 'correct',
                            'items-start border-2 border-danger bg-danger/22':
                                optionState(option) === 'wrong',
                            'items-center border border-gold bg-white/20':
                                optionState(option) === 'pending',
                            'items-center border border-white/20 bg-white/10':
                                optionState(option) === 'idle',
                            'cursor-pointer hover:border-gold/70 hover:bg-white/15':
                                !isAnswered && !isBusy,
                            'opacity-70':
                                isAnswered && optionState(option) === 'idle',
                        },
                    )
                "
                @click="emit('answer', option.id)"
            >
                <span
                    :class="
                        cn('font-bold', {
                            'text-success': optionState(option) === 'correct',
                            'text-danger': optionState(option) === 'wrong',
                            'text-gold': ['idle', 'pending'].includes(
                                optionState(option),
                            ),
                        })
                    "
                >
                    {{ option.letter }}
                </span>
                <span class="flex min-w-0 flex-1 flex-col gap-1.5">
                    <span
                        :class="
                            cn(
                                'leading-[1.3]',
                                optionState(option) === 'correct'
                                    ? 'font-bold'
                                    : 'font-medium',
                            )
                        "
                    >
                        {{ option.text }}
                    </span>
                    <span
                        v-if="
                            optionState(option) === 'correct' &&
                            turn.explanation
                        "
                        class="text-[19px] leading-[1.35] text-white/85"
                    >
                        {{ turn.explanation
                        }}<sup
                            v-if="marker"
                            class="ml-px text-[13px] font-semibold"
                            >{{ marker }}</sup
                        >
                    </span>
                </span>
                <img
                    v-if="optionState(option) === 'correct'"
                    src="/images/game/icon-correct.svg"
                    alt="Correct answer"
                    width="28"
                    height="28"
                    class="size-7 shrink-0"
                />
                <svg
                    v-else-if="optionState(option) === 'wrong'"
                    viewBox="0 0 28 28"
                    class="size-7 shrink-0"
                    role="img"
                    aria-label="Wrong answer"
                >
                    <circle cx="14" cy="14" r="14" fill="#e5484d" />
                    <path
                        d="M9.5 9.5l9 9m0-9l-9 9"
                        stroke="white"
                        stroke-width="2.5"
                        stroke-linecap="round"
                    />
                </svg>
            </button>
        </div>

        <p
            v-if="isAnswered"
            :class="
                cn(
                    'text-[22px] font-bold',
                    turn.is_correct ? 'text-success' : 'text-danger',
                )
            "
        >
            {{
                turn.is_correct
                    ? `Correct! +${turn.points} points for Team ${teamName}`
                    : `Wrong answer. No points for Team ${teamName}`
            }}
        </p>

        <div class="flex items-center gap-4">
            <GlassButton
                :disabled="!isAnswered || isBusy"
                @click="emit('done')"
            >
                Done
            </GlassButton>
            <PrimaryButton
                class="w-[348px]"
                :disabled="!turn.can_take_challenge || isBusy"
                @click="emit('challenge')"
            >
                Take a challenge
            </PrimaryButton>
        </div>
    </GameModal>
</template>
