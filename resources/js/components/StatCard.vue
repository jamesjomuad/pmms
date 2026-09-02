<script setup lang="ts">
import type { Component } from 'vue';

defineProps<{
    icon: Component;
    label: string;
    value: number | string;
    iconClass?: string;
    iconBgClass?: string;
}>();
</script>

<template>
    <div class="flex items-center gap-3 rounded-lg border bg-card p-3">
        <div
            class="flex h-9 w-9 shrink-0 items-center justify-center rounded-md"
            :class="iconBgClass ?? 'bg-muted'"
        >
            <component
                :is="icon"
                class="h-4 w-4"
                :class="iconClass ?? 'text-muted-foreground'"
            />
        </div>
        <div>
            <p class="text-2xl font-semibold tracking-tight">
                {{ value }}
            </p>
            <p class="text-xs text-muted-foreground">{{ label }}</p>
        </div>
    </div>
</template>
