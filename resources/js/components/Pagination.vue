<script setup lang="ts">
import { computed } from "vue";
import { Link } from "@inertiajs/vue3";

type PaginationLink = {
  url: string | null;
  label: string;
  active: boolean;
};

const props = withDefaults(
  defineProps<{
    links: PaginationLink[];
    only?: string[];
    preserveScroll?: boolean;
    preserveState?: boolean;
  }>(),
  {
    only: () => [],
    preserveScroll: true,
    preserveState: true,
  }
);

const visible = computed(() => (props.links?.length ?? 0) > 3);
</script>

<template>
  <nav v-if="visible" class="flex items-center" aria-label="Pagination">
    <ul class="inline-flex items-center gap-1 flex-wrap">
      <li v-for="(link, i) in links" :key="i">
        <!-- Dots -->
        <span
          v-if="!link.url && String(link.label).includes('...')"
          class="px-3 py-2 text-sm text-gray-500"
          v-html="link.label"
        />

        <!-- Disabled prev/next -->
        <span
          v-else-if="!link.url"
          class="px-3 py-2 text-sm rounded-lg border border-gray-200 bg-gray-100 text-gray-400 cursor-not-allowed select-none"
        >
          <span v-html="link.label" />
        </span>

        <Link
          v-else
          :href="link.url"
          :only="only.length ? only : undefined"
          :preserve-scroll="preserveScroll"
          :preserve-state="preserveState"
          class="px-3 py-2 text-sm rounded-lg border transition select-none"
          :class="link.active
            ? 'bg-gray-900 text-white border-gray-900'
            : 'bg-white text-gray-700 border-gray-200 hover:bg-gray-50'"
        >
          <span v-html="link.label" />
        </Link>
      </li>
    </ul>
  </nav>
</template>