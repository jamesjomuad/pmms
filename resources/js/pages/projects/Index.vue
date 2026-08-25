<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Plus } from '@lucide/vue';
import { computed } from 'vue';
import Heading from '@/components/Heading.vue';
import ProjectCard from '@/components/ProjectCard.vue';
import { Button } from '@/components/ui/button';
import { create as projectCreate } from '@/routes/projects';
import type { Project, Team } from '@/types';

const props = defineProps<{
    projects: Project[];
}>();

const createUrl = computed(() =>
    props.currentTeam
        ? projectCreate(props.currentTeam.slug).url
        : '#',
);

defineOptions({
    layout: (layoutProps: { currentTeam?: Team | null }) => ({
        breadcrumbs: [
            {
                title: 'Projects',
                href: layoutProps.currentTeam
                    ? `/projects`
                    : '/',
            },
        ],
    }),
});
</script>

<template>
    <Head title="Projects" />

    <h1 class="sr-only">Projects</h1>

    <div class="flex flex-col space-y-6">
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
            class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3"
        >
            <ProjectCard
                v-for="project in projects"
                :key="project.id"
                :project="project"
            />
        </div>

        <div
            v-else
            class="flex flex-col items-center justify-center rounded-lg border border-dashed p-12 text-center"
        >
            <p class="text-sm text-muted-foreground">
                No projects yet. Create your first project to get started.
            </p>
            <Button as-child class="mt-4">
                <Link :href="createUrl">
                    <Plus class="mr-1 h-4 w-4" />
                    New project
                </Link>
            </Button>
        </div>
    </div>
</template>
