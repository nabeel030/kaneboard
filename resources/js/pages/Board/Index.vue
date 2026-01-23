<script setup lang="ts">
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { reactive, computed, watch } from 'vue';
import draggable from 'vuedraggable';

import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';

const page = usePage();
const authUser = page.props.auth?.user as { id: number; name: string; email?: string } | null;

type Project = { id: number; name: string };
type Ticket = {
  id: number;
  title: string;
  description?: string | null;
  status: string;
  position: number;
  created_by: number;
  assigned_to?: number | null;
};
type Member = { id: number; name: string; email: string };

const props = defineProps<{
  projects: Project[];
  selectedProjectId: number | null;
  columns: Record<string, Ticket[]>;
  statuses: string[];
  members: Member[];
}>();

const state = reactive({
  selectedProjectId: props.selectedProjectId ?? (props.projects[0]?.id ?? null),
  columns: JSON.parse(JSON.stringify(props.columns)) as Record<string, Ticket[]>,
  modalOpen: false,
  modalStatus: 'backlog' as string,
});

const labels: Record<string, string> = {
  backlog: 'Backlog',
  todo: 'Todo',
  in_progress: 'In progress',
  done: 'Done',
  tested: 'Tested',
  completed: 'Completed',
};

// ✅ Column colors + card tints (light + dark)
const columnTheme: Record<
  string,
  { ring: string; bg: string; chip: string; card: string; cardHover: string }
> = {
  backlog: {
    ring: 'ring-slate-200 dark:ring-slate-800',
    bg: 'bg-slate-50 dark:bg-slate-950/40',
    chip: 'bg-slate-100 text-slate-700 dark:bg-slate-900 dark:text-slate-200',
    card: 'bg-white dark:bg-slate-950/60',
    cardHover: 'hover:bg-white/80 dark:hover:bg-slate-950/80',
  },
  todo: {
    ring: 'ring-blue-200 dark:ring-blue-900/60',
    bg: 'bg-blue-50 dark:bg-blue-950/30',
    chip: 'bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-200',
    card: 'bg-blue-50/40 dark:bg-blue-950/40',
    cardHover: 'hover:bg-blue-50/70 dark:hover:bg-blue-950/55',
  },
  in_progress: {
    ring: 'ring-amber-200 dark:ring-amber-900/60',
    bg: 'bg-amber-50 dark:bg-amber-950/25',
    chip: 'bg-amber-100 text-amber-800 dark:bg-amber-900/50 dark:text-amber-200',
    card: 'bg-amber-50/40 dark:bg-amber-950/35',
    cardHover: 'hover:bg-amber-50/70 dark:hover:bg-amber-950/50',
  },
  done: {
    ring: 'ring-emerald-200 dark:ring-emerald-900/60',
    bg: 'bg-emerald-50 dark:bg-emerald-950/25',
    chip: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/45 dark:text-emerald-200',
    card: 'bg-emerald-50/40 dark:bg-emerald-950/35',
    cardHover: 'hover:bg-emerald-50/70 dark:hover:bg-emerald-950/50',
  },
  tested: {
    ring: 'ring-purple-200 dark:ring-purple-900/60',
    bg: 'bg-purple-50 dark:bg-purple-950/25',
    chip: 'bg-purple-100 text-purple-800 dark:bg-purple-900/50 dark:text-purple-200',
    card: 'bg-purple-50/40 dark:bg-purple-950/35',
    cardHover: 'hover:bg-purple-50/70 dark:hover:bg-purple-950/50',
  },
  completed: {
    ring: 'ring-zinc-200 dark:ring-zinc-800',
    bg: 'bg-zinc-50 dark:bg-zinc-950/40',
    chip: 'bg-zinc-100 text-zinc-700 dark:bg-zinc-900 dark:text-zinc-200',
    card: 'bg-zinc-50/40 dark:bg-zinc-950/45',
    cardHover: 'hover:bg-zinc-50/70 dark:hover:bg-zinc-950/60',
  },
};

