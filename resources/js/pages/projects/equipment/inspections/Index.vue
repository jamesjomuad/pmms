<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, ClipboardCheck, Plus } from '@lucide/vue';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { create as inspectionCreate } from '@/routes/projects/equipment/inspections';

const props = defineProps<{
    project: { id: number; name: string };
    equipment: { id: number; title: string };
    inspections: {
        id: number;
        inspected_date: string | null;
        result: string;
        notes: string | null;
        inspector: { id: number; name: string } | null;
    }[];
}>();

const createUrl = inspectionCreate({
    project: props.project.id,
    equipment: props.equipment.id,
}).url;

const resultVariant = (result: string) => {
    switch (result) {
        case 'passed':
            return 'default';
        case 'failed':
            return 'destructive';
        case 'damaged':
            return 'destructive';
        case 'wrong_item':
            return 'secondary';
        default:
            return 'secondary';
    }
};

const resultLabel = (result: string) => {
    const labels: Record<string, string> = {
        passed: 'Passed',
        failed: 'Failed',
        damaged: 'Damaged',
        wrong_item: 'Wrong Item',
    };

    return labels[result] ?? result;
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
    <Head :title="`Inspections - ${equipment.title}`" />

    <h1 class="sr-only">Equipment Inspections</h1>

    <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4">
        <div>
            <Button variant="ghost" size="sm" as-child>
                <Link :href="`/projects/${project.id}/equipment/${equipment.id}`">
                    <ArrowLeft class="mr-1 h-4 w-4" />
                    Equipment
                </Link>
            </Button>
        </div>

        <div class="flex items-center justify-between">
            <Heading
                variant="small"
                title="Inspections"
                :description="`Inspect delivered equipment for ${equipment.title}`"
            />

            <Button as-child>
                <Link :href="createUrl">
                    <Plus class="mr-1 h-4 w-4" />
                    Add inspection
                </Link>
            </Button>
        </div>

        <div v-if="inspections.length > 0" class="rounded-lg border">
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>Inspected On</TableHead>
                        <TableHead>Inspector</TableHead>
                        <TableHead>Result</TableHead>
                        <TableHead>Notes</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableRow v-for="inspection in inspections" :key="inspection.id">
                        <TableCell>
                            <Link
                                :href="`/projects/${project.id}/equipment/${equipment.id}/inspections/${inspection.id}`"
                                class="font-medium text-foreground hover:underline"
                            >
                                {{ inspection.inspected_date ?? '—' }}
                            </Link>
                        </TableCell>
                        <TableCell class="text-muted-foreground">
                            {{ inspection.inspector?.name ?? '—' }}
                        </TableCell>
                        <TableCell>
                            <Badge :variant="resultVariant(inspection.result)">
                                {{ resultLabel(inspection.result) }}
                            </Badge>
                        </TableCell>
                        <TableCell class="max-w-xs truncate text-muted-foreground">
                            {{ inspection.notes ?? '—' }}
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </div>

        <div
            v-else
            class="flex flex-col items-center justify-center rounded-lg border border-dashed px-6 py-16 text-center"
        >
            <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-muted">
                <ClipboardCheck class="h-6 w-6 text-muted-foreground/60" />
            </div>
            <h3 class="mb-1 text-sm font-medium">No inspections yet</h3>
            <p class="mb-6 max-w-sm text-sm text-muted-foreground">
                Record inspection results once delivered equipment arrives.
            </p>
            <Button as-child>
                <Link :href="createUrl">
                    <Plus class="mr-1 h-4 w-4" />
                    Add inspection
                </Link>
            </Button>
        </div>
    </div>
</template>
