<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    Award,
    BarChart3,
    Bell,
    ChevronRight,
    ClipboardCheck,
    FileText,
    Hammer,
    History,
    LayoutDashboard,
    ListChecks,
    Thermometer,
    Truck,
    Users,
    Wrench,
    Zap,
    ArrowRight,
} from '@lucide/vue';
import { dashboard, login, register } from '@/routes';

const stages = [
    { icon: Award, label: 'Awarded', color: 'text-blue-400' },
    { icon: FileText, label: 'Submittals', color: 'text-sky-400' },
    { icon: ClipboardCheck, label: 'Shop Drawings', color: 'text-cyan-400' },
    { icon: Wrench, label: 'Procurement', color: 'text-teal-400' },
    { icon: Truck, label: 'Mobilization', color: 'text-emerald-400' },
    { icon: Hammer, label: 'Installation', color: 'text-amber-400' },
    { icon: Zap, label: 'Startup', color: 'text-orange-400' },
    { icon: BarChart3, label: 'TAB', color: 'text-red-400' },
    { icon: ListChecks, label: 'Punch List', color: 'text-rose-400' },
    { icon: LayoutDashboard, label: 'Closeout', color: 'text-pink-400' },
];

const features = [
    {
        icon: LayoutDashboard,
        title: 'Project Tracking',
        description:
            'Full lifecycle visibility from award through closeout. Track every stage, every handoff, every deadline.',
    },
    {
        icon: ClipboardCheck,
        title: 'Approval Workflows',
        description:
            'Route submittals, shop drawings, and change orders through configurable approval chains. No bottlenecks.',
    },
    {
        icon: Users,
        title: 'Team Collaboration',
        description:
            'Role-based access for PMs, field techs, estimators, and executives. Everyone sees what they need.',
    },
    {
        icon: FileText,
        title: 'Document Management',
        description:
            'Shop drawings, submittals, photos, punch list items — all versioned, tagged, and searchable.',
    },
    {
        icon: Bell,
        title: 'Smart Notifications',
        description:
            'Config-driven alerts via email, SMS, or in-app. Nobody drops the ball on a rejection or deadline.',
    },
    {
        icon: History,
        title: 'Full Audit Trail',
        description:
            'Every stage transition, approval decision, and record change is logged. Complete accountability.',
    },
];
</script>

