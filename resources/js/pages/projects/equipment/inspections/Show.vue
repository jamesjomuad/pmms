<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, Pencil } from '@lucide/vue';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

defineProps<{
    project: { id: number; name: string };
    equipment: { id: number; title: string };
    inspection: {
        id: number;
        inspected_date: string | null;
        result: string;
        notes: string | null;
        created_at: string;
        inspector: { id: number; name: string } | null;
    };
}>();

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
    <Head :title="`Inspection - ${equipment.title}`" />

    <h1 class="sr-only">Equipment Inspection</h1>

    <div
        class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4"
    >
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <Button variant="ghost" size="sm" as-child>
                    <Link
                        :href="`/projects/${project.id}/equipment/${equipment.id}/inspections`"
                    >
                        <ArrowLeft class="mr-1 h-4 w-4" />
                        Back
                    </Link>
                </Button>
                <Heading
                    variant="small"
                    title="Inspection"
                    :description="`${equipment.title} · ${project.name}`"
                />
            </div>

            <Button variant="outline" size="sm" as-child>
                <Link
                    :href="`/projects/${project.id}/equipment/${equipment.id}/inspections/${inspection.id}/edit`"
                >
                    <Pencil class="mr-1 h-4 w-4" />
                    Edit
                </Link>
            </Button>
        </div>

        <Card class="max-w-2xl">
            <CardHeader>
                <CardTitle>Inspection Details</CardTitle>
            </CardHeader>
            <CardContent>
                <dl class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <dt class="text-sm text-muted-foreground">Result</dt>
                        <dd class="mt-1">
                            <Badge :variant="resultVariant(inspection.result)">
                                {{ resultLabel(inspection.result) }}
                            </Badge>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm text-muted-foreground">Inspector</dt>
                        <dd class="mt-1 text-sm">
                            {{ inspection.inspector?.name ?? '—' }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm text-muted-foreground">
                            Inspection Date
                        </dt>
                        <dd class="mt-1 text-sm">
                            {{ inspection.inspected_date ?? '—' }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm text-muted-foreground">Created</dt>
                        <dd class="mt-1 text-sm">
                            {{
                                new Date(
                                    inspection.created_at,
                                ).toLocaleDateString()
                            }}
                        </dd>
                    </div>
                    <div v-if="inspection.notes" class="sm:col-span-2">
                        <dt class="text-sm text-muted-foreground">Notes</dt>
                        <dd class="mt-1 text-sm whitespace-pre-wrap">
                            {{ inspection.notes }}
                        </dd>
                    </div>
                </dl>
            </CardContent>
        </Card>
    </div>
</template>
