<script setup lang="ts">
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { reactive, computed, watch } from 'vue';
import draggable from 'vuedraggable';

import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import { useToast } from '@/stores/toast';
const toast = useToast();

const page = usePage();
const authUser = page.props.auth?.user as { id: number; name: string; email?: string } | null;

type Project = { id: number; name: string };
type Ticket = {
    id: number;
    title: string;
    description?: string | null;
    status: string;
    position: number;
    created_by: number;
    assigned_to?: number | null;
    deadline?: string | null;
    priority?: string;
};
type Member = { id: number; name: string; email: string };

const props = defineProps<{
    projects: Project[];
    selectedProjectId: number | null;
    columns: Record<string, Ticket[]>;
    statuses: string[];
    members: Member[];
}>();

const state = reactive({
    selectedProjectId: props.selectedProjectId ?? (props.projects[0]?.id ?? null),
    columns: JSON.parse(JSON.stringify(props.columns)) as Record<string, Ticket[]>,
    modalOpen: false,
    modalStatus: 'backlog' as string,
});

const labels: Record<string, string> = {
    backlog: 'Backlog',
    todo: 'Todo',
    in_progress: 'In progress',
    done: 'Done',
    tested: 'Tested',
    completed: 'Completed',
};

// ✅ Column colors + card tints (light + dark)
const columnTheme: Record<
    string,
    { ring: string; bg: string; chip: string; card: string; cardHover: string }
> = {
    backlog: {
        ring: 'ring-slate-200 dark:ring-slate-800',
        bg: 'bg-slate-50 dark:bg-slate-950/40',
        chip: 'bg-slate-100 text-slate-700 dark:bg-slate-900 dark:text-slate-200',
        card: 'bg-white dark:bg-slate-950/60',
        cardHover: 'hover:bg-white/80 dark:hover:bg-slate-950/80',
    },
    todo: {
        ring: 'ring-blue-200 dark:ring-blue-900/60',
        bg: 'bg-blue-50 dark:bg-blue-950/30',
        chip: 'bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-200',
        card: 'bg-blue-50/40 dark:bg-blue-950/40',
        cardHover: 'hover:bg-blue-50/70 dark:hover:bg-blue-950/55',
    },
    in_progress: {
        ring: 'ring-amber-200 dark:ring-amber-900/60',
        bg: 'bg-amber-50 dark:bg-amber-950/25',
        chip: 'bg-amber-100 text-amber-800 dark:bg-amber-900/50 dark:text-amber-200',
        card: 'bg-amber-50/40 dark:bg-amber-950/35',
        cardHover: 'hover:bg-amber-50/70 dark:hover:bg-amber-950/50',
    },
    done: {
        ring: 'ring-emerald-200 dark:ring-emerald-900/60',
        bg: 'bg-emerald-50 dark:bg-emerald-950/25',
        chip: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/45 dark:text-emerald-200',
        card: 'bg-emerald-50/40 dark:bg-emerald-950/35',
        cardHover: 'hover:bg-emerald-50/70 dark:hover:bg-emerald-950/50',
    },
    tested: {
        ring: 'ring-purple-200 dark:ring-purple-900/60',
        bg: 'bg-purple-50 dark:bg-purple-950/25',
        chip: 'bg-purple-100 text-purple-800 dark:bg-purple-900/50 dark:text-purple-200',
        card: 'bg-purple-50/40 dark:bg-purple-950/35',
        cardHover: 'hover:bg-purple-50/70 dark:hover:bg-purple-950/50',
    },
    completed: {
        ring: 'ring-zinc-200 dark:ring-zinc-800',
        bg: 'bg-zinc-50 dark:bg-zinc-950/40',
        chip: 'bg-zinc-100 text-zinc-700 dark:bg-zinc-900 dark:text-zinc-200',
        card: 'bg-zinc-50/40 dark:bg-zinc-950/45',
        cardHover: 'hover:bg-zinc-50/70 dark:hover:bg-zinc-950/60',
    },
};

function themeFor(status: string) {
    return (
        columnTheme[status] ?? {
            ring: 'ring-zinc-200 dark:ring-zinc-800',
            bg: 'bg-zinc-50 dark:bg-zinc-950/40',
            chip: 'bg-zinc-100 text-zinc-700 dark:bg-zinc-900 dark:text-zinc-200',
            card: 'bg-white dark:bg-zinc-950/60',
            cardHover: 'hover:bg-white/80 dark:hover:bg-zinc-950/80',
        }
    );
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: dashboard().url },
    { title: 'Ticket Board', href: '/ticket-board' },
];

