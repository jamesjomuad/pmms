<script setup lang="ts">
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import Heading from '@/components/Heading.vue';
import TeamAssignment from '@/components/TeamAssignment.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { index as projectsIndex, update, show as projectShow } from '@/routes/projects';
import type {
    ProjectDetail,
    ProjectRoleOption,
    StageOption,
    TeamAssignment as TeamAssignmentType,
    UserOption,
} from '@/types';

const props = defineProps<{
    project: ProjectDetail;
    stages: StageOption[];
    projectRoles: ProjectRoleOption[];
    users: UserOption[];
}>();

const page = usePage();

const indexUrl = computed(() =>
    page.props.currentTeam ? projectsIndex(page.props.currentTeam.slug).url : '#',
);

const showUrl = computed(() =>
    page.props.currentTeam ? projectShow({ current_team: page.props.currentTeam.slug, project: props.project.id }).url : '#',
);

const updateUrl = computed(() =>
    page.props.currentTeam ? update({ current_team: page.props.currentTeam.slug, project: props.project.id }).url : '#',
);

const form = useForm({
    name: props.project.name,
    client_name: props.project.client_name,
    project_number: props.project.project_number,
    awarded_date: props.project.awarded_date,
    estimated_completion_date: props.project.estimated_completion_date ?? '',
    status: props.project.status,
    notes: props.project.notes ?? '',
    team: props.project.team.map((m) => ({
        user_id: m.id,
        project_role: m.project_role,
    })) as TeamAssignmentType[],
});

const submit = () => {
    form.patch(updateUrl.value);
};

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Projects',
                href: indexUrl.value,
            },
            {
                title: 'Edit',
                href: indexUrl.value,
            },
        ],
    },
});
</script>

<template>
    <Head :title="`Edit ${project.name}`" />

    <h1 class="sr-only">Edit {{ project.name }}</h1>

    <div class="flex flex-col space-y-6">
        <div class="flex items-center justify-between">
            <Heading
                variant="small"
                :title="`Edit ${project.name}`"
                :description="project.project_number"
            />
        </div>

        <form @submit.prevent="submit" class="space-y-6">
            <Card>
                <CardHeader>
                    <CardTitle>Project Details</CardTitle>
                </CardHeader>
                <CardContent class="space-y-4">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="space-y-2">
                            <Label for="name">Project Name</Label>
                            <Input
                                id="name"
                                v-model="form.name"
                                placeholder="e.g. Downtown Office Complex"
                                required
                            />
                            <p
                                v-if="form.errors.name"
                                class="text-sm text-destructive"
                            >
                                {{ form.errors.name }}
                            </p>
                        </div>

                        <div class="space-y-2">
                            <Label for="client_name">Client Name</Label>
                            <Input
                                id="client_name"
                                v-model="form.client_name"
                                placeholder="e.g. Acme Corp"
                                required
                            />
                            <p
                                v-if="form.errors.client_name"
                                class="text-sm text-destructive"
                            >
                                {{ form.errors.client_name }}
                            </p>
                        </div>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-4">
                        <div class="space-y-2">
                            <Label for="project_number">Project Number</Label>
                            <Input
                                id="project_number"
                                v-model="form.project_number"
                                placeholder="e.g. PMMS-001"
                                required
                            />
                            <p
                                v-if="form.errors.project_number"
                                class="text-sm text-destructive"
                            >
                                {{ form.errors.project_number }}
                            </p>
                        </div>

                        <div class="space-y-2">
                            <Label for="awarded_date">Award Date</Label>
                            <Input
                                id="awarded_date"
                                v-model="form.awarded_date"
                                type="date"
                                required
                            />
                            <p
                                v-if="form.errors.awarded_date"
                                class="text-sm text-destructive"
                            >
                                {{ form.errors.awarded_date }}
                            </p>
                        </div>

                        <div class="space-y-2">
                            <Label for="estimated_completion_date">
                                Est. Completion
                            </Label>
                            <Input
                                id="estimated_completion_date"
                                v-model="form.estimated_completion_date"
                                type="date"
                            />
                            <p
                                v-if="form.errors.estimated_completion_date"
                                class="text-sm text-destructive"
                            >
                                {{ form.errors.estimated_completion_date }}
                            </p>
                        </div>

                        <div class="space-y-2">
                            <Label for="status">Status</Label>
                            <Select v-model="form.status">
                                <SelectTrigger id="status">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="active">
                                        Active
                                    </SelectItem>
                                    <SelectItem value="on_hold">
                                        On Hold
                                    </SelectItem>
                                    <SelectItem value="closed">
                                        Closed
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <p
                                v-if="form.errors.status"
                                class="text-sm text-destructive"
                            >
                                {{ form.errors.status }}
                            </p>
                        </div>
                    </div>

                    <div class="space-y-2">
                        <Label for="notes">Notes</Label>
                        <Textarea
                            id="notes"
                            v-model="form.notes"
                            placeholder="Optional project notes..."
                            rows="3"
                        />
                        <p
                            v-if="form.errors.notes"
                            class="text-sm text-destructive"
                        >
                            {{ form.errors.notes }}
                        </p>
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Project Team</CardTitle>
                </CardHeader>
                <CardContent>
                    <TeamAssignment
                        v-model="form.team"
                        :users="users"
                        :roles="projectRoles"
                    />
                </CardContent>
            </Card>

            <div class="flex items-center justify-end gap-3">
                <Button
                    type="button"
                    variant="outline"
                    @click="router.visit(showUrl)"
                >
                    Cancel
                </Button>
                <Button type="submit" :disabled="form.processing">
                    Save Changes
                </Button>
            </div>
        </form>
    </div>
</template>
