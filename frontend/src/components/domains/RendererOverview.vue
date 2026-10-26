<template>
  <div class="bg-white rounded-lg shadow">
    <div class="px-6 py-4 border-b border-gray-200">
      <h2 class="text-lg font-medium text-gray-900">Renderer</h2>
      <p class="text-sm text-gray-500 mt-1">
        Siti che mostrano le landing page e versione del renderer installato. I renderer precedenti alla
        {{ currentVersion }} non comunicano il sito: si riconoscono dall'IP del server e vanno aggiornati.
      </p>
    </div>

    <div class="p-6 space-y-4">
      <div v-if="!loading && rows.length" class="flex flex-wrap gap-3 text-sm">
        <span class="px-3 py-1.5 rounded-lg bg-gray-50 text-gray-700">{{ sites.length }} {{ sites.length === 1 ? 'installazione' : 'installazioni' }}</span>
        <span class="px-3 py-1.5 rounded-lg bg-green-50 text-green-700">{{ upToDate }} aggiornati</span>
        <span v-if="outdated" class="px-3 py-1.5 rounded-lg bg-amber-50 text-amber-800">{{ outdated }} da aggiornare</span>
      </div>

      <div v-if="error" class="rounded-md bg-red-50 p-3 text-sm text-red-800">{{ error }}</div>

      <div v-if="loading" class="text-center py-8 text-gray-500">Caricamento...</div>
      <div v-else-if="!sites.length" class="text-center py-8 text-gray-500">
        Nessun accesso registrato: il registro si riempie man mano che i siti mostrano le pagine.
      </div>
      <div v-else class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sito</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Renderer</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pagine</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Richieste</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ultimo accesso</th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-for="row in sites" :key="`${row.host}|${row.renderer}|${row.version}|${row.ip}`" class="hover:bg-gray-50">
              <td class="px-4 py-3 text-sm">
                <span v-if="row.host" class="font-medium text-gray-900">{{ row.display_host }}</span>
                <span v-else class="text-gray-700">Server {{ row.ip }}</span>
              </td>
              <td class="px-4 py-3 text-sm whitespace-nowrap">
                <span class="text-gray-700">{{ rendererLabel(row) }}</span>
                <span
                  v-if="row.outdated"
                  class="ml-1 px-1.5 py-0.5 rounded text-xs font-medium bg-amber-100 text-amber-800"
                >da aggiornare</span>
              </td>
              <td class="px-4 py-3 text-sm text-gray-500">{{ row.pages_count }}</td>
              <td class="px-4 py-3 text-sm text-gray-500">{{ row.hits }}</td>
              <td class="px-4 py-3 text-sm text-gray-500 whitespace-nowrap" :title="row.last_seen_at">{{ timeAgo(row.last_seen_at) }}</td>
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
 * Panoramica dei siti che mostrano le pagine (registro renderer_hits):
 * serve a sapere quali siti hanno ancora un renderer da aggiornare.
 */
const domainStore = useDomainStore()

const loading = ref(true)
const error = ref('')
const rows = ref([])
const currentVersion = ref('2.1.0')

// L'anteprima dell'applicazione non è un sito del cliente
const sites = computed(() => rows.value.filter(r => !r.is_app))
const outdated = computed(() => sites.value.filter(r => r.outdated).length)
const upToDate = computed(() => sites.value.length - outdated.value)

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
