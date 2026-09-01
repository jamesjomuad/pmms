<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft } from '@lucide/vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

const props = defineProps<{
    project: { id: number; name: string };
    equipment: { id: number; title: string };
}>();

const form = useForm({
    supplier_name: '',
    quoted_cost: '',
    quoted_lead_time_days: '',
    quote_date: '',
});

const submit = () => {
    form.post(`/projects/${props.project.id}/equipment/${props.equipment.id}/quotations`);
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
    <Head :title="`Add Quotation - ${equipment.title}`" />

    <h1 class="sr-only">Add Supplier Quotation</h1>

    <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4">
        <div class="flex items-center gap-4">
            <Button variant="ghost" size="sm" as-child>
                <Link :href="`/projects/${project.id}/equipment/${equipment.id}/quotations`">
                    <ArrowLeft class="mr-1 h-4 w-4" />
                    Back
                </Link>
            </Button>
            <Heading
                variant="small"
                title="Add Supplier Quotation"
                :description="`For ${equipment.title}`"
            />
        </div>

        <Card class="max-w-2xl">
            <CardHeader>
                <CardTitle>Quotation Details</CardTitle>
            </CardHeader>
            <CardContent>
                <form @submit.prevent="submit" class="space-y-6">
                    <div class="space-y-2">
                        <Label for="supplier_name">Supplier</Label>
                        <Input
                            id="supplier_name"
                            v-model="form.supplier_name"
                            placeholder="Supplier name"
                            :class="{ 'border-destructive': form.errors.supplier_name }"
                        />
                        <p v-if="form.errors.supplier_name" class="text-sm text-destructive">
                            {{ form.errors.supplier_name }}
                        </p>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="space-y-2">
                            <Label for="quoted_cost">Quoted Cost ($)</Label>
                            <Input
                                id="quoted_cost"
                                v-model="form.quoted_cost"
                                type="number"
                                step="0.01"
                                min="0"
                                placeholder="0.00"
                                :class="{ 'border-destructive': form.errors.quoted_cost }"
                            />
                            <p v-if="form.errors.quoted_cost" class="text-sm text-destructive">
                                {{ form.errors.quoted_cost }}
                            </p>
                        </div>

                        <div class="space-y-2">
                            <Label for="quoted_lead_time_days">Lead Time (days)</Label>
                            <Input
                                id="quoted_lead_time_days"
                                v-model="form.quoted_lead_time_days"
                                type="number"
                                min="1"
                                placeholder="e.g. 30"
                                :class="{ 'border-destructive': form.errors.quoted_lead_time_days }"
                            />
                            <p v-if="form.errors.quoted_lead_time_days" class="text-sm text-destructive">
                                {{ form.errors.quoted_lead_time_days }}
                            </p>
                        </div>
                    </div>

                    <div class="space-y-2">
                        <Label for="quote_date">Quote Date</Label>
                        <Input
                            id="quote_date"
                            v-model="form.quote_date"
                            type="date"
                            :class="{ 'border-destructive': form.errors.quote_date }"
                        />
                        <p v-if="form.errors.quote_date" class="text-sm text-destructive">
                            {{ form.errors.quote_date }}
                        </p>
                    </div>

                    <div class="flex justify-end gap-2">
                        <Button variant="outline" type="button" as-child>
                            <Link :href="`/projects/${project.id}/equipment/${equipment.id}/quotations`">
                                Cancel
                            </Link>
                        </Button>
                        <Button type="submit" :disabled="form.processing">
                            Add Quotation
                        </Button>
                    </div>
                </form>
            </CardContent>
        </Card>
    </div>
</template>
