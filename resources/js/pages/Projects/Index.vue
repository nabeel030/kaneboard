<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, reactive } from 'vue';

import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import projectRoutes from '@/routes/projects';
import { type BreadcrumbItem } from '@/types';

type Owner = { id: number; name: string; email?: string };

type Project = {
  id: number;
  owner_id: number;
  name: string;
  description?: string | null;
  owner?: Owner | null;
  is_owner?: boolean;
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
});

const createForm = useForm({
  name: '',
  description: '',
});

const editForm = useForm({
  name: '',
  description: '',
});

const editingProject = computed(() => {
  if (!ui.editingId) return null;
  return props.projects.find((p) => p.id === ui.editingId) ?? null;
});

function openCreate() {
  createForm.reset();
  createForm.clearErrors();
  ui.createOpen = true;
}

function closeCreate() {
  ui.createOpen = false;
}

function submitCreate() {
  createForm.post(projectRoutes.index().url, {
    preserveScroll: true,
    onSuccess: () => closeCreate(),
  });
}

function openEdit(p: Project) {
  ui.editingId = p.id;
  editForm.clearErrors();
  editForm.name = p.name;
  editForm.description = p.description ?? '';
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
  });
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

      <div class="grid gap-3 md:grid-cols-2">
        <div
          v-for="p in props.projects"
          :key="p.id"
          class="rounded-xl border p-4 hover:bg-muted/30"
        >
          <div class="flex items-start justify-between gap-3">
            <Link
              :href="projectRoutes.show(p.id)"
              class="cursor-pointer font-medium hover:underline"
            >
              {{ p.name }}
            </Link>

            <span
              v-if="p.is_owner !== undefined"
              class="rounded border px-2 py-1 text-xs text-muted-foreground"
            >
              {{ p.is_owner ? 'Owner' : 'Member' }}
            </span>
          </div>

          <div v-if="p.owner" class="mt-1 text-sm text-muted-foreground">
            Owner: {{ p.owner.name }}<span v-if="p.owner.email"> ({{ p.owner.email }})</span>
          </div>

          <div v-if="p.description" class="mt-1 text-sm text-muted-foreground">
            {{ p.description }}
          </div>

          <div class="mt-3 flex items-center justify-end gap-2">
            <button
              type="button"
              class="cursor-pointer rounded border px-3 py-2 text-sm hover:bg-muted/40 disabled:cursor-not-allowed disabled:opacity-50"
              @click="openEdit(p)"
              :disabled="p.is_owner === false"
              title="Only the owner can edit"
            >
              Edit
            </button>
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
                <div class="mt-1 text-sm text-muted-foreground">Create a project with name and description.</div>
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
                  :disabled="createForm.processing"
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
                  :disabled="editForm.processing"
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
.fade-enter-active, .fade-leave-active { transition: opacity 120ms ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
.pop-enter-active { transition: transform 140ms ease, opacity 140ms ease; }
.pop-enter-from { transform: scale(0.98); opacity: 0; }
.pop-leave-to { transform: scale(0.98); opacity: 0; }
</style>
