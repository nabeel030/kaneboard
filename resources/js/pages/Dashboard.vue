<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed, onMounted, ref } from 'vue';

import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import projectRoutes from '@/routes/projects';
import { type BreadcrumbItem } from '@/types';
import ProgressPie from '@/components/ui/charts/ProgressPie.vue';

// vue-chartjs + chart.js
import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  BarElement,
  ArcElement,
  Tooltip,
  Legend,
  Filler,
  Title,
} from 'chart.js';
import { Line, Bar, Doughnut } from 'vue-chartjs';

ChartJS.register(
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  BarElement,
  ArcElement,
  Tooltip,
  Legend,
  Filler,
  Title
);

type Kpis = {
  projects: number;
  tickets: number;
  openTickets: number;
  overdue: number;
  dueSoon: number;
  completed7: number;
  myOpen: number;
};

type TrendPoint = { date: string; count: number };
type StatusCount = { status: string; count: number };

type RiskTicket = {
  id: number;
  title: string;
  status: string;
  deadline?: string | null;
  project?: { id: number; name: string } | null;
};

type ProjectRiskRow = {
  id: number;
  name: string;
  status: string; // ON_TRACK/AT_RISK/LATE/...
  expected_progress?: number | null; // 0..1
  actual_progress?: number | null; // 0..1
  end_date?: string | null;
  forecast_end?: string | null;
  confidence?: number | null;
  risk_signals?: string[];
};

const props = defineProps<{
  kpis: Kpis;

  completionTrend14: TrendPoint[];
  statusDistribution: StatusCount[];
  myWorkload: { open: number; done: number };

  riskTickets: RiskTicket[];
  riskyProjects: ProjectRiskRow[];
}>();

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Dashboard', href: dashboard().url }];

const statusLabels: Record<string, string> = {
  backlog: 'Backlog',
  todo: 'Todo',
  in_progress: 'In progress',
  done: 'Done',
  tested: 'Tested',
  completed: 'Completed',
};

const healthLabel = (s: string) => (s || 'UNKNOWN').replaceAll('_', ' ');
const pct = (v?: number | null) => Math.round(((v ?? 0) * 100));

const healthPill = (status: string) => {
  switch (status) {
    case 'ON_TRACK':
      return 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-900/60 dark:bg-emerald-950/25 dark:text-emerald-300';
    case 'AT_RISK':
      return 'border-yellow-300 bg-yellow-50 text-yellow-800 dark:border-yellow-900/60 dark:bg-yellow-950/25 dark:text-yellow-300';
    case 'LATE':
      return 'border-red-300 bg-red-50 text-red-700 dark:border-red-900/60 dark:bg-red-950/25 dark:text-red-300';
    case 'COMPLETED':
      return 'border-blue-200 bg-blue-50 text-blue-700 dark:border-blue-900/60 dark:bg-blue-950/25 dark:text-blue-300';
    case 'NO_SCHEDULE':
      return 'border-slate-200 bg-slate-50 text-slate-700 dark:border-slate-700 dark:bg-slate-900/40 dark:text-slate-200';
    default:
      return 'border-slate-200 bg-slate-50 text-slate-700 dark:border-slate-700 dark:bg-slate-900/40 dark:text-slate-200';
  }
};

// ---- Chart theme that adapts to your dark class ----
const isDark = ref(false);

function readDarkMode() {
  // works with Tailwind's "dark" class on <html>
  isDark.value = document.documentElement.classList.contains('dark');
}

onMounted(() => {
  readDarkMode();

  // lightweight watcher for class changes
  const obs = new MutationObserver(() => readDarkMode());
  obs.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
});

// Nice colors that work on both themes
const chartGridColor = computed(() => (isDark.value ? 'rgba(255,255,255,0.10)' : 'rgba(0,0,0,0.08)'));
const chartTextColor = computed(() => (isDark.value ? 'rgba(255,255,255,0.75)' : 'rgba(0,0,0,0.70)'));

