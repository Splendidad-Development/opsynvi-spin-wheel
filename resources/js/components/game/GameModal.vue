<script setup lang="ts">
import { cn } from '@/lib/utils';

withDefaults(
    defineProps<{
        closable?: boolean;
        class?: string;
    }>(),
    { closable: true, class: '' },
);

const emit = defineEmits<{ close: [] }>();
</script>

<template>
    <div
        class="absolute inset-0 z-30 flex items-center justify-center bg-black/50"
        role="dialog"
        aria-modal="true"
    >
        <div
            :class="
                cn(
                    'relative flex flex-col items-center rounded-[60px] text-white',
                    $props.class,
                )
            "
        >
            <slot />
            <button
                v-if="closable"
                type="button"
                class="absolute -top-[23px] -right-[42.54px] size-[42.54px] transition hover:scale-110"
                aria-label="Close"
                @click="emit('close')"
            >
                <img
                    src="/images/game/icon-close.svg"
                    alt=""
                    width="44"
                    height="44"
                    class="size-full"
                />
            </button>
        </div>
    </div>
</template>