watch(
    () => props.columns,
    (newCols) => {
        state.columns = JSON.parse(JSON.stringify(newCols));
    }
);

const canCreateTicket = computed(() => !!state.selectedProjectId);

const form = useForm({
    title: '',
    description: '',
    status: 'backlog',
    assigned_to: null as number | null,
    files: [] as File[],
    deadline: null as string | null,
    priority: 'low',
});

function openAddModal(status: string) {
    if (!state.selectedProjectId) return;
    state.modalStatus = status;
    form.reset();
    form.clearErrors();
    form.status = status;
    state.modalOpen = true;
}

function closeModal() {
    state.modalOpen = false;
    form.reset();
}

function changeProject() {
    if (!state.selectedProjectId) return;
    router.get('/ticket-board', { project: state.selectedProjectId }, { preserveScroll: true });
}

function saveOrder() {
    if (!state.selectedProjectId) return;

    const payload: { columns: Record<string, number[]> } = { columns: {} };
    for (const s of props.statuses) {
        payload.columns[s] = (state.columns[s] ?? []).map((t) => t.id);
    }

    router.post(`/projects/${state.selectedProjectId}/board/reorder`, payload, {
        preserveScroll: true,
        preserveState: true,
    });
}

function submitTicket() {
    if (!state.selectedProjectId) return;

    form.post(`/projects/${state.selectedProjectId}/tickets`, {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => closeModal(),
        onError: () => {
            toast.error('Please fix the errors and try again.');
        },
    });
}

const editState = reactive({
    open: false,
    ticketId: null as number | null,
    ticket: null as Ticket | null,
});

const canEditSelectedTicket = computed(() => {
    if (!authUser?.id) return false;
    if (!editState.ticket) return false;
    return editState.ticket.created_by === authUser.id;
});

const editForm = useForm({
    title: '',
    description: '',
    status: 'backlog',
    assigned_to: null as number | null,
    deadline: null as string | null,
    priority: 'low',
});

function openEditModal(ticket: Ticket) {
    editState.ticketId = ticket.id;
    editState.ticket = ticket;

    editForm.clearErrors();
    editForm.title = ticket.title;
    editForm.description = ticket.description ?? '';
    editForm.status = ticket.status;
    editForm.assigned_to = ticket.assigned_to ?? null;
    editForm.deadline = ticket.deadline ?? null;
    editForm.priority = ticket.priority ?? 'low';

    editState.open = true;
}

function closeEditModal() {
    editState.open = false;
    editState.ticketId = null;
    editState.ticket = null;
}

function submitEdit() {
    if (!editState.ticketId) return;
    if (!canEditSelectedTicket.value) return;

    editForm.patch(`/tickets/${editState.ticketId}`, {
        preserveScroll: true,
        onSuccess: () => closeEditModal(),
        onError: () => {
            toast.error('Please fix the errors and try again.');
        },
    });
}

function destroyTicket() {
    if (!editState.ticketId) return;
    if (!canEditSelectedTicket.value) return;
    if (!confirm('Delete this ticket?')) return;

    router.delete(`/tickets/${editState.ticketId}`, {
        preserveScroll: true,
        onSuccess: () => closeEditModal(),
    });
}
function onFilesChange(e: Event) {
    const input = e.target as HTMLInputElement;
    form.files = input.files ? Array.from(input.files) : [];
}

function goToTicketDetails() {
    if (!editState.ticketId) return;
    router.get(`/tickets/${editState.ticketId}`, {}, { preserveScroll: true });
}

const fileErrors = computed(() => {
    const errs = (form.errors ?? {}) as Record<string, any>;
    return Object.entries(errs)
        .filter(([k]) => typeof k === 'string' && k.startsWith('files.'))
        .map(([, v]) => String(v));
});

function priorityClasses(priority?: string) {
  switch (priority) {
    case 'high':
      return 'bg-red-100 text-red-700 border-red-300';
    case 'medium':
      return 'bg-orange-100 text-orange-800 border-orange-300';
    case 'low':
    default:
      return 'bg-blue-100 text-blue-800 border-blue-300';
  }
}

function formatDeadline(deadline?: string | null) {
  if (!deadline) return '';
  return deadline.split('T')[0];
}

</script>

