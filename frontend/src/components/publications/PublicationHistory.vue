<template>
  <div class="bg-white rounded-lg shadow">
    <div class="px-6 py-4 border-b border-gray-200">
      <h2 class="text-lg font-medium text-gray-900">Pubblicazioni</h2>
      <p class="text-sm text-gray-500 mt-1">
        Periodi in cui ogni pagina è stata online (pubblicata e non archiviata) e per quale azienda: base della
        fatturazione per pagina pubblicata. Le pagine pubblicate prima del 14/11/2026 hanno un inizio stimato.
      </p>
    </div>

    <div class="p-6 space-y-4">
      <div v-if="!loading && rows.length" class="flex flex-wrap items-center gap-3 text-sm">
        <span class="px-3 py-1.5 rounded-lg bg-gray-50 text-gray-700">{{ groups.length }} {{ groups.length === 1 ? 'pagina' : 'pagine' }}</span>
        <span class="px-3 py-1.5 rounded-lg bg-green-50 text-green-800">{{ onlineCount }} online ora</span>
        <span class="px-3 py-1.5 rounded-lg bg-gray-50 text-gray-700" :title="month ? 'Somma dei giorni online nel mese di ogni pagina' : 'Somma dei giorni online di ogni pagina'">
          {{ totalDays }} giorni-pagina{{ month ? ' nel mese' : '' }}
        </span>
        <span v-if="estimatedCount" class="px-3 py-1.5 rounded-lg bg-blue-50 text-blue-800" title="Pagine già pubblicate quando è partito lo storico: l'inizio viene dal registro attività o dalla prima richiesta di un sito">
          {{ estimatedCount }} con inizio ricostruito
        </span>

        <div class="flex flex-wrap items-center gap-2 ml-auto">
          <input
            v-model="search"
            type="search"
            placeholder="Cerca pagina…"
            class="px-3 py-1.5 border border-gray-300 rounded-lg text-sm w-44"
          />
          <select v-model="month" class="px-3 py-1.5 border border-gray-300 rounded-lg text-sm">
            <option value="">Tutto lo storico</option>
            <option v-for="m in months" :key="m.value" :value="m.value">{{ m.label }}</option>
          </select>
          <select v-model="company" class="px-3 py-1.5 border border-gray-300 rounded-lg text-sm">
            <option value="">Tutte le aziende</option>
            <option v-for="c in companyOptions" :key="c.value" :value="c.value">{{ c.label }}</option>
          </select>
          <select v-model="status" class="px-3 py-1.5 border border-gray-300 rounded-lg text-sm">
            <option value="all">Tutte le pagine</option>
            <option value="online">Online ora</option>
            <option value="offline">Non più online</option>
          </select>
          <button
            type="button"
            class="bg-green-600 hover:bg-green-700 disabled:opacity-50 text-white px-4 py-1.5 rounded-lg text-sm font-medium whitespace-nowrap"
            :disabled="!groups.length"
            title="Una riga per periodo, con i filtri attuali"
            @click="exportCsv"
          >
            Esporta CSV
          </button>
        </div>
      </div>

      <div v-if="error" class="rounded-md bg-red-50 p-3 text-sm text-red-800">{{ error }}</div>

      <div v-if="loading" class="text-center py-8 text-gray-500">Caricamento...</div>
      <div v-else-if="!rows.length" class="text-center py-8 text-gray-500">
        Nessun periodo registrato: lo storico si riempie quando le pagine vengono pubblicate.
      </div>
      <div v-else-if="!groups.length" class="text-center py-8 text-gray-500">Nessuna pagina corrisponde ai filtri.</div>
      <div v-else class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">Pagina</th>
              <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">Azienda</th>
              <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">Online dal</th>
              <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">Al</th>
              <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">Giorni{{ month ? ' nel mese' : '' }}</th>
              <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">Note</th>
            </tr>
          </thead>
          <tbody v-for="group in groups" :key="group.page.id" class="bg-white divide-y divide-gray-100 border-t border-gray-200">
            <tr v-for="(period, i) in group.periods" :key="period.id" class="hover:bg-gray-50">
              <td v-if="i === 0" :rowspan="group.periods.length" class="px-3 py-2 text-sm align-top min-w-[14rem] max-w-[20rem]">
                <router-link
                  v-if="group.page.status !== 'deleted'"
                  :to="`/editor/${group.page.id}`"
                  class="block truncate font-medium text-primary-600 hover:underline"
                  :title="group.page.title"
                >{{ group.page.title }}</router-link>
                <span v-else class="block text-gray-500 italic">Pagina eliminata</span>
                <span class="flex flex-wrap items-center gap-1 text-xs text-gray-400">
                  <span class="font-mono truncate" :title="group.page.slug">#{{ group.page.id }}<template v-if="group.page.slug"> · {{ group.page.slug }}</template></span>
                  <span :class="['px-1.5 py-0.5 rounded font-medium', STATUS[group.page.status].classes]">{{ STATUS[group.page.status].label }}</span>
                  <span class="text-gray-500">· {{ group.days }} {{ group.days === 1 ? 'giorno' : 'giorni' }}{{ month ? ' nel mese' : '' }}</span>
                </span>
              </td>
              <td class="px-3 py-2 text-sm text-gray-700 align-top whitespace-nowrap">{{ companyName(period.company_id) }}</td>
              <td class="px-3 py-2 text-sm text-gray-700 align-top whitespace-nowrap" :title="period.started_at">
                {{ formatDateTime(period.started_at) }}
              </td>
              <td class="px-3 py-2 text-sm align-top whitespace-nowrap" :title="period.ended_at || ''">
                <span v-if="period.ended_at" class="text-gray-700">{{ formatDateTime(period.ended_at) }}</span>
                <span v-else class="px-1.5 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">in corso</span>
              </td>
              <td class="px-3 py-2 text-sm text-gray-700 align-top text-right tabular-nums">{{ period.days }}</td>
              <td class="px-3 py-2 text-xs align-top">
                <span v-if="SOURCES[period.start_source]" class="block text-blue-700" :title="SOURCES[period.start_source].help">
                  Inizio {{ SOURCES[period.start_source].label }}
                </span>
                <span v-if="period.end_reason" class="block text-gray-500">Fine: {{ END_REASONS[period.end_reason] || period.end_reason }}</span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { usePublicationStore } from '../../stores/publicationStore'

