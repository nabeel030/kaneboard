<script setup lang="ts">
import { Head, Link, usePage, useForm, router } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';

import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import Select2Multi from '@/components/Select2Multi.vue'; // (not used here but kept if your file expects it)

import dayjs from 'dayjs';
import relativeTime from 'dayjs/plugin/relativeTime';

dayjs.extend(relativeTime);

function fromNow(date: string) {
  return dayjs(date).fromNow();
}

type User = { id: number; name: string; email?: string | null };

type Comment = {
  id: number;
  body: string;
  created_at: string;
  user: User;
  user_id: number;
};

type Attachment = {
  id: number;
  original_name: string;
  size?: number | null;
  mime_type?: string | null;
  path: string;
  created_at?: string | null;
};

type TimerState = {
  running: boolean;
  elapsed_seconds: number;      // ✅ TOTAL seconds up to NOW (server-truth)
  started_at?: string | null;   // optional (only for badge/extra info)
};

type Ticket = {
  id: number;
  title: string;
  description?: string | null;
  status: string;
  position?: number;
  created_at?: string | null;

  creator?: User | null;
  assignee?: User | null;
  created_by?: number;
  assigned_to?: number | null;

  attachments?: Attachment[];
  project?: { id: number; name: string } | null;
  priority?: string | 'low';
  is_overdue: number;
  deadline?: string | null;

  started_at?: string | null;
  completed_at?: string | null;
  estimate?: number | null;
};

const props = defineProps<{
  ticket: Ticket;
  statuses?: string[];
  comments: Comment[];
  timer?: TimerState | null;
}>();

const labels: Record<string, string> = {
  backlog: 'Backlog',
  todo: 'Todo',
  in_progress: 'In progress',
  done: 'Done',
  tested: 'Tested',
  completed: 'Completed',
};

const page = usePage();
const authUser = page.props.auth?.user as User | null;

const canSee = computed(() => !!props.ticket?.id);

function formatBytes(bytes?: number | null) {
  if (!bytes && bytes !== 0) return '';
  const units = ['B', 'KB', 'MB', 'GB', 'TB'];
  let n = bytes;
  let u = 0;
  while (n >= 1024 && u < units.length - 1) {
    n = n / 1024;
    u++;
  }
  return `${n.toFixed(u === 0 ? 0 : 1)} ${units[u]}`;
}

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Dashboard', href: dashboard().url },
  { title: 'Ticket Board', href: '/ticket-board' },
  { title: `Ticket #${props.ticket.id}`, href: `/tickets/${props.ticket.id}` },
];

function isImage(mime?: string | null) {
  return !!mime && mime.startsWith('image/');
}
function isPdf(mime?: string | null) {
  return mime === 'application/pdf';
}

/* ---------------------------
   ✅ Comments
---------------------------- */
const commentForm = useForm({ body: '' });

function submitComment() {
  if (!props.ticket?.id) return;

  commentForm.post(`/tickets/${props.ticket.id}/comments`, {
    preserveScroll: true,
    onSuccess: () => commentForm.reset('body'),
  });
}

function deleteComment(commentId: number) {
  if (!confirm('Delete this comment?')) return;

  commentForm.delete(`/tickets/${props.ticket.id}/comments/${commentId}`, {
    preserveScroll: true,
  });
}

function canDeleteComment(c: Comment) {
  return !!authUser?.id && c.user_id === authUser.id && dayjs().diff(dayjs(c.created_at), 'minute') < 5;
}

function priorityClasses(priority?: string) {
  switch (priority) {
    case 'high':
      return 'bg-red-100 text-red-700 border-red-300 dark:bg-red-950/30 dark:text-red-200 dark:border-red-900/50';
    case 'medium':
      return 'bg-orange-100 text-orange-800 border-orange-300 dark:bg-orange-950/30 dark:text-orange-200 dark:border-orange-900/50';
    case 'low':
    default:
      return 'bg-blue-100 text-blue-800 border-blue-300 dark:bg-blue-950/30 dark:text-blue-200 dark:border-blue-900/50';
  }
}

function formatDeadline(deadline?: string | null) {
  if (!deadline) return '';
  return deadline.split('T')[0];
}

/* ---------------------------
   ✅ TIMER UI (Server-truth)
   - Server returns elapsed_seconds INCLUDING running time up to NOW
   - Frontend only increments locally while running
---------------------------- */

const isInProgress = computed(() => props.ticket.status === 'in_progress');
const isDoneLike = computed(() => ['done', 'tested', 'completed'].includes(props.ticket.status));

