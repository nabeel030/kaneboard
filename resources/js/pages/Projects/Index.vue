<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, reactive } from 'vue';

import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import projectRoutes from '@/routes/projects';
import { type BreadcrumbItem } from '@/types';
import { useToast } from '@/stores/toast';
const toast = useToast();

type Owner = { id: number; name: string; email?: string };

type Project = {
  id: number;
  owner_id: number;
  name: string;
  description?: string | null;
  owner?: Owner | null;
  is_owner?: boolean;

  start_date?: string | null;
  end_date?: string | null;
  baseline_start_date?: string | null;
  baseline_end_date?: string | null;

  total_tracked_seconds?: number;
  total_tracked_hours?: number;
};

const props = defineProps<{ projects: Project[] }>();

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Dashboard', href: dashboard().url },
  { title: 'Projects', href: projectRoutes.index().url },
];

const ui = reactive({
  createOpen: false,
  editOpen: false,
  editingId: null as number | null,
  createUseBaselineSameAsPlan: false,
});

const createForm = useForm({
  name: '',
  description: '',
  start_date: '',
  end_date: '',
  baseline_start_date: '',
  baseline_end_date: '',
});

const editForm = useForm({
  name: '',
  description: '',
  start_date: '',
  end_date: '',
  baseline_start_date: '',
  baseline_end_date: '',
});

const editingProject = computed(() => {
  if (!ui.editingId) return null;
  return props.projects.find((p) => p.id === ui.editingId) ?? null;
});

function openCreate() {
  createForm.reset();
  createForm.clearErrors();

  ui.createUseBaselineSameAsPlan = false;

  const today = new Date();
  const end = new Date();
  end.setDate(end.getDate() + 14);

  const toISO = (d: Date) => d.toISOString().slice(0, 10);

  createForm.start_date = toISO(today);
  createForm.end_date = toISO(end);

  createForm.baseline_start_date = createForm.start_date;
  createForm.baseline_end_date = createForm.end_date;

  ui.createOpen = true;
}

function closeCreate() {
  ui.createOpen = false;
}

function submitCreate() {
  if (ui.createUseBaselineSameAsPlan) {
    createForm.baseline_start_date = createForm.start_date;
    createForm.baseline_end_date = createForm.end_date;
  }

  createForm.post(projectRoutes.index().url, {
    preserveScroll: true,
    onSuccess: () => closeCreate(),
    onError: () => toast.error('Please fix the errors and try again.'),
  });
}

function openEdit(p: Project) {
  ui.editingId = p.id;

  editForm.clearErrors();
  editForm.name = p.name;
  editForm.description = p.description ?? '';
  editForm.start_date = p.start_date ?? '';
  editForm.end_date = p.end_date ?? '';
  editForm.baseline_start_date = p.baseline_start_date ?? '';
  editForm.baseline_end_date = p.baseline_end_date ?? '';

  ui.editOpen = true;
}

function closeEdit() {
  ui.editOpen = false;
  ui.editingId = null;
}

function submitEdit() {
  if (!ui.editingId) return;

  editForm.put(projectRoutes.update(ui.editingId).url, {
    preserveScroll: true,
    onSuccess: () => closeEdit(),
    onError: () => toast.error('Please fix the errors and try again.'),
  });
}

const canEditProject = (p: Project) => p.is_owner !== false;

const isInvalidDateRange = (start: string, end: string) => {
  if (!start || !end) return false;
  return new Date(end).getTime() < new Date(start).getTime();
};

const createDateRangeInvalid = computed(() =>
  isInvalidDateRange(createForm.start_date, createForm.end_date)
);

const editDateRangeInvalid = computed(() =>
  isInvalidDateRange(editForm.start_date, editForm.end_date)
);

function rebaselineToPlan() {
  editForm.baseline_start_date = editForm.start_date;
  editForm.baseline_end_date = editForm.end_date;
}

function formatHours(h?: number | null) {
  if (h == null) return '';
  if (h === 0) return '0h';
  return `${h.toFixed(h % 1 === 0 ? 0 : 1)} h`;
}

function dateRange(p: Project) {
  const s = p.start_date ?? '—';
  const e = p.end_date ?? '—';
  return `${s} → ${e}`;
}
</script>

