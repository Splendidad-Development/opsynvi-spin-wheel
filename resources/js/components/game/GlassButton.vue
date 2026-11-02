<script setup lang="ts">
import { cn } from '@/lib/utils';

withDefaults(
    defineProps<{
        tone?: 'light' | 'dark';
        emphasis?: boolean;
        disabled?: boolean;
        class?: string;
    }>(),
    { tone: 'light', emphasis: false, disabled: false, class: '' },
);
</script>

<template>
    <button
        type="button"
        :disabled="disabled"
        :class="
            cn(
                'flex h-[73px] w-[258.78px] shrink-0 items-center justify-center gap-5 rounded-[60px] px-10 text-[24px] text-white transition hover:brightness-125 disabled:cursor-not-allowed disabled:opacity-40 disabled:hover:brightness-100',
                tone === 'light' ? 'bg-white/20' : 'bg-[rgba(10,31,68,0.6)]',
                emphasis ? 'font-bold uppercase italic' : 'font-semibold',
                $props.class,
            )
        "
    >
        <slot />
    </button>
</template>
