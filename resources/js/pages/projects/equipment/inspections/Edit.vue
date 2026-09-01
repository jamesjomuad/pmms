<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft } from '@lucide/vue';
import type { User } from '@/types';
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

const props = defineProps<{
    project: { id: number; name: string };
    equipment: { id: number; title: string };
    users: User[];
    inspection: {
        id: number;
        inspected_by: number | null;
        inspected_date: string | null;
        result: string;
        notes: string | null;
    };
}>();

const form = useForm({
    inspected_by: props.inspection.inspected_by,
    inspected_date: props.inspection.inspected_date ?? '',
    result: props.inspection.result,
    notes: props.inspection.notes ?? '',
});

const submit = () => {
    form.patch(
        `/projects/${props.project.id}/equipment/${props.equipment.id}/inspections/${props.inspection.id}`,
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
    <Head :title="`Edit Inspection - ${equipment.title}`" />

    <h1 class="sr-only">Edit Equipment Inspection</h1>

    <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4">
        <div class="flex items-center gap-4">
            <Button variant="ghost" size="sm" as-child>
                <Link :href="`/projects/${project.id}/equipment/${equipment.id}/inspections/${inspection.id}`">
                    <ArrowLeft class="mr-1 h-4 w-4" />
                    Back
                </Link>
            </Button>
            <Heading
                variant="small"
                title="Edit Inspection"
                :description="`For ${equipment.title}`"
            />
        </div>

        <Card class="max-w-2xl">
            <CardHeader>
                <CardTitle>Inspection Details</CardTitle>
            </CardHeader>
            <CardContent>
                <form @submit.prevent="submit" class="space-y-6">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="space-y-2">
                            <Label>Inspector</Label>
                            <Select v-model="form.inspected_by">
                                <SelectTrigger>
                                    <SelectValue placeholder="Select team member" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem v-for="user in users" :key="user.id" :value="user.id">
                                        {{ user.name }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <p v-if="form.errors.inspected_by" class="text-sm text-destructive">
                                {{ form.errors.inspected_by }}
                            </p>
                        </div>

                        <div class="space-y-2">
                            <Label for="inspected_date">Inspection Date</Label>
                            <Input
                                id="inspected_date"
                                v-model="form.inspected_date"
                                type="date"
                                :class="{ 'border-destructive': form.errors.inspected_date }"
                            />
                            <p v-if="form.errors.inspected_date" class="text-sm text-destructive">
                                {{ form.errors.inspected_date }}
                            </p>
                        </div>
                    </div>

                    <div class="space-y-2">
                        <Label>Result</Label>
                        <Select v-model="form.result">
                            <SelectTrigger>
                                <SelectValue placeholder="Select result" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="passed">Passed</SelectItem>
                                <SelectItem value="failed">Failed</SelectItem>
                                <SelectItem value="damaged">Damaged</SelectItem>
                                <SelectItem value="wrong_item">Wrong Item</SelectItem>
                            </SelectContent>
                        </Select>
                        <p v-if="form.errors.result" class="text-sm text-destructive">
                            {{ form.errors.result }}
                        </p>
                    </div>

                    <div class="space-y-2">
                        <Label for="notes">Notes</Label>
                        <Textarea
                            id="notes"
                            v-model="form.notes"
                            placeholder="Inspection notes..."
                            :class="{ 'border-destructive': form.errors.notes }"
                        />
                        <p v-if="form.errors.notes" class="text-sm text-destructive">
                            {{ form.errors.notes }}
                        </p>
                    </div>

                    <div class="flex justify-end gap-2">
                        <Button variant="outline" type="button" as-child>
                            <Link :href="`/projects/${project.id}/equipment/${equipment.id}/inspections/${inspection.id}`">
                                Cancel
                            </Link>
                        </Button>
                        <Button type="submit" :disabled="form.processing">
                            Save Changes
                        </Button>
                    </div>
                </form>
            </CardContent>
        </Card>
    </div>
</template>
