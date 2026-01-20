<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';

import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import teamRoutes from '@/routes/teams';
import { type BreadcrumbItem } from '@/types';

type Team = { id: number; name: string };

const props = defineProps<{ teams: Team[] }>();

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Dashboard', href: dashboard().url },
  { title: 'Teams', href: teamRoutes.index().url },
];
</script>

<template>
  <Head title="Teams" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex flex-col gap-4 p-4">
      <div class="flex items-center justify-between">
        <div class="text-2xl font-semibold">Teams</div>

        <Link
          :href="teamRoutes.create()"
          class="rounded bg-black px-3 py-2 text-sm text-white"
        >
          New Team
        </Link>
      </div>

      <div class="grid gap-3 md:grid-cols-2">
        <Link
          v-for="t in props.teams"
          :key="t.id"
          :href="teamRoutes.show(t.id)"
          class="rounded-xl border p-4 hover:bg-muted/30"
        >
          <div class="font-medium">{{ t.name }}</div>
        </Link>
      </div>
    </div>
  </AppLayout>
</template>
