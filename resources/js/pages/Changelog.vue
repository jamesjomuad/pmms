<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import {
    Calendar,
    Check,
    Filter,
    Layers,
    MinusCircle,
    Package,
    RefreshCw,
    Search,
    ShieldAlert,
    Sparkles,
    Tag,
    Wrench,
    X,
} from '@lucide/vue';
import type { Component } from 'vue';
import { computed, ref } from 'vue';
import EmptyState from '@/components/EmptyState.vue';
import Heading from '@/components/Heading.vue';
import StatCard from '@/components/StatCard.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import { Input } from '@/components/ui/input';

type ReleaseSection = {
    type: string;
    items: string[];
};

type Release = {
    version: string;
    date: string | null;
    is_unreleased: boolean;
    sections: ReleaseSection[];
};

type Stats = {
    total_releases: number;
    total_changes: number;
    latest_version: string;
};

const props = defineProps<{
    releases: Release[];
    stats: Stats;
}>();

const searchQuery = ref('');
const selectedCategory = ref<string>('all');

const categories = [
    { id: 'all', label: 'All Changes' },
    { id: 'Added', label: 'Added' },
    { id: 'Changed', label: 'Changed' },
    { id: 'Fixed', label: 'Fixed' },
    { id: 'Removed', label: 'Removed' },
];

const categoryConfig: Record<
    string,
    {
        icon: Component;
        badgeClass: string;
        dotClass: string;
    }
> = {
    Added: {
        icon: Sparkles,
        badgeClass:
            'border-emerald-500/20 bg-emerald-500/10 text-emerald-700 dark:text-emerald-400',
        dotClass: 'bg-emerald-500',
    },
    Changed: {
        icon: RefreshCw,
        badgeClass:
            'border-blue-500/20 bg-blue-500/10 text-blue-700 dark:text-blue-400',
        dotClass: 'bg-blue-500',
    },
    Fixed: {
        icon: Wrench,
        badgeClass:
            'border-amber-500/20 bg-amber-500/10 text-amber-700 dark:text-amber-400',
        dotClass: 'bg-amber-500',
    },
    Removed: {
        icon: MinusCircle,
        badgeClass:
            'border-rose-500/20 bg-rose-500/10 text-rose-700 dark:text-rose-400',
        dotClass: 'bg-rose-500',
    },
    Security: {
        icon: ShieldAlert,
        badgeClass:
            'border-purple-500/20 bg-purple-500/10 text-purple-700 dark:text-purple-400',
        dotClass: 'bg-purple-500',
    },
};

