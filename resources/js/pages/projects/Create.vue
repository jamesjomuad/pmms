<script setup lang="ts">
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import Heading from '@/components/Heading.vue';
import TeamAssignment from '@/components/TeamAssignment.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import DatePicker from '@/components/ui/date-picker/DatePicker.vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { index as projectIndex, create as projectCreate, store } from '@/routes/projects';
import type {
    ProjectRoleOption,
    StageOption,
    Team,
    TeamAssignment as TeamAssignmentType,
    UserOption,
} from '@/types';

const props = defineProps<{
    stages: StageOption[];
    projectRoles: ProjectRoleOption[];
    users: UserOption[];
    nextProjectNumber: string;
}>();

const page = usePage();

const storeUrl = computed(() =>
    page.props.currentTeam ? store(page.props.currentTeam.slug).url : '#',
);

const cancelUrl = computed(() =>
    page.props.currentTeam
        ? projectIndex(page.props.currentTeam.slug).url
        : '/projects',
);

const form = useForm({
    name: '',
    client_name: '',
    project_number: props.nextProjectNumber,
    awarded_date: new Date().toISOString().split('T')[0],
    estimated_completion_date: '',
    notes: '',
    team: [] as TeamAssignmentType[],
});

const submit = () => {
    form.post(storeUrl.value);
};

defineOptions({
    layout: (layoutProps: { currentTeam?: Team | null }) => ({
        breadcrumbs: [
            {
                title: 'Projects',
                href: layoutProps.currentTeam ? projectIndex(layoutProps.currentTeam.slug).url : '/',
            },
            {
                title: 'New Project',
                href: layoutProps.currentTeam ? projectCreate(layoutProps.currentTeam.slug).url : '/',
            },
        ],
    }),
});
</script>

<template>
    <Head title="New Project" />

    <h1 class="sr-only">New Project</h1>

    <div
        class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4"
    >
        <div class="flex items-center justify-between">
            <Heading
                variant="small"
                title="New Project"
                description="Create a new HVAC project"
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

                    <div class="grid gap-4 sm:grid-cols-3">
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
                            <DatePicker
                                id="awarded_date"
                                v-model="form.awarded_date"
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
                                Estimated Completion
                            </Label>
                            <DatePicker
                                id="estimated_completion_date"
                                v-model="form.estimated_completion_date"
                            />
                            <p
                                v-if="form.errors.estimated_completion_date"
                                class="text-sm text-destructive"
                            >
                                {{ form.errors.estimated_completion_date }}
                            </p>
                        </div>
                    </div>

                    <div class="space-y-2">
                        <Label for="notes">Notes</Label>
                        <textarea
                            id="notes"
                            v-model="form.notes"
                            placeholder="Optional project notes..."
                            rows="3"
                            class="flex min-h-[60px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50"
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
                    @click="router.visit(cancelUrl)"
                >
                    Cancel
                </Button>
                <Button type="submit" :disabled="form.processing">
                    Create Project
                </Button>
            </div>
        </form>
    </div>
</template>
