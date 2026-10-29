<template>
  <div class="space-y-6">
    <!-- Intestazione e filtri: una riga sopra tutti i grafici -->
    <div class="bg-white rounded-lg shadow px-6 py-4">
      <div class="flex flex-wrap items-center gap-3">
        <div class="mr-auto">
          <h2 class="text-lg font-medium text-gray-900">Statistiche</h2>
          <p class="text-sm text-gray-500">Lead, visite e conversione delle landing page. Solo numeri aggregati, nessun dato dei visitatori.</p>
        </div>
        <div class="flex w-full sm:w-auto p-1 bg-gray-100 rounded-lg" role="group" aria-label="Periodo">
          <button
            v-for="option in PERIODS"
            :key="option.key"
            type="button"
            :aria-pressed="period === option.key"
            :class="[
              'flex-1 sm:flex-none px-1 sm:px-3 py-1.5 rounded-md text-xs sm:text-sm font-medium whitespace-nowrap transition-colors',
              period === option.key ? 'bg-white text-blue-600 shadow-sm' : 'text-gray-600 hover:text-gray-900'
            ]"
            @click="period = option.key"
          >
            {{ option.label }}
          </button>
        </div>
        <label class="flex items-center gap-2 text-sm text-gray-700">
          Raggruppa per
          <select v-model="group" class="px-3 py-1.5 border border-gray-300 rounded-lg text-sm">
            <option v-for="(label, key) in GROUPS" :key="key" :value="key">{{ label.toLowerCase() }}</option>
          </select>
        </label>
      </div>
    </div>

    <div v-if="error" class="rounded-md bg-red-50 p-4 text-sm text-red-800">{{ error }}</div>

    <!-- Numeri del periodo -->
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4" :class="{ 'opacity-60': loading }">
      <StatCard
        label="Lead"
        :value="summary ? formatNumber(summary.leads) : '…'"
        :change="summary?.leads_change_pct ?? null"
        :change-label="`rispetto ${currentPeriod.previous}`"
        :note="summary && summary.leads_change_pct == null ? 'Nessun lead nel periodo precedente' : ''"
        :trend="leadTrend"
      />
      <StatCard
        label="Visitatori"
        :value="summary ? formatNumber(summary.visitors) : '…'"
        :note="summary ? `${formatNumber(summary.views)} visualizzazioni · solo siti con renderer 2.1 o successivo` : ''"
        :trend="visitorTrend"
      />
      <StatCard
        label="Conversione"
        :value="summary ? formatPercent(summary.conversion_pct) : '…'"
        :note="summary ? conversionNote : ''"
      />
      <StatCard
        label="Pagine pubblicate"
        :value="summary ? formatNumber(summary.published_pages) : '…'"
        :note="summary ? `${formatNumber(summary.active_sites)} ${summary.active_sites === 1 ? 'sito' : 'siti'} con renderer 2.1 o successivo` : ''"
      />
    </div>
    <p v-if="summary?.outdated_renderers" class="-mt-3 text-sm text-amber-800 flex items-center gap-1.5">
      <span aria-hidden="true">⚠</span>
      {{ summary.outdated_renderers }} renderer da aggiornare: i siti con un renderer precedente alla 2.1 non contano
      le visite (scheda Renderer).
    </p>

    <!-- Lead nel tempo -->
    <section class="bg-white rounded-lg shadow p-6" :class="{ 'opacity-60': loading }">
      <h3 class="text-base font-medium text-gray-900">Lead {{ currentPeriod.unit }}</h3>
      <VChart class="h-64 mt-2" :option="leadsOption" autoresize />
    </section>

    <!-- Lead per pagina, sito o azienda -->
    <section class="bg-white rounded-lg shadow p-6" :class="{ 'opacity-60': loading }">
      <h3 class="text-base font-medium text-gray-900">Lead per {{ GROUPS[group].toLowerCase() }}</h3>
      <p class="text-sm text-gray-500">Le {{ LIMIT }} con più lead nel periodo, le altre sommate in "Altre".</p>
      <VChart class="h-80 mt-2" :option="breakdownOption" autoresize />
    </section>

    <!-- Calendario: sempre gli ultimi 12 mesi -->
    <section class="bg-white rounded-lg shadow p-6">
      <h3 class="text-base font-medium text-gray-900">Calendario dei lead</h3>
      <p class="text-sm text-gray-500">Ultimi 12 mesi, giorno per giorno: più scuro, più lead.</p>
      <div class="overflow-x-auto">
        <VChart v-if="calendar.length" class="h-[220px] mt-2 min-w-[760px]" :option="calendarOption" autoresize />
        <p v-else class="py-10 text-center text-sm text-gray-400">Caricamento…</p>
      </div>
    </section>

    <!-- Conversione: tabella (fa anche da vista testuale dei grafici per gruppo) -->
    <section class="bg-white rounded-lg shadow" :class="{ 'opacity-60': loading }">
      <div class="px-6 pt-6">
        <h3 class="text-base font-medium text-gray-900">Conversione per {{ GROUPS[group].toLowerCase() }}</h3>
        <p class="text-sm text-gray-500">
          Conversione = lead tracciati / visitatori. Sono tracciati i lead di pagine e siti che contavano già le visite
          (renderer 2.1 o successivo): gli altri non hanno visite con cui confrontarli.
        </p>
      </div>
      <div class="overflow-x-auto mt-4">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left font-medium text-gray-500">{{ GROUPS[group] }}</th>
              <th class="px-6 py-3 text-right font-medium text-gray-500">Lead</th>
              <th class="px-6 py-3 text-right font-medium text-gray-500">Lead tracciati</th>
              <th class="px-6 py-3 text-right font-medium text-gray-500">Visitatori</th>
              <th class="px-6 py-3 text-right font-medium text-gray-500">Conversione</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr v-for="row in visibleConversion" :key="row.series_id ?? row.series" class="hover:bg-gray-50">
              <td class="px-6 py-2.5 text-gray-900">
                <span class="inline-flex items-center gap-2">
                  <span
                    v-if="colorOf(row)"
                    class="w-2.5 h-2.5 rounded-sm shrink-0"
                    :style="{ backgroundColor: colorOf(row) }"
                    aria-hidden="true"
                  ></span>
                  {{ row.series }}
                </span>
              </td>
              <td class="px-6 py-2.5 text-right tabular-nums text-gray-900">{{ formatNumber(row.leads) }}</td>
              <td class="px-6 py-2.5 text-right tabular-nums text-gray-600">{{ formatNumber(row.leads_tracked) }}</td>
              <td class="px-6 py-2.5 text-right tabular-nums text-gray-600">{{ formatNumber(row.visitors) }}</td>
              <td class="px-6 py-2.5 text-right tabular-nums text-gray-900">{{ formatPercent(row.conversion_pct) }}</td>
            </tr>
            <tr v-if="!conversion.length && !loading">
              <td colspan="5" class="px-6 py-8 text-center text-gray-400">Nessun dato nel periodo</td>
            </tr>
          </tbody>
        </table>
      </div>
      <div v-if="conversion.length > TABLE_ROWS" class="px-6 py-3 border-t border-gray-100">
        <button type="button" class="text-sm text-blue-600 hover:text-blue-800" @click="showAllRows = !showAllRows">
          {{ showAllRows ? 'Mostra meno' : `Mostra tutte (${conversion.length})` }}
        </button>
      </div>
    </section>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import VChart from 'vue-echarts'
