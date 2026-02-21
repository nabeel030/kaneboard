<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { Link, router, usePage } from '@inertiajs/vue3'

type RunningTimer = null | {
  ticket_id: number
  ticket_title?: string | null
  ticket_status?: string | null
  started_at?: string | null
  elapsed_seconds: number
}

const STORAGE_KEY = 'kb_floating_timer_v1'
const POS_KEY = 'kb_floating_timer_pos_v1'
const MIN_KEY = 'kb_floating_timer_min_v1'

function readJSON<T>(key: string, fallback: T): T {
  try {
    const raw = localStorage.getItem(key)
    if (!raw) return fallback
    return JSON.parse(raw) as T
  } catch {
    return fallback
  }
}

function writeJSON(key: string, value: any) {
  try {
    localStorage.setItem(key, JSON.stringify(value))
  } catch {
    // ignore
  }
}

function readBool(key: string, fallback = false) {
  try {
    const raw = localStorage.getItem(key)
    if (raw === null) return fallback
    return raw === '1'
  } catch {
    return fallback
  }
}

function writeBool(key: string, v: boolean) {
  try {
    localStorage.setItem(key, v ? '1' : '0')
  } catch {
    // ignore
  }
}

const page = usePage()
const runningTimer = computed(() => (page.props.runningTimer as RunningTimer) ?? null)
const visible = computed(() => !!runningTimer.value)

/* ---------------------------
   ✅ Persisted seconds
---------------------------- */
const seconds = ref<number>(0)
let tickInterval: number | null = null
let lastTickMs: number | null = null

function startTick() {
  stopTick()
  lastTickMs = Date.now()

  tickInterval = window.setInterval(() => {
    const nowMs = Date.now()

    // ✅ Prevent giant jumps if JS thread paused during navigation/render
    const raw = Math.floor((nowMs - (lastTickMs ?? nowMs)) / 1000)
    const deltaSec = Math.max(0, Math.min(raw, 2)) // cap at 2s per tick

    if (deltaSec > 0) {
      seconds.value += deltaSec
      lastTickMs = nowMs
      persistState()
    }
  }, 250)
}

function stopTick() {
  if (tickInterval) window.clearInterval(tickInterval)
  tickInterval = null
  lastTickMs = null
}

function persistState() {
  const rt = runningTimer.value
  if (!rt) return

  writeJSON(STORAGE_KEY, {
    ticket_id: rt.ticket_id,
    seconds: seconds.value,
    updated_at_ms: Date.now(), // keep this accurate
  })
}

function restoreState() {
  const rt = runningTimer.value
  if (!rt) {
    seconds.value = 0
    return
  }

  const saved = readJSON<{ ticket_id: number; seconds: number; updated_at_ms: number } | null>(STORAGE_KEY, null)

  const serverSeconds = Math.max(0, Math.floor(rt.elapsed_seconds ?? 0))

  // default from server
  let nextSeconds = serverSeconds

  if (saved && saved.ticket_id === rt.ticket_id && typeof saved.seconds === 'number') {
    nextSeconds = Math.max(0, Math.floor(saved.seconds))

    // ✅ If browser was closed, catch up using updated_at_ms
    if (typeof saved.updated_at_ms === 'number' && saved.updated_at_ms > 0) {
      const gap = Math.floor((Date.now() - saved.updated_at_ms) / 1000)
      if (gap > 0) {
        nextSeconds += gap
      }
    }
  }

  // ✅ Never go backward vs server truth
  seconds.value = Math.max(nextSeconds, serverSeconds)
}

/* ---------------------------
   ✅ Draggable
---------------------------- */
type Pos = { x: number; y: number }
const pos = ref<Pos | null>(null)
const dragging = ref(false)

function clamp(n: number, min: number, max: number) {
  return Math.min(max, Math.max(min, n))
}

