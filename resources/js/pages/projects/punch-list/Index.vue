<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { CheckSquare, Plus, Search, X } from '@lucide/vue';
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
import { create as punchListCreate } from '@/routes/projects/punch-list';

const props = defineProps<{
    project: { id: number; name: string };
    punchListItems: {
        id: number;
        title: string;
        location: string | null;
        trade: string | null;
        priority: string;
        status: string;
        due_date: string | null;
        assigned_to: string | null;
        created_at: string;
    }[];
}>();

const searchQuery = ref('');
const statusFilter = ref<string>('all');
const priorityFilter = ref<string>('all');

const createUrl = punchListCreate(props.project.id).url;

const filteredItems = computed(() => {
    let result = props.punchListItems;

    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase();
        result = result.filter(
            (i) =>
                i.title.toLowerCase().includes(query) ||
                i.location?.toLowerCase().includes(query),
        );
    }

    if (statusFilter.value !== 'all') {
        result = result.filter((i) => i.status === statusFilter.value);
    }

    if (priorityFilter.value !== 'all') {
        result = result.filter((i) => i.priority === priorityFilter.value);
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
        pending: 'Open',
        in_review: 'In Review',
        approved: 'Resolved',
        rejected: 'Rejected',
        cancelled: 'Cancelled',
    };

    return labels[status] ?? status;
};

const priorityVariant = (priority: string) => {
    switch (priority) {
        case 'critical':
            return 'destructive';
        case 'high':
            return 'default';
        case 'medium':
            return 'secondary';
        case 'low':
            return 'outline';
        default:
            return 'secondary';
    }
};

const clearFilters = () => {
    searchQuery.value = '';
    statusFilter.value = 'all';
    priorityFilter.value = 'all';
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
    <Head :title="`Punch List - ${project.name}`" />

    <h1 class="sr-only">Punch List</h1>

    <div
        class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4"
    >
        <div class="flex items-center justify-between">
            <Heading
                variant="small"
                title="Punch List"
                :description="`Track punch list items for ${project.name}`"
            />

            <Button as-child>
                <Link :href="createUrl">
                    <Plus class="mr-1 h-4 w-4" />
                    Add item
                </Link>
            </Button>
        </div>

        <div
            v-if="punchListItems.length > 0"
            class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
        >
            <div class="relative flex-1 sm:max-w-xs">
                <Search
                    class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground"
                />
                <Input
                    v-model="searchQuery"
                    placeholder="Search punch list..."
                    class="pl-9"
                />
            </div>

            <div class="flex items-center gap-2">
                <select
                    v-model="statusFilter"
                    class="h-9 rounded-md border bg-transparent px-3 text-xs font-medium text-muted-foreground outline-none focus:border-ring focus:ring-2 focus:ring-ring/50"
                >
                    <option value="all">All Status</option>
                    <option value="pending">Open</option>
                    <option value="approved">Resolved</option>
                </select>

                <select
                    v-model="priorityFilter"
                    class="h-9 rounded-md border bg-transparent px-3 text-xs font-medium text-muted-foreground outline-none focus:border-ring focus:ring-2 focus:ring-ring/50"
                >
                    <option value="all">All Priority</option>
                    <option value="critical">Critical</option>
                    <option value="high">High</option>
                    <option value="medium">Medium</option>
                    <option value="low">Low</option>
                </select>

                <Button
                    v-if="searchQuery || statusFilter !== 'all' || priorityFilter !== 'all'"
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
            v-if="punchListItems.length > 0 && filteredItems.length > 0"
            class="rounded-lg border"
        >
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>Title</TableHead>
                        <TableHead>Location</TableHead>
                        <TableHead>Trade</TableHead>
                        <TableHead>Priority</TableHead>
                        <TableHead>Status</TableHead>
                        <TableHead>Due Date</TableHead>
                        <TableHead>Assigned To</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableRow
                        v-for="item in filteredItems"
                        :key="item.id"
                    >
                        <TableCell>
                            <Link
                                :href="`/projects/${project.id}/punch-list/${item.id}`"
                                class="font-medium text-foreground hover:underline"
                            >
                                {{ item.title }}
                            </Link>
                        </TableCell>
                        <TableCell class="text-muted-foreground">
                            {{ item.location ?? '—' }}
                        </TableCell>
                        <TableCell class="text-muted-foreground">
                            {{ item.trade ?? '—' }}
                        </TableCell>
                        <TableCell>
                            <Badge :variant="priorityVariant(item.priority)">
                                {{ item.priority }}
                            </Badge>
                        </TableCell>
                        <TableCell>
                            <Badge :variant="statusVariant(item.status)">
                                {{ statusLabel(item.status) }}
                            </Badge>
                        </TableCell>
                        <TableCell class="text-muted-foreground">
                            {{ item.due_date ?? '—' }}
                        </TableCell>
                        <TableCell class="text-muted-foreground">
                            {{ item.assigned_to ?? '—' }}
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </div>

        <div
            v-else-if="punchListItems.length > 0 && filteredItems.length === 0"
            class="flex flex-col items-center justify-center rounded-lg border border-dashed px-6 py-16 text-center"
        >
            <div
                class="mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-muted"
            >
                <Search class="h-6 w-6 text-muted-foreground/60" />
            </div>
            <h3 class="mb-1 text-sm font-medium">No punch list items found</h3>
            <p class="mb-6 max-w-sm text-sm text-muted-foreground">
                No items match your current filters.
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
                <CheckSquare class="h-6 w-6 text-muted-foreground/60" />
            </div>
            <h3 class="mb-1 text-sm font-medium">No punch list items yet</h3>
            <p class="mb-6 max-w-sm text-sm text-muted-foreground">
                Add your first punch list item to start tracking issues and
                resolutions.
            </p>
            <Button as-child>
                <Link :href="createUrl">
                    <Plus class="mr-1 h-4 w-4" />
                    Add item
                </Link>
            </Button>
        </div>
    </div>
</template>