const timerRunning = ref<boolean>(props.timer?.running ?? false);
const elapsedSeconds = ref<number>(props.timer?.elapsed_seconds ?? 0);

// optional, not used in math anymore — just in case you want to show tooltip later
const runningStartedAt = ref<string | null>(props.timer?.started_at ?? null);

let tickInterval: number | null = null;
let lastTickMs: number | null = null;

function startTicking() {
  stopTicking();
  lastTickMs = Date.now();

  tickInterval = window.setInterval(() => {
    if (!timerRunning.value) return;

    const nowMs = Date.now();
    // const deltaSec = Math.floor((nowMs - (lastTickMs ?? nowMs)) / 1000);
    const raw = Math.floor((nowMs - (lastTickMs ?? nowMs)) / 1000);
    const deltaSec = Math.max(0, Math.min(raw, 2)); // cap catch-up to 2s

    if (deltaSec > 0) {
      elapsedSeconds.value += deltaSec; // ✅ increment from server total
      lastTickMs = nowMs;
    }
  }, 250);
}

function stopTicking() {
  if (tickInterval) {
    window.clearInterval(tickInterval);
    tickInterval = null;
  }
  lastTickMs = null;
}

function syncFromServer(next?: TimerState | null) {
  timerRunning.value = !!next?.running;
  elapsedSeconds.value = next?.elapsed_seconds ?? 0;
  runningStartedAt.value = next?.started_at ?? null;

  if (timerRunning.value) startTicking();
  else stopTicking();
}

// ✅ Sync whenever Inertia props.timer changes
watch(
  () => props.timer,
  (next) => syncFromServer(next),
  { immediate: true, deep: true }
);

let offStart: any = null;
let offFinish: any = null;

onMounted(() => {
  offStart = router.on('start', () => {
    lastTickMs = Date.now();
  });

  offFinish = router.on('finish', () => {
    lastTickMs = Date.now();
  });

  if (timerRunning.value) startTicking();
});

onBeforeUnmount(() => {
  stopTicking();
  offStart?.();
  offFinish?.();
});
function formatHMS(totalSeconds: number) {
  const s = Math.max(0, Math.floor(totalSeconds));
  const hh = Math.floor(s / 3600);
  const mm = Math.floor((s % 3600) / 60);
  const ss = s % 60;
  const pad = (n: number) => String(n).padStart(2, '0');
  return `${pad(hh)}:${pad(mm)}:${pad(ss)}`;
}

const timerLabel = computed(() => formatHMS(elapsedSeconds.value));

// ✅ Buttons only in in_progress (your requirement)
const canStart = computed(() => isInProgress.value && !timerRunning.value);
const canPause = computed(() => isInProgress.value && timerRunning.value);
const canResume = computed(() => isInProgress.value && !timerRunning.value && elapsedSeconds.value > 0);
const canStop = computed(() => isInProgress.value && timerRunning.value);

// ✅ Post without extra reload (Inertia redirect already refreshes once)
function postTimer(url: string, opts?: { confirmText?: string }) {
  if (opts?.confirmText && !confirm(opts.confirmText)) return;

  router.post(url, {}, {
    preserveScroll: true,
    preserveState: true,
  });
}

function timerStart() {
  postTimer(`/tickets/${props.ticket.id}/timer/start`);
}

function timerPause() {
  postTimer(`/tickets/${props.ticket.id}/timer/pause`);
}

// If you don't have /timer/resume endpoint, map resume -> start
function timerResume() {
  postTimer(`/tickets/${props.ticket.id}/timer/start`);
}

function timerStop() {
  postTimer(`/tickets/${props.ticket.id}/timer/stop`, {
    confirmText: 'Stop timer? This will finalize the current session.',
  });
}
</script>