function loadPos() {
  const saved = readJSON<Pos | null>(POS_KEY, null)
  if (saved && typeof saved.x === 'number' && typeof saved.y === 'number') {
    pos.value = saved
    return
  }
  // default bottom-right-ish
  const w = 360
  const h = 160
  pos.value = {
    x: Math.max(16, window.innerWidth - w - 24),
    y: Math.max(16, window.innerHeight - h - 24),
  }
}

function savePos() {
  if (!pos.value) return
  writeJSON(POS_KEY, pos.value)
}

let dragStart = { mx: 0, my: 0, x: 0, y: 0 }

function onPointerDown(e: PointerEvent) {
  if (!pos.value) return
  dragging.value = true
  dragStart = { mx: e.clientX, my: e.clientY, x: pos.value.x, y: pos.value.y }
  ;(e.target as HTMLElement).setPointerCapture?.(e.pointerId)
}

function onPointerMove(e: PointerEvent) {
  if (!dragging.value || !pos.value) return

  const dx = e.clientX - dragStart.mx
  const dy = e.clientY - dragStart.my

  const cardW = minimized.value ? 220 : 360
  const cardH = minimized.value ? 64 : 170

  pos.value = {
    x: clamp(dragStart.x + dx, 8, window.innerWidth - cardW - 8),
    y: clamp(dragStart.y + dy, 8, window.innerHeight - cardH - 8),
  }
}

function onPointerUp() {
  if (!dragging.value) return
  dragging.value = false
  savePos()
}

/* ---------------------------
   ✅ Minimize
---------------------------- */
const minimized = ref<boolean>(readBool(MIN_KEY, false))

function toggleMinimize() {
  minimized.value = !minimized.value
  writeBool(MIN_KEY, minimized.value)

  if (pos.value) {
    const cardW = minimized.value ? 220 : 360
    const cardH = minimized.value ? 64 : 170
    pos.value = {
      x: clamp(pos.value.x, 8, window.innerWidth - cardW - 8),
      y: clamp(pos.value.y, 8, window.innerHeight - cardH - 8),
    }
    savePos()
  }
}

/* ---------------------------
   ✅ Formatting + actions
---------------------------- */
function pad(n: number) {
  return String(n).padStart(2, '0')
}

const label = computed(() => {
  const s = Math.max(0, Math.floor(seconds.value))
  const hh = Math.floor(s / 3600)
  const mm = Math.floor((s % 3600) / 60)
  const ss = s % 60
  return `${pad(hh)}:${pad(mm)}:${pad(ss)}`
})

function pause() {
  const rt = runningTimer.value
  if (!rt) return

  router.post(`/tickets/${rt.ticket_id}/timer/pause`, {}, {
    preserveScroll: true,
    preserveState: true,
  })
}

function stop() {
  const rt = runningTimer.value
  if (!rt) return

  if (!confirm('Stop timer? This will finalize the current session.')) return

  router.post(`/tickets/${rt.ticket_id}/timer/stop`, {}, {
    preserveScroll: true,
    preserveState: true,
  })
}

/* ---------------------------
   ✅ Lifecycle + syncing
---------------------------- */
watch(runningTimer, (next) => {
  if (!next) {
    stopTick()
    seconds.value = 0
    return
  }

  restoreState()
  persistState()
  startTick()
}, { immediate: true })

function onResize() {
  if (!pos.value) return
  const cardW = minimized.value ? 220 : 360
  const cardH = minimized.value ? 64 : 170
  pos.value = {
    x: clamp(pos.value.x, 8, window.innerWidth - cardW - 8),
    y: clamp(pos.value.y, 8, window.innerHeight - cardH - 8),
  }
  savePos()
}

function handleBeforeUnload(e: BeforeUnloadEvent) {
//   if (!runningTimer.value) return

//   e.preventDefault()
//   e.returnValue = 'Timer is running! Are you sure you want to leave?'
}


let offStart: any = null
let offFinish: any = null

