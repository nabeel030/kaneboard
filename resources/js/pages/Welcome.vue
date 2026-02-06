<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import { dashboard, login, register } from '@/routes';
import AppLogoIcon from '@/components/AppLogoIcon.vue';

withDefaults(
  defineProps<{
    canRegister: boolean;
  }>(),
  { canRegister: true },
);

type Role = 'pm' | 'dev' | 'lead';
type TourKey = 'dashboard' | 'board' | 'health' | 'members';

const role = ref<Role>('pm');
const tour = ref<TourKey>('members');

const roleTabs: { key: Role; label: string }[] = [
  { key: 'pm', label: 'PM / PO' },
  { key: 'dev', label: 'Developers' },
  { key: 'lead', label: 'Leads' },
];

const roleCopy: Record<
  Role,
  {
    badge: string;
    headlineA: string;
    headlineB: string;
    sub: string;
    bullets: string[];
  }
> = {
  pm: {
    badge: 'For PMs & Product Owners',
    headlineA: 'Predictable delivery',
    headlineB: 'without chasing updates',
    sub: 'Turn ticket flow into a delivery narrative with progress tracking, risk signals, and stakeholder-ready reporting.',
    bullets: [
      'Progress vs expected: defend timelines with evidence',
      'Risk signals: overdue work surfaces early',
      'Less status meetings: the board becomes the update',
    ],
  },
  dev: {
    badge: 'For Developers',
    headlineA: 'Fast execution',
    headlineB: 'without heavy process',
    sub: 'A developer-friendly board with filters, lanes, and clean cards that keeps flow moving.',
    bullets: [
      'Power filters: search, assignee, priority, type, due',
      'Clear lanes: backlog → todo → in progress → done',
      'Less context switching: one clean place to run work',
    ],
  },
  lead: {
    badge: 'For Leads',
    headlineA: 'See what’s moving',
    headlineB: 'and what’s at risk',
    sub: 'Portfolio clarity with project health signals, risk indicators, and operational snapshots — without digging into every ticket.',
    bullets: [
      'Project health: progress vs expected at a glance',
      'Risk signals: overdue + due soon surfaced clearly',
      'Faster decisions: focus attention where it matters',
    ],
  },
};

const heroImage = computed(() => {
  // optional hero.png, fallback to ticket board
  return '/images/landing/hero.png';
});

const heroImageFallback = '/images/landing/ticket-board.png';

type Tour = {
  key: TourKey;
  tab: string;
  title: string;
  desc: string;
  importanceTitle: string;
  importance: string[];
  img: string;
  // Use fixed height so “members.png vertical” issue is avoided
  frameHeight: string;
};

const tours: Tour[] = [
  {
    key: 'dashboard',
    tab: 'Dashboard',
    title: 'Portfolio overview',
    desc: 'See projects, tickets, and recent activity in one clean snapshot.',
    importanceTitle: 'Why it matters',
    importance: ['Less reporting', 'Faster triage', 'Instant context for stakeholders'],
    img: '/images/landing/dashboard.png',
    frameHeight: 'h-[430px]',
  },
  {
    key: 'board',
    tab: 'Ticket Board',
    title: 'Execution board built for flow',
    desc: 'Lanes, filters, and clear cards keep work moving without friction.',
    importanceTitle: 'Why it matters',
    importance: ['Faster execution', 'Clear ownership', 'Less context switching'],
    img: '/images/landing/ticket-board.png',
    frameHeight: 'h-[430px]',
  },
  {
    key: 'health',
    tab: 'Project Health',
    title: 'Progress vs expected + risk signals',
    desc: 'Make delivery measurable with objective progress tracking and early warning signals.',
    importanceTitle: 'Why it matters',
    importance: ['Defend timelines', 'Spot risk early', 'Control scope creep'],
    img: '/images/landing/project.png',
    frameHeight: 'h-[430px]',
  },
  {
    key: 'members',
    tab: 'Members',
    title: 'Simple team & access management',
    desc: 'Onboard members cleanly and control project visibility without confusion.',
    importanceTitle: 'Why it matters',
    importance: ['Easy onboarding', 'Project assignment', 'Clear structure'],
    img: '/images/landing/members.png',
    frameHeight: 'h-[480px]', // members tends to look “vertical”; give it a bit more canvas height
  },
];

