<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    AlertCircle,
    CheckCircle2,
    Clock,
    ExternalLink,
    FileText,
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

interface SubmittalItem {
    id: number;
    title: string;
    spec_section: string | null;
    revision_number: number;
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
    submittals: SubmittalItem[];
    projects: ProjectOption[];
}>();

const searchQuery = ref('');
const statusFilter = ref<string>('all');
const projectFilter = ref<string>('all');

const filteredSubmittals = computed(() => {
    let result = props.submittals;

    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase();
        result = result.filter(
            (s) =>
                s.title.toLowerCase().includes(query) ||
                (s.spec_section &&
                    s.spec_section.toLowerCase().includes(query)) ||
                s.project.name.toLowerCase().includes(query) ||
                s.project.project_number.toLowerCase().includes(query),
        );
    }

    if (statusFilter.value !== 'all') {
        result = result.filter((s) => s.status === statusFilter.value);
    }

    if (projectFilter.value !== 'all') {
        const pId = Number(projectFilter.value);
        result = result.filter((s) => s.project.id === pId);
    }

    return result;
});

const stats = computed(() => {
    const total = props.submittals.length;
    const pending = props.submittals.filter(
        (s) => s.status === 'pending' || s.status === 'in_review',
    ).length;
    const approved = props.submittals.filter(
        (s) => s.status === 'approved',
    ).length;
    const revision = props.submittals.filter(
        (s) => s.status === 'revision' || s.status === 'rejected',
    ).length;

    return { total, pending, approved, revision };
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
                title: 'Submittals',
                href: '/submittals',
            },
        ],
    }),
});
</script>

<template>
    <Head title="Submittals" />

    <h1 class="sr-only">Submittals</h1>

    <div
        class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4"
    >
        <div class="flex items-center justify-between">
            <Heading
                variant="small"
                title="Submittals"
                description="Track and manage material and equipment submittals across all your projects"
            />
        </div>

        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <StatCard
                :icon="FileText"
                label="Total Submittals"
                :value="stats.total"
                icon-bg-class="bg-blue-50 dark:bg-blue-950"
                icon-class="text-blue-600 dark:text-blue-400"
            />
            <StatCard
                :icon="Clock"
                label="Pending Review"
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
                label="Revision Required"
                :value="stats.revision"
                icon-bg-class="bg-rose-50 dark:bg-rose-950"
                icon-class="text-rose-600 dark:text-rose-400"
            />
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
                    placeholder="Search submittals or projects..."
                    class="pl-9"
                    aria-label="Search submittals"
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
                        <SelectItem value="revision"
                            >Revision Required</SelectItem
                        >
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
            v-if="submittals.length > 0 && filteredSubmittals.length > 0"
            class="rounded-lg border"
        >
            <Table aria-label="Submittals">
                <TableHeader>
                    <TableRow>
                        <TableHead>Title</TableHead>
                        <TableHead>Project</TableHead>
                        <TableHead>Spec Section</TableHead>
                        <TableHead>Revision</TableHead>
                        <TableHead>Status</TableHead>
                        <TableHead>Due Date</TableHead>
                        <TableHead>Assigned To</TableHead>
                        <TableHead class="text-right">Actions</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableRow
                        v-for="submittal in filteredSubmittals"
                        :key="submittal.id"
                    >
                        <TableCell>
                            <Link
                                :href="`/projects/${submittal.project.id}/submittals/${submittal.id}`"
                                class="font-medium text-foreground hover:underline"
                            >
                                {{ submittal.title }}
                            </Link>
                        </TableCell>
                        <TableCell>
                            <Link
                                :href="`/projects/${submittal.project.id}`"
                                class="text-sm text-muted-foreground hover:text-foreground hover:underline"
                            >
                                {{ submittal.project.name }}
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
                        <TableCell class="text-right">
                            <Button variant="ghost" size="sm" as-child>
                                <Link
                                    :href="`/projects/${submittal.project.id}/submittals/${submittal.id}`"
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
            v-else-if="submittals.length > 0 && filteredSubmittals.length === 0"
            class="flex flex-col items-center justify-center rounded-lg border border-dashed px-6 py-16 text-center"
        >
            <div
                class="mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-muted"
            >
                <Search class="h-6 w-6 text-muted-foreground/60" />
            </div>
            <h3 class="mb-1 text-sm font-medium">No submittals found</h3>
            <p class="mb-6 max-w-sm text-sm text-muted-foreground">
                No submittals match your current search and filter settings.
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
                <FileText class="h-6 w-6 text-muted-foreground/60" />
            </div>
            <h3 class="mb-1 text-sm font-medium">No submittals yet</h3>
            <p class="mb-6 max-w-sm text-sm text-muted-foreground">
                Submittals created within your projects will appear here for
                centralized tracking.
            </p>
            <Button as-child>
                <Link href="/projects"> View Projects </Link>
            </Button>
        </div>
    </div>
</template>
