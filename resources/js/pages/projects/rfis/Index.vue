<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { HelpCircle, Plus, Search, X } from '@lucide/vue';
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
import { create as rfiCreate } from '@/routes/projects/rfis';

const props = defineProps<{
    project: { id: number; name: string };
    rfis: {
        id: number;
        title: string;
        status: string;
        due_date: string | null;
        assigned_to: string | null;
        created_by: string;
        created_at: string;
    }[];
}>();

const searchQuery = ref('');
const statusFilter = ref<string>('all');

const createUrl = rfiCreate(props.project.id).url;

const filteredRfis = computed(() => {
    let result = props.rfis;

    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase();
        result = result.filter((r) => r.title.toLowerCase().includes(query));
    }

    if (statusFilter.value !== 'all') {
        result = result.filter((r) => r.status === statusFilter.value);
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
        approved: 'Responded',
        rejected: 'Rejected',
        revision: 'Revision Required',
        cancelled: 'Cancelled',
    };

    return labels[status] ?? status;
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
    <Head :title="`RFIs - ${project.name}`" />

    <h1 class="sr-only">RFIs</h1>

    <div
        class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4"
    >
        <div class="flex items-center justify-between">
            <Heading
                variant="small"
                title="RFIs"
                :description="`Request for Information for ${project.name}`"
            />

            <Button as-child>
                <Link :href="createUrl">
                    <Plus class="mr-1 h-4 w-4" />
                    New RFI
                </Link>
            </Button>
        </div>

        <div
            v-if="rfis.length > 0"
            class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
        >
            <div class="relative flex-1 sm:max-w-xs">
                <Search
                    class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground"
                />
                <Input
                    v-model="searchQuery"
                    placeholder="Search RFIs..."
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
                    <option value="approved">Responded</option>
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
            v-if="rfis.length > 0 && filteredRfis.length > 0"
            class="rounded-lg border"
        >
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>Title</TableHead>
                        <TableHead>Status</TableHead>
                        <TableHead>Due Date</TableHead>
                        <TableHead>Assigned To</TableHead>
                        <TableHead>Created By</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableRow v-for="rfi in filteredRfis" :key="rfi.id">
                        <TableCell>
                            <Link
                                :href="`/projects/${project.id}/rfis/${rfi.id}`"
                                class="font-medium text-foreground hover:underline"
                            >
                                {{ rfi.title }}
                            </Link>
                        </TableCell>
                        <TableCell>
                            <Badge :variant="statusVariant(rfi.status)">
                                {{ statusLabel(rfi.status) }}
                            </Badge>
                        </TableCell>
                        <TableCell class="text-muted-foreground">
                            {{ rfi.due_date ?? '—' }}
                        </TableCell>
                        <TableCell class="text-muted-foreground">
                            {{ rfi.assigned_to ?? '—' }}
                        </TableCell>
                        <TableCell class="text-muted-foreground">
                            {{ rfi.created_by }}
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </div>

        <div
            v-else-if="rfis.length > 0 && filteredRfis.length === 0"
            class="flex flex-col items-center justify-center rounded-lg border border-dashed px-6 py-16 text-center"
        >
            <div
                class="mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-muted"
            >
                <Search class="h-6 w-6 text-muted-foreground/60" />
            </div>
            <h3 class="mb-1 text-sm font-medium">No RFIs found</h3>
            <p class="mb-6 max-w-sm text-sm text-muted-foreground">
                No RFIs match your current filters.
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
                <HelpCircle class="h-6 w-6 text-muted-foreground/60" />
            </div>
            <h3 class="mb-1 text-sm font-medium">No RFIs yet</h3>
            <p class="mb-6 max-w-sm text-sm text-muted-foreground">
                Create your first RFI to request information from the project
                team.
            </p>
            <Button as-child>
                <Link :href="createUrl">
                    <Plus class="mr-1 h-4 w-4" />
                    New RFI
                </Link>
            </Button>
        </div>
    </div>
</template>
