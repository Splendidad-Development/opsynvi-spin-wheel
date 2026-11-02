<script setup lang="ts">
import GameModal from '@/components/game/GameModal.vue';
import GlassButton from '@/components/game/GlassButton.vue';
import PrimaryButton from '@/components/game/PrimaryButton.vue';

defineProps<{ isBusy: boolean }>();

const emit = defineEmits<{ confirm: []; close: [] }>();
</script>

<template>
    <GameModal
        class="w-[760px] gap-5 bg-navy px-12 py-10"
        @close="emit('close')"
    >
        <p class="text-[36px] font-bold">Restart the game?</p>
        <p class="text-center text-[23px] font-medium text-white/85">
            A new game starts with all 15 questions and zero points for every
            team. The current game is saved in the history.
        </p>
        <div class="mt-2 flex items-center gap-4">
            <GlassButton :disabled="isBusy" @click="emit('close')">
                Cancel
            </GlassButton>
            <PrimaryButton :disabled="isBusy" @click="emit('confirm')">
                Restart
            </PrimaryButton>
        </div>
    </GameModal>
</template>
