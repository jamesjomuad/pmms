<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { DraftingCompass, Plus, Search, X } from '@lucide/vue';
import { computed, ref } from 'vue';
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
import { create as shopDrawingCreate } from '@/routes/projects/shop-drawings';

const props = defineProps<{
    project: { id: number; name: string };
    shopDrawings: {
        id: number;
        title: string;
        drawing_number: string | null;
        revision_number: number;
        status: string;
        due_date: string | null;
        assigned_to: string | null;
        created_at: string;
    }[];
}>();

const searchQuery = ref('');
const statusFilter = ref<string>('all');

const createUrl = shopDrawingCreate(props.project.id).url;

const filteredShopDrawings = computed(() => {
    let result = props.shopDrawings;

    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase();
        result = result.filter(
            (s) =>
                s.title.toLowerCase().includes(query) ||
                s.drawing_number?.toLowerCase().includes(query),
        );
    }

    if (statusFilter.value !== 'all') {
        result = result.filter((s) => s.status === statusFilter.value);
    }

    return result;
});

const statusVariant = (status: string) => {
    switch (status) {
        case 'approved':
            return 'default';
        case 'pending':
        case 'in_review':
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
                { title: 'Shop Drawings', href: '#' },
            ],
        };
    },
});
</script>

<template>
    <Head :title="`Shop Drawings - ${project.name}`" />

    <h1 class="sr-only">Shop Drawings</h1>

    <div
        class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4"
    >
        <div class="flex items-center justify-between">
            <Heading
                variant="small"
                title="Shop Drawings"
                :description="`Manage shop drawings for ${project.name}`"
            />

            <Button as-child>
                <Link :href="createUrl">
                    <Plus class="mr-1 h-4 w-4" />
                    New shop drawing
                </Link>
            </Button>
        </div>

        <div
            v-if="shopDrawings.length > 0"
            class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
        >
            <div class="relative flex-1 sm:max-w-xs">
                <Search
                    class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground"
                />
                <Input
                    v-model="searchQuery"
                    placeholder="Search shop drawings..."
                    class="pl-9"
                />
            </div>

            <div class="flex items-center gap-2">
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
                        <SelectItem value="revision"
                            >Revision Required</SelectItem
                        >
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
            v-if="shopDrawings.length > 0 && filteredShopDrawings.length > 0"
            class="rounded-lg border"
        >
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>Title</TableHead>
                        <TableHead>Drawing #</TableHead>
                        <TableHead>Revision</TableHead>
                        <TableHead>Status</TableHead>
                        <TableHead>Due Date</TableHead>
                        <TableHead>Assigned To</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableRow
                        v-for="drawing in filteredShopDrawings"
                        :key="drawing.id"
                    >
                        <TableCell>
                            <Link
                                :href="`/projects/${project.id}/shop-drawings/${drawing.id}`"
                                class="font-medium text-foreground hover:underline"
                            >
                                {{ drawing.title }}
                            </Link>
                        </TableCell>
                        <TableCell class="text-muted-foreground">
                            {{ drawing.drawing_number ?? '—' }}
                        </TableCell>
                        <TableCell class="text-muted-foreground">
                            Rev. {{ drawing.revision_number }}
                        </TableCell>
                        <TableCell>
                            <Badge :variant="statusVariant(drawing.status)">
                                {{ statusLabel(drawing.status) }}
                            </Badge>
                        </TableCell>
                        <TableCell class="text-muted-foreground">
                            {{ drawing.due_date ?? '—' }}
                        </TableCell>
                        <TableCell class="text-muted-foreground">
                            {{ drawing.assigned_to ?? '—' }}
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </div>

        <div
            v-else-if="
                shopDrawings.length > 0 && filteredShopDrawings.length === 0
            "
            class="flex flex-col items-center justify-center rounded-lg border border-dashed px-6 py-16 text-center"
        >
            <div
                class="mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-muted"
            >
                <Search class="h-6 w-6 text-muted-foreground/60" />
            </div>
            <h3 class="mb-1 text-sm font-medium">No shop drawings found</h3>
            <p class="mb-6 max-w-sm text-sm text-muted-foreground">
                No shop drawings match your current filters.
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
                <DraftingCompass class="h-6 w-6 text-muted-foreground/60" />
            </div>
            <h3 class="mb-1 text-sm font-medium">No shop drawings yet</h3>
            <p class="mb-6 max-w-sm text-sm text-muted-foreground">
                Create your first shop drawing to start tracking approvals and
                revisions.
            </p>
            <Button as-child>
                <Link :href="createUrl">
                    <Plus class="mr-1 h-4 w-4" />
                    New shop drawing
                </Link>
            </Button>
        </div>
    </div>
</template>