const currentTour = computed(() => tours.find(t => t.key === tour.value) ?? tours[0]);

// Role → default tour (makes the “live feature tour” feel role-aware)
const roleDefaultTour: Record<Role, TourKey> = {
  pm: 'health',
  dev: 'board',
  lead: 'dashboard',
};

watch(role, (r) => {
  tour.value = roleDefaultTour[r];
});
</script>

<template>
  <Head title="Welcome">
    <link rel="preconnect" href="https://rsms.me/" />
    <link rel="stylesheet" href="https://rsms.me/inter/inter.css" />
  </Head>

  <div class="min-h-screen bg-[#fff8f6] text-[#121212] dark:bg-[#070707] dark:text-white">
    <!-- Subtle gradient background like your screenshot -->
    <div class="pointer-events-none fixed inset-0 overflow-hidden">
      <div class="absolute -top-48 left-[-120px] h-[520px] w-[520px] rounded-full bg-[#f53003]/18 blur-[120px]" />
      <div class="absolute top-40 right-[-180px] h-[520px] w-[520px] rounded-full bg-[#fb923c]/12 blur-[120px]" />
      <div class="absolute bottom-[-240px] left-1/2 h-[560px] w-[560px] -translate-x-1/2 rounded-full bg-[#60a5fa]/10 blur-[150px]" />
      <div
        class="absolute inset-0 opacity-[0.05] dark:opacity-[0.06]"
        style="
          background-image:
            radial-gradient(circle at 20% 15%, rgba(245,48,3,0.12), transparent 40%),
            radial-gradient(circle at 85% 10%, rgba(251,146,60,0.10), transparent 45%),
            radial-gradient(circle at 60% 85%, rgba(96,165,250,0.10), transparent 45%);
        "
      />
    </div>

    <!-- NAV -->
    <header class="relative mx-auto max-w-6xl px-5 pt-6 lg:px-8">
      <div class="flex items-center justify-between rounded-2xl border border-black/10 bg-white/70 px-4 py-3 shadow-sm backdrop-blur-md dark:border-white/10 dark:bg-white/5">
        <div class="flex items-center gap-3">
          <div class="grid h-9 w-9 place-items-center rounded-xl border border-black/10 bg-white shadow-sm dark:border-white/10 dark:bg-white/10">
            <AppLogoIcon class="size-9 fill-current text-black dark:text-white" />
          </div>
          <div class="leading-tight">
            <div class="text-sm font-semibold tracking-tight">Kaneboard</div>
            <div class="text-xs text-black/55 dark:text-white/55">Run work. See progress</div>
          </div>
        </div>

        <div class="flex items-center gap-2">
          <template v-if="$page.props.auth.user">
            <Link
              :href="dashboard()"
              class="inline-flex items-center justify-center rounded-xl bg-black px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-black/90 dark:bg-white dark:text-black dark:hover:bg-white/90"
            >
              Open app
            </Link>
          </template>

          <template v-else>
            <Link
              :href="login()"
              class="inline-flex items-center justify-center rounded-xl px-4 py-2 text-sm font-medium text-black/80 transition hover:bg-black/5 dark:text-white/80 dark:hover:bg-white/10"
            >
              Log in
            </Link>
            <Link
              v-if="canRegister"
              :href="register()"
              class="inline-flex items-center justify-center rounded-xl bg-[#f53003] px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-[#dd2a03]"
            >
              Get Started
            </Link>
          </template>
        </div>
      </div>
    </header>

    <!-- HERO -->
    <main class="relative mx-auto max-w-6xl px-5 pb-16 pt-8 lg:px-8">
      <section class="grid gap-10 lg:grid-cols-2 lg:items-center">
        <!-- Left -->
        <div>
          <!-- Role tabs -->
          <div class="inline-flex rounded-2xl border border-black/10 bg-white/70 p-1 shadow-sm backdrop-blur-md dark:border-white/10 dark:bg-white/5">
            <button
              v-for="t in roleTabs"
              :key="t.key"
              type="button"
              @click="role = t.key"
              class="rounded-xl px-3 py-2 text-xs font-semibold transition"
              :class="role === t.key
                ? 'bg-[#f53003] text-white'
                : 'text-black/70 hover:bg-black/5 dark:text-white/70 dark:hover:bg-white/10'"
            >
              {{ t.label }}
            </button>
          </div>

          <div class="mt-4 inline-flex items-center gap-2 rounded-full border border-black/10 bg-white/70 px-3 py-1 text-xs font-semibold text-black/70 shadow-sm backdrop-blur-md dark:border-white/10 dark:bg-white/5 dark:text-white/70">
            <span class="h-1.5 w-1.5 rounded-full bg-[#22c55e]" />
            <span>{{ roleCopy[role].badge }}</span>
          </div>

          <h1 class="mt-4 text-3xl font-semibold leading-tight tracking-tight sm:text-4xl">
            <span class="text-black dark:text-white">{{ roleCopy[role].headlineA }}</span>
            <br />
            <span class="text-[#f53003]">{{ roleCopy[role].headlineB }}</span>
          </h1>

          <p class="mt-3 max-w-xl text-sm leading-relaxed text-black/65 dark:text-white/70">
            {{ roleCopy[role].sub }}
          </p>

          <ul class="mt-5 space-y-2 text-sm text-black/70 dark:text-white/70">
            <li v-for="b in roleCopy[role].bullets" :key="b" class="flex gap-2">
              <span class="mt-2 h-1.5 w-1.5 shrink-0 rounded-full bg-[#f53003]" />
              <span>{{ b }}</span>
            </li>
          </ul>

          <div class="mt-6 flex flex-wrap items-center gap-3">
            <template v-if="$page.props.auth.user">
              <Link
                :href="dashboard()"
                class="inline-flex items-center justify-center rounded-xl bg-[#f53003] px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-[#dd2a03]"
              >
                Go to Dashboard
              </Link>
            </template>

            <template v-else>
              <Link
                :href="register()"
                class="inline-flex items-center justify-center rounded-xl bg-[#f53003] px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-[#dd2a03]"
              >
                Start tracking delivery
              </Link>
              <button
                type="button"
                class="inline-flex items-center justify-center rounded-xl border border-black/10 bg-white/70 px-5 py-3 text-sm font-semibold text-black/80 shadow-sm backdrop-blur-md transition hover:bg-white dark:border-white/10 dark:bg-white/5 dark:text-white/80 dark:hover:bg-white/10"
                @click="tour = roleDefaultTour[role]"
              >
                Watch demo
              </button>
            </template>
          </div>

          <div class="mt-5 flex flex-wrap items-center gap-4 text-xs text-black/55 dark:text-white/55">
            <span class="inline-flex items-center gap-2">
              <span class="grid h-5 w-5 place-items-center rounded-full bg-black/5 dark:bg-white/10">⚡</span>
              2-minute setup
            </span>
            <span class="inline-flex items-center gap-2">
              <span class="grid h-5 w-5 place-items-center rounded-full bg-black/5 dark:bg-white/10">🔒</span>
              Roles & access
            </span>
            <span class="inline-flex items-center gap-2">
              <span class="grid h-5 w-5 place-items-center rounded-full bg-black/5 dark:bg-white/10">📈</span>
              Delivery signals
            </span>
          </div>
        </div>

        <!-- Right: preview -->
        <div class="relative">
          <div class="absolute -inset-4 rounded-[32px] bg-gradient-to-br from-[#f53003]/10 via-transparent to-[#fb923c]/10 blur-0" />
          <div class="relative overflow-hidden rounded-3xl border border-black/10 bg-white/70 p-4 shadow-lg backdrop-blur-md dark:border-white/10 dark:bg-white/5">
            <!-- Browser chrome -->
            <div class="mb-3 flex items-center gap-2">
              <span class="h-2.5 w-2.5 rounded-full bg-red-400/80" />
              <span class="h-2.5 w-2.5 rounded-full bg-yellow-400/80" />
              <span class="h-2.5 w-2.5 rounded-full bg-green-400/80" />
              <span class="ml-2 text-xs text-black/50 dark:text-white/50">kaneboard.app</span>
            </div>

            <div class="overflow-hidden rounded-2xl border border-black/10 bg-white dark:border-white/10 dark:bg-white/5">
              <img
                :src="heroImage"
                :onerror="`this.onerror=null;this.src='${heroImageFallback}';`"
                alt="Kaneboard preview"
                class="h-[360px] w-full object-contain bg-white/60 dark:bg-white/5"
              />
            </div>

            <!-- Floating callouts -->
            <div class="pointer-events-none absolute right-6 top-16 hidden rounded-2xl border border-black/10 bg-white/90 px-3 py-2 text-xs shadow-sm backdrop-blur-md lg:block dark:border-white/10 dark:bg-black/40">
              <div class="font-semibold">31 tickets done</div>
              <div class="text-black/60 dark:text-white/60">this sprint</div>
            </div>

            <div class="pointer-events-none absolute left-6 bottom-8 hidden rounded-2xl border border-black/10 bg-white/90 px-3 py-2 text-xs shadow-sm backdrop-blur-md lg:block dark:border-white/10 dark:bg-black/40">
              <div class="font-semibold text-[#16a34a]">+18%</div>
              <div class="text-black/60 dark:text-white/60">ahead of plan</div>
            </div>
          </div>
        </div>
      </section>

      <!-- LIVE TOUR -->
      <section class="mt-16">
        <div class="text-center">
          <div class="text-[11px] font-semibold tracking-widest text-[#f53003]">LIVE FEATURE TOUR</div>
          <h2 class="mt-2 text-2xl font-semibold tracking-tight">See Kaneboard in action</h2>
          <p class="mx-auto mt-2 max-w-2xl text-sm text-black/60 dark:text-white/60">
            Explore the key screens that make delivery measurable and execution fast.
          </p>
        </div>

        <div class="mt-6 flex flex-wrap items-center justify-center gap-2">
          <button
            v-for="t in tours"
            :key="t.key"
            type="button"
            @click="tour = t.key"
            class="rounded-full border px-4 py-2 text-xs font-semibold transition"
            :class="tour === t.key
              ? 'border-black/10 bg-black text-white dark:border-white/10 dark:bg-white dark:text-black'
              : 'border-black/10 bg-white/70 text-black/70 hover:bg-white dark:border-white/10 dark:bg-white/5 dark:text-white/70 dark:hover:bg-white/10'"
          >
            {{ t.tab }}
          </button>
        </div>

        <div class="mt-8 grid gap-6 lg:grid-cols-12 lg:items-center">
          <div class="lg:col-span-4">
            <div class="rounded-3xl border border-black/10 bg-white/70 p-6 shadow-sm backdrop-blur-md dark:border-white/10 dark:bg-white/5">
              <div class="text-xs font-semibold text-black/60 dark:text-white/60">{{ currentTour.title }}</div>
              <p class="mt-2 text-sm text-black/70 dark:text-white/70">{{ currentTour.desc }}</p>

              <div class="mt-4 text-xs font-semibold text-black/60 dark:text-white/60">
                {{ currentTour.importanceTitle }}
              </div>
              <ul class="mt-2 space-y-2 text-sm text-black/70 dark:text-white/70">
                <li v-for="i in currentTour.importance" :key="i" class="flex gap-2">
                  <span class="mt-2 h-1.5 w-1.5 shrink-0 rounded-full bg-[#f53003]" />
                  <span>{{ i }}</span>
                </li>
              </ul>

              <div class="mt-5">
                <Link
                  v-if="canRegister && !$page.props.auth.user"
                  :href="register()"
                  class="inline-flex items-center justify-center rounded-xl bg-[#f53003] px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-[#dd2a03]"
                >
                  Try it free
                </Link>
              </div>
            </div>
          </div>

          <div class="lg:col-span-8">
            <div class="overflow-hidden rounded-3xl border border-black/10 bg-white/70 p-4 shadow-lg backdrop-blur-md dark:border-white/10 dark:bg-white/5">
              <div class="mb-3 flex items-center gap-2">
                <span class="h-2.5 w-2.5 rounded-full bg-red-400/80" />
                <span class="h-2.5 w-2.5 rounded-full bg-yellow-400/80" />
                <span class="h-2.5 w-2.5 rounded-full bg-green-400/80" />
                <span class="ml-2 text-xs text-black/50 dark:text-white/50">kaneboard.app</span>
              </div>

              <div class="overflow-hidden rounded-2xl border border-black/10 bg-white dark:border-white/10 dark:bg-white/5">
                <img
                  :src="currentTour.img"
                  :alt="currentTour.title"
                  class="w-full object-contain bg-white/60 dark:bg-white/5"
                  :class="currentTour.frameHeight"
                  loading="lazy"
                />
              </div>
            </div>
          </div>
        </div>
      </section>

      <section class="mt-16">
        <div class="text-center">
          <div class="text-[11px] font-semibold tracking-widest text-[#f53003]">FEATURES</div>
          <h2 class="mt-2 text-2xl font-semibold tracking-tight">
            Everything you need to <span class="text-[#f53003]">ship with confidence</span>
          </h2>
          <p class="mx-auto mt-2 max-w-2xl text-sm text-black/60 dark:text-white/60">
            A developer-friendly board meets PM-ready delivery insights. One tool for execution and visibility.
          </p>
        </div>

        <div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
          <div class="rounded-3xl border border-black/10 bg-white/70 p-5 shadow-sm backdrop-blur-md dark:border-white/10 dark:bg-white/5">
            <div class="text-sm font-semibold">Kanban Board</div>
            <p class="mt-2 text-sm text-black/60 dark:text-white/60">
              Fast lanes with drag & drop, filters, priorities, and due dates.
            </p>
            <div class="mt-3 flex flex-wrap gap-2 text-[11px] text-black/60 dark:text-white/60">
              <span class="rounded-full bg-black/5 px-2 py-1 dark:bg-white/10">Lanes</span>
              <span class="rounded-full bg-black/5 px-2 py-1 dark:bg-white/10">Filters</span>
              <span class="rounded-full bg-black/5 px-2 py-1 dark:bg-white/10">Due dates</span>
            </div>
          </div>

          <div class="rounded-3xl border border-black/10 bg-white/70 p-5 shadow-sm backdrop-blur-md dark:border-white/10 dark:bg-white/5">
            <div class="text-sm font-semibold">Project Health</div>
            <p class="mt-2 text-sm text-black/60 dark:text-white/60">
              See actual vs expected progress. Track completion rate and delivery pressure.
            </p>
            <div class="mt-3 flex flex-wrap gap-2 text-[11px] text-black/60 dark:text-white/60">
              <span class="rounded-full bg-black/5 px-2 py-1 dark:bg-white/10">Progress</span>
              <span class="rounded-full bg-black/5 px-2 py-1 dark:bg-white/10">Forecast</span>
              <span class="rounded-full bg-black/5 px-2 py-1 dark:bg-white/10">Scope</span>
            </div>
          </div>

          <div class="rounded-3xl border border-black/10 bg-white/70 p-5 shadow-sm backdrop-blur-md dark:border-white/10 dark:bg-white/5">
            <div class="text-sm font-semibold">Risk Signals</div>
            <p class="mt-2 text-sm text-black/60 dark:text-white/60">
              Overdue tickets, delivery pressure, and blockers surface before they become problems.
            </p>
            <div class="mt-3 flex flex-wrap gap-2 text-[11px] text-black/60 dark:text-white/60">
              <span class="rounded-full bg-black/5 px-2 py-1 dark:bg-white/10">Overdue</span>
              <span class="rounded-full bg-black/5 px-2 py-1 dark:bg-white/10">Due soon</span>
              <span class="rounded-full bg-black/5 px-2 py-1 dark:bg-white/10">Alerts</span>
            </div>
          </div>

          <div class="rounded-3xl border border-black/10 bg-white/70 p-5 shadow-sm backdrop-blur-md dark:border-white/10 dark:bg-white/5">
            <div class="text-sm font-semibold">Team Management</div>
            <p class="mt-2 text-sm text-black/60 dark:text-white/60">
              Add members, assign to projects, and control visibility with a simple structure.
            </p>
            <div class="mt-3 flex flex-wrap gap-2 text-[11px] text-black/60 dark:text-white/60">
              <span class="rounded-full bg-black/5 px-2 py-1 dark:bg-white/10">Members</span>
              <span class="rounded-full bg-black/5 px-2 py-1 dark:bg-white/10">Access</span>
              <span class="rounded-full bg-black/5 px-2 py-1 dark:bg-white/10">Assignments</span>
            </div>
          </div>
        </div>

        <div class="mt-6 grid gap-4 lg:grid-cols-3">
          <div class="rounded-3xl border border-black/10 bg-white/70 p-5 text-center shadow-sm backdrop-blur-md dark:border-white/10 dark:bg-white/5">
            <div class="text-2xl font-semibold text-[#f53003]">50%</div>
            <div class="mt-1 text-xs text-black/60 dark:text-white/60">fewer status meetings</div>
          </div>
          <div class="rounded-3xl border border-black/10 bg-white/70 p-5 text-center shadow-sm backdrop-blur-md dark:border-white/10 dark:bg-white/5">
            <div class="text-2xl font-semibold text-[#f53003]">3×</div>
            <div class="mt-1 text-xs text-black/60 dark:text-white/60">faster risk detection</div>
          </div>
          <div class="rounded-3xl border border-black/10 bg-white/70 p-5 text-center shadow-sm backdrop-blur-md dark:border-white/10 dark:bg-white/5">
            <div class="text-2xl font-semibold text-[#f53003]">2min</div>
            <div class="mt-1 text-xs text-black/60 dark:text-white/60">to first project</div>
          </div>
        </div>
      </section>

      <section class="mt-16">
        <div class="text-center">
          <div class="text-[11px] font-semibold tracking-widest text-[#f53003]">TESTIMONIALS</div>
          <h2 class="mt-2 text-2xl font-semibold tracking-tight">
            Trusted by teams who <span class="text-[#f53003]">ship on time</span>
          </h2>
        </div>

        <div class="mt-8 grid gap-4 lg:grid-cols-3">
          <div class="rounded-3xl border border-black/10 bg-white/70 p-6 shadow-sm backdrop-blur-md dark:border-white/10 dark:bg-white/5">
            <p class="text-sm text-black/70 dark:text-white/70">
              “Finally a board that gives me real delivery signals — not vibes. I can defend timelines with actual data.”
            </p>
            <div class="mt-4 text-xs font-semibold">Product Owner</div>
            <div class="text-xs text-black/50 dark:text-white/50">Tech Startup</div>
          </div>

          <div class="rounded-3xl border border-black/10 bg-white/70 p-6 shadow-sm backdrop-blur-md dark:border-white/10 dark:bg-white/5">
            <p class="text-sm text-black/70 dark:text-white/70">
              “Filters + lanes are fast. The project health view makes reporting painless. It’s the tool I wish we had years ago.”
            </p>
            <div class="mt-4 text-xs font-semibold">Engineering Lead</div>
            <div class="text-xs text-black/50 dark:text-white/50">SaaS Company</div>
          </div>

          <div class="rounded-3xl border border-black/10 bg-white/70 p-6 shadow-sm backdrop-blur-md dark:border-white/10 dark:bg-white/5">
            <p class="text-sm text-black/70 dark:text-white/70">
              “We cut status meeting time because the dashboard answers everything stakeholders ask.”
            </p>
            <div class="mt-4 text-xs font-semibold">Project Manager</div>
            <div class="text-xs text-black/50 dark:text-white/50">Agency</div>
          </div>
        </div>
      </section>

      <section class="mt-16">
        <div class="grid gap-6 lg:grid-cols-12 lg:items-start">
          <div class="lg:col-span-4">
            <div class="text-[11px] font-semibold tracking-widest text-[#f53003]">HOW IT WORKS</div>
            <h2 class="mt-2 text-2xl font-semibold tracking-tight">Get value in <span class="text-[#f53003]">2 minutes</span></h2>
            <p class="mt-2 text-sm text-black/60 dark:text-white/60">
              Simple setup. Clear outcomes. A tool that sells itself because it actually works.
            </p>

            <div class="mt-5">
              <Link
                v-if="canRegister && !$page.props.auth.user"
                :href="register()"
                class="inline-flex items-center justify-center rounded-xl bg-[#f53003] px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-[#dd2a03]"
              >
                Start free today
              </Link>
            </div>
          </div>

          <div class="lg:col-span-8">
            <div class="grid gap-4 sm:grid-cols-2">
              <div class="rounded-3xl border border-black/10 bg-white/70 p-5 shadow-sm backdrop-blur-md dark:border-white/10 dark:bg-white/5">
                <div class="text-[11px] font-semibold text-[#f53003]">STEP 01</div>
                <div class="mt-2 text-sm font-semibold">Create a project window</div>
                <p class="mt-1 text-sm text-black/60 dark:text-white/60">
                  Define plan dates and track progress against reality. Set your baseline and start measuring.
                </p>
              </div>

              <div class="rounded-3xl border border-black/10 bg-white/70 p-5 shadow-sm backdrop-blur-md dark:border-white/10 dark:bg-white/5">
                <div class="text-[11px] font-semibold text-[#f53003]">STEP 02</div>
                <div class="mt-2 text-sm font-semibold">Run tickets through lanes</div>
                <p class="mt-1 text-sm text-black/60 dark:text-white/60">
                  Keep execution fast with filters, priorities, and due dates. Developers love the simplicity.
                </p>
              </div>

              <div class="rounded-3xl border border-black/10 bg-white/70 p-5 shadow-sm backdrop-blur-md dark:border-white/10 dark:bg-white/5">
                <div class="text-[11px] font-semibold text-[#f53003]">STEP 03</div>
                <div class="mt-2 text-sm font-semibold">Watch risk signals</div>
                <p class="mt-1 text-sm text-black/60 dark:text-white/60">
                  Overdue tickets and delivery pressure become visible before they explode. No surprises.
                </p>
              </div>

              <div class="rounded-3xl border border-black/10 bg-white/70 p-5 shadow-sm backdrop-blur-md dark:border-white/10 dark:bg-white/5">
                <div class="text-[11px] font-semibold text-[#f53003]">STEP 04</div>
                <div class="mt-2 text-sm font-semibold">Share status instantly</div>
                <p class="mt-1 text-sm text-black/60 dark:text-white/60">
                  Stakeholders get clarity without manual reports. The board becomes the update.
                </p>
              </div>
            </div>
          </div>
        </div>
      </section>

      <section class="mt-16">
        <div class="rounded-[28px] border border-black/10 bg-white/70 p-6 shadow-sm backdrop-blur-md dark:border-white/10 dark:bg-white/5">
          <div class="flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
            <div>
              <div class="inline-flex items-center gap-2 rounded-full border border-black/10 bg-white/70 px-3 py-1 text-xs font-semibold text-black/70 dark:border-white/10 dark:bg-white/5 dark:text-white/70">
                <span class="h-1.5 w-1.5 rounded-full bg-[#f53003]" />
                Ready to ship?
              </div>
              <h3 class="mt-3 text-xl font-semibold tracking-tight">
                Make delivery measurable — <span class="text-[#f53003]">not “vibes-based”</span>
              </h3>
              <p class="mt-2 text-sm text-black/60 dark:text-white/60">
                Kaneboard is where execution meets confidence. PMs, devs, and stakeholders align on what’s actually happening.
              </p>
            </div>

            <div class="flex flex-wrap items-center gap-3">
              <template v-if="$page.props.auth.user">
                <Link
                  :href="dashboard()"
                  class="inline-flex items-center justify-center rounded-xl bg-[#f53003] px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-[#dd2a03]"
                >
                  Open app →
                </Link>
              </template>

              <template v-else>
                <Link
                  v-if="canRegister"
                  :href="register()"
                  class="inline-flex items-center justify-center rounded-xl bg-[#f53003] px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-[#dd2a03]"
                >
                  Get started free →
                </Link>
              </template>
            </div>
          </div>
        </div>
      </section>

      <!-- FOOTER -->
      <footer class="mt-10 flex flex-col items-center justify-between gap-3 border-t border-black/10 pt-6 text-xs text-black/55 dark:border-white/10 dark:text-white/55 lg:flex-row">
        <div class="flex items-center gap-2">
            <AppLogoIcon class="size-9 fill-current text-black dark:text-white" />
          <span>Kaneboard</span>
        </div>
        <div class="flex items-center gap-4">
          <a href="#" class="hover:underline" @click.prevent>Privacy</a>
          <a href="#" class="hover:underline" @click.prevent>Terms</a>
          <a href="#" class="hover:underline" @click.prevent>Status</a>
          <a href="#" class="hover:underline" @click.prevent>Docs</a>
        </div>
        <div>© {{ new Date().getFullYear() }} Kaneboard. All rights reserved.</div>
      </footer>
    </main>
  </div>
</template>
