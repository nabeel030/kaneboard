<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';

import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import projectRoutes from '@/routes/projects';
import { type BreadcrumbItem } from '@/types';

type Project = {
  id: number;
  name: string;
  description?: string | null;
  team?: { id: number; name: string };
};

const props = defineProps<{ projects: Project[] }>();

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Dashboard', href: dashboard().url },
  { title: 'Projects', href: projectRoutes.index().url },
];
</script>

<template>
  <Head title="Projects" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex flex-col gap-4 p-4">
      <div class="flex items-center justify-between">
        <div class="text-2xl font-semibold">Projects</div>

        <Link
          :href="projectRoutes.create()"
          class="rounded bg-black px-3 py-2 text-sm text-white"
        >
          New Project
        </Link>
      </div>

      <div class="grid gap-3 md:grid-cols-2">
        <Link
          v-for="p in props.projects"
          :key="p.id"
          :href="projectRoutes.show(p.id)"
          class="rounded-xl border p-4 hover:bg-muted/30"
        >
          <div class="font-medium">{{ p.name }}</div>
          <div v-if="p.team" class="mt-1 text-sm text-muted-foreground">
            Team: {{ p.team.name }}
          </div>
          <div v-if="p.description" class="mt-1 text-sm text-muted-foreground">
            {{ p.description }}
          </div>
        </Link>
      </div>
    </div>
  </AppLayout>
</template>
