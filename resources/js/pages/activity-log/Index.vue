<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import ActivityLog from '@/components/ActivityLog.vue';
import Heading from '@/components/Heading.vue';
import type { ActivityLogEntry } from '@/types/projects';

defineProps<{
    activities: {
        data: ActivityLogEntry[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
    filters: {
        search?: string;
        event?: string;
    };
    events: string[];
}>();

defineOptions({
    layout: () => ({
        breadcrumbs: [
            {
                title: 'Activity Log',
                href: '/activity-log',
            },
        ],
    }),
});
</script>

<template>
    <Head title="Activity Log" />

    <div
        class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4"
    >
        <Heading
            variant="small"
            title="Activity Log"
            description="Track all changes across projects"
        />

        <ActivityLog
            :activities="activities"
            :filters="filters"
            :events="events"
        />
    </div>
</template>
