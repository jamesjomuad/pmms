<script setup lang="ts">
import { ref, watch } from 'vue';
import { Calendar } from '@lucide/vue';
import { cn } from '@/lib/utils';

const props = withDefaults(
    defineProps<{
        modelValue?: string;
        placeholder?: string;
        disabled?: boolean;
        class?: string;
        id?: string;
    }>(),
    {
        placeholder: 'Pick a date',
    },
);

const emit = defineEmits<{
    (e: 'update:modelValue', value: string): void;
}>();

const inputRef = ref<HTMLInputElement | null>(null);

const handleWrapperClick = () => {
    if (props.disabled) return;

    const input = inputRef.value;
    if (input) {
        input.showPicker?.();
        input.focus();
    }
};

watch(
    () => props.modelValue,
    (val) => {
        emit('update:modelValue', val ?? '');
    },
);
</script>

<template>
    <div
        class="relative flex items-center"
        :class="disabled ? 'cursor-not-allowed opacity-50' : 'cursor-pointer'"
        @click="handleWrapperClick"
    >
        <input
            :id="id"
            ref="inputRef"
            type="date"
            :value="modelValue"
            :disabled="disabled"
            :class="
                cn(
                    'file:text-foreground placeholder:text-muted-foreground selection:bg-primary selection:text-primary-foreground dark:bg-input/30 border-input h-9 w-full min-w-0 rounded-md border bg-transparent px-3 py-1 pr-10 text-base shadow-xs transition-[color,box-shadow] outline-none file:inline-flex file:h-7 file:border-0 file:bg-transparent file:text-sm file:font-medium disabled:pointer-events-none disabled:cursor-not-allowed disabled:opacity-50 md:text-sm',
                    'focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]',
                    'aria-invalid:ring-destructive/20 dark:aria-invalid:ring-destructive/40 aria-invalid:border-destructive',
                    props.class,
                )
            "
            @input="emit('update:modelValue', ($event.target as HTMLInputElement).value)"
        />
        <Calendar
            class="pointer-events-none absolute right-3 h-4 w-4 text-muted-foreground"
        />
    </div>
</template>
