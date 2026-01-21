<script setup lang="ts">
import { Head, Link, usePage, useForm  } from '@inertiajs/vue3';
import { computed } from 'vue';

import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';
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

type Ticket = {
  id: number;
  title: string;
  description?: string | null;
  status: string;
  position?: number;
  created_at?: string | null;

  // relations
  creator?: User | null;   // recommended name from backend
  assignee?: User | null;  // recommended name from backend
  created_by?: number;     // fallback if you don't send creator relation
  assigned_to?: number | null;

  attachments?: Attachment[];
  project?: { id: number; name: string } | null;
};

const props = defineProps<{
  ticket: Ticket;
  statuses?: string[];
  comments: Comment[];
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

// small helper
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
  { title: 'Kaneboard', href: '/kaneboard' },
  { title: `Ticket #${props.ticket.id}`, href: `/tickets/${props.ticket.id}` },
];

function isImage(mime?: string | null) {
  return !!mime && mime.startsWith('image/');
}

function isPdf(mime?: string | null) {
  return mime === 'application/pdf';
}

const commentForm = useForm({
  body: '',
});

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
  return !!authUser?.id && c.user_id === authUser.id;
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
            </div>
          </div>

          <div class="flex gap-2">
            <Link
              href="/kaneboard"
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
          <!-- Image preview -->
          <img
            v-if="isImage(a.mime_type)"
            :src="a.path"
            :alt="a.original_name"
            class="max-h-[420px] w-full object-contain"
            loading="lazy"
          />

          <!-- PDF preview -->
          <iframe
            v-else-if="isPdf(a.mime_type)"
            :src="a.path"
            class="h-[420px] w-full"
          />

          <!-- Fallback (no preview) -->
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
