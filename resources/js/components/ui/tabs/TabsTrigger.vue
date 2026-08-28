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
    setActive: (value: string) => void;
}>('tabs-active-value');

const isActive = () => ctx?.activeValue.value === props.value;

const onClick = () => ctx?.setActive(props.value);
</script>

<template>
    <button
        data-slot="tabs-trigger"
        :class="
            cn(
                'inline-flex items-center justify-center whitespace-nowrap rounded-md px-3 py-1 text-sm font-medium ring-offset-background transition-all focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50',
                isActive()
                    ? 'bg-background text-foreground shadow'
                    : 'text-muted-foreground hover:text-foreground',
                props.class,
            )
        "
        @click="onClick"
    >
        <slot />
    </button>
</template>
