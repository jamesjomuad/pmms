<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { watch } from 'vue';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
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
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import type { ManagedUser, RoleOption } from '@/types';

type Props = {
    user: ManagedUser | null;
    open: boolean;
    availableRoles: RoleOption[];
};

const props = defineProps<Props>();
const emit = defineEmits<{
    'update:open': [value: boolean];
}>();

const form = useForm({
    name: '',
    email: '',
    role: 'member',
    password: '',
    password_confirmation: '',
});

watch(
    () => props.user,
    (newUser) => {
        if (newUser) {
            form.name = newUser.name;
            form.email = newUser.email;
            form.role = newUser.role;
            form.password = '';
            form.password_confirmation = '';
            form.clearErrors();
        }
    },
    { immediate: true },
);

const handleOpenChange = (value: boolean) => {
    emit('update:open', value);
};

const submit = () => {
    const match = window.location.pathname.match(/^\/([^/]+)/);
    const slug = match ? match[1] : null;
    if (!slug || !props.user) return;

    form.patch(`/${slug}/users/${props.user.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            emit('update:open', false);
            form.reset('password', 'password_confirmation');
        },
    });
};
</script>

<template>
    <Dialog :open="props.open" @update:open="handleOpenChange">
        <DialogContent class="sm:max-w-lg">
            <form v-if="props.user" @submit.prevent="submit" class="space-y-6">
                <DialogHeader>
                    <DialogTitle>Edit User Details</DialogTitle>
                    <DialogDescription>
                        Update name, email, company role, or set a new password.
                    </DialogDescription>
                </DialogHeader>

                <div class="grid gap-4 py-2">
                    <div class="grid gap-2">
                        <Label for="edit-user-name">Full Name</Label>
                        <Input
                            id="edit-user-name"
                            v-model="form.name"
                            type="text"
                            placeholder="e.g. Jane Doe"
                            required
                            autocomplete="name"
                        />
                        <InputError :message="form.errors.name" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="edit-user-email">Email Address</Label>
                        <Input
                            id="edit-user-email"
                            v-model="form.email"
                            type="email"
                            placeholder="jane.doe@example.com"
                            required
                            autocomplete="email"
                        />
                        <InputError :message="form.errors.email" />
                    </div>

                    <div v-if="props.user.role !== 'owner'" class="grid gap-2">
                        <Label for="edit-user-role">Company Role</Label>
                        <Select v-model="form.role">
                            <SelectTrigger id="edit-user-role" class="w-full">
                                <SelectValue placeholder="Select a role" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="role in props.availableRoles"
                                    :key="role.value"
                                    :value="role.value"
                                >
                                    {{ role.label }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError :message="form.errors.role" />
                    </div>

                    <div class="space-y-3 pt-2 border-t">
                        <div class="space-y-1">
                            <h4 class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">
                                Reset Password (Optional)
                            </h4>
                            <p class="text-xs text-muted-foreground">
                                Leave blank to keep the existing password.
                            </p>
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div class="grid gap-2">
                                <Label for="edit-user-password">New Password</Label>
                                <PasswordInput
                                    id="edit-user-password"
                                    v-model="form.password"
                                    placeholder="••••••••"
                                    autocomplete="new-password"
                                />
                                <InputError :message="form.errors.password" />
                            </div>

                            <div class="grid gap-2">
                                <Label for="edit-user-password-confirmation">Confirm Password</Label>
                                <PasswordInput
                                    id="edit-user-password-confirmation"
                                    v-model="form.password_confirmation"
                                    placeholder="••••••••"
                                    autocomplete="new-password"
                                />
                                <InputError :message="form.errors.password_confirmation" />
                            </div>
                        </div>
                    </div>
                </div>

                <DialogFooter class="gap-2">
                    <DialogClose as-child>
                        <Button variant="secondary" type="button">Cancel</Button>
                    </DialogClose>
                    <Button type="submit" :disabled="form.processing">
                        {{ form.processing ? 'Saving...' : 'Save Changes' }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