function themeFor(status: string) {
  return (
    columnTheme[status] ?? {
      ring: 'ring-zinc-200 dark:ring-zinc-800',
      bg: 'bg-zinc-50 dark:bg-zinc-950/40',
      chip: 'bg-zinc-100 text-zinc-700 dark:bg-zinc-900 dark:text-zinc-200',
      card: 'bg-white dark:bg-zinc-950/60',
      cardHover: 'hover:bg-white/80 dark:hover:bg-zinc-950/80',
    }
  );
}

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Dashboard', href: dashboard().url },
  { title: 'Kaneboard', href: '/kaneboard' },
];

watch(
  () => props.columns,
  (newCols) => {
    state.columns = JSON.parse(JSON.stringify(newCols));
  }
);

const canCreateTicket = computed(() => !!state.selectedProjectId);

const form = useForm({
  title: '',
  description: '',
  status: 'backlog',
  assigned_to: null as number | null,
  files: [] as File[],
});

function openAddModal(status: string) {
  if (!state.selectedProjectId) return;
  state.modalStatus = status;
  form.reset();
  form.clearErrors();
  form.status = status;
  state.modalOpen = true;
}

function closeModal() {
  state.modalOpen = false;
}

function changeProject() {
  if (!state.selectedProjectId) return;
  router.get('/kaneboard', { project: state.selectedProjectId }, { preserveScroll: true });
}

function saveOrder() {
  if (!state.selectedProjectId) return;

  const payload: { columns: Record<string, number[]> } = { columns: {} };
  for (const s of props.statuses) {
    payload.columns[s] = (state.columns[s] ?? []).map((t) => t.id);
  }

  router.post(`/projects/${state.selectedProjectId}/board/reorder`, payload, {
    preserveScroll: true,
    preserveState: true,
  });
}

function submitTicket() {
  if (!state.selectedProjectId) return;

  form.post(`/projects/${state.selectedProjectId}/tickets`, {
    preserveScroll: true,
    forceFormData: true, // ✅ required for file uploads
    onSuccess: () => closeModal(),
  });
}

const editState = reactive({
  open: false,
  ticketId: null as number | null,
  ticket: null as Ticket | null,
});

const canEditSelectedTicket = computed(() => {
  if (!authUser?.id) return false;
  if (!editState.ticket) return false;
  return editState.ticket.created_by === authUser.id;
});

const editForm = useForm({
  title: '',
  description: '',
  status: 'backlog',
  assigned_to: null as number | null,
});

function openEditModal(ticket: Ticket) {
  editState.ticketId = ticket.id;
  editState.ticket = ticket;

  editForm.clearErrors();
  editForm.title = ticket.title;
  editForm.description = ticket.description ?? '';
  editForm.status = ticket.status;
  editForm.assigned_to = ticket.assigned_to ?? null;

  editState.open = true;
}

function closeEditModal() {
  editState.open = false;
  editState.ticketId = null;
  editState.ticket = null;
}

function submitEdit() {
  if (!editState.ticketId) return;
  if (!canEditSelectedTicket.value) return;

  editForm.patch(`/tickets/${editState.ticketId}`, {
    preserveScroll: true,
    onSuccess: () => closeEditModal(),
  });
}

function destroyTicket() {
  if (!editState.ticketId) return;
  if (!canEditSelectedTicket.value) return;
  if (!confirm('Delete this ticket?')) return;

  router.delete(`/tickets/${editState.ticketId}`, {
    preserveScroll: true,
    onSuccess: () => closeEditModal(),
  });
}
function onFilesChange(e: Event) {
  const input = e.target as HTMLInputElement;
  form.files = input.files ? Array.from(input.files) : [];
}

function goToTicketDetails() {
  if (!editState.ticketId) return;
  router.get(`/tickets/${editState.ticketId}`, {}, { preserveScroll: true });
}

</script>

