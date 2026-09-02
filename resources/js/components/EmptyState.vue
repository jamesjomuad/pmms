<script setup lang="ts">
import type { Component } from 'vue';

defineProps<{
    icon: Component;
    title: string;
    description: string;
    actionLabel?: string;
    actionHref?: string;
}>();
</script>

<template>
    <div
        class="flex flex-col items-center justify-center rounded-lg border border-dashed px-6 py-16 text-center"
    >
        <div
            class="mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-muted"
        >
            <component :is="icon" class="h-6 w-6 text-muted-foreground/60" />
        </div>
        <h3 class="mb-1 text-sm font-medium">{{ title }}</h3>
        <p class="mb-6 max-w-sm text-sm text-muted-foreground">
            {{ description }}
        </p>
        <slot name="action" />
    </div>
</template>
