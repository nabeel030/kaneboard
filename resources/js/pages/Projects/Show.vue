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

const props = defineProps<{
  project: {
    id: number;
    owner_id: number;
    name: string;
    description?: string | null;
  };
  owner: User | null;
  members: User[];
  allMembers: User[]; // ✅ NEW
  can: {
    update: boolean;
    delete: boolean;
    manageMembers: boolean;
  };
}>();

const select2Options = computed(() =>
  availableOptions.value.map((u) => ({
    id: u.id,
    text: u.email ? `${u.name} (${u.email})` : u.name,
  }))
);

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

const addMemberForm = useForm<{
  user_ids: number[];
}>({
  user_ids: [],
});

const assignedIds = computed(() => new Set(props.members.map((m) => m.id)));

const availableOptions = computed(() => {
  // Show users that are not already assigned AND not the owner
  return props.allMembers.filter(
    (u) => !assignedIds.value.has(u.id) && u.id !== props.project.owner_id
  );
});

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
</script>

<template>

  <Head :title="project.name" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex flex-col gap-6 p-4">
      <!-- Header -->
      <div class="flex items-start justify-between gap-4">
        <div>
          <div class="text-2xl font-semibold">{{ project.name }}</div>

          <div v-if="owner" class="mt-1 text-sm text-muted-foreground">
            Owner:
            <span class="font-medium text-foreground">{{ owner.name }}</span>
            <span v-if="owner.email"> ({{ owner.email }})</span>
          </div>

          <div v-if="project.description" class="mt-2 text-sm text-muted-foreground">
            {{ project.description }}
          </div>
        </div>

        <div class="flex gap-2">
          <button v-if="can.delete" @click="destroyProject"
            class="cursor-pointer rounded border px-3 py-2 text-sm text-red-600 hover:bg-red-50">
            Delete
          </button>
        </div>
      </div>

      <!-- Members -->
      <div class="rounded-xl border p-4">
        <div class="mb-3 flex items-center justify-between gap-3">
          <div>
            <div class="text-lg font-semibold">Members</div>
            <div class="mt-1 text-sm text-muted-foreground">
              Members can only see projects they are assigned to.
            </div>
          </div>

          <button v-if="can.manageMembers" type="button"
            class="cursor-pointer rounded border px-3 py-2 text-sm hover:bg-muted/40" @click="openAddMember">
            + Add Members
          </button>
        </div>

        <div v-if="members.length === 0" class="text-sm text-muted-foreground">
          No members assigned to this project.
        </div>

        <ul v-else class="space-y-2">
          <li v-for="m in members" :key="m.id" class="flex items-center justify-between gap-3 rounded border px-3 py-2">
            <div>
              <div class="font-medium">{{ m.name }}</div>
              <div v-if="m.email" class="text-sm text-muted-foreground">{{ m.email }}</div>
            </div>

            <div class="flex items-center gap-2">
              <span v-if="m.id === project.owner_id" class="rounded border px-2 py-1 text-xs text-muted-foreground">
                Owner
              </span>

              <button v-if="can.manageMembers && m.id !== project.owner_id" type="button"
                class="cursor-pointer rounded border px-3 py-2 text-sm text-red-600 hover:bg-red-50"
                @click="removeMember(m.id)">
                Remove
              </button>
            </div>
          </li>
        </ul>
      </div>
    </div>

    <!-- Add Members Modal (Multi-select) -->
    <transition name="fade">
      <div v-if="ui.addMemberOpen" class="fixed inset-0 z-50 flex items-center justify-center" aria-modal="true"
        role="dialog" @keydown.esc="closeAddMember">
        <div class="absolute inset-0 bg-black/40 cursor-pointer" @click="closeAddMember" />

        <transition name="pop">
          <div class="relative z-10 w-full max-w-lg rounded-2xl border bg-background p-5 shadow-xl">
            <div class="flex items-start justify-between gap-3">
              <div>
                <div class="text-lg font-semibold">Add Members</div>
                <div class="mt-1 text-sm text-muted-foreground">
                  Select one or more members to assign to this project.
                </div>
              </div>

              <button class="cursor-pointer rounded p-2 hover:bg-muted/40" type="button" @click="closeAddMember"
                aria-label="Close">
                ✕
              </button>
            </div>

            <form class="mt-4 space-y-3" @submit.prevent="submitAddMember">
              <div>
                <label class="text-sm">Members</label>

                <div class="mt-1">
                  <Select2Multi v-model="addMemberForm.user_ids" :options="select2Options"
                    placeholder="Select members..."
                    :disabled="addMemberForm.processing || select2Options.length === 0" />
                </div>

                <div v-if="select2Options.length === 0" class="mt-2 text-sm text-muted-foreground">
                  Everyone is already assigned to this project.
                </div>

                <div v-if="addMemberForm.errors.user_ids" class="mt-1 text-sm text-red-600">
                  {{ addMemberForm.errors.user_ids }}
                </div>
              </div>

              <div class="mt-4 flex items-center justify-end gap-2">
                <button type="button" class="cursor-pointer rounded border px-3 py-2 text-sm" @click="closeAddMember"
                  :disabled="addMemberForm.processing">
                  Cancel
                </button>

                <button type="submit"
                  class="cursor-pointer rounded bg-black px-3 py-2 text-sm text-white disabled:cursor-not-allowed disabled:opacity-50"
                  :disabled="addMemberForm.processing || addMemberForm.user_ids.length === 0">
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
