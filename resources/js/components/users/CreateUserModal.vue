<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { watch } from 'vue';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
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
import type { RoleOption } from '@/types';

type Props = {
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
    password: '',
    password_confirmation: '',
    role: 'member',
    email_verified: true,
});

watch(
    () => props.open,
    (isOpen) => {
        if (!isOpen) {
            form.reset();
            form.clearErrors();
        }
    },
);

const handleOpenChange = (value: boolean) => {
    emit('update:open', value);
};

const submit = () => {
    form.post('/users', {
        preserveScroll: true,
        onSuccess: () => {
            emit('update:open', false);
            form.reset();
        },
    });
};
</script>

<template>
    <Dialog :open="props.open" @update:open="handleOpenChange">
        <DialogContent class="sm:max-w-lg">
            <form @submit.prevent="submit" class="space-y-6">
                <DialogHeader>
                    <DialogTitle>Add New User</DialogTitle>
                    <DialogDescription>
                        Create an account for a new team member and assign their company role.
                    </DialogDescription>
                </DialogHeader>

                <div class="grid gap-4 py-2">
                    <div class="grid gap-2">
                        <Label for="create-user-name">Full Name</Label>
                        <Input
                            id="create-user-name"
                            v-model="form.name"
                            type="text"
                            placeholder="e.g. Jane Doe"
                            required
                            autocomplete="name"
                        />
                        <InputError :message="form.errors.name" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="create-user-email">Email Address</Label>
                        <Input
                            id="create-user-email"
                            v-model="form.email"
                            type="email"
                            placeholder="jane.doe@example.com"
                            required
                            autocomplete="email"
                        />
                        <InputError :message="form.errors.email" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="create-user-role">Company Role</Label>
                        <Select v-model="form.role">
                            <SelectTrigger id="create-user-role" class="w-full">
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

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="grid gap-2">
                            <Label for="create-user-password">Password</Label>
                            <PasswordInput
                                id="create-user-password"
                                v-model="form.password"
                                placeholder="••••••••"
                                required
                                autocomplete="new-password"
                            />
                            <InputError :message="form.errors.password" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="create-user-password-confirmation">Confirm Password</Label>
                            <PasswordInput
                                id="create-user-password-confirmation"
                                v-model="form.password_confirmation"
                                placeholder="••••••••"
                                required
                                autocomplete="new-password"
                            />
                            <InputError :message="form.errors.password_confirmation" />
                        </div>
                    </div>

                    <div class="flex items-center space-x-2 pt-2">
                        <Checkbox
                            id="create-user-verified"
                            :checked="form.email_verified"
                            @update:checked="(val: boolean) => (form.email_verified = val)"
                        />
                        <Label
                            for="create-user-verified"
                            class="text-sm font-normal text-muted-foreground leading-none cursor-pointer"
                        >
                            Mark email as verified immediately
                        </Label>
                    </div>
                </div>

                <DialogFooter class="gap-2">
                    <DialogClose as-child>
                        <Button variant="secondary" type="button">Cancel</Button>
                    </DialogClose>
                    <Button type="submit" :disabled="form.processing">
                        {{ form.processing ? 'Creating...' : 'Create User' }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
