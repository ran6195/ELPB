<template>
  <div class="bg-white rounded-lg shadow">
    <div class="px-6 py-4 border-b border-gray-200">
      <h2 class="text-lg font-medium text-gray-900">Domini</h2>
      <p class="text-sm text-gray-500 mt-1">
        Siti dei clienti su cui sono pubblicate le landing page. Si associano alle pagine dalle impostazioni della pagina
        e servono a sapere da quale sito arrivano i contatti.
      </p>
    </div>

    <div class="p-6 space-y-6">
      <!-- Nuovo dominio -->
      <form @submit.prevent="addDomain" class="flex flex-wrap items-end gap-3">
        <div class="flex-1 min-w-[12rem]">
          <label class="block text-xs font-medium text-gray-700 mb-1">Dominio</label>
          <input
            v-model="form.hostname"
            type="text"
            placeholder="cliente.it"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
          />
        </div>
        <div class="flex-1 min-w-[12rem]">
          <label class="block text-xs font-medium text-gray-700 mb-1">Etichetta (facoltativa)</label>
          <input
            v-model="form.label"
            type="text"
            maxlength="100"
            placeholder="Sito principale"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
          />
        </div>
        <div v-if="companies.length" class="min-w-[12rem]">
          <label class="block text-xs font-medium text-gray-700 mb-1">Società</label>
          <select
            v-model="form.company_id"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
          >
            <option value="">Nessuna (admin)</option>
            <option v-for="c in companies" :key="c.id" :value="c.id">{{ c.name }}</option>
          </select>
        </div>
        <button
          type="submit"
          :disabled="saving || !form.hostname.trim()"
          class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors disabled:opacity-50"
        >
          Aggiungi dominio
        </button>
      </form>
      <p class="text-xs text-gray-500 -mt-3">
        "www." e indirizzi completi vengono semplificati: <code>https://www.cliente.it/lp/</code> diventa <code>cliente.it</code>.
        I sottodomini restano distinti.
      </p>

      <div v-if="error" class="rounded-md bg-red-50 p-3 text-sm text-red-800">{{ error }}</div>

      <!-- Elenco -->
      <div v-if="loading" class="text-center py-8 text-gray-500">Caricamento...</div>
      <div v-else-if="!domainStore.domains.length" class="text-center py-8 text-gray-500">
        Nessun dominio registrato
      </div>
      <div v-else class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Dominio</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Etichetta</th>
              <th v-if="companies.length" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Società</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pagine</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Lead</th>
              <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Azioni</th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-for="domain in domainStore.domains" :key="domain.id" class="hover:bg-gray-50 align-top">
              <template v-if="editing?.id === domain.id">
                <td class="px-4 py-3">
                  <input v-model="editing.hostname" type="text" class="w-full px-2 py-1 border border-gray-300 rounded text-sm" />
                </td>
                <td class="px-4 py-3">
                  <input v-model="editing.label" type="text" maxlength="100" class="w-full px-2 py-1 border border-gray-300 rounded text-sm" />
                </td>
              </template>
              <template v-else>
                <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ domain.display_hostname }}</td>
                <td class="px-4 py-3 text-sm text-gray-500">{{ domain.label || '—' }}</td>
              </template>
              <td v-if="companies.length" class="px-4 py-3 text-sm text-gray-500">{{ domain.company?.name || '—' }}</td>
              <td class="px-4 py-3 text-sm text-gray-500">
                <span v-if="!domain.pages?.length" class="text-gray-400">—</span>
                <span v-for="p in domain.pages" :key="p.id" class="block" :title="`#${p.id}-${p.slug}`">{{ p.title }}</span>
              </td>
              <td class="px-4 py-3 text-sm text-gray-500">{{ domain.leads_count }}</td>
              <td class="px-4 py-3 text-right text-sm font-medium space-x-2 whitespace-nowrap">
                <template v-if="editing?.id === domain.id">
                  <button @click="saveEdit" :disabled="saving" class="text-blue-600 hover:text-blue-900">Salva</button>
                  <button @click="editing = null" class="text-gray-500 hover:text-gray-700">Annulla</button>
                </template>
                <template v-else>
                  <button @click="startEdit(domain)" class="text-blue-600 hover:text-blue-900 inline-flex items-center" title="Modifica">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                  </button>
                  <button @click="removeDomain(domain)" class="text-red-600 hover:text-red-900 inline-flex items-center" title="Elimina">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                  </button>
                </template>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useDomainStore } from '../../stores/domainStore'

/**
 * Anagrafica domini per i pannelli admin e aziendale.
 * Con `companies` (solo admin) si sceglie la società proprietaria del nuovo dominio.
 */
defineProps({
  companies: { type: Array, default: () => [] }
})

const domainStore = useDomainStore()

const loading = ref(true)
const saving = ref(false)
const error = ref('')
const form = ref({ hostname: '', label: '', company_id: '' })
const editing = ref(null)

onMounted(async () => {
  try {
    await domainStore.fetchDomains(true)
  } catch (e) {
    error.value = 'Impossibile caricare i domini'
  } finally {
    loading.value = false
  }
})

async function run(action) {
  saving.value = true
  error.value = ''
  try {
    await action()
    return true
  } catch (e) {
    error.value = e.response?.data?.error || 'Errore durante il salvataggio'
    return false
  } finally {
    saving.value = false
  }
}

async function addDomain() {
  const data = { hostname: form.value.hostname.trim(), label: form.value.label }
  if (form.value.company_id) data.company_id = form.value.company_id
  if (await run(() => domainStore.createDomain(data))) {
    form.value = { hostname: '', label: '', company_id: form.value.company_id }
  }
}

function startEdit(domain) {
  editing.value = { id: domain.id, hostname: domain.display_hostname, label: domain.label || '' }
}

async function saveEdit() {
  const { id, hostname, label } = editing.value
  if (await run(() => domainStore.updateDomain(id, { hostname, label }))) {
    editing.value = null
  }
}

async function removeDomain(domain) {
  const pages = domain.pages?.length
    ? `\n\nVerrà tolto da ${domain.pages.length} ${domain.pages.length === 1 ? 'pagina' : 'pagine'}: ${domain.pages.map(p => p.title).join(', ')}.`
    : ''
  if (!confirm(`Eliminare il dominio ${domain.display_hostname}?${pages}\n\nI contatti già ricevuti mantengono il sito di provenienza.`)) {
    return
  }
  await run(() => domainStore.deleteDomain(domain.id))
}
</script>
