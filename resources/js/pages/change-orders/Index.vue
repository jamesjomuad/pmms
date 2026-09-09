<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    AlertCircle,
    CheckCircle2,
    Clock,
    DollarSign,
    ExternalLink,
    Search,
    X,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import Heading from '@/components/Heading.vue';
import StatCard from '@/components/StatCard.vue';
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

interface ChangeOrderItem {
    id: number;
    title: string;
    cost_impact: number;
    schedule_impact_days: number;
    status: string;
    requested_by: string | null;
    approved_by: string | null;
    requested_at: string | null;
    created_at: string;
    project: {
        id: number;
        name: string;
        project_number: string;
    };
}

interface ProjectOption {
    id: number;
    name: string;
    project_number: string;
}

const props = defineProps<{
    changeOrders: ChangeOrderItem[];
    projects: ProjectOption[];
}>();

const searchQuery = ref('');
const statusFilter = ref<string>('all');
const projectFilter = ref<string>('all');

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
        result = result.filter(
            (co) =>
                co.title.toLowerCase().includes(query) ||
                co.project.name.toLowerCase().includes(query) ||
                co.project.project_number.toLowerCase().includes(query),
        );
    }

    if (statusFilter.value !== 'all') {
        result = result.filter((co) => co.status === statusFilter.value);
    }

    if (projectFilter.value !== 'all') {
        const pId = Number(projectFilter.value);
        result = result.filter((co) => co.project.id === pId);
    }

    return result;
});

const stats = computed(() => {
    const total = props.changeOrders.length;
    const pending = props.changeOrders.filter(
        (co) => co.status === 'pending' || co.status === 'in_review',
    ).length;
    const approved = props.changeOrders.filter(
        (co) => co.status === 'approved',
    ).length;
    const rejected = props.changeOrders.filter(
        (co) => co.status === 'rejected',
    ).length;

    return { total, pending, approved, rejected };
});

const statusVariant = (status: string) => {
    switch (status) {
        case 'approved': {
            return 'default';
        }
        case 'pending':
        case 'in_review': {
            return 'secondary';
        }
        case 'rejected': {
            return 'destructive';
        }
        case 'draft': {
            return 'outline';
        }
        default: {
            return 'secondary';
        }
    }
};

const statusLabel = (status: string) => {
    const labels: Record<string, string> = {
        draft: 'Draft',
        pending: 'Pending',
        in_review: 'In Review',
        approved: 'Approved',
        rejected: 'Rejected',
        revision: 'Revision Required',
        cancelled: 'Cancelled',
    };

    return labels[status] ?? status;
};

const clearFilters = () => {
    searchQuery.value = '';
    statusFilter.value = 'all';
    projectFilter.value = 'all';
};

defineOptions({
    layout: () => ({
        breadcrumbs: [
            {
                title: 'Change Orders',
                href: '/change-orders',
            },
        ],
    }),
});
</script>

