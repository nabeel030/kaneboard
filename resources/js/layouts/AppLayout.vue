<script setup lang="ts">
import AppLayout from '@/layouts/app/AppSidebarLayout.vue';
import type { BreadcrumbItemType } from '@/types';
import { watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import ToastHost from '@/components/ToastHost.vue';
import { useToast } from '@/stores/toast';


interface Props {
    breadcrumbs?: BreadcrumbItemType[];
}

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});

const page = usePage();
const toast = useToast();

watch(
  () => (page.props as any).flash,
  (flash) => {
    if (!flash) return;
    if (flash.success) toast.success(flash.success);
    if (flash.error) toast.error(flash.error);
  },
  { deep: true, immediate: true }
);

</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <ToastHost />
        <slot />
    </AppLayout>
</template>
