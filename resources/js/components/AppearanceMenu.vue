<script setup lang="ts">
import { computed, ref, onMounted, onBeforeUnmount } from 'vue';
import { Monitor, Moon, Sun, Check, ChevronDown } from 'lucide-vue-next';
import { useAppearance } from '@/composables/useAppearance';

const { appearance, updateAppearance } = useAppearance();

const open = ref(false);
const root = ref<HTMLElement | null>(null);

const items = [
  { value: 'light', label: 'Light', Icon: Sun },
  { value: 'dark', label: 'Dark', Icon: Moon },
  { value: 'system', label: 'System', Icon: Monitor },
] as const;

const ActiveIcon = computed(() => {
  const found = items.find((i) => i.value === appearance.value);
  return found?.Icon ?? Monitor;
});

function toggle() {
  open.value = !open.value;
}

function close() {
  open.value = false;
}

function choose(value: 'light' | 'dark' | 'system') {
  updateAppearance(value);
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
  <div ref="root" class="relative inline-flex">
    <!-- Trigger -->
    <button
      type="button"
      class="cursor-pointer inline-flex items-center gap-2 rounded-lg border bg-background px-3 py-2 text-sm shadow-sm hover:bg-muted/40"
      @click="toggle"
      aria-haspopup="menu"
      :aria-expanded="open ? 'true' : 'false'"
      title="Theme"
    >
      <component :is="ActiveIcon" class="h-4 w-4" />
      <span class="hidden sm:inline">
        {{ appearance.charAt(0).toUpperCase() + appearance.slice(1) }}
      </span>
      <ChevronDown class="h-4 w-4 opacity-70" />
    </button>

    <!-- Menu -->
    <div
      v-if="open"
      class="absolute right-0 top-full z-50 mt-2 w-44 overflow-hidden rounded-xl border bg-background shadow-lg"
      role="menu"
    >
      <button
        v-for="item in items"
        :key="item.value"
        type="button"
        role="menuitem"
        class="cursor-pointer flex w-full items-center justify-between gap-2 px-3 py-2 text-left text-sm hover:bg-muted/40"
        @click="choose(item.value)"
      >
        <span class="flex items-center gap-2">
          <component :is="item.Icon" class="h-4 w-4 opacity-80" />
          {{ item.label }}
        </span>

        <Check v-if="appearance === item.value" class="h-4 w-4" />
      </button>
    </div>
  </div>
</template>
