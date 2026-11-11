<script setup lang="ts">
import GameModal from '@/components/game/GameModal.vue';
import PrimaryButton from '@/components/game/PrimaryButton.vue';
import type { GameTeam } from '@/types';

defineProps<{
    teams: GameTeam[];
    isFirstTeam: boolean;
    questionsPerTeam: number;
}>();

const emit = defineEmits<{ choose: [teamId: number] }>();
</script>

<template>
    <GameModal
        :closable="false"
        class="w-[860px] gap-2 bg-navy px-[72px] py-12"
    >
        <p class="text-[26px] font-semibold text-white/85">
            {{ isFirstTeam ? 'Get ready' : 'Next team' }}
        </p>
        <p class="text-[56px] font-extrabold text-gold">
            {{
                isFirstTeam
                    ? 'Which team plays first?'
                    : 'Which team plays next?'
            }}
        </p>
        <p class="text-[24px] font-semibold">
            The team plays all {{ questionsPerTeam }} of its questions in a row
        </p>
        <div class="h-6" />
        <div class="flex flex-wrap items-center justify-center gap-4">
            <PrimaryButton
                v-for="team in teams"
                :key="team.id"
                @click="emit('choose', team.id)"
            >
                Team {{ team.name }}
            </PrimaryButton>
        </div>
    </GameModal>
</template>
