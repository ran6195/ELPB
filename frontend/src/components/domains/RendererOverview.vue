<template>
  <div class="bg-white rounded-lg shadow">
    <div class="px-6 py-4 border-b border-gray-200">
      <div class="flex items-center justify-between gap-3">
        <h2 class="text-lg font-medium text-gray-900">Renderer</h2>
        <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer select-none" title="Aggiorna la tabella ogni 4 secondi">
          <input v-model="live" type="checkbox" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500" />
          <span class="flex items-center gap-1.5">
            Diretta
            <span v-if="live" class="w-1.5 h-1.5 rounded-full bg-green-500 animate-pulse"></span>
          </span>
        </label>
      </div>
      <p class="text-sm text-gray-500 mt-1">
        Per ogni pagina: domini associati, sito che la mostra, renderer e server. I renderer precedenti alla
        {{ currentVersion }} non comunicano il sito: si riconoscono dall'IP del server e vanno aggiornati.
      </p>
    </div>

    <div class="p-6 space-y-4">
      <div v-if="!loading && sites.length" class="flex flex-wrap items-center gap-3 text-sm">
        <span class="px-3 py-1.5 rounded-lg bg-gray-50 text-gray-700">{{ groups.length }} {{ groups.length === 1 ? 'pagina' : 'pagine' }}</span>
        <span v-if="withoutDomain" class="px-3 py-1.5 rounded-lg bg-amber-50 text-amber-800">{{ withoutDomain }} senza dominio associato</span>
        <span v-if="outdated" class="px-3 py-1.5 rounded-lg bg-amber-50 text-amber-800">{{ outdated }} con renderer da aggiornare</span>
        <button
          v-if="botCount"
          type="button"
          class="px-3 py-1.5 rounded-lg text-sm border"
          :class="showBots ? 'border-gray-400 bg-gray-100 text-gray-800' : 'border-gray-200 text-gray-500 hover:text-gray-700'"
          :title="'Bot, assistenti IA e strumenti che scaricano le pagine dall\'API: non sono renderer'"
          @click="showBots = !showBots"
        >
          {{ showBots ? 'Nascondi' : 'Mostra' }} richieste automatiche ({{ botCount }})
        </button>
        <select v-model="filter" class="ml-auto px-3 py-1.5 border border-gray-300 rounded-lg text-sm">
          <option value="all">Tutte le pagine</option>
          <option value="no-domain">Senza dominio associato</option>
          <option value="outdated">Renderer da aggiornare</option>
        </select>
        <button
          type="button"
          class="bg-green-600 hover:bg-green-700 disabled:opacity-50 text-white px-4 py-1.5 rounded-lg text-sm font-medium whitespace-nowrap"
          :disabled="!filteredGroups.length"
          title="Esporta le righe mostrate, con il filtro attuale"
          @click="exportCsv"
        >
          Esporta CSV
        </button>
      </div>

      <div v-if="error" class="rounded-md bg-red-50 p-3 text-sm text-red-800">{{ error }}</div>

      <div v-if="loading" class="text-center py-8 text-gray-500">Caricamento...</div>
      <div v-else-if="!sites.length" class="text-center py-8 text-gray-500">
        Nessun accesso registrato: il registro si riempie man mano che i siti mostrano le pagine.
      </div>
      <div v-else-if="!filteredGroups.length" class="text-center py-8 text-gray-500">Nessuna pagina corrisponde al filtro.</div>
      <div v-else class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pagina</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Proprietario</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Domini associati</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sito che la mostra</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Renderer</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Server</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Richieste</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ultimo accesso</th>
            </tr>
          </thead>
          <tbody v-for="group in filteredGroups" :key="group.page.id" class="bg-white divide-y divide-gray-100 border-t border-gray-200">
            <tr
              v-for="(row, i) in group.rows"
              :key="rowKey(row)"
              :class="freshKeys.has(rowKey(row)) ? 'bg-green-50' : 'hover:bg-gray-50'"
              class="transition-colors duration-700"
            >
              <template v-if="i === 0">
                <td :rowspan="group.rows.length" class="px-4 py-3 text-sm align-top">
                  <router-link :to="`/editor/${group.page.id}`" class="font-medium text-primary-600 hover:underline">{{ group.page.title }}</router-link>
                  <span class="block font-mono text-xs text-gray-400">#{{ group.page.id }} · {{ group.page.slug }}</span>
                  <span class="block text-xs text-gray-500 mt-0.5">{{ group.hits }} {{ group.hits === 1 ? 'richiesta' : 'richieste' }} in totale</span>
                </td>
                <td :rowspan="group.rows.length" class="px-4 py-3 text-sm align-top">
                  <span class="block text-gray-700">{{ group.page.owner || '—' }}</span>
                  <span class="block text-xs text-gray-500">{{ group.page.company || 'Senza azienda' }}</span>
                </td>
                <td :rowspan="group.rows.length" class="px-4 py-3 text-sm align-top">
                  <span v-for="d in group.domains" :key="d" class="block text-gray-700">{{ d }}</span>
                  <span v-if="!group.domains.length" class="px-1.5 py-0.5 rounded text-xs font-medium bg-amber-100 text-amber-800">nessuno</span>
                </td>
              </template>
              <td class="px-4 py-3 text-sm align-top">
                <template v-if="row.host">
                  <span class="text-gray-900">{{ row.display_host }}</span>
                  <span
                    v-if="group.domains.length && !row.associated"
                    class="ml-1 px-1.5 py-0.5 rounded text-xs font-medium bg-amber-100 text-amber-800"
                    title="Il sito non è tra i domini associati alla pagina"
                  >non associato</span>
                </template>
                <template v-else>
                  <span class="text-gray-500 italic">non comunicato</span>
                  <span v-if="row.lead_hosts.length" class="block text-xs text-gray-500 mt-0.5">
                    Contatti da: <span class="text-gray-700">{{ row.lead_hosts.join(', ') }}</span>
                  </span>
                </template>
              </td>
              <td class="px-4 py-3 text-sm whitespace-nowrap align-top">
                <span class="text-gray-700">{{ rendererLabel(row) }}</span>
                <span
                  v-if="row.outdated"
                  class="ml-1 px-1.5 py-0.5 rounded text-xs font-medium bg-amber-100 text-amber-800"
                >da aggiornare</span>
              </td>
              <td class="px-4 py-3 text-sm text-gray-500 font-mono whitespace-nowrap align-top">{{ row.ip || '—' }}</td>
              <td class="px-4 py-3 text-sm text-gray-500 align-top">{{ row.hits }}</td>
              <td class="px-4 py-3 text-sm text-gray-500 whitespace-nowrap align-top" :title="row.last_seen_at">{{ timeAgo(row.last_seen_at, now) }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue'
import { useDomainStore } from '../../stores/domainStore'
import { rendererLabel, timeAgo } from '../../utils/renderers'

/**
 * Registro dei renderer per pagina (renderer_hits): quale sito mostra ogni pagina,
 * se la pagina ha domini associati e quali renderer vanno aggiornati.
 * Diretta ogni 4 secondi come il registro attività: arrivano solo le righe con accessi
 * nuovi, che restano evidenziate per qualche secondo.
 */
const domainStore = useDomainStore()

const loading = ref(true)
const error = ref('')
const rows = ref([])
const currentVersion = ref('2.1.0')
const filter = ref('all')
const showBots = ref(false)

const LIVE_INTERVAL = 4000
const FRESH_FOR = 15000
const LIVE_KEY = 'elpb.renderersLive'
const live = ref(readLivePreference())
const lastSeenAt = ref(null)
const freshKeys = ref(new Set())
const now = ref(Date.now())

// L'anteprima dell'applicazione non è un sito del cliente; bot e assistenti IA
// (richieste automatiche) si vedono solo a richiesta e non entrano nei conteggi
const botCount = computed(() => rows.value.filter(r => !r.is_app && r.is_bot).length)
const sites = computed(() => rows.value.filter(r => !r.is_app && (showBots.value || !r.is_bot)))

// Righe raggruppate per pagina: prima le pagine con più richieste (somma delle righe
// visibili), dentro ogni pagina i siti con più richieste; a parità titolo e sito
const groups = computed(() => {
  const byPage = new Map()
  for (const row of sites.value) {
    if (!byPage.has(row.page.id)) {
      byPage.set(row.page.id, { page: row.page, domains: row.domains, rows: [], hits: 0 })
    }
    const group = byPage.get(row.page.id)
    group.rows.push(row)
    group.hits += row.hits
  }
  const list = [...byPage.values()]
  for (const group of list) {
    group.rows.sort((a, b) => b.hits - a.hits || a.host.localeCompare(b.host))
  }
  return list.sort((a, b) => b.hits - a.hits || a.page.title.localeCompare(b.page.title, 'it'))
})

// Conteggi solo sui renderer veri, anche quando i bot sono visibili
const realGroups = computed(() => groups.value.filter(g => g.rows.some(r => !r.is_bot)))
const withoutDomain = computed(() => realGroups.value.filter(g => !g.domains.length).length)
const outdated = computed(() => realGroups.value.filter(g => g.rows.some(r => r.outdated)).length)

const filteredGroups = computed(() => {
  if (filter.value === 'no-domain') return groups.value.filter(g => !g.domains.length)
  if (filter.value === 'outdated') return groups.value.filter(g => g.rows.some(r => r.outdated))
  return groups.value
})

// Valori che Excel interpreterebbe come formule vengono resi testo
function csvCell(value) {
  let text = value == null ? '' : String(value)
  if (/^[=+\-@\t\r]/.test(text)) text = `'${text}`
  return `"${text.replace(/"/g, '""')}"`
}

// Una riga per pagina e sito, come la tabella (filtro e richieste automatiche compresi);
// separatore ";" come l'export del registro attività, per Excel in italiano
function exportCsv() {
  const headers = [
    'ID pagina', 'Pagina', 'Slug', 'Proprietario', 'Azienda', 'Domini associati',
    'Sito che la mostra', 'Sito associato', 'Contatti da', 'Renderer', 'Da aggiornare',
    'Server', 'Richieste', 'Ultimo accesso'
  ]
  const lines = []
  for (const group of filteredGroups.value) {
    for (const row of group.rows) {
      lines.push([
        group.page.id,
        group.page.title,
        group.page.slug,
        group.page.owner || '',
        group.page.company || 'Senza azienda',
        group.domains.join(', '),
        row.host ? row.display_host : 'non comunicato',
        row.host && group.domains.length ? (row.associated ? 'sì' : 'no') : '',
        row.host ? '' : (row.lead_hosts || []).join(', '),
        rendererLabel(row),
        row.outdated ? 'sì' : 'no',
        row.ip || '',
        row.hits,
        row.last_seen_at || ''
      ])
    }
  }

  const csv = [headers, ...lines].map(cells => cells.map(csvCell).join(';')).join('\r\n')
  const url = URL.createObjectURL(new Blob(['﻿' + csv], { type: 'text/csv;charset=utf-8;' }))
  const link = document.createElement('a')
  link.href = url
  link.download = `renderer_${new Date().toISOString().split('T')[0]}.csv`
  document.body.appendChild(link)
  link.click()
  document.body.removeChild(link)
  URL.revokeObjectURL(url)
}

// Chiave di una riga: pagina, sito, renderer e IP (come l'indice univoco di renderer_hits)
const rowKey = (row) => `${row.page.id}|${row.host}|${row.renderer}|${row.ip}`

// --- Diretta -------------------------------------------------------------
let liveTimer = null
let polling = false

async function pollUpdates() {
  now.value = Date.now()
  if (!live.value || loading.value || polling || document.hidden || !lastSeenAt.value) return
  polling = true
  try {
    const result = await domainStore.fetchRendererUpdates(lastSeenAt.value)
    lastSeenAt.value = result.last_seen_at || lastSeenAt.value
    // Righe dello stesso secondo dell'ultimo giro tornano uguali: si evidenziano solo i cambi
    const byKey = new Map(rows.value.map(row => [rowKey(row), row]))
    const changed = result.data.filter(row => {
      const known = byKey.get(rowKey(row))
      return !known || known.hits !== row.hits || known.last_seen_at !== row.last_seen_at
    })
    if (changed.length) {
      for (const row of changed) byKey.set(rowKey(row), row)
      rows.value = [...byKey.values()] // l'ordine lo decide groups
      markFresh(changed.map(rowKey))
    }
  } catch (e) {
    // La diretta riprova al giro successivo
  } finally {
    polling = false
  }
}

function markFresh(keys) {
  freshKeys.value = new Set([...freshKeys.value, ...keys])
  setTimeout(() => {
    const next = new Set(freshKeys.value)
    keys.forEach(key => next.delete(key))
    freshKeys.value = next
  }, FRESH_FOR)
}

function readLivePreference() {
  try {
    return localStorage.getItem(LIVE_KEY) !== '0'
  } catch (e) {
    return true
  }
}

watch(live, (value) => {
  try {
    localStorage.setItem(LIVE_KEY, value ? '1' : '0')
  } catch (e) {
    // Preferenza non salvata: resta per questa visita
  }
})

onMounted(async () => {
  try {
    const result = await domainStore.fetchRenderers()
    rows.value = result.data
    currentVersion.value = result.current_version || currentVersion.value
    // Senza accessi registrati la diretta parte da adesso (ora del database: italiana)
    lastSeenAt.value = result.last_seen_at || databaseNow()
  } catch (e) {
    error.value = 'Impossibile caricare il registro dei renderer'
  } finally {
    loading.value = false
  }
  liveTimer = setInterval(pollUpdates, LIVE_INTERVAL)
})

onUnmounted(() => {
  clearInterval(liveTimer)
})

/** Adesso nel formato del database, ora italiana: "2026-10-28 15:30:00" */
function databaseNow() {
  return new Date().toLocaleString('sv-SE', { timeZone: 'Europe/Rome' })
}
</script>
