<script setup lang="ts">
import GameModal from '@/components/game/GameModal.vue';
import GlassButton from '@/components/game/GlassButton.vue';
import { questionReferences } from '@/lib/references';

const emit = defineEmits<{ close: [] }>();

const COLUMNS = 3;
const perColumn = Math.ceil(questionReferences.length / COLUMNS);
const columns = Array.from({ length: COLUMNS }, (_, index) =>
    questionReferences.slice(index * perColumn, (index + 1) * perColumn),
);
</script>

<template>
    <GameModal
        class="w-[1760px] gap-6 rounded-[48px] bg-navy px-12 py-8"
        @close="emit('close')"
    >
        <p class="text-[36px] font-bold">References</p>
        <div class="flex w-full items-start gap-10">
            <div
                v-for="(column, index) in columns"
                :key="index"
                class="flex min-w-0 flex-1 flex-col gap-4"
            >
                <div
                    v-for="entry in column"
                    :key="entry.question"
                    class="flex w-full flex-col gap-1 text-[14px] leading-[1.35] break-words text-white/88"
                >
                    <p class="text-[16px] leading-normal font-bold text-gold">
                        Question {{ entry.question }}
                    </p>
                    <p v-if="entry.note">{{ entry.note }}</p>
                    <p>
                        <span class="font-semibold">
                            {{
                                entry.references.length > 1
                                    ? 'References:'
                                    : 'Reference:'
                            }}
                        </span>
                        <template
                            v-for="(reference, position) in entry.references"
                            :key="position"
                        >
                            <br v-if="position > 0 && !entry.inline" />
                            {{ ` ${position + 1}. ${reference}` }}
                        </template>
                    </p>
                </div>
            </div>
        </div>
        <GlassButton @click="emit('close')">Done</GlassButton>
    </GameModal>
</template>
