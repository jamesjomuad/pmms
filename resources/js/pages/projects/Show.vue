<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Pencil } from '@lucide/vue';
import { computed } from 'vue';
import Heading from '@/components/Heading.vue';
import StageTimeline from '@/components/StageTimeline.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { edit as projectEdit } from '@/routes/projects';
import type { ProjectDetail, StageOption } from '@/types';

const props = defineProps<{
    project: ProjectDetail;
    stages: StageOption[];
}>();

const editUrl = computed(() => projectEdit(props.project.id).url);

const statusVariant = (status: string) => {
    switch (status) {
        case 'active':
            return 'default';
        case 'on_hold':
            return 'secondary';
        case 'closed':
            return 'outline';
        default:
            return 'default';
    }
};

const statusLabel = (status: string) => {
    switch (status) {
        case 'active':
            return 'Active';
        case 'on_hold':
            return 'On Hold';
        case 'closed':
            return 'Closed';
        default:
            return status;
    }
};

defineOptions({
    layout: () => {
        const pathMatch = window.location.pathname.match(/\/projects\/(\d+)/);
        const projectId = pathMatch ? pathMatch[1] : '';
        return {
            breadcrumbs: [
                {
                    title: 'Projects',
                    href: '/projects',
                },
                {
                    title: 'Project Details',
                    href: `/projects/${projectId}`,
                },
            ],
        };
    },
});
</script>

<template>
    <Head :title="project.name" />

    <h1 class="sr-only">{{ project.name }}</h1>

    <div
        class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4"
    >
        <div class="flex items-center justify-between">
            <Heading
                variant="small"
                :title="project.name"
                :description="`${project.client_name} · ${project.project_number}`"
            />
            <Button variant="outline" as-child>
                <Link :href="editUrl">
                    <Pencil class="mr-1 h-4 w-4" />
                    Edit
                </Link>
            </Button>
        </div>

        <Card>
            <CardContent class="pt-6">
                <StageTimeline
                    :stages="stages"
                    :current-stage-id="project.current_stage.id"
                />
            </CardContent>
        </Card>

        <div class="grid gap-6 lg:grid-cols-3">
            <Card class="lg:col-span-2">
                <CardHeader>
                    <CardTitle>Project Details</CardTitle>
                </CardHeader>
                <CardContent>
                    <dl class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <dt class="text-sm text-muted-foreground">
                                Status
                            </dt>
                            <dd class="mt-1">
                                <Badge :variant="statusVariant(project.status)">
                                    {{ statusLabel(project.status) }}
                                </Badge>
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm text-muted-foreground">
                                Current Stage
                            </dt>
                            <dd class="mt-1">
                                <Badge variant="outline">
                                    {{ project.current_stage.label }}
                                </Badge>
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm text-muted-foreground">
                                Award Date
                            </dt>
                            <dd class="mt-1 text-sm">
                                {{ project.awarded_date }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm text-muted-foreground">
                                Est. Completion
                            </dt>
                            <dd class="mt-1 text-sm">
                                {{ project.estimated_completion_date ?? '—' }}
                            </dd>
                        </div>
                        <div v-if="project.notes" class="sm:col-span-2">
                            <dt class="text-sm text-muted-foreground">Notes</dt>
                            <dd class="mt-1 text-sm whitespace-pre-wrap">
                                {{ project.notes }}
                            </dd>
                        </div>
                    </dl>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>
                        Team
                        <span class="text-sm font-normal text-muted-foreground">
                            ({{ project.team.length }})
                        </span>
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <ul v-if="project.team.length > 0" class="space-y-3">
                        <li
                            v-for="member in project.team"
                            :key="member.id"
                            class="flex items-center justify-between"
                        >
                            <div class="min-w-0">
                                <p class="truncate text-sm font-medium">
                                    {{ member.name }}
                                </p>
                                <p
                                    class="truncate text-xs text-muted-foreground"
                                >
                                    {{ member.email }}
                                </p>
                            </div>
                            <Badge variant="secondary" class="shrink-0 text-xs">
                                {{ member.project_role_label }}
                            </Badge>
                        </li>
                    </ul>
                    <p v-else class="text-sm text-muted-foreground">
                        No team members assigned.
                    </p>
                </CardContent>
            </Card>
        </div>

        <Card>
            <CardHeader>
                <CardTitle>Stage History</CardTitle>
            </CardHeader>
            <CardContent>
                <ul v-if="project.stage_history.length > 0" class="space-y-4">
                    <li
                        v-for="entry in project.stage_history"
                        :key="entry.id"
                        class="flex items-start gap-3"
                    >
                        <div
                            class="mt-1 h-2 w-2 shrink-0 rounded-full bg-primary"
                        />
                        <div>
                            <p class="text-sm">
                                <span class="font-medium">
                                    {{ entry.changed_by }}
                                </span>
                                moved project to
                                <span class="font-medium">
                                    {{ entry.to_stage }}
                                </span>
                                <span v-if="entry.from_stage">
                                    from
                                    <span class="font-medium">
                                        {{ entry.from_stage }}
                                    </span>
                                </span>
                            </p>
                            <p class="mt-0.5 text-xs text-muted-foreground">
                                {{
                                    new Date(entry.changed_at).toLocaleString()
                                }}
                            </p>
                            <p
                                v-if="entry.notes"
                                class="mt-1 text-sm text-muted-foreground"
                            >
                                {{ entry.notes }}
                            </p>
                        </div>
                    </li>
                </ul>
                <p v-else class="text-sm text-muted-foreground">
                    No stage history yet.
                </p>
            </CardContent>
        </Card>
    </div>
</template>
