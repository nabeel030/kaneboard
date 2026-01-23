import { reactive } from 'vue';

export type ToastType = 'success' | 'error' | 'info';

export type ToastItem = {
  id: string;
  type: ToastType;
  title?: string;
  message: string;
  timeout: number;
};

const state = reactive({
  toasts: [] as ToastItem[],
});

function uid() {
  return Math.random().toString(36).slice(2) + Date.now().toString(36);
}

export function useToast() {
  function push(input: Omit<ToastItem, 'id'>) {
    const id = uid();
    const toast: ToastItem = { id, ...input };
    state.toasts.push(toast);

    window.setTimeout(() => {
      remove(id);
    }, toast.timeout);

    return id;
  }

  function remove(id: string) {
    const idx = state.toasts.findIndex((t) => t.id === id);
    if (idx !== -1) state.toasts.splice(idx, 1);
  }

  return {
    toasts: state.toasts,
    push,
    remove,
    success: (message: string, title = 'Success', timeout = 2500) =>
      push({ type: 'success', title, message, timeout }),
    error: (message: string, title = 'Error', timeout = 3500) =>
      push({ type: 'error', title, message, timeout }),
    info: (message: string, title = 'Info', timeout = 2500) =>
      push({ type: 'info', title, message, timeout }),
  };
}
