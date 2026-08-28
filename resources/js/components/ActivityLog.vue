<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { Clock, User } from '@lucide/vue';
import { computed, ref } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import type { ActivityLogEntry } from '@/types/projects';

const props = defineProps<{
    activities: {
        data: ActivityLogEntry[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
    filters?: {
        search?: string;
        event?: string;
    };
    events?: string[];
    showSearch?: boolean;
    showProjectColumn?: boolean;
}>();

const search = ref(props.filters?.search ?? '');
const selectedEvent = ref(props.filters?.event ?? '');

const eventLabel = (event: string | null) => {
    if (!event) return '—';
    return event.split('.').map((word) => word.charAt(0).toUpperCase() + word.slice(1)).join(' ');
};

const formatProperties = (properties: Record<string, unknown>) => {
    const attrs = properties.attributes as Record<string, unknown> | undefined;
    const old = properties.old as Record<string, unknown> | undefined;

    if (!attrs && !old) return null;

    const changes: string[] = [];

    if (old && attrs) {
        for (const [key, value] of Object.entries(attrs)) {
            if (old[key] !== undefined && old[key] !== value) {
                changes.push(`${key}: ${old[key]} → ${value}`);
            }
        }
    } else if (attrs) {
        changes.push(Object.keys(attrs).join(', '));
    }

    return changes.length > 0 ? changes.join('; ') : null;
};

const applyFilters = () => {
    router.get(
        route('activity-log.index'),
        {
            search: search.value || undefined,
            event: selectedEvent.value || undefined,
        },
        { preserveState: true, replace: true },
    );
};

const clearFilters = () => {
    search.value = '';
    selectedEvent.value = '';
    applyFilters();
};
</script>

<template>
    <Card>
        <CardHeader class="flex flex-row items-center justify-between">
            <CardTitle class="flex items-center gap-2">
                <Clock class="size-5" />
                Activity Log
                <Badge variant="secondary" class="ml-1 text-xs">
                    {{ activities.total }}
                </Badge>
            </CardTitle>
        </CardHeader>
        <CardContent>
            <div v-if="showSearch !== false" class="mb-4 flex flex-col gap-3 sm:flex-row">
                <Input
                    v-model="search"
                    placeholder="Search activities..."
                    class="max-w-sm"
                    @keyup.enter="applyFilters"
                />
                <select
                    v-model="selectedEvent"
                    class="rounded-md border bg-background px-3 py-2 text-sm"
                    @change="applyFilters"
                >
                    <option value="">All events</option>
                    <option v-for="evt in events" :key="evt" :value="evt">
                        {{ eventLabel(evt) }}
                    </option>
                </select>
                <Button
                    v-if="search || selectedEvent"
                    variant="ghost"
                    size="sm"
                    @click="clearFilters"
                >
                    Clear
                </Button>
            </div>

            <div v-if="activities.data.length > 0">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Event</TableHead>
                            <TableHead>Description</TableHead>
                            <TableHead>User</TableHead>
                            <TableHead>Changes</TableHead>
                            <TableHead class="text-right">Time</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="activity in activities.data" :key="activity.id">
                            <TableCell>
                                <Badge variant="outline" class="text-xs">
                                    {{ eventLabel(activity.event) }}
                                </Badge>
                            </TableCell>
                            <TableCell class="max-w-xs truncate text-sm">
                                {{ activity.description }}
                            </TableCell>
                            <TableCell>
                                <div v-if="activity.causer" class="flex items-center gap-1.5">
                                    <User class="size-3.5 text-muted-foreground" />
                                    <span class="text-sm">{{ activity.causer.name }}</span>
                                </div>
                                <span v-else class="text-sm text-muted-foreground">System</span>
                            </TableCell>
                            <TableCell class="max-w-xs">
                                <span v-if="formatProperties(activity.properties)" class="text-xs text-muted-foreground">
                                    {{ formatProperties(activity.properties) }}
                                </span>
                                <span v-else class="text-xs text-muted-foreground">—</span>
                            </TableCell>
                            <TableCell class="text-right text-xs text-muted-foreground">
                                {{ new Date(activity.created_at).toLocaleString() }}
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>
            <p v-else class="py-8 text-center text-sm text-muted-foreground">
                No activity recorded yet.
            </p>
        </CardContent>
    </Card>
</template>
