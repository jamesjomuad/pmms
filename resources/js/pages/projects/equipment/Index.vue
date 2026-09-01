<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Package, Plus, Search, X } from '@lucide/vue';
import { computed, ref } from 'vue';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { create as equipmentCreate } from '@/routes/projects/equipment';

const props = defineProps<{
    project: { id: number; name: string };
    equipment: {
        id: number;
        title: string;
        manufacturer: string | null;
        model_number: string | null;
        cost: number | null;
        quantity: number;
        status: string;
        po_status: string;
        expected_delivery: string | null;
        assigned_to: string | null;
        created_at: string;
    }[];
}>();

const searchQuery = ref('');
const statusFilter = ref<string>('all');

const createUrl = equipmentCreate(props.project.id).url;

const filteredEquipment = computed(() => {
    let result = props.equipment;

    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase();
        result = result.filter(
            (e) =>
                e.title.toLowerCase().includes(query) ||
                e.manufacturer?.toLowerCase().includes(query),
        );
    }

    if (statusFilter.value !== 'all') {
        result = result.filter((e) => e.status === statusFilter.value);
    }

    return result;
});

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
        revision: 'Revision Required',
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

const clearFilters = () => {
    searchQuery.value = '';
    statusFilter.value = 'all';
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
    <Head :title="`Equipment - ${project.name}`" />

    <h1 class="sr-only">Equipment</h1>

    <div
        class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4"
    >
        <div class="flex items-center justify-between">
            <Heading
                variant="small"
                title="Equipment"
                :description="`Track equipment for ${project.name}`"
            />

            <Button as-child>
                <Link :href="createUrl">
                    <Plus class="mr-1 h-4 w-4" />
                    Add equipment
                </Link>
            </Button>
        </div>

        <div
            v-if="equipment.length > 0"
            class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
        >
            <div class="relative flex-1 sm:max-w-xs">
                <Search
                    class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground"
                />
                <Input
                    v-model="searchQuery"
                    placeholder="Search equipment..."
                    class="pl-9"
                />
            </div>

            <div class="flex items-center gap-2">
                <select
                    v-model="statusFilter"
                    class="h-9 rounded-md border bg-transparent px-3 text-xs font-medium text-muted-foreground outline-none focus:border-ring focus:ring-2 focus:ring-ring/50"
                >
                    <option value="all">All Status</option>
                    <option value="draft">Draft</option>
                    <option value="pending">Pending</option>
                    <option value="approved">Received</option>
                </select>

                <Button
                    v-if="searchQuery || statusFilter !== 'all'"
                    variant="ghost"
                    size="sm"
                    class="h-8 px-2 text-xs"
                    @click="clearFilters"
                >
                    <X class="mr-1 h-3 w-3" />
                    Clear
                </Button>
            </div>
        </div>

        <div
            v-if="equipment.length > 0 && filteredEquipment.length > 0"
            class="rounded-lg border"
        >
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>Title</TableHead>
                        <TableHead>Manufacturer</TableHead>
                        <TableHead>Cost</TableHead>
                        <TableHead>Qty</TableHead>
                        <TableHead>Status</TableHead>
                        <TableHead>PO Status</TableHead>
                        <TableHead>Expected</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableRow
                        v-for="item in filteredEquipment"
                        :key="item.id"
                    >
                        <TableCell>
                            <Link
                                :href="`/projects/${project.id}/equipment/${item.id}`"
                                class="font-medium text-foreground hover:underline"
                            >
                                {{ item.title }}
                            </Link>
                        </TableCell>
                        <TableCell class="text-muted-foreground">
                            {{ item.manufacturer ?? '—' }}
                        </TableCell>
                        <TableCell class="text-muted-foreground">
                            {{ formatCurrency(item.cost) }}
                        </TableCell>
                        <TableCell class="text-muted-foreground">
                            {{ item.quantity }}
                        </TableCell>
                        <TableCell>
                            <Badge :variant="statusVariant(item.status)">
                                {{ statusLabel(item.status) }}
                            </Badge>
                        </TableCell>
                        <TableCell class="text-muted-foreground">
                            {{ poStatusLabel(item.po_status) }}
                        </TableCell>
                        <TableCell class="text-muted-foreground">
                            {{ item.expected_delivery ?? '—' }}
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </div>

        <div
            v-else-if="equipment.length > 0 && filteredEquipment.length === 0"
            class="flex flex-col items-center justify-center rounded-lg border border-dashed px-6 py-16 text-center"
        >
            <div
                class="mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-muted"
            >
                <Search class="h-6 w-6 text-muted-foreground/60" />
            </div>
            <h3 class="mb-1 text-sm font-medium">No equipment found</h3>
            <p class="mb-6 max-w-sm text-sm text-muted-foreground">
                No equipment matches your current filters.
            </p>
            <Button variant="outline" @click="clearFilters">
                Clear filters
            </Button>
        </div>

        <div
            v-else
            class="flex flex-col items-center justify-center rounded-lg border border-dashed px-6 py-16 text-center"
        >
            <div
                class="mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-muted"
            >
                <Package class="h-6 w-6 text-muted-foreground/60" />
            </div>
            <h3 class="mb-1 text-sm font-medium">No equipment yet</h3>
            <p class="mb-6 max-w-sm text-sm text-muted-foreground">
                Add your first equipment item to start tracking procurement and
                delivery.
            </p>
            <Button as-child>
                <Link :href="createUrl">
                    <Plus class="mr-1 h-4 w-4" />
                    Add equipment
                </Link>
            </Button>
        </div>
    </div>
</template>
