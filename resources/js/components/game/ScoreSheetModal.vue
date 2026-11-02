<script setup lang="ts">
import GameModal from '@/components/game/GameModal.vue';
import GlassButton from '@/components/game/GlassButton.vue';
import { cn } from '@/lib/utils';
import type { GameState, GameTeam, GameTurn } from '@/types';

const props = defineProps<{ game: GameState }>();

const emit = defineEmits<{ close: [] }>();

type Row = { label: string; turn: GameTurn | null };

function rows(team: GameTeam): Row[] {
    return Array.from(
        { length: props.game.questions_per_team },
        (_, index) => ({
            label: `Question ${index + 1}`,
            turn:
                props.game.turns.find(
                    (turn) =>
                        turn.team_id === team.id &&
                        turn.team_turn_number === index + 1,
                ) ?? null,
        }),
    );
}

function isScored(turn: GameTurn | null): turn is GameTurn {
    return turn?.status === 'answered' || turn?.status === 'completed';
}
</script>

<template>
    <GameModal
        class="w-[1408px] gap-10 bg-[rgba(10,31,68,0.85)] px-10 py-8"
        @close="emit('close')"
    >
        <p class="text-[36px] font-bold">Score sheet</p>
        <div class="flex items-start gap-10">
            <div
                v-for="team in game.teams"
                :key="team.id"
                class="flex w-[416px] flex-col gap-4"
            >
                <p class="text-[28px] font-bold text-gold">{{ team.name }}</p>
                <div class="flex w-full flex-col gap-3">
                    <p class="text-[18px] font-medium">
                        Scores ({{ game.points_per_question }} points per
                        question)
                    </p>
                    <div
                        v-for="row in rows(team)"
                        :key="row.label"
                        class="flex w-full items-center gap-4"
                    >
                        <p class="flex-1 text-[18px] font-semibold">
                            {{ row.label }}
                            <span
                                v-if="row.turn"
                                class="font-medium text-white/60"
                            >
                                · Q{{ row.turn.question.number }}
                            </span>
                        </p>
                        <div
                            :class="
                                cn(
                                    'flex h-11 w-[120px] items-center justify-center rounded-[22px] border-[1.5px] text-[20px] font-bold',
                                    isScored(row.turn)
                                        ? row.turn.points > 0
                                            ? 'border-success bg-success/20'
                                            : 'border-danger bg-danger/20'
                                        : 'border-white/20 bg-white/8 text-white/60',
                                )
                            "
                        >
                            <template v-if="isScored(row.turn)">
                                {{ row.turn.points }} pts
                            </template>
                            <template v-else-if="row.turn">In play</template>
                            <template v-else>–</template>
                        </div>
                    </div>
                    <div class="h-px w-full bg-white/25" />
                    <div class="flex w-full items-center gap-4">
                        <p class="flex-1 text-[20px] font-bold text-gold">
                            Total
                        </p>
                        <div
                            class="flex h-11 w-[120px] items-center justify-center rounded-[22px] border-[1.5px] border-gold bg-gold/20 text-[20px] font-bold text-gold"
                        >
                            {{ team.score }} /
                            {{
                                game.questions_per_team *
                                game.points_per_question
                            }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <GlassButton @click="emit('close')">Done</GlassButton>
    </GameModal>
</template>
