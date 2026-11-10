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
        class="w-[1000px] gap-4 bg-navy px-14 py-9"
        @close="emit('close')"
    >
        <p class="text-[26px] font-bold text-gold">
            Challenge {{ turn.challenge.number }}
        </p>
        <p
            v-if="turn.challenge.title"
            class="text-center text-[44px] font-extrabold"
        >
            {{ turn.challenge.title }}
        </p>
        <p class="w-full text-center text-[26px] leading-[1.35] font-medium">
            {{
                turn.challenge.description ??
                'Your host will present this challenge.'
            }}
        </p>
        <div class="mt-6 flex items-center gap-4">
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
