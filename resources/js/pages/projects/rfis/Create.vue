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
    users: User[];
}>();

const form = useForm({
    title: '',
    question: '',
    assigned_to: null as number | null,
    due_date: '',
});

const submit = () => {
    form.post(`/projects/${props.project.id}/rfis`);
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
    <Head :title="`Create RFI - ${project.name}`" />

    <h1 class="sr-only">Create RFI</h1>

    <div
        class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4"
    >
        <div class="flex items-center gap-4">
            <Button variant="ghost" size="sm" as-child>
                <Link :href="`/projects/${project.id}/rfis`">
                    <ArrowLeft class="mr-1 h-4 w-4" />
                    Back
                </Link>
            </Button>
            <Heading
                variant="small"
                title="Create RFI"
                :description="`For ${project.name}`"
            />
        </div>

        <Card class="max-w-2xl">
            <CardHeader>
                <CardTitle>RFI Details</CardTitle>
            </CardHeader>
            <CardContent>
                <form @submit.prevent="submit" class="space-y-6">
                    <div class="space-y-2">
                        <Label for="title">Title</Label>
                        <Input
                            id="title"
                            v-model="form.title"
                            placeholder="RFI title"
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
                        <Label for="question">Question</Label>
                        <Textarea
                            id="question"
                            v-model="form.question"
                            placeholder="Describe your question..."
                            rows="5"
                            :class="{
                                'border-destructive': form.errors.question,
                            }"
                        />
                        <p
                            v-if="form.errors.question"
                            class="text-sm text-destructive"
                        >
                            {{ form.errors.question }}
                        </p>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
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
                    </div>

                    <div class="flex justify-end gap-2">
                        <Button variant="outline" type="button" as-child>
                            <Link :href="`/projects/${project.id}/rfis`">
                                Cancel
                            </Link>
                        </Button>
                        <Button type="submit" :disabled="form.processing">
                            Create RFI
                        </Button>
                    </div>
                </form>
            </CardContent>
        </Card>
    </div>
</template>
