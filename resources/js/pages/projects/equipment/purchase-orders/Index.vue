<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, ClipboardList, Plus } from '@lucide/vue';
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
import { create as poCreate } from '@/routes/projects/equipment/purchase-orders';

const props = defineProps<{
    project: { id: number; name: string };
    equipment: { id: number; title: string };
    purchaseOrders: {
        id: number;
        po_number: string;
        cost: number;
        issued_date: string | null;
        expected_delivery_date: string | null;
        status: string;
        supplier_quotation: { id: number; supplier_name: string } | null;
    }[];
}>();

const createUrl = poCreate({
    project: props.project.id,
    equipment: props.equipment.id,
}).url;

const statusVariant = (status: string) => {
    switch (status) {
        case 'delivered':
            return 'default';
        case 'issued':
            return 'secondary';
        case 'shipped':
            return 'secondary';
        case 'in_manufacturing':
            return 'outline';
        case 'acknowledged':
            return 'outline';
        default:
            return 'secondary';
    }
};

const statusLabel = (status: string) => {
    const labels: Record<string, string> = {
        issued: 'Issued',
        acknowledged: 'Acknowledged',
        in_manufacturing: 'In Manufacturing',
        shipped: 'Shipped',
        delivered: 'Delivered',
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
    <Head :title="`Purchase Orders - ${equipment.title}`" />

    <h1 class="sr-only">Purchase Orders</h1>

    <div
        class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4"
    >
        <div>
            <Button variant="ghost" size="sm" as-child>
                <Link
                    :href="`/projects/${project.id}/equipment/${equipment.id}`"
                >
                    <ArrowLeft class="mr-1 h-4 w-4" />
                    Equipment
                </Link>
            </Button>
        </div>

        <div class="flex items-center justify-between">
            <Heading
                variant="small"
                title="Purchase Orders"
                :description="`Track orders for ${equipment.title}`"
            />

            <Button as-child>
                <Link :href="createUrl">
                    <Plus class="mr-1 h-4 w-4" />
                    Issue PO
                </Link>
            </Button>
        </div>

        <div v-if="purchaseOrders.length > 0" class="rounded-lg border">
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>PO Number</TableHead>
                        <TableHead>Supplier</TableHead>
                        <TableHead>Cost</TableHead>
                        <TableHead>Issued</TableHead>
                        <TableHead>Expected</TableHead>
                        <TableHead>Status</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableRow v-for="po in purchaseOrders" :key="po.id">
                        <TableCell>
                            <Link
                                :href="`/projects/${project.id}/equipment/${equipment.id}/purchase-orders/${po.id}`"
                                class="font-medium text-foreground hover:underline"
                            >
                                {{ po.po_number }}
                            </Link>
                        </TableCell>
                        <TableCell class="text-muted-foreground">
                            {{ po.supplier_quotation?.supplier_name ?? '—' }}
                        </TableCell>
                        <TableCell class="text-muted-foreground">
                            {{ formatCurrency(po.cost) }}
                        </TableCell>
                        <TableCell class="text-muted-foreground">
                            {{ po.issued_date ?? '—' }}
                        </TableCell>
                        <TableCell class="text-muted-foreground">
                            {{ po.expected_delivery_date ?? '—' }}
                        </TableCell>
                        <TableCell>
                            <Badge :variant="statusVariant(po.status)">
                                {{ statusLabel(po.status) }}
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
            <div
                class="mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-muted"
            >
                <ClipboardList class="h-6 w-6 text-muted-foreground/60" />
            </div>
            <h3 class="mb-1 text-sm font-medium">No purchase orders yet</h3>
            <p class="mb-6 max-w-sm text-sm text-muted-foreground">
                Issue a purchase order once a supplier quotation has been
                selected.
            </p>
            <Button as-child>
                <Link :href="createUrl">
                    <Plus class="mr-1 h-4 w-4" />
                    Issue PO
                </Link>
            </Button>
        </div>
    </div>
</template>
