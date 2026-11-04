<script setup lang="ts">
import { computed } from 'vue';
import GameModal from '@/components/game/GameModal.vue';
import GlassButton from '@/components/game/GlassButton.vue';
import PrimaryButton from '@/components/game/PrimaryButton.vue';
import { cn } from '@/lib/utils';
import type { GameState } from '@/types';

const props = withDefaults(
    defineProps<{ game: GameState; canRestart?: boolean }>(),
    { canRestart: true },
);

const emit = defineEmits<{ close: []; restart: [] }>();

const subtitle = computed(() => {
    if (!props.game.is_completed) {
        return `Standings after ${props.game.completed_turns} of ${props.game.total_questions} questions`;
    }

    const leaders = props.game.leaderboard.filter((entry) => entry.rank === 1);

    return leaders.length > 1
        ? `It's a tie between ${leaders.map((entry) => entry.name).join(' & ')}!`
        : `Team ${leaders[0]?.name} wins!`;
});
</script>

<template>
    <GameModal
        class="w-[824px] gap-7 bg-[rgba(10,31,68,0.85)] px-10 py-8"
        @close="emit('close')"
    >
        <div class="flex flex-col items-center gap-1">
            <p class="text-[36px] font-bold">Results</p>
            <p class="text-[20px] font-semibold text-white/75">
                {{ subtitle }}
            </p>
        </div>
        <div class="flex w-full flex-col gap-3.5">
            <div
                v-for="entry in game.leaderboard"
                :key="entry.team_id"
                :class="
                    cn(
                        'flex h-[100px] w-full items-center gap-5 rounded-[20px] px-7 py-[18px]',
                        entry.rank === 1
                            ? 'border-2 border-gold bg-gold/18'
                            : 'border border-white/20 bg-white/8',
                    )
                "
            >
                <div
                    :class="
                        cn(
                            'flex size-12 shrink-0 items-center justify-center rounded-3xl text-[24px] font-extrabold',
                            entry.rank === 1
                                ? 'bg-gold text-navy'
                                : 'bg-white/15',
                        )
                    "
                >
                    {{ entry.rank }}
                </div>
                <p
                    :class="
                        cn(
                            'flex-1 font-bold',
                            entry.rank === 1
                                ? 'text-[30px] text-gold'
                                : 'text-[26px]',
                        )
                    "
                >
                    {{ entry.name }}
                    <span
                        v-if="entry.is_tied"
                        class="ml-2 align-middle text-[18px] font-semibold opacity-75"
                    >
                        (tie)
                    </span>
                </p>
                <p
                    :class="
                        cn(
                            'font-extrabold whitespace-nowrap',
                            entry.rank === 1
                                ? 'text-[30px] text-gold'
                                : 'text-[26px]',
                        )
                    "
                >
                    {{ entry.score }} pts
                </p>
            </div>
        </div>
        <div class="flex items-center gap-4">
            <GlassButton @click="emit('close')">Done</GlassButton>
            <PrimaryButton :disabled="!canRestart" @click="emit('restart')">
                Restart
            </PrimaryButton>
        </div>
    </GameModal>
</template>