onMounted(() => {
    window.addEventListener('beforeunload', handleBeforeUnload) 
  loadPos()
  window.addEventListener('resize', onResize)

  // ✅ Prevent big delta jumps during Inertia visits
  offStart = router.on('start', () => {
    lastTickMs = Date.now()
  })
  offFinish = router.on('finish', () => {
    lastTickMs = Date.now()
  })

  if (visible.value) {
    restoreState()
    startTick()
  }
})

onBeforeUnmount(() => {
    window.addEventListener('beforeunload', handleBeforeUnload) 

  stopTick()
  window.removeEventListener('resize', onResize)
  offStart?.()
  offFinish?.()
})
</script>

<template>
  <div v-if="visible && pos" class="fixed z-[9999]" :style="{ left: `${pos.x}px`, top: `${pos.y}px` }">
    <!-- Glow layer -->
    <div
      class="absolute -inset-2 rounded-3xl blur-xl opacity-70"
      style="background: radial-gradient(circle at 30% 20%, rgba(16,185,129,.35), transparent 55%),
                           radial-gradient(circle at 70% 80%, rgba(34,197,94,.25), transparent 55%);"
    />

    <div
      class="relative rounded-2xl border bg-white/95 shadow-2xl backdrop-blur dark:bg-zinc-950/90 ring-2 ring-emerald-400/40"
    >
      <!-- Draggable header -->
      <div
        class="flex items-center justify-between gap-3 px-4 py-3 select-none cursor-grab active:cursor-grabbing"
        @pointerdown="onPointerDown"
        @pointermove="onPointerMove"
        @pointerup="onPointerUp"
        @pointercancel="onPointerUp"
      >
        <div class="flex items-center gap-2 min-w-0">
          <span class="relative flex h-2.5 w-2.5">
            <span class="absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75 animate-ping"></span>
            <span class="relative inline-flex h-2.5 w-2.5 rounded-full bg-emerald-500"></span>
          </span>

          <div class="min-w-0">
            <div class="text-[11px] font-semibold tracking-wide text-emerald-700 dark:text-emerald-200 uppercase">
              Timer running
            </div>
            <div v-if="!minimized" class="truncate text-xs text-muted-foreground">
              #{{ runningTimer?.ticket_id }} — {{ runningTimer?.ticket_title ?? 'Ticket' }}
            </div>
          </div>
        </div>

        <button
          type="button"
          class="cursor-pointer rounded-lg border px-2 py-1 text-xs hover:bg-muted/40"
          @click.stop="toggleMinimize"
          title="Minimize"
        >
          {{ minimized ? '▢' : '—' }}
        </button>
      </div>

      <!-- Body -->
      <div v-if="!minimized" class="px-5 pb-5">
        <div class="mt-1 text-3xl font-extrabold tabular-nums text-emerald-600">
          {{ label }}
        </div>

        <div class="mt-4 flex gap-2">
          <button
            type="button"
            class="flex-1 cursor-pointer rounded-xl border px-3 py-2 text-sm font-medium hover:bg-muted/40 transition"
            @click="pause"
          >
            ⏸
          </button>

          <button
            type="button"
            class="flex-1 cursor-pointer rounded-xl border px-3 py-2 text-sm font-medium text-red-600 hover:bg-red-50 transition"
            @click="stop"
          >
            ■
          </button>

          <Link
            :href="`/tickets/${runningTimer?.ticket_id}`"
            class="flex-1 cursor-pointer rounded-xl border px-3 py-2 text-center text-sm font-medium hover:bg-muted/40 transition"
          >
            Open
          </Link>
        </div>
      </div>

      <!-- Minimized -->
      <div v-else class="px-4 pb-3">
        <div class="flex items-center justify-between gap-3">
          <div class="text-lg font-bold tabular-nums text-emerald-600">
            {{ label }}
          </div>

          <Link
            :href="`/tickets/${runningTimer?.ticket_id}`" preserve-state preserve-scroll
            class="cursor-pointer rounded-xl border px-3 py-1.5 text-center text-xs font-medium hover:bg-muted/40 transition"
          >
            Open
          </Link>
        </div>
      </div>
    </div>
  </div>
</template>