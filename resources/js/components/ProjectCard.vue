<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import { show as projectShow } from '@/routes/projects';
import type { Project } from '@/types';

const props = defineProps<{
    project: Project;
    totalStages?: number;
}>();

const projectUrl = computed(() => projectShow(props.project.id).url);

const statusConfig = computed(() => {
    switch (props.project.status) {
        case 'active':
            return {
                label: 'Active',
                dot: 'bg-emerald-500',
                border: 'border-l-emerald-500',
                badge: 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950 dark:text-emerald-400 dark:border-emerald-800',
            };
        case 'on_hold':
            return {
                label: 'On Hold',
                dot: 'bg-amber-500',
                border: 'border-l-amber-500',
                badge: 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-950 dark:text-amber-400 dark:border-amber-800',
            };
        case 'closed':
            return {
                label: 'Closed',
                dot: 'bg-muted-foreground/40',
                border: 'border-l-muted-foreground/40',
                badge: 'bg-muted text-muted-foreground border-border',
            };
        default:
            return {
                label: props.project.status,
                dot: 'bg-muted-foreground/40',
                border: 'border-l-muted-foreground/40',
                badge: 'bg-muted text-muted-foreground border-border',
            };
    }
});

const stageProgress = computed(() => {
    if (!props.totalStages || props.totalStages === 0) {
        return 0;
    }

    const stageIndex = props.project.current_stage.id - 1;

    return Math.round(((stageIndex + 1) / props.totalStages) * 100);
});
</script>

<template>
    <Link :href="projectUrl" class="group block">
        <div
            class="relative overflow-hidden rounded-lg border border-l-[3px] bg-card transition-all duration-200 hover:border-l-muted-foreground/25 hover:shadow-md hover:shadow-black/5 dark:hover:shadow-black/20"
            :class="statusConfig.border"
        >
            <div class="p-4">
                <div class="mb-3 flex items-start justify-between gap-2">
                    <h3
                        class="text-sm leading-tight font-semibold tracking-tight text-foreground transition-colors group-hover:text-primary"
                    >
                        {{ project.name }}
                    </h3>
                    <span
                        class="inline-flex shrink-0 items-center gap-1.5 rounded-full border px-2 py-0.5 text-[11px] font-medium"
                        :class="statusConfig.badge"
                    >
                        <span
                            class="h-1.5 w-1.5 rounded-full"
                            :class="statusConfig.dot"
                        />
                        {{ statusConfig.label }}
                    </span>
                </div>

                <p class="mb-3 text-sm text-muted-foreground">
                    {{ project.client_name }}
                </p>

                <div class="mb-3 flex items-center gap-2">
                    <span
                        class="inline-flex items-center rounded-md bg-muted px-1.5 py-0.5 font-mono text-xs text-muted-foreground"
                    >
                        {{ project.project_number }}
                    </span>
                    <span class="text-xs text-muted-foreground/60">·</span>
                    <span class="text-xs text-muted-foreground">
                        {{ project.awarded_date }}
                    </span>
                </div>

                <div class="space-y-1.5">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-medium text-muted-foreground">
                            {{ project.current_stage.label }}
                        </span>
                        <span class="text-[11px] text-muted-foreground/70">
                            {{ stageProgress }}%
                        </span>
                    </div>
                    <div
                        class="h-1.5 w-full overflow-hidden rounded-full bg-muted"
                    >
                        <div
                            class="h-full rounded-full transition-all duration-500 ease-out"
                            :class="
                                project.status === 'active'
                                    ? 'bg-emerald-500'
                                    : project.status === 'on_hold'
                                      ? 'bg-amber-500'
                                      : 'bg-muted-foreground/30'
                            "
                            :style="{ width: `${stageProgress}%` }"
                        />
                    </div>
                </div>

                <div
                    v-if="project.team_count && project.team_count > 0"
                    class="mt-3 flex items-center gap-1.5 text-xs text-muted-foreground"
                >
                    <svg
                        class="h-3.5 w-3.5"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke-width="1.5"
                        stroke="currentColor"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z"
                        />
                    </svg>
                    <span
                        >{{ project.team_count }} member{{
                            project.team_count !== 1 ? 's' : ''
                        }}</span
                    >
                </div>
            </div>
        </div>
    </Link>
</template>
