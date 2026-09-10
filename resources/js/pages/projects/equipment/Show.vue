<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    ArrowLeft,
    CheckCircle2,
    ClipboardList,
    MessageSquare,
    Package,
    Paperclip,
    Pencil,
} from '@lucide/vue';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';

const props = defineProps<{
    project: { id: number; name: string };
    equipment: {
        id: number;
        title: string;
        description: string | null;
        manufacturer: string | null;
        model_number: string | null;
        serial_number: string | null;
        cost: number | null;
        quantity: number;
        total_cost: number | null;
        status: string;
        po_status: string;
        lead_time: string | null;
        expected_delivery: string | null;
        received_at: string | null;
        created_at: string;
        assignee: { id: number; name: string } | null;
        procurement: {
            quotation_count: number;
            purchase_order_count: number;
            inspection_count: number;
        };
        comments: {
            id: number;
            body: string;
            user: { id: number; name: string };
            created_at: string;
        }[];
        attachments: {
            id: number;
            name: string;
            human_size: string;
            url: string;
        }[];
    };
}>();

const statusVariant = (status: string) => {
    switch (status) {
        case 'approved':
            return 'default';
        case 'pending':
            return 'secondary';
        case 'rejected':
            return 'destructive';
        case 'draft':
            return 'outline';
        default:
            return 'secondary';
    }
};

const statusLabel = (status: string) => {
    const labels: Record<string, string> = {
        draft: 'Draft',
        pending: 'Pending',
        in_review: 'In Review',
        approved: 'Received',
        rejected: 'Rejected',
        cancelled: 'Cancelled',
    };

    return labels[status] ?? status;
};

const poStatusLabel = (status: string) => {
    const labels: Record<string, string> = {
        pending: 'Pending',
        ordered: 'Ordered',
        shipped: 'Shipped',
        delivered: 'Delivered',
        cancelled: 'Cancelled',
    };

    return labels[status] ?? status;
};

const formatCurrency = (value: number | null) => {
    if (value === null) {
        return '—';
    }

    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
    }).format(value);
};

