<script setup lang="ts">
import GameModal from '@/components/game/GameModal.vue';
import PrimaryButton from '@/components/game/PrimaryButton.vue';

defineProps<{
    teamName: string;
    questionNumber: number;
}>();

const emit = defineEmits<{ continue: [] }>();
</script>

<template>
    <GameModal
        :closable="false"
        class="w-[706px] gap-2 bg-navy px-[72px] py-12"
    >
        <p class="text-[26px] font-semibold text-white/85">Next turn</p>
        <p class="text-[72px] font-extrabold whitespace-nowrap text-gold">
            Team {{ teamName }}
        </p>
        <p class="text-[24px] font-semibold">
            Question {{ questionNumber }} is for your team
        </p>
        <div class="h-6" />
        <PrimaryButton v-focus @click="emit('continue')"
            >Continue</PrimaryButton
        >
    </GameModal>
</template>
