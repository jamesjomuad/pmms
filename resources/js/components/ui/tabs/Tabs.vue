<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { provide, ref } from 'vue';

const props = defineProps<{
    class?: HTMLAttributes['class'];
    defaultValue?: string;
    modelValue?: string;
}>();

const emits = defineEmits<{
    'update:modelValue': [value: string];
}>();

const activeValue = ref(props.modelValue ?? props.defaultValue ?? '');

provide('tabs-active-value', {
    activeValue,
    setActive: (value: string) => {
        activeValue.value = value;
        emits('update:modelValue', value);
    },
});
</script>

<template>
    <div data-slot="tabs" :class="props.class">
        <slot />
    </div>
</template>
