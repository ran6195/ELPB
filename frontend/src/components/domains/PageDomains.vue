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

    <!-- Dove viene mostrata (registro dei renderer) e visite -->
    <div v-if="renderers" class="mt-5">
      <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Dove viene mostrata</p>
      <p v-if="!visibleHits.length && !botHits.length" class="text-xs text-gray-500 p-3 bg-gray-50 rounded-lg">
        Nessun sito ha ancora scaricato la pagina da quando è attivo il registro.
      </p>
      <div v-else class="space-y-1.5">
        <div
          v-for="hit in visibleHits"
          :key="`${hit.host}|${hit.renderer}|${hit.ip}`"
          class="p-2.5 rounded-lg border text-xs"
          :class="hit.outdated ? 'border-amber-200 bg-amber-50' : 'border-gray-200'"
        >
          <div class="flex items-center justify-between gap-2">
            <span class="min-w-0 truncate text-sm text-gray-900" :title="hit.last_url || ''">
              {{ hit.host ? hit.display_host : 'Renderer non aggiornato' }}
            </span>
            <span v-if="hit.host && hit.is_app" class="shrink-0 px-1.5 py-0.5 rounded bg-gray-100 text-gray-600">anteprima</span>
            <span v-else-if="hit.associated" class="shrink-0 px-1.5 py-0.5 rounded bg-green-100 text-green-700">associato</span>
            <button
              v-else-if="hit.host && (domainFor(hit.host) || canManage)"
              type="button"
              :disabled="saving"
              @click="associateHost(hit.host)"
              class="shrink-0 font-medium text-primary-700 hover:text-primary-800 disabled:opacity-50"
            >
              {{ domainFor(hit.host) ? 'Associa' : 'Aggiungi e associa' }}
            </button>
          </div>
          <div class="mt-0.5 text-gray-500">
            {{ rendererLabel(hit) }}
            <span v-if="hit.outdated" class="text-amber-700 font-medium">· da aggiornare</span>
            <span v-if="hit.ip"> · server {{ hit.ip }}</span>
            · {{ timeAgo(hit.last_seen_at) }}
          </div>
          <div v-if="hit.last_url" class="mt-0.5 text-gray-400 truncate" :title="hit.last_url">{{ hit.last_url }}</div>
        </div>
        <button
          v-if="botHits.length"
          type="button"
          class="text-xs text-gray-500 hover:text-gray-700 hover:underline"
          title="Bot, assistenti IA e strumenti che scaricano la pagina dall'API: non sono renderer"
          @click="showBots = !showBots"
        >
          {{ showBots ? 'Nascondi' : 'Mostra' }} richieste automatiche ({{ botHits.length }})
        </button>
      </div>

      <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mt-4 mb-2">
        Visite ultimi {{ renderers.views_days }} giorni
      </p>
      <p v-if="!renderers.views.length" class="text-xs text-gray-500 p-3 bg-gray-50 rounded-lg">
        Nessuna visita registrata. Le visite si contano dai siti con il renderer 2.1 o successivo.
      </p>
      <div v-else class="space-y-1">
        <div v-for="v in renderers.views" :key="v.host" class="flex items-center justify-between gap-2 text-xs">
          <span class="min-w-0 truncate text-gray-700">{{ v.display_host || 'sito sconosciuto' }}</span>
          <span class="shrink-0 text-gray-500">{{ v.views }} {{ v.views === 1 ? 'visita' : 'visite' }} · {{ v.visitors }} {{ v.visitors === 1 ? 'visitatore' : 'visitatori' }}</span>
        </div>
        <p v-if="renderers.first_view_day" class="text-xs text-gray-400 pt-1">
          Dati raccolti dal {{ formatDay(renderers.first_view_day) }}, senza cookie.
        </p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { usePageStore } from '../../stores/pageStore'
import { useAuthStore } from '../../stores/authStore'
import { useDomainStore, sameOwner } from '../../stores/domainStore'
import { rendererLabel, timeAgo, formatDay } from '../../utils/renderers'

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
const renderers = ref(null)
const showBots = ref(false)
// Richieste automatiche (bot, assistenti IA): nascoste finché non si chiede di vederle
const botHits = computed(() => (renderers.value?.hits || []).filter(h => h.is_bot))
const visibleHits = computed(() => (renderers.value?.hits || []).filter(h => showBots.value || !h.is_bot))

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
    await Promise.all([loadSuggestions(), loadRenderers()])
  } catch (e) {
    error.value = 'Impossibile caricare i domini'
  } finally {
    loading.value = false
  }
})

watch(() => props.page.id, () => Promise.all([loadSuggestions(), loadRenderers()]))

async function loadSuggestions() {
  try {
    suggestions.value = await domainStore.fetchSuggestions(props.page.id)
  } catch (e) {
    suggestions.value = []
  }
}

async function loadRenderers() {
  try {
    renderers.value = await domainStore.fetchPageRenderers(props.page.id)
  } catch (e) {
    renderers.value = null
  }
}

/** Dominio in anagrafica con questo hostname e lo stesso proprietario della pagina */
function domainFor(hostname) {
  return availableDomains.value.find(d => d.hostname === hostname)
}

async function run(action) {
  saving.value = true
  error.value = ''
  try {
    await action()
    await Promise.all([loadSuggestions(), loadRenderers()])
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

function associateHost(hostname) {
  const domain = domainFor(hostname)
  return associateSuggestion({ hostname, domain_id: domain?.id || null })
}
</script>
