<script setup lang="ts">
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, reactive } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';

type WorkspaceRow = {
  id: number;
  name: string;
  owner: null | { id: number; name: string; email: string };
  created_at: string;
};

type MemberRow = { id: number; name: string; email: string; created_at: string };
type RoleOption = { id: number; name: string };

const props = defineProps<{
  workspaces: WorkspaceRow[];
  currentWorkspaceId: number;
  members: MemberRow[];
  roles: RoleOption[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Dashboard', href: dashboard().url },
  { title: 'Workspaces', href: '/workspaces' },
];

const page = usePage();
const perms = (page.props.auth as any)?.permissions || [];
const can = (p: string) => perms.includes(p) || perms.includes('workspaces.manage');

const ui = reactive({
  modalOpen: false,
  mode: 'create' as 'create' | 'edit',
  editingId: null as number | null,
});

const empty = () => ({ name: '' });

const form = useForm({
  name: '',
});

const canSubmit = computed(() => !form.processing);

function openCreate() {
  ui.mode = 'create';
  ui.editingId = null;

  form.clearErrors();
  form.defaults(empty());
  form.reset();

  ui.modalOpen = true;
}

function openEdit(w: WorkspaceRow) {
  ui.mode = 'edit';
  ui.editingId = w.id;

  form.clearErrors();
  form.defaults({ name: w.name });
  form.reset();

  ui.modalOpen = true;
}

function closeModal() {
  ui.modalOpen = false;
  ui.mode = 'create';
  ui.editingId = null;

  form.clearErrors();
  form.defaults(empty());
  form.reset();
}

function submit() {
  if (ui.mode === 'create') {
    form.post('/workspaces', {
      preserveScroll: true,
      onSuccess: () => closeModal(),
    });
  } else {
    form.put(`/workspaces/${ui.editingId}`, {
      preserveScroll: true,
      onSuccess: () => closeModal(),
    });
  }
}

function destroyWorkspace(id: number) {
  if (!confirm('Delete this workspace?')) return;
  router.delete(`/workspaces/${id}`, { preserveScroll: true });
}

function switchWorkspace(id: number) {
  router.post(`/workspaces/${id}/switch`, {}, { preserveScroll: true });
}

const invite = useForm({
  email: '',
  name: '',
  role: 'Member',
});

const inviteUi = reactive({
  open: false,
});

function openInvite() {
  invite.clearErrors();
  invite.reset();
  invite.role = 'Member';
  inviteUi.open = true;
}

function closeInvite() {
  inviteUi.open = false;
  invite.clearErrors();
  invite.reset();
}

function submitInvite() {
  invite.post(`/workspaces/${props.currentWorkspaceId}/members`, {
    preserveScroll: true,
    onSuccess: () => closeInvite(),
  });
}

function removeMember(id: number) {
  if (!confirm('Remove this member from workspace?')) return;
  router.delete(`/workspaces/${props.currentWorkspaceId}/members/${id}`, { preserveScroll: true });
}

</script>

<template>

  <Head title="Workspaces" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="p-4 space-y-4">
      <div class="flex items-center justify-between gap-3">
        <div>
          <div class="text-2xl font-semibold">Workspaces</div>
          <div class="mt-1 text-sm text-muted-foreground">
            Create and manage workspaces. Switch between them anytime.
          </div>
        </div>

        <button v-if="can('workspaces.manage')" class="rounded bg-black px-3 py-2 text-sm text-white" type="button"
          @click="openCreate">
          + New Workspace
        </button>
      </div>

      <div class="overflow-hidden rounded-xl border">
        <div class="grid grid-cols-12 border-b bg-muted/20 px-4 py-2 text-sm font-medium">
          <div class="col-span-5">Name</div>
          <div class="col-span-3">Owner</div>
          <div class="col-span-2">Created</div>
          <div class="col-span-2 text-right">Actions</div>
        </div>

        <div v-if="props.workspaces.length === 0" class="p-4 text-sm text-muted-foreground">
          No workspaces yet.
        </div>

        <div v-for="w in props.workspaces" :key="w.id"
          class="grid grid-cols-12 items-center px-4 py-3 text-sm hover:bg-muted/20">
          <div class="col-span-5 font-medium">
            <div class="flex items-center gap-2">
              <span>{{ w.name }}</span>
              <span v-if="w.id === props.currentWorkspaceId"
                class="rounded bg-black px-2 py-0.5 text-[11px] text-white">
                Current
              </span>
            </div>
          </div>

          <div class="col-span-3 text-muted-foreground">
            <span v-if="w.owner">{{ w.owner.name }}</span>
            <span v-else>-</span>
          </div>

          <div class="col-span-2 text-muted-foreground">
            {{ new Date(w.created_at).toLocaleDateString() }}
          </div>

          <div class="col-span-2 flex justify-end gap-2">
            <button class="rounded border px-3 py-1.5 text-xs" type="button" @click="switchWorkspace(w.id)">
              Switch
            </button>

            <button v-if="can('workspaces.manage')" class="rounded border px-3 py-1.5 text-xs" type="button"
              @click="openEdit(w)">
              Edit
            </button>

            <button v-if="can('workspaces.manage')" class="rounded border px-3 py-1.5 text-xs" type="button"
              @click="destroyWorkspace(w.id)">
              Delete
            </button>
          </div>
        </div>
      </div>
      <div class="p-4 space-y-3 border rounded-xl">
          <div class="flex items-center justify-between">
            <div>
              <div class="text-lg font-semibold">Workspace Members</div>
              <div class="text-sm text-muted-foreground">
                Members inside the current workspace.
              </div>
            </div>

            <button v-if="can('workspaces.manage')" type="button" class="rounded bg-black px-3 py-2 text-sm text-white"
              @click="openInvite" :disabled="!props.currentWorkspaceId">
              + Invite Member
            </button>
          </div>

          <div class="overflow-hidden rounded-xl border">
            <div class="grid grid-cols-12 border-b bg-muted/20 px-4 py-2 text-sm font-medium">
              <div class="col-span-4">Name</div>
              <div class="col-span-5">Email</div>
              <div class="col-span-2">Joined</div>
              <div class="col-span-1 text-right">Action</div>
            </div>

            <div v-if="props.members.length === 0" class="p-4 text-sm text-muted-foreground">
              No members in this workspace.
            </div>

            <div v-for="m in props.members" :key="m.id"
              class="grid grid-cols-12 items-center px-4 py-3 text-sm hover:bg-muted/20">
              <div class="col-span-4 font-medium">{{ m.name }}</div>
              <div class="col-span-5 text-muted-foreground">{{ m.email }}</div>
              <div class="col-span-2 text-muted-foreground">
                {{ new Date(m.created_at).toLocaleDateString() }}
              </div>

              <div class="col-span-1 flex justify-end">
                <button v-if="can('workspaces.manage')" class="rounded border px-2 py-1 text-xs" type="button"
                  @click="removeMember(m.id)">
                  Remove
                </button>
              </div>
            </div>
          </div>
        </div>
    </div>

    <!-- Create/Edit Modal -->
    <transition name="fade">
      <div v-if="ui.modalOpen" class="fixed inset-0 z-50 flex items-center justify-center" aria-modal="true"
        role="dialog" @keydown.esc="closeModal">
        <div class="absolute inset-0 bg-black/40 cursor-pointer" @click="closeModal" />

        <transition name="pop">
          <div class="relative z-10 w-full max-w-lg rounded-2xl border bg-background p-5 shadow-xl">
            <div class="flex items-start justify-between gap-3">
              <div>
                <div class="text-lg font-semibold">
                  {{ ui.mode === 'create' ? 'Create Workspace' : 'Edit Workspace' }}
                </div>
                <div class="mt-1 text-sm text-muted-foreground">
                  {{ ui.mode === 'create'
                    ? 'Create a new workspace for your team.'
                    : 'Update workspace name.' }}
                </div>
              </div>

              <button class="rounded p-2 hover:bg-muted/40" type="button" @click="closeModal">✕</button>
            </div>

            <form class="mt-4 space-y-3" @submit.prevent="submit">
              <div>
                <label class="text-sm">Workspace Name</label>
                <input v-model="form.name" class="mt-1 w-full rounded border px-3 py-2" />
                <div v-if="form.errors.name" class="mt-1 text-sm text-red-600">{{ form.errors.name }}</div>
              </div>

              <div class="mt-4 flex items-center justify-end gap-2">
                <button type="button" class="rounded border px-3 py-2 text-sm" @click="closeModal"
                  :disabled="form.processing">
                  Cancel
                </button>

                <button type="submit" class="rounded bg-black px-3 py-2 text-sm text-white disabled:opacity-50"
                  :disabled="!canSubmit">
                  <span v-if="form.processing">Saving…</span>
                  <span v-else>Save</span>
                </button>
              </div>
            </form>
          </div>
        </transition>
      </div>
    </transition>
    <transition name="fade">
      <div v-if="inviteUi.open" class="fixed inset-0 z-50 flex items-center justify-center" aria-modal="true"
        role="dialog" @keydown.esc="closeInvite">
        <div class="absolute inset-0 bg-black/40 cursor-pointer" @click="closeInvite" />

        <transition name="pop">
          <div class="relative z-10 w-full max-w-lg rounded-2xl border bg-background p-5 shadow-xl">
            <div class="flex items-start justify-between gap-3">
              <div>
                <div class="text-lg font-semibold">Invite Member</div>
                <div class="mt-1 text-sm text-muted-foreground">
                  Add a member to the current workspace and assign a role.
                </div>
              </div>

              <button class="rounded p-2 hover:bg-muted/40" type="button" @click="closeInvite">✕</button>
            </div>

            <form class="mt-4 space-y-3" @submit.prevent="submitInvite">
              <div>
                <label class="text-sm">Email</label>
                <input v-model="invite.email" type="email" class="mt-1 w-full rounded border px-3 py-2" />
                <div v-if="invite.errors.email" class="mt-1 text-sm text-red-600">{{ invite.errors.email }}</div>
              </div>

              <div>
                <label class="text-sm">Name (only if new user)</label>
                <input v-model="invite.name" class="mt-1 w-full rounded border px-3 py-2" />
                <div v-if="invite.errors.name" class="mt-1 text-sm text-red-600">{{ invite.errors.name }}</div>
              </div>

              <div>
                <label class="text-sm">Role</label>
                <select v-model="invite.role" class="mt-1 w-full rounded border px-3 py-2">
                  <option v-for="r in props.roles" :key="r.id" :value="r.name">
                    {{ r.name }}
                  </option>
                </select>
                <div v-if="invite.errors.role" class="mt-1 text-sm text-red-600">{{ invite.errors.role }}</div>
              </div>

              <div class="mt-4 flex items-center justify-end gap-2">
                <button type="button" class="rounded border px-3 py-2 text-sm" @click="closeInvite"
                  :disabled="invite.processing">
                  Cancel
                </button>
                <button type="submit" class="rounded bg-black px-3 py-2 text-sm text-white disabled:opacity-50"
                  :disabled="invite.processing">
                  <span v-if="invite.processing">Inviting…</span>
                  <span v-else>Invite</span>
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