import StatCard from './StatCard.vue'
import { useStatsStore } from '../../stores/statsStore'
import {
  SERIES, OTHERS, SEQUENTIAL, INK, baseOption, categoryAxis, valueAxis,
  formatNumber, formatPercent, periodLabel, isoDate, seriesColors
} from './charts'

const statsStore = useStatsStore()

const PERIODS = [
  { key: '7', label: '7 giorni', days: 7, interval: 'day', unit: 'al giorno', previous: 'ai 7 giorni precedenti' },
  { key: '30', label: '30 giorni', days: 30, interval: 'day', unit: 'al giorno', previous: 'ai 30 giorni precedenti' },
  { key: '90', label: '90 giorni', days: 90, interval: 'week', unit: 'a settimana', previous: 'ai 90 giorni precedenti' },
  { key: '12m', label: '12 mesi', months: 12, interval: 'month', unit: 'al mese', previous: 'ai 12 mesi precedenti' }
]
const GROUPS = { page: 'Pagina', site: 'Sito', company: 'Azienda' }
const LIMIT = 7
const TABLE_ROWS = 15
const PERIOD_KEY = 'elpb.statsPeriod'
const GROUP_KEY = 'elpb.statsGroup'

// Preferenze dell'ultimo utilizzo (solo comodità: senza storage si parte da 30 giorni per pagina)
function stored(key, allowed, fallback) {
  try {
    const value = localStorage.getItem(key)
    return allowed.includes(value) ? value : fallback
  } catch {
    return fallback
  }
}
function remember(key, value) {
  try { localStorage.setItem(key, value) } catch { /* storage non disponibile */ }
}

