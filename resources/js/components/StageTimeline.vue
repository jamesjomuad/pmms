<script setup lang="ts">
import { Check } from '@lucide/vue';
import type { ProjectStage } from '@/types';

defineProps<{
    stages: ProjectStage[];
    currentStageId: number;
}>();

const getStageIndex = (stages: ProjectStage[], id: number) =>
    stages.findIndex((s) => s.id === id);
</script>

<template>
    <nav aria-label="Project progress" class="w-full">
        <ol class="flex items-center">
            <li
                v-for="(stage, index) in stages"
                :key="stage.id"
                class="flex items-center"
                :class="index < stages.length - 1 ? 'flex-1' : ''"
            >
                <div class="flex flex-col items-center">
                    <div
                        class="flex h-8 w-8 items-center justify-center rounded-full border-2 text-xs font-medium transition-all duration-200"
                        :class="
                            stage.id === currentStageId
                                ? 'border-primary bg-primary text-primary-foreground shadow-sm shadow-primary/20'
                                : getStageIndex(stages, stage.id) <
                                    getStageIndex(stages, currentStageId)
                                  ? 'border-primary bg-primary/10 text-primary'
                                  : 'border-border bg-background text-muted-foreground'
                        "
                    >
                        <Check
                            v-if="
                                getStageIndex(stages, stage.id) <
                                getStageIndex(stages, currentStageId)
                            "
                            class="h-4 w-4"
                        />
                        <span v-else>{{ index + 1 }}</span>
                    </div>
                    <span
                        class="mt-1.5 max-w-[5rem] text-center text-[11px] leading-tight sm:text-xs"
                        :class="
                            stage.id === currentStageId
                                ? 'font-medium text-foreground'
                                : 'text-muted-foreground'
                        "
                    >
                        {{ stage.label }}
                    </span>
                </div>
                <div
                    v-if="index < stages.length - 1"
                    class="mx-1 h-0.5 flex-1"
                    :class="
                        getStageIndex(stages, stage.id) <
                        getStageIndex(stages, currentStageId)
                            ? 'bg-primary'
                            : 'bg-border'
                    "
                />
            </li>
        </ol>
    </nav>
</template>
