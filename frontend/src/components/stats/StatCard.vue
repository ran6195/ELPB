<template>
  <div class="rounded-xl border border-gray-200 bg-white p-4 flex flex-col gap-1 min-w-0">
    <p class="text-sm text-gray-500">{{ label }}</p>
    <p class="text-3xl font-semibold text-gray-900 tabular-nums leading-tight">{{ value }}</p>

    <!-- Variazione sul periodo precedente: freccia e testo, mai solo il colore -->
    <p v-if="change != null" class="text-sm tabular-nums" :style="{ color: change >= 0 ? INK.up : INK.down }">
      <span aria-hidden="true">{{ change > 0 ? '▲' : change < 0 ? '▼' : '=' }}</span>
      {{ change > 0 ? '+' : '' }}{{ formatPercent(change) }}
      <span class="text-gray-500">{{ changeLabel }}</span>
    </p>
    <p v-if="note" class="text-sm text-gray-500">{{ note }}</p>

    <VChart v-if="trend?.length > 1" class="h-10 mt-1" :option="sparkline" autoresize />
  </div>
</template>

<script setup>
import { computed } from 'vue'
import VChart from 'vue-echarts'
import { INK, SERIES, formatPercent } from './charts'

const props = defineProps({
  label: { type: String, required: true },
  value: { type: String, required: true },
  /** Variazione percentuale (null: non mostrata) */
  change: { type: Number, default: null },
  changeLabel: { type: String, default: '' },
  note: { type: String, default: '' },
  /** Valori dell'andamento nel periodo, per la linea sotto il numero */
  trend: { type: Array, default: null }
})

const sparkline = computed(() => ({
  animation: false,
  grid: { left: 0, right: 0, top: 2, bottom: 2 },
  xAxis: { type: 'category', show: false, data: props.trend.map((_, i) => i) },
  yAxis: { type: 'value', show: false, min: 0 },
  series: [{
    type: 'line',
    data: props.trend,
    symbol: 'none',
    lineStyle: { width: 2, color: SERIES[0] },
    areaStyle: { color: 'rgba(42,120,214,0.08)' }
  }]
}))
</script>