/**
 * Storico della pubblicazione pagina per pagina (page_publications), solo admin.
 * Con un mese scelto mostra solo i periodi che lo toccano e conta i giorni online
 * dentro il mese (un giorno conta se la pagina è stata online anche solo in parte).
 */
const publicationStore = usePublicationStore()

const STATUS = {
  online: { label: 'online', classes: 'bg-green-100 text-green-800' },
  draft: { label: 'ritirata', classes: 'bg-gray-100 text-gray-700' },
  archived: { label: 'archiviata', classes: 'bg-amber-100 text-amber-800' },
  deleted: { label: 'eliminata', classes: 'bg-red-100 text-red-700' }
}

// Inizio ricostruito per le pagine già pubblicate quando è partito lo storico
const SOURCES = {
  audit_log: { label: 'dal registro attività', help: 'Ultima pubblicazione registrata nel registro attività: data esatta' },
  first_hit: { label: 'stimato: prima richiesta di un sito', help: 'Pubblicata prima dello storico: prima volta che un sito ha chiesto la pagina' },
  first_view: { label: 'stimato: prima visita', help: 'Pubblicata prima dello storico: primo giorno con visite registrate' },
  backfill: { label: 'stimato: nessuna traccia', help: 'Pubblicata prima dello storico e mai mostrata da un sito: inizio fissato al giorno del recupero' }
}

const END_REASONS = {
  unpublished: 'ritirata',
  archived: 'archiviata',
  deleted: 'eliminata',
  reassigned: 'passata a un\'altra azienda',
  reconciled: 'chiusa dal riallineamento'
}

const NO_COMPANY = 'none'

const loading = ref(true)
const error = ref('')
const rows = ref([])
const companies = ref({})
const serverNow = ref('')
const search = ref('')
const month = ref('')
const company = ref('')
const status = ref('all')

onMounted(async () => {
  try {
    const result = await publicationStore.fetchPublications()
    rows.value = result.data || []
    companies.value = result.companies || {}
    serverNow.value = result.now || ''
  } catch (e) {
    error.value = e.response?.data?.error || 'Impossibile caricare lo storico della pubblicazione'
  } finally {
    loading.value = false
  }
})

// --- Giorni: "AAAA-MM-GG" come numero di giorni, senza fusi orari ---
function dayNumber(value) {
  const [y, m, d] = String(value).slice(0, 10).split('-').map(Number)
  return Date.UTC(y, m - 1, d) / 86400000
}
function dayString(number) {
  return new Date(number * 86400000).toISOString().slice(0, 10)
}

const today = computed(() => dayNumber(serverNow.value || new Date().toISOString()))

// Giorni toccati da un periodo, limitati al mese scelto
function periodDays(period, range) {
  let from = dayNumber(period.started_at)
  let to = period.ended_at ? dayNumber(period.ended_at) : today.value
  if (range) {
    from = Math.max(from, range.from)
    to = Math.min(to, range.to)
  }
  const days = []
  for (let d = from; d <= to; d++) days.push(d)
  return days
}

const monthRange = computed(() => {
  if (!month.value) return null
  const [y, m] = month.value.split('-').map(Number)
  return { from: Date.UTC(y, m - 1, 1) / 86400000, to: Date.UTC(y, m, 0) / 86400000 }
})

