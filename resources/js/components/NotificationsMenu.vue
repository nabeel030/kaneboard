<script setup lang="ts">
import { onMounted, onBeforeUnmount, ref } from 'vue';
import { Bell, Check, CheckCheck } from 'lucide-vue-next';
import { router } from '@inertiajs/vue3';

type NotificationItem = {
  id: string;
  read_at: string | null;
  created_at: string | null;
  data: {
    project_id?: number;
    project_name?: string;
    ticket_id?: number;
    ticket_title?: string;
    action?: string;
    message?: string;
    actor_id?: number;
  };
};

const open = ref(false);
const root = ref<HTMLElement | null>(null);

const unreadCount = ref(0);
const notifications = ref<NotificationItem[]>([]);
const loading = ref(false);

async function fetchNotifications() {
  loading.value = true;
  try {
    const res = await fetch('/notifications', { headers: { Accept: 'application/json' } });
    const json = await res.json();
    unreadCount.value = json.unreadCount ?? 0;
    notifications.value = json.notifications ?? [];
  } finally {
    loading.value = false;
  }
}

async function markRead(id: string) {
    router.post(route('notifications.read', { id }), {}, {
        preserveScroll: true
    });
    await fetchNotifications();
}

async function markAllRead() {
    router.post(route('notifications.readAll'), {
        preserveScroll: true
    });

  await fetchNotifications();
}

function toggle() {
  open.value = !open.value;
  if (open.value) fetchNotifications();
}
function close() {
  open.value = false;
}

function onClickOutside(e: MouseEvent) {
  if (!open.value) return;
  if (!root.value) return;
  if (root.value.contains(e.target as Node)) return;
  close();
}

function onEscape(e: KeyboardEvent) {
  if (e.key === 'Escape') close();
}

onMounted(() => {
  fetchNotifications();
  document.addEventListener('mousedown', onClickOutside);
  document.addEventListener('keydown', onEscape);

  // optional polling every 20s
  const t = window.setInterval(fetchNotifications, 20000);
  (window as any).__notifTimer = t;
});

onBeforeUnmount(() => {
  document.removeEventListener('mousedown', onClickOutside);
  document.removeEventListener('keydown', onEscape);
  const t = (window as any).__notifTimer;
  if (t) clearInterval(t);
});
</script>

<template>
  <div ref="root" class="relative inline-flex">
    <!-- Bell Button -->
    <button
      type="button"
      class="cursor-pointer relative inline-flex items-center justify-center rounded-lg border bg-background p-2 shadow-sm hover:bg-muted/40"
      @click="toggle"
      title="Notifications"
    >
      <Bell class="h-4 w-4" />

      <span
        v-if="unreadCount > 0"
        class="absolute -right-1 -top-1 min-w-[18px] rounded-full bg-red-600 px-1 text-center text-[11px] leading-[18px] text-white"
      >
        {{ unreadCount > 99 ? '99+' : unreadCount }}
      </span>
    </button>

    <!-- Dropdown -->
    <div
      v-if="open"
      class="absolute right-0 top-full z-50 mt-2 w-96 overflow-hidden rounded-xl border bg-background shadow-lg"
      role="menu"
    >
      <div class="flex items-center justify-between border-b px-3 py-2">
        <div class="text-sm font-semibold">Notifications</div>

        <button
          type="button"
          class="cursor-pointer inline-flex items-center gap-2 rounded-md border px-2 py-1 text-xs hover:bg-muted/40 disabled:opacity-50"
          @click="markAllRead"
          :disabled="unreadCount === 0"
        >
          <CheckCheck class="h-3.5 w-3.5" />
          Mark all read
        </button>
      </div>

      <div class="max-h-96 overflow-auto">
        <div v-if="loading" class="p-3 text-sm text-muted-foreground">Loading…</div>

        <div v-else-if="notifications.length === 0" class="p-3 text-sm text-muted-foreground">
          No notifications yet.
        </div>

        <div v-else class="divide-y">
          <button
            v-for="n in notifications"
            :key="n.id"
            type="button"
            class="cursor-pointer flex w-full items-start justify-between gap-3 px-3 py-3 text-left hover:bg-muted/40"
            @click="n.read_at ? null : markRead(n.id)"
          >
            <div class="min-w-0">
              <div class="truncate text-sm font-medium">
                {{ n.data.message ?? 'Ticket activity' }}
              </div>

              <div class="mt-1 text-xs text-muted-foreground">
                Project: {{ n.data.project_name ?? '—' }}
                • Ticket: #{{ n.data.ticket_id ?? '—' }}
              </div>

              <div class="mt-1 text-[11px] text-muted-foreground">
                {{ n.created_at ?? '' }}
              </div>
            </div>

            <div class="shrink-0">
              <span v-if="!n.read_at" class="rounded-full bg-blue-600/10 px-2 py-1 text-[11px] text-blue-700 dark:text-blue-200">
                New
              </span>
              <span v-else class="inline-flex items-center gap-1 text-[11px] text-muted-foreground">
                <CheckCheck class="h-3.5 w-3.5" />
                Read
              </span>
            </div>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
