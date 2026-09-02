<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    Briefcase,
    CheckCircle2,
    Clock,
    Pause,
    Plus,
    Search,
    X,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import Heading from '@/components/Heading.vue';
import ProjectCard from '@/components/ProjectCard.vue';
import StatCard from '@/components/StatCard.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { create as projectCreate } from '@/routes/projects';
import type { Project } from '@/types';

const props = defineProps<{
    projects: Project[];
    stages: { id: number; key: string; label: string }[];
}>();

const searchQuery = ref('');
const activeStatusFilter = ref<string>('all');
const activeStageFilter = ref<string>('all');

const createUrl = projectCreate().url;

const stats = computed(() => {
    const active = props.projects.filter((p) => p.status === 'active').length;
    const onHold = props.projects.filter((p) => p.status === 'on_hold').length;
    const closed = props.projects.filter((p) => p.status === 'closed').length;

    return { active, onHold, closed, total: props.projects.length };
});

const filteredProjects = computed(() => {
    let result = props.projects;

    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase();
        result = result.filter(
            (p) =>
                p.name.toLowerCase().includes(query) ||
                p.client_name.toLowerCase().includes(query) ||
                p.project_number.toLowerCase().includes(query),
        );
    }

    if (activeStatusFilter.value !== 'all') {
        result = result.filter((p) => p.status === activeStatusFilter.value);
    }

    if (activeStageFilter.value !== 'all') {
        result = result.filter(
            (p) => p.current_stage.key === activeStageFilter.value,
        );
    }

    return result;
});

const statusFilters = [
    { value: 'all', label: 'All' },
    { value: 'active', label: 'Active' },
    { value: 'on_hold', label: 'On Hold' },
    { value: 'closed', label: 'Closed' },
];

const clearSearch = () => {
    searchQuery.value = '';
    activeStatusFilter.value = 'all';
    activeStageFilter.value = 'all';
};

const hasActiveFilters = computed(
    () =>
        searchQuery.value !== '' ||
        activeStatusFilter.value !== 'all' ||
        activeStageFilter.value !== 'all',
);

defineOptions({
    layout: () => ({
        breadcrumbs: [
            {
                title: 'Projects',
                href: '/projects',
            },
        ],
    }),
});
</script>

<template>
    <Head title="Projects" />

    <h1 class="sr-only">Projects</h1>

    <div
        class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4"
    >
        <div class="flex items-center justify-between">
            <Heading
                variant="small"
                title="Projects"
                description="Manage your HVAC projects"
            />

            <Button as-child>
                <Link :href="createUrl">
                    <Plus class="mr-1 h-4 w-4" />
                    New project
                </Link>
            </Button>
        </div>

        <div
            v-if="projects.length > 0"
            class="grid gap-2 sm:grid-cols-2 lg:grid-cols-4"
        >
            <StatCard
                :icon="Briefcase"
                :value="stats.total"
                label="Total"
            />
            <StatCard
                :icon="CheckCircle2"
                :value="stats.active"
                label="Active"
                icon-bg-class="bg-emerald-50 dark:bg-emerald-950"
                icon-class="text-emerald-600 dark:text-emerald-400"
            />
            <StatCard
                :icon="Clock"
                :value="stats.onHold"
                label="On Hold"
                icon-bg-class="bg-amber-50 dark:bg-amber-950"
                icon-class="text-amber-600 dark:text-amber-400"
            />
            <StatCard
                :icon="Pause"
                :value="stats.closed"
                label="Closed"
            />
        </div>

        <div
            v-if="projects.length > 0"
            class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
        >
            <div class="relative flex-1 sm:max-w-xs">
                <Search
                    class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground"
                />
                <Input
                    v-model="searchQuery"
                    placeholder="Search projects..."
                    aria-label="Search projects"
                    class="pl-9"
                />
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <div
                    class="flex items-center gap-1 rounded-lg border bg-card p-1"
                >
                    <button
                        v-for="filter in statusFilters"
                        :key="filter.value"
                        class="rounded-md px-3 py-1.5 text-xs font-medium transition-colors"
                        :class="
                            activeStatusFilter === filter.value
                                ? 'bg-primary text-primary-foreground'
                                : 'text-muted-foreground hover:text-foreground'
                        "
                        @click="activeStatusFilter = filter.value"
                    >
                        {{ filter.label }}
                    </button>
                </div>

                <Select v-model="activeStageFilter">
                    <SelectTrigger class="h-9 w-auto text-xs" aria-label="Filter by stage">
                        <SelectValue placeholder="All Stages" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="all">All Stages</SelectItem>
                        <SelectItem
                            v-for="stage in stages"
                            :key="stage.key"
                            :value="stage.key"
                        >
                            {{ stage.label }}
                        </SelectItem>
                    </SelectContent>
                </Select>

                <Button
                    v-if="hasActiveFilters"
                    variant="ghost"
                    size="sm"
                    class="h-8 px-2 text-xs"
                    @click="clearSearch"
                >
                    <X class="mr-1 h-3 w-3" />
                    Clear
                </Button>
            </div>
        </div>

        <div
            v-if="projects.length > 0 && filteredProjects.length > 0"
            class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3"
        >
            <ProjectCard
                v-for="project in filteredProjects"
                :key="project.id"
                :project="project"
                :total-stages="stages.length"
            />
        </div>

        <div
            v-else-if="projects.length > 0 && filteredProjects.length === 0"
            class="flex flex-col items-center justify-center rounded-lg border border-dashed px-6 py-16 text-center"
        >
            <div
                class="mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-muted"
            >
                <Search class="h-6 w-6 text-muted-foreground/60" />
            </div>
            <h3 class="mb-1 text-sm font-medium">No projects found</h3>
            <p class="mb-6 max-w-sm text-sm text-muted-foreground">
                No projects match your current filters. Try adjusting your
                search or filters.
            </p>
            <Button variant="outline" @click="clearSearch">
                Clear filters
            </Button>
        </div>

        <div
            v-else
            class="flex flex-col items-center justify-center rounded-lg border border-dashed px-6 py-16 text-center"
        >
            <div
                class="mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-muted"
            >
                <Briefcase class="h-6 w-6 text-muted-foreground/60" />
            </div>
            <h3 class="mb-1 text-sm font-medium">No projects yet</h3>
            <p class="mb-6 max-w-sm text-sm text-muted-foreground">
                Create your first project to start tracking jobs, managing
                teams, and monitoring progress through each stage.
            </p>
            <Button as-child>
                <Link :href="createUrl">
                    <Plus class="mr-1 h-4 w-4" />
                    New project
                </Link>
            </Button>
        </div>
    </div>
</template>