const receiveItem = () => {
    router.post(
        `/projects/${props.project.id}/equipment/${props.equipment.id}/receive`,
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
    <Head :title="`${equipment.title} - Equipment`" />

    <h1 class="sr-only">{{ equipment.title }}</h1>

    <div
        class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4"
    >
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <Button variant="ghost" size="sm" as-child>
                    <Link :href="`/projects/${project.id}/equipment`">
                        <ArrowLeft class="mr-1 h-4 w-4" />
                        Back
                    </Link>
                </Button>
                <Heading
                    variant="small"
                    :title="equipment.title"
                    :description="`${equipment.manufacturer ?? 'Unknown'} · ${project.name}`"
                />
            </div>

            <div class="flex items-center gap-2">
                <Button
                    v-if="equipment.status === 'pending'"
                    variant="default"
                    size="sm"
                    @click="receiveItem"
                >
                    <CheckCircle2 class="mr-1 h-4 w-4" />
                    Mark as Received
                </Button>
                <Button variant="outline" size="sm" as-child>
                    <Link
                        :href="`/projects/${project.id}/equipment/${equipment.id}/edit`"
                    >
                        <Pencil class="mr-1 h-4 w-4" />
                        Edit
                    </Link>
                </Button>
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-3">
            <Card class="lg:col-span-2">
                <CardHeader>
                    <CardTitle>Equipment Details</CardTitle>
                </CardHeader>
                <CardContent>
                    <dl class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <dt class="text-sm text-muted-foreground">
                                Status
                            </dt>
                            <dd class="mt-1">
                                <Badge
                                    :variant="statusVariant(equipment.status)"
                                >
                                    {{ statusLabel(equipment.status) }}
                                </Badge>
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm text-muted-foreground">
                                PO Status
                            </dt>
                            <dd class="mt-1 text-sm">
                                {{ poStatusLabel(equipment.po_status) }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm text-muted-foreground">
                                Model Number
                            </dt>
                            <dd class="mt-1 text-sm">
                                {{ equipment.model_number ?? '—' }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm text-muted-foreground">
                                Serial Number
                            </dt>
                            <dd class="mt-1 text-sm">
                                {{ equipment.serial_number ?? '—' }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm text-muted-foreground">
                                Unit Cost
                            </dt>
                            <dd class="mt-1 text-sm">
                                {{ formatCurrency(equipment.cost) }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm text-muted-foreground">
                                Quantity
                            </dt>
                            <dd class="mt-1 text-sm">
                                {{ equipment.quantity }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm text-muted-foreground">
                                Total Cost
                            </dt>
                            <dd class="mt-1 text-sm font-medium">
                                {{ formatCurrency(equipment.total_cost) }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm text-muted-foreground">
                                Assigned To
                            </dt>
                            <dd class="mt-1 text-sm">
                                {{ equipment.assignee?.name ?? '—' }}
                            </dd>
                        </div>
                        <div v-if="equipment.description" class="sm:col-span-2">
                            <dt class="text-sm text-muted-foreground">
                                Description
                            </dt>
                            <dd class="mt-1 text-sm whitespace-pre-wrap">
                                {{ equipment.description }}
                            </dd>
                        </div>
                    </dl>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Delivery</CardTitle>
                </CardHeader>
                <CardContent>
                    <dl class="space-y-3">
                        <div>
                            <dt class="text-sm text-muted-foreground">
                                Lead Time
                            </dt>
                            <dd class="mt-1 text-sm">
                                {{ equipment.lead_time ?? '—' }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm text-muted-foreground">
                                Expected Delivery
                            </dt>
                            <dd class="mt-1 text-sm">
                                {{ equipment.expected_delivery ?? '—' }}
                            </dd>
                        </div>
                        <div v-if="equipment.received_at">
                            <dt class="text-sm text-muted-foreground">
                                Received
                            </dt>
                            <dd class="mt-1 text-sm">
                                {{
                                    new Date(
                                        equipment.received_at,
                                    ).toLocaleDateString()
                                }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm text-muted-foreground">
                                Created
                            </dt>
                            <dd class="mt-1 text-sm">
                                {{
                                    new Date(
                                        equipment.created_at,
                                    ).toLocaleDateString()
                                }}
                            </dd>
                        </div>
                    </dl>
                </CardContent>
            </Card>
        </div>

        <Tabs default-value="comments">
            <TabsList>
                <TabsTrigger value="comments">
                    <MessageSquare class="mr-1 h-4 w-4" />
                    Comments
                    <Badge variant="secondary" class="ml-1.5 text-xs">
                        {{ equipment.comments.length }}
                    </Badge>
                </TabsTrigger>
                <TabsTrigger value="attachments">
                    <Paperclip class="mr-1 h-4 w-4" />
                    Attachments
                    <Badge variant="secondary" class="ml-1.5 text-xs">
                        {{ equipment.attachments.length }}
                    </Badge>
                </TabsTrigger>
                <TabsTrigger value="procurement">
                    <ClipboardList class="mr-1 h-4 w-4" />
                    Procurement
                    <Badge variant="secondary" class="ml-1.5 text-xs">
                        {{
                            equipment.procurement.quotation_count +
                            equipment.procurement.purchase_order_count
                        }}
                    </Badge>
                </TabsTrigger>
            </TabsList>

            <TabsContent value="comments">
                <Card>
                    <CardContent class="pt-6">
                        <div
                            v-if="equipment.comments.length > 0"
                            class="space-y-4"
                        >
                            <div
                                v-for="comment in equipment.comments"
                                :key="comment.id"
                                class="rounded-lg border p-4"
                            >
                                <div class="flex items-center justify-between">
                                    <p class="text-sm font-medium">
                                        {{ comment.user.name }}
                                    </p>
                                    <p class="text-xs text-muted-foreground">
                                        {{
                                            new Date(
                                                comment.created_at,
                                            ).toLocaleString()
                                        }}
                                    </p>
                                </div>
                                <p class="mt-2 text-sm whitespace-pre-wrap">
                                    {{ comment.body }}
                                </p>
                            </div>
                        </div>
                        <p v-else class="text-sm text-muted-foreground">
                            No comments yet.
                        </p>
                    </CardContent>
                </Card>
            </TabsContent>

            <TabsContent value="attachments">
                <Card>
                    <CardContent class="pt-6">
                        <div
                            v-if="equipment.attachments.length > 0"
                            class="space-y-2"
                        >
                            <div
                                v-for="attachment in equipment.attachments"
                                :key="attachment.id"
                                class="flex items-center justify-between rounded-lg border p-3"
                            >
                                <div class="flex items-center gap-3">
                                    <Package
                                        class="h-4 w-4 text-muted-foreground"
                                    />
                                    <div>
                                        <p class="text-sm font-medium">
                                            {{ attachment.name }}
                                        </p>
                                        <p
                                            class="text-xs text-muted-foreground"
                                        >
                                            {{ attachment.human_size }}
                                        </p>
                                    </div>
                                </div>
                                <Button variant="ghost" size="sm" as-child>
                                    <a :href="attachment.url" target="_blank"
                                        >Download</a
                                    >
                                </Button>
                            </div>
                        </div>
                        <p v-else class="text-sm text-muted-foreground">
                            No attachments.
                        </p>
                    </CardContent>
                </Card>
            </TabsContent>

            <TabsContent value="procurement">
                <div class="grid gap-4 sm:grid-cols-3">
                    <Card>
                        <CardHeader>
                            <CardTitle>Quotations</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p class="mb-4 text-sm text-muted-foreground">
                                {{ equipment.procurement.quotation_count }}
                                quotation(s) received.
                            </p>
                            <Button variant="outline" size="sm" as-child>
                                <Link
                                    :href="`/projects/${project.id}/equipment/${equipment.id}/quotations`"
                                >
                                    View quotations
                                </Link>
                            </Button>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader>
                            <CardTitle>Purchase Orders</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p class="mb-4 text-sm text-muted-foreground">
                                {{ equipment.procurement.purchase_order_count }}
                                PO(s) issued.
                            </p>
                            <Button variant="outline" size="sm" as-child>
                                <Link
                                    :href="`/projects/${project.id}/equipment/${equipment.id}/purchase-orders`"
                                >
                                    View purchase orders
                                </Link>
                            </Button>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader>
                            <CardTitle>Inspections</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p class="mb-4 text-sm text-muted-foreground">
                                {{ equipment.procurement.inspection_count }}
                                inspection(s) recorded.
                            </p>
                            <Button variant="outline" size="sm" as-child>
                                <Link
                                    :href="`/projects/${project.id}/equipment/${equipment.id}/inspections`"
                                >
                                    View inspections
                                </Link>
                            </Button>
                        </CardContent>
                    </Card>
                </div>
            </TabsContent>
        </Tabs>
    </div>
</template>
