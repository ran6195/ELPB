<template>
  <div class="bg-white rounded-lg shadow">
    <div class="px-6 py-4 border-b border-gray-200 flex flex-wrap items-start justify-between gap-4">
      <div>
        <h2 class="text-lg font-medium text-gray-900">Registro attività</h2>
        <p class="text-sm text-gray-500 mt-1">
          Chi ha fatto che cosa, quando, da dove e con quale esito. Le voci sono conservate per 24 mesi.
        </p>
      </div>
      <div class="flex items-center gap-3">
        <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer select-none" title="Aggiorna l'elenco ogni 4 secondi">
          <input v-model="live" type="checkbox" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500" />
          <span class="flex items-center gap-1.5">
            Diretta
            <span v-if="live && page === 1" class="w-1.5 h-1.5 rounded-full bg-green-500 animate-pulse"></span>
          </span>
        </label>
        <button
          type="button"
          class="px-3 py-2 text-sm font-medium rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50 disabled:opacity-50"
          :disabled="exporting"
          @click="exportCsv"
        >
          {{ exporting ? 'Esportazione...' : 'Esporta CSV' }}
        </button>
      </div>
    </div>

    <!-- FILTRI -->
    <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/60 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-8 gap-3 text-sm">
      <input
        v-model="text.utente"
        type="search"
        placeholder="Utente, email o record"
        class="xl:col-span-2 px-3 py-2 border border-gray-300 rounded-lg"
      />
      <select v-model="filters.azienda" class="px-3 py-2 border border-gray-300 rounded-lg">
        <option value="">Tutte le aziende</option>
        <option v-for="c in companies" :key="c.id" :value="c.id">{{ c.name }}</option>
      </select>
      <select v-model="filters.categoria" class="px-3 py-2 border border-gray-300 rounded-lg">
        <option value="">Tutte le categorie</option>
        <option v-for="(label, code) in labels.categories" :key="code" :value="code">{{ label }}</option>
      </select>
      <select v-model="filters.esito" class="px-3 py-2 border border-gray-300 rounded-lg">
        <option value="">Tutti gli esiti</option>
        <option v-for="(label, code) in labels.outcomes" :key="code" :value="code">{{ label }}</option>
      </select>
      <label class="flex items-center gap-2">
        <span class="text-gray-500 shrink-0">Dal</span>
        <input v-model="filters.dal" type="date" class="w-full px-2 py-2 border border-gray-300 rounded-lg" />
      </label>
      <label class="flex items-center gap-2">
        <span class="text-gray-500 shrink-0">Al</span>
        <input v-model="filters.al" type="date" class="w-full px-2 py-2 border border-gray-300 rounded-lg" />
      </label>
      <input v-model="text.ip" type="search" placeholder="IP" class="px-3 py-2 border border-gray-300 rounded-lg font-mono" />

      <div class="sm:col-span-2 lg:col-span-4 xl:col-span-8 flex flex-wrap items-center gap-3">
        <label class="flex items-center gap-2 text-gray-700 cursor-pointer select-none">
          <input v-model="filters.senza_consultazioni" type="checkbox" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500" />
          Nascondi consultazioni
        </label>
        <span
          v-if="filters.richiesta"
          class="inline-flex items-center gap-1.5 px-2 py-1 rounded-md bg-blue-50 text-blue-800 text-xs"
        >
          Richiesta <span class="font-mono">{{ filters.richiesta }}</span>
          <button type="button" class="text-blue-500 hover:text-blue-800" title="Togli il filtro" @click="filters.richiesta = ''">✕</button>
        </span>
        <button
          v-if="hasFilters"
          type="button"
          class="text-blue-600 hover:text-blue-800 hover:underline"
          @click="resetFilters"
        >
          Azzera filtri
        </button>
        <span class="ml-auto text-gray-500">
          {{ total }} {{ total === 1 ? 'voce' : 'voci' }}
        </span>
      </div>
    </div>

    <div v-if="error" class="m-6 rounded-md bg-red-50 p-3 text-sm text-red-800">{{ error }}</div>

    <!-- VOCI -->
    <div v-if="loading && !rows.length" class="text-center py-12 text-gray-500">Caricamento...</div>
    <div v-else-if="!rows.length" class="text-center py-12 text-gray-500">
      {{ hasFilters ? 'Nessuna voce corrisponde ai filtri.' : 'Il registro è ancora vuoto.' }}
    </div>
    <div v-else :class="['divide-y divide-gray-100', loading ? 'opacity-60' : '']">
      <template v-for="(row, index) in rows" :key="row.id">
        <div v-if="index === 0 || dayOf(row) !== dayOf(rows[index - 1])" class="px-6 py-2 bg-gray-50 text-xs font-semibold text-gray-500 uppercase tracking-wider">
          {{ formatDay(row.created_at) }}
        </div>

        <details :class="['group', freshIds.has(row.id) ? 'bg-green-50/70' : '']">
          <summary class="px-6 py-3 cursor-pointer list-none hover:bg-gray-50 flex flex-wrap lg:flex-nowrap items-start gap-x-4 gap-y-1 text-sm">
            <span class="w-20 shrink-0 font-mono text-xs text-gray-500 pt-0.5" :title="row.created_at">{{ formatTime(row.created_at) }}</span>
            <span :class="['w-28 shrink-0 text-center px-2 py-0.5 rounded text-xs font-medium', categoryClass(row.category)]">{{ row.category_label }}</span>

            <span class="min-w-0 flex-1">
              <span class="font-medium text-gray-900">{{ row.description }}</span>
              <span v-if="subjectText(row)" class="text-gray-600"> · {{ subjectText(row) }}</span>
              <span v-if="row.properties?.saves > 1" class="ml-1 text-xs text-gray-500">({{ row.properties.saves }} salvataggi)</span>
              <span v-if="row.properties?.staff_view" class="ml-1 px-1.5 py-0.5 rounded text-xs bg-purple-50 text-purple-700" title="Un amministratore ha lavorato su un'altra azienda">staff</span>
              <span v-if="row.company || row.page" class="block text-xs text-gray-500 truncate">
                <template v-if="row.company">{{ row.company.name }}</template>
                <template v-if="row.company && row.page"> · </template>
                <template v-if="row.page">{{ row.page.title }}<span v-if="row.page.archived"> (archiviata)</span></template>
              </span>
            </span>

            <span class="w-48 shrink-0 text-gray-700 truncate">
              <template v-if="row.user">
                {{ row.user.name || row.user.email }}
                <span class="block text-xs text-gray-500 truncate">{{ row.user.role_label }}</span>
              </template>
              <span v-else class="text-gray-400">{{ row.channel === 'console' ? 'Script' : 'Sistema / anonimo' }}</span>
            </span>

            <span class="w-28 shrink-0 text-right">
              <span
                v-if="row.outcome && row.outcome !== 'ok'"
                :class="['px-2 py-0.5 rounded text-xs font-medium', outcomeClass(row.outcome)]"
              >{{ row.outcome_label }}</span>
              <span v-if="row.duration_ms !== null" class="block text-xs text-gray-400">{{ row.duration_ms }} ms</span>
            </span>
          </summary>

          <!-- DETTAGLIO -->
          <div class="px-6 pb-4 pt-1 lg:pl-[15rem] space-y-3 text-sm">
            <!-- Campi modificati -->
            <div v-if="changedFields(row).length" class="overflow-x-auto">
              <table class="min-w-full text-xs border border-gray-200 rounded">
                <thead class="bg-gray-50 text-gray-500">
                  <tr>
                    <th class="px-3 py-1.5 text-left font-medium">Campo</th>
                    <th v-if="row.properties.old" class="px-3 py-1.5 text-left font-medium">Prima</th>
                    <th v-if="row.properties.new" class="px-3 py-1.5 text-left font-medium">Dopo</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                  <tr v-for="field in changedFields(row)" :key="field" class="align-top">
                    <td class="px-3 py-1.5 font-mono text-gray-700 whitespace-nowrap">{{ field }}</td>
                    <td v-if="row.properties.old" class="px-3 py-1.5 text-red-700"><pre class="value">{{ formatValue(row.properties.old[field]) }}</pre></td>
                    <td v-if="row.properties.new" class="px-3 py-1.5 text-green-700"><pre class="value">{{ formatValue(row.properties.new[field]) }}</pre></td>
                  </tr>
                </tbody>
              </table>
            </div>

            <!-- Salvataggio dell'editor -->
            <p v-if="row.properties?.blocks" class="text-gray-700">
              Blocchi: {{ row.properties.blocks.count }}
              <template v-if="row.properties.blocks.added">· aggiunti {{ countsText(row.properties.blocks.added) }}</template>
              <template v-if="row.properties.blocks.removed">· tolti {{ countsText(row.properties.blocks.removed) }}</template>
              <template v-if="row.properties.last_saved_at">· ultimo salvataggio alle {{ formatTime(row.properties.last_saved_at) }}</template>
            </p>

            <!-- Domini della pagina -->
            <p v-if="row.properties?.attached || row.properties?.detached" class="text-gray-700">
              <span v-if="row.properties.attached" class="text-green-700">+ {{ row.properties.attached.join(', ') }}</span>
              <span v-if="row.properties.attached && row.properties.detached"> · </span>
              <span v-if="row.properties.detached" class="text-red-700">− {{ row.properties.detached.join(', ') }}</span>
              <span v-if="row.properties.reason" class="text-gray-500"> ({{ labels.domain_reasons?.[row.properties.reason] || row.properties.reason }})</span>
            </p>

            <!-- Pagina assegnata -->
            <p v-if="row.properties?.from && row.properties?.to" class="text-gray-700">
              Da {{ ownerText(row.properties.from) }} a {{ ownerText(row.properties.to) }}
            </p>

            <!-- Lead ricevuto -->
            <p v-if="row.properties?.notification" class="text-gray-700">
              <template v-if="row.properties.origin_host">Dal sito {{ row.properties.origin_host }} · </template>
              {{ labels.notifications?.[row.properties.notification] || row.properties.notification }}
            </p>

            <p v-if="row.properties?.error" class="text-red-700">Messaggio: {{ row.properties.error }}</p>

            <div v-if="row.properties?.input">
              <p class="text-xs font-medium text-gray-500 mb-1">Dati inviati</p>
              <pre class="value bg-gray-50 border border-gray-200 rounded p-2 text-xs text-gray-700">{{ formatJson(row.properties.input) }}</pre>
            </div>

            <div v-if="otherProperties(row)">
              <p class="text-xs font-medium text-gray-500 mb-1">Altri dettagli</p>
              <pre class="value bg-gray-50 border border-gray-200 rounded p-2 text-xs text-gray-700">{{ formatJson(otherProperties(row)) }}</pre>
            </div>

            <!-- Metadati -->
            <dl class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-1 text-xs">
              <div v-if="row.method" class="flex gap-2 min-w-0">
                <dt class="text-gray-500 shrink-0">Richiesta HTTP</dt>
                <dd class="font-mono text-gray-700 truncate" :title="row.url">{{ row.method }} {{ pathOf(row.url) }} → {{ row.status }}</dd>
              </div>
              <div class="flex gap-2">
                <dt class="text-gray-500 shrink-0">Canale</dt>
                <dd class="text-gray-700">{{ row.channel_label }}<template v-if="row.properties?.source"> ({{ row.properties.source }})</template></dd>
              </div>
              <div v-if="row.user?.email" class="flex gap-2 min-w-0">
                <dt class="text-gray-500 shrink-0">Email</dt>
                <dd class="text-gray-700 truncate">{{ row.user.email }}</dd>
              </div>
              <div v-if="row.ip" class="flex gap-2">
                <dt class="text-gray-500 shrink-0">IP</dt>
                <dd class="font-mono text-gray-700">{{ row.ip }}</dd>
              </div>
              <div v-if="row.user_agent" class="flex gap-2 min-w-0 md:col-span-2">
                <dt class="text-gray-500 shrink-0">Browser</dt>
                <dd class="text-gray-700 truncate" :title="row.user_agent">{{ row.user_agent }}</dd>
              </div>
              <div v-if="row.request_id" class="flex gap-2">
                <dt class="text-gray-500 shrink-0">ID richiesta</dt>
                <dd class="font-mono text-gray-700">{{ row.request_id }}</dd>
              </div>
            </dl>

            <div class="flex flex-wrap gap-3 text-xs">
              <button v-if="row.request_id" type="button" class="text-blue-600 hover:underline" @click="filterBy('richiesta', row.request_id)">Stessa richiesta</button>
              <button v-if="row.ip" type="button" class="text-blue-600 hover:underline" @click="filterBy('ip', row.ip)">Stesso IP</button>
              <button v-if="row.user?.email" type="button" class="text-blue-600 hover:underline" @click="filterBy('utente', row.user.email)">Stesso utente</button>
              <router-link v-if="row.page && !row.page.deleted && !row.page.archived" :to="`/editor/${row.page.id}`" class="text-blue-600 hover:underline">Apri la pagina</router-link>
            </div>
          </div>
        </details>
      </template>
    </div>

    <!-- PAGINAZIONE -->
    <div v-if="lastPage > 1" class="px-6 py-3 border-t border-gray-200 flex items-center justify-between text-sm">
      <button
        type="button"
        class="px-3 py-1.5 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50 disabled:opacity-40"
        :disabled="page <= 1 || loading"
        @click="goTo(page - 1)"
      >
        ← Più recenti
      </button>
      <span class="text-gray-500">Pagina {{ page }} di {{ lastPage }}</span>
      <button
        type="button"
        class="px-3 py-1.5 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50 disabled:opacity-40"
        :disabled="page >= lastPage || loading"
        @click="goTo(page + 1)"
      >
        Meno recenti →
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, watch, onMounted, onUnmounted } from 'vue'
import { useAuditStore } from '../../stores/auditStore'

