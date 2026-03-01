<script setup lang="ts">
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import { SidebarTrigger } from '@/components/ui/sidebar';
import type { BreadcrumbItemType } from '@/types';
import AppearanceMenu from '@/components/AppearanceMenu.vue';
import NotificationsMenu from '@/components/NotificationsMenu.vue';

withDefaults(
    defineProps<{
        breadcrumbs?: BreadcrumbItemType[];
    }>(),
    {
        breadcrumbs: () => [],
    },
);
import { router, usePage } from '@inertiajs/vue3';
import WorkspaceSwtich from './WorkspaceSwtich.vue';

const page = usePage();
const workspaces = (page.props as any).workspaces || [];
const currentWorkspaceId = (page.props as any).currentWorkspaceId;

console.log(workspaces)
function switchWorkspace(id: number) {
    router.post(`/workspaces/${id}/switch`, {}, { preserveScroll: true });
}
</script>

<template>
    <header
        class="flex h-16 shrink-0 items-center gap-2 border-b border-sidebar-border/70 px-6 transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-12 md:px-4 justify-between">
        <div class="flex items-center gap-2">
            <SidebarTrigger class="-ml-1" />
            <template v-if="breadcrumbs && breadcrumbs.length > 0">
                <Breadcrumbs :breadcrumbs="breadcrumbs" />
            </template>
        </div>
        <div class="flex items-center gap-2">
            <NotificationsMenu />
            <AppearanceMenu />
            <WorkspaceSwtich />
        </div>
    </header>
</template>
