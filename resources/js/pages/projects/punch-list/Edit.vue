<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft } from '@lucide/vue';
import Heading from '@/components/Heading.vue';
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
import type { User } from '@/types';

const props = defineProps<{
    project: { id: number; name: string };
    punchListItem: {
        id: number;
        title: string;
        description: string | null;
        location: string | null;
        trade: string | null;
        priority: string;
        status: string;
        due_date: string | null;
        assigned_to: number | null;
    };
    users: User[];
}>();

const form = useForm({
    title: props.punchListItem.title,
    description: props.punchListItem.description ?? '',
    location: props.punchListItem.location ?? '',
    trade: props.punchListItem.trade ?? '',
    priority: props.punchListItem.priority,
    assigned_to: props.punchListItem.assigned_to,
    due_date: props.punchListItem.due_date ?? '',
});

const submit = () => {
    form.patch(
        `/projects/${props.project.id}/punch-list/${props.punchListItem.id}`,
    );
};

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
    <Head :title="`Edit ${punchListItem.title} - Punch List`" />

    <h1 class="sr-only">Edit Punch List Item</h1>

    <div
        class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4"
    >
        <div class="flex items-center gap-4">
            <Button variant="ghost" size="sm" as-child>
                <Link
                    :href="`/projects/${project.id}/punch-list/${punchListItem.id}`"
                >
                    <ArrowLeft class="mr-1 h-4 w-4" />
                    Back
                </Link>
            </Button>
            <Heading
                variant="small"
                :title="`Edit ${punchListItem.title}`"
                :description="project.name"
            />
        </div>

        <Card class="max-w-2xl">
            <CardHeader>
                <CardTitle>Item Details</CardTitle>
            </CardHeader>
            <CardContent>
                <form @submit.prevent="submit" class="space-y-6">
                    <div class="space-y-2">
                        <Label for="title">Title</Label>
                        <Input
                            id="title"
                            v-model="form.title"
                            placeholder="Describe the issue"
                            :class="{ 'border-destructive': form.errors.title }"
                        />
                        <p
                            v-if="form.errors.title"
                            class="text-sm text-destructive"
                        >
                            {{ form.errors.title }}
                        </p>
                    </div>

                    <div class="space-y-2">
                        <Label for="description">Description</Label>
                        <Textarea
                            id="description"
                            v-model="form.description"
                            placeholder="Additional details..."
                            :class="{
                                'border-destructive': form.errors.description,
                            }"
                        />
                        <p
                            v-if="form.errors.description"
                            class="text-sm text-destructive"
                        >
                            {{ form.errors.description }}
                        </p>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="space-y-2">
                            <Label for="location">Location</Label>
                            <Input
                                id="location"
                                v-model="form.location"
                                placeholder="e.g. Building A, Room 101"
                                :class="{
                                    'border-destructive': form.errors.location,
                                }"
                            />
                            <p
                                v-if="form.errors.location"
                                class="text-sm text-destructive"
                            >
                                {{ form.errors.location }}
                            </p>
                        </div>

                        <div class="space-y-2">
                            <Label for="trade">Trade</Label>
                            <Input
                                id="trade"
                                v-model="form.trade"
                                placeholder="e.g. Electrical"
                                :class="{
                                    'border-destructive': form.errors.trade,
                                }"
                            />
                            <p
                                v-if="form.errors.trade"
                                class="text-sm text-destructive"
                            >
                                {{ form.errors.trade }}
                            </p>
                        </div>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="space-y-2">
                            <Label>Priority</Label>
                            <Select v-model="form.priority">
                                <SelectTrigger>
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="low">Low</SelectItem>
                                    <SelectItem value="medium"
                                        >Medium</SelectItem
                                    >
                                    <SelectItem value="high">High</SelectItem>
                                    <SelectItem value="critical"
                                        >Critical</SelectItem
                                    >
                                </SelectContent>
                            </Select>
                            <p
                                v-if="form.errors.priority"
                                class="text-sm text-destructive"
                            >
                                {{ form.errors.priority }}
                            </p>
                        </div>

                        <div class="space-y-2">
                            <Label>Assigned To</Label>
                            <Select v-model="form.assigned_to">
                                <SelectTrigger>
                                    <SelectValue
                                        placeholder="Select team member"
                                    />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="user in users"
                                        :key="user.id"
                                        :value="user.id"
                                    >
                                        {{ user.name }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <p
                                v-if="form.errors.assigned_to"
                                class="text-sm text-destructive"
                            >
                                {{ form.errors.assigned_to }}
                            </p>
                        </div>
                    </div>

                    <div class="space-y-2">
                        <Label for="due_date">Due Date</Label>
                        <Input
                            id="due_date"
                            v-model="form.due_date"
                            type="date"
                            :class="{
                                'border-destructive': form.errors.due_date,
                            }"
                        />
                        <p
                            v-if="form.errors.due_date"
                            class="text-sm text-destructive"
                        >
                            {{ form.errors.due_date }}
                        </p>
                    </div>

                    <div class="flex justify-end gap-2">
                        <Button variant="outline" type="button" as-child>
                            <Link
                                :href="`/projects/${project.id}/punch-list/${punchListItem.id}`"
                            >
                                Cancel
                            </Link>
                        </Button>
                        <Button type="submit" :disabled="form.processing">
                            Update Item
                        </Button>
                    </div>
                </form>
            </CardContent>
        </Card>
    </div>
</template>
