<template>
  <div class="border-t border-gray-200 pt-6">
    <h4 class="text-xs font-semibold text-gray-500 uppercase mb-2 tracking-wide">
      Domini
    </h4>
    <p class="text-xs text-gray-500 mb-4">
      Siti del cliente su cui è pubblicata la pagina. Facoltativo: serve a sapere da quale sito arrivano i contatti.
    </p>

    <!-- Domini dell'azienda selezionabili -->
    <div v-if="loading" class="text-xs text-gray-400">Caricamento domini...</div>
    <div v-else-if="availableDomains.length" class="space-y-1.5 mb-4">
      <label
        v-for="domain in availableDomains"
        :key="domain.id"
        class="flex items-center gap-3 p-2.5 rounded-lg border cursor-pointer transition-colors"
        :class="selectedIds.includes(domain.id) ? 'border-primary-300 bg-primary-50' : 'border-gray-200 hover:bg-gray-50'"
      >
        <input
          type="checkbox"
          :checked="selectedIds.includes(domain.id)"
          :disabled="saving"
          @change="toggleDomain(domain.id)"
          class="h-4 w-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500"
        />
        <span class="min-w-0">
          <span class="block text-sm text-gray-900 truncate">{{ domain.display_hostname }}</span>
          <span v-if="domain.label" class="block text-xs text-gray-500 truncate">{{ domain.label }}</span>
        </span>
      </label>
    </div>
    <p v-else class="text-xs text-gray-500 mb-4 p-3 bg-gray-50 rounded-lg">
      Nessun dominio registrato per questa azienda.
    </p>

    <!-- Nuovo dominio (solo admin e responsabile aziendale) -->
    <form v-if="canManage" @submit.prevent="addDomain" class="flex gap-2">
      <input
        v-model="newHostname"
        type="text"
        placeholder="cliente.it"
        :disabled="saving"
        class="flex-1 min-w-0 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-200 focus:border-primary-500 transition-all outline-none text-sm"
      />
      <button
        type="submit"
        :disabled="saving || !newHostname.trim()"
        class="px-3 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors text-sm font-medium disabled:opacity-50 disabled:cursor-not-allowed"
      >
        Aggiungi
      </button>
    </form>
    <p v-else class="text-xs text-gray-500">
      Per aggiungere un nuovo dominio chiedi al responsabile aziendale.
    </p>

    <p v-if="error" class="mt-2 text-xs text-red-600">{{ error }}</p>

    <!-- Siti da cui sono arrivati contatti ma non associati -->
    <div v-if="suggestions.length" class="mt-4 p-3 bg-amber-50 border border-amber-200 rounded-lg">
      <p class="text-xs font-medium text-amber-800 mb-2">Contatti ricevuti da siti non associati</p>
      <div v-for="s in suggestions" :key="s.hostname" class="flex items-center justify-between gap-2 py-1">
        <span class="text-xs text-amber-900 min-w-0 truncate">
          {{ s.hostname }} · {{ s.leads_count }} {{ s.leads_count === 1 ? 'contatto' : 'contatti' }}
        </span>
        <button
          v-if="s.domain_id || canManage"
          type="button"
          :disabled="saving"
          @click="associateSuggestion(s)"
          class="shrink-0 text-xs font-medium text-primary-700 hover:text-primary-800 disabled:opacity-50"
        >
          {{ s.domain_id ? 'Associa' : 'Aggiungi e associa' }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { usePageStore } from '../../stores/pageStore'
import { useAuthStore } from '../../stores/authStore'
import { useDomainStore, sameOwner } from '../../stores/domainStore'

/**
 * Associazione della pagina ai domini dell'azienda. Ogni modifica viene salvata subito.
 */
const props = defineProps({
  page: { type: Object, required: true }
})

const pageStore = usePageStore()
const authStore = useAuthStore()
const domainStore = useDomainStore()

const loading = ref(true)
const saving = ref(false)
const error = ref('')
const newHostname = ref('')
const suggestions = ref([])

const canManage = computed(() => authStore.isAdmin || authStore.isCompany)

// Lo store è la fonte di verità (aggiornato dopo ogni salvataggio)
const pageDomains = computed(() => pageStore.currentPage?.id === props.page.id
  ? pageStore.currentPage.domains || []
  : props.page.domains || [])

const selectedIds = computed(() => pageDomains.value.map(d => d.id))

const availableDomains = computed(() => domainStore.domains.filter(d => sameOwner(d, props.page)))

onMounted(async () => {
  try {
    await domainStore.fetchDomains(true)
    await loadSuggestions()
  } catch (e) {
    error.value = 'Impossibile caricare i domini'
  } finally {
    loading.value = false
  }
})

watch(() => props.page.id, loadSuggestions)

async function loadSuggestions() {
  try {
    suggestions.value = await domainStore.fetchSuggestions(props.page.id)
  } catch (e) {
    suggestions.value = []
  }
}

async function run(action) {
  saving.value = true
  error.value = ''
  try {
    await action()
    await loadSuggestions()
  } catch (e) {
    error.value = e.response?.data?.error || 'Errore durante il salvataggio'
  } finally {
    saving.value = false
  }
}

function toggleDomain(id) {
  const ids = selectedIds.value.includes(id)
    ? selectedIds.value.filter(x => x !== id)
    : [...selectedIds.value, id]
  return run(() => domainStore.setPageDomains(props.page.id, ids))
}

function addDomain() {
  return run(async () => {
    await domainStore.createDomain({ hostname: newHostname.value.trim(), page_id: props.page.id })
    newHostname.value = ''
  })
}

function associateSuggestion(s) {
  if (s.domain_id) {
    return run(() => domainStore.setPageDomains(props.page.id, [...selectedIds.value, s.domain_id]))
  }
  return run(() => domainStore.createDomain({ hostname: s.hostname, page_id: props.page.id }))
}
</script>
