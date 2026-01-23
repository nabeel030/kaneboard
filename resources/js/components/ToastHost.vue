<script setup lang="ts">
import { computed } from 'vue';
import { useToast } from '@/stores/toast';

const toast = useToast();

function typeClasses(type: 'success' | 'error' | 'info') {
  switch (type) {
    case 'success':
      return {
        wrapper: 'border-green-200 bg-green-50 text-green-900',
        title: 'text-green-900',
        message: 'text-green-700',
        close: 'hover:bg-green-100',
        icon: '✓',
      };
    case 'error':
      return {
        wrapper: 'border-red-200 bg-red-50 text-red-900',
        title: 'text-red-900',
        message: 'text-red-700',
        close: 'hover:bg-red-100',
        icon: '✕',
      };
    default:
      return {
        wrapper: 'border-blue-200 bg-blue-50 text-blue-900',
        title: 'text-blue-900',
        message: 'text-blue-700',
        close: 'hover:bg-blue-100',
        icon: 'ℹ',
      };
  }
}
</script>

<template>
  <div class="fixed right-4 top-4 z-[9999] flex w-[min(420px,calc(100vw-2rem))] flex-col gap-2">
    <div
      v-for="t in toast.toasts"
      :key="t.id"
      :class="[
        'rounded-xl border p-3 shadow-lg transition',
        typeClasses(t.type).wrapper
      ]"
      role="status"
      aria-live="polite"
    >
      <div class="flex items-start gap-3">
        <!-- Icon -->
        <div class="mt-0.5 text-lg font-bold">
          {{ typeClasses(t.type).icon }}
        </div>

        <!-- Content -->
        <div class="min-w-0 flex-1">
          <div
            class="text-sm font-semibold"
            :class="typeClasses(t.type).title"
          >
            {{ t.title ?? (t.type === 'success' ? 'Success' : t.type === 'error' ? 'Error' : 'Info') }}
          </div>

          <div
            class="mt-0.5 break-words text-sm"
            :class="typeClasses(t.type).message"
          >
            {{ t.message }}
          </div>
        </div>

        <!-- Close -->
        <button
          type="button"
          class="rounded px-2 py-1 text-sm transition"
          :class="typeClasses(t.type).close"
          @click="toast.remove(t.id)"
          aria-label="Dismiss"
        >
          ✕
        </button>
      </div>
    </div>
  </div>
</template>
