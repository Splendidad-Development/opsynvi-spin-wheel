<script setup lang="ts">
import GameModal from '@/components/game/GameModal.vue';
import PrimaryButton from '@/components/game/PrimaryButton.vue';
import type { ChallengeOutcome, GameTurn } from '@/types';

defineProps<{
    turn: GameTurn;
    isBusy: boolean;
}>();

const emit = defineEmits<{
    resolve: [outcome: ChallengeOutcome];
    close: [];
}>();
</script>

<template>
    <GameModal
        v-if="turn.challenge"
        class="w-[874px] gap-5 bg-navy px-10 py-5"
        @close="emit('close')"
    >
        <p class="text-[23px] font-bold">
            Challenge {{ turn.challenge.number }}
        </p>
        <p class="w-[644px] text-center text-[23px] font-medium">
            {{
                turn.challenge.description ??
                'Your host will present this challenge.'
            }}
        </p>
        <div class="flex items-center gap-4">
            <PrimaryButton
                variant="accept"
                class="w-[389px]"
                :disabled="isBusy"
                @click="emit('resolve', 'accepted')"
            >
                Challenge accepted
            </PrimaryButton>
            <PrimaryButton
                variant="decline"
                class="w-[389px]"
                :disabled="isBusy"
                @click="emit('resolve', 'declined')"
            >
                Challenge declined
            </PrimaryButton>
        </div>
    </GameModal>
</template>