<template>
  <Head title="Kaneboard" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
      <div class="flex items-center justify-between gap-4">
        <div class="text-2xl font-semibold">Kaneboard</div>

        <div class="flex items-center gap-2">
          <span class="text-sm text-muted-foreground">Project</span>
          <select
            v-model="state.selectedProjectId"
            class="cursor-pointer rounded border px-3 py-2 text-sm"
            @change="changeProject"
          >
            <option v-for="p in props.projects" :key="p.id" :value="p.id">
              {{ p.name }}
            </option>
          </select>
        </div>
      </div>

      <div v-if="!state.selectedProjectId" class="rounded border p-4 text-sm text-muted-foreground">
        Create a project first, then come back to Kaneboard.
      </div>

      <div
        v-else
        class="grid gap-4"
        :style="{ gridTemplateColumns: `repeat(${statuses.length}, minmax(260px, 1fr))` }"
      >
        <div
          v-for="status in statuses"
          :key="status"
          :class="[
            'relative overflow-hidden rounded-xl border border-sidebar-border/70 ring-1',
            themeFor(status).ring,
            themeFor(status).bg,
          ]"
        >
          <!-- Column header -->
          <div class="flex items-center justify-between border-b p-3">
            <div class="flex items-center gap-2">
              <div class="font-semibold">{{ labels[status] ?? status }}</div>
              <span
                :class="[
                  'rounded-full px-2 py-0.5 text-xs font-medium',
                  themeFor(status).chip,
                ]"
              >
                {{ (state.columns[status]?.length ?? 0) }}
              </span>
            </div>

            <button
              type="button"
              class="cursor-pointer rounded border px-2 py-1 text-xs hover:bg-muted/40 disabled:cursor-not-allowed disabled:opacity-50"
              :disabled="!canCreateTicket"
              @click="openAddModal(status)"
            >
              + Add
            </button>
          </div>

          <draggable
            v-model="state.columns[status]"
            item-key="id"
            group="tickets"
            class="min-h-[220px] space-y-2 p-3"
            @end="saveOrder"
          >
            <template #item="{ element }">
              <button
                  type="button"
                  class="
                    cursor-pointer
                    w-full
                    rounded-xl
                    border
                    bg-white
                    dark:bg-zinc-950
                    p-3
                    text-left
                    shadow-sm
                    transition
                    hover:shadow
                    hover:bg-zinc-50
                    dark:hover:bg-zinc-900
                  "
                  @click="openEditModal(element)"
                >

                <div class="font-medium">{{ element.title }}</div>

                <div v-if="element.description" class="mt-1 text-sm text-muted-foreground">
                  {{ element.description }}
                </div>

                <div class="mt-2 flex items-center justify-between">
                  <span class="text-xs text-muted-foreground">
                    #{{ element.id }}
                  </span>

                  <span
                    v-if="element.assigned_to"
                    class="rounded-full border px-2 py-0.5 text-xs text-muted-foreground"
                  >
                    Assigned
                  </span>
                </div>
              </button>
            </template>
          </draggable>
        </div>
      </div>
    </div>

    <!-- Add Ticket Modal -->
    <transition name="fade">
      <div v-if="state.modalOpen" class="fixed inset-0 z-50 flex items-center justify-center">
        <div class="absolute inset-0 bg-black/40 cursor-pointer" @click="closeModal" />

        <transition name="pop">
            <div
                v-if="state.modalOpen"
                class="relative z-10 w-full max-w-lg rounded-2xl border bg-background p-5 shadow-xl"
            >
            <div class="flex items-start justify-between">
              <div>
                <div class="text-lg font-semibold">Add Ticket</div>
                <div class="text-sm text-muted-foreground">
                  Column: {{ labels[state.modalStatus] ?? state.modalStatus }}
                </div>
              </div>

              <button class="cursor-pointer rounded p-2 hover:bg-muted/40" @click="closeModal">✕</button>
            </div>

            <form class="mt-4 space-y-3" @submit.prevent="submitTicket">
              <input v-model="form.title" class="w-full rounded border px-3 py-2" placeholder="Title" />
              <textarea v-model="form.description" class="w-full rounded border px-3 py-2" rows="4" />

              <select v-model="form.assigned_to" class="cursor-pointer w-full rounded border px-3 py-2">
                <option :value="null">Unassigned</option>
                <option v-for="m in props.members" :key="m.id" :value="m.id">
                  {{ m.name }} ({{ m.email }})
                </option>
              </select>

              <div>
  <label class="text-sm">Attachments</label>

  <input
    type="file"
    multiple
    class="cursor-pointer mt-1 w-full rounded border px-3 py-2 text-sm"
    @change="onFilesChange"
  />

  <div v-if="form.files?.length" class="mt-2 space-y-1 text-xs text-muted-foreground">
    <div v-for="(f, i) in form.files" :key="i">
      • {{ f.name }}
    </div>
  </div>

  <div v-if="form.errors.files" class="mt-1 text-sm text-red-600">
    {{ form.errors.files }}
  </div>
