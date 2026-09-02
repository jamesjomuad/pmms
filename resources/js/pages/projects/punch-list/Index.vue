<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { CheckSquare, Plus, Search, X } from '@lucide/vue';
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
import {
    priorityLabel,
    priorityVariant,
    statusLabel,
    statusVariant,
} from '@/composables/statusHelpers';
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

const clearFilters = () => {
    searchQuery.value = '';
    statusFilter.value = 'all';
    priorityFilter.value = 'all';
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
                    title: 'Punch List',
                    href: `/projects/${projectId}/punch-list`,
                },
            ],
        };
    },
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
                    aria-label="Search punch list"
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
                        priorityFilter !== 'all'
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
                        <TableHead>Location</TableHead>
                        <TableHead>Trade</TableHead>
                        <TableHead>Priority</TableHead>
                        <TableHead>Status</TableHead>
                        <TableHead>Due Date</TableHead>
                        <TableHead>Assigned To</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableRow v-for="item in filteredItems" :key="item.id">
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
                    </TableRow>
                </TableBody>
            </Table>
        </div>

        <EmptyState
            v-else-if="punchListItems.length > 0 && filteredItems.length === 0"
            :icon="Search"
            title="No punch list items found"
            description="No items match your current filters."
        >
            <template #action>
                <Button variant="outline" @click="clearFilters">
                    Clear filters
                </Button>
            </template>
        </EmptyState>

        <EmptyState
            v-else
            :icon="CheckSquare"
            title="No punch list items yet"
            description="Add your first punch list item to start tracking issues and resolutions."
        >
            <template #action>
                <Button as-child>
                    <Link :href="createUrl">
                        <Plus class="mr-1 h-4 w-4" />
                        Add item
                    </Link>
                </Button>
            </template>
        </EmptyState>
    </div>
</template>
