<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed, reactive } from 'vue';

import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import projectRoutes from '@/routes/projects';
import { type BreadcrumbItem } from '@/types';
import Select2Multi from '@/components/Select2Multi.vue';

type User = {
  id: number;
  name: string;
  email?: string;
};

type ProjectHealth = {
  status: string;
  message?: string;

  expected_progress?: number; // 0..1
  actual_progress?: number;   // 0..1

  start_date?: string | null;
  end_date?: string | null;

  forecast_end?: string | null;
  confidence?: number | null;
  throughput_per_day?: number | null;

  tickets?: {
    total: number;
    done: number;
    open: number;
    overdue: number;
    due_soon: number;
  };

  scope_creep_pct?: number;
  risk_signals?: string[];
};

const props = defineProps<{
  project: {
    id: number;
    owner_id: number;
    name: string;
    description?: string | null;

    start_date?: string | null;
    end_date?: string | null;
    baseline_start_date?: string | null;
    baseline_end_date?: string | null;
  };
  owner: User | null;
  members: User[];
  allMembers: User[];
  can: {
    update: boolean;
    delete: boolean;
    manageMembers: boolean;
  };
  projectHealth: ProjectHealth;
}>();

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Dashboard', href: dashboard().url },
  { title: 'Projects', href: projectRoutes.index().url },
  { title: props.project.name, href: projectRoutes.show(props.project.id).url },
];

function destroyProject() {
  if (!confirm('Delete this project? This action cannot be undone.')) return;
  router.delete(projectRoutes.destroy(props.project.id).url);
}

/** Members modal **/
const ui = reactive({
  addMemberOpen: false,
});

const addMemberForm = useForm<{ user_ids: number[] }>({
  user_ids: [],
});

const assignedIds = computed(() => new Set(props.members.map((m) => m.id)));

const availableOptions = computed(() => {
  return props.allMembers.filter(
    (u) => !assignedIds.value.has(u.id) && u.id !== props.project.owner_id
  );
});

const select2Options = computed(() =>
  availableOptions.value.map((u) => ({
    id: u.id,
    text: u.email ? `${u.name} (${u.email})` : u.name,
  }))
);

function openAddMember() {
  addMemberForm.reset();
  addMemberForm.clearErrors();
  ui.addMemberOpen = true;
}

function closeAddMember() {
  ui.addMemberOpen = false;
}

function submitAddMember() {
  addMemberForm.post(`/projects/${props.project.id}/members`, {
    preserveScroll: true,
    onSuccess: () => closeAddMember(),
  });
}

function removeMember(userId: number) {
  if (!confirm('Remove this member from the project?')) return;

  router.delete(`/projects/${props.project.id}/members/${userId}`, {
    preserveScroll: true,
  });
}

// ---------- Project health UI helpers ----------
const health = computed(() => props.projectHealth);

const expectedPct = computed(() => Math.round(((health.value.expected_progress ?? 0) * 100)));
const actualPct = computed(() => Math.round(((health.value.actual_progress ?? 0) * 100)));

const showHealthDetails = computed(() => {
  return !['NO_SCHEDULE', 'INVALID_SCHEDULE'].includes(health.value.status);
});

const hasForecast = computed(() => !!health.value.forecast_end);

const statusLabel = computed(() => {
  const s = health.value.status || 'UNKNOWN';
  return s.replaceAll('_', ' ');
});

const statusPill = computed(() => {
  switch (health.value.status) {
    case 'ON_TRACK':
      return 'border-green-200 bg-green-50 text-green-700 dark:border-green-900/60 dark:bg-green-950/40 dark:text-green-300';
    case 'AT_RISK':
      return 'border-yellow-300 bg-yellow-50 text-yellow-800 dark:border-yellow-900/60 dark:bg-yellow-950/35 dark:text-yellow-300';
    case 'LATE':
      return 'border-red-300 bg-red-50 text-red-700 dark:border-red-900/60 dark:bg-red-950/35 dark:text-red-300';
    case 'COMPLETED':
      return 'border-blue-200 bg-blue-50 text-blue-700 dark:border-blue-900/60 dark:bg-blue-950/35 dark:text-blue-300';
    case 'NOT_STARTED':
      return 'border-slate-200 bg-slate-50 text-slate-700 dark:border-slate-700 dark:bg-slate-900/40 dark:text-slate-200';
    case 'NO_SCHEDULE':
      return 'border-slate-200 bg-slate-50 text-slate-700 dark:border-slate-700 dark:bg-slate-900/40 dark:text-slate-200';
    case 'INVALID_SCHEDULE':
      return 'border-red-200 bg-red-50 text-red-700 dark:border-red-900/60 dark:bg-red-950/35 dark:text-red-300';
    default:
      return 'border-slate-200 bg-slate-50 text-slate-700 dark:border-slate-700 dark:bg-slate-900/40 dark:text-slate-200';
  }
});

