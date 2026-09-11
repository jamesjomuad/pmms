<script setup lang="ts">
import {
    Briefcase,
    Calendar,
    CheckCircle2,
    Edit3,
    KeyRound,
    Lock,
    Mail,
    Shield,
    XCircle,
} from '@lucide/vue';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Sheet,
    SheetClose,
    SheetContent,
    SheetDescription,
    SheetFooter,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet';
import { useInitials } from '@/composables/useInitials';
import type { ManagedUser } from '@/types';

type Props = {
    user: ManagedUser | null;
    open: boolean;
};

const props = defineProps<Props>();
const emit = defineEmits<{
    'update:open': [value: boolean];
    edit: [user: ManagedUser];
}>();

const { getInitials } = useInitials();

const formatDate = (dateString?: string | null) => {
    if (!dateString) {
        return 'Never';
    }

    return new Date(dateString).toLocaleDateString(undefined, {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
};

const handleOpenChange = (value: boolean) => {
    emit('update:open', value);
};
</script>

<template>
    <Sheet :open="props.open" @update:open="handleOpenChange">
        <SheetContent class="flex w-full flex-col overflow-y-auto p-6 sm:max-w-lg">
            <div v-if="props.user" class="flex-1 space-y-6">
                <SheetHeader class="space-y-3 border-b pb-4">
                    <div class="flex items-center gap-3">
                        <Avatar class="h-14 w-14 border-2 border-primary/20">
                            <AvatarFallback
                                class="bg-primary/10 text-lg font-semibold text-primary"
                            >
                                {{ getInitials(props.user.name) }}
                            </AvatarFallback>
                        </Avatar>
                        <div class="space-y-1">
                            <SheetTitle class="text-xl leading-none font-bold">
                                {{ props.user.name }}
                            </SheetTitle>
                            <SheetDescription class="text-sm">
                                {{ props.user.email }}
                            </SheetDescription>
                            <div class="flex items-center gap-2 pt-1">
                                <Badge
                                    :variant="
                                        props.user.role === 'owner'
                                            ? 'default'
                                            : props.user.role === 'admin'
                                              ? 'secondary'
                                              : 'outline'
                                    "
                                    class="capitalize"
                                >
                                    {{ props.user.role_label }}
                                </Badge>
                                <Badge
                                    v-if="props.user.email_verified_at"
                                    variant="outline"
                                    class="border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-900/50 dark:bg-emerald-950/50 dark:text-emerald-300"
                                >
                                    Verified
                                </Badge>
                                <Badge
                                    v-else
                                    variant="outline"
                                    class="border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-900/50 dark:bg-amber-950/50 dark:text-amber-300"
                                >
                                    Unverified
                                </Badge>
                            </div>
                        </div>
                    </div>
                </SheetHeader>

                <!-- Profile Information -->
                <div class="space-y-3">
                    <h4
                        class="text-xs font-semibold tracking-wider text-muted-foreground uppercase"
                    >
                        Account Details
                    </h4>
                    <div class="grid gap-2 rounded-lg border p-3 text-sm">
                        <div
                            class="flex items-center justify-between border-b border-border/50 py-1"
                        >
                            <span
                                class="flex items-center gap-2 text-muted-foreground"
                            >
                                <Mail class="h-4 w-4" /> Email Address
                            </span>
                            <span class="max-w-[200px] truncate font-medium">{{
                                props.user.email
                            }}</span>
                        </div>
                        <div
                            class="flex items-center justify-between border-b border-border/50 py-1"
                        >
                            <span
                                class="flex items-center gap-2 text-muted-foreground"
                            >
                                <Calendar class="h-4 w-4" /> Member Since
                            </span>
                            <span class="font-medium">{{
                                formatDate(props.user.created_at)
                            }}</span>
                        </div>
                        <div class="flex items-center justify-between py-1">
                            <span
                                class="flex items-center gap-2 text-muted-foreground"
                            >
                                <Shield class="h-4 w-4" /> Access Level
                            </span>
                            <span class="font-medium capitalize">{{
                                props.user.role_label
                            }}</span>
                        </div>
                    </div>
                </div>

                <!-- Security Posture -->
                <div class="space-y-3">
                    <h4
                        class="text-xs font-semibold tracking-wider text-muted-foreground uppercase"
                    >
                        Security & Authentication
                    </h4>
                    <div class="grid gap-2 rounded-lg border p-3 text-sm">
                        <div
                            class="flex items-center justify-between border-b border-border/50 py-1"
                        >
                            <span
                                class="flex items-center gap-2 text-muted-foreground"
                            >
                                <Lock class="h-4 w-4" /> Two-Factor
                                Authentication
                            </span>
                            <span class="flex items-center gap-1.5 font-medium">
                                <CheckCircle2
                                    v-if="props.user.two_factor_enabled"
                                    class="h-4 w-4 text-emerald-500"
                                />
                                <XCircle
                                    v-else
                                    class="h-4 w-4 text-muted-foreground/60"
                                />
                                {{
                                    props.user.two_factor_enabled
                                        ? 'Enabled'
                                        : 'Disabled'
                                }}
                            </span>
                        </div>
                        <div class="flex items-center justify-between py-1">
                            <span
                                class="flex items-center gap-2 text-muted-foreground"
                            >
                                <KeyRound class="h-4 w-4" /> Email Verification
                            </span>
                            <span class="font-medium">
                                {{
                                    props.user.email_verified_at
                                        ? formatDate(
                                              props.user.email_verified_at,
                                          )
                                        : 'Pending'
                                }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Assigned HVAC Projects -->
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <h4
                            class="text-xs font-semibold tracking-wider text-muted-foreground uppercase"
                        >
                            Assigned HVAC Projects ({{
                                props.user.projects_count
                            }})
                        </h4>
                    </div>

                    <div
                        v-if="
                            props.user.projects &&
                            props.user.projects.length > 0
                        "
                        class="space-y-2"
                    >
                        <div
                            v-for="project in props.user.projects"
                            :key="project.id"
                            class="flex items-center justify-between rounded-lg border bg-card p-3 transition-colors hover:bg-muted/30"
                        >
                            <div class="space-y-1">
                                <div class="flex items-center gap-2">
                                    <span class="text-sm font-medium">{{
                                        project.name
                                    }}</span>
                                    <span
                                        class="font-mono text-xs text-muted-foreground"
                                        >({{ project.project_number }})</span
                                    >
                                </div>
                                <div
                                    class="flex items-center gap-2 text-xs text-muted-foreground"
                                >
                                    <span
                                        class="inline-flex items-center gap-1"
                                    >
                                        <Briefcase class="h-3 w-3" />
                                        {{
                                            project.project_role_label ||
                                            'Team Member'
                                        }}
                                    </span>
                                </div>
                            </div>
                            <Badge
                                variant="outline"
                                :class="
                                    project.status === 'active'
                                        ? 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-900 dark:bg-emerald-950 dark:text-emerald-300'
                                        : project.status === 'on_hold'
                                          ? 'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-900 dark:bg-amber-950 dark:text-amber-300'
                                          : 'border-muted-foreground/30 text-muted-foreground'
                                "
                                class="text-xs capitalize"
                            >
                                {{ project.status }}
                            </Badge>
                        </div>
                    </div>

                    <div
                        v-else
                        class="flex flex-col items-center justify-center rounded-lg border border-dashed px-4 py-8 text-center"
                    >
                        <Briefcase
                            class="mb-2 h-8 w-8 text-muted-foreground/40"
                        />
                        <p class="text-sm font-medium">
                            No project assignments
                        </p>
                        <p
                            class="mt-0.5 max-w-xs text-xs text-muted-foreground"
                        >
                            This user is not currently assigned to any active or
                            scheduled HVAC projects.
                        </p>
                    </div>
                </div>
            </div>

            <SheetFooter class="gap-2 border-t pt-4 sm:justify-between">
                <SheetClose as-child>
                    <Button variant="outline">Close</Button>
                </SheetClose>
                <Button
                    v-if="props.user?.can.update"
                    @click="props.user && emit('edit', props.user)"
                    class="gap-1.5"
                >
                    <Edit3 class="h-4 w-4" />
                    Edit User
                </Button>
            </SheetFooter>
        </SheetContent>
    </Sheet>
</template>
