<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    ArrowLeft,
    FileText,
    MessageSquare,
    Paperclip,
    Pencil,
    Send,
} from '@lucide/vue';
import { computed } from 'vue';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';

const props = defineProps<{
    project: { id: number; name: string };
    rfi: {
        id: number;
        title: string;
        question: string;
        response: string | null;
        status: string;
        due_date: string | null;
        responded_at: string | null;
        created_at: string;
        assignee: { id: number; name: string } | null;
        creator: { id: number; name: string };
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
        pending: 'Pending',
        in_review: 'In Review',
        approved: 'Responded',
        rejected: 'Rejected',
        revision: 'Revision Required',
        cancelled: 'Cancelled',
    };

    return labels[status] ?? status;
};

const canSubmit = computed(() => props.rfi.status === 'draft');

const submitForResponse = () => {
    router.post(`/projects/${props.project.id}/rfis/${props.rfi.id}/submit`);
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
    <Head :title="`${rfi.title} - RFI`" />

    <h1 class="sr-only">{{ rfi.title }}</h1>

    <div
        class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4"
    >
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <Button variant="ghost" size="sm" as-child>
                    <Link :href="`/projects/${project.id}/rfis`">
                        <ArrowLeft class="mr-1 h-4 w-4" />
                        Back
                    </Link>
                </Button>
                <Heading
                    variant="small"
                    :title="rfi.title"
                    :description="`Created by ${rfi.creator.name} · ${project.name}`"
                />
            </div>

            <div class="flex items-center gap-2">
                <Button
                    v-if="canSubmit"
                    variant="default"
                    size="sm"
                    @click="submitForResponse"
                >
                    <Send class="mr-1 h-4 w-4" />
                    Submit
                </Button>
                <Button variant="outline" size="sm" as-child>
                    <Link :href="`/projects/${project.id}/rfis/${rfi.id}/edit`">
                        <Pencil class="mr-1 h-4 w-4" />
                        Edit
                    </Link>
                </Button>
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-3">
            <Card class="lg:col-span-2">
                <CardHeader>
                    <CardTitle>RFI Details</CardTitle>
                </CardHeader>
                <CardContent>
                    <dl class="space-y-4">
                        <div>
                            <dt class="text-sm text-muted-foreground">
                                Status
                            </dt>
                            <dd class="mt-1">
                                <Badge :variant="statusVariant(rfi.status)">
                                    {{ statusLabel(rfi.status) }}
                                </Badge>
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm text-muted-foreground">
                                Question
                            </dt>
                            <dd class="mt-1 text-sm whitespace-pre-wrap">
                                {{ rfi.question }}
                            </dd>
                        </div>
                        <div v-if="rfi.response">
                            <dt class="text-sm text-muted-foreground">
                                Response
                            </dt>
                            <dd class="mt-1 text-sm whitespace-pre-wrap">
                                {{ rfi.response }}
                            </dd>
                        </div>
                    </dl>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Details</CardTitle>
                </CardHeader>
                <CardContent>
                    <dl class="space-y-3">
                        <div>
                            <dt class="text-sm text-muted-foreground">
                                Due Date
                            </dt>
                            <dd class="mt-1 text-sm">
                                {{ rfi.due_date ?? '—' }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm text-muted-foreground">
                                Assigned To
                            </dt>
                            <dd class="mt-1 text-sm">
                                {{ rfi.assignee?.name ?? '—' }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm text-muted-foreground">
                                Created
                            </dt>
                            <dd class="mt-1 text-sm">
                                {{
                                    new Date(
                                        rfi.created_at,
                                    ).toLocaleDateString()
                                }}
                            </dd>
                        </div>
                        <div v-if="rfi.responded_at">
                            <dt class="text-sm text-muted-foreground">
                                Responded
                            </dt>
                            <dd class="mt-1 text-sm">
                                {{
                                    new Date(
                                        rfi.responded_at,
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
                        {{ rfi.comments.length }}
                    </Badge>
                </TabsTrigger>
                <TabsTrigger value="attachments">
                    <Paperclip class="mr-1 h-4 w-4" />
                    Attachments
                    <Badge variant="secondary" class="ml-1.5 text-xs">
                        {{ rfi.attachments.length }}
                    </Badge>
                </TabsTrigger>
            </TabsList>

            <TabsContent value="comments">
                <Card>
                    <CardContent class="pt-6">
                        <div v-if="rfi.comments.length > 0" class="space-y-4">
                            <div
                                v-for="comment in rfi.comments"
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
                            v-if="rfi.attachments.length > 0"
                            class="space-y-2"
                        >
                            <div
                                v-for="attachment in rfi.attachments"
                                :key="attachment.id"
                                class="flex items-center justify-between rounded-lg border p-3"
                            >
                                <div class="flex items-center gap-3">
                                    <FileText
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
                                    <a :href="attachment.url" target="_blank">
                                        Download
                                    </a>
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
