<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowLeft, CheckCircle2, Pencil, Truck } from '@lucide/vue';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

const props = defineProps<{
    project: { id: number; name: string };
    equipment: { id: number; title: string };
    purchaseOrder: {
        id: number;
        po_number: string;
        cost: number;
        issued_date: string | null;
        expected_delivery_date: string | null;
        actual_delivery_date: string | null;
        status: string;
        supplier_quotation: { id: number; supplier_name: string; quoted_cost: number } | null;
    };
}>();

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

const markDelivered = () => {
    router.post(
        `/projects/${props.project.id}/equipment/${props.equipment.id}/purchase-orders/${props.purchaseOrder.id}/deliver`,
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
    <Head :title="`${purchaseOrder.po_number} - Purchase Order`" />

    <h1 class="sr-only">{{ purchaseOrder.po_number }}</h1>

    <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <Button variant="ghost" size="sm" as-child>
                    <Link :href="`/projects/${project.id}/equipment/${equipment.id}/purchase-orders`">
                        <ArrowLeft class="mr-1 h-4 w-4" />
                        Back
                    </Link>
                </Button>
                <Heading
                    variant="small"
                    :title="purchaseOrder.po_number"
                    :description="`${equipment.title} · ${project.name}`"
                />
            </div>

            <div class="flex items-center gap-2">
                <Button
                    v-if="purchaseOrder.status !== 'delivered'"
                    variant="default"
                    size="sm"
                    @click="markDelivered"
                >
                    <CheckCircle2 class="mr-1 h-4 w-4" />
                    Mark Delivered
                </Button>
                <Button variant="outline" size="sm" as-child>
                    <Link
                        :href="`/projects/${project.id}/equipment/${equipment.id}/purchase-orders/${purchaseOrder.id}/edit`"
                    >
                        <Pencil class="mr-1 h-4 w-4" />
                        Edit
                    </Link>
                </Button>
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-2">
            <Card>
                <CardHeader>
                    <CardTitle>Order Details</CardTitle>
                </CardHeader>
                <CardContent>
                    <dl class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <dt class="text-sm text-muted-foreground">Status</dt>
                            <dd class="mt-1">
                                <Badge :variant="statusVariant(purchaseOrder.status)">
                                    {{ statusLabel(purchaseOrder.status) }}
                                </Badge>
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm text-muted-foreground">Cost</dt>
                            <dd class="mt-1 text-sm font-medium">
                                {{ formatCurrency(purchaseOrder.cost) }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm text-muted-foreground">Issued Date</dt>
                            <dd class="mt-1 text-sm">{{ purchaseOrder.issued_date ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm text-muted-foreground">Expected Delivery</dt>
                            <dd class="mt-1 text-sm">{{ purchaseOrder.expected_delivery_date ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm text-muted-foreground">Actual Delivery</dt>
                            <dd class="mt-1 text-sm">{{ purchaseOrder.actual_delivery_date ?? '—' }}</dd>
                        </div>
                    </dl>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Supplier</CardTitle>
                </CardHeader>
                <CardContent>
                    <template v-if="purchaseOrder.supplier_quotation">
                        <p class="text-sm font-medium">
                            {{ purchaseOrder.supplier_quotation.supplier_name }}
                        </p>
                        <p class="mt-1 text-sm text-muted-foreground">
                            Quoted
                            {{ formatCurrency(purchaseOrder.supplier_quotation.quoted_cost) }}
                        </p>
                    </template>
                    <p v-else class="flex items-center gap-2 text-sm text-muted-foreground">
                        <Truck class="h-4 w-4" />
                        No linked quotation
                    </p>
                </CardContent>
            </Card>
        </div>
    </div>
</template>
