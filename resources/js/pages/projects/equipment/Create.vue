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
    description: '',
    manufacturer: '',
    model_number: '',
    serial_number: '',
    cost: '',
    quantity: 1,
    assigned_to: null as number | null,
    lead_time: '',
    expected_delivery: '',
});

const submit = () => {
    form.post(`/projects/${props.project.id}/equipment`);
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
    <Head :title="`Add Equipment - ${project.name}`" />

    <h1 class="sr-only">Add Equipment</h1>

    <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4">
        <div class="flex items-center gap-4">
            <Button variant="ghost" size="sm" as-child>
                <Link :href="`/projects/${project.id}/equipment`">
                    <ArrowLeft class="mr-1 h-4 w-4" />
                    Back
                </Link>
            </Button>
            <Heading
                variant="small"
                title="Add Equipment"
                :description="`For ${project.name}`"
            />
        </div>

        <Card class="max-w-2xl">
            <CardHeader>
                <CardTitle>Equipment Details</CardTitle>
            </CardHeader>
            <CardContent>
                <form @submit.prevent="submit" class="space-y-6">
                    <div class="space-y-2">
                        <Label for="title">Title</Label>
                        <Input
                            id="title"
                            v-model="form.title"
                            placeholder="Equipment name"
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
                            placeholder="Describe the equipment..."
                            :class="{ 'border-destructive': form.errors.description }"
                        />
                        <p v-if="form.errors.description" class="text-sm text-destructive">
                            {{ form.errors.description }}
                        </p>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="space-y-2">
                            <Label for="manufacturer">Manufacturer</Label>
                            <Input
                                id="manufacturer"
                                v-model="form.manufacturer"
                                placeholder="e.g. Trane"
                                :class="{ 'border-destructive': form.errors.manufacturer }"
                            />
                            <p v-if="form.errors.manufacturer" class="text-sm text-destructive">
                                {{ form.errors.manufacturer }}
                            </p>
                        </div>

                        <div class="space-y-2">
                            <Label for="model_number">Model Number</Label>
                            <Input
                                id="model_number"
                                v-model="form.model_number"
                                placeholder="e.g. MDL-001"
                                :class="{ 'border-destructive': form.errors.model_number }"
                            />
                            <p v-if="form.errors.model_number" class="text-sm text-destructive">
                                {{ form.errors.model_number }}
                            </p>
                        </div>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="space-y-2">
                            <Label for="serial_number">Serial Number</Label>
                            <Input
                                id="serial_number"
                                v-model="form.serial_number"
                                placeholder="e.g. SN-001"
                                :class="{ 'border-destructive': form.errors.serial_number }"
                            />
                            <p v-if="form.errors.serial_number" class="text-sm text-destructive">
                                {{ form.errors.serial_number }}
                            </p>
                        </div>

                        <div class="space-y-2">
                            <Label for="cost">Unit Cost ($)</Label>
                            <Input
                                id="cost"
                                v-model="form.cost"
                                type="number"
                                step="0.01"
                                min="0"
                                placeholder="0.00"
                                :class="{ 'border-destructive': form.errors.cost }"
                            />
                            <p v-if="form.errors.cost" class="text-sm text-destructive">
                                {{ form.errors.cost }}
                            </p>
                        </div>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="space-y-2">
                            <Label for="quantity">Quantity</Label>
                            <Input
                                id="quantity"
                                v-model.number="form.quantity"
                                type="number"
                                min="1"
                                :class="{ 'border-destructive': form.errors.quantity }"
                            />
                            <p v-if="form.errors.quantity" class="text-sm text-destructive">
                                {{ form.errors.quantity }}
                            </p>
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
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="space-y-2">
                            <Label for="lead_time">Lead Time</Label>
                            <Input
                                id="lead_time"
                                v-model="form.lead_time"
                                type="date"
                                :class="{ 'border-destructive': form.errors.lead_time }"
                            />
                            <p v-if="form.errors.lead_time" class="text-sm text-destructive">
                                {{ form.errors.lead_time }}
                            </p>
                        </div>

                        <div class="space-y-2">
                            <Label for="expected_delivery">Expected Delivery</Label>
                            <Input
                                id="expected_delivery"
                                v-model="form.expected_delivery"
                                type="date"
                                :class="{ 'border-destructive': form.errors.expected_delivery }"
                            />
                            <p v-if="form.errors.expected_delivery" class="text-sm text-destructive">
                                {{ form.errors.expected_delivery }}
                            </p>
                        </div>
                    </div>

                    <div class="flex justify-end gap-2">
                        <Button variant="outline" type="button" as-child>
                            <Link :href="`/projects/${project.id}/equipment`">
                                Cancel
                            </Link>
                        </Button>
                        <Button type="submit" :disabled="form.processing">
                            Add Equipment
                        </Button>
                    </div>
                </form>
            </CardContent>
        </Card>
    </div>
</template>
