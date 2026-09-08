<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import Heading from '@/components/Heading.vue';
import TeamAssignment from '@/components/TeamAssignment.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import DatePicker from '@/components/ui/date-picker/DatePicker.vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    show as projectShow,
    update as projectUpdate,
} from '@/routes/projects';
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

const showUrl = computed(() => projectShow(props.project.id).url);
const updateUrl = computed(() => projectUpdate(props.project.id).url);

const form = useForm({
    name: props.project.name,
    client_name: props.project.client_name,
    project_number: props.project.project_number,
    awarded_date: props.project.awarded_date,
    estimated_completion_date: props.project.estimated_completion_date ?? '',
    status: props.project.status,
    notes: props.project.notes ?? '',
    address: props.project.address ?? '',
    city: props.project.city ?? '',
    state: props.project.state ?? '',
    postal_code: props.project.postal_code ?? '',
    latitude: props.project.latitude?.toString() ?? '',
    longitude: props.project.longitude?.toString() ?? '',
    team: props.project.team.map((m) => ({
        user_id: m.id,
        project_role: m.project_role,
    })) as TeamAssignmentType[],
});

const submit = () => {
    form.patch(updateUrl.value);
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
                {
                    title: 'Edit',
                    href: `/projects/${projectId}/edit`,
                },
            ],
        };
    },
});
</script>

<template>
    <Head :title="`Edit ${project.name}`" />

    <h1 class="sr-only">Edit {{ project.name }}</h1>

    <div
        class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4"
    >
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
                                Est. Completion
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
                    <CardTitle>Site Address &amp; Location</CardTitle>
                </CardHeader>
                <CardContent class="space-y-4">
                    <div class="space-y-2">
                        <Label for="address">Street Address</Label>
                        <Input
                            id="address"
                            v-model="form.address"
                            placeholder="e.g. 1200 Market Street"
                        />
                        <p
                            v-if="form.errors.address"
                            class="text-sm text-destructive"
                        >
                            {{ form.errors.address }}
                        </p>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-3">
                        <div class="space-y-2">
                            <Label for="city">City</Label>
                            <Input
                                id="city"
                                v-model="form.city"
                                placeholder="e.g. San Francisco"
                            />
                            <p
                                v-if="form.errors.city"
                                class="text-sm text-destructive"
                            >
                                {{ form.errors.city }}
                            </p>
                        </div>

                        <div class="space-y-2">
                            <Label for="state">State</Label>
                            <Input
                                id="state"
                                v-model="form.state"
                                placeholder="e.g. CA"
                            />
                            <p
                                v-if="form.errors.state"
                                class="text-sm text-destructive"
                            >
                                {{ form.errors.state }}
                            </p>
                        </div>

                        <div class="space-y-2">
                            <Label for="postal_code">Postal Code</Label>
                            <Input
                                id="postal_code"
                                v-model="form.postal_code"
                                placeholder="e.g. 94103"
                            />
                            <p
                                v-if="form.errors.postal_code"
                                class="text-sm text-destructive"
                            >
                                {{ form.errors.postal_code }}
                            </p>
                        </div>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="space-y-2">
                            <Label for="latitude">Latitude</Label>
                            <Input
                                id="latitude"
                                v-model="form.latitude"
                                inputmode="decimal"
                                placeholder="e.g. 37.7863"
                            />
                            <p
                                v-if="form.errors.latitude"
                                class="text-sm text-destructive"
                            >
                                {{ form.errors.latitude }}
                            </p>
                        </div>

                        <div class="space-y-2">
                            <Label for="longitude">Longitude</Label>
                            <Input
                                id="longitude"
                                v-model="form.longitude"
                                inputmode="decimal"
                                placeholder="e.g. -122.4039"
                            />
                            <p
                                v-if="form.errors.longitude"
                                class="text-sm text-destructive"
                            >
                                {{ form.errors.longitude }}
                            </p>
                        </div>
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
