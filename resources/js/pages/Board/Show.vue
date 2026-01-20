<script setup lang="ts">
import { reactive } from 'vue';
import draggable from 'vuedraggable';
import { Head, router } from '@inertiajs/vue3';

import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';

type Ticket = {
  id: number;
  title: string;
  description?: string | null;
  status: string;
  position: number;
};

type Project = {
  id: number;
  name: string;
  description?: string | null;
  team_id: number;
};

const props = defineProps<{
  project: Project;
  columns: Record<string, Ticket[]>;
  statuses: string[];
}>();

const state = reactive({
  columns: JSON.parse(JSON.stringify(props.columns)) as Record<string, Ticket[]>,
});

const labels: Record<string, string> = {
  backlog: 'Backlog',
  todo: 'Todo',
  in_progress: 'In progress',
  done: 'Done',
  tested: 'Tested',
  completed: 'Completed',
};

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Dashboard',
    href: route('dashboard'),
  },
  {
    title: props.project.name,
    // if you have a projects.show route, use it; otherwise point to the board itself:
    href: route('projects.board', props.project.id),
  },
  {
    title: 'Board',
    href: route('projects.board', props.project.id),
  },
];

function saveOrder() {
  const payload: { columns: Record<string, number[]> } = { columns: {} };

  for (const s of props.statuses) {
    payload.columns[s] = (state.columns[s] ?? []).map((t) => t.id);
  }

  router.post(route('board.reorder', props.project.id), payload, {
    preserveScroll: true,
    preserveState: true,
  });
}
</script>

<template>
  <Head :title="`${project.name} — Board`" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
      <div class="flex items-start justify-between gap-4">
        <div>
          <div class="text-2xl font-semibold">{{ project.name }}</div>
          <div v-if="project.description" class="mt-1 text-sm text-muted-foreground">
            {{ project.description }}
          </div>
        </div>
      </div>

      <div
        class="grid gap-4"
        :style="{ gridTemplateColumns: `repeat(${statuses.length}, minmax(260px, 1fr))` }"
      >
        <div
          v-for="status in statuses"
          :key="status"
          class="relative overflow-hidden rounded-xl border border-sidebar-border/70 bg-card dark:border-sidebar-border"
        >
          <div class="border-b border-sidebar-border/70 p-3 font-semibold dark:border-sidebar-border">
            {{ labels[status] ?? status }}
          </div>

          <draggable
            v-model="state.columns[status]"
            item-key="id"
            group="tickets"
            class="min-h-[220px] space-y-2 p-3"
            @end="saveOrder"
          >
            <template #item="{ element }">
              <div class="rounded-xl border bg-background p-3">
                <div class="font-medium">{{ element.title }}</div>
                <div v-if="element.description" class="mt-1 text-sm text-muted-foreground">
                  {{ element.description }}
                </div>
              </div>
            </template>
          </draggable>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
