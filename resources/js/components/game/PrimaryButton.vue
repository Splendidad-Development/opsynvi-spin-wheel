<script setup lang="ts">
import { cn } from '@/lib/utils';

withDefaults(
    defineProps<{
        variant?: 'gold' | 'accept' | 'decline';
        disabled?: boolean;
        class?: string;
    }>(),
    { variant: 'gold', disabled: false, class: '' },
);
</script>

<template>
    <button
        type="button"
        :disabled="disabled"
        :class="
            cn(
                'flex h-[73px] shrink-0 items-center justify-center rounded-[60px] px-14 text-[24px] font-bold whitespace-nowrap uppercase italic transition hover:brightness-110 disabled:cursor-not-allowed disabled:opacity-50 disabled:hover:brightness-100',
                {
                    'bg-gold text-navy': variant === 'gold',
                    'bg-accept text-white': variant === 'accept',
                    'bg-decline text-white': variant === 'decline',
                },
                $props.class,
            )
        "
    >
        <slot />
    </button>
</template>