const period = ref(stored(PERIOD_KEY, PERIODS.map(p => p.key), '30'))
const group = ref(stored(GROUP_KEY, Object.keys(GROUPS), 'page'))
const loading = ref(true)
const error = ref('')
const summary = ref(null)
const leadsTotal = ref([])
const viewsTotal = ref([])
const breakdown = ref([])
const conversion = ref([])
const calendar = ref([])
const showAllRows = ref(false)

const currentPeriod = computed(() => PERIODS.find(p => p.key === period.value))

/** Inizio del periodo a mezzanotte (giorni interi); 12 mesi: dal primo del mese */
function periodStart(option) {
  const today = new Date()
  if (option.months) return new Date(today.getFullYear(), today.getMonth() - option.months + 1, 1)
  return new Date(today.getFullYear(), today.getMonth(), today.getDate() - option.days + 1)
}

// Le risposte di una richiesta superata (cambio rapido di periodo) vengono ignorate
let requestId = 0

async function load() {
  const id = ++requestId
  const option = currentPeriod.value
  const params = { from: isoDate(periodStart(option)), interval: option.interval }
  loading.value = true
  error.value = ''
  try {
    const [s, leads, views, byGroup, conv] = await Promise.all([
      statsStore.fetchSummary({ from: params.from }),
      statsStore.fetchLeads(params),
      statsStore.fetchViews(params),
      statsStore.fetchLeads({ ...params, group: group.value, limit: LIMIT }),
      statsStore.fetchConversion({ from: params.from, group: group.value })
    ])
    if (id !== requestId) return
    summary.value = s
    leadsTotal.value = leads
    viewsTotal.value = views
    breakdown.value = byGroup
    conversion.value = conv
  } catch (e) {
    if (id !== requestId) return
    error.value = e.response?.data?.error || 'Impossibile caricare le statistiche.'
  } finally {
    if (id === requestId) loading.value = false
  }
}

async function loadCalendar() {
  const today = new Date()
  const from = new Date(today.getFullYear() - 1, today.getMonth(), today.getDate() + 1)
  try {
    calendar.value = await statsStore.fetchLeads({ from: isoDate(from), interval: 'day' })
  } catch {
    calendar.value = []
  }
}

onMounted(() => {
  load()
  loadCalendar()
})
watch(period, value => { remember(PERIOD_KEY, value); load() })
watch(group, value => { remember(GROUP_KEY, value); showAllRows.value = false; load() })

// --- Numeri ---------------------------------------------------------------

const leadTrend = computed(() => leadsTotal.value.map(r => r.leads))
const visitorTrend = computed(() => viewsTotal.value.map(r => r.visitors))

const conversionNote = computed(() => {
  const s = summary.value
  if (s.conversion_pct == null) return 'Nessun visitatore contato nel periodo'
  return `su ${formatNumber(s.leads_tracked)} ${s.leads_tracked === 1 ? 'lead tracciato' : 'lead tracciati'} di ${formatNumber(s.leads)}`
})

// --- Lead nel tempo -------------------------------------------------------

const leadsOption = computed(() => {
  const interval = currentPeriod.value.interval
  const rows = leadsTotal.value
  return {
    ...baseOption(),
    tooltip: {
      ...baseOption().tooltip,
      formatter: ([point]) => `${periodLabel(rows[point.dataIndex].time, interval, true)}<br><b>${formatNumber(point.value)}</b> lead`
    },
    xAxis: categoryAxis(rows.map(r => periodLabel(r.time, interval))),
    yAxis: valueAxis(),
    series: [{
      type: 'bar',
      name: 'Lead',
      data: rows.map(r => r.leads),
      barMaxWidth: 28,
      itemStyle: { color: SERIES[0], borderRadius: [4, 4, 0, 0] },
      emphasis: { itemStyle: { color: '#1c5cab' } }
    }]
  }
})

// --- Lead per gruppo (barre impilate) -------------------------------------

// Colore per serie: resta lo stesso cambiando periodo finché la serie è visibile
const colorMemory = new Map()
const OTHERS_KEY = '__others__'
const keyOf = row => row.series_id ?? (row.series === 'Altre' ? OTHERS_KEY : row.series)

const breakdownSeries = computed(() => {
  const times = [...new Set(breakdown.value.map(r => r.time))]
  const bySeries = new Map()
  for (const row of breakdown.value) {
    const key = keyOf(row)
    if (!bySeries.has(key)) bySeries.set(key, { key, name: row.series, values: new Map() })
    bySeries.get(key).values.set(row.time, row.leads)
  }
  const list = [...bySeries.values()]
  seriesColors(colorMemory, list.filter(s => s.key !== OTHERS_KEY && s.name !== 'Nessun dato').map(s => s.key))
  return { times, list }
})