// ---- Line chart (trend) ----
const lineData = computed(() => {
  const labels = (props.completionTrend14 ?? []).map(p => p.date.slice(5)); // "MM-DD"
  const values = (props.completionTrend14 ?? []).map(p => p.count);

  return {
    labels,
    datasets: [
      {
        label: 'Completed',
        data: values,
        tension: 0.35,
        fill: true,
        borderWidth: 2,
        pointRadius: 3,
        pointHoverRadius: 4,
        borderColor: isDark.value ? 'rgba(255,255,255,0.75)' : 'rgba(0,0,0,0.75)',
        backgroundColor: isDark.value ? 'rgba(255,255,255,0.10)' : 'rgba(0,0,0,0.06)',
      },
    ],
  };
});

const lineOptions = computed(() => ({
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: { display: false },
    tooltip: { enabled: true },
  },
  scales: {
    x: {
      grid: { color: chartGridColor.value },
      ticks: { color: chartTextColor.value, maxRotation: 0, autoSkip: true },
    },
    y: {
      beginAtZero: true,
      grid: { color: chartGridColor.value },
      ticks: { color: chartTextColor.value, precision: 0 },
    },
  },
}));

const trendAvgPerDay = computed(() => {
  const pts = props.completionTrend14 ?? [];
  const sum = pts.reduce((a, p) => a + (p.count || 0), 0);
  return Math.round((sum / Math.max(1, pts.length)) * 10) / 10;
});

// ---- Bar chart (status distribution) ----
const barData = computed(() => {
  const labels = props.statusDistribution.map(s => statusLabels[s.status] ?? s.status);
  const values = props.statusDistribution.map(s => s.count);

  // subtle per-status tint
  const colorsLight: Record<string, string> = {
    backlog: 'rgba(100,116,139,0.55)',
    todo: 'rgba(37,99,235,0.55)',
    in_progress: 'rgba(217,119,6,0.55)',
    done: 'rgba(5,150,105,0.55)',
    tested: 'rgba(124,58,237,0.55)',
    completed: 'rgba(82,82,91,0.55)',
  };
  const colorsDark: Record<string, string> = {
    backlog: 'rgba(148,163,184,0.60)',
    todo: 'rgba(96,165,250,0.60)',
    in_progress: 'rgba(251,191,36,0.60)',
    done: 'rgba(52,211,153,0.60)',
    tested: 'rgba(167,139,250,0.60)',
    completed: 'rgba(161,161,170,0.60)',
  };

  const bg = props.statusDistribution.map(s => (isDark.value ? colorsDark : colorsLight)[s.status] ?? (isDark.value ? 'rgba(255,255,255,0.35)' : 'rgba(0,0,0,0.25)'));

  return {
    labels,
    datasets: [
      {
        label: 'Tickets',
        data: values,
        backgroundColor: bg,
        borderRadius: 10,
        borderSkipped: false,
      },
    ],
  };
});

const barOptions = computed(() => ({
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: { display: false },
    tooltip: { enabled: true },
  },
  scales: {
    x: {
      grid: { display: false },
      ticks: { color: chartTextColor.value },
    },
    y: {
      beginAtZero: true,
      grid: { color: chartGridColor.value },
      ticks: { color: chartTextColor.value, precision: 0 },
    },
  },
}));

// ---- Doughnut (my workload) ----
const donutData = computed(() => {
  const open = props.myWorkload?.open ?? 0;
  const done = props.myWorkload?.done ?? 0;

  return {
    labels: ['Done', 'Open'],
    datasets: [
      {
        data: [done, open],
        backgroundColor: isDark.value
          ? ['rgba(52,211,153,0.70)', 'rgba(248,113,113,0.55)']
          : ['rgba(5,150,105,0.65)', 'rgba(239,68,68,0.50)'],
        borderWidth: 0,
      },
    ],
  };
});

const donutOptions = computed(() => ({
  responsive: true,
  maintainAspectRatio: false,
  cutout: '72%',
  plugins: {
    legend: { display: false },
    tooltip: { enabled: true },
  },
}));

const donutPct = computed(() => {
  const open = props.myWorkload?.open ?? 0;
  const done = props.myWorkload?.done ?? 0;
  const total = Math.max(1, open + done);
  return Math.round((done / total) * 100);
});


</script>

