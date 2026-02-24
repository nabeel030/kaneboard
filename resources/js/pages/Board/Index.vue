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
type Member = { id: number; name: string; email: string };
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

    assignee?: { id: number; name: string } | null;
    is_overdue?: boolean;
    type: string;
    tracked_hours?: number;
};

type TicketTypes = {
    value: string;
    label: string;
};

const props = defineProps<{
    projects: Project[];
    selectedProjectId: number | null;
    columns: Record<string, Ticket[]>;
    statuses: string[];
    members: Member[];
    ticketTypes: TicketTypes[];
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
    type: 'feature',
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
    type: 'feature',
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
    editForm.type = ticket.type ?? 'feature';

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
        case 'bug':
            return 'bg-red-100 text-red-700 border-red-300';
        case 'medium':
        case 'improvement':
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

/* =========================
   ✅ UX Filters (beautiful)
   ========================= */

type DuePreset = 'any' | 'no_deadline' | 'has_deadline' | 'overdue' | 'due_today' | 'due_7d';

const filters = reactive({
    q: '',
    assignee: 'any' as 'any' | 'unassigned' | string, // string is member id
    mineOnly: false,
    priority: 'any' as 'any' | 'low' | 'medium' | 'high',
    type: 'any' as 'any' | string,
    due: 'any' as DuePreset,
    sort: 'manual' as 'manual' | 'deadline_asc' | 'deadline_desc' | 'priority_desc' | 'id_desc' | 'title_asc',
});

const hasActiveFilters = computed(() => {
    return (
        filters.q.trim().length > 0 ||
        filters.assignee !== 'any' ||
        filters.mineOnly ||
        filters.priority !== 'any' ||
        filters.due !== 'any' ||
        filters.sort !== 'manual' ||
        filters.type !== 'any'
    );
});

function clearFilters() {
    filters.q = '';
    filters.assignee = 'any';
    filters.mineOnly = false;
    filters.priority = 'any';
    filters.due = 'any';
    filters.sort = 'manual';
    filters.type = 'any';
}

function normalize(s: string) {
    return s.trim().toLowerCase();
}

function ticketMatches(ticket: Ticket) {
    // Text query: title / description / #id
    const q = normalize(filters.q);
    if (q) {
        const hay = `${ticket.title ?? ''} ${ticket.description ?? ''} #${ticket.id ?? ''}`.toLowerCase();
        if (!hay.includes(q)) return false;
    }

    // Type filter
    if (filters.type !== 'any') {
        if (ticket.type !== filters.type) return false;
    }

    // Mine only
    if (filters.mineOnly && authUser?.id) {
        if (ticket.created_by !== authUser.id) return false;
    }

    // Assignee
    if (filters.assignee !== 'any') {
        if (filters.assignee === 'unassigned') {
            if (ticket.assigned_to != null) return false;
        } else {
            const idNum = Number(filters.assignee);
            if (Number.isFinite(idNum)) {
                if ((ticket.assigned_to ?? null) !== idNum) return false;
            }
        }
    }

    if (filters.priority !== 'any') {
        const p = (ticket.priority ?? 'low') as 'low' | 'medium' | 'high';
        if (p !== filters.priority) return false;
    }

    const raw = ticket.deadline ?? null;
    const hasDeadline = !!raw;

    const todayStr = new Date().toISOString().split('T')[0];
    const today = new Date(todayStr + 'T00:00:00');

    const dStr = raw ? raw.split('T')[0] : null;
    const d = dStr ? new Date(dStr + 'T00:00:00') : null;

    const isOverdue = ticket.is_overdue ?? (d ? d.getTime() < today.getTime() : false);

    if (filters.due === 'no_deadline' && hasDeadline) return false;
    if (filters.due === 'has_deadline' && !hasDeadline) return false;

    if (filters.due === 'overdue') {
        if (!hasDeadline || !isOverdue) return false;
    }

    if (filters.due === 'due_today') {
        if (!hasDeadline || !d) return false;
        if (d.toISOString().split('T')[0] !== todayStr) return false;
    }

    if (filters.due === 'due_7d') {
        if (!hasDeadline || !d) return false;
        const diffMs = d.getTime() - today.getTime();
        const diffDays = Math.floor(diffMs / (1000 * 60 * 60 * 24));
        // include today..next 7 days
        if (diffDays < 0 || diffDays > 7) return false;
    }

    return true;
}

function compareTickets(a: Ticket, b: Ticket) {
    if (filters.sort === 'manual') return 0;

    const aTitle = (a.title ?? '').toLowerCase();
    const bTitle = (b.title ?? '').toLowerCase();

    const aDeadline = a.deadline ? new Date(a.deadline.split('T')[0] + 'T00:00:00').getTime() : null;
    const bDeadline = b.deadline ? new Date(b.deadline.split('T')[0] + 'T00:00:00').getTime() : null;

    const prioScore = (p?: string) => {
        switch (p) {
            case 'high':
                return 3;
            case 'medium':
                return 2;
            case 'low':
            default:
                return 1;
        }
    };

    switch (filters.sort) {
        case 'deadline_asc': {
            if (aDeadline == null && bDeadline == null) return 0;
            if (aDeadline == null) return 1;
            if (bDeadline == null) return -1;
            return aDeadline - bDeadline;
        }
        case 'deadline_desc': {
            if (aDeadline == null && bDeadline == null) return 0;
            if (aDeadline == null) return 1;
            if (bDeadline == null) return -1;
            return bDeadline - aDeadline;
        }
        case 'priority_desc': {
            const diff = prioScore(b.priority) - prioScore(a.priority);
            if (diff !== 0) return diff;
            return b.id - a.id;
        }
        case 'id_desc':
            return b.id - a.id;
        case 'title_asc':
            return aTitle.localeCompare(bTitle);
        default:
            return 0;
    }
}

const filteredColumns = computed(() => {
    const out: Record<string, Ticket[]> = {};
    for (const status of props.statuses) {
        const base = state.columns[status] ?? [];
        const filtered = base.filter(ticketMatches);
        if (filters.sort !== 'manual') filtered.sort(compareTickets);
        out[status] = filtered;
    }
    return out;
});

const filteredCountByStatus = computed(() => {
    const out: Record<string, number> = {};
    for (const s of props.statuses) out[s] = (filteredColumns.value[s]?.length ?? 0);
    return out;
});

function activeFilterChips() {
    const chips: { key: string; label: string; onClear: () => void }[] = [];

    if (filters.q.trim()) {
        chips.push({
            key: 'q',
            label: `Search: "${filters.q.trim()}"`,
            onClear: () => (filters.q = ''),
        });
    }

    if (filters.mineOnly) {
        chips.push({
            key: 'mine',
            label: 'Created by me',
            onClear: () => (filters.mineOnly = false),
        });
    }

    if (filters.assignee !== 'any') {
        const label =
            filters.assignee === 'unassigned'
                ? 'Assignee: Unassigned'
                : `Assignee: ${props.members.find((m) => m.id === Number(filters.assignee))?.name ?? 'Member'}`;
        chips.push({
            key: 'assignee',
            label,
            onClear: () => (filters.assignee = 'any'),
        });
    }

    if (filters.priority !== 'any') {
        chips.push({
            key: 'priority',
            label: `Priority: ${filters.priority}`,
            onClear: () => (filters.priority = 'any'),
        });
    }

    if (filters.due !== 'any') {
        const map: Record<DuePreset, string> = {
            any: 'Any due date',
            no_deadline: 'No deadline',
            has_deadline: 'Has deadline',
            overdue: 'Overdue',
            due_today: 'Due today',
            due_7d: 'Due in 7 days',
        };
        chips.push({
            key: 'due',
            label: `Due: ${map[filters.due]}`,
            onClear: () => (filters.due = 'any'),
        });
    }

    if (filters.sort !== 'manual') {
        const map: Record<string, string> = {
            deadline_asc: 'Deadline ↑',
            deadline_desc: 'Deadline ↓',
            priority_desc: 'Priority ↓',
            id_desc: 'Newest (#) ↓',
            title_asc: 'Title A→Z',
        };
        chips.push({
            key: 'sort',
            label: `Sort: ${map[filters.sort] ?? filters.sort}`,
            onClear: () => (filters.sort = 'manual'),
        });
    }

    if (filters.type !== 'any') {
        chips.push({
            key: 'type',
            label: `Ticket Type: ${filters.type}`,
            onClear: () => (filters.type = 'any'),
        });
    }

    return chips;
}

const activeChips = computed(() => activeFilterChips());

function formatHours(h?: number | null) {
  if (h == null) return '';
  if (h === 0) return '0h';
  return `${h.toFixed(h % 1 === 0 ? 0 : 1)} h`;
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
                    <select
                        v-model="state.selectedProjectId"
                        class="cursor-pointer rounded border px-3 py-2 text-sm"
                        @change="changeProject"
                    >
                        <option v-for="p in props.projects" :key="p.id" :value="p.id">
                            {{ p.name }}
                        </option>
                    </select>
                </div>
            </div>

            <div
                class="rounded-2xl border bg-background/60 p-4 shadow-sm backdrop-blur supports-[backdrop-filter]:bg-background/40"
            >
                <div class="flex flex-col gap-3">
                    <div class="flex flex-wrap items-end gap-3">
                        <div class="min-w-[220px] flex-1">
                            <label class="text-xs font-medium text-muted-foreground">Search</label>
                            <div class="mt-1 flex items-center gap-2 rounded-xl border bg-background px-3 py-2">
                                <span class="text-muted-foreground text-sm">⌕</span>
                                <input
                                    v-model="filters.q"
                                    type="text"
                                    class="w-full bg-transparent text-sm outline-none"
                                    placeholder="Title, description, or #id…"
                                />
                                <button
                                    v-if="filters.q"
                                    type="button"
                                    class="rounded-lg px-2 py-1 text-xs text-muted-foreground hover:bg-muted/40"
                                    @click="filters.q = ''"
                                >
                                    Clear
                                </button>
                            </div>
                        </div>

                        <div class="min-w-[200px]">
                            <label class="text-xs font-medium text-muted-foreground">Assignee</label>
                            <select
                                v-model="filters.assignee"
                                class="mt-1 w-full cursor-pointer rounded-xl border bg-background px-3 py-2 text-sm"
                            >
                                <option value="any">Any</option>
                                <option value="unassigned">Unassigned</option>
                                <option v-for="m in props.members" :key="m.id" :value="String(m.id)">
                                    {{ m.name }}
                                </option>
                            </select>
                        </div>

                        <div class="min-w-[160px]">
                            <label class="text-xs font-medium text-muted-foreground">Priority</label>
                            <select
                                v-model="filters.priority"
                                class="mt-1 w-full cursor-pointer rounded-xl border bg-background px-3 py-2 text-sm"
                            >
                                <option value="any">Any</option>
                                <option value="low">Low</option>
                                <option value="medium">Medium</option>
                                <option value="high">High</option>
                            </select>
                        </div>

                        <div class="min-w-[160px]">
                            <label class="text-xs font-medium text-muted-foreground">Ticket Type</label>
                            <select
                                v-model="filters.type"
                                class="mt-1 w-full cursor-pointer rounded-xl border bg-background px-3 py-2 text-sm"
                            >
                                <option value="any">Any</option>
                                <option v-for="t in props.ticketTypes" :key="t.value" :value="t.value">
                                    {{ t.label }}
                                </option>
                            </select>
                        </div>

                        <div class="min-w-[180px]">
                            <label class="text-xs font-medium text-muted-foreground">Due</label>
                            <select
                                v-model="filters.due"
                                class="mt-1 w-full cursor-pointer rounded-xl border bg-background px-3 py-2 text-sm"
                            >
                                <option value="any">Any</option>
                                <option value="no_deadline">No deadline</option>
                                <option value="has_deadline">Has deadline</option>
                                <option value="overdue">Overdue</option>
                                <option value="due_today">Due today</option>
                                <option value="due_7d">Due in 7 days</option>
                            </select>
                        </div>

                        <div class="min-w-[180px]">
                            <label class="text-xs font-medium text-muted-foreground">Sort</label>
                            <select
                                v-model="filters.sort"
                                class="mt-1 w-full cursor-pointer rounded-xl border bg-background px-3 py-2 text-sm"
                            >
                                <option value="manual">Manual (board order)</option>
                                <option value="deadline_asc">Deadline ↑</option>
                                <option value="deadline_desc">Deadline ↓</option>
                                <option value="priority_desc">Priority ↓</option>
                                <option value="id_desc">Newest (#) ↓</option>
                                <option value="title_asc">Title A→Z</option>
                            </select>
                        </div>

                        <button
                            type="button"
                            class="mt-5 inline-flex items-center gap-2 rounded-xl border px-3 py-2 text-sm transition hover:bg-muted/40"
                            :class="filters.mineOnly ? 'bg-muted/40' : ''"
                            @click="filters.mineOnly = !filters.mineOnly"
                        >
                            <span class="text-base">👤</span>
                            <span>Created by me</span>
                        </button>

                        <button
                            type="button"
                            class="mt-5 inline-flex items-center gap-2 rounded-xl border px-3 py-2 text-sm text-muted-foreground transition hover:bg-muted/40"
                            :disabled="!hasActiveFilters"
                            :class="!hasActiveFilters ? 'opacity-50 cursor-not-allowed' : ''"
                            @click="clearFilters"
                        >
                            <span class="text-base">↺</span>
                            <span>Reset</span>
                        </button>
                    </div>

                    <div v-if="activeChips.length" class="flex flex-wrap items-center gap-2">
                        <span class="text-xs text-muted-foreground">Active:</span>
                        <button
                            v-for="chip in activeChips"
                            :key="chip.key"
                            type="button"
                            class="group inline-flex items-center gap-2 rounded-full border bg-background px-3 py-1.5 text-xs shadow-sm transition hover:bg-muted/40"
                            @click="chip.onClear"
                            :title="'Remove ' + chip.label"
                        >
                            <span class="font-medium">{{ chip.label }}</span>
                            <span class="text-muted-foreground group-hover:text-foreground">✕</span>
                        </button>

                        <span class="ml-1 text-xs text-muted-foreground">
                            (Drag & drop is disabled while filters/sort are active.)
                        </span>
                    </div>
                </div>
            </div>

            <div v-if="!state.selectedProjectId" class="rounded border p-4 text-sm text-muted-foreground">
                Create a project first, then come back to Ticket Board.
            </div>

            <div
                v-else
                class="grid gap-4 overflow-y-auto h-full"
                :style="{ gridTemplateColumns: `repeat(${statuses.length}, minmax(360px, 1fr))` }"
            >
                <div
                    v-for="status in statuses"
                    :key="status"
                    :class="[
                        'relative overflow-hidden rounded-xl border border-sidebar-border/70 ring-1',
                        themeFor(status).ring,
                        themeFor(status).bg,
                    ]"
                >
                    <div class="flex items-center justify-between border-b p-3">
                        <div class="flex items-center gap-2">
                            <div class="font-semibold">{{ labels[status] ?? status }}</div>
                            <span
                                :class="[
                                    'rounded-full px-2 py-0.5 text-xs font-medium',
                                    themeFor(status).chip,
                                ]"
                            >
                                <template v-if="hasActiveFilters">
                                    {{ filteredCountByStatus[status] }} / {{ (state.columns[status]?.length ?? 0) }}
                                </template>
                                <template v-else>
                                    {{ (state.columns[status]?.length ?? 0) }}
                                </template>
                            </span>
                        </div>

                        <button
                            type="button"
                            class="cursor-pointer rounded border px-2 py-1 text-xs hover:bg-muted/40 disabled:cursor-not-allowed disabled:opacity-50"
                            :disabled="!canCreateTicket"
                            @click="openAddModal(status)"
                        >
                            + Add
                        </button>
                    </div>

                    <div
                        v-if="hasActiveFilters"
                        class="min-h-[220px] max-h-[70vh] overflow-y-auto space-y-2 p-3"
                    >
                        <div v-if="(filteredColumns[status]?.length ?? 0) === 0" class="rounded-xl border bg-background/60 p-3 text-sm text-muted-foreground">
                            No tickets match your filters.
                        </div>

                        <button
                            v-for="element in filteredColumns[status]"
                            :key="element.id"
                            type="button"
                            class="
                                mb-4
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
                            "
                            @click="openEditModal(element)"
                        >
                            <div class="font-medium flex justify-between">
                                    <span>
                                        <span class="text-xs text-muted-foreground">#{{ element.id }}</span>
                                        {{ element.title }}
                                    </span>
                                <div>
                                    <span
                                        class="rounded-full border px-2 py-0.5 text-xs font-medium capitalize"
                                        :class="priorityClasses(element.type)"
                                    >
                                        {{ element.type ?? 'feature' }}
                                    </span>
                                </div>
                            </div>

                            <div v-if="element.description" class="mt-1 text-sm text-muted-foreground">
                                {{ element.description }}
                            </div>

                            <div class="mt-4 flex items-center justify-between gap-2">
                                <span class="rounded-full border px-2 py-0.5 text-xs border-zinc-300 dark:bg-zinc-900">
                                    {{ element.assignee?.name ?? 'Not Assigned' }}
                                </span>

                                <span
                                    v-if="element.deadline"
                                    class="rounded-full border px-2 py-0.5 text-xs font-medium"
                                    :class="
                                        (element.is_overdue ?? false)
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
                    </div>

                    <draggable
                        v-else
                        v-model="state.columns[status]"
                        item-key="id"
                        group="tickets"
                        class="min-h-[220px] max-h-[70vh] overflow-y-auto space-y-2 p-3"
                        @end="saveOrder"
                    >
                        <template #item="{ element }">
                            <button
                                type="button"
                                class="
                                    mb-4
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
                                "
                                @click="openEditModal(element)"
                            >
                                <div class="font-medium flex items-center justify-between">
                                    <span>
                                        <span class="text-xs text-muted-foreground">#{{ element.id }}</span>
                                        {{ element.title }}
                                    </span>
                                    <div>
                                        <span
                                            class="rounded-full border px-2 py-0.5 text-xs font-medium  capitalize"
                                            :class="priorityClasses(element.type)"
                                        >
                                            {{ element.type ?? 'feature' }}
                                        </span>
                                    </div>
                                </div>

                                <div v-if="element.description" class="mt-1 text-sm text-muted-foreground">
                                    {{ element.description }}
                                </div>

                                <div class="mt-4 flex items-center justify-between gap-2">
                                    <span class="rounded-full border px-2 py-0.5 text-xs border-zinc-300 dark:bg-zinc-900">
                                        {{ element.assignee?.name ?? 'Not Assigned' }}
                                    </span>

                                    <span
                                        v-if="element.deadline"
                                        class="rounded-full border px-2 py-0.5 text-xs font-medium"
                                        :class="
                                            (element.is_overdue ?? false)
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
                                    <span
                                        v-if="(element.tracked_hours ?? 0) > 0"
                                        class="rounded-full border px-2 py-0.5 text-xs font-medium bg-zinc-100 text-zinc-700 border-zinc-300 dark:bg-zinc-900 dark:text-zinc-200"
                                        title="Tracked time"
                                        >
                                        ⏱ {{ formatHours(element.tracked_hours) }}
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
                    <div
                        v-if="state.modalOpen"
                        class="relative z-10 w-full max-w-lg rounded-2xl border bg-background p-5 shadow-xl max-h-[85vh] flex flex-col"
                    >
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
                                <input v-model="form.title" class="mt-1 w-full rounded border px-3 py-2" placeholder="Title" />
                                <div v-if="form.errors.title" class="mt-1 text-sm text-red-600">
                                    {{ form.errors.title }}
                                </div>
                            </div>

                            <div>
                                <label class="text-sm">Description</label>
                                <textarea
                                    v-model="form.description"
                                    class="mt-1 w-full rounded border px-3 py-2"
                                    rows="4"
                                    placeholder="Optional description..."
                                />
                                <div v-if="form.errors.description" class="mt-1 text-sm text-red-600">
                                    {{ form.errors.description }}
                                </div>
                            </div>

                            <div>
                                <label class="text-sm">Assignee</label>
                                <select v-model="form.assigned_to" class="mt-1 w-full cursor-pointer rounded border px-3 py-2">
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
                                <input v-model="form.deadline" type="date" class="mt-1 w-full rounded border px-3 py-2" />
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
                                <label class="text-sm">Type</label>
                                <select v-model="form.type" class="mt-1 w-full cursor-pointer rounded border px-3 py-2">
                                    <option disabled value="">Select type</option>
                                    <option
                                        v-for="type in ticketTypes"
                                        :key="type.value"
                                        :value="type.value"
                                    >
                                        {{ type.label }}
                                    </option>
                                </select>
                                <div v-if="form.errors.type" class="mt-1 text-sm text-red-600">
                                    {{ form.errors.type }}
                                </div>
                            </div>

                            <div>
                                <label class="text-sm">Attachments</label>

                                <input
                                    type="file"
                                    multiple
                                    class="cursor-pointer mt-1 w-full rounded border px-3 py-2 text-sm"
                                    @change="onFilesChange"
                                />

                                <div v-if="form.files?.length" class="mt-2 space-y-1 text-xs text-muted-foreground">
                                    <div v-for="(f, i) in form.files" :key="i">• {{ f.name }}</div>
                                </div>

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
                                <button
                                    type="button"
                                    class="cursor-pointer rounded border px-3 py-2 text-sm"
                                    @click="closeModal"
                                    :disabled="form.processing"
                                >
                                    Cancel
                                </button>

                                <button
                                    type="submit"
                                    class="cursor-pointer rounded bg-black px-3 py-2 text-sm text-white disabled:cursor-not-allowed disabled:opacity-50"
                                    :disabled="form.processing"
                                >
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
                    <div
                        v-if="editState.open"
                        class="relative z-10 w-full max-w-lg rounded-2xl border bg-background p-5 shadow-xl max-h-[85vh] flex flex-col"
                    >
                        <div class="flex items-start justify-between">
                            <div>
                                <div class="text-lg font-semibold">Edit Ticket</div>

                                <div v-if="!canEditSelectedTicket" class="mt-1 text-sm text-muted-foreground">
                                    You can view this ticket, but only the creator can edit or delete it.
                                </div>
                            </div>

                            <button class="cursor-pointer rounded p-2 hover:bg-muted/40" @click="closeEditModal">✕</button>
                        </div>

                        <form class="mt-4 space-y-3 overflow-y-auto flex-1 pr-1" @submit.prevent="submitEdit">
                            <div>
                                <label class="text-sm">Title</label>
                                <input
                                    v-model="editForm.title"
                                    class="mt-1 w-full rounded border px-3 py-2"
                                    :disabled="!canEditSelectedTicket"
                                />
                                <div v-if="editForm.errors.title" class="mt-1 text-sm text-red-600">
                                    {{ editForm.errors.title }}
                                </div>
                            </div>

                            <div>
                                <label class="text-sm">Description</label>
                                <textarea
                                    v-model="editForm.description"
                                    class="mt-1 w-full rounded border px-3 py-2"
                                    rows="4"
                                    :disabled="!canEditSelectedTicket"
                                />
                                <div v-if="editForm.errors.description" class="mt-1 text-sm text-red-600">
                                    {{ editForm.errors.description }}
                                </div>
                            </div>

                            <div>
                                <label class="text-sm">Status</label>
                                <select
                                    v-model="editForm.status"
                                    class="mt-1 w-full cursor-pointer rounded border px-3 py-2"
                                    :disabled="!canEditSelectedTicket"
                                >
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
                                <select
                                    v-model="editForm.assigned_to"
                                    class="mt-1 w-full cursor-pointer rounded border px-3 py-2"
                                    :disabled="!canEditSelectedTicket"
                                >
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
                                <input v-model="editForm.deadline" type="date" class="mt-1 w-full rounded border px-3 py-2" />
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

                            <div>
                                <label class="text-sm">Type</label>
                                <select v-model="editForm.type" class="mt-1 w-full cursor-pointer rounded border px-3 py-2">
                                    <option disabled value="">Select type</option>
                                    <option
                                        v-for="type in ticketTypes"
                                        :key="type.value"
                                        :value="type.value"
                                    >
                                        {{ type.label }}
                                    </option>
                                </select>
                                <div v-if="editForm.errors.type" class="mt-1 text-sm text-red-600">
                                    {{ editForm.errors.type }}
                                </div>
                            </div>

                            <div class="flex justify-end gap-2 sticky bottom-0 bg-background pt-3">
                                <button
                                    v-if="canEditSelectedTicket"
                                    type="button"
                                    class="cursor-pointer rounded border px-3 py-2 text-sm text-red-600 hover:bg-red-50 disabled:opacity-50"
                                    @click="destroyTicket"
                                    :disabled="editForm.processing"
                                >
                                    Delete
                                </button>
                                <div v-else />

                                <div class="flex gap-2">
                                    <button type="button" class="cursor-pointer rounded border px-3 py-2 text-sm" @click="goToTicketDetails">
                                        View details
                                    </button>

                                    <button
                                        v-if="canEditSelectedTicket"
                                        type="submit"
                                        class="cursor-pointer rounded bg-black px-3 py-2 text-sm text-white disabled:cursor-not-allowed disabled:opacity-50"
                                        :disabled="editForm.processing"
                                    >
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
