<script setup lang="ts">
import { Check, Circle, X } from '@lucide/vue';

type StepStatus = 'approved' | 'rejected' | 'pending' | 'skipped';

defineProps<{
    steps: {
        id: number;
        approver: string;
        status: StepStatus;
        decided_at: string | null;
        comments: string | null;
    }[];
}>();

const stepClasses = (status: StepStatus) => {
    switch (status) {
        case 'approved':
            return 'border-emerald-500 bg-emerald-500 text-white';
        case 'rejected':
            return 'border-destructive bg-destructive text-destructive-foreground';
        case 'pending':
            return 'border-primary bg-primary text-primary-foreground';
        case 'skipped':
            return 'border-border bg-background text-muted-foreground';
    }
};

const lineClasses = (status: StepStatus) => {
    switch (status) {
        case 'approved':
            return 'bg-emerald-500';
        case 'rejected':
            return 'bg-destructive';
        default:
            return 'bg-border';
    }
};

const labelClasses = (status: StepStatus) => {
    switch (status) {
        case 'approved':
            return 'text-emerald-600 dark:text-emerald-400';
        case 'rejected':
            return 'text-destructive';
        default:
            return 'text-muted-foreground';
    }
};
</script>

<template>
    <nav v-if="steps.length > 0" aria-label="Approval workflow" class="w-full">
        <ol class="flex items-center">
            <li
                v-for="(step, index) in steps"
                :key="step.id"
                class="flex items-center"
                :class="index < steps.length - 1 ? 'flex-1' : ''"
            >
                <div class="flex flex-col items-center">
                    <div
                        class="flex h-8 w-8 items-center justify-center rounded-full border-2 text-xs font-medium transition-all duration-200"
                        :class="stepClasses(step.status)"
                    >
                        <Check v-if="step.status === 'approved'" class="h-4 w-4" />
                        <X v-else-if="step.status === 'rejected'" class="h-4 w-4" />
                        <Circle v-else-if="step.status === 'skipped'" class="h-2 w-2" />
                        <span v-else>{{ index + 1 }}</span>
                    </div>
                    <div class="mt-1.5 text-center">
                        <span
                            class="block max-w-[6rem] text-[11px] leading-tight sm:text-xs"
                            :class="labelClasses(step.status)"
                        >
                            {{ step.approver }}
                        </span>
                        <span
                            v-if="step.decided_at"
                            class="block text-[10px] text-muted-foreground"
                        >
                            {{ new Date(step.decided_at).toLocaleDateString() }}
                        </span>
                    </div>
                </div>
                <div
                    v-if="index < steps.length - 1"
                    class="mx-1 h-0.5 flex-1"
                    :class="lineClasses(step.status)"
                />
            </li>
        </ol>
    </nav>
</template>