// Nice “insight” helpers (pure UI; no logic changes)
const gapPct = computed(() => Math.max(0, expectedPct.value - actualPct.value));
const aheadPct = computed(() => Math.max(0, actualPct.value - expectedPct.value));

const ticketsTotal = computed(() => health.value.tickets?.total ?? 0);
const ticketsDone = computed(() => health.value.tickets?.done ?? 0);
const ticketsOpen = computed(() => health.value.tickets?.open ?? 0);
const ticketsOverdue = computed(() => health.value.tickets?.overdue ?? 0);
const ticketsDueSoon = computed(() => health.value.tickets?.due_soon ?? 0);

const deltaPct = computed(() => actualPct.value - expectedPct.value);

const deltaLabel = computed(() => {
  if (deltaPct.value > 0) return `+${deltaPct.value}% ahead of plan`;
  if (deltaPct.value < 0) return `${deltaPct.value}% behind plan`;
  return 'On plan';
});

const deltaTone = computed(() => {
  if (deltaPct.value > 0) return 'border-green-200 bg-green-50 text-green-700 dark:border-green-900/60 dark:bg-green-950/35 dark:text-green-300';
  if (deltaPct.value < 0) return 'border-red-200 bg-red-50 text-red-700 dark:border-red-900/60 dark:bg-red-950/35 dark:text-red-300';
  return 'border-slate-200 bg-slate-50 text-slate-700 dark:border-slate-700 dark:bg-slate-900/40 dark:text-slate-200';
});

const confidenceTone = computed(() => {
  const c = health.value.confidence ?? null;
  if (c == null) return 'text-slate-700 dark:text-slate-200';
  if (c >= 80) return 'text-green-700 dark:text-green-300';
  if (c >= 60) return 'text-yellow-700 dark:text-yellow-300';
  return 'text-red-700 dark:text-red-300';
});

const initials = (name?: string) => {
  if (!name) return '?';
  const parts = name.trim().split(/\s+/).slice(0, 2);
  return parts.map(p => p[0]?.toUpperCase()).join('');
};
</script>

