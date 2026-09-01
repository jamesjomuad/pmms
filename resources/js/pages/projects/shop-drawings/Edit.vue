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
    shopDrawing: {
        id: number;
        title: string;
        description: string | null;
        drawing_number: string | null;
        revision_number: number;
        status: string;
        due_date: string | null;
        assigned_to: number | null;
    };
    users: User[];
}>();

const form = useForm({
    title: props.shopDrawing.title,
    description: props.shopDrawing.description ?? '',
    drawing_number: props.shopDrawing.drawing_number ?? '',
    assigned_to: props.shopDrawing.assigned_to,
    due_date: props.shopDrawing.due_date ?? '',
});

const submit = () => {
    form.patch(`/projects/${props.project.id}/shop-drawings/${props.shopDrawing.id}`);
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
    <Head :title="`Edit ${shopDrawing.title} - Shop Drawing`" />

    <h1 class="sr-only">Edit Shop Drawing</h1>

    <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4">
        <div class="flex items-center gap-4">
            <Button variant="ghost" size="sm" as-child>
                <Link :href="`/projects/${project.id}/shop-drawings/${shopDrawing.id}`">
                    <ArrowLeft class="mr-1 h-4 w-4" />
                    Back
                </Link>
            </Button>
            <Heading
                variant="small"
                :title="`Edit ${shopDrawing.title}`"
                :description="`${shopDrawing.drawing_number ? shopDrawing.drawing_number + ' · ' : ''}Rev. ${shopDrawing.revision_number} · ${project.name}`"
            />
        </div>

        <Card class="max-w-2xl">
            <CardHeader>
                <CardTitle>Drawing Details</CardTitle>
            </CardHeader>
            <CardContent>
                <form @submit.prevent="submit" class="space-y-6">
                    <div class="space-y-2">
                        <Label for="title">Title</Label>
                        <Input
                            id="title"
                            v-model="form.title"
                            placeholder="Shop drawing title"
                            :class="{ 'border-destructive': form.errors.title }"
                        />
                        <p v-if="form.errors.title" class="text-sm text-destructive">
                            {{ form.errors.title }}
                        </p>
                    </div>

                    <div class="space-y-2">
                        <Label for="description">Description</Label>
                        <Textarea
                            id="description"
                            v-model="form.description"
                            placeholder="Describe the shop drawing..."
                            :class="{ 'border-destructive': form.errors.description }"
                        />
                        <p v-if="form.errors.description" class="text-sm text-destructive">
                            {{ form.errors.description }}
                        </p>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="space-y-2">
                            <Label for="drawing_number">Drawing Number</Label>
                            <Input
                                id="drawing_number"
                                v-model="form.drawing_number"
                                placeholder="e.g. SD-001"
                                :class="{ 'border-destructive': form.errors.drawing_number }"
                            />
                            <p v-if="form.errors.drawing_number" class="text-sm text-destructive">
                                {{ form.errors.drawing_number }}
                            </p>
                        </div>

                        <div class="space-y-2">
                            <Label for="due_date">Due Date</Label>
                            <Input
                                id="due_date"
                                v-model="form.due_date"
                                type="date"
                                :class="{ 'border-destructive': form.errors.due_date }"
                            />
                            <p v-if="form.errors.due_date" class="text-sm text-destructive">
                                {{ form.errors.due_date }}
                            </p>
                        </div>
                    </div>

                    <div class="space-y-2">
                        <Label>Assigned To</Label>
                        <Select v-model="form.assigned_to">
                            <SelectTrigger>
                                <SelectValue placeholder="Select team member" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem v-for="user in users" :key="user.id" :value="user.id">
                                    {{ user.name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <p v-if="form.errors.assigned_to" class="text-sm text-destructive">
                            {{ form.errors.assigned_to }}
                        </p>
                    </div>

                    <div class="flex justify-end gap-2">
                        <Button variant="outline" type="button" as-child>
                            <Link :href="`/projects/${project.id}/shop-drawings/${shopDrawing.id}`">
                                Cancel
                            </Link>
                        </Button>
                        <Button type="submit" :disabled="form.processing">
                            Update Shop Drawing
                        </Button>
                    </div>
                </form>
            </CardContent>
        </Card>
    </div>
</template>
