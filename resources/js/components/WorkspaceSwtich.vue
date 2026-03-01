<script setup lang="ts">
import { computed, ref, onMounted, onBeforeUnmount } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import { Layers, Check, ChevronDown, Settings2 } from 'lucide-vue-next';

type Workspace = { id: number; name: string };

const page = usePage();

const workspaces = computed<Workspace[]>(
  () => ((page.props as any).workspaces || []) as Workspace[]
);

const currentWorkspaceId = computed<number>(
  () => Number((page.props as any).currentWorkspaceId || 0)
);

const open = ref(false);
const root = ref<HTMLElement | null>(null);

const activeWorkspace = computed(() => {
  return workspaces.value.find(w => w.id === currentWorkspaceId.value) || null;
});

function toggle() {
  if (workspaces.value.length <= 1) return;
  open.value = !open.value;
}

function close() {
  open.value = false;
}

function switchWorkspace(id: number) {
  if (!id || id === currentWorkspaceId.value) return;
  router.post(`/workspaces/${id}/switch`, {}, { preserveScroll: true });
  close();
}

function goManage() {
  router.visit('/workspaces');
  close();
}

function onClickOutside(e: MouseEvent) {
  if (!open.value) return;
  if (!root.value) return;
  if (root.value.contains(e.target as Node)) return;
  close();
}

function onEscape(e: KeyboardEvent) {
  if (e.key === 'Escape') close();
}

onMounted(() => {
  document.addEventListener('mousedown', onClickOutside);
  document.addEventListener('keydown', onEscape);
});

onBeforeUnmount(() => {
  document.removeEventListener('mousedown', onClickOutside);
  document.removeEventListener('keydown', onEscape);
});
</script>

<template>
  <div v-if="workspaces.length" ref="root" class="relative inline-flex">
    <!-- Trigger -->
    <button
      type="button"
      class="cursor-pointer inline-flex items-center gap-2 rounded-lg border bg-background px-3 py-2 text-sm shadow-sm hover:bg-muted/40"
      @click="toggle"
      aria-haspopup="menu"
      :aria-expanded="open ? 'true' : 'false'"
      title="Workspace"
    >
      <Layers class="h-4 w-4" />
      <span class="hidden sm:inline max-w-[160px] truncate">
        {{ activeWorkspace?.name ?? 'Workspace' }}
      </span>
      <ChevronDown class="h-4 w-4 opacity-70" />
    </button>

    <!-- Menu -->
    <div
      v-if="open"
      class="absolute right-0 top-full z-50 mt-2 w-60 overflow-hidden rounded-xl border bg-background shadow-lg"
      role="menu"
    >
      <!-- Workspace items -->
      <button
        v-for="w in workspaces"
        :key="w.id"
        type="button"
        role="menuitem"
        class="cursor-pointer flex w-full items-center justify-between gap-2 px-3 py-2 text-left text-sm hover:bg-muted/40"
        @click="switchWorkspace(w.id)"
      >
        <span class="flex items-center gap-2 min-w-0">
          <Layers class="h-4 w-4 opacity-80" />
          <span class="truncate">{{ w.name }}</span>
        </span>

        <Check v-if="currentWorkspaceId === w.id" class="h-4 w-4" />
      </button>

      <!-- Divider -->
      <div class="h-px bg-muted/60 my-1"></div>

      <!-- Manage -->
      <button
        type="button"
        role="menuitem"
        class="cursor-pointer flex w-full items-center justify-between gap-2 px-3 py-2 text-left text-sm hover:bg-muted/40"
        @click="goManage"
      >
        <span class="flex items-center gap-2">
          <Settings2 class="h-4 w-4 opacity-80" />
          Manage workspaces
        </span>
      </button>
    </div>
  </div>
</template>