<template>
  <Head :title="project.name" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="p-4 space-y-6">
      <!-- HERO -->
      <div
        class="rounded-2xl border p-5 shadow-sm
               bg-gradient-to-br from-slate-50 via-white to-slate-100
               dark:border-slate-800 dark:from-slate-950 dark:via-slate-950 dark:to-slate-900"
      >
        <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
          <div class="min-w-0">
            <div class="flex items-center gap-3">
              <div class="text-2xl font-semibold truncate">
                {{ project.name }}
              </div>

              <span
                class="inline-flex items-center rounded-full border px-3 py-1 text-xs font-semibold shadow-sm"
                :class="statusPill"
              >
                {{ statusLabel }}
              </span>
            </div>

            <div v-if="owner" class="mt-1 text-sm text-muted-foreground">
              Owned by <span class="font-medium text-foreground">{{ owner.name }}</span>
              <span v-if="owner.email"> ({{ owner.email }})</span>
            </div>

            <div v-if="project.description" class="mt-2 text-sm text-muted-foreground">
              {{ project.description }}
            </div>

            <div v-if="showHealthDetails" class="mt-3 flex flex-wrap gap-2">
              <span class="rounded-full border bg-background px-3 py-1 text-xs text-muted-foreground shadow-sm dark:border-slate-800">
                Plan:
                <span class="font-medium text-foreground">{{ health.start_date }}</span>
                →
                <span class="font-medium text-foreground">{{ health.end_date }}</span>
              </span>
            </div>
          </div>

          <div class="flex items-center gap-2">
            <Link
                :href="'/ticket-board?project=' + project.id"
                class="cursor-pointer rounded-xl border px-3 py-2 text-sm text-blue-600 hover:bg-blue-50 shadow-sm
                        dark:border-slate-800 dark:text-blue-300 dark:hover:bg-blue-950/30"
                >
                Ticket Board
            </Link>
            <button
              v-if="can.delete"
              @click="destroyProject"
              class="cursor-pointer rounded-xl border px-3 py-2 text-sm text-red-600 hover:bg-red-50 shadow-sm
                     dark:border-slate-800 dark:text-red-300 dark:hover:bg-red-950/30"
            >
              Delete
            </button>
          </div>
        </div>

        <div class="mt-5 grid gap-3 md:grid-cols-4">
          <div class="rounded-xl border p-4 shadow-sm
                      bg-emerald-50/50 border-emerald-100
                      dark:bg-emerald-950/20 dark:border-emerald-900/40">
            <div class="text-xs text-emerald-700/80 dark:text-emerald-300/80">Actual progress</div>
            <div class="mt-1 text-2xl font-semibold text-emerald-900 dark:text-emerald-200">{{ actualPct }}%</div>
            <div class="mt-2 h-2 w-full overflow-hidden rounded bg-emerald-100 dark:bg-emerald-900/30">
              <div class="h-2 bg-emerald-600 dark:bg-emerald-400" :style="{ width: actualPct + '%' }" />
            </div>
          </div>

          <div class="rounded-xl border p-4 shadow-sm
                      bg-sky-50/50 border-sky-100
                      dark:bg-sky-950/20 dark:border-sky-900/40">
            <div class="text-xs text-sky-700/80 dark:text-sky-300/80">Expected progress</div>
            <div class="mt-1 text-2xl font-semibold text-sky-900 dark:text-sky-200">{{ expectedPct }}%</div>
            <div class="mt-2 h-2 w-full overflow-hidden rounded bg-sky-100 dark:bg-sky-900/30">
              <div class="h-2 bg-sky-600 dark:bg-sky-400" :style="{ width: expectedPct + '%' }" />
            </div>
          </div>

          <div class="rounded-xl border p-4 shadow-sm
                      bg-violet-50/50 border-violet-100
                      dark:bg-violet-950/20 dark:border-violet-900/40">
            <div class="text-xs text-violet-700/80 dark:text-violet-300/80">Tickets</div>
            <div class="mt-1 text-2xl font-semibold text-violet-900 dark:text-violet-200">{{ ticketsDone }}/{{ ticketsTotal }}</div>
            <div class="mt-1 text-sm text-violet-800/70 dark:text-violet-200/70">{{ ticketsOpen }} open</div>
          </div>

          <div
            class="rounded-xl border p-4 shadow-sm"
            :class="gapPct > 0
              ? 'bg-red-50/60 border-red-200 dark:bg-red-950/20 dark:border-red-900/40'
              : aheadPct > 0
                ? 'bg-green-50/60 border-green-200 dark:bg-green-950/20 dark:border-green-900/40'
                : 'bg-slate-50/60 dark:bg-slate-900/40 dark:border-slate-800'"
          >
            <div class="text-xs text-muted-foreground">Risk</div>

            <div v-if="gapPct > 0" class="mt-1 text-2xl font-semibold text-red-700 dark:text-red-300">
              -{{ gapPct }}%
            </div>
            <div v-else-if="aheadPct > 0" class="mt-1 text-2xl font-semibold text-green-700 dark:text-green-300">
              +{{ aheadPct }}%
            </div>
            <div v-else class="mt-1 text-2xl font-semibold text-slate-700 dark:text-slate-200">0%</div>

            <div
              class="mt-1 text-sm"
              :class="gapPct > 0 ? 'text-red-700 dark:text-red-300' : aheadPct > 0 ? 'text-green-700 dark:text-green-300' : 'text-slate-600 dark:text-slate-300'"
            >
              {{ gapPct > 0 ? 'behind plan' : aheadPct > 0 ? 'ahead of plan' : 'on plan' }}
            </div>
          </div>
        </div>
      </div>

      <div class="grid gap-4 lg:grid-cols-3">
        <div class="rounded-2xl border p-5 shadow-sm
                    bg-gradient-to-b from-indigo-50/40 to-background
                    dark:border-slate-800 dark:from-indigo-950/15 dark:to-background">
          <div class="flex items-start justify-between gap-3">
            <div>
              <div class="text-sm font-medium">Forecast</div>
              <div class="mt-1 text-xs text-muted-foreground">Based on recent completion rate</div>
            </div>
            <span class="rounded-full border bg-background px-2 py-1 text-xs text-muted-foreground shadow-sm dark:border-slate-800">
              14-day window
            </span>
          </div>

          <div v-if="!showHealthDetails" class="mt-4 rounded-xl border bg-muted/20 p-3 text-sm dark:border-slate-800">
            <div class="font-medium">{{ health.message ?? 'Project schedule is not configured.' }}</div>
            <div class="mt-1 text-muted-foreground">
              Set <span class="font-medium">start_date</span> and <span class="font-medium">end_date</span> to enable forecasting.
            </div>
          </div>

          <div v-else class="mt-4 space-y-3">
            <div class="rounded-xl border p-3 shadow-sm bg-white/60 dark:bg-slate-950/30 dark:border-slate-800">
              <div class="flex items-center justify-between gap-3">
                <div class="text-sm font-medium">Actual vs Expected</div>
                <span class="rounded-full border px-2 py-1 text-xs font-semibold" :class="deltaTone">
                  {{ deltaLabel }}
                </span>
              </div>

              <div class="mt-3 grid grid-cols-2 gap-3">
                <div class="rounded-lg border bg-background p-3 dark:border-slate-800">
                  <div class="text-xs text-muted-foreground">Expected</div>
                  <div class="mt-1 text-xl font-semibold text-sky-900 dark:text-sky-200">{{ expectedPct }}%</div>
                  <div class="mt-2 h-2 w-full overflow-hidden rounded bg-sky-100 dark:bg-sky-900/30">
                    <div class="h-2 bg-sky-600 dark:bg-sky-400" :style="{ width: expectedPct + '%' }" />
                  </div>
                </div>

                <div class="rounded-lg border bg-background p-3 dark:border-slate-800">
                  <div class="text-xs text-muted-foreground">Actual</div>
                  <div class="mt-1 text-xl font-semibold text-emerald-900 dark:text-emerald-200">{{ actualPct }}%</div>
                  <div class="mt-2 h-2 w-full overflow-hidden rounded bg-emerald-100 dark:bg-emerald-900/30">
                    <div class="h-2 bg-emerald-600 dark:bg-emerald-400" :style="{ width: actualPct + '%' }" />
                  </div>
                </div>
              </div>
            </div>

            <div class="flex items-center justify-between">
              <div class="text-sm text-muted-foreground">Forecast end</div>
              <div class="text-sm font-semibold text-indigo-900 dark:text-indigo-200">{{ hasForecast ? health.forecast_end : '—' }}</div>
            </div>

            <div class="flex items-center justify-between">
              <div class="text-sm text-muted-foreground">Confidence</div>
              <div class="text-sm font-semibold" :class="confidenceTone">
                {{ health.confidence != null ? `${health.confidence}%` : '—' }}
              </div>
            </div>

            <div class="flex items-center justify-between">
              <div class="text-sm text-muted-foreground">Throughput/day</div>
              <div class="text-sm font-semibold text-indigo-900 dark:text-indigo-200">
                {{ health.throughput_per_day != null ? health.throughput_per_day : '—' }}
              </div>
            </div>

            <div
              v-if="health.scope_creep_pct != null"
              class="rounded-xl border p-3 shadow-sm"
              :class="health.scope_creep_pct > 30
                ? 'bg-red-50 border-red-200 dark:bg-red-950/20 dark:border-red-900/40'
                : 'bg-yellow-50 border-yellow-200 dark:bg-yellow-950/15 dark:border-yellow-900/40'"
            >
              <div class="flex items-center justify-between">
                <div class="text-sm text-muted-foreground">Scope creep</div>
                <div
                  class="text-sm font-semibold"
                  :class="health.scope_creep_pct > 30 ? 'text-red-700 dark:text-red-300' : 'text-yellow-800 dark:text-yellow-300'"
                >
                  ~{{ health.scope_creep_pct }}%
                </div>
              </div>
              <div class="mt-1 text-xs text-muted-foreground">
                Extra tickets created after the baseline start date.
              </div>
            </div>
          </div>
        </div>

        <div class="rounded-2xl border p-5 shadow-sm
                    bg-gradient-to-b from-violet-50/40 to-background
                    dark:border-slate-800 dark:from-violet-950/15 dark:to-background">
          <div class="flex items-start justify-between">
            <div>
              <div class="text-sm font-medium">Tickets</div>
              <div class="mt-1 text-xs text-muted-foreground">Operational snapshot</div>
            </div>
          </div>

          <div class="mt-4 grid grid-cols-2 gap-3">
            <div class="rounded-xl border p-3 shadow-sm bg-white/60 dark:bg-slate-950/30 dark:border-slate-800">
              <div class="text-xs text-muted-foreground">Total</div>
              <div class="mt-1 text-xl font-semibold text-violet-900 dark:text-violet-200">{{ ticketsTotal }}</div>
            </div>

            <div class="rounded-xl border p-3 shadow-sm bg-green-50/60 border-green-100 dark:bg-green-950/15 dark:border-green-900/40">
              <div class="text-xs text-muted-foreground">Done</div>
              <div class="mt-1 text-xl font-semibold text-green-800 dark:text-green-300">{{ ticketsDone }}</div>
            </div>

            <div class="rounded-xl border p-3 shadow-sm bg-red-50/60 border-red-100 dark:bg-red-950/20 dark:border-red-900/40">
              <div class="text-xs text-muted-foreground">Overdue</div>
              <div class="mt-1 text-xl font-semibold text-red-700 dark:text-red-300">{{ ticketsOverdue }}</div>
            </div>

            <div class="rounded-xl border p-3 shadow-sm bg-yellow-50/60 border-yellow-100 dark:bg-yellow-950/15 dark:border-yellow-900/40">
              <div class="text-xs text-muted-foreground">Due soon</div>
              <div class="mt-1 text-xl font-semibold text-yellow-800 dark:text-yellow-300">{{ ticketsDueSoon }}</div>
            </div>
          </div>

          <div class="mt-4 rounded-xl border p-3 shadow-sm bg-white/60 dark:bg-slate-950/30 dark:border-slate-800">
            <div class="flex items-center justify-between">
              <div class="text-sm text-muted-foreground">Progress</div>
              <div class="text-sm font-semibold text-emerald-900 dark:text-emerald-200">{{ actualPct }}%</div>
            </div>
            <div class="mt-2 h-2 w-full overflow-hidden rounded bg-emerald-100 dark:bg-emerald-900/30">
              <div class="h-2 bg-emerald-600 dark:bg-emerald-400" :style="{ width: actualPct + '%' }" />
            </div>
            <div class="mt-2 text-xs text-muted-foreground">
              {{ ticketsOpen }} open tickets remaining.
            </div>
          </div>
        </div>

        <div class="rounded-2xl border p-5 shadow-sm
                    bg-gradient-to-b from-red-50/40 to-background
                    dark:border-slate-800 dark:from-red-950/15 dark:to-background">
          <div class="flex items-start justify-between gap-3">
            <div>
              <div class="text-sm font-medium">Risk signals</div>
              <div class="mt-1 text-xs text-muted-foreground">What’s driving the status</div>
            </div>
          </div>

          <div v-if="health.risk_signals?.length" class="mt-4 space-y-2">
            <div
              v-for="(s, idx) in health.risk_signals"
              :key="idx"
              class="rounded-xl border-l-4 border-red-400 bg-red-50/70 p-3 text-sm shadow-sm
                     dark:border-red-500/60 dark:bg-red-950/20"
            >
              <div class="flex items-start gap-2">
                <span class="text-red-600 font-bold dark:text-red-300">⚠</span>
                <div class="text-sm">{{ s }}</div>
              </div>
            </div>
          </div>

          <div v-else class="mt-4 rounded-xl border p-3 text-sm text-muted-foreground shadow-sm bg-white/60 dark:bg-slate-950/30 dark:border-slate-800">
            No risk signals detected.
          </div>
        </div>
      </div>

      <div class="rounded-2xl border p-5 shadow-sm
                  bg-gradient-to-b from-slate-50/50 to-background
                  dark:border-slate-800 dark:from-slate-950/30 dark:to-background">
        <div class="mb-4 flex items-center justify-between gap-3">
          <div>
            <div class="text-lg font-semibold">Members</div>
            <div class="mt-1 text-sm text-muted-foreground">
              Members can only see projects they are assigned to.
            </div>
          </div>

          <button
            v-if="can.manageMembers"
            type="button"
            class="cursor-pointer rounded-xl border bg-white px-3 py-2 text-sm hover:bg-muted/40 shadow-sm
                   dark:bg-slate-950/30 dark:border-slate-800"
            @click="openAddMember"
          >
            + Add Members
          </button>
        </div>

        <div v-if="members.length === 0" class="text-sm text-muted-foreground">
          No members assigned to this project.
        </div>

        <div v-else class="grid gap-3 md:grid-cols-2">
          <div
            v-for="m in members"
            :key="m.id"
            class="flex items-center justify-between gap-3 rounded-xl border p-4 shadow-sm
                   bg-white/70 hover:bg-white
                   dark:bg-slate-950/30 dark:border-slate-800 dark:hover:bg-slate-950/40"
          >
            <div class="flex items-center gap-3 min-w-0">
              <div
                class="flex h-10 w-10 items-center justify-center rounded-full border text-sm font-semibold
                       bg-gradient-to-br from-slate-200 to-slate-300 text-slate-800
                       dark:from-slate-800 dark:to-slate-700 dark:text-slate-100 dark:border-slate-700"
              >
                {{ initials(m.name) }}
              </div>

              <div class="min-w-0">
                <div class="truncate font-medium">{{ m.name }}</div>
                <div v-if="m.email" class="truncate text-sm text-muted-foreground">{{ m.email }}</div>
              </div>
            </div>

            <div class="flex items-center gap-2">
              <span
                v-if="m.id === project.owner_id"
                class="rounded-full border bg-white px-2 py-1 text-xs text-muted-foreground shadow-sm
                       dark:bg-slate-950/30 dark:border-slate-800"
              >
                Owner
              </span>

              <button
                v-if="can.manageMembers && m.id !== project.owner_id"
                type="button"
                class="cursor-pointer rounded-xl border bg-white px-3 py-2 text-sm text-red-600 hover:bg-red-50 shadow-sm
                       dark:bg-slate-950/30 dark:border-slate-800 dark:text-red-300 dark:hover:bg-red-950/30"
                @click="removeMember(m.id)"
              >
                Remove
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <transition name="fade">
      <div
        v-if="ui.addMemberOpen"
        class="fixed inset-0 z-50 flex items-center justify-center"
        aria-modal="true"
        role="dialog"
        @keydown.esc="closeAddMember"
      >
        <div class="absolute inset-0 cursor-pointer bg-black/40" @click="closeAddMember" />

        <transition name="pop">
          <div class="relative z-10 w-full max-w-lg rounded-2xl border bg-background p-5 shadow-xl dark:border-slate-800">
            <div class="flex items-start justify-between gap-3">
              <div>
                <div class="text-lg font-semibold">Add Members</div>
                <div class="mt-1 text-sm text-muted-foreground">
                  Select one or more members to assign to this project.
                </div>
              </div>

              <button
                class="cursor-pointer rounded p-2 hover:bg-muted/40"
                type="button"
                @click="closeAddMember"
                aria-label="Close"
              >
                ✕
              </button>
            </div>

            <form class="mt-4 space-y-3" @submit.prevent="submitAddMember">
              <div>
                <label class="text-sm">Members</label>

                <div class="mt-1">
                  <Select2Multi
                    v-model="addMemberForm.user_ids"
                    :options="select2Options"
                    placeholder="Select members..."
                    :disabled="addMemberForm.processing || select2Options.length === 0"
                  />
                </div>

                <div v-if="select2Options.length === 0" class="mt-2 text-sm text-muted-foreground">
                  Everyone is already assigned to this project.
                </div>

                <div v-if="addMemberForm.errors.user_ids" class="mt-1 text-sm text-red-600">
                  {{ addMemberForm.errors.user_ids }}
                </div>
              </div>

              <div class="mt-4 flex items-center justify-end gap-2">
                <button
                  type="button"
                  class="cursor-pointer rounded-xl border px-3 py-2 text-sm dark:border-slate-800"
                  @click="closeAddMember"
                  :disabled="addMemberForm.processing"
                >
                  Cancel
                </button>

                <button
                  type="submit"
                  class="cursor-pointer rounded-xl bg-black px-3 py-2 text-sm text-white disabled:cursor-not-allowed disabled:opacity-50"
                  :disabled="addMemberForm.processing || addMemberForm.user_ids.length === 0"
                >
                  <span v-if="addMemberForm.processing">Adding…</span>
                  <span v-else>Add Selected</span>
                </button>
              </div>
            </form>
          </div>
        </transition>
      </div>
    </transition>
  </AppLayout>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 120ms ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
.pop-enter-active {
  transition: transform 140ms ease, opacity 140ms ease;
}
.pop-enter-from {
  transform: scale(0.98);
  opacity: 0;
}
.pop-leave-to {
  transform: scale(0.98);
  opacity: 0;
}
</style>
