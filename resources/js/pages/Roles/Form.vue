<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';

type PermissionGroup = { group: string; items: string[] };

const props = defineProps<{
  mode: 'create' | 'edit';
  role: null | { id: number; name: string; permissions: string[] };
  allPermissions: PermissionGroup[];
}>();

const form = useForm({
  name: props.role?.name ?? '',
  permissions: props.role?.permissions ?? ([] as string[]),
});

const title = computed(() => (props.mode === 'create' ? 'Create Role' : 'Edit Role'));

function togglePermission(p: string) {
  const i = form.permissions.indexOf(p);
  if (i >= 0) form.permissions.splice(i, 1);
  else form.permissions.push(p);
}

function toggleGroup(items: string[]) {
  const hasAll = items.every(p => form.permissions.includes(p));
  if (hasAll) {
    form.permissions = form.permissions.filter(p => !items.includes(p));
  } else {
    const set = new Set([...form.permissions, ...items]);
    form.permissions = Array.from(set);
  }
}

function submit() {
  if (props.mode === 'create') {
    form.post('/roles', { preserveScroll: true });
  } else {
    form.put(`/roles/${props.role!.id}`, { preserveScroll: true });
  }
}
</script>

<template>
  <Head :title="title" />

  <AppLayout :breadcrumbs="[
    { title: 'Dashboard', href: dashboard().url },
    { title: 'Roles', href: '/roles' },
    { title, href: '#' },
  ]">
    <div class="p-4 space-y-4">
      <div class="flex items-center justify-between">
        <div>
          <div class="text-2xl font-semibold">{{ title }}</div>
          <div class="mt-1 text-sm text-muted-foreground">
            Set a role name and choose permissions.
          </div>
        </div>

        <Link href="/roles" class="rounded border px-3 py-2 text-sm">Back</Link>
      </div>

      <div class="rounded-2xl border p-4">
        <form class="space-y-4" @submit.prevent="submit">
          <div>
            <label class="text-sm font-medium">Role Name</label>
            <input v-model="form.name" class="mt-1 w-full rounded border px-3 py-2" />
            <div v-if="form.errors.name" class="mt-1 text-sm text-red-600">{{ form.errors.name }}</div>
          </div>

          <div class="space-y-4">
            <div class="text-sm font-medium">Permissions</div>

            <div v-for="g in props.allPermissions" :key="g.group" class="rounded-xl border p-3">
              <div class="flex items-center justify-between">
                <div class="font-semibold text-sm capitalize">{{ g.group }}</div>

                <button
                  type="button"
                  class="rounded border px-2 py-1 text-xs"
                  @click="toggleGroup(g.items)"
                >
                  Toggle group
                </button>
              </div>

              <div class="mt-3 grid grid-cols-1 md:grid-cols-2 gap-2">
                <label
                  v-for="p in g.items"
                  :key="p"
                  class="flex items-center gap-2 rounded border px-3 py-2 text-sm hover:bg-muted/20 cursor-pointer"
                >
                  <input
                    type="checkbox"
                    :checked="form.permissions.includes(p)"
                    @change="togglePermission(p)"
                  />
                  <span>{{ p }}</span>
                </label>
              </div>
            </div>

            <div v-if="form.errors.permissions" class="text-sm text-red-600">
              {{ form.errors.permissions }}
            </div>
          </div>

          <div class="flex justify-end gap-2">
            <Link href="/roles" class="rounded border px-3 py-2 text-sm">Cancel</Link>
            <button
              type="submit"
              class="rounded bg-black px-3 py-2 text-sm text-white disabled:opacity-50"
              :disabled="form.processing"
            >
              <span v-if="form.processing">Saving…</span>
              <span v-else>Save</span>
            </button>
          </div>
        </form>
      </div>
    </div>
  </AppLayout>
</template>