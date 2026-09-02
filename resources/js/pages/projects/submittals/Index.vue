<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { FileText, Plus, Search, X } from '@lucide/vue';
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
import { create as submittalCreate } from '@/routes/projects/submittals';
import type { Submittal } from '@/types';

const props = defineProps<{
    project: { id: number; name: string };
    submittals: Submittal[];
}>();

const searchQuery = ref('');
const statusFilter = ref<string>('all');

const createUrl = submittalCreate(props.project.id).url;

const filteredSubmittals = computed(() => {
    let result = props.submittals;

    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase();
        result = result.filter(
            (s) =>
                s.title.toLowerCase().includes(query) ||
                s.spec_section?.toLowerCase().includes(query),
        );
    }

    if (statusFilter.value !== 'all') {
        result = result.filter((s) => s.status === statusFilter.value);
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
                    title: 'Submittals',
                    href: `/projects/${projectId}/submittals`,
                },
            ],
        };
    },
});
</script>

<template>
    <Head :title="`Submittals - ${project.name}`" />

    <h1 class="sr-only">Submittals</h1>

    <div
        class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4"
    >
        <div class="flex items-center justify-between">
            <Heading
                variant="small"
                title="Submittals"
                :description="`Manage submittals for ${project.name}`"
            />

            <Button as-child>
                <Link :href="createUrl">
                    <Plus class="mr-1 h-4 w-4" />
                    New submittal
                </Link>
            </Button>
        </div>

        <div
            v-if="submittals.length > 0"
            class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
        >
            <div class="relative flex-1 sm:max-w-xs">
                <Search
                    class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground"
                />
                <Input
                    v-model="searchQuery"
                    placeholder="Search submittals..."
                    aria-label="Search submittals"
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
                        <SelectItem value="revision">Revision Required</SelectItem>
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
            v-if="submittals.length > 0 && filteredSubmittals.length > 0"
            class="rounded-lg border"
        >
            <Table aria-label="Submittals">
                <TableHeader>
                    <TableRow>
                        <TableHead>Title</TableHead>
                        <TableHead>Spec Section</TableHead>
                        <TableHead>Revision</TableHead>
                        <TableHead>Status</TableHead>
                        <TableHead>Due Date</TableHead>
                        <TableHead>Assigned To</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableRow
                        v-for="submittal in filteredSubmittals"
                        :key="submittal.id"
                    >
                        <TableCell>
                            <Link
                                :href="`/projects/${project.id}/submittals/${submittal.id}`"
                                class="font-medium text-foreground hover:underline"
                            >
                                {{ submittal.title }}
                            </Link>
                        </TableCell>
                        <TableCell class="text-muted-foreground">
                            {{ submittal.spec_section ?? '—' }}
                        </TableCell>
                        <TableCell class="text-muted-foreground">
                            Rev. {{ submittal.revision_number }}
                        </TableCell>
                        <TableCell>
                            <Badge :variant="statusVariant(submittal.status)">
                                {{ statusLabel(submittal.status) }}
                            </Badge>
                        </TableCell>
                        <TableCell class="text-muted-foreground">
                            {{ submittal.due_date ?? '—' }}
                        </TableCell>
                        <TableCell class="text-muted-foreground">
                            {{ submittal.assigned_to ?? '—' }}
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </div>

        <EmptyState
            v-else-if="submittals.length > 0 && filteredSubmittals.length === 0"
            :icon="Search"
            title="No submittals found"
            description="No submittals match your current filters."
        >
            <template #action>
                <Button variant="outline" @click="clearFilters">
                    Clear filters
                </Button>
            </template>
        </EmptyState>

        <EmptyState
            v-else
            :icon="FileText"
            title="No submittals yet"
            description="Create your first submittal to start tracking approvals and revisions."
        >
            <template #action>
                <Button as-child>
                    <Link :href="createUrl">
                        <Plus class="mr-1 h-4 w-4" />
                        New submittal
                    </Link>
                </Button>
            </template>
        </EmptyState>
    </div>
</template>