<template>
  <Head :title="`Ticket #${ticket.id}`" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex h-full flex-1 flex-col gap-4 p-4">
      <div v-if="!canSee" class="rounded border p-4 text-sm text-muted-foreground">
        Ticket not found.
      </div>

      <div v-else class="space-y-4">
        <!-- Header -->
        <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
          <div>
            <div class="text-2xl font-semibold">
              {{ ticket.title }}
            </div>

            <div class="mt-1 flex flex-wrap items-center gap-2 text-sm text-muted-foreground">
              <span class="rounded-full border px-2 py-0.5">
                #{{ ticket.id }}
              </span>

              <span class="rounded-full border px-2 py-0.5">
                Status: {{ labels[ticket.status] ?? ticket.status }}
              </span>

              <span v-if="ticket.project" class="rounded-full border px-2 py-0.5">
                Project: {{ ticket.project.name }}
              </span>

              <span
                class="rounded-full border px-2 py-0.5 text-xs font-medium capitalize"
                :class="priorityClasses(ticket.priority)"
              >
                Priority: {{ ticket.priority ?? 'low' }}
              </span>

              <span
                v-if="ticket.deadline"
                class="rounded-full border px-2 py-0.5 text-xs font-medium"
                :class="
                  ticket.is_overdue
                    ? 'bg-red-100 text-red-700 border-red-300 dark:bg-red-950/30 dark:text-red-200 dark:border-red-900/50'
                    : 'bg-zinc-100 text-zinc-700 border-zinc-300 dark:bg-zinc-900 dark:text-zinc-200 dark:border-zinc-800'
                "
              >
                Deadline: {{ formatDeadline(ticket.deadline) }}
              </span>
            </div>

            <!-- ✅ TIMER STRIP (NEW) -->
            <div class="mt-3 rounded-2xl border bg-background p-4">
  <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <div>
      <div class="text-sm font-semibold">Time Tracker</div>

      <div class="mt-1 flex items-center gap-2">
        <span
          class="inline-flex items-center rounded-full border px-2 py-1 text-xs"
          :class="timerRunning ? 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/30 dark:text-emerald-200 dark:border-emerald-900/50'
                              : 'bg-slate-50 text-slate-700 border-slate-200 dark:bg-slate-950/30 dark:text-slate-200 dark:border-slate-800'"
        >
          {{ timerRunning ? 'Running' : 'Paused' }}
        </span>

        <span class="text-2xl font-semibold tabular-nums">
          {{ timerLabel }}
        </span>
      </div>

      <div class="mt-1 text-xs text-muted-foreground">
        <template v-if="isInProgress">
          Track work time for this ticket (start, pause, resume).
        </template>
        <template v-else-if="isDoneLike">
          Ticket is finished. Showing total tracked time (read-only).
        </template>
        <template v-else>
          Move ticket to <b>In progress</b> to start tracking time.
        </template>
      </div>
    </div>

    <!-- ✅ Buttons only when in_progress -->
    <div v-if="isInProgress" class="flex flex-wrap gap-2">
      <button
        type="button"
        class="cursor-pointer rounded-xl border px-3 py-2 text-sm hover:bg-muted/40 disabled:cursor-not-allowed disabled:opacity-50"
        @click="timerStart"
        :disabled="!canStart"
      >
        ▶ Start
      </button>

      <button
        type="button"
        class="cursor-pointer rounded-xl border px-3 py-2 text-sm hover:bg-muted/40 disabled:cursor-not-allowed disabled:opacity-50"
        @click="timerPause"
        :disabled="!canPause"
      >
        ⏸ Pause
      </button>

      <button
        type="button"
        class="cursor-pointer rounded-xl border px-3 py-2 text-sm hover:bg-muted/40 disabled:cursor-not-allowed disabled:opacity-50"
        @click="timerResume"
        :disabled="!canResume"
      >
        ↻ Resume
      </button>

      <button
          type="button"
          class="cursor-pointer rounded-xl border px-3 py-2 text-sm text-red-600 hover:bg-red-50 disabled:cursor-not-allowed disabled:opacity-50"
          @click="timerStop"
          :disabled="!canStop"
          title="Stop timer"
        >
          ■ Stop
        </button>
    </div>
  </div>
