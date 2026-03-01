<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';

type RoleRow = { id: number; name: string; users_count: number };

const props = defineProps<{ roles: RoleRow[] }>();

const page = usePage();
const perms = (page.props.auth as any)?.permissions || [];
const can = (p: string) => perms.includes(p);

function destroyRole(id: number) {
  if (!confirm('Delete this role?')) return;
  router.delete(`/roles/${id}`, { preserveScroll: true });
}
</script>

<template>
  <Head title="Roles" />

  <AppLayout :breadcrumbs="[
    { title: 'Dashboard', href: dashboard().url },
    { title: 'Roles', href: '/roles' },
  ]">
    <div class="p-4 space-y-4">
      <div class="flex items-center justify-between gap-3">
        <div>
          <div class="text-2xl font-semibold">Roles</div>
          <div class="mt-1 text-sm text-muted-foreground">Manage roles and assign permissions.</div>
        </div>

        <Link
          v-if="can('roles.create')"
          href="/roles/create"
          class="rounded bg-black px-3 py-2 text-sm text-white"
        >
          + New Role
        </Link>
      </div>

      <div class="overflow-hidden rounded-xl border">
        <div class="grid grid-cols-12 border-b bg-muted/20 px-4 py-2 text-sm font-medium">
          <div class="col-span-6">Role</div>
          <div class="col-span-2">Users</div>
          <div class="col-span-4 text-right">Actions</div>
        </div>

        <div v-if="props.roles.length === 0" class="p-4 text-sm text-muted-foreground">
          No roles found.
        </div>

        <div
          v-for="r in props.roles"
          :key="r.id"
          class="grid grid-cols-12 items-center px-4 py-3 text-sm hover:bg-muted/20"
        >
          <div class="col-span-6 font-medium">{{ r.name }}</div>
          <div class="col-span-2 text-muted-foreground">{{ r.users_count }}</div>

          <div class="col-span-4 flex justify-end gap-2">
            <Link
              v-if="can('roles.update')"
              :href="`/roles/${r.id}/edit`"
              class="rounded border px-3 py-1.5 text-xs"
            >
              Edit
            </Link>

            <button
              v-if="can('roles.delete')"
              class="rounded border px-3 py-1.5 text-xs"
              type="button"
              @click="destroyRole(r.id)"
            >
              Delete
            </button>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>