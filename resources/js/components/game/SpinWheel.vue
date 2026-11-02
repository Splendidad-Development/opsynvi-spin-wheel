<script setup lang="ts">
import { computed, ref, watch } from 'vue';

const props = defineProps<{
    /** Slice numbers already used in this game; they are dimmed. */
    usedNumbers: number[];
    /** Slice currently in play; it stays bright and is outlined. */
    activeNumber?: number | null;
    /** Slice to rest on without animating (e.g. after a reload). */
    restingNumber?: number | null;
    /** Name of the team that played each used slice, keyed by slice number. */
    teamNames?: Record<number, string>;
}>();

const SLICES = 15;
const SLICE_ANGLE = 360 / SLICES;
const CENTER = 247;
const RADIUS = 234.35;
const LABEL_RADIUS = 118;
const SPIN_DURATION_MS = 4800;
const REDUCED_MOTION_DURATION_MS = 600;

const rotation = ref(restingRotation(props.restingNumber));
const durationMs = ref(0);

/**
 * Slice 1 sits under the pointer (3 o'clock) and numbers increase clockwise,
 * so slice n is brought to the pointer by rotating the wheel −(n − 1) × 24°.
 */
function restingRotation(sliceNumber: number | null | undefined): number {
    return sliceNumber ? (360 - (sliceNumber - 1) * SLICE_ANGLE) % 360 : 0;
}

watch(
    () => props.restingNumber,
    (sliceNumber) => {
        if (durationMs.value > 0 || !sliceNumber) {
            return;
        }

        const target = restingRotation(sliceNumber);
        const offset = Math.abs(((rotation.value - target + 540) % 360) - 180);

        // Only re-seat when the wheel is not already resting inside that slice.
        if (offset > SLICE_ANGLE / 2) {
            rotation.value = target;
        }
    },
);

function prefersReducedMotion(): boolean {
    return window.matchMedia('(prefers-reduced-motion: reduce)').matches;
}

/**
 * Spin several full turns and land on the given slice. Resolves when the wheel stops.
 */
function spinTo(sliceNumber: number): Promise<void> {
    const reduced = prefersReducedMotion();
    const jitter = (Math.random() - 0.5) * SLICE_ANGLE * 0.6;
    const target = restingRotation(sliceNumber) + jitter;
    const current = rotation.value;
    const fullTurns = reduced ? 1 : 6;
    const delta = (((target - current) % 360) + 360) % 360;

    durationMs.value = reduced ? REDUCED_MOTION_DURATION_MS : SPIN_DURATION_MS;
    rotation.value = current + fullTurns * 360 + delta;

    return new Promise((resolve) =>
        window.setTimeout(() => {
            durationMs.value = 0;
            resolve();
        }, durationMs.value + 100),
    );
}

function reset(): void {
    durationMs.value = 0;
    rotation.value = 0;
}

defineExpose({ spinTo, reset });

function slicePath(sliceNumber: number): string {
    const toPoint = (degrees: number) => {
        const radians = (degrees * Math.PI) / 180;

        return `${CENTER + RADIUS * Math.cos(radians)} ${CENTER + RADIUS * Math.sin(radians)}`;
    };
    const middle = (sliceNumber - 1) * SLICE_ANGLE;

    return `M ${CENTER} ${CENTER} L ${toPoint(middle - SLICE_ANGLE / 2)} A ${RADIUS} ${RADIUS} 0 0 1 ${toPoint(middle + SLICE_ANGLE / 2)} Z`;
}

const dimmedSlices = computed(() =>
    props.usedNumbers.filter((number) => number !== props.activeNumber),
);

/**
 * Team labels run along each slice's centre line, between the hub and the printed number.
 */
const teamLabels = computed(() =>
    Object.entries(props.teamNames ?? {}).map(([number, name]) => ({
        number: Number(number),
        name,
        angle: (Number(number) - 1) * SLICE_ANGLE,
    })),
);
</script>

<template>
    <div class="relative h-[494px] w-[530px] shrink-0">
        <div
            class="absolute top-0 left-0 size-[494px]"
            :style="{
                transform: `rotate(${rotation}deg)`,
                transition: durationMs
                    ? `transform ${durationMs}ms cubic-bezier(0.12, 0.8, 0.2, 1)`
                    : 'none',
            }"
        >
            <img
                src="/images/game/wheel.svg"
                alt=""
                width="494"
                height="494"
                class="size-full select-none"
                draggable="false"
            />
            <svg
                viewBox="0 0 494 494"
                class="pointer-events-none absolute inset-0 size-full"
                aria-hidden="true"
            >
                <path
                    v-for="number in dimmedSlices"
                    :key="number"
                    :d="slicePath(number)"
                    fill="#0a1f44"
                    fill-opacity="0.72"
                />
                <path
                    v-if="activeNumber"
                    :d="slicePath(activeNumber)"
                    fill="none"
                    stroke="#fcb61a"
                    stroke-width="5"
                    stroke-linejoin="round"
                />
                <text
                    v-for="label in teamLabels"
                    :key="`team-${label.number}`"
                    :x="CENTER + LABEL_RADIUS"
                    :y="CENTER"
                    :transform="`rotate(${label.angle} ${CENTER} ${CENTER})`"
                    text-anchor="middle"
                    dominant-baseline="central"
                    fill="#ffffff"
                    stroke="#0a1f44"
                    stroke-width="4"
                    stroke-linejoin="round"
                    paint-order="stroke"
                    class="text-[15px] font-extrabold tracking-wide uppercase"
                >
                    {{ label.name }}
                </text>
            </svg>
        </div>
        <img
            src="/images/game/wheel-pointer.svg"
            alt=""
            width="37"
            height="25"
            class="absolute top-[234.53px] left-[493.6px] h-[25px] w-[37px]"
        />
    </div>
</template>
