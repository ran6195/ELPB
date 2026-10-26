<template>
  <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" @click.self="$emit('close')">
    <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4 p-6">
      <h3 class="text-xl font-semibold text-gray-900 mb-1">Assegna pagina</h3>
      <p class="text-sm text-gray-500 mb-5 truncate">{{ page.title }} <span class="font-mono text-xs">#{{ page.id }}-{{ page.slug }}</span></p>

      <div v-if="loading" class="text-sm text-gray-500 py-6 text-center">Caricamento utenti...</div>
      <div v-else class="space-y-4">
        <div>
          <label class="block text-xs font-medium text-gray-700 mb-2">Azienda</label>
          <select
            v-model="companyId"
            class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-primary-500"
          >
            <option :value="NO_COMPANY">Nessuna azienda</option>
            <option v-for="c in companies" :key="c.id" :value="c.id">{{ c.name }}</option>
          </select>
        </div>

        <div>
          <label class="block text-xs font-medium text-gray-700 mb-2">Proprietario</label>
          <select
            v-model="userId"
            :disabled="!companyUsers.length"
            class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-primary-500 disabled:bg-gray-50"
          >
            <option v-for="u in companyUsers" :key="u.id" :value="u.id">
              {{ u.name }} — {{ roleLabels[u.role] || u.role }}
            </option>
          </select>
          <p v-if="!companyUsers.length" class="text-xs text-amber-700 mt-1">
            Nessun utente in questa azienda: creane uno prima di assegnarle la pagina.
          </p>
          <p v-else class="text-xs text-gray-500 mt-1">
            La pagina sarà visibile al proprietario e al responsabile dell'azienda.
          </p>
        </div>

        <!-- Conseguenze del cambio di azienda -->
        <div v-if="companyChanges && (page.leads_count || page.domains?.length)" class="p-3 bg-amber-50 border border-amber-200 rounded-lg text-xs text-amber-900 space-y-1">
          <p v-if="page.leads_count">
            I {{ page.leads_count }} {{ page.leads_count === 1 ? 'contatto ricevuto passa' : 'contatti ricevuti passano' }}
            all'azienda {{ targetCompanyName }}.
          </p>
          <p v-if="page.domains?.length">
            {{ page.domains.length === 1 ? 'Il dominio' : 'I domini' }}
            {{ page.domains.map(d => d.display_hostname).join(', ') }}
            {{ page.domains.length === 1 ? 'appartiene' : 'appartengono' }} all'azienda attuale e
            {{ page.domains.length === 1 ? 'verrà tolto' : 'verranno tolti' }} dalla pagina.
          </p>
        </div>

        <p v-if="error" class="text-sm text-red-600">{{ error }}</p>
      </div>

      <div class="flex justify-end gap-3 mt-6">
        <button @click="$emit('close')" class="px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded-lg">Annulla</button>
        <button
          @click="assign"
          :disabled="saving || !userId || unchanged"
          class="bg-primary-600 hover:bg-primary-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
        >
          {{ saving ? 'Assegnazione...' : 'Assegna' }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { useAuthStore } from '../stores/authStore'
import { usePageStore } from '../stores/pageStore'

/**
 * Assegnazione di una pagina ad azienda e proprietario (solo admin).
 * L'azienda della pagina segue sempre quella del proprietario scelto.
 */
const props = defineProps({
  page: { type: Object, required: true }
})
const emit = defineEmits(['close', 'assigned'])

const authStore = useAuthStore()
const pageStore = usePageStore()

const NO_COMPANY = 'none'
const roleLabels = { admin: 'admin', company: 'responsabile', user: 'utente' }

const loading = ref(true)
const saving = ref(false)
const error = ref('')
const users = ref([])
const companies = ref([])
const companyId = ref(props.page.company_id ?? NO_COMPANY)
const userId = ref(props.page.user_id)

const companyUsers = computed(() => users.value
  .filter(u => (companyId.value === NO_COMPANY ? !u.company_id : u.company_id === companyId.value))
  // Prima il responsabile, poi gli utenti, in ordine di nome
  .sort((a, b) => (a.role === 'company' ? -1 : 0) - (b.role === 'company' ? -1 : 0) || a.name.localeCompare(b.name)))

const companyChanges = computed(() => (companyId.value === NO_COMPANY ? null : companyId.value) !== (props.page.company_id ?? null))
const unchanged = computed(() => !companyChanges.value && userId.value === props.page.user_id)
const targetCompanyName = computed(() => companies.value.find(c => c.id === companyId.value)?.name || 'nessuna (admin)')

// Cambiando azienda si propone il suo responsabile (o il primo utente)
watch(companyId, () => {
  if (!companyUsers.value.some(u => u.id === userId.value)) {
    userId.value = companyUsers.value[0]?.id ?? null
  }
})

onMounted(async () => {
  try {
    const [usersData, companiesData] = await Promise.all([authStore.fetchUsers(), authStore.fetchCompanies()])
    users.value = Array.isArray(usersData) ? usersData : []
    companies.value = (Array.isArray(companiesData) ? companiesData : [])
      .map(({ id, name }) => ({ id, name }))
      .sort((a, b) => a.name.localeCompare(b.name))
  } catch (e) {
    error.value = 'Impossibile caricare utenti e aziende'
  } finally {
    loading.value = false
  }
})

async function assign() {
  saving.value = true
  error.value = ''
  try {
    const result = await pageStore.reassignPage(props.page.id, userId.value)
    emit('assigned', result)
  } catch (e) {
    error.value = e.response?.data?.error || 'Errore durante l\'assegnazione'
  } finally {
    saving.value = false
  }
}
</script>