</div>


              <div class="flex justify-end gap-2">
                <button type="button" class="cursor-pointer rounded border px-3 py-2 text-sm" @click="closeModal">
                  Cancel
                </button>

                <button
                  type="submit"
                  class="cursor-pointer rounded bg-black px-3 py-2 text-sm text-white disabled:cursor-not-allowed disabled:opacity-50"
                  :disabled="form.processing"
                >
                  Save
                </button>
              </div>
            </form>
          </div>
        </transition>
      </div>
    </transition>

    <!-- Edit Ticket Modal -->
    <transition name="fade">
      <div v-if="editState.open" class="fixed inset-0 z-50 flex items-center justify-center">
        <div class="absolute inset-0 bg-black/40 cursor-pointer" @click="closeEditModal" />

        <transition name="pop">
            <div
                v-if="editState.open"
                class="relative z-10 w-full max-w-lg rounded-2xl border bg-background p-5 shadow-xl"
            >
            <div class="flex items-start justify-between">
              <div>
                <div class="text-lg font-semibold">Edit Ticket</div>

                <div v-if="!canEditSelectedTicket" class="mt-1 text-sm text-muted-foreground">
                  You can view this ticket, but only the creator can edit or delete it.
                </div>
              </div>

              <button class="cursor-pointer rounded p-2 hover:bg-muted/40" @click="closeEditModal">✕</button>
            </div>

            <form class="mt-4 space-y-3" @submit.prevent="submitEdit">
              <input
                v-model="editForm.title"
                class="w-full rounded border px-3 py-2"
                :disabled="!canEditSelectedTicket"
              />
              <textarea
                v-model="editForm.description"
                class="w-full rounded border px-3 py-2"
                rows="4"
                :disabled="!canEditSelectedTicket"
              />

              <select
                v-model="editForm.status"
                class="cursor-pointer w-full rounded border px-3 py-2"
                :disabled="!canEditSelectedTicket"
              >
                <option v-for="s in props.statuses" :key="s" :value="s">
                  {{ labels[s] ?? s }}
                </option>
              </select>

              <select
                v-model="editForm.assigned_to"
                class="cursor-pointer w-full rounded border px-3 py-2"
                :disabled="!canEditSelectedTicket"
              >
                <option :value="null">Unassigned</option>
                <option v-for="m in props.members" :key="m.id" :value="m.id">
                  {{ m.name }} ({{ m.email }})
                </option>
              </select>

              <div class="flex justify-between">

                <button
                  v-if="canEditSelectedTicket"
                  type="button"
                  class="cursor-pointer rounded border px-3 py-2 text-sm text-red-600 hover:bg-red-50"
                  @click="destroyTicket"
                >
                  Delete
                </button>
                <div v-else />

                <div class="flex gap-2">
                  <button
                        type="button"
                        class="cursor-pointer rounded border px-3 py-2 text-sm"
                        @click="goToTicketDetails"
                    >
                        View details
                    </button>

                  <button
                    v-if="canEditSelectedTicket"
                    type="submit"
                    class="cursor-pointer rounded bg-black px-3 py-2 text-sm text-white disabled:cursor-not-allowed disabled:opacity-50"
                    :disabled="editForm.processing"
                  >
                    Save
                  </button>
                </div>
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
