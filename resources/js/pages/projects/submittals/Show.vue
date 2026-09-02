<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    ArrowLeft,
    CheckCircle2,
    Clock,
    FileText,
    MessageSquare,
    Paperclip,
    Pencil,
    RefreshCw,
    Send,
    XCircle,
} from '@lucide/vue';
import { computed } from 'vue';
import ApprovalWorkflow from '@/components/ApprovalWorkflow.vue';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import type { SubmittalDetail } from '@/types';

const props = defineProps<{
    project: { id: number; name: string };
    submittal: SubmittalDetail;
}>();

const statusVariant = (status: string) => {
    switch (status) {
        case 'approved':
            return 'default';
        case 'pending':
        case 'in_review':
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
        approved: 'Approved',
        rejected: 'Rejected',
        revision: 'Revision Required',
        cancelled: 'Cancelled',
    };

    return labels[status] ?? status;
};

const canSubmit = computed(
    () =>
        props.submittal.status === 'draft' ||
        props.submittal.status === 'revision',
);

const canApprove = computed(
    () =>
        props.submittal.status === 'pending' ||
        props.submittal.status === 'in_review',
);

const canReject = computed(
    () =>
        props.submittal.status === 'pending' ||
        props.submittal.status === 'in_review',
);

const canCreateRevision = computed(
    () => props.submittal.status === 'rejected',
);

const submitForReview = () => {
    router.post(`/projects/${props.project.id}/submittals/${props.submittal.id}/submit`);
};

const approve = () => {
    router.post(`/projects/${props.project.id}/submittals/${props.submittal.id}/approve`);
};

const requestRevision = () => {
    router.post(`/projects/${props.project.id}/submittals/${props.submittal.id}/request-revision`);
};

