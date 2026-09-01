<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft } from '@lucide/vue';
import type { AcceptableValue } from 'reka-ui';
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

const props = defineProps<{
    project: { id: number; name: string };
    equipment: { id: number; title: string };
    quotations: { id: number; supplier_name: string; quoted_cost: number; status: string }[];
    selectedQuotation: {
        id: number;
        supplier_name: string;
        quoted_cost: number;
        status: string;
    } | null;
}>();

const form = useForm({
    supplier_quotation_id: props.selectedQuotation
        ? String(props.selectedQuotation.id)
        : ('' as string),
    po_number: '',
    issued_date: '',
    cost: props.selectedQuotation
        ? String(props.selectedQuotation.quoted_cost)
        : ('' as string),
    expected_delivery_date: '',
    actual_delivery_date: '',
});

const selectableQuotations = props.quotations.filter((q) => q.status !== 'declined');

const onQuotationChange = (value: AcceptableValue) => {
    if (!value) {
        return;
    }

    const quotation = props.quotations.find((q) => String(q.id) === String(value));
    if (quotation) {
        form.cost = String(quotation.quoted_cost);
    }
};

const submit = () => {
    form.post(
        `/projects/${props.project.id}/equipment/${props.equipment.id}/purchase-orders`,
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
    <Head :title="`Issue PO - ${equipment.title}`" />

    <h1 class="sr-only">Issue Purchase Order</h1>

    <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4">
        <div class="flex items-center gap-4">
            <Button variant="ghost" size="sm" as-child>
                <Link :href="`/projects/${project.id}/equipment/${equipment.id}/purchase-orders`">
                    <ArrowLeft class="mr-1 h-4 w-4" />
                    Back
                </Link>
            </Button>
            <Heading
                variant="small"
                title="Issue Purchase Order"
                :description="`For ${equipment.title}`"
            />
        </div>

        <Card class="max-w-2xl">
            <CardHeader>
                <CardTitle>Purchase Order Details</CardTitle>
            </CardHeader>
            <CardContent>
                <form @submit.prevent="submit" class="space-y-6">
                    <div class="space-y-2">
                        <Label>Supplier Quotation</Label>
                        <Select
                            v-model="form.supplier_quotation_id"
                            @update:model-value="onQuotationChange"
                        >
                            <SelectTrigger>
                                <SelectValue placeholder="Select quotation (optional)" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem v-for="q in selectableQuotations" :key="q.id" :value="String(q.id)">
                                    {{ q.supplier_name }} —
                                    {{ new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(q.quoted_cost) }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <p v-if="form.errors.supplier_quotation_id" class="text-sm text-destructive">
                            {{ form.errors.supplier_quotation_id }}
                        </p>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="space-y-2">
                            <Label for="po_number">PO Number</Label>
                            <Input
                                id="po_number"
                                v-model="form.po_number"
                                placeholder="e.g. PO-0001"
                                :class="{ 'border-destructive': form.errors.po_number }"
                            />
                            <p v-if="form.errors.po_number" class="text-sm text-destructive">
                                {{ form.errors.po_number }}
                            </p>
                        </div>

                        <div class="space-y-2">
                            <Label for="cost">Cost ($)</Label>
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
                            <Label for="issued_date">Issued Date</Label>
                            <Input
                                id="issued_date"
                                v-model="form.issued_date"
                                type="date"
                                :class="{ 'border-destructive': form.errors.issued_date }"
                            />
                            <p v-if="form.errors.issued_date" class="text-sm text-destructive">
                                {{ form.errors.issued_date }}
                            </p>
                        </div>

                        <div class="space-y-2">
                            <Label for="expected_delivery_date">Expected Delivery</Label>
                            <Input
                                id="expected_delivery_date"
                                v-model="form.expected_delivery_date"
                                type="date"
                                :class="{ 'border-destructive': form.errors.expected_delivery_date }"
                            />
                            <p v-if="form.errors.expected_delivery_date" class="text-sm text-destructive">
                                {{ form.errors.expected_delivery_date }}
                            </p>
                        </div>
                    </div>

                    <div class="flex justify-end gap-2">
                        <Button variant="outline" type="button" as-child>
                            <Link :href="`/projects/${project.id}/equipment/${equipment.id}/purchase-orders`">
                                Cancel
                            </Link>
                        </Button>
                        <Button type="submit" :disabled="form.processing">
                            Issue PO
                        </Button>
                    </div>
                </form>
            </CardContent>
        </Card>
    </div>
</template>
