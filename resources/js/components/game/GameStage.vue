<script setup lang="ts">
import { onBeforeUnmount, onMounted, ref } from 'vue';

const STAGE_WIDTH = 1920;
const STAGE_HEIGHT = 1080;

const scale = ref(1);

function updateScale(): void {
    scale.value = Math.min(
        window.innerWidth / STAGE_WIDTH,
        window.innerHeight / STAGE_HEIGHT,
    );
}

onMounted(() => {
    updateScale();
    window.addEventListener('resize', updateScale);
});

onBeforeUnmount(() => window.removeEventListener('resize', updateScale));
</script>

<template>
    <!-- The design is a fixed 1920×1080 screen; it is scaled as a whole to fit any display. -->
    <div
        class="fixed inset-0 overflow-hidden bg-[radial-gradient(ellipse_at_center,#1b4e8c_0%,#133a6e_27%,#0b2550_55%,#081937_77%,#040c1e_100%)]"
    >
        <img
            src="/images/game/bg.jpg"
            alt=""
            class="pointer-events-none absolute inset-0 size-full object-cover"
        />
        <div
            class="absolute top-1/2 left-1/2 h-[1080px] w-[1920px] origin-center"
            :style="{
                transform: `translate(-50%, -50%) scale(${scale})`,
            }"
        >
            <slot />
        </div>

        <!-- Content pinned to the screen edges rather than the scaled stage. -->
        <slot name="overlay" :scale="scale" />
    </div>
</template>