<template>
  <Head title="Projects" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex flex-col gap-4 p-4">
      <div class="flex items-center justify-between">
        <div class="text-2xl font-semibold">Projects</div>

        <button
          type="button"
          class="cursor-pointer rounded bg-black px-3 py-2 text-sm text-white disabled:cursor-not-allowed disabled:opacity-50"
          @click="openCreate"
        >
          New Project
        </button>
      </div>

      <!-- GRID -->
      <div class="grid gap-4 md:grid-cols-2">
        <div
          v-for="p in props.projects"
          :key="p.id"
          class="
            group relative overflow-hidden rounded-2xl border bg-white p-5
            shadow-sm transition
            hover:-translate-y-[1px] hover:shadow-md hover:border-zinc-300
            dark:bg-zinc-950 dark:border-zinc-800 dark:hover:border-zinc-700
            focus-within:-translate-y-[1px] focus-within:shadow-md
          "
        >
          <!-- hover/active accent (like screenshot) -->
          <div
            class="
              pointer-events-none absolute inset-0 opacity-0 transition
              group-hover:opacity-100 group-focus-within:opacity-100
            "
          >
            <div class="absolute left-0 top-0 h-full w-1 bg-red-500"></div>
            <div class="absolute inset-0 bg-red-50/60 dark:bg-red-950/10"></div>
          </div>

          <div class="relative">
            <!-- TOP ROW -->
            <div class="flex items-start justify-between gap-3">
              <Link
                :href="projectRoutes.show(p.id)"
                class="text-lg font-semibold leading-tight hover:underline"
              >
                {{ p.name }}
              </Link>

              <div class="flex items-center gap-2">
                <span
                  v-if="(p.total_tracked_hours ?? 0) > 0"
                  class="
                    inline-flex items-center gap-1 rounded-full border px-2.5 py-1 text-xs font-medium
                    bg-zinc-50 text-zinc-800 border-zinc-200
                    dark:bg-zinc-900 dark:text-zinc-100 dark:border-zinc-800
                  "
                  title="Tracked time"
                >
                  <span class="text-[13px]">🕒</span>
                  {{ formatHours(p.total_tracked_hours) }}
                </span>

                <span
                  v-if="p.is_owner !== undefined"
                  class="
                    inline-flex items-center rounded-full border px-2.5 py-1 text-xs
                    bg-white text-zinc-700 border-zinc-200
                    dark:bg-zinc-950 dark:text-zinc-200 dark:border-zinc-800
                  "
                >
                  {{ p.is_owner ? 'Owner' : 'Member' }}
                </span>
              </div>
            </div>

            <!-- OWNER LINE -->
            <div v-if="p.owner" class="mt-2 flex items-center gap-2 text-sm text-muted-foreground">
              <!-- user icon -->
              <svg class="h-4 w-4 opacity-70" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M20 21a8 8 0 0 0-16 0"></path>
                <circle cx="12" cy="7" r="4"></circle>
              </svg>

              <span class="font-medium text-foreground">{{ p.owner.name }}</span>
              <span v-if="p.owner.email" class="text-muted-foreground">
                ({{ p.owner.email }})
              </span>
            </div>

            <!-- DESCRIPTION -->
            <div v-if="p.description" class="mt-3 text-sm text-muted-foreground">
              {{ p.description }}
            </div>

            <!-- DIVIDER -->
            <div class="mt-4 border-t border-zinc-200/70 dark:border-zinc-800/70"></div>

            <!-- BOTTOM ROW -->
            <div class="mt-4 flex items-center justify-between gap-3">
              <div class="flex items-center gap-2 text-sm">
                <!-- calendar icon -->
                <svg class="h-4 w-4 text-muted-foreground" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M8 2v4"></path>
                  <path d="M16 2v4"></path>
                  <rect x="3" y="4" width="18" height="18" rx="2"></rect>
                  <path d="M3 10h18"></path>
                </svg>

                <span class="font-medium text-foreground">
                  {{ dateRange(p) }}
                </span>
              </div>

              <button
                type="button"
                class="
                  inline-flex items-center gap-2 rounded-lg px-2 py-1 text-sm
                  text-muted-foreground hover:text-foreground hover:bg-zinc-100
                  dark:hover:bg-zinc-900 cursor-pointer
                "
                @click="openEdit(p)"
                :disabled="!canEditProject(p)"
                title="Only the owner can edit"
              >
                <!-- pencil icon -->
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M12 20h9"></path>
                  <path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"></path>
                </svg>
                <span>Edit</span>
              </button>
            </div>
          </div>
        </div>
      </div>

      <div
        v-if="props.projects.length === 0"
        class="rounded border p-4 text-sm text-muted-foreground"
      >
        No projects yet. Create your first project.
      </div>
    </div>

    <!-- Create Modal -->
    <transition name="fade">
      <div
        v-if="ui.createOpen"
        class="fixed inset-0 z-50 flex items-center justify-center"
        aria-modal="true"
        role="dialog"
        @keydown.esc="closeCreate"
      >
        <div class="absolute inset-0 bg-black/40 cursor-pointer" @click="closeCreate" />

        <transition name="pop">
          <div class="relative z-10 w-full max-w-lg rounded-2xl border bg-background p-5 shadow-xl">
            <div class="flex items-start justify-between gap-3">
              <div>
                <div class="text-lg font-semibold">New Project</div>
                <div class="mt-1 text-sm text-muted-foreground">
                  Create a project with schedule dates to enable Project Health.
                </div>
              </div>

              <button
                class="cursor-pointer rounded p-2 hover:bg-muted/40"
                type="button"
                @click="closeCreate"
                aria-label="Close"
              >
                ✕
              </button>
            </div>

            <form class="mt-4 space-y-3" @submit.prevent="submitCreate">
              <div>
                <label class="text-sm">Name</label>
                <input
                  v-model="createForm.name"
                  class="mt-1 w-full rounded border px-3 py-2"
                  placeholder="e.g. Kaneboard v2"
                />
                <div v-if="createForm.errors.name" class="mt-1 text-sm text-red-600">
                  {{ createForm.errors.name }}
                </div>
              </div>

              <div>
                <label class="text-sm">Description</label>
                <textarea
                  v-model="createForm.description"
                  rows="3"
                  class="mt-1 w-full rounded border px-3 py-2"
                  placeholder="Optional details..."
                />
                <div v-if="createForm.errors.description" class="mt-1 text-sm text-red-600">
                  {{ createForm.errors.description }}
                </div>
              </div>

              <div class="rounded-lg border p-3">
                <div class="text-sm font-medium">Schedule</div>
                <div class="mt-2 grid gap-3 md:grid-cols-2">
                  <div>
                    <label class="text-sm">Start date</label>
                    <input
                      v-model="createForm.start_date"
                      type="date"
                      class="mt-1 w-full rounded border px-3 py-2"
                    />
                    <div v-if="createForm.errors.start_date" class="mt-1 text-sm text-red-600">
                      {{ createForm.errors.start_date }}
                    </div>
                  </div>

                  <div>
                    <label class="text-sm">End date</label>
                    <input
                      v-model="createForm.end_date"
                      type="date"
                      class="mt-1 w-full rounded border px-3 py-2"
                    />
                    <div v-if="createForm.errors.end_date" class="mt-1 text-sm text-red-600">
                      {{ createForm.errors.end_date }}
                    </div>
                  </div>
                </div>

                <div v-if="createDateRangeInvalid" class="mt-2 text-sm text-red-600">
                  End date must be on or after start date.
                </div>

                <div class="mt-3 flex items-center gap-2">
                  <input
                    id="baselineSame"
                    type="checkbox"
                    v-model="ui.createUseBaselineSameAsPlan"
                    class="h-4 w-4"
                  />
                  <label for="baselineSame" class="text-sm text-muted-foreground">
                    Use same dates as baseline (recommended)
                  </label>
                </div>

                <div v-if="!ui.createUseBaselineSameAsPlan" class="mt-3 grid gap-3 md:grid-cols-2">
                  <div>
                    <label class="text-sm">Baseline start</label>
                    <input
                      v-model="createForm.baseline_start_date"
                      type="date"
                      class="mt-1 w-full rounded border px-3 py-2"
                    />
                    <div v-if="createForm.errors.baseline_start_date" class="mt-1 text-sm text-red-600">
                      {{ createForm.errors.baseline_start_date }}
                    </div>
                  </div>

                  <div>
                    <label class="text-sm">Baseline end</label>
                    <input
                      v-model="createForm.baseline_end_date"
                      type="date"
                      class="mt-1 w-full rounded border px-3 py-2"
                    />
                    <div v-if="createForm.errors.baseline_end_date" class="mt-1 text-sm text-red-600">
                      {{ createForm.errors.baseline_end_date }}
                    </div>
                  </div>
                </div>
              </div>

              <div class="mt-4 flex items-center justify-end gap-2">
                <button
                  type="button"
                  class="cursor-pointer rounded border px-3 py-2 text-sm"
                  @click="closeCreate"
                  :disabled="createForm.processing"
                >
                  Cancel
                </button>

                <button
                  type="submit"
                  class="cursor-pointer rounded bg-black px-3 py-2 text-sm text-white disabled:cursor-not-allowed disabled:opacity-50"
                  :disabled="createForm.processing || createDateRangeInvalid"
                >
                  <span v-if="createForm.processing">Creating…</span>
                  <span v-else>Create</span>
                </button>
              </div>
            </form>
          </div>
        </transition>
      </div>
    </transition>

    <!-- Edit Modal -->
    <transition name="fade">
      <div
        v-if="ui.editOpen"
        class="fixed inset-0 z-50 flex items-center justify-center"
        aria-modal="true"
        role="dialog"
        @keydown.esc="closeEdit"
      >
        <div class="absolute inset-0 bg-black/40 cursor-pointer" @click="closeEdit" />

        <transition name="pop">
          <div class="relative z-10 w-full max-w-lg rounded-2xl border bg-background p-5 shadow-xl">
            <div class="flex items-start justify-between gap-3">
              <div>
                <div class="text-lg font-semibold">Edit Project</div>
                <div v-if="editingProject" class="mt-1 text-sm text-muted-foreground">
                  {{ editingProject.name }}
                </div>
              </div>

              <button
                class="cursor-pointer rounded p-2 hover:bg-muted/40"
                type="button"
                @click="closeEdit"
                aria-label="Close"
              >
                ✕
              </button>
            </div>

            <form class="mt-4 space-y-3" @submit.prevent="submitEdit">
              <div>
                <label class="text-sm">Name</label>
                <input v-model="editForm.name" class="mt-1 w-full rounded border px-3 py-2" />
                <div v-if="editForm.errors.name" class="mt-1 text-sm text-red-600">
                  {{ editForm.errors.name }}
                </div>
              </div>

              <div>
                <label class="text-sm">Description</label>
                <textarea v-model="editForm.description" rows="3" class="mt-1 w-full rounded border px-3 py-2" />
                <div v-if="editForm.errors.description" class="mt-1 text-sm text-red-600">
                  {{ editForm.errors.description }}
                </div>
              </div>

              <div class="rounded-lg border p-3">
                <div class="flex items-center justify-between">
                  <div class="text-sm font-medium">Schedule</div>

                  <button
                    type="button"
                    class="cursor-pointer rounded border px-3 py-1 text-xs hover:bg-muted/40"
                    @click="rebaselineToPlan"
                    title="Set baseline dates to match current plan"
                  >
                    Rebaseline to plan
                  </button>
                </div>

                <div class="mt-2 grid gap-3 md:grid-cols-2">
                  <div>
                    <label class="text-sm">Start date</label>
                    <input v-model="editForm.start_date" type="date" class="mt-1 w-full rounded border px-3 py-2" />
                    <div v-if="editForm.errors.start_date" class="mt-1 text-sm text-red-600">
                      {{ editForm.errors.start_date }}
                    </div>
                  </div>

                  <div>
                    <label class="text-sm">End date</label>
                    <input v-model="editForm.end_date" type="date" class="mt-1 w-full rounded border px-3 py-2" />
                    <div v-if="editForm.errors.end_date" class="mt-1 text-sm text-red-600">
                      {{ editForm.errors.end_date }}
                    </div>
                  </div>
                </div>

                <div v-if="editDateRangeInvalid" class="mt-2 text-sm text-red-600">
                  End date must be on or after start date.
                </div>

                <div class="mt-3 grid gap-3 md:grid-cols-2">
                  <div>
                    <label class="text-sm">Baseline start</label>
                    <input
                      v-model="editForm.baseline_start_date"
                      type="date"
                      class="mt-1 w-full rounded border px-3 py-2"
                    />
                    <div v-if="editForm.errors.baseline_start_date" class="mt-1 text-sm text-red-600">
                      {{ editForm.errors.baseline_start_date }}
                    </div>
                  </div>

                  <div>
                    <label class="text-sm">Baseline end</label>
                    <input
                      v-model="editForm.baseline_end_date"
                      type="date"
                      class="mt-1 w-full rounded border px-3 py-2"
                    />
                    <div v-if="editForm.errors.baseline_end_date" class="mt-1 text-sm text-red-600">
                      {{ editForm.errors.baseline_end_date }}
                    </div>
                  </div>
                </div>
              </div>

              <div class="mt-4 flex items-center justify-end gap-2">
                <button
                  type="button"
                  class="cursor-pointer rounded border px-3 py-2 text-sm"
                  @click="closeEdit"
                  :disabled="editForm.processing"
                >
                  Cancel
                </button>

                <button
                  type="submit"
                  class="cursor-pointer rounded bg-black px-3 py-2 text-sm text-white disabled:cursor-not-allowed disabled:opacity-50"
                  :disabled="editForm.processing || editDateRangeInvalid"
                >
                  <span v-if="editForm.processing">Saving…</span>
                  <span v-else>Save</span>
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