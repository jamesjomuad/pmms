<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, Files, Plus } from '@lucide/vue';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { create as quotationCreate } from '@/routes/projects/equipment/quotations';

const props = defineProps<{
    project: { id: number; name: string };
    equipment: { id: number; title: string };
    quotations: {
        id: number;
        supplier_name: string;
        quoted_cost: number;
        quoted_lead_time_days: number | null;
        quote_date: string | null;
        status: string;
        created_at: string;
    }[];
}>();

const createUrl = quotationCreate({
    project: props.project.id,
    equipment: props.equipment.id,
}).url;

const statusVariant = (status: string) => {
    switch (status) {
        case 'selected':
            return 'default';
        case 'received':
            return 'secondary';
        case 'declined':
            return 'outline';
        default:
            return 'secondary';
    }
};

const statusLabel = (status: string) => {
    const labels: Record<string, string> = {
        received: 'Received',
        selected: 'Selected',
        declined: 'Declined',
    };

    return labels[status] ?? status;
};

const formatCurrency = (value: number) =>
    new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
    }).format(value);

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
    <Head :title="`Quotations - ${equipment.title}`" />

    <h1 class="sr-only">Supplier Quotations</h1>

    <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4">
        <div>
            <Button variant="ghost" size="sm" as-child>
                <Link :href="`/projects/${project.id}/equipment/${equipment.id}`">
                    <ArrowLeft class="mr-1 h-4 w-4" />
                    Equipment
                </Link>
            </Button>
        </div>

        <div class="flex items-center justify-between">
            <Heading
                variant="small"
                title="Supplier Quotations"
                :description="`Compare quotes for ${equipment.title}`"
            />

            <Button as-child>
                <Link :href="createUrl">
                    <Plus class="mr-1 h-4 w-4" />
                    Add quotation
                </Link>
            </Button>
        </div>

        <div v-if="quotations.length > 0" class="rounded-lg border">
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>Supplier</TableHead>
                        <TableHead>Quoted Cost</TableHead>
                        <TableHead>Lead Time</TableHead>
                        <TableHead>Quote Date</TableHead>
                        <TableHead>Status</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableRow v-for="quote in quotations" :key="quote.id">
                        <TableCell>
                            <Link
                                :href="`/projects/${project.id}/equipment/${equipment.id}/quotations/${quote.id}`"
                                class="font-medium text-foreground hover:underline"
                            >
                                {{ quote.supplier_name }}
                            </Link>
                        </TableCell>
                        <TableCell class="text-muted-foreground">
                            {{ formatCurrency(quote.quoted_cost) }}
                        </TableCell>
                        <TableCell class="text-muted-foreground">
                            {{ quote.quoted_lead_time_days ? `${quote.quoted_lead_time_days} days` : '—' }}
                        </TableCell>
                        <TableCell class="text-muted-foreground">
                            {{ quote.quote_date ?? '—' }}
                        </TableCell>
                        <TableCell>
                            <Badge :variant="statusVariant(quote.status)">
                                {{ statusLabel(quote.status) }}
                            </Badge>
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </div>

        <div
            v-else
            class="flex flex-col items-center justify-center rounded-lg border border-dashed px-6 py-16 text-center"
        >
            <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-muted">
                <Files class="h-6 w-6 text-muted-foreground/60" />
            </div>
            <h3 class="mb-1 text-sm font-medium">No quotations yet</h3>
            <p class="mb-6 max-w-sm text-sm text-muted-foreground">
                Add supplier quotations to compare and select the best option.
            </p>
            <Button as-child>
                <Link :href="createUrl">
                    <Plus class="mr-1 h-4 w-4" />
                    Add quotation
                </Link>
            </Button>
        </div>
    </div>
</template>
