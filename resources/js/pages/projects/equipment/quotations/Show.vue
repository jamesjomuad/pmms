<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowLeft, CheckCircle2, FileX2, Pencil } from '@lucide/vue';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

const props = defineProps<{
    project: { id: number; name: string };
    equipment: { id: number; title: string };
    quotation: {
        id: number;
        supplier_name: string;
        quoted_cost: number;
        quoted_lead_time_days: number | null;
        quote_date: string | null;
        status: string;
        created_at: string;
    };
}>();

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

const selectQuotation = () => {
    router.post(
        `/projects/${props.project.id}/equipment/${props.equipment.id}/quotations/${props.quotation.id}/select`,
    );
};

const declineQuotation = () => {
    router.post(
        `/projects/${props.project.id}/equipment/${props.equipment.id}/quotations/${props.quotation.id}/decline`,
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
    <Head :title="`${quotation.supplier_name} - Quotation`" />

    <h1 class="sr-only">{{ quotation.supplier_name }}</h1>

    <div
        class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4"
    >
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <Button variant="ghost" size="sm" as-child>
                    <Link
                        :href="`/projects/${project.id}/equipment/${equipment.id}/quotations`"
                    >
                        <ArrowLeft class="mr-1 h-4 w-4" />
                        Back
                    </Link>
                </Button>
                <Heading
                    variant="small"
                    :title="quotation.supplier_name"
                    :description="`${equipment.title} · ${project.name}`"
                />
            </div>

            <div class="flex items-center gap-2">
                <Button
                    v-if="quotation.status === 'received'"
                    variant="default"
                    size="sm"
                    @click="selectQuotation"
                >
                    <CheckCircle2 class="mr-1 h-4 w-4" />
                    Select Quote
                </Button>
                <Button
                    v-if="quotation.status === 'received'"
                    variant="outline"
                    size="sm"
                    @click="declineQuotation"
                >
                    <FileX2 class="mr-1 h-4 w-4" />
                    Decline
                </Button>
                <Button variant="outline" size="sm" as-child>
                    <Link
                        :href="`/projects/${project.id}/equipment/${equipment.id}/quotations/${quotation.id}/edit`"
                    >
                        <Pencil class="mr-1 h-4 w-4" />
                        Edit
                    </Link>
                </Button>
            </div>
        </div>

        <Card class="max-w-2xl">
            <CardHeader>
                <CardTitle>Quotation Details</CardTitle>
            </CardHeader>
            <CardContent>
                <dl class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <dt class="text-sm text-muted-foreground">Status</dt>
                        <dd class="mt-1">
                            <Badge :variant="statusVariant(quotation.status)">
                                {{ statusLabel(quotation.status) }}
                            </Badge>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm text-muted-foreground">
                            Quoted Cost
                        </dt>
                        <dd class="mt-1 text-sm font-medium">
                            {{ formatCurrency(quotation.quoted_cost) }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm text-muted-foreground">Lead Time</dt>
                        <dd class="mt-1 text-sm">
                            {{
                                quotation.quoted_lead_time_days
                                    ? `${quotation.quoted_lead_time_days} days`
                                    : '—'
                            }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm text-muted-foreground">
                            Quote Date
                        </dt>
                        <dd class="mt-1 text-sm">
                            {{ quotation.quote_date ?? '—' }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm text-muted-foreground">Created</dt>
                        <dd class="mt-1 text-sm">
                            {{
                                new Date(
                                    quotation.created_at,
                                ).toLocaleDateString()
                            }}
                        </dd>
                    </div>
                </dl>
            </CardContent>
        </Card>
    </div>
</template>
