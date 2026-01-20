<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { reactive } from 'vue';

import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import projectRoutes from '@/routes/projects';
import { type BreadcrumbItem } from '@/types';

type Team = { id: number; name: string };

const props = defineProps<{ teams: Team[] }>();

const form = reactive({
  team_id: props.teams[0]?.id ?? null,
  name: '',
  description: '',
});

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Dashboard', href: dashboard().url },
  { title: 'Projects', href: projectRoutes.index().url },
  { title: 'Create', href: '' },
];

function submit() {
  // Store endpoint for resource is the index URL (POST /projects)
  router.post(projectRoutes.index().url, form, {
    preserveScroll: true,
  });
}
</script>

<template>
  <Head title="Create Project" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="max-w-xl p-4">
      <div class="text-2xl font-semibold">Create Project</div>

      <form class="mt-4 space-y-3" @submit.prevent="submit">
        <div>
          <label class="text-sm">Team</label>
          <select v-model="form.team_id" class="mt-1 w-full rounded border px-3 py-2">
            <option v-for="t in props.teams" :key="t.id" :value="t.id">
              {{ t.name }}
            </option>
          </select>
        </div>

        <div>
          <label class="text-sm">Name</label>
          <input v-model="form.name" class="mt-1 w-full rounded border px-3 py-2" />
        </div>

        <div>
          <label class="text-sm">Description</label>
          <textarea v-model="form.description" rows="3" class="mt-1 w-full rounded border px-3 py-2" />
        </div>

        <div class="flex gap-2">
          <button class="rounded bg-black px-3 py-2 text-sm text-white" type="submit">
            Create
          </button>
          <Link :href="projectRoutes.index()" class="rounded border px-3 py-2 text-sm">
            Cancel
          </Link>
        </div>
      </form>
    </div>
  </AppLayout>
</template>
