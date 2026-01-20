<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { reactive } from 'vue';

import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import teamRoutes from '@/routes/teams';
import { type BreadcrumbItem } from '@/types';

const props = defineProps<{ team: { id: number; name: string } }>();

const form = reactive({
  name: props.team.name,
});

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Dashboard', href: dashboard().url },
  { title: 'Teams', href: teamRoutes.index().url },
  { title: props.team.name, href: teamRoutes.show(props.team.id).url },
  { title: 'Edit', href: '' },
];

function submit() {
  router.put(teamRoutes.update(props.team.id).url, form, {
    preserveScroll: true,
  });
}
</script>

<template>
  <Head title="Edit Team" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="max-w-xl p-4">
      <div class="text-2xl font-semibold">Edit Team</div>

      <form class="mt-4 space-y-3" @submit.prevent="submit">
        <div>
          <label class="text-sm">Name</label>
          <input v-model="form.name" class="mt-1 w-full rounded border px-3 py-2" />
        </div>

        <div class="flex gap-2">
          <button class="rounded bg-black px-3 py-2 text-sm text-white" type="submit">
            Save
          </button>
          <Link :href="teamRoutes.show(props.team.id)" class="rounded border px-3 py-2 text-sm">
            Cancel
          </Link>
        </div>
      </form>
    </div>
  </AppLayout>
</template>
