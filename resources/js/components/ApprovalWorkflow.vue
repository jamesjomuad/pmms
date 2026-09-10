<script setup lang="ts">
import { Check, Circle, Stamp, X } from '@lucide/vue';

type StepStatus = 'approved' | 'rejected' | 'pending' | 'skipped' | string;

const props = defineProps<{
    steps: {
        id: number;
        approver: string;
        status: StepStatus;
        decided_at: string | null;
        comments: string | null;
        approver_id?: number | null;
        approved_as_noted?: boolean | null;
    }[];
    currentApproverId?: number | null;
}>();

const isCurrentStep = (index: number, status: StepStatus) =>
    status === 'pending' &&
    props.currentApproverId !== null &&
    props.currentApproverId !== undefined &&
    props.steps[index]?.approver_id === props.currentApproverId;

const titleFor = (step: (typeof props.steps)[number], index: number) => {
    if (isCurrentStep(index, step.status)) {
        return 'Awaiting your review';
    }

    if (step.status === 'approved' && step.approved_as_noted) {
        return 'Approved as Noted — see reviewer notes';
    }

    return undefined;
};

const stepClasses = (step: (typeof props.steps)[number]) => {
    switch (step.status) {
        case 'approved':
            return step.approved_as_noted
                ? 'border-amber-500 bg-amber-500 text-white'
                : 'border-emerald-500 bg-emerald-500 text-white';
        case 'rejected':
            return 'border-destructive bg-destructive text-destructive-foreground';
        case 'pending':
            return 'border-primary bg-primary text-primary-foreground';
        case 'skipped':
        default:
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

const labelClasses = (step: (typeof props.steps)[number]) => {
    switch (step.status) {
        case 'approved':
            return step.approved_as_noted
                ? 'text-amber-600 dark:text-amber-400'
                : 'text-emerald-600 dark:text-emerald-400';
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
                        :class="stepClasses(step)"
                        :title="titleFor(step, index)"
                    >
                        <Check
                            v-if="
                                step.status === 'approved' &&
                                !step.approved_as_noted
                            "
                            class="h-4 w-4"
                        />
                        <Stamp
                            v-else-if="
                                step.status === 'approved' &&
                                step.approved_as_noted
                            "
                            class="h-4 w-4"
                        />
                        <X
                            v-else-if="step.status === 'rejected'"
                            class="h-4 w-4"
                        />
                        <Circle
                            v-else-if="step.status === 'skipped'"
                            class="h-2 w-2"
                        />
                        <span v-else>{{ index + 1 }}</span>
                    </div>
                    <div class="mt-1.5 text-center">
                        <span
                            class="block max-w-[6rem] text-[11px] leading-tight sm:text-xs"
                            :class="labelClasses(step)"
                        >
                            {{ step.approver }}
                        </span>
                        <span
                            v-if="isCurrentStep(index, step.status)"
                            class="block text-[10px] font-medium text-primary"
                        >
                            Awaiting your review
                        </span>
                        <span
                            v-else-if="
                                step.status === 'approved' &&
                                step.approved_as_noted
                            "
                            class="block text-[10px] font-medium text-amber-600 dark:text-amber-400"
                        >
                            Approved as Noted
                        </span>
                        <span
                            v-else-if="step.decided_at"
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
