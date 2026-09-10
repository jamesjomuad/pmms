<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    ArrowLeft,
    CheckSquare,
    MessageSquare,
    Paperclip,
    Pencil,
} from '@lucide/vue';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';

const props = defineProps<{
    project: { id: number; name: string };
    punchListItem: {
        id: number;
        title: string;
        description: string | null;
        location: string | null;
        trade: string | null;
        priority: string;
        status: string;
        due_date: string | null;
        resolved_at: string | null;
        resolution_notes: string | null;
        created_at: string;
        assignee: { id: number; name: string } | null;
        comments: {
            id: number;
            body: string;
            user: { id: number; name: string };
            created_at: string;
        }[];
        attachments: {
            id: number;
            name: string;
            human_size: string;
            url: string;
        }[];
    };
}>();

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
        pending: 'Open',
        in_review: 'In Review',
        approved: 'Resolved',
        rejected: 'Rejected',
        cancelled: 'Cancelled',
    };

    return labels[status] ?? status;
};

const priorityVariant = (priority: string) => {
    switch (priority) {
        case 'critical':
            return 'destructive';
        case 'high':
            return 'default';
        case 'medium':
            return 'secondary';
        case 'low':
            return 'outline';
        default:
            return 'secondary';
    }
};

const resolveItem = () => {
    router.post(
        `/projects/${props.project.id}/punch-list/${props.punchListItem.id}/resolve`,
    );
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
    <Head :title="`${punchListItem.title} - Punch List`" />

    <h1 class="sr-only">{{ punchListItem.title }}</h1>

    <div
        class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4"
    >
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <Button variant="ghost" size="sm" as-child>
                    <Link :href="`/projects/${project.id}/punch-list`">
                        <ArrowLeft class="mr-1 h-4 w-4" />
                        Back
                    </Link>
                </Button>
                <Heading
                    variant="small"
                    :title="punchListItem.title"
                    :description="`${punchListItem.location ?? 'No location'} · ${project.name}`"
                />
            </div>

            <div class="flex items-center gap-2">
                <Button
                    v-if="punchListItem.status === 'pending'"
                    variant="default"
                    size="sm"
                    @click="resolveItem"
                >
                    <CheckSquare class="mr-1 h-4 w-4" />
                    Mark as Resolved
                </Button>
                <Button variant="outline" size="sm" as-child>
                    <Link
                        :href="`/projects/${project.id}/punch-list/${punchListItem.id}/edit`"
                    >
                        <Pencil class="mr-1 h-4 w-4" />
                        Edit
                    </Link>
                </Button>
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-3">
            <Card class="lg:col-span-2">
                <CardHeader>
                    <CardTitle>Item Details</CardTitle>
                </CardHeader>
                <CardContent>
                    <dl class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <dt class="text-sm text-muted-foreground">
                                Status
                            </dt>
                            <dd class="mt-1">
                                <Badge
                                    :variant="
                                        statusVariant(punchListItem.status)
                                    "
                                >
                                    {{ statusLabel(punchListItem.status) }}
                                </Badge>
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm text-muted-foreground">
                                Priority
                            </dt>
                            <dd class="mt-1">
                                <Badge
                                    :variant="
                                        priorityVariant(punchListItem.priority)
                                    "
                                >
                                    {{ punchListItem.priority }}
                                </Badge>
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm text-muted-foreground">
                                Location
                            </dt>
                            <dd class="mt-1 text-sm">
                                {{ punchListItem.location ?? '—' }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm text-muted-foreground">Trade</dt>
                            <dd class="mt-1 text-sm">
                                {{ punchListItem.trade ?? '—' }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm text-muted-foreground">
                                Due Date
                            </dt>
                            <dd class="mt-1 text-sm">
                                {{ punchListItem.due_date ?? '—' }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm text-muted-foreground">
                                Assigned To
                            </dt>
                            <dd class="mt-1 text-sm">
                                {{ punchListItem.assignee?.name ?? '—' }}
                            </dd>
                        </div>
                        <div
                            v-if="punchListItem.description"
                            class="sm:col-span-2"
                        >
                            <dt class="text-sm text-muted-foreground">
                                Description
                            </dt>
                            <dd class="mt-1 text-sm whitespace-pre-wrap">
                                {{ punchListItem.description }}
                            </dd>
                        </div>
                        <div
                            v-if="punchListItem.resolution_notes"
                            class="sm:col-span-2"
                        >
                            <dt class="text-sm text-muted-foreground">
                                Resolution Notes
                            </dt>
                            <dd class="mt-1 text-sm whitespace-pre-wrap">
                                {{ punchListItem.resolution_notes }}
                            </dd>
                        </div>
                    </dl>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Timeline</CardTitle>
                </CardHeader>
                <CardContent>
                    <dl class="space-y-3">
                        <div>
                            <dt class="text-sm text-muted-foreground">
                                Created
                            </dt>
                            <dd class="mt-1 text-sm">
                                {{
                                    new Date(
                                        punchListItem.created_at,
                                    ).toLocaleDateString()
                                }}
                            </dd>
                        </div>
                        <div v-if="punchListItem.resolved_at">
                            <dt class="text-sm text-muted-foreground">
                                Resolved
                            </dt>
                            <dd class="mt-1 text-sm">
                                {{
                                    new Date(
                                        punchListItem.resolved_at,
                                    ).toLocaleDateString()
                                }}
                            </dd>
                        </div>
                    </dl>
                </CardContent>
            </Card>
        </div>

        <Tabs default-value="comments">
            <TabsList>
                <TabsTrigger value="comments">
                    <MessageSquare class="mr-1 h-4 w-4" />
                    Comments
                    <Badge variant="secondary" class="ml-1.5 text-xs">
                        {{ punchListItem.comments.length }}
                    </Badge>
                </TabsTrigger>
                <TabsTrigger value="attachments">
                    <Paperclip class="mr-1 h-4 w-4" />
                    Attachments
                    <Badge variant="secondary" class="ml-1.5 text-xs">
                        {{ punchListItem.attachments.length }}
                    </Badge>
                </TabsTrigger>
            </TabsList>

            <TabsContent value="comments">
                <Card>
                    <CardContent class="pt-6">
                        <div
                            v-if="punchListItem.comments.length > 0"
                            class="space-y-4"
                        >
                            <div
                                v-for="comment in punchListItem.comments"
                                :key="comment.id"
                                class="rounded-lg border p-4"
                            >
                                <div class="flex items-center justify-between">
                                    <p class="text-sm font-medium">
                                        {{ comment.user.name }}
                                    </p>
                                    <p class="text-xs text-muted-foreground">
                                        {{
                                            new Date(
                                                comment.created_at,
                                            ).toLocaleString()
                                        }}
                                    </p>
                                </div>
                                <p class="mt-2 text-sm whitespace-pre-wrap">
                                    {{ comment.body }}
                                </p>
                            </div>
                        </div>
                        <p v-else class="text-sm text-muted-foreground">
                            No comments yet.
                        </p>
                    </CardContent>
                </Card>
            </TabsContent>

            <TabsContent value="attachments">
                <Card>
                    <CardContent class="pt-6">
                        <div
                            v-if="punchListItem.attachments.length > 0"
                            class="space-y-2"
                        >
                            <div
                                v-for="attachment in punchListItem.attachments"
                                :key="attachment.id"
                                class="flex items-center justify-between rounded-lg border p-3"
                            >
                                <div class="flex items-center gap-3">
                                    <CheckSquare
                                        class="h-4 w-4 text-muted-foreground"
                                    />
                                    <div>
                                        <p class="text-sm font-medium">
                                            {{ attachment.name }}
                                        </p>
                                        <p
                                            class="text-xs text-muted-foreground"
                                        >
                                            {{ attachment.human_size }}
                                        </p>
                                    </div>
                                </div>
                                <Button variant="ghost" size="sm" as-child>
                                    <a :href="attachment.url" target="_blank"
                                        >Download</a
                                    >
                                </Button>
                            </div>
                        </div>
                        <p v-else class="text-sm text-muted-foreground">
                            No attachments.
                        </p>
                    </CardContent>
                </Card>
            </TabsContent>
        </Tabs>
    </div>
</template>
