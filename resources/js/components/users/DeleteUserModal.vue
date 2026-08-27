<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { AlertTriangle } from '@lucide/vue';
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import type { ManagedUser } from '@/types';

type Props = {
    user: ManagedUser | null;
    open: boolean;
};

const props = defineProps<Props>();
const emit = defineEmits<{
    'update:open': [value: boolean];
}>();

const processing = ref(false);

const handleOpenChange = (value: boolean) => {
    emit('update:open', value);
};

const submit = () => {
    const match = window.location.pathname.match(/^\/([^/]+)/);
    const slug = match ? match[1] : null;
    if (!slug || !props.user) return;

    processing.value = true;
    router.delete(`/${slug}/users/${props.user.id}`, {
        preserveScroll: true,
        onFinish: () => {
            processing.value = false;
            emit('update:open', false);
        },
    });
};
</script>

<template>
    <Dialog :open="props.open" @update:open="handleOpenChange">
        <DialogContent class="sm:max-w-md">
            <div v-if="props.user" class="space-y-4">
                <DialogHeader>
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-destructive/10 text-destructive">
                            <AlertTriangle class="h-5 w-5" />
                        </div>
                        <div>
                            <DialogTitle>Remove Team Member</DialogTitle>
                            <DialogDescription class="mt-1">
                                Are you sure you want to remove <strong>{{ props.user.name }}</strong>?
                            </DialogDescription>
                        </div>
                    </div>
                </DialogHeader>

                <div class="rounded-lg bg-muted/60 p-3 text-xs text-muted-foreground space-y-1">
                    <p>• The user will lose all access to this company workspace.</p>
                    <p v-if="props.user.projects_count > 0">
                        • They are currently assigned to <strong>{{ props.user.projects_count }}</strong> HVAC project(s). These assignments will be detached.
                    </p>
                    <p>• Historical stage transitions and audit log records will remain preserved.</p>
                </div>

                <DialogFooter class="gap-2">
                    <DialogClose as-child>
                        <Button variant="secondary" type="button" :disabled="processing">
                            Cancel
                        </Button>
                    </DialogClose>
                    <Button
                        variant="destructive"
                        type="button"
                        :disabled="processing"
                        @click="submit"
                    >
                        {{ processing ? 'Removing...' : 'Remove User' }}
                    </Button>
                </DialogFooter>
            </div>
        </DialogContent>
    </Dialog>
</template>
