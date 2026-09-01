<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft } from '@lucide/vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';

const props = defineProps<{
    project: { id: number; name: string };
    changeOrder: {
        id: number;
        title: string;
        description: string | null;
        cost_impact: number;
        schedule_impact_days: number;
        status: string;
    };
}>();

const form = useForm({
    title: props.changeOrder.title,
    description: props.changeOrder.description ?? '',
    cost_impact: props.changeOrder.cost_impact,
    schedule_impact_days: props.changeOrder.schedule_impact_days,
});

const submit = () => {
    form.patch(`/projects/${props.project.id}/change-orders/${props.changeOrder.id}`);
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
    <Head :title="`Edit ${changeOrder.title} - Change Order`" />

    <h1 class="sr-only">Edit Change Order</h1>

    <div
        class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4"
    >
        <div class="flex items-center gap-4">
            <Button variant="ghost" size="sm" as-child>
                <Link :href="`/projects/${project.id}/change-orders/${changeOrder.id}`">
                    <ArrowLeft class="mr-1 h-4 w-4" />
                    Back
                </Link>
            </Button>
            <Heading
                variant="small"
                :title="`Edit ${changeOrder.title}`"
                :description="project.name"
            />
        </div>

        <Card class="max-w-2xl">
            <CardHeader>
                <CardTitle>Change Order Details</CardTitle>
            </CardHeader>
            <CardContent>
                <form @submit.prevent="submit" class="space-y-6">
                    <div class="space-y-2">
                        <Label for="title">Title</Label>
                        <Input
                            id="title"
                            v-model="form.title"
                            placeholder="Change order title"
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
                            placeholder="Describe the change order..."
                            :class="{ 'border-destructive': form.errors.description }"
                        />
                        <p v-if="form.errors.description" class="text-sm text-destructive">
                            {{ form.errors.description }}
                        </p>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="space-y-2">
                            <Label for="cost_impact">Cost Impact</Label>
                            <Input
                                id="cost_impact"
                                v-model.number="form.cost_impact"
                                type="number"
                                min="0"
                                placeholder="0"
                                :class="{ 'border-destructive': form.errors.cost_impact }"
                            />
                            <p v-if="form.errors.cost_impact" class="text-sm text-destructive">
                                {{ form.errors.cost_impact }}
                            </p>
                        </div>

                        <div class="space-y-2">
                            <Label for="schedule_impact_days">Schedule Impact (Days)</Label>
                            <Input
                                id="schedule_impact_days"
                                v-model.number="form.schedule_impact_days"
                                type="number"
                                min="0"
                                placeholder="0"
                                :class="{ 'border-destructive': form.errors.schedule_impact_days }"
                            />
                            <p v-if="form.errors.schedule_impact_days" class="text-sm text-destructive">
                                {{ form.errors.schedule_impact_days }}
                            </p>
                        </div>
                    </div>

                    <div class="flex justify-end gap-2">
                        <Button variant="outline" type="button" as-child>
                            <Link :href="`/projects/${project.id}/change-orders/${changeOrder.id}`">
                                Cancel
                            </Link>
                        </Button>
                        <Button type="submit" :disabled="form.processing">
                            Update Change Order
                        </Button>
                    </div>
                </form>
            </CardContent>
        </Card>
    </div>
</template>