<template>

    <Head title="Ticket Board" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <div class="flex items-center justify-between gap-4">
                <div class="text-2xl font-semibold">Ticket Board</div>

                <div class="flex items-center gap-2">
                    <span class="text-sm text-muted-foreground">Project</span>
                    <select v-model="state.selectedProjectId" class="cursor-pointer rounded border px-3 py-2 text-sm"
                        @change="changeProject">
                        <option v-for="p in props.projects" :key="p.id" :value="p.id">
                            {{ p.name }}
                        </option>
                    </select>
                </div>
            </div>

            <div v-if="!state.selectedProjectId" class="rounded border p-4 text-sm text-muted-foreground">
                Create a project first, then come back to Kaneboard.
            </div>

            <div v-else class="grid gap-4 overflow-y-auto h-full"
                :style="{ gridTemplateColumns: `repeat(${statuses.length}, minmax(360px, 1fr))` }">
                <div v-for="status in statuses" :key="status" :class="[
                    'relative overflow-hidden rounded-xl border border-sidebar-border/70 ring-1',
                    themeFor(status).ring,
                    themeFor(status).bg,
                ]">
                    <!-- Column header -->
                    <div class="flex items-center justify-between border-b p-3">
                        <div class="flex items-center gap-2">
                            <div class="font-semibold">{{ labels[status] ?? status }}</div>
                            <span :class="[
                                'rounded-full px-2 py-0.5 text-xs font-medium',
                                themeFor(status).chip,
                            ]">
                                {{ (state.columns[status]?.length ?? 0) }}
                            </span>
                        </div>

                        <button type="button"
                            class="cursor-pointer rounded border px-2 py-1 text-xs hover:bg-muted/40 disabled:cursor-not-allowed disabled:opacity-50"
                            :disabled="!canCreateTicket" @click="openAddModal(status)">
                            + Add
                        </button>
                    </div>

                    <draggable v-model="state.columns[status]" item-key="id" group="tickets"
                        class="min-h-[220px] max-h-[70vh] overflow-y-auto space-y-2 p-3" @end="saveOrder">
                        <template #item="{ element }">
                            <button type="button" class="
                    cursor-pointer
                    w-full
                    rounded-xl
                    border
                    bg-white
                    dark:bg-zinc-950
                    p-3
                    text-left
                    shadow-sm
                    transition
                    hover:shadow
                    hover:bg-zinc-50
                    dark:hover:bg-zinc-900
                  " @click="openEditModal(element)">

                                <div class="font-medium flex items-center justify-between">
                                    <span>{{ element.title }}</span>
                                    <span class="text-xs text-muted-foreground">
                                        #{{ element.id }}
                                    </span>
                                </div>

                                <div v-if="element.description" class="mt-1 text-sm text-muted-foreground">
                                    {{ element.description }}
                                </div>
                                <div class="mt-4 flex items-center justify-between">
                                    <span
                                        class="rounded-full border px-2 py-0.5 text-xs border-zinc-300 dark:bg-zinc-900">
                                        {{ element.assignee?.name ?? 'Not Assigned' }}
                                    </span>
                                    <span
                                        v-if="element.deadline"
                                        class="rounded-full border px-2 py-0.5 text-xs font-medium"
                                        :class="
                                            element.is_overdue
                                            ? 'bg-red-100 text-red-700 border-red-300'
                                            : 'bg-zinc-100 text-zinc-700 border-zinc-300 dark:bg-zinc-900 dark:text-zinc-200'
                                        "
                                        >
                                        {{ formatDeadline(element.deadline) }}
                                    </span>
                                    <span
                                        class="rounded-full border px-2 py-0.5 text-xs font-medium capitalize"
                                        :class="priorityClasses(element.priority)"
                                        >
                                        {{ element.priority ?? 'low' }}
                                    </span>
                                </div>
                            </button>
                        </template>
                    </draggable>
                </div>
            </div>
        </div>

        <!-- Add Ticket Modal -->
        <transition name="fade">
            <div v-if="state.modalOpen" class="fixed inset-0 z-50 flex items-center justify-center">
                <div class="absolute inset-0 bg-black/40 cursor-pointer" @click="closeModal" />

                <transition name="pop">
                    <div v-if="state.modalOpen"
                        class="relative z-10 w-full max-w-lg rounded-2xl border bg-background p-5 shadow-xl max-h-[85vh] flex flex-col">
                        <div class="flex items-start justify-between">
                            <div>
                                <div class="text-lg font-semibold">Add Ticket</div>
                                <div class="text-sm text-muted-foreground">
                                    Column: {{ labels[state.modalStatus] ?? state.modalStatus }}
                                </div>
                            </div>

                            <button class="cursor-pointer rounded p-2 hover:bg-muted/40" @click="closeModal">✕</button>
                        </div>

                        <form class="mt-4 space-y-3 overflow-y-auto flex-1 pr-1" @submit.prevent="submitTicket">
                            <div>
                                <label class="text-sm">Title</label>
                                <input v-model="form.title" class="mt-1 w-full rounded border px-3 py-2"
                                    placeholder="Title" />
                                <div v-if="form.errors.title" class="mt-1 text-sm text-red-600">
                                    {{ form.errors.title }}
                                </div>
                            </div>

                            <div>
                                <label class="text-sm">Description</label>
                                <textarea v-model="form.description" class="mt-1 w-full rounded border px-3 py-2"
                                    rows="4" placeholder="Optional description..." />
                                <div v-if="form.errors.description" class="mt-1 text-sm text-red-600">
                                    {{ form.errors.description }}
                                </div>
                            </div>

                            <div>
                                <label class="text-sm">Assignee</label>
                                <select v-model="form.assigned_to"
                                    class="mt-1 w-full cursor-pointer rounded border px-3 py-2">
                                    <option :value="null">Unassigned</option>
                                    <option v-for="m in props.members" :key="m.id" :value="m.id">
                                        {{ m.name }} ({{ m.email }})
                                    </option>
                                </select>
                                <div v-if="form.errors.assigned_to" class="mt-1 text-sm text-red-600">
                                    {{ form.errors.assigned_to }}
                                </div>
                            </div>
                            <div>
                                <label class="text-sm">Deadline</label>
                                <input
                                    v-model="form.deadline"
                                    type="date"
                                    class="mt-1 w-full rounded border px-3 py-2"
                                />
                                <div v-if="form.errors.deadline" class="mt-1 text-sm text-red-600">
                                    {{ form.errors.deadline }}
                                </div>
                            </div>
                            <div>
                                <label class="text-sm">Priority</label>
                                <select v-model="form.priority" class="mt-1 w-full cursor-pointer rounded border px-3 py-2">
                                    <option value="low">Low</option>
                                    <option value="medium">Medium</option>
                                    <option value="high">High</option>
                                </select>
                                <div v-if="form.errors.priority" class="mt-1 text-sm text-red-600">
                                    {{ form.errors.priority }}
                                </div>
                            </div>

                            <div>
                                <label class="text-sm">Attachments</label>

                                <input type="file" multiple
                                    class="cursor-pointer mt-1 w-full rounded border px-3 py-2 text-sm"
                                    @change="onFilesChange" />

                                <div v-if="form.files?.length" class="mt-2 space-y-1 text-xs text-muted-foreground">
                                    <div v-for="(f, i) in form.files" :key="i">
                                        • {{ f.name }}
                                    </div>
                                </div>

                                <!-- Supports both `files` and `files.0` style errors -->
                                <div v-if="form.errors.files" class="mt-1 text-sm text-red-600">
                                    {{ form.errors.files }}
                                </div>

                                <div v-else-if="fileErrors.length" class="mt-1 space-y-1">
                                    <div v-for="(msg, i) in fileErrors" :key="i" class="text-sm text-red-600">
                                        {{ msg }}
                                    </div>
                                </div>
                            </div>

                            <div class="flex justify-end gap-2 sticky bottom-0 bg-background pt-3">
                                <button type="button" class="cursor-pointer rounded border px-3 py-2 text-sm"
                                    @click="closeModal" :disabled="form.processing">
                                    Cancel
                                </button>

                                <button type="submit"
                                    class="cursor-pointer rounded bg-black px-3 py-2 text-sm text-white disabled:cursor-not-allowed disabled:opacity-50"
                                    :disabled="form.processing">
                                    <span v-if="form.processing">Saving…</span>
                                    <span v-else>Save</span>
                                </button>
                            </div>
                        </form>

                    </div>
                </transition>
            </div>
        </transition>

        <!-- Edit Ticket Modal -->
        <transition name="fade">
            <div v-if="editState.open" class="fixed inset-0 z-50 flex items-center justify-center">
                <div class="absolute inset-0 bg-black/40 cursor-pointer" @click="closeEditModal" />

                <transition name="pop">
                    <div v-if="editState.open"
                        class="relative z-10 w-full max-w-lg rounded-2xl border bg-background p-5 shadow-xl max-h-[85vh] flex flex-col">
                    <div class="flex items-start justify-between">
                            <div>
                                <div class="text-lg font-semibold">Edit Ticket</div>

                                <div v-if="!canEditSelectedTicket" class="mt-1 text-sm text-muted-foreground">
                                    You can view this ticket, but only the creator can edit or delete it.
                                </div>
                            </div>

                            <button class="cursor-pointer rounded p-2 hover:bg-muted/40"
                                @click="closeEditModal">✕</button>
                        </div>

                        <form class="mt-4 space-y-3 overflow-y-auto flex-1 pr-1" @submit.prevent="submitEdit">
                            <div>
                                <label class="text-sm">Title</label>
                                <input v-model="editForm.title" class="mt-1 w-full rounded border px-3 py-2"
                                    :disabled="!canEditSelectedTicket" />
                                <div v-if="editForm.errors.title" class="mt-1 text-sm text-red-600">
                                    {{ editForm.errors.title }}
                                </div>
                            </div>

                            <div>
                                <label class="text-sm">Description</label>
                                <textarea v-model="editForm.description" class="mt-1 w-full rounded border px-3 py-2"
                                    rows="4" :disabled="!canEditSelectedTicket" />
                                <div v-if="editForm.errors.description" class="mt-1 text-sm text-red-600">
                                    {{ editForm.errors.description }}
                                </div>
                            </div>

                            <div>
                                <label class="text-sm">Status</label>
                                <select v-model="editForm.status"
                                    class="mt-1 w-full cursor-pointer rounded border px-3 py-2"
                                    :disabled="!canEditSelectedTicket">
                                    <option v-for="s in props.statuses" :key="s" :value="s">
                                        {{ labels[s] ?? s }}
                                    </option>
                                </select>
                                <div v-if="editForm.errors.status" class="mt-1 text-sm text-red-600">
                                    {{ editForm.errors.status }}
                                </div>
                            </div>

                            <div>
                                <label class="text-sm">Assignee</label>
                                <select v-model="editForm.assigned_to"
                                    class="mt-1 w-full cursor-pointer rounded border px-3 py-2"
                                    :disabled="!canEditSelectedTicket">
                                    <option :value="null">Unassigned</option>
                                    <option v-for="m in props.members" :key="m.id" :value="m.id">
                                        {{ m.name }} ({{ m.email }})
                                    </option>
                                </select>
                                <div v-if="editForm.errors.assigned_to" class="mt-1 text-sm text-red-600">
                                    {{ editForm.errors.assigned_to }}
                                </div>
                            </div>
                            <div>
                                <label class="text-sm">Deadline</label>
                                <input
                                    v-model="editForm.deadline"
                                    type="date"
                                    class="mt-1 w-full rounded border px-3 py-2"
                                />
                                <div v-if="editForm.errors.deadline" class="mt-1 text-sm text-red-600">
                                    {{ editForm.errors.deadline }}
                                </div>
                            </div>
                            <div>
                                <label class="text-sm">Priority</label>
                                <select v-model="editForm.priority" class="mt-1 w-full cursor-pointer rounded border px-3 py-2">
                                    <option value="low">Low</option>
                                    <option value="medium">Medium</option>
                                    <option value="high">High</option>
                                </select>
                                <div v-if="editForm.errors.priority" class="mt-1 text-sm text-red-600">
                                    {{ editForm.errors.priority }}
                                </div>
                            </div>

                            <div class="flex justify-end gap-2 sticky bottom-0 bg-background pt-3">
                                <button v-if="canEditSelectedTicket" type="button"
                                    class="cursor-pointer rounded border px-3 py-2 text-sm text-red-600 hover:bg-red-50 disabled:opacity-50"
                                    @click="destroyTicket" :disabled="editForm.processing">
                                    Delete
                                </button>
                                <div v-else />

                                <div class="flex gap-2">
                                    <button type="button" class="cursor-pointer rounded border px-3 py-2 text-sm"
                                        @click="goToTicketDetails">
                                        View details
                                    </button>

                                    <button v-if="canEditSelectedTicket" type="submit"
                                        class="cursor-pointer rounded bg-black px-3 py-2 text-sm text-white disabled:cursor-not-allowed disabled:opacity-50"
                                        :disabled="editForm.processing">
                                        <span v-if="editForm.processing">Saving…</span>
                                        <span v-else>Save</span>
                                    </button>
                                </div>
                            </div>
                        </form>

                    </div>
                </transition>
            </div>
        </transition>
    </AppLayout>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
    transition: opacity 120ms ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}

.pop-enter-active {
    transition: transform 140ms ease, opacity 140ms ease;
}

.pop-enter-from {
    transform: scale(0.98);
    opacity: 0;
}

.pop-leave-to {
    transform: scale(0.98);
    opacity: 0;
}
</style>