<template>
  <Head title="Dashboard" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="p-4 space-y-6">
      <!-- Header -->
      <div class="flex flex-col gap-1">
        <div class="text-2xl font-semibold">Workspace Dashboard</div>
        <div class="text-sm text-muted-foreground">
          Trends, workload, risks — everything you need to prioritize today.
        </div>
      </div>

      <!-- KPI Strip -->
      <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-7">
        <div class="rounded-2xl border bg-background p-4 shadow-sm">
          <div class="text-xs text-muted-foreground">Projects</div>
          <div class="mt-1 text-2xl font-semibold">{{ props.kpis.projects }}</div>
        </div>

        <div class="rounded-2xl border bg-background p-4 shadow-sm">
          <div class="text-xs text-muted-foreground">Tickets</div>
          <div class="mt-1 text-2xl font-semibold">{{ props.kpis.tickets }}</div>
        </div>

        <div class="rounded-2xl border bg-background p-4 shadow-sm">
          <div class="text-xs text-muted-foreground">Open</div>
          <div class="mt-1 text-2xl font-semibold">{{ props.kpis.openTickets }}</div>
        </div>

        <div class="rounded-2xl border bg-background p-4 shadow-sm">
          <div class="text-xs text-muted-foreground">Overdue</div>
          <div class="mt-1 text-2xl font-semibold text-red-700 dark:text-red-300">
            {{ props.kpis.overdue }}
          </div>
        </div>

        <div class="rounded-2xl border bg-background p-4 shadow-sm">
          <div class="text-xs text-muted-foreground">Due soon</div>
          <div class="mt-1 text-2xl font-semibold text-yellow-800 dark:text-yellow-300">
            {{ props.kpis.dueSoon }}
          </div>
        </div>

        <div class="rounded-2xl border bg-background p-4 shadow-sm">
          <div class="text-xs text-muted-foreground">Completed (7d)</div>
          <div class="mt-1 text-2xl font-semibold text-emerald-700 dark:text-emerald-300">
            {{ props.kpis.completed7 }}
          </div>
        </div>

        <div class="rounded-2xl border bg-background p-4 shadow-sm">
          <div class="text-xs text-muted-foreground">My open</div>
          <div class="mt-1 text-2xl font-semibold">{{ props.kpis.myOpen }}</div>
        </div>
      </div>

      <!-- Charts Row -->
      <div class="grid gap-4 lg:grid-cols-3">
        <!-- Trend line -->
        <div class="rounded-2xl border bg-background p-5 shadow-sm lg:col-span-2">
          <div class="flex items-start justify-between gap-3">
            <div>
              <div class="text-lg font-semibold">Completion trend</div>
              <div class="text-sm text-muted-foreground">Tickets completed per day (last 14 days)</div>
            </div>
            <div class="text-right">
              <div class="text-xs text-muted-foreground">Avg/day</div>
              <div class="text-sm font-semibold">{{ trendAvgPerDay }}</div>
            </div>
          </div>

          <div class="mt-4 h-56">
            <Line :data="lineData" :options="lineOptions" />
          </div>
        </div>

        <!-- Status distribution + my workload -->
        <div class="rounded-2xl border bg-background p-5 shadow-sm space-y-5">
          <div>
            <div class="text-lg font-semibold">Status distribution</div>
            <div class="text-sm text-muted-foreground">Where the work currently lives</div>

            <div class="mt-4 h-56">
              <Bar :data="barData" :options="barOptions" />
            </div>
          </div>

          <div class="rounded-2xl border bg-muted/10 p-4">
            <div class="flex items-start justify-between">
              <div>
                <div class="text-sm font-semibold">My workload</div>
                <div class="text-xs text-muted-foreground">Assigned to me</div>
              </div>
              <div class="text-xs text-muted-foreground">
                {{ props.myWorkload.done }} done • {{ props.myWorkload.open }} open
              </div>
            </div>

            <div class="mt-4 flex items-center gap-4">
              <div class="relative h-24 w-24">
                <Doughnut :data="donutData" :options="donutOptions" />
                <div class="absolute inset-0 flex items-center justify-center">
                  <div class="text-sm font-semibold">{{ donutPct }}%</div>
                </div>
              </div>

              <div class="min-w-0">
                <div class="text-sm text-muted-foreground">Completion rate</div>
                <div class="text-xl font-semibold">{{ donutPct }}%</div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Action Row -->
      <div class="grid gap-4 lg:grid-cols-2">
        <!-- Risk tickets -->
        <div class="rounded-2xl border bg-background p-5 shadow-sm">
          <div>
            <div class="text-lg font-semibold">Top risk tickets</div>
            <div class="text-sm text-muted-foreground">
              Overdue and due-soon tickets that need attention
            </div>
          </div>

          <div v-if="props.riskTickets.length === 0" class="mt-4 rounded-xl border bg-muted/10 p-4 text-sm text-muted-foreground">
            No risky tickets 🎉
          </div>

          <div v-else class="mt-4 space-y-2">
            <div
              v-for="t in props.riskTickets"
              :key="t.id"
              class="rounded-xl border bg-muted/10 p-4 hover:bg-muted/20"
            >
              <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                  <div class="truncate font-medium">{{ t.title }}</div>
                  <div class="mt-1 text-xs text-muted-foreground">
                    Project: {{ t.project?.name ?? '—' }} •
                    <span class="font-medium">{{ statusLabels[t.status] ?? t.status }}</span>
                    <span v-if="t.deadline"> • Due: {{ t.deadline }}</span>
                  </div>
                </div>
                <div class="shrink-0 text-xs text-muted-foreground">#{{ t.id }}</div>
              </div>
            </div>
          </div>
        </div>

        <!-- Risky projects -->
        <div class="rounded-2xl border bg-background p-5 shadow-sm">
          <div>
            <div class="text-lg font-semibold">Projects needing attention</div>
            <div class="text-sm text-muted-foreground">
              Late / at-risk projects across your workspace
            </div>
          </div>

          <div v-if="props.riskyProjects.length === 0" class="mt-4 rounded-xl border bg-muted/10 p-4 text-sm text-muted-foreground">
            No risky projects 🎉
          </div>

          <div v-else class="mt-4 space-y-3">
            <div
              v-for="p in props.riskyProjects"
              :key="p.id"
              class="rounded-2xl border bg-muted/10 p-4 hover:bg-muted/20"
            >
              <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                  <Link class="truncate font-semibold hover:underline block" :href="projectRoutes.show(p.id).url">
                    {{ p.name }}
                  </Link>

                  <div class="mt-1 text-xs text-muted-foreground">
                    End: <span class="font-medium text-foreground">{{ p.end_date ?? '—' }}</span>
                    <span v-if="p.forecast_end"> • Forecast: <span class="font-medium text-foreground">{{ p.forecast_end }}</span></span>
                    <span v-if="p.confidence != null"> • Confidence: <span class="font-medium">{{ p.confidence }}%</span></span>
                  </div>
                </div>

                <span class="shrink-0 rounded-full border px-3 py-1 text-xs font-semibold" :class="healthPill(p.status)">
                  {{ healthLabel(p.status) }}
                </span>
              </div>

              <div class="mt-3 grid grid-cols-2 gap-4">
  <!-- Expected -->
  <div class="rounded-xl border bg-background p-3 dark:border-slate-800 flex items-center gap-4">
    <ProgressPie
      :value="pct(p.expected_progress)"
      label="Expected"
      color="#0284c7" 
    />

    <div>
      <div class="text-xs text-muted-foreground"> Expected progress</div>
    </div>
  </div>

  <!-- Actual -->
  <div class="rounded-xl border bg-background p-3 dark:border-slate-800 flex items-center gap-4">
    <ProgressPie
      :value="pct(p.actual_progress)"
      label="Actual"
      color="#059669" 
    />

    <div>
      <div class="text-xs text-muted-foreground">Actual progress</div>
    </div>
  </div>
</div>

              

              <div v-if="p.risk_signals?.length" class="mt-3">
                <div class="text-xs font-semibold text-muted-foreground">Signals</div>
                <ul class="mt-2 space-y-1 text-xs text-muted-foreground">
                  <li v-for="(s, idx) in p.risk_signals.slice(0, 2)" :key="idx" class="flex gap-2">
                    <span class="mt-[2px]">•</span>
                    <span class="truncate">{{ s }}</span>
                  </li>
                </ul>
              </div>
            </div>

            <div class="text-xs text-muted-foreground">
              Tip: click a project to open its health dashboard.
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