function colorOf(row) {
  return colorMemory.get(String(row.series_id)) || null
}

const breakdownOption = computed(() => {
  const interval = currentPeriod.value.interval
  const { times, list } = breakdownSeries.value
  return {
    ...baseOption(),
    grid: { ...baseOption().grid, top: 48 },
    legend: {
      type: 'scroll',
      top: 0,
      left: 0,
      icon: 'roundRect',
      itemWidth: 10,
      itemHeight: 10,
      textStyle: { color: INK.secondary }
    },
    tooltip: {
      ...baseOption().tooltip,
      formatter: points => {
        const shown = points.filter(p => p.value > 0).sort((a, b) => b.value - a.value)
        const total = points.reduce((sum, p) => sum + (p.value || 0), 0)
        const lines = shown.map(p => `${p.marker} ${p.seriesName}: <b>${formatNumber(p.value)}</b>`)
        return [`${periodLabel(times[points[0].dataIndex], interval, true)} · <b>${formatNumber(total)}</b> lead`, ...lines].join('<br>')
      }
    },
    xAxis: categoryAxis(times.map(t => periodLabel(t, interval))),
    yAxis: valueAxis(),
    series: list.map(s => ({
      type: 'bar',
      name: s.name,
      stack: 'lead',
      barMaxWidth: 28,
      // Zero = nessuna barra (null), altrimenti il bordo disegna un segmento vuoto sulla base
      data: times.map(t => s.values.get(t) || null),
      // Bordo del colore della superficie: 2px di stacco tra i segmenti impilati
      itemStyle: { color: colorMemory.get(s.key) || OTHERS, borderColor: '#ffffff', borderWidth: 1 },
      emphasis: { focus: 'series' }
    }))
  }
})

// --- Calendario -----------------------------------------------------------

const calendarOption = computed(() => {
  const data = calendar.value.map(r => [r.time.slice(0, 10), r.leads])
  const max = Math.max(1, ...data.map(d => d[1]))
  return {
    textStyle: { fontFamily: baseOption().textStyle.fontFamily, color: INK.secondary },
    tooltip: {
      backgroundColor: '#ffffff',
      borderColor: 'rgba(11,11,11,0.10)',
      textStyle: { color: INK.primary, fontSize: 13 },
      formatter: p => `${periodLabel(p.value[0], 'day', true)}<br><b>${formatNumber(p.value[1])}</b> lead`
    },
    visualMap: {
      min: 0,
      max,
      calculable: false,
      orient: 'horizontal',
      left: 'right',
      bottom: 0,
      itemWidth: 10,
      itemHeight: 120,
      text: [`${formatNumber(max)}`, '0'],
      textStyle: { color: INK.muted },
      inRange: { color: SEQUENTIAL }
    },
    calendar: {
      top: 24,
      left: 32,
      right: 8,
      bottom: 36,
      // Larghezza adattata alla scheda, altezza fissa: con 'auto' su entrambe le celle
      // restano quadrate e il calendario occupa metà riga
      cellSize: ['auto', 18],
      range: data.length ? [data[0][0], data[data.length - 1][0]] : isoDate(new Date()).slice(0, 4),
      splitLine: { show: false },
      itemStyle: { borderColor: '#ffffff', borderWidth: 2, color: '#f6f5f2' },
      dayLabel: { firstDay: 1, nameMap: ['D', 'L', 'M', 'M', 'G', 'V', 'S'], color: INK.muted, fontSize: 11 },
      monthLabel: {
        nameMap: ['gen', 'feb', 'mar', 'apr', 'mag', 'giu', 'lug', 'ago', 'set', 'ott', 'nov', 'dic'],
        color: INK.muted,
        fontSize: 11
      },
      yearLabel: { show: false }
    },
    series: [{ type: 'heatmap', coordinateSystem: 'calendar', data }]
  }
})

// --- Tabella --------------------------------------------------------------

// Dalla conversione più alta; senza visitatori (conversione non calcolabile) in fondo, per lead
const sortedConversion = computed(() => [...conversion.value].sort((a, b) =>
  (a.conversion_pct == null) - (b.conversion_pct == null)
  || (b.conversion_pct ?? 0) - (a.conversion_pct ?? 0)
  || b.leads_tracked - a.leads_tracked
  || b.leads - a.leads
))

const visibleConversion = computed(() =>
  showAllRows.value ? sortedConversion.value : sortedConversion.value.slice(0, TABLE_ROWS)
)
</script>
