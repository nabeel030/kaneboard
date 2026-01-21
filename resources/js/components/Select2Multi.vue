<script setup lang="ts">
import { onMounted, onBeforeUnmount, ref, watch } from 'vue';
import $ from 'jquery';

// 👇 THIS IS THE KEY FIX
import select2 from 'select2';

import 'select2/dist/css/select2.css';

select2($); // 👈 attach select2 to THIS jQuery instance

type Option = { id: number; text: string };

const props = defineProps<{
  modelValue: number[];
  options: Option[];
  placeholder?: string;
  disabled?: boolean;
}>();

const emit = defineEmits<{
  (e: 'update:modelValue', value: number[]): void;
}>();

const el = ref<HTMLSelectElement | null>(null);

function syncToDom(value: number[]) {
  if (!el.value) return;
  $(el.value).val(value.map(String)).trigger('change.select2');
}

onMounted(() => {
  if (!el.value) return;

  const $el = $(el.value);

  $el.select2({
    width: '100%',
    placeholder: props.placeholder ?? 'Select...',
    closeOnSelect: false,
  });

  $el.on('change', () => {
    const val = ($el.val() ?? []) as string[];
    emit('update:modelValue', val.map(Number));
  });

  syncToDom(props.modelValue);
});

watch(() => props.modelValue, syncToDom, { deep: true });

watch(
  () => props.options,
  () => syncToDom(props.modelValue),
  { deep: true }
);

watch(
  () => props.disabled,
  (d) => {
    if (!el.value) return;
    $(el.value).prop('disabled', !!d);
  }
);

onBeforeUnmount(() => {
  if (!el.value) return;
  const $el = $(el.value);
  $el.off('change');
  $el.select2('destroy');
});
</script>

<template>
  <select ref="el" multiple class="w-full" :disabled="disabled">
    <option v-for="o in options" :key="o.id" :value="o.id">
      {{ o.text }}
    </option>
  </select>
</template>

<style scoped>
:deep(.select2-container .select2-selection--multiple) {
  min-height: 40px;
  border-radius: 0.375rem;
  border: 1px solid rgb(228 228 231);
  padding: 0.25rem 0.5rem;
}
:deep(.select2-container--default .select2-selection--multiple .select2-selection__choice) {
  border-radius: 0.375rem;
}
</style>
