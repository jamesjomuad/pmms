<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { inject } from 'vue';
import { cn } from '@/lib/utils';

const props = defineProps<{
    class?: HTMLAttributes['class'];
    value: string;
}>();

const ctx = inject<{
    activeValue: { value: string };
}>('tabs-active-value');

const isActive = () => ctx?.activeValue.value === props.value;
</script>

<template>
    <div
        v-if="isActive()"
        data-slot="tabs-content"
        :class="
            cn(
                'mt-2 ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2',
                props.class,
            )
        "
    >
        <slot />
    </div>
</template>
