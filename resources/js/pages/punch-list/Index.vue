<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    AlertCircle,
    CheckCircle2,
    CheckSquare,
    Clock,
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
import {
    priorityLabel,
    priorityVariant,
    statusLabel,
    statusVariant,
} from '@/composables/statusHelpers';

interface PunchListItem {
    id: number;
    title: string;
    location: string | null;
    trade: string | null;
    priority: string;
    status: string;
    due_date: string | null;
    assigned_to: string | null;
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
    punchListItems: PunchListItem[];
    projects: ProjectOption[];
}>();

const searchQuery = ref('');
const statusFilter = ref<string>('all');
const priorityFilter = ref<string>('all');
const projectFilter = ref<string>('all');

const filteredItems = computed(() => {
    let result = props.punchListItems;

    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase();
        result = result.filter(
            (item) =>
                item.title.toLowerCase().includes(query) ||
                (item.location &&
                    item.location.toLowerCase().includes(query)) ||
                (item.trade && item.trade.toLowerCase().includes(query)) ||
                item.project.name.toLowerCase().includes(query) ||
                item.project.project_number.toLowerCase().includes(query),
        );
    }

    if (statusFilter.value !== 'all') {
        result = result.filter((item) => item.status === statusFilter.value);
    }

    if (priorityFilter.value !== 'all') {
        result = result.filter(
            (item) => item.priority === priorityFilter.value,
        );
    }

    if (projectFilter.value !== 'all') {
        const pId = Number(projectFilter.value);
        result = result.filter((item) => item.project.id === pId);
    }

    return result;
});

const stats = computed(() => {
    const total = props.punchListItems.length;
    const open = props.punchListItems.filter(
        (item) =>
            item.status === 'pending' ||
            item.status === 'draft' ||
            item.status === 'in_review',
    ).length;
    const resolved = props.punchListItems.filter(
        (item) => item.status === 'approved',
    ).length;
    const critical = props.punchListItems.filter(
        (item) => item.priority === 'critical',
    ).length;

    return { total, open, resolved, critical };
});

const clearFilters = () => {
    searchQuery.value = '';
    statusFilter.value = 'all';
    priorityFilter.value = 'all';
    projectFilter.value = 'all';
};

defineOptions({
    layout: () => ({
        breadcrumbs: [
            {
                title: 'Punch List',
                href: '/punch-list',
            },
        ],
    }),
});
</script>

<template>
    <Head title="Punch List" />

    <h1 class="sr-only">Punch List</h1>

    <div
        class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4"
    >
        <div class="flex items-center justify-between">
            <Heading
                variant="small"
                title="Punch List"
                description="Track unresolved items and resolutions across all your projects"
            />
        </div>

        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <StatCard
                :icon="CheckSquare"
                label="Total Items"
                :value="stats.total"
                icon-bg-class="bg-blue-50 dark:bg-blue-950"
                icon-class="text-blue-600 dark:text-blue-400"
            />
            <StatCard
                :icon="Clock"
                label="Open Items"
                :value="stats.open"
                icon-bg-class="bg-amber-50 dark:bg-amber-950"
                icon-class="text-amber-600 dark:text-amber-400"
            />
            <StatCard
                :icon="CheckCircle2"
                label="Resolved"
                :value="stats.resolved"
                icon-bg-class="bg-emerald-50 dark:bg-emerald-950"
                icon-class="text-emerald-600 dark:text-emerald-400"
            />
            <StatCard
                :icon="AlertCircle"
                label="Critical Priority"
                :value="stats.critical"
                icon-bg-class="bg-rose-50 dark:bg-rose-950"
                icon-class="text-rose-600 dark:text-rose-400"
            />
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
                    placeholder="Search items, locations, or projects..."
                    class="pl-9"
                    aria-label="Search punch list"
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
                        <SelectItem value="pending">Open</SelectItem>
                        <SelectItem value="approved">Resolved</SelectItem>
                    </SelectContent>
                </Select>

                <Select v-model="priorityFilter">
                    <SelectTrigger
                        class="h-9 w-auto text-xs"
                        aria-label="Filter by priority"
                    >
                        <SelectValue placeholder="All Priority" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="all">All Priority</SelectItem>
                        <SelectItem value="critical">Critical</SelectItem>
                        <SelectItem value="high">High</SelectItem>
                        <SelectItem value="medium">Medium</SelectItem>
                        <SelectItem value="low">Low</SelectItem>
                    </SelectContent>
                </Select>

                <Button
                    v-if="
                        searchQuery ||
                        statusFilter !== 'all' ||
                        priorityFilter !== 'all' ||
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
            v-if="punchListItems.length > 0 && filteredItems.length > 0"
            class="rounded-lg border"
        >
            <Table aria-label="Punch List Items">
                <TableHeader>
                    <TableRow>
                        <TableHead>Title</TableHead>
                        <TableHead>Project</TableHead>
                        <TableHead>Location</TableHead>
                        <TableHead>Trade</TableHead>
                        <TableHead>Priority</TableHead>
                        <TableHead>Status</TableHead>
                        <TableHead>Due Date</TableHead>
                        <TableHead>Assigned To</TableHead>
                        <TableHead class="text-right">Actions</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableRow v-for="item in filteredItems" :key="item.id">
                        <TableCell>
                            <Link
                                :href="`/projects/${item.project.id}/punch-list/${item.id}`"
                                class="font-medium text-foreground hover:underline"
                            >
                                {{ item.title }}
                            </Link>
                        </TableCell>
                        <TableCell>
                            <Link
                                :href="`/projects/${item.project.id}`"
                                class="text-sm text-muted-foreground hover:text-foreground hover:underline"
                            >
                                {{ item.project.name }}
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
                                {{ priorityLabel(item.priority) }}
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
                        <TableCell class="text-right">
                            <Button variant="ghost" size="sm" as-child>
                                <Link
                                    :href="`/projects/${item.project.id}/punch-list/${item.id}`"
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
                No punch list items match your current search and filter
                settings.
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
                Punch list items created within your projects will appear here
                for centralized tracking.
            </p>
            <Button as-child>
                <Link href="/projects"> View Projects </Link>
            </Button>
        </div>
    </div>
</template>