</div>
            <!-- /TIMER STRIP -->
          </div>

          <div class="flex gap-2">
            <Link
              href="/ticket-board"
              class="cursor-pointer rounded border px-3 py-2 text-sm hover:bg-muted/40"
            >
              Back to board
            </Link>
          </div>
        </div>

        <!-- Main content -->
        <div class="grid gap-4 lg:grid-cols-3">
          <!-- Left: description -->
          <div class="lg:col-span-2 rounded-2xl border bg-background p-5">
            <div class="text-sm font-semibold">Description</div>

            <div v-if="ticket.description" class="mt-2 whitespace-pre-wrap text-sm text-foreground/90">
              {{ ticket.description }}
            </div>
            <div v-else class="mt-2 text-sm text-muted-foreground">
              No description provided.
            </div>

            <!-- Attachments under description -->
            <div class="mt-6">
              <div class="text-sm font-semibold">Attachments</div>

              <div v-if="ticket.attachments?.length" class="mt-3 space-y-4">
                <div
                  v-for="a in ticket.attachments"
                  :key="a.id"
                  class="rounded-xl border p-3"
                >
                  <!-- Preview -->
                  <div class="overflow-hidden rounded-lg border bg-muted/20">
                    <img
                      v-if="isImage(a.mime_type)"
                      :src="a.path"
                      :alt="a.original_name"
                      class="max-h-[420px] w-full object-contain"
                      loading="lazy"
                    />

                    <iframe
                      v-else-if="isPdf(a.mime_type)"
                      :src="a.path"
                      class="h-[420px] w-full"
                    />

                    <div v-else class="p-4 text-sm text-muted-foreground">
                      No preview available for this file type.
                    </div>
                  </div>

                  <!-- File row -->
                  <div class="mt-3 flex items-center justify-between gap-3">
                    <div class="min-w-0">
                      <div class="truncate text-sm font-medium">
                        {{ a.original_name }}
                      </div>

                      <div class="mt-0.5 text-xs text-muted-foreground">
                        <span v-if="a.mime_type">{{ a.mime_type }}</span>
                        <span v-if="a.mime_type && a.size"> • </span>
                        <span v-if="a.size">{{ formatBytes(a.size) }}</span>
                      </div>
                    </div>

                    <a
                      :href="a.path"
                      target="_blank"
                      rel="noopener"
                      class="shrink-0 cursor-pointer rounded border px-3 py-2 text-xs hover:bg-muted/40"
                    >
                      Open / Download
                    </a>
                  </div>
                </div>
              </div>

              <div v-else class="mt-2 text-sm text-muted-foreground">
                No attachments.
              </div>
            </div>
          </div>

          <!-- Right: meta -->
          <div class="space-y-4">
            <div class="rounded-2xl border bg-background p-5">
              <div class="text-sm font-semibold">People</div>

              <div class="mt-3 space-y-3 text-sm">
                <div class="flex items-start justify-between gap-3">
                  <div class="text-muted-foreground">Creator</div>
                  <div class="text-right">
                    <div class="font-medium">
                      {{ ticket.creator?.name ?? 'Unknown' }}
                    </div>
                    <div v-if="ticket.creator?.email" class="text-xs text-muted-foreground">
                      {{ ticket.creator.email }}
                    </div>
                  </div>
                </div>

                <div class="flex items-start justify-between gap-3">
                  <div class="text-muted-foreground">Assignee</div>
                  <div class="text-right">
                    <div class="font-medium">
                      {{ ticket.assignee?.name ?? 'Unassigned' }}
                    </div>
                    <div v-if="ticket.assignee?.email" class="text-xs text-muted-foreground">
                      {{ ticket.assignee.email }}
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Comments -->
            <div class="mt-6">
              <div class="text-sm font-semibold">Comments</div>

              <!-- Add comment -->
              <form class="mt-3 space-y-2" @submit.prevent="submitComment">
                <textarea
                  v-model="commentForm.body"
                  rows="3"
                  class="w-full rounded border px-3 py-2 text-sm"
                  placeholder="Write a comment..."
                />
                <div v-if="commentForm.errors.body" class="text-sm text-red-600">
                  {{ commentForm.errors.body }}
                </div>

                <div class="flex justify-end">
                  <button
                    type="submit"
                    class="cursor-pointer rounded bg-black px-3 py-2 text-xs text-white disabled:cursor-not-allowed disabled:opacity-50"
                    :disabled="commentForm.processing || !commentForm.body.trim()"
                  >
                    Post
                  </button>
                </div>
              </form>

              <!-- List comments -->
              <div v-if="props.comments?.length" class="mt-4 space-y-3">
                <div v-for="c in props.comments" :key="c.id" class="rounded-xl border p-3">
                  <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                      <div class="text-xs text-muted-foreground">
                        <span class="font-medium text-foreground">{{ c.user?.name ?? 'User' }}</span>
                        <span class="mx-1">•</span>
                        <span :title="dayjs(c.created_at).format('YYYY-MM-DD HH:mm')">
                          {{ fromNow(c.created_at) }}
                        </span>
                      </div>

                      <div class="mt-2 whitespace-pre-wrap text-sm">
                        {{ c.body }}
                      </div>
                    </div>

                    <button
                      v-if="canDeleteComment(c)"
                      type="button"
                      class="cursor-pointer rounded border px-2 py-1 text-xs text-red-600 hover:bg-red-50"
                      @click="deleteComment(c.id)"
                    >
                      Delete
                    </button>
                  </div>
                </div>
              </div>

              <div v-else class="mt-3 text-sm text-muted-foreground">
                No comments yet.
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
