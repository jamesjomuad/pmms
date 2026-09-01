<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import {
    CheckCircle2,
    Edit,
    Eye,
    Lock,
    MoreHorizontal,
    Plus,
    Search,
    ShieldCheck,
    Trash2,
    Users,
    X,
    XCircle,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import Heading from '@/components/Heading.vue';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import CreateUserModal from '@/components/users/CreateUserModal.vue';
import DeleteUserModal from '@/components/users/DeleteUserModal.vue';
import EditUserModal from '@/components/users/EditUserModal.vue';
import UserDetailsSheet from '@/components/users/UserDetailsSheet.vue';
import { useInitials } from '@/composables/useInitials';
import type { ManagedUser, RoleOption, UserStats } from '@/types';

type Props = {
    users: ManagedUser[];
    stats: UserStats;
    availableRoles: RoleOption[];
    canCreateUser: boolean;
};

const props = defineProps<Props>();

const { getInitials } = useInitials();

const searchQuery = ref('');
const roleFilter = ref('all');
const verifiedFilter = ref('all');
const twoFactorFilter = ref('all');

const createUserOpen = ref(false);
const editUserOpen = ref(false);
const userToEdit = ref<ManagedUser | null>(null);
const deleteUserOpen = ref(false);
const userToDelete = ref<ManagedUser | null>(null);
const detailsSheetOpen = ref(false);
const userForDetails = ref<ManagedUser | null>(null);

const filteredUsers = computed(() => {
    return props.users.filter((user) => {
        if (searchQuery.value) {
            const query = searchQuery.value.toLowerCase();
            const matchesName = user.name.toLowerCase().includes(query);
            const matchesEmail = user.email.toLowerCase().includes(query);

            if (!matchesName && !matchesEmail) {
return false;
}
        }

        if (roleFilter.value !== 'all' && user.role !== roleFilter.value) {
            return false;
        }

        if (verifiedFilter.value === 'verified' && !user.email_verified_at) {
            return false;
        }

        if (verifiedFilter.value === 'unverified' && user.email_verified_at) {
            return false;
        }

        if (twoFactorFilter.value === 'enabled' && !user.two_factor_enabled) {
            return false;
        }

        if (twoFactorFilter.value === 'disabled' && user.two_factor_enabled) {
            return false;
        }

        return true;
    });
});

const hasActiveFilters = computed(() => {
    return (
        searchQuery.value !== '' ||
        roleFilter.value !== 'all' ||
        verifiedFilter.value !== 'all' ||
        twoFactorFilter.value !== 'all'
    );
});

const clearFilters = () => {
    searchQuery.value = '';
    roleFilter.value = 'all';
    verifiedFilter.value = 'all';
    twoFactorFilter.value = 'all';
};

const openDetails = (user: ManagedUser) => {
    userForDetails.value = user;
    detailsSheetOpen.value = true;
};

const openEdit = (user: ManagedUser) => {
    detailsSheetOpen.value = false;
    userToEdit.value = user;
    editUserOpen.value = true;
};

const openDelete = (user: ManagedUser) => {
    userToDelete.value = user;
    deleteUserOpen.value = true;
};

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString(undefined, {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
};

defineOptions({
    layout: () => ({
        breadcrumbs: [
            {
                title: 'Users',
                href: '/users',
            },
        ],
    }),
});
</script>

<template>
    <Head title="Users Management" />

    <h1 class="sr-only">Users Management</h1>

    <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4">
        <!-- Top header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <Heading
                variant="small"
                title="Users Management"
                description="Manage team members, security postures, and project assignments."
            />

            <Button
                v-if="props.canCreateUser"
                @click="createUserOpen = true"
                class="gap-1.5"
            >
                <Plus class="h-4 w-4" />
                Add User
            </Button>
        </div>

        <!-- Metrics / Stats Cards -->
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <div class="flex items-center gap-3 rounded-lg border bg-card p-3">
                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-md bg-muted">
                    <Users class="h-4 w-4 text-muted-foreground" />
                </div>
                <div>
                    <p class="text-2xl font-semibold tracking-tight">{{ props.stats.total }}</p>
                    <p class="text-xs text-muted-foreground">Total Users</p>
                </div>
            </div>

            <div class="flex items-center gap-3 rounded-lg border bg-card p-3">
                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-md bg-emerald-50 dark:bg-emerald-950">
                    <CheckCircle2 class="h-4 w-4 text-emerald-600 dark:text-emerald-400" />
                </div>
                <div>
                    <p class="text-2xl font-semibold tracking-tight">{{ props.stats.verified }}</p>
                    <p class="text-xs text-muted-foreground">Verified Accounts</p>
                </div>
            </div>

            <div class="flex items-center gap-3 rounded-lg border bg-card p-3">
                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-md bg-indigo-50 dark:bg-indigo-950">
                    <ShieldCheck class="h-4 w-4 text-indigo-600 dark:text-indigo-400" />
                </div>
                <div>
                    <p class="text-2xl font-semibold tracking-tight">{{ props.stats.admins_owners }}</p>
                    <p class="text-xs text-muted-foreground">Admins & Owners</p>
                </div>
            </div>

            <div class="flex items-center gap-3 rounded-lg border bg-card p-3">
                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-md bg-amber-50 dark:bg-amber-950">
                    <Lock class="h-4 w-4 text-amber-600 dark:text-amber-400" />
                </div>
                <div>
                    <p class="text-2xl font-semibold tracking-tight">{{ props.stats.two_factor_enabled }}</p>
                    <p class="text-xs text-muted-foreground">2FA Enabled</p>
                </div>
            </div>
        </div>

        <!-- Filter bar -->
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="relative flex-1 sm:max-w-xs">
                <Search class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                <Input
                    v-model="searchQuery"
                    placeholder="Search by name or email..."
                    class="pl-9"
                />
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <select
                    v-model="roleFilter"
                    class="h-9 rounded-md border bg-transparent px-3 text-xs font-medium text-muted-foreground outline-none focus:border-ring focus:ring-2 focus:ring-ring/50"
                >
                    <option value="all">All Roles</option>
                    <option value="owner">Owners</option>
                    <option value="admin">Admins</option>
                    <option value="member">Members</option>
                </select>

                <select
                    v-model="verifiedFilter"
                    class="h-9 rounded-md border bg-transparent px-3 text-xs font-medium text-muted-foreground outline-none focus:border-ring focus:ring-2 focus:ring-ring/50"
                >
                    <option value="all">All Verification</option>
                    <option value="verified">Verified</option>
                    <option value="unverified">Unverified</option>
                </select>

                <select
                    v-model="twoFactorFilter"
                    class="h-9 rounded-md border bg-transparent px-3 text-xs font-medium text-muted-foreground outline-none focus:border-ring focus:ring-2 focus:ring-ring/50"
                >
                    <option value="all">All 2FA</option>
                    <option value="enabled">2FA Enabled</option>
                    <option value="disabled">2FA Disabled</option>
                </select>

                <Button
                    v-if="hasActiveFilters"
                    variant="ghost"
                    size="sm"
                    class="h-8 px-2 text-xs"
                    @click="clearFilters"
                >
                    <X class="mr-1 h-3 w-3" />
                    Clear
                </Button>
            </div>
        </div>

        <!-- Users Table -->
        <div class="rounded-lg border bg-card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="border-b bg-muted/40 text-xs font-semibold text-muted-foreground uppercase">
                        <tr>
                            <th class="px-4 py-3">User</th>
                            <th class="px-4 py-3">Role</th>
                            <th class="px-4 py-3">Projects</th>
                            <th class="px-4 py-3">Security</th>
                            <th class="px-4 py-3">Joined</th>
                            <th class="px-4 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border/60">
                        <tr
                            v-for="user in filteredUsers"
                            :key="user.id"
                            class="transition-colors hover:bg-muted/30"
                        >
                            <!-- User Info -->
                            <td class="px-4 py-3.5">
                                <div class="flex items-center gap-3">
                                    <Avatar class="h-9 w-9">
                                        <AvatarFallback class="bg-primary/10 text-primary font-medium text-xs">
                                            {{ getInitials(user.name) }}
                                        </AvatarFallback>
                                    </Avatar>
                                    <div>
                                        <button
                                            type="button"
                                            @click="openDetails(user)"
                                            class="font-medium text-foreground hover:underline text-left"
                                        >
                                            {{ user.name }}
                                        </button>
                                        <p class="text-xs text-muted-foreground">{{ user.email }}</p>
                                    </div>
                                </div>
                            </td>

                            <!-- Role Badge -->
                            <td class="px-4 py-3.5 whitespace-nowrap">
                                <Badge
                                    :variant="user.role === 'owner' ? 'default' : user.role === 'admin' ? 'secondary' : 'outline'"
                                    class="capitalize text-xs font-medium"
                                >
                                    {{ user.role_label }}
                                </Badge>
                            </td>

                            <!-- Assigned Projects -->
                            <td class="px-4 py-3.5 whitespace-nowrap">
                                <button
                                    type="button"
                                    @click="openDetails(user)"
                                    class="inline-flex items-center gap-1.5 rounded-md px-2 py-1 text-xs font-medium bg-muted hover:bg-muted/80 text-muted-foreground hover:text-foreground transition-colors"
                                >
                                    <span>{{ user.projects_count }} {{ user.projects_count === 1 ? 'project' : 'projects' }}</span>
                                </button>
                            </td>

                            <!-- Security Posture -->
                            <td class="px-4 py-3.5 whitespace-nowrap">
                                <div class="flex items-center gap-2">
                                    <span
                                        :title="user.two_factor_enabled ? '2FA is enabled' : '2FA is disabled'"
                                        class="inline-flex items-center gap-1 text-xs px-2 py-0.5 rounded-full"
                                        :class="user.two_factor_enabled ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-300' : 'bg-muted text-muted-foreground'"
                                    >
                                        <Lock class="h-3 w-3" />
                                        {{ user.two_factor_enabled ? '2FA' : 'No 2FA' }}
                                    </span>

                                    <span
                                        :title="user.email_verified_at ? 'Email verified' : 'Email unverified'"
                                        class="inline-flex items-center gap-1 text-xs px-2 py-0.5 rounded-full"
                                        :class="user.email_verified_at ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-300' : 'bg-amber-50 text-amber-700 dark:bg-amber-950/50 dark:text-amber-300'"
                                    >
                                        <CheckCircle2 v-if="user.email_verified_at" class="h-3 w-3 text-emerald-600 dark:text-emerald-400" />
                                        <XCircle v-else class="h-3 w-3 text-amber-600 dark:text-amber-400" />
                                        {{ user.email_verified_at ? 'Verified' : 'Pending' }}
                                    </span>
                                </div>
                            </td>

                            <!-- Joined Date -->
                            <td class="px-4 py-3.5 whitespace-nowrap text-xs text-muted-foreground">
                                {{ formatDate(user.created_at) }}
                            </td>

                            <!-- Actions Menu -->
                            <td class="px-4 py-3.5 text-right whitespace-nowrap">
                                <DropdownMenu>
                                    <DropdownMenuTrigger as-child>
                                        <Button variant="ghost" size="icon" class="h-8 w-8">
                                            <MoreHorizontal class="h-4 w-4" />
                                            <span class="sr-only">Open actions menu</span>
                                        </Button>
                                    </DropdownMenuTrigger>
                                    <DropdownMenuContent align="end" class="w-44">
                                        <DropdownMenuLabel>Actions</DropdownMenuLabel>
                                        <DropdownMenuItem @click="openDetails(user)">
                                            <Eye class="mr-2 h-4 w-4" />
                                            View Details
                                        </DropdownMenuItem>
                                        <DropdownMenuItem
                                            v-if="user.can.update"
                                            @click="openEdit(user)"
                                        >
                                            <Edit class="mr-2 h-4 w-4" />
                                            Edit User
                                        </DropdownMenuItem>
                                        <template v-if="user.can.delete">
                                            <DropdownMenuSeparator />
                                            <DropdownMenuItem
                                                @click="openDelete(user)"
                                                class="text-destructive focus:text-destructive"
                                            >
                                                <Trash2 class="mr-2 h-4 w-4" />
                                                Remove User
                                            </DropdownMenuItem>
                                        </template>
                                    </DropdownMenuContent>
                                </DropdownMenu>
                            </td>
                        </tr>

                        <!-- Empty State -->
                        <tr v-if="filteredUsers.length === 0">
                            <td colspan="6" class="p-12 text-center">
                                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-muted mb-3">
                                    <Search class="h-6 w-6 text-muted-foreground/60" />
                                </div>
                                <h3 class="text-sm font-medium">No users found</h3>
                                <p class="text-xs text-muted-foreground max-w-sm mx-auto mt-1 mb-4">
                                    No users matched your current search or filter criteria. Try adjusting your filters.
                                </p>
                                <Button v-if="hasActiveFilters" variant="outline" size="sm" @click="clearFilters">
                                    Clear Filters
                                </Button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Modals and Drawer -->
        <CreateUserModal
            v-model:open="createUserOpen"
            :available-roles="props.availableRoles"
        />

        <EditUserModal
            v-model:open="editUserOpen"
            :user="userToEdit"
            :available-roles="props.availableRoles"
        />

        <DeleteUserModal
            v-model:open="deleteUserOpen"
            :user="userToDelete"
        />

        <UserDetailsSheet
            v-model:open="detailsSheetOpen"
            :user="userForDetails"
            @edit="openEdit"
        />
    </div>
</template>
