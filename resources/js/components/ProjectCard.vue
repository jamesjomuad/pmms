<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { Badge } from '@/components/ui/badge';
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { show as projectShow } from '@/routes/projects';
import type { Project } from '@/types';

const props = defineProps<{
    project: Project;
}>();

const page = usePage();

const projectUrl = computed(() =>
    page.props.currentTeam
        ? projectShow({ current_team: page.props.currentTeam.slug, project: props.project.id }).url
        : '#',
);

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
</script>

<template>
    <Link :href="projectUrl" class="block">
        <Card class="transition-colors hover:border-muted-foreground/25">
            <CardHeader class="pb-3">
                <div class="flex items-start justify-between gap-2">
                    <CardTitle class="text-base leading-tight">
                        {{ project.name }}
                    </CardTitle>
                    <Badge :variant="statusVariant(project.status)" class="shrink-0 text-xs">
                        {{ statusLabel(project.status) }}
                    </Badge>
                </div>
                <p class="text-sm text-muted-foreground">
                    {{ project.client_name }}
                </p>
            </CardHeader>
            <CardContent>
                <div class="flex items-center justify-between text-sm text-muted-foreground">
                    <span class="font-mono text-xs">
                        {{ project.project_number }}
                    </span>
                    <Badge variant="outline" class="text-xs">
                        {{ project.current_stage.label }}
                    </Badge>
                </div>
            </CardContent>
        </Card>
    </Link>
</template>
