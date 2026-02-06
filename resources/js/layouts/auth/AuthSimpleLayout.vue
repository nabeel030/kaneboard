<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { home } from '@/routes';

defineProps<{
  title?: string;
  description?: string;
}>();
</script>

<template>
  <div class="relative min-h-svh bg-[#fff8f6] text-[#121212] dark:bg-[#070707] dark:text-white">
    <!-- Landing-like background -->
    <div class="pointer-events-none fixed inset-0 overflow-hidden">
      <div class="absolute -top-48 left-[-120px] h-[520px] w-[520px] rounded-full bg-[#f53003]/18 blur-[120px]" />
      <div class="absolute top-40 right-[-180px] h-[520px] w-[520px] rounded-full bg-[#fb923c]/12 blur-[120px]" />
      <div class="absolute bottom-[-240px] left-1/2 h-[560px] w-[560px] -translate-x-1/2 rounded-full bg-[#60a5fa]/10 blur-[150px]" />
      <div
        class="absolute inset-0 opacity-[0.05] dark:opacity-[0.06]"
        style="
          background-image:
            radial-gradient(circle at 20% 15%, rgba(245,48,3,0.12), transparent 40%),
            radial-gradient(circle at 85% 10%, rgba(251,146,60,0.10), transparent 45%),
            radial-gradient(circle at 60% 85%, rgba(96,165,250,0.10), transparent 45%);
        "
      />
    </div>

    <!-- Optional subtle grid (nice for auth pages) -->
    <div
      class="pointer-events-none fixed inset-0 opacity-[0.035] dark:opacity-[0.06]"
      style="
        background-image:
          linear-gradient(rgba(0,0,0,0.10) 1px, transparent 1px),
          linear-gradient(90deg, rgba(0,0,0,0.10) 1px, transparent 1px);
        background-size: 80px 80px;
      "
    />

    <!-- Content -->
    <div class="relative flex min-h-svh items-center justify-center p-6 md:p-10">
      <div class="w-full max-w-sm">
        <!-- Glass card -->
        <div class="rounded-3xl border border-black/10 bg-white/70 p-6 shadow-lg backdrop-blur-md dark:border-white/10 dark:bg-white/5 md:p-8">
          <div class="flex flex-col gap-4">
            <div class="flex flex-col items-center gap-2">
              <Link :href="home()" class="flex flex-col items-center gap-2 font-medium">
                <div class="mb-1 grid h-10 w-10 place-items-center rounded-xl border border-black/10 bg-white shadow-sm dark:border-white/10 dark:bg-white/10">
                  <AppLogoIcon class="size-9 fill-current text-black dark:text-white" />
                </div>
                <span class="sr-only">{{ title }}</span>
              </Link>

              <div class="space-y-2 text-center">
                <h1 class="text-xl font-semibold tracking-tight">{{ title }}</h1>
                <p class="text-center text-sm text-black/60 dark:text-white/60">
                  {{ description }}
                </p>
              </div>
            </div>

            <slot />
          </div>
        </div>

        <!-- tiny footer -->
        <div class="mt-4 text-center text-xs text-black/50 dark:text-white/50">
          © {{ new Date().getFullYear() }} Kaneboard
        </div>
      </div>
    </div>
  </div>
</template>