// Mesi dal primo inizio registrato a oggi, il più recente per primo
const months = computed(() => {
  const starts = rows.value.flatMap(r => r.periods.map(p => p.started_at)).sort()
  if (!starts.length) return []
  const first = dayString(dayNumber(starts[0]))
  const last = dayString(today.value)
  let [y, m] = first.split('-').map(Number)
  const [ly, lm] = last.split('-').map(Number)
  const list = []
  while (y < ly || (y === ly && m <= lm)) {
    const value = `${y}-${String(m).padStart(2, '0')}`
    const label = new Date(Date.UTC(y, m - 1, 1)).toLocaleDateString('it-IT', { month: 'long', year: 'numeric', timeZone: 'UTC' })
    list.unshift({ value, label: label.charAt(0).toUpperCase() + label.slice(1) })
    m++
    if (m > 12) { m = 1; y++ }
  }
  return list
})

function companyName(id) {
  if (id == null) return 'Senza azienda'
  return companies.value[id] || `Azienda #${id}`
}

const companyOptions = computed(() => {
  const ids = new Set(rows.value.flatMap(r => r.periods.map(p => p.company_id)))
  const options = [...ids].filter(id => id != null)
    .map(id => ({ value: String(id), label: companyName(id) }))
    .sort((a, b) => a.label.localeCompare(b.label, 'it'))
  if (ids.has(null)) options.push({ value: NO_COMPANY, label: 'Senza azienda' })
  return options
})

// Una riga per pagina con i periodi che passano i filtri; online prima, poi per titolo
const groups = computed(() => {
  const range = monthRange.value
  const text = search.value.trim().toLowerCase()
  const list = []
  for (const row of rows.value) {
    if (status.value === 'online' && row.page.status !== 'online') continue
    if (status.value === 'offline' && row.page.status === 'online') continue
    if (text && !`${row.page.title || ''} ${row.page.slug || ''} #${row.page.id}`.toLowerCase().includes(text)) continue

    const periods = []
    const allDays = new Set()
    for (const period of row.periods) {
      if (company.value === NO_COMPANY ? period.company_id != null : company.value && String(period.company_id) !== company.value) continue
      const days = periodDays(period, range)
      if (range && !days.length) continue
      days.forEach(d => allDays.add(d))
      periods.push({ ...period, days: days.length })
    }
    if (!periods.length) continue
    list.push({ page: row.page, periods, days: allDays.size })
  }
  return list.sort((a, b) =>
    (a.page.status === 'online' ? 0 : 1) - (b.page.status === 'online' ? 0 : 1)
    || (a.page.title || '').localeCompare(b.page.title || '', 'it')
    || a.page.id - b.page.id)
})

const onlineCount = computed(() => groups.value.filter(g => g.page.status === 'online').length)
const totalDays = computed(() => groups.value.reduce((sum, g) => sum + g.days, 0))
const estimatedCount = computed(() => groups.value.filter(g => g.periods.some(p => SOURCES[p.start_source])).length)

function formatDateTime(value) {
  if (!value) return ''
  const [date, time = ''] = String(value).split(' ')
  const [y, m, d] = date.split('-')
  return `${d}/${m}/${y} ${time.slice(0, 5)}`.trim()
}

// Valori che Excel interpreterebbe come formule vengono resi testo
function csvCell(value) {
  let text = value == null ? '' : String(value)
  if (/^[=+\-@\t\r]/.test(text)) text = `'${text}`
  return `"${text.replace(/"/g, '""')}"`
}

// Una riga per periodo, con i filtri attuali; separatore ";" e BOM per Excel in italiano
function exportCsv() {
  const headers = [
    'ID pagina', 'Pagina', 'Slug', 'Stato pagina', 'Azienda', 'Online dal', 'Al',
    month.value ? 'Giorni nel mese' : 'Giorni', 'Origine inizio', 'Motivo fine'
  ]
  const lines = []
  for (const group of groups.value) {
    for (const period of group.periods) {
      lines.push([
        group.page.id,
        group.page.title || 'Pagina eliminata',
        group.page.slug || '',
        STATUS[group.page.status].label,
        companyName(period.company_id),
        period.started_at,
        period.ended_at || 'in corso',
        period.days,
        SOURCES[period.start_source]?.label || 'registrato',
        period.end_reason ? (END_REASONS[period.end_reason] || period.end_reason) : ''
      ])
    }
  }

  const csv = [headers, ...lines].map(cells => cells.map(csvCell).join(';')).join('\r\n')
  const url = URL.createObjectURL(new Blob(['﻿' + csv], { type: 'text/csv;charset=utf-8;' }))
  const link = document.createElement('a')
  link.href = url
  link.download = `pubblicazioni_${month.value || 'storico'}_${new Date().toISOString().split('T')[0]}.csv`
  document.body.appendChild(link)
  link.click()
  document.body.removeChild(link)
  URL.revokeObjectURL(url)
}
</script>