<template>
    <Head title="Change Orders" />

    <h1 class="sr-only">Change Orders</h1>

    <div
        class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4"
    >
        <div class="flex items-center justify-between">
            <Heading
                variant="small"
                title="Change Orders"
                description="Track cost and schedule impact requests across all your projects"
            />
        </div>

        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <StatCard
                :icon="DollarSign"
                label="Total Change Orders"
                :value="stats.total"
                icon-bg-class="bg-blue-50 dark:bg-blue-950"
                icon-class="text-blue-600 dark:text-blue-400"
            />
            <StatCard
                :icon="Clock"
                label="Pending Approval"
                :value="stats.pending"
                icon-bg-class="bg-amber-50 dark:bg-amber-950"
                icon-class="text-amber-600 dark:text-amber-400"
            />
            <StatCard
                :icon="CheckCircle2"
                label="Approved"
                :value="stats.approved"
                icon-bg-class="bg-emerald-50 dark:bg-emerald-950"
                icon-class="text-emerald-600 dark:text-emerald-400"
            />
            <StatCard
                :icon="AlertCircle"
                label="Rejected"
                :value="stats.rejected"
                icon-bg-class="bg-rose-50 dark:bg-rose-950"
                icon-class="text-rose-600 dark:text-rose-400"
            />
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
                    placeholder="Search change orders or projects..."
                    class="pl-9"
                    aria-label="Search change orders"
                />
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <Select v-model="projectFilter">
                    <SelectTrigger
                        class="h-9 w-auto min-w-[140px] text-xs"
                        aria-label="Filter by project"
                    >
                        <SelectValue placeholder="All Projects" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="all">All Projects</SelectItem>
                        <SelectItem
                            v-for="proj in projects"
                            :key="proj.id"
                            :value="String(proj.id)"
                        >
                            {{ proj.name }}
                        </SelectItem>
                    </SelectContent>
                </Select>

                <Select v-model="statusFilter">
                    <SelectTrigger
                        class="h-9 w-auto text-xs"
                        aria-label="Filter by status"
                    >
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
                    v-if="
                        searchQuery ||
                        statusFilter !== 'all' ||
                        projectFilter !== 'all'
                    "
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
                        <TableHead>Project</TableHead>
                        <TableHead>Cost Impact</TableHead>
                        <TableHead>Schedule Impact</TableHead>
                        <TableHead>Status</TableHead>
                        <TableHead>Requested By</TableHead>
                        <TableHead>Requested At</TableHead>
                        <TableHead class="text-right">Actions</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableRow
                        v-for="changeOrder in filteredChangeOrders"
                        :key="changeOrder.id"
                    >
                        <TableCell>
                            <Link
                                :href="`/projects/${changeOrder.project.id}/change-orders/${changeOrder.id}`"
                                class="font-medium text-foreground hover:underline"
                            >
                                {{ changeOrder.title }}
                            </Link>
                        </TableCell>
                        <TableCell>
                            <Link
                                :href="`/projects/${changeOrder.project.id}`"
                                class="text-sm text-muted-foreground hover:text-foreground hover:underline"
                            >
                                {{ changeOrder.project.name }}
                            </Link>
                        </TableCell>
                        <TableCell class="text-muted-foreground">
                            {{ currency.format(changeOrder.cost_impact) }}
                        </TableCell>
                        <TableCell class="text-muted-foreground">
                            {{
                                changeOrder.schedule_impact_days > 0
                                    ? `${changeOrder.schedule_impact_days} days`
                                    : '—'
                            }}
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
                                    ? new Date(
                                          changeOrder.requested_at,
                                      ).toLocaleDateString()
                                    : '—'
                            }}
                        </TableCell>
                        <TableCell class="text-right">
                            <Button variant="ghost" size="sm" as-child>
                                <Link
                                    :href="`/projects/${changeOrder.project.id}/change-orders/${changeOrder.id}`"
                                    class="text-xs"
                                >
                                    <ExternalLink class="mr-1 h-3 w-3" />
                                    View
                                </Link>
                            </Button>
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </div>

        <div
            v-else-if="
                changeOrders.length > 0 && filteredChangeOrders.length === 0
            "
            class="flex flex-col items-center justify-center rounded-lg border border-dashed px-6 py-16 text-center"
        >
            <div
                class="mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-muted"
            >
                <Search class="h-6 w-6 text-muted-foreground/60" />
            </div>
            <h3 class="mb-1 text-sm font-medium">No change orders found</h3>
            <p class="mb-6 max-w-sm text-sm text-muted-foreground">
                No change orders match your current search and filter settings.
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
                <DollarSign class="h-6 w-6 text-muted-foreground/60" />
            </div>
            <h3 class="mb-1 text-sm font-medium">No change orders yet</h3>
            <p class="mb-6 max-w-sm text-sm text-muted-foreground">
                Change orders created within your projects will appear here for
                centralized tracking.
            </p>
            <Button as-child>
                <Link href="/projects"> View Projects </Link>
            </Button>
        </div>
    </div>
</template>