<template>
    <Head title="PMMS">
        <meta
            name="description"
            content="Precision Mechanical Management System — Enterprise HVAC project management"
        />
    </Head>

    <div
        class="relative min-h-screen bg-[#0B1120] text-white selection:bg-sky-500/30"
    >
        <!-- Grid texture overlay -->
        <div
            class="pointer-events-none fixed inset-0 opacity-[0.03]"
            style="
                background-image: url(&quot;data:image/svg+xml,%3Csvg width='60' height='60' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M0 0h60v60H0z' fill='none'/%3E%3Cpath d='M0 30h60M30 0v60' stroke='%23fff' stroke-width='0.5'/%3E%3C/svg%3E&quot;);
            "
        />

        <!-- Gradient glow -->
        <div
            class="pointer-events-none fixed top-0 left-1/2 h-[600px] w-[900px] -translate-x-1/2 rounded-full bg-sky-500/[0.07] blur-[120px]"
        />

        <!-- Nav -->
        <header class="relative z-10 border-b border-white/[0.06]">
            <div
                class="mx-auto flex h-16 max-w-6xl items-center justify-between px-6"
            >
                <div class="flex items-center gap-3">
                    <div
                        class="flex h-8 w-8 items-center justify-center rounded-md bg-sky-500/20 ring-1 ring-sky-500/30"
                    >
                        <Thermometer class="h-4 w-4 text-sky-400" />
                    </div>
                    <span
                        class="text-sm font-semibold tracking-wide text-white/90"
                        >PMMS</span
                    >
                </div>
                <nav class="flex items-center gap-1">
                    <template v-if="$page.props.auth.user">
                        <Link
                            :href="dashboard().url"
                            class="inline-flex items-center gap-2 rounded-md bg-sky-500/10 px-4 py-2 text-sm font-medium text-sky-400 ring-1 ring-sky-500/20 transition hover:bg-sky-500/20 hover:ring-sky-500/30"
                        >
                            Dashboard
                            <ArrowRight class="h-3.5 w-3.5" />
                        </Link>
                    </template>
                    <template v-else>
                        <Link
                            :href="login()"
                            class="rounded-md px-4 py-2 text-sm font-medium text-white/60 transition hover:text-white/90"
                        >
                            Sign in
                        </Link>
                        <Link
                            :href="register()"
                            class="inline-flex items-center gap-2 rounded-md bg-sky-500/10 px-4 py-2 text-sm font-medium text-sky-400 ring-1 ring-sky-500/20 transition hover:bg-sky-500/20 hover:ring-sky-500/30"
                        >
                            Get Started
                            <ChevronRight class="h-3.5 w-3.5" />
                        </Link>
                    </template>
                </nav>
            </div>
        </header>

        <!-- Hero -->
        <section
            class="relative z-10 px-6 pt-24 pb-20 text-center lg:pt-36 lg:pb-28"
        >
            <div class="mx-auto max-w-4xl">
                <div
                    class="mb-6 inline-flex items-center gap-2 rounded-full border border-sky-500/20 bg-sky-500/10 px-4 py-1.5 text-xs font-medium text-sky-400"
                >
                    <span
                        class="h-1.5 w-1.5 animate-pulse rounded-full bg-sky-400"
                    />
                    Built for commercial HVAC contractors
                </div>
                <h1
                    class="text-4xl font-bold tracking-tight text-white sm:text-5xl lg:text-6xl"
                >
                    Manage every project<br class="hidden sm:block" />
                    <span
                        class="bg-gradient-to-r from-sky-400 via-cyan-300 to-teal-400 bg-clip-text text-transparent"
                        >from award to closeout</span
                    >
                </h1>
                <p
                    class="mx-auto mt-6 max-w-2xl text-lg leading-relaxed text-white/50"
                >
                    One system for submittals, shop drawings, equipment
                    procurement, installation tracking, TAB, punch lists, and
                    closeout. Purpose-built for precision mechanical teams.
                </p>
                <div
                    class="mt-10 flex flex-col items-center justify-center gap-4 sm:flex-row"
                >
                    <Link
                        :href="register()"
                        class="inline-flex items-center gap-2 rounded-lg bg-sky-500 px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-sky-500/25 transition hover:bg-sky-400 hover:shadow-sky-500/40"
                    >
                        Start managing projects
                        <ArrowRight class="h-4 w-4" />
                    </Link>
                    <Link
                        :href="login()"
                        class="inline-flex items-center gap-2 rounded-lg border border-white/10 bg-white/5 px-6 py-3 text-sm font-medium text-white/70 transition hover:border-white/20 hover:bg-white/10 hover:text-white"
                    >
                        Sign in to your account
                    </Link>
                </div>
            </div>
        </section>

        <!-- Pipeline -->
        <section
            class="relative z-10 border-y border-white/[0.06] bg-white/[0.01] py-16"
        >
            <div class="mx-auto max-w-6xl px-6">
                <div class="mb-10 text-center">
                    <h2
                        class="text-sm font-semibold tracking-widest text-sky-400/80 uppercase"
                    >
                        Project Lifecycle
                    </h2>
                    <p class="mt-2 text-lg text-white/50">
                        Every stage, tracked end to end
                    </p>
                </div>
                <div
                    class="flex flex-wrap items-center justify-center gap-x-2 gap-y-4 lg:gap-x-0"
                >
                    <template v-for="(stage, i) in stages" :key="stage.label">
                        <div class="flex items-center gap-2">
                            <div
                                class="group flex items-center gap-2.5 rounded-lg border border-white/[0.06] bg-white/[0.03] px-3.5 py-2.5 transition hover:border-white/[0.12] hover:bg-white/[0.06]"
                            >
                                <component
                                    :is="stage.icon"
                                    :class="['h-4 w-4', stage.color]"
                                />
                                <span
                                    class="text-xs font-medium text-white/70"
                                    >{{ stage.label }}</span
                                >
                            </div>
                            <ChevronRight
                                v-if="i < stages.length - 1"
                                class="hidden h-4 w-4 text-white/15 lg:block"
                            />
                        </div>
                    </template>
                </div>
            </div>
        </section>

        <!-- Features -->
        <section class="relative z-10 px-6 py-20 lg:py-28">
            <div class="mx-auto max-w-6xl">
                <div class="mb-14 text-center">
                    <h2
                        class="text-sm font-semibold tracking-widest text-sky-400/80 uppercase"
                    >
                        Core Capabilities
                    </h2>
                    <p
                        class="mt-2 text-3xl font-bold tracking-tight text-white"
                    >
                        Everything you need,<br class="sm:hidden" />
                        nothing you don't
                    </p>
                </div>
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <div
                        v-for="feature in features"
                        :key="feature.title"
                        class="group relative rounded-xl border border-white/[0.06] bg-white/[0.02] p-6 transition hover:border-white/[0.12] hover:bg-white/[0.04]"
                    >
                        <div
                            class="mb-4 flex h-10 w-10 items-center justify-center rounded-lg bg-sky-500/10 ring-1 ring-sky-500/20 transition group-hover:bg-sky-500/15 group-hover:ring-sky-500/30"
                        >
                            <component
                                :is="feature.icon"
                                class="h-5 w-5 text-sky-400"
                            />
                        </div>
                        <h3 class="mb-2 text-sm font-semibold text-white">
                            {{ feature.title }}
                        </h3>
                        <p class="text-sm leading-relaxed text-white/45">
                            {{ feature.description }}
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Bottom CTA -->
        <section class="relative z-10 border-t border-white/[0.06] px-6 py-20">
            <div class="mx-auto max-w-2xl text-center">
                <h2 class="text-3xl font-bold tracking-tight text-white">
                    Ready to run your projects right?
                </h2>
                <p class="mt-4 text-lg text-white/45">
                    Stop juggling spreadsheets, email threads, and half-tracked
                    submittals. Get the system your team actually needs.
                </p>
                <div
                    class="mt-10 flex flex-col items-center justify-center gap-4 sm:flex-row"
                >
                    <Link
                        :href="register()"
                        class="inline-flex items-center gap-2 rounded-lg bg-sky-500 px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-sky-500/25 transition hover:bg-sky-400 hover:shadow-sky-500/40"
                    >
                        Create your account
                        <ArrowRight class="h-4 w-4" />
                    </Link>
                    <Link
                        :href="login()"
                        class="inline-flex items-center gap-2 rounded-lg border border-white/10 bg-white/5 px-6 py-3 text-sm font-medium text-white/70 transition hover:border-white/20 hover:bg-white/10 hover:text-white"
                    >
                        Sign in
                    </Link>
                </div>
            </div>
        </section>

        <!-- Footer -->
        <footer class="relative z-10 border-t border-white/[0.06] bg-black/20">
            <div
                class="mx-auto flex h-14 max-w-6xl items-center justify-between px-6"
            >
                <div class="flex items-center gap-2.5">
                    <div
                        class="flex h-6 w-6 items-center justify-center rounded bg-sky-500/15"
                    >
                        <Thermometer class="h-3 w-3 text-sky-400" />
                    </div>
                    <span class="text-xs font-medium text-white/40">PMMS</span>
                </div>
                <div class="flex items-center gap-4 text-xs text-white/30">
                    <span>Precision Mechanical Management System</span>
                </div>
            </div>
        </footer>
    </div>
</template>
