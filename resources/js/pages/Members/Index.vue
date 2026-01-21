<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, reactive } from 'vue';

import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';

type Member = {
  id: number;
  name: string;
  email: string;
  created_at: string;
};

const props = defineProps<{
  members: Member[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Dashboard', href: dashboard().url },
  { title: 'Members', href: '/members' },
];

const ui = reactive({
  modalOpen: false,
});

const form = useForm({
  name: '',
  email: '',
  password: '',
});

const canSubmit = computed(() => !form.processing);

function openModal() {
  form.reset();
  form.clearErrors();
  ui.modalOpen = true;
}

function closeModal() {
  ui.modalOpen = false;
}

function submit() {
  form.post('/members/store', {
    preserveScroll: true,
    onSuccess: () => closeModal(),
  });
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
            Create members and later assign them to projects.
          </div>
        </div>

        <button
          type="button"
          class="cursor-pointer rounded bg-black px-3 py-2 text-sm text-white disabled:cursor-not-allowed disabled:opacity-50"
          @click="openModal"
        >
          + Add Member
        </button>
      </div>

      <!-- List -->
      <div class="overflow-hidden rounded-xl border">
        <div class="grid grid-cols-12 border-b bg-muted/20 px-4 py-2 text-sm font-medium">
          <div class="col-span-5">Name</div>
          <div class="col-span-5">Email</div>
          <div class="col-span-2">Created</div>
        </div>

        <div v-if="props.members.length === 0" class="p-4 text-sm text-muted-foreground">
          No members yet.
        </div>

        <div
          v-for="m in props.members"
          :key="m.id"
          class="grid grid-cols-12 items-center px-4 py-3 text-sm hover:bg-muted/20"
        >
          <div class="col-span-5 font-medium">{{ m.name }}</div>
          <div class="col-span-5 text-muted-foreground">{{ m.email }}</div>
          <div class="col-span-2 text-muted-foreground">
            {{ new Date(m.created_at).toLocaleDateString() }}
          </div>
        </div>
      </div>
    </div>

    <!-- Add Member Modal -->
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
                <div class="text-lg font-semibold">Add Member</div>
                <div class="mt-1 text-sm text-muted-foreground">
                  Create login credentials for a new member.
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
                <label class="text-sm">Password</label>
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
