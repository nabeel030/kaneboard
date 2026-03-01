<script setup lang="ts">
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, reactive } from 'vue';

import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';

type Member = {
  id: number;
  name: string;
  email: string;
  created_at: string;
  roles: string[];
};

type RoleOption = {
  id: number;
  name: string;
};

type Workspace = {
  id: number;
  name: string;
  owner_id: number;
};

const props = defineProps<{
  members: Member[];
  roles: RoleOption[];
  workspace: Workspace[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Dashboard', href: dashboard().url },
  { title: 'Members', href: '/members' },
];

const page = usePage();
const perms = (page.props.auth as any)?.permissions || [];
const can = (p: string) => perms.includes(p) || perms.includes('members.manage');

const ui = reactive({
  modalOpen: false,
  mode: 'create' as 'create' | 'edit',
  editingId: null as number | null,
});

const form = useForm({
  name: '',
  email: '',
  password: '',
  roles: [] as string[],
});

const canSubmit = computed(() => !form.processing);

const emptyMember = () => ({
  name: '',
  email: '',
  password: '',
  roles: [] as string[],
});

function openCreate() {
  ui.mode = 'create';
  ui.editingId = null;

  form.clearErrors();
  form.defaults(emptyMember());   
  form.reset();                   

  ui.modalOpen = true;
}

function openEdit(m: Member) {
  ui.mode = 'edit';
  ui.editingId = m.id;

  form.clearErrors();

  form.defaults({
    name: m.name,
    email: m.email,
    password: '',
    roles: Array.isArray(m.roles) ? [...m.roles] : [],
  });
  form.reset();

  ui.modalOpen = true;
}

function closeModal() {
  ui.modalOpen = false;

  ui.mode = 'create';
  ui.editingId = null;

  form.clearErrors();
  form.defaults(emptyMember());
  form.reset();
}

function submit() {
  if (ui.mode === 'create') {
    form.post('/members', {
      preserveScroll: true,
      onSuccess: () => closeModal(),
    });
  } else {
    form.put(`/members/${ui.editingId}`, {
      preserveScroll: true,
      onSuccess: () => closeModal(),
    });
  }
}

function destroyMember(id: number) {
  if (!confirm('Delete this member?')) return;
  router.delete(`/members/${id}`, { preserveScroll: true });
}
</script>

<template>
  <Head title="Members" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex flex-col gap-4 p-4">
      <div class="flex items-center justify-between gap-4">
        <div>
          <div class="text-2xl font-semibold">Members</div>
          <div class="mt-1 text-sm text-muted-foreground">
            Members in <span class="font-medium">{{ props.workspace.name }}</span>.
          </div>
          <div class="mt-1 text-sm text-muted-foreground">
            Create members and assign roles & access.
          </div>
        </div>

        <button
          v-if="can('members.create')"
          type="button"
          class="cursor-pointer rounded bg-black px-3 py-2 text-sm text-white disabled:cursor-not-allowed disabled:opacity-50"
          @click="openCreate"
        >
          + Add Member
        </button>
      </div>

      <!-- List -->
      <div class="overflow-hidden rounded-xl border">
        <div class="grid grid-cols-12 border-b bg-muted/20 px-4 py-2 text-sm font-medium">
          <div class="col-span-4">Name</div>
          <div class="col-span-4">Email</div>
          <div class="col-span-2">Roles</div>
          <div class="col-span-2 text-right">Actions</div>
        </div>

        <div v-if="props.members.length === 0" class="p-4 text-sm text-muted-foreground">
          No members yet.
        </div>

        <div
          v-for="m in props.members"
          :key="m.id"
          class="grid grid-cols-12 items-center px-4 py-3 text-sm hover:bg-muted/20"
        >
          <div class="col-span-4 font-medium">{{ m.name }}</div>
          <div class="col-span-4 text-muted-foreground">{{ m.email }}</div>

          <div class="col-span-2 text-muted-foreground">
            <span v-if="m.roles?.length">{{ m.roles.join(', ') }}</span>
            <span v-else>-</span>
          </div>

          <div class="col-span-2 flex justify-end gap-2">
            <button
              v-if="can('members.update')"
              type="button"
              class="rounded border px-3 py-1.5 text-xs"
              @click="openEdit(m)"
            >
              Edit
            </button>

            <button
              v-if="can('members.delete')"
              type="button"
              class="rounded border px-3 py-1.5 text-xs"
              @click="destroyMember(m.id)"
            >
              Delete
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Add/Edit Member Modal -->
    <transition name="fade">
      <div
        v-if="ui.modalOpen"
        class="fixed inset-0 z-50 flex items-center justify-center"
        aria-modal="true"
        role="dialog"
        @keydown.esc="closeModal"
      >
        <div class="absolute inset-0 bg-black/40 cursor-pointer" @click="closeModal" />

        <transition name="pop">
          <div class="relative z-10 w-full max-w-lg rounded-2xl border bg-background p-5 shadow-xl">
            <div class="flex items-start justify-between gap-3">
              <div>
                <div class="text-lg font-semibold">
                  {{ ui.mode === 'create' ? 'Add Member' : 'Edit Member' }}
                </div>
                <div class="mt-1 text-sm text-muted-foreground">
                  {{ ui.mode === 'create'
                    ? 'Create login credentials and assign roles.'
                    : 'Update member info and roles.' }}
                </div>
              </div>

              <button
                class="cursor-pointer rounded p-2 hover:bg-muted/40"
                type="button"
                @click="closeModal"
                aria-label="Close"
              >
                ✕
              </button>
            </div>

            <form class="mt-4 space-y-3" @submit.prevent="submit">
              <div>
                <label class="text-sm">Name</label>
                <input
                  v-model="form.name"
                  class="mt-1 w-full rounded border px-3 py-2"
                  placeholder="e.g. Ali Khan"
                />
                <div v-if="form.errors.name" class="mt-1 text-sm text-red-600">
                  {{ form.errors.name }}
                </div>
              </div>

              <div>
                <label class="text-sm">Email</label>
                <input
                  v-model="form.email"
                  type="email"
                  class="mt-1 w-full rounded border px-3 py-2"
                  placeholder="e.g. ali@example.com"
                />
                <div v-if="form.errors.email" class="mt-1 text-sm text-red-600">
                  {{ form.errors.email }}
                </div>
              </div>

              <div>
                <label class="text-sm">
                  Password
                  <span v-if="ui.mode === 'edit'" class="text-xs text-muted-foreground">
                    (leave blank to keep)
                  </span>
                </label>
                <input
                  v-model="form.password"
                  type="password"
                  class="mt-1 w-full rounded border px-3 py-2"
                  placeholder="Min 8 characters"
                />
                <div v-if="form.errors.password" class="mt-1 text-sm text-red-600">
                  {{ form.errors.password }}
                </div>
              </div>

              <div>
                <label class="text-sm">Roles</label>
                <select v-model="form.roles" multiple class="mt-1 w-full rounded border px-3 py-2">
                  <option v-for="r in props.roles" :key="r.id" :value="r.name">
                    {{ r.name }}
                  </option>
                </select>
                <div class="mt-1 text-xs text-muted-foreground">
                  Hold Ctrl/Command to select multiple.
                </div>
                <div v-if="(form.errors as any).roles" class="mt-1 text-sm text-red-600">
                  {{ (form.errors as any).roles }}
                </div>
              </div>

              <div class="mt-4 flex items-center justify-end gap-2">
                <button
                  type="button"
                  class="cursor-pointer rounded border px-3 py-2 text-sm"
                  @click="closeModal"
                  :disabled="form.processing"
                >
                  Cancel
                </button>

                <button
                  type="submit"
                  class="cursor-pointer rounded bg-black px-3 py-2 text-sm text-white disabled:cursor-not-allowed disabled:opacity-50"
                  :disabled="!canSubmit"
                >
                  <span v-if="form.processing">Saving…</span>
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