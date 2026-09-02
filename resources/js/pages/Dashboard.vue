<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    Briefcase,
    CheckCircle2,
    Clock,
    Plus,
    Pause,
    Activity,
} from '@lucide/vue';
import Heading from '@/components/Heading.vue';
import PendingInvitationsModal from '@/components/PendingInvitationsModal.vue';
import StatCard from '@/components/StatCard.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { statusLabel, statusVariant } from '@/composables/statusHelpers';
import { create as projectCreate } from '@/routes/projects';
import type { DashboardInvitation } from '@/types';

type ProjectSummary = {
    id: number;
    name: string;
    client_name: string;
    project_number: string;
    status: string;
    current_stage: { key: string; label: string };
};

type ActivityEntry = {
    description: string;
    event: string;
    created_at: string;
    causer_name: string;
    project_name: string | null;
    project_id: number | null;
};

defineProps<{
    pendingInvitations?: DashboardInvitation[];
    projectStats: { total: number; active: number; on_hold: number; closed: number };
    recentProjects: ProjectSummary[];
    recentActivity: ActivityEntry[];
}>();

const createUrl = projectCreate().url;

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString(undefined, {
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

defineOptions({
    layout: () => ({
        breadcrumbs: [
            {
                title: 'Dashboard',
                href: '/dashboard',
            },
        ],
    }),
});
</script>

<template>
    <Head title="Dashboard" />

    <PendingInvitationsModal
        v-if="pendingInvitations && pendingInvitations.length > 0"
        :invitations="pendingInvitations"
    />

    <div
        class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4"
    >
        <div class="flex items-center justify-between">
            <Heading
                variant="small"
                title="Dashboard"
                description="Overview of your projects and recent activity"
            />

            <Button as-child>
                <Link :href="createUrl">
                    <Plus class="mr-1 h-4 w-4" />
                    New project
                </Link>
            </Button>
        </div>

        <!-- Stats -->
        <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-4">
            <StatCard
                :icon="Briefcase"
                :value="projectStats.total"
                label="Total Projects"
            />
            <StatCard
                :icon="CheckCircle2"
                :value="projectStats.active"
                label="Active"
                icon-bg-class="bg-emerald-50 dark:bg-emerald-950"
                icon-class="text-emerald-600 dark:text-emerald-400"
            />
            <StatCard
                :icon="Clock"
                :value="projectStats.on_hold"
                label="On Hold"
                icon-bg-class="bg-amber-50 dark:bg-amber-950"
                icon-class="text-amber-600 dark:text-amber-400"
            />
            <StatCard
                :icon="Pause"
                :value="projectStats.closed"
                label="Closed"
            />
        </div>

        <div class="grid gap-6 lg:grid-cols-2">
            <!-- Recent Projects -->
            <div class="rounded-lg border">
                <div class="flex items-center justify-between border-b px-4 py-3">
                    <h2 class="text-sm font-medium">Recent Projects</h2>
                    <Link
                        href="/projects"
                        class="text-xs text-muted-foreground hover:text-foreground"
                    >
                        View all
                    </Link>
                </div>
                <div v-if="recentProjects.length > 0" class="divide-y">
                    <Link
                        v-for="project in recentProjects"
                        :key="project.id"
                        :href="`/projects/${project.id}`"
                        class="flex items-center justify-between px-4 py-3 hover:bg-muted/50 transition-colors"
                    >
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-medium">
                                {{ project.name }}
                            </p>
                            <p class="truncate text-xs text-muted-foreground">
                                {{ project.client_name }} &middot; {{ project.project_number }}
                            </p>
                        </div>
                        <div class="ml-4 flex items-center gap-2">
                            <Badge :variant="statusVariant(project.status)" class="text-xs">
                                {{ statusLabel(project.status) }}
                            </Badge>
                        </div>
                    </Link>
                </div>
                <div v-else class="px-4 py-12 text-center">
                    <Briefcase class="mx-auto mb-3 h-8 w-8 text-muted-foreground/40" />
                    <p class="text-sm text-muted-foreground">No projects yet</p>
                    <Button as-child variant="outline" size="sm" class="mt-3">
                        <Link :href="createUrl">
                            <Plus class="mr-1 h-3 w-3" />
                            Create project
                        </Link>
                    </Button>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="rounded-lg border">
                <div class="flex items-center justify-between border-b px-4 py-3">
                    <h2 class="text-sm font-medium">Recent Activity</h2>
                    <Link
                        href="/activity-log"
                        class="text-xs text-muted-foreground hover:text-foreground"
                    >
                        View all
                    </Link>
                </div>
                <div v-if="recentActivity.length > 0" class="divide-y">
                    <div
                        v-for="(entry, index) in recentActivity"
                        :key="index"
                        class="px-4 py-3"
                    >
                        <p class="text-sm">
                            <span class="font-medium">{{ entry.causer_name }}</span>
                            <span class="text-muted-foreground"> {{ entry.description }}</span>
                        </p>
                        <div class="mt-1 flex items-center gap-2 text-xs text-muted-foreground">
                            <span>{{ formatDate(entry.created_at) }}</span>
                            <template v-if="entry.project_name">
                                <span>&middot;</span>
                                <Link
                                    :href="`/projects/${entry.project_id}`"
                                    class="hover:text-foreground hover:underline"
                                >
                                    {{ entry.project_name }}
                                </Link>
                            </template>
                        </div>
                    </div>
                </div>
                <div v-else class="px-4 py-12 text-center">
                    <Activity class="mx-auto mb-3 h-8 w-8 text-muted-foreground/40" />
                    <p class="text-sm text-muted-foreground">No recent activity</p>
                </div>
            </div>
        </div>
    </div>
</template>