const formatItem = (text: string): string => {
    let safe = text
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;');

    safe = safe.replace(
        /\[([^\]]+)\]\(([^)]+)\)/g,
        '<a href="$2" target="_blank" rel="noopener noreferrer" class="font-medium text-primary underline underline-offset-4 hover:opacity-80">$1</a>',
    );

    safe = safe.replace(
        /`([^`]+)`/g,
        '<code class="rounded bg-muted px-1.5 py-0.5 font-mono text-xs font-semibold text-foreground">$1</code>',
    );

    safe = safe.replace(
        /\*\*([^*]+)\*\*/g,
        '<strong class="font-semibold text-foreground">$1</strong>',
    );

    return safe;
};

const formatDate = (dateStr: string | null): string => {
    if (!dateStr) {
        return '';
    }

    const [year, month, day] = dateStr.split('-');

    if (!year || !month || !day) {
        return dateStr;
    }

    const date = new Date(
        parseInt(year, 10),
        parseInt(month, 10) - 1,
        parseInt(day, 10),
    );

    return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
};

const filteredReleases = computed(() => {
    const q = searchQuery.value.trim().toLowerCase();
    const cat = selectedCategory.value;

    return props.releases
        .map((release) => {
            const filteredSections = release.sections
                .filter((section) => {
                    if (cat !== 'all' && section.type !== cat) {
                        return false;
                    }

                    return true;
                })
                .map((section) => {
                    const filteredItems = section.items.filter((item) => {
                        if (!q) {
                            return true;
                        }

                        return item.toLowerCase().includes(q);
                    });

                    return {
                        ...section,
                        items: filteredItems,
                    };
                })
                .filter((section) => section.items.length > 0);

            return {
                ...release,
                sections: filteredSections,
            };
        })
        .filter((release) => release.sections.length > 0);
});

const totalVisibleChanges = computed(() => {
    return filteredReleases.value.reduce((total, release) => {
        return (
            total +
            release.sections.reduce(
                (secTotal, s) => secTotal + s.items.length,
                0,
            )
        );
    }, 0);
});

const clearFilters = () => {
    searchQuery.value = '';
    selectedCategory.value = 'all';
};

defineOptions({
    layout: () => ({
        breadcrumbs: [
            {
                title: 'Dashboard',
                href: '/dashboard',
            },
            {
                title: 'Changelog',
                href: '/changelog',
            },
        ],
    }),
});
</script>

<template>
    <Head title="Changelog" />

    <div
        class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4 sm:p-6"
    >
        <!-- Header -->
        <div
            class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between"
        >
            <Heading
                variant="small"
                title="Changelog"
                description="System updates, feature releases, and fixes for PMMS"
            />
        </div>

        <!-- Quick Stats -->
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <StatCard
                :icon="Tag"
                label="Latest Release"
                :value="stats.latest_version"
                icon-bg-class="bg-primary/10"
                icon-class="text-primary"
            />
            <StatCard
                :icon="Package"
                label="Total Releases"
                :value="stats.total_releases"
                icon-bg-class="bg-blue-500/10"
                icon-class="text-blue-500"
            />
            <StatCard
                :icon="Layers"
                label="Total Changes Logged"
                :value="stats.total_changes"
                icon-bg-class="bg-emerald-500/10"
                icon-class="text-emerald-500"
            />
        </div>

        <!-- Filters Toolbar -->
        <div
            class="flex flex-col gap-3 rounded-lg border bg-card p-4 sm:flex-row sm:items-center sm:justify-between"
        >
            <div class="relative w-full sm:max-w-xs">
                <Search
                    class="absolute top-2.5 left-3 h-4 w-4 text-muted-foreground"
                />
                <Input
                    v-model="searchQuery"
                    placeholder="Search changelog..."
                    class="pl-9"
                />
            </div>

            <div class="flex flex-wrap items-center gap-1.5">
                <span
                    class="mr-1 hidden items-center gap-1 text-xs text-muted-foreground md:inline-flex"
                >
                    <Filter class="h-3.5 w-3.5" /> Filter:
                </span>
                <button
                    v-for="cat in categories"
                    :key="cat.id"
                    type="button"
                    class="inline-flex items-center gap-1 rounded-full px-3 py-1 text-xs font-medium transition-colors"
                    :class="
                        selectedCategory === cat.id
                            ? 'bg-primary text-primary-foreground shadow-xs'
                            : 'bg-muted/70 text-muted-foreground hover:bg-muted hover:text-foreground'
                    "
                    @click="selectedCategory = cat.id"
                >
                    {{ cat.label }}
                </button>
            </div>
        </div>

        <!-- Timeline / Release List -->
        <div v-if="filteredReleases.length > 0" class="relative pl-6 sm:pl-8">
            <!-- Timeline vertical line -->
            <div
                class="absolute top-4 bottom-4 left-2 w-px bg-border sm:left-3"
                aria-hidden="true"
            />

            <div class="space-y-10">
                <article
                    v-for="release in filteredReleases"
                    :key="release.version"
                    class="relative"
                >
                    <!-- Timeline Node Marker -->
                    <div
                        class="absolute top-1.5 -left-6 flex h-5 w-5 items-center justify-center rounded-full border-2 border-background bg-card shadow-xs sm:-left-8 sm:h-6 sm:w-6"
                        :class="
                            release.is_unreleased
                                ? 'text-primary ring-2 ring-primary/40'
                                : 'text-muted-foreground'
                        "
                    >
                        <span
                            v-if="release.is_unreleased"
                            class="relative flex h-2 w-2"
                        >
                            <span
                                class="absolute inline-flex h-full w-full animate-ping rounded-full bg-primary opacity-75"
                            />
                            <span
                                class="relative inline-flex h-2 w-2 rounded-full bg-primary"
                            />
                        </span>
                        <Check v-else class="h-3 w-3" />
                    </div>

                    <!-- Release Card -->
                    <Card class="border shadow-xs">
                        <CardHeader class="border-b bg-muted/20 pb-3">
                            <div
                                class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between"
                            >
                                <div
                                    class="flex flex-wrap items-center gap-2.5"
                                >
                                    <h2
                                        class="text-lg font-semibold tracking-tight"
                                    >
                                        {{
                                            release.is_unreleased
                                                ? 'Unreleased'
                                                : `v${release.version}`
                                        }}
                                    </h2>

                                    <Badge
                                        v-if="release.is_unreleased"
                                        variant="default"
                                        class="text-xs tracking-wider uppercase"
                                    >
                                        In Development
                                    </Badge>
                                    <Badge
                                        v-else
                                        variant="outline"
                                        class="font-mono text-xs"
                                    >
                                        Stable
                                    </Badge>

                                    <span
                                        v-if="release.date"
                                        class="inline-flex items-center gap-1 text-xs text-muted-foreground"
                                    >
                                        <Calendar class="h-3.5 w-3.5" />
                                        {{ formatDate(release.date) }}
                                    </span>
                                </div>

                                <div class="text-xs text-muted-foreground">
                                    {{
                                        release.sections.reduce(
                                            (sum, s) => sum + s.items.length,
                                            0,
                                        )
                                    }}
                                    {{
                                        release.sections.reduce(
                                            (sum, s) => sum + s.items.length,
                                            0,
                                        ) === 1
                                            ? 'change'
                                            : 'changes'
                                    }}
                                </div>
                            </div>
                        </CardHeader>

                        <CardContent class="space-y-6 pt-5">
                            <div
                                v-for="section in release.sections"
                                :key="section.type"
                                class="space-y-2.5"
                            >
                                <!-- Section Category Header -->
                                <div class="flex items-center gap-2">
                                    <span
                                        class="inline-flex items-center gap-1.5 rounded-md border px-2.5 py-0.5 text-xs font-semibold"
                                        :class="
                                            categoryConfig[section.type]
                                                ?.badgeClass ??
                                            'bg-muted text-muted-foreground'
                                        "
                                    >
                                        <component
                                            :is="
                                                categoryConfig[section.type]
                                                    ?.icon ?? Sparkles
                                            "
                                            class="h-3.5 w-3.5"
                                        />
                                        {{ section.type }}
                                    </span>
                                    <span class="text-xs text-muted-foreground">
                                        ({{ section.items.length }})
                                    </span>
                                </div>

                                <!-- Item List -->
                                <ul class="space-y-2 pl-1">
                                    <li
                                        v-for="(item, idx) in section.items"
                                        :key="idx"
                                        class="flex items-start gap-2.5 text-sm leading-relaxed"
                                    >
                                        <span
                                            class="mt-2 h-1.5 w-1.5 shrink-0 rounded-full"
                                            :class="
                                                categoryConfig[section.type]
                                                    ?.dotClass ?? 'bg-border'
                                            "
                                        />
                                        <!-- eslint-disable-next-line vue/no-v-html -->
                                        <div
                                            class="text-muted-foreground"
                                            v-html="formatItem(item)"
                                        />
                                    </li>
                                </ul>
                            </div>
                        </CardContent>
                    </Card>
                </article>
            </div>
        </div>

        <!-- Empty Filter State -->
        <EmptyState
            v-else
            :icon="Search"
            title="No updates found"
            :description="
                searchQuery
                    ? `No changelog updates matched '${searchQuery}' in the selected category.`
                    : 'No updates found for this filter.'
            "
        >
            <template #action>
                <Button variant="outline" size="sm" @click="clearFilters">
                    <X class="mr-1 h-3.5 w-3.5" />
                    Reset Filters
                </Button>
            </template>
        </EmptyState>

        <!-- Showing count footer -->
        <p
            v-if="
                filteredReleases.length > 0 &&
                (searchQuery || selectedCategory !== 'all')
            "
            class="text-center text-xs text-muted-foreground"
        >
            Showing {{ totalVisibleChanges }} updates matching your filter
        </p>
    </div>
</template>