/**
 * Registro attività (solo admin): filtri, righe espandibili con i campi modificati,
 * diretta ogni 4 secondi sulla prima pagina ed export CSV con gli stessi filtri.
 */
defineProps({
  companies: { type: Array, default: () => [] }
})

const auditStore = useAuditStore()

const LIVE_INTERVAL = 4000
const MAX_ROWS = 500
const LIVE_KEY = 'elpb.auditLive'

const emptyFilters = () => ({
  azienda: '', utente: '', categoria: '', esito: '', dal: '', al: '', richiesta: '', ip: '', senza_consultazioni: false
})
const filters = reactive(emptyFilters())
// Campi di testo: arrivano ai filtri dopo 400 ms senza digitare
const text = reactive({ utente: '', ip: '' })

const rows = ref([])
const labels = ref({})
const page = ref(1)
const lastPage = ref(1)
const total = ref(0)
const lastId = ref(0)
const loading = ref(false)
const exporting = ref(false)
const error = ref('')
const freshIds = ref(new Set())
const live = ref(readLivePreference())

const hasFilters = computed(() => Object.entries(filters).some(([, v]) => v !== '' && v !== false))

// Proprietà mostrate in sezioni dedicate: il resto va in "Altri dettagli"
const SHOWN_KEYS = ['old', 'new', 'blocks', 'saves', 'last_saved_at', 'attached', 'detached', 'reason', 'from', 'to',
  'notification', 'origin_host', 'error', 'input', 'staff_view', 'source', 'params']

