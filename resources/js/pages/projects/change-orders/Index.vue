<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { DollarSign, Plus, Search, X } from '@lucide/vue';
import { computed, ref } from 'vue';
import EmptyState from '@/components/EmptyState.vue';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { statusLabel, statusVariant } from '@/composables/statusHelpers';
import { create as changeOrderCreate } from '@/routes/projects/change-orders';

const props = defineProps<{
    project: { id: number; name: string };
    changeOrders: {
        id: number;
        title: string;
        cost_impact: number;
        schedule_impact_days: number;
        status: string;
        requested_by: string | null;
        requested_at: string | null;
        created_at: string;
    }[];
}>();

const searchQuery = ref('');
const statusFilter = ref<string>('all');

const createUrl = changeOrderCreate(props.project.id).url;

const currency = new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0,
});

const filteredChangeOrders = computed(() => {
    let result = props.changeOrders;

    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase();
        result = result.filter((co) =>
            co.title.toLowerCase().includes(query),
        );
    }

    if (statusFilter.value !== 'all') {
        result = result.filter((co) => co.status === statusFilter.value);
    }

    return result;
});

const clearFilters = () => {
    searchQuery.value = '';
    statusFilter.value = 'all';
};

defineOptions({
    layout: () => {
        const pathMatch = window.location.pathname.match(/\/projects\/(\d+)/);
        const projectId = pathMatch ? pathMatch[1] : '';

        return {
            breadcrumbs: [
                { title: 'Projects', href: '/projects' },
                {
                    title: 'Project Details',
                    href: `/projects/${projectId}`,
                },
                {
                    title: 'Change Orders',
                    href: `/projects/${projectId}/change-orders`,
                },
            ],
        };
    },
});
</script>

<template>
    <Head :title="`Change Orders - ${project.name}`" />

    <h1 class="sr-only">Change Orders</h1>

    <div
        class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4"
    >
        <div class="flex items-center justify-between">
            <Heading
                variant="small"
                title="Change Orders"
                :description="`Manage change orders for ${project.name}`"
            />

            <Button as-child>
                <Link :href="createUrl">
                    <Plus class="mr-1 h-4 w-4" />
                    New change order
                </Link>
            </Button>
        </div>

        <div
            v-if="changeOrders.length > 0"
            class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
        >
            <div class="relative flex-1 sm:max-w-xs">
                <Search
                    class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground"
                />
                <Input
                    v-model="searchQuery"
                    placeholder="Search change orders..."
                    aria-label="Search change orders"
                    class="pl-9"
                />
            </div>

            <div class="flex items-center gap-2">
                <Select v-model="statusFilter">
                    <SelectTrigger class="h-9 w-auto text-xs" aria-label="Filter by status">
                        <SelectValue placeholder="All Status" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="all">All Status</SelectItem>
                        <SelectItem value="draft">Draft</SelectItem>
                        <SelectItem value="pending">Pending</SelectItem>
                        <SelectItem value="in_review">In Review</SelectItem>
                        <SelectItem value="approved">Approved</SelectItem>
                        <SelectItem value="rejected">Rejected</SelectItem>
                    </SelectContent>
                </Select>

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
            v-if="changeOrders.length > 0 && filteredChangeOrders.length > 0"
            class="rounded-lg border"
        >
            <Table aria-label="Change Orders">
                <TableHeader>
                    <TableRow>
                        <TableHead>Title</TableHead>
                        <TableHead>Cost Impact</TableHead>
                        <TableHead>Schedule Impact</TableHead>
                        <TableHead>Status</TableHead>
                        <TableHead>Requested By</TableHead>
                        <TableHead>Requested At</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableRow
                        v-for="changeOrder in filteredChangeOrders"
                        :key="changeOrder.id"
                    >
                        <TableCell>
                            <Link
                                :href="`/projects/${project.id}/change-orders/${changeOrder.id}`"
                                class="font-medium text-foreground hover:underline"
                            >
                                {{ changeOrder.title }}
                            </Link>
                        </TableCell>
                        <TableCell class="text-muted-foreground">
                            {{ currency.format(changeOrder.cost_impact) }}
                        </TableCell>
                        <TableCell class="text-muted-foreground">
                            {{ changeOrder.schedule_impact_days > 0 ? `${changeOrder.schedule_impact_days} days` : '—' }}
                        </TableCell>
                        <TableCell>
                            <Badge :variant="statusVariant(changeOrder.status)">
                                {{ statusLabel(changeOrder.status) }}
                            </Badge>
                        </TableCell>
                        <TableCell class="text-muted-foreground">
                            {{ changeOrder.requested_by ?? '—' }}
                        </TableCell>
                        <TableCell class="text-muted-foreground">
                            {{
                                changeOrder.requested_at
                                    ? new Date(changeOrder.requested_at).toLocaleDateString()
                                    : '—'
                            }}
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </div>

        <EmptyState
            v-else-if="changeOrders.length > 0 && filteredChangeOrders.length === 0"
            :icon="Search"
            title="No change orders found"
            description="No change orders match your current filters."
        >
            <template #action>
                <Button variant="outline" @click="clearFilters">
                    Clear filters
                </Button>
            </template>
        </EmptyState>

        <EmptyState
            v-else
            :icon="DollarSign"
            title="No change orders yet"
            description="Create your first change order to start tracking cost and schedule impacts."
        >
            <template #action>
                <Button as-child>
                    <Link :href="createUrl">
                        <Plus class="mr-1 h-4 w-4" />
                        New change order
                    </Link>
                </Button>
            </template>
        </EmptyState>
    </div>
</template>
