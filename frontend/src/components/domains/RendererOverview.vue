<template>
  <div class="bg-white rounded-lg shadow">
    <div class="px-6 py-4 border-b border-gray-200">
      <h2 class="text-lg font-medium text-gray-900">Renderer</h2>
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
        <select v-model="filter" class="ml-auto px-3 py-1.5 border border-gray-300 rounded-lg text-sm">
          <option value="all">Tutte le pagine</option>
          <option value="no-domain">Senza dominio associato</option>
          <option value="outdated">Renderer da aggiornare</option>
        </select>
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
            <tr v-for="(row, i) in group.rows" :key="`${row.host}|${row.renderer}|${row.ip}`" class="hover:bg-gray-50">
              <template v-if="i === 0">
                <td :rowspan="group.rows.length" class="px-4 py-3 text-sm align-top">
                  <router-link :to="`/editor/${group.page.id}`" class="font-medium text-primary-600 hover:underline">{{ group.page.title }}</router-link>
                  <span class="block font-mono text-xs text-gray-400">#{{ group.page.id }} · {{ group.page.slug }}</span>
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
              <td class="px-4 py-3 text-sm text-gray-500 whitespace-nowrap align-top" :title="row.last_seen_at">{{ timeAgo(row.last_seen_at) }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useDomainStore } from '../../stores/domainStore'
import { rendererLabel, timeAgo } from '../../utils/renderers'

/**
 * Registro dei renderer per pagina (renderer_hits): quale sito mostra ogni pagina,
 * se la pagina ha domini associati e quali renderer vanno aggiornati.
 */
const domainStore = useDomainStore()

const loading = ref(true)
const error = ref('')
const rows = ref([])
const currentVersion = ref('2.1.0')
const filter = ref('all')

// L'anteprima dell'applicazione non è un sito del cliente
const sites = computed(() => rows.value.filter(r => !r.is_app))

// Righe raggruppate per pagina, nell'ordine (per titolo) restituito dal backend
const groups = computed(() => {
  const byPage = new Map()
  for (const row of sites.value) {
    if (!byPage.has(row.page.id)) {
      byPage.set(row.page.id, { page: row.page, domains: row.domains, rows: [] })
    }
    byPage.get(row.page.id).rows.push(row)
  }
  return [...byPage.values()]
})

const withoutDomain = computed(() => groups.value.filter(g => !g.domains.length).length)
const outdated = computed(() => groups.value.filter(g => g.rows.some(r => r.outdated)).length)

const filteredGroups = computed(() => {
  if (filter.value === 'no-domain') return groups.value.filter(g => !g.domains.length)
  if (filter.value === 'outdated') return groups.value.filter(g => g.rows.some(r => r.outdated))
  return groups.value
})

onMounted(async () => {
  try {
    const result = await domainStore.fetchRenderers()
    rows.value = result.data
    currentVersion.value = result.current_version || currentVersion.value
  } catch (e) {
    error.value = 'Impossibile caricare il registro dei renderer'
  } finally {
    loading.value = false
  }
})
</script>
