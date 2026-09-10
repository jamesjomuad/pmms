<script setup lang="ts">
import { Plus, X } from '@lucide/vue';
import { computed, ref } from 'vue';
import { Button } from '@/components/ui/button';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import type { ProjectRoleOption, TeamAssignment, UserOption } from '@/types';

const props = defineProps<{
    users: UserOption[];
    roles: ProjectRoleOption[];
}>();

const model = defineModel<TeamAssignment[]>({
    get: (value) => value ?? [],
    set: (value) => value,
});

const members = computed(() => model.value ?? []);

const selectedUserId = ref<number | null>(null);
const selectedRole = ref<string>('');

const addMember = () => {
    if (!selectedUserId.value || !selectedRole.value) {
        return;
    }

    if (members.value.some((m) => m.user_id === selectedUserId.value)) {
        return;
    }

    model.value = [
        ...members.value,
        {
            user_id: selectedUserId.value,
            project_role: selectedRole.value as TeamAssignment['project_role'],
        },
    ];

    selectedUserId.value = null;
    selectedRole.value = '';
};

const removeMember = (userId: number) => {
    model.value = members.value.filter((m) => m.user_id !== userId);
};

const getUserName = (userId: number) =>
    props.users.find((u) => u.id === userId)?.name ?? 'Unknown';

const getUserEmail = (userId: number) =>
    props.users.find((u) => u.id === userId)?.email ?? '';

const getRoleLabel = (value: string) =>
    props.roles.find((r) => r.value === value)?.label ?? value;

const availableUsers = computed(() =>
    props.users.filter((u) => !members.value.some((m) => m.user_id === u.id)),
);
</script>

<template>
    <div class="space-y-3">
        <div
            v-for="member in members"
            :key="member.user_id"
            class="flex items-center gap-3 rounded-lg border px-3 py-2"
        >
            <div class="min-w-0 flex-1">
                <p class="truncate text-sm font-medium">
                    {{ getUserName(member.user_id) }}
                </p>
                <p class="truncate text-xs text-muted-foreground">
                    {{ getUserEmail(member.user_id) }}
                </p>
            </div>
            <span
                class="shrink-0 rounded-full bg-primary/10 px-2 py-0.5 text-xs font-medium text-primary"
            >
                {{ getRoleLabel(member.project_role) }}
            </span>
            <Button
                type="button"
                variant="ghost"
                size="sm"
                class="h-7 w-7 shrink-0 p-0"
                @click="removeMember(member.user_id)"
            >
                <X class="h-3.5 w-3.5" />
            </Button>
        </div>

        <div class="flex items-end gap-2">
            <div class="flex-1">
                <label for="team-user" class="mb-1.5 block text-sm font-medium">
                    Add team member
                </label>
                <Select v-model="selectedUserId">
                    <SelectTrigger id="team-user">
                        <SelectValue placeholder="Select a user" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem
                            v-for="user in availableUsers"
                            :key="user.id"
                            :value="user.id"
                        >
                            {{ user.name }}
                        </SelectItem>
                    </SelectContent>
                </Select>
            </div>
            <div class="w-44">
                <label for="team-role" class="mb-1.5 block text-sm font-medium">
                    Role
                </label>
                <Select v-model="selectedRole">
                    <SelectTrigger id="team-role">
                        <SelectValue placeholder="Select role" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem
                            v-for="role in roles"
                            :key="role.value"
                            :value="role.value"
                        >
                            {{ role.label }}
                        </SelectItem>
                    </SelectContent>
                </Select>
            </div>
            <Button
                type="button"
                variant="outline"
                size="sm"
                class="h-9 shrink-0"
                :disabled="!selectedUserId || !selectedRole"
                @click="addMember"
            >
                <Plus class="mr-1 h-4 w-4" />
                Add
            </Button>
        </div>
    </div>
</template>
