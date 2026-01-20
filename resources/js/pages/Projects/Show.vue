<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import projects from '@/routes/projects';
import teams from '@/routes/teams';
import { type BreadcrumbItem } from '@/types';

const props = defineProps<{
  project: { id: number; team_id: number; name: string; description?: string | null };
  team: { id: number; name: string };
}>();

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Dashboard', href: dashboard().url },
  { title: 'Projects', href: projects.index().url },
  { title: props.project.name, href: projects.show(props.project.id).url },
];

function destroyProject() {
  if (!confirm('Delete this project?')) return;
  router.delete(projects.destroy(props.project.id).url);
}
</script>

<template>
  <Head :title="project.name" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex flex-col gap-4 p-4">
      <div class="flex items-start justify-between gap-4">
        <div>
          <div class="text-2xl font-semibold">{{ project.name }}</div>
          <div class="mt-1 text-sm text-muted-foreground">
            Team:
            <Link :href="teams.show(team.id)" class="underline">{{ team.name }}</Link>
          </div>
          <div v-if="project.description" class="mt-2 text-sm text-muted-foreground">
            {{ project.description }}
          </div>
        </div>

        <div class="flex gap-2">
          <Link :href="projects.edit(project.id)" class="rounded border px-3 py-2 text-sm">Edit</Link>
          <button @click="destroyProject" class="rounded border px-3 py-2 text-sm">Delete</button>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