async function load(targetPage = page.value) {
  loading.value = true
  error.value = ''
  try {
    const result = await auditStore.fetchLogs(filters, targetPage)
    rows.value = result.data
    labels.value = result.labels
    page.value = result.meta.page
    lastPage.value = result.meta.last_page
    total.value = result.meta.total
    lastId.value = result.meta.last_id
    freshIds.value = new Set()
  } catch (e) {
    error.value = 'Impossibile caricare il registro attività'
  } finally {
    loading.value = false
  }
}

function goTo(target) {
  load(target)
  window.scrollTo({ top: 0, behavior: 'smooth' })
}

// --- Diretta -------------------------------------------------------------
let liveTimer = null

async function pollNewer() {
  if (!live.value || page.value !== 1 || loading.value || document.hidden) return
  try {
    const result = await auditStore.fetchNewer(filters, lastId.value)
    if (result.truncated) {
      await load(1)
      return
    }
    lastId.value = result.last_id
    if (result.data.length) {
      const known = new Set(rows.value.map(r => r.id))
      const added = result.data.filter(r => !known.has(r.id))
      rows.value = [...added, ...rows.value].slice(0, MAX_ROWS)
      total.value += added.length
      lastPage.value = Math.max(1, Math.ceil(total.value / 50))
      freshIds.value = new Set([...freshIds.value, ...added.map(r => r.id)])
    }
  } catch (e) {
    // La diretta riprova al giro successivo
  }
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

// --- Filtri --------------------------------------------------------------
let textTimer = null
watch(text, () => {
  clearTimeout(textTimer)
  textTimer = setTimeout(() => {
    filters.utente = text.utente.trim()
    filters.ip = text.ip.trim()
  }, 400)
})

watch(filters, () => load(1))

function filterBy(key, value) {
  if (key in text) text[key] = value
  filters[key] = value
}

function resetFilters() {
  Object.assign(filters, emptyFilters())
  text.utente = ''
  text.ip = ''
}

async function exportCsv() {
  exporting.value = true
  try {
    await auditStore.exportCsv(filters)
  } catch (e) {
    error.value = 'Esportazione non riuscita'
  } finally {
    exporting.value = false
  }
}

// --- Formattazione -------------------------------------------------------
const CATEGORY_CLASSES = {
  view: 'bg-gray-100 text-gray-700',
  action: 'bg-blue-50 text-blue-700',
  change: 'bg-amber-50 text-amber-800',
  access: 'bg-indigo-50 text-indigo-700',
  publish: 'bg-green-50 text-green-700',
  system: 'bg-purple-50 text-purple-700'
}
const categoryClass = (code) => CATEGORY_CLASSES[code] || 'bg-gray-100 text-gray-700'
const outcomeClass = (code) => (code === 'invalid' || code === 'not_found' ? 'bg-amber-100 text-amber-800' : 'bg-red-100 text-red-800')

const dayOf = (row) => String(row.created_at).slice(0, 10)

function formatDay(value) {
  const date = new Date(String(value).slice(0, 10) + 'T00:00:00')
  return date.toLocaleDateString('it-IT', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' })
}

const formatTime = (value) => String(value).slice(11, 19)

function subjectText(row) {
  if (!row.subject) return ''
  // Il tipo di record è già nella descrizione ("Pagina modificata"): basta l'etichetta
  return row.subject.label || (row.subject.id ? `${row.subject.type_label} #${row.subject.id}` : '')
}

function changedFields(row) {
  const p = row.properties
  if (!p || (!p.old && !p.new)) return []
  return [...new Set([...Object.keys(p.old || {}), ...Object.keys(p.new || {})])]
}

function formatValue(value) {
  if (value === null || value === undefined) return '—'
  if (value === true) return 'sì'
  if (value === false) return 'no'
  if (typeof value === 'object') return JSON.stringify(value, null, 2)
  return String(value)
}

const formatJson = (value) => JSON.stringify(value, null, 2)

const countsText = (counts) => Object.entries(counts).map(([type, n]) => (n > 1 ? `${type} ×${n}` : type)).join(', ')

function ownerText(owner) {
  const name = owner.user || 'nessuno'
  return owner.company ? `${name} (${owner.company})` : name
}

function otherProperties(row) {
  if (!row.properties) return null
  const rest = Object.fromEntries(Object.entries(row.properties).filter(([key]) => !SHOWN_KEYS.includes(key)))
  return Object.keys(rest).length ? rest : null
}

function pathOf(url) {
  try {
    const parsed = new URL(url)
    return parsed.pathname + parsed.search
  } catch (e) {
    return url
  }
}

onMounted(() => {
  load(1)
  liveTimer = setInterval(pollNewer, LIVE_INTERVAL)
})

onUnmounted(() => {
  clearInterval(liveTimer)
  clearTimeout(textTimer)
})
</script>

<style scoped>
summary::-webkit-details-marker {
  display: none;
}
.value {
  white-space: pre-wrap;
  word-break: break-word;
  max-height: 12rem;
  overflow: auto;
  font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
  margin: 0;
}
</style>
