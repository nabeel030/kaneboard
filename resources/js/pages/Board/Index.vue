<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { reactive, computed, watch } from 'vue';
import draggable from 'vuedraggable';

import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';

type Project = { id: number; name: string };
type Ticket = { id: number; title: string; description?: string | null; status: string; position: number };

const props = defineProps<{
  projects: Project[];
  selectedProjectId: number | null;
  columns: Record<string, Ticket[]>;
  statuses: string[];
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
    onSuccess: () => closeModal(),
  });
}

const editState = reactive({
  open: false,
  ticketId: null as number | null,
});

const editForm = useForm({
  title: '',
  description: '',
  status: 'backlog',
});

function openEditModal(ticket: Ticket) {
  editState.ticketId = ticket.id;
  editForm.clearErrors();
  editForm.title = ticket.title;
  editForm.description = ticket.description ?? '';
  editForm.status = ticket.status;
  editState.open = true;
}

function closeEditModal() {
  editState.open = false;
  editState.ticketId = null;
}

function submitEdit() {
  if (!editState.ticketId) return;

  editForm.patch(`/tickets/${editState.ticketId}`, {
    preserveScroll: true,
    onSuccess: () => closeEditModal(),
  });
}

function destroyTicket() {
  if (!editState.ticketId) return;
  if (!confirm('Delete this ticket?')) return;

  router.delete(`/tickets/${editState.ticketId}`, {
    preserveScroll: true,
    onSuccess: () => closeEditModal(),
  });
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
          class="relative overflow-hidden rounded-xl border border-sidebar-border/70 bg-card"
        >
          <div class="flex items-center justify-between border-b p-3">
            <div class="font-semibold">{{ labels[status] ?? status }}</div>

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
                class="cursor-pointer w-full rounded-xl border bg-background p-3 text-left hover:bg-muted/30"
                @click="openEditModal(element)"
              >
                <div class="font-medium">{{ element.title }}</div>
                <div v-if="element.description" class="mt-1 text-sm text-muted-foreground">
                  {{ element.description }}
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
          <div class="relative z-10 w-full max-w-lg rounded-2xl border bg-background p-5 shadow-xl">
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

              <div class="flex justify-end gap-2">
                <button
                  type="button"
                  class="cursor-pointer rounded border px-3 py-2 text-sm"
                  @click="closeModal"
                >
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
          <div class="relative z-10 w-full max-w-lg rounded-2xl border bg-background p-5 shadow-xl">
            <div class="flex justify-between">
              <div class="text-lg font-semibold">Edit Ticket</div>
              <button class="cursor-pointer rounded p-2 hover:bg-muted/40" @click="closeEditModal">✕</button>
            </div>

            <form class="mt-4 space-y-3" @submit.prevent="submitEdit">
              <input v-model="editForm.title" class="w-full rounded border px-3 py-2" />
              <textarea v-model="editForm.description" class="w-full rounded border px-3 py-2" rows="4" />
              <select v-model="editForm.status" class="cursor-pointer w-full rounded border px-3 py-2">
                <option v-for="s in props.statuses" :key="s" :value="s">
                  {{ labels[s] ?? s }}
                </option>
              </select>

              <div class="flex justify-between">
                <button
                  type="button"
                  class="cursor-pointer rounded border px-3 py-2 text-sm text-red-600 hover:bg-red-50"
                  @click="destroyTicket"
                >
                  Delete
                </button>

                <div class="flex gap-2">
                  <button
                    type="button"
                    class="cursor-pointer rounded border px-3 py-2 text-sm"
                    @click="closeEditModal"
                  >
                    Cancel
                  </button>

                  <button
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
.fade-enter-active, .fade-leave-active { transition: opacity 120ms ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
.pop-enter-active { transition: transform 140ms ease, opacity 140ms ease; }
.pop-enter-from { transform: scale(0.98); opacity: 0; }
.pop-leave-to { transform: scale(0.98); opacity: 0; }
</style>