const newRevision = () => {
    router.post(`/projects/${props.project.id}/submittals/${props.submittal.id}/new-revision`);
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
    <Head :title="`${submittal.title} - Submittal`" />

    <h1 class="sr-only">{{ submittal.title }}</h1>

    <div
        class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4"
    >
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <Button variant="ghost" size="sm" as-child>
                    <Link :href="`/projects/${project.id}/submittals`">
                        <ArrowLeft class="mr-1 h-4 w-4" />
                        Back
                    </Link>
                </Button>
                <Heading
                    variant="small"
                    :title="submittal.title"
                    :description="`Rev. ${submittal.revision_number} · ${project.name}`"
                />
            </div>

            <div class="flex items-center gap-2">
                <Button
                    v-if="canSubmit"
                    variant="default"
                    size="sm"
                    @click="submitForReview"
                >
                    <Send class="mr-1 h-4 w-4" />
                    Submit for Review
                </Button>
                <Button
                    v-if="canApprove"
                    variant="default"
                    size="sm"
                    @click="approve"
                >
                    <CheckCircle2 class="mr-1 h-4 w-4" />
                    Approve
                </Button>
                <Button
                    v-if="canReject"
                    variant="destructive"
                    size="sm"
                    @click="requestRevision"
                >
                    <XCircle class="mr-1 h-4 w-4" />
                    Request Revision
                </Button>
                <Button
                    v-if="canCreateRevision"
                    variant="default"
                    size="sm"
                    @click="newRevision"
                >
                    <RefreshCw class="mr-1 h-4 w-4" />
                    New Revision
                </Button>
                <Button variant="outline" size="sm" as-child>
                    <Link
                        :href="`/projects/${project.id}/submittals/${submittal.id}/edit`"
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
                    <CardTitle>Submittal Details</CardTitle>
                </CardHeader>
                <CardContent>
                    <dl class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <dt class="text-sm text-muted-foreground">Status</dt>
                            <dd class="mt-1">
                                <Badge :variant="statusVariant(submittal.status)">
                                    {{ statusLabel(submittal.status) }}
                                </Badge>
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm text-muted-foreground">
                                Spec Section
                            </dt>
                            <dd class="mt-1 text-sm">
                                {{ submittal.spec_section ?? '—' }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm text-muted-foreground">
                                Due Date
                            </dt>
                            <dd class="mt-1 text-sm">
                                {{ submittal.due_date ?? '—' }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm text-muted-foreground">
                                Assigned To
                            </dt>
                            <dd class="mt-1 text-sm">
                                {{ submittal.assignee?.name ?? '—' }}
                            </dd>
                        </div>
                        <div v-if="submittal.description" class="sm:col-span-2">
                            <dt class="text-sm text-muted-foreground">
                                Description
                            </dt>
                            <dd class="mt-1 text-sm whitespace-pre-wrap">
                                {{ submittal.description }}
                            </dd>
                        </div>
                        <div
                            v-if="submittal.rejection_reason"
                            class="sm:col-span-2"
                        >
                            <dt class="text-sm text-muted-foreground">
                                Rejection Reason
                            </dt>
                            <dd class="mt-1 text-sm text-destructive">
                                {{ submittal.rejection_reason }}
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
                    <ul class="space-y-3">
                        <li class="flex items-start gap-3">
                            <div
                                class="mt-1 h-2 w-2 shrink-0 rounded-full bg-muted-foreground/30"
                            />
                            <div>
                                <p class="text-sm">Created</p>
                                <p class="text-xs text-muted-foreground">
                                    {{
                                        new Date(
                                            submittal.created_at,
                                        ).toLocaleDateString()
                                    }}
                                </p>
                            </div>
                        </li>
                        <li v-if="submittal.submitted_at" class="flex items-start gap-3">
                            <div
                                class="mt-1 h-2 w-2 shrink-0 rounded-full bg-primary"
                            />
                            <div>
                                <p class="text-sm">Submitted for Review</p>
                                <p class="text-xs text-muted-foreground">
                                    {{
                                        new Date(
                                            submittal.submitted_at,
                                        ).toLocaleDateString()
                                    }}
                                </p>
                            </div>
                        </li>
                        <li v-if="submittal.approved_at" class="flex items-start gap-3">
                            <div
                                class="mt-1 h-2 w-2 shrink-0 rounded-full bg-emerald-500"
                            />
                            <div>
                                <p class="text-sm">Approved</p>
                                <p class="text-xs text-muted-foreground">
                                    {{
                                        new Date(
                                            submittal.approved_at,
                                        ).toLocaleDateString()
                                    }}
                                </p>
                            </div>
                        </li>
                    </ul>
                </CardContent>
            </Card>
        </div>

        <Tabs default-value="comments">
            <TabsList>
                <TabsTrigger value="comments">
                    <MessageSquare class="mr-1 h-4 w-4" />
                    Comments
                    <Badge variant="secondary" class="ml-1.5 text-xs">
                        {{ submittal.comments.length }}
                    </Badge>
                </TabsTrigger>
                <TabsTrigger value="attachments">
                    <Paperclip class="mr-1 h-4 w-4" />
                    Attachments
                    <Badge variant="secondary" class="ml-1.5 text-xs">
                        {{ submittal.attachments.length }}
                    </Badge>
                </TabsTrigger>
                <TabsTrigger value="approvals">
                    <CheckCircle2 class="mr-1 h-4 w-4" />
                    Approval History
                    <Badge variant="secondary" class="ml-1.5 text-xs">
                        {{ submittal.approval_requests.length }}
                    </Badge>
                </TabsTrigger>
            </TabsList>

            <TabsContent value="comments">
                <Card>
                    <CardContent class="pt-6">
                        <div
                            v-if="submittal.comments.length > 0"
                            class="space-y-4"
                        >
                            <div
                                v-for="comment in submittal.comments"
                                :key="comment.id"
                                class="rounded-lg border p-4"
                            >
                                <div
                                    class="flex items-center justify-between"
                                >
                                    <p class="text-sm font-medium">
                                        {{ comment.user.name }}
                                    </p>
                                    <p
                                        class="text-xs text-muted-foreground"
                                    >
                                        {{
                                            new Date(
                                                comment.created_at,
                                            ).toLocaleString()
                                        }}
                                    </p>
                                </div>
                                <p
                                    class="mt-2 text-sm whitespace-pre-wrap"
                                >
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
                            v-if="submittal.attachments.length > 0"
                            class="space-y-2"
                        >
                            <div
                                v-for="attachment in submittal.attachments"
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
                                    <a
                                        :href="attachment.url"
                                        target="_blank"
                                    >
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

            <TabsContent value="approvals">
                <Card>
                    <CardContent class="pt-6">
                        <div
                            v-if="submittal.approval_requests.length > 0"
                            class="space-y-6"
                        >
                            <div
                                v-for="request in submittal.approval_requests"
                                :key="request.id"
                                class="rounded-lg border p-4"
                            >
                                <div
                                    class="flex items-center justify-between"
                                >
                                    <p class="text-sm font-medium">
                                        Requested by
                                        {{ request.requested_by }}
                                    </p>
                                    <Badge
                                        :variant="
                                            statusVariant(request.status)
                                        "
                                    >
                                        {{ statusLabel(request.status) }}
                                    </Badge>
                                </div>
                                <div class="mt-4">
                                    <ApprovalWorkflow :steps="request.steps" />
                                </div>
                            </div>
                        </div>
                        <p v-else class="text-sm text-muted-foreground">
                            No approval requests yet.
                        </p>
                    </CardContent>
                </Card>
            </TabsContent>
        </Tabs>
    </div>
</template>
