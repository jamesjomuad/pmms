<script setup lang="ts">
import { router, useForm } from '@inertiajs/vue3';
import { Send, Trash2 } from '@lucide/vue';
import { computed } from 'vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Textarea } from '@/components/ui/textarea';
import type { Comment } from '@/types/projects';

const props = defineProps<{
    projectId: number;
    commentableType: string;
    commentableId: number;
    comments: Comment[];
    authUserId: number;
}>();

const sortedComments = computed(() =>
    [...props.comments].sort(
        (a, b) => Date.parse(b.created_at) - Date.parse(a.created_at),
    ),
);

const form = useForm({
    commentable_type: props.commentableType,
    commentable_id: props.commentableId,
    body: '',
});

const canSubmit = computed(() => form.body.trim() !== '');

const addComment = () => {
    form.post(`/projects/${props.projectId}/comments`, {
        preserveScroll: true,
        onSuccess: () => form.reset('body'),
    });
};

const removeComment = (comment: Comment) => {
    router.delete(`/projects/${props.projectId}/comments/${comment.id}`, {
        preserveScroll: true,
    });
};
</script>

<template>
    <Card>
        <CardContent class="pt-6">
            <form class="space-y-3" @submit.prevent="addComment">
                <Textarea
                    v-model="form.body"
                    placeholder="Add a comment..."
                    class="min-h-20"
                    :disabled="form.processing"
                />
                <p v-if="form.errors.body" class="text-sm text-destructive">
                    {{ form.errors.body }}
                </p>
                <div class="flex justify-end">
                    <Button
                        type="submit"
                        size="sm"
                        :disabled="form.processing || !canSubmit"
                    >
                        <Send class="mr-1 h-4 w-4" />
                        Add Comment
                    </Button>
                </div>
            </form>

            <div v-if="sortedComments.length > 0" class="mt-6 space-y-4">
                <div
                    v-for="comment in sortedComments"
                    :key="comment.id"
                    class="rounded-lg border p-4"
                >
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
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
                        <Button
                            v-if="comment.user.id === authUserId"
                            variant="ghost"
                            size="sm"
                            type="button"
                            :disabled="form.processing"
                            @click="removeComment(comment)"
                        >
                            <Trash2 class="h-4 w-4" />
                            <span class="sr-only">Delete comment</span>
                        </Button>
                    </div>
                    <p class="mt-2 text-sm whitespace-pre-wrap">
                        {{ comment.body }}
                    </p>
                </div>
            </div>
            <p v-else class="mt-6 text-sm text-muted-foreground">
                No comments yet.
            </p>
        </CardContent>
    </Card>
</template>
