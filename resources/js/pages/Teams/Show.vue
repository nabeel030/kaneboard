<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';

import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import teamRoutes from '@/routes/teams';
import projectRoutes from '@/routes/projects';
import { type BreadcrumbItem } from '@/types';

type Project = { id: number; name: string; description?: string | null };

const props = defineProps<{
  team: { id: number; name: string };
  projects: Project[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Dashboard', href: dashboard().url },
  { title: 'Teams', href: teamRoutes.index().url },
  { title: props.team.name, href: teamRoutes.show(props.team.id).url },
];

function destroyTeam() {
  if (!confirm('Delete this team? This will delete its projects too.')) return;

  router.delete(teamRoutes.destroy(props.team.id).url, {
    preserveScroll: true,
  });
}
</script>

<template>
  <Head :title="props.team.name" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex flex-col gap-4 p-4">
      <div class="flex items-start justify-between gap-4">
        <div>
          <div class="text-2xl font-semibold">{{ props.team.name }}</div>
          <div class="mt-1 text-sm text-muted-foreground">Projects in this team</div>
        </div>

        <div class="flex gap-2">
          <Link
            :href="projectRoutes.create()"
            class="rounded bg-black px-3 py-2 text-sm text-white"
          >
            New Project
          </Link>

          <Link
            :href="teamRoutes.edit(props.team.id)"
            class="rounded border px-3 py-2 text-sm"
          >
            Edit
          </Link>

          <button
            @click="destroyTeam"
            class="rounded border px-3 py-2 text-sm"
          >
            Delete
          </button>
        </div>
      </div>

      <div class="grid gap-3 md:grid-cols-2">
        <Link
          v-for="p in props.projects"
          :key="p.id"
          :href="projectRoutes.show(p.id)"
          class="rounded-xl border p-4 hover:bg-muted/30"
        >
          <div class="font-medium">{{ p.name }}</div>
          <div v-if="p.description" class="mt-1 text-sm text-muted-foreground">
            {{ p.description }}
          </div>
        </Link>
      </div>
    </div>
  </AppLayout>
</template>
