<script setup lang="ts">
import { computed } from 'vue';
import { Doughnut } from 'vue-chartjs';
import {
  Chart as ChartJS,
  ArcElement,
  Tooltip,
  Legend,
} from 'chart.js';

ChartJS.register(ArcElement, Tooltip, Legend);

const props = defineProps<{
  value: number; // 0–100
  label: string;
  color?: string;
}>();

const remaining = computed(() => Math.max(0, 100 - props.value));

const chartData = computed(() => ({
  labels: ['Progress', 'Remaining'],
  datasets: [
    {
      data: [props.value, remaining.value],
      backgroundColor: [
        props.color ?? '#0f172a', // slate-900
        'rgba(0,0,0,0.08)',
      ],
      borderWidth: 0,
      cutout: '70%',
    },
  ],
}));

const options = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: { display: false },
    tooltip: {
      callbacks: {
        label: (ctx: any) => `${ctx.raw}%`,
      },
    },
  },
};
</script>

<template>
  <div class="relative h-28 w-28">
    <Doughnut :data="chartData" :options="options" />

    <!-- Center label -->
    <div class="pointer-events-none absolute inset-0 flex flex-col items-center justify-center">
      <div class="text-lg font-semibold">{{ value }}%</div>
      <div class="text-[10px] text-muted-foreground">{{ label }}</div>
    </div>
  </div>
</template>
