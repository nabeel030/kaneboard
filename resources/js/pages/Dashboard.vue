<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';

import PlaceholderPattern from '../components/PlaceholderPattern.vue';

type RecentTicket = {
  id: number;
  title: string;
  status: string;
  created_at?: string | null;
  project?: { id: number; name: string } | null;
};

const props = defineProps<{
  stats: { projects: number; tickets: number; assignedToMe: number };
  statuses: string[];
  statusCounts: Record<string, number>;
  recentTickets: RecentTicket[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Dashboard', href: dashboard().url },
];

const labels: Record<string, string> = {
  backlog: 'Backlog',
  todo: 'Todo',
  in_progress: 'In progress',
  done: 'Done',
  tested: 'Tested',
  completed: 'Completed',
};

/** ✅ same visual language as Kaneboard (light & soft) */
const statusTheme: Record<string, string> = {
  backlog: 'bg-slate-50 text-slate-700 dark:bg-slate-950/40 dark:text-slate-200',
  todo: 'bg-blue-50 text-blue-700 dark:bg-blue-950/30 dark:text-blue-200',
  in_progress: 'bg-amber-50 text-amber-800 dark:bg-amber-950/30 dark:text-amber-200',
  done: 'bg-emerald-50 text-emerald-800 dark:bg-emerald-950/30 dark:text-emerald-200',
  tested: 'bg-purple-50 text-purple-800 dark:bg-purple-950/30 dark:text-purple-200',
  completed: 'bg-zinc-50 text-zinc-700 dark:bg-zinc-950/40 dark:text-zinc-200',
};

function themeFor(status: string) {
  return statusTheme[status] ?? statusTheme.completed;
}
</script>

<template>
  <Head title="Dashboard" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
      <!-- Top cards -->
      <div class="grid auto-rows-min gap-4 md:grid-cols-3">
        <div class="relative aspect-video overflow-hidden rounded-xl border" :class="themeFor('done')">
          <div class="absolute inset-0 opacity-40">
            <PlaceholderPattern />
          </div>
          <div class="relative z-10 flex h-full flex-col justify-between p-4">
            <div class="text-sm text-muted-foreground">Projects</div>
            <div class="text-4xl font-semibold">{{ props.stats.projects }}</div>
            <div class="text-xs text-muted-foreground">
              Projects you own or are assigned to
            </div>
          </div>
        </div>

        <div class="relative aspect-video overflow-hidden rounded-xl border" :class="themeFor('todo')">
          <div class="absolute inset-0 opacity-40">
            <PlaceholderPattern />
          </div>
          <div class="relative z-10 flex h-full flex-col justify-between p-4">
            <div class="text-sm text-muted-foreground">Tickets</div>
            <div class="text-4xl font-semibold">{{ props.stats.tickets }}</div>
            <div class="text-xs text-muted-foreground">
              Total tickets in your accessible projects
            </div>
          </div>
        </div>

        <div class="relative aspect-video overflow-hidden rounded-xl border" :class="themeFor('tested')">
          <div class="absolute inset-0 opacity-40">
            <PlaceholderPattern />
          </div>
          <div class="relative z-10 flex h-full flex-col justify-between p-4">
            <div class="text-sm text-muted-foreground">Assigned to me</div>
            <div class="text-4xl font-semibold">{{ props.stats.assignedToMe }}</div>
            <div class="text-xs text-muted-foreground">
              Tickets currently assigned to you
            </div>
          </div>
        </div>
      </div>

      <!-- Big section -->
      <div class="relative flex-1 rounded-xl border overflow-hidden">
        <div class="absolute inset-0 opacity-30">
          <PlaceholderPattern />
        </div>

        <div class="relative z-10 p-4 space-y-6">
          <div>
            <div class="text-lg font-semibold">Overview</div>
            <div class="text-sm text-muted-foreground">
              Ticket breakdown by status and recent activity.
            </div>
          </div>

          <!-- ✅ Status breakdown with colors -->
          <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
            <div
              v-for="s in props.statuses"
              :key="s"
              class="rounded-xl border p-4"
              :class="themeFor(s)"
            >
              <div class="flex items-center justify-between">
                <div class="font-medium">{{ labels[s] ?? s }}</div>
                <div class="rounded-full border px-2 py-1 text-xs">
                  {{ props.statusCounts[s] ?? 0 }}
                </div>
              </div>
            </div>
          </div>

          <!-- ✅ Recent tickets with same status tint -->
          <div class="rounded-xl border bg-background/60 p-4">
            <div class="flex items-center justify-between">
              <div class="font-semibold">Recent tickets</div>
              <div class="text-xs text-muted-foreground">Latest 6</div>
            </div>

            <div v-if="props.recentTickets.length === 0" class="mt-3 text-sm text-muted-foreground">
              No tickets yet.
            </div>

            <div v-else class="mt-3 space-y-2">
              <div
                v-for="t in props.recentTickets"
                :key="t.id"
                class="flex items-center justify-between gap-4 rounded-lg border p-3"
                :class="themeFor(t.status)"
              >
                <div class="min-w-0">
                  <div class="truncate font-medium">{{ t.title }}</div>
                  <div class="mt-1 text-xs opacity-80">
                    Project: {{ t.project?.name ?? '—' }} •
                    {{ labels[t.status] ?? t.status }}
                  </div>
                </div>

                <div class="text-xs opacity-70 shrink-0">
                  #{{ t.id }}
                </div>
              </div>
            </div>
          </div>

        </div>
      </div>
    </div>
  </AppLayout>
</template>
