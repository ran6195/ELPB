<template>
  <div class="min-h-screen bg-gray-50">
    <!-- Header -->
    <div class="bg-white border-b border-gray-200 shadow-sm">
      <div class="max-w-7xl mx-auto px-8 py-6">
        <div class="flex justify-between items-center">
          <div>
            <h1 class="text-2xl font-semibold text-gray-900 mb-1">Libreria Media</h1>
            <p class="text-gray-500 text-sm">Immagini e video {{ authStore.isAdmin ? 'di tutte le aziende' : 'della tua azienda' }}</p>
          </div>
          <div class="flex gap-2">
            <button
              v-if="filter !== 'archived'"
              @click="showUpload = !showUpload"
              class="bg-primary-600 hover:bg-primary-700 text-white px-6 py-2.5 rounded-lg font-medium transition-colors shadow-md flex items-center gap-2"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
              </svg>
              <span>Carica file</span>
            </button>
            <router-link
              to="/"
              class="bg-white hover:bg-gray-50 text-gray-700 px-6 py-2.5 rounded-lg font-medium border border-gray-300 transition-colors"
            >
              Torna alle Pagine
            </router-link>
          </div>
        </div>
      </div>
    </div>

    <!-- Filtri -->
    <div class="bg-white border-b border-gray-200">
      <div class="max-w-7xl mx-auto px-8 py-3 flex items-center gap-3 flex-wrap">
        <div class="relative">
          <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0" />
          </svg>
          <input
            v-model="search"
            type="text"
            placeholder="Cerca per nome o testo alternativo..."
            class="pl-9 pr-3 py-1.5 text-sm border border-gray-200 rounded-lg bg-white text-gray-700 focus:outline-none focus:ring-2 focus:ring-primary-500 w-72"
          />
        </div>

        <div class="flex items-center bg-gray-100 rounded-lg p-1 gap-1">
          <button
            v-for="f in filters"
            :key="f.id"
            @click="filter = f.id"
            :class="filter === f.id ? 'bg-white shadow text-gray-900' : 'text-gray-500 hover:text-gray-700'"
            class="px-3 py-1.5 rounded-md text-sm font-medium transition-all"
          >
            {{ f.label }}
          </button>
        </div>

        <select
          v-if="authStore.isAdmin"
          v-model="companyId"
          class="text-sm border border-gray-200 rounded-lg px-3 py-2 bg-white text-gray-700 focus:outline-none focus:ring-2 focus:ring-primary-500"
        >
          <option value="">Tutte le aziende</option>
          <option v-for="c in companies" :key="c.id" :value="c.id">{{ c.name }}</option>
        </select>

        <span class="ml-auto text-sm text-gray-500">{{ total }} file</span>
      </div>
    </div>

    <div class="max-w-7xl mx-auto px-8 py-6">
      <!-- Upload -->
      <div v-if="showUpload && filter !== 'archived'" class="mb-6">
        <MediaDropzone :type="filter === 'image' || filter === 'video' ? filter : null" @uploaded="onUploaded" />
      </div>

      <div v-if="filter === 'archived'" class="mb-4 text-sm text-gray-600 bg-amber-50 border border-amber-200 rounded-lg px-4 py-3">
        I file archiviati non compaiono nella scelta dall'editor ma restano raggiungibili dalle pagine che li usano. Eliminandoli definitivamente il file viene cancellato dal server.
      </div>

      <!-- Loading -->
      <div v-if="loading && !items.length" class="flex justify-center py-16">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-primary-600"></div>
      </div>

      <p v-else-if="error" class="text-center text-red-600 py-16">{{ error }}</p>

      <!-- Vuoto -->
      <div v-else-if="!items.length" class="text-center py-20">
        <svg class="w-14 h-14 mx-auto text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
        </svg>
        <p class="text-gray-500">
          {{ search ? 'Nessun file corrisponde alla ricerca' : filter === 'archived' ? 'Nessun file archiviato' : 'Nessun file nella libreria' }}
        </p>
        <button
          v-if="!search && filter !== 'archived'"
          @click="showUpload = true"
          class="mt-3 text-sm font-medium text-primary-600 hover:text-primary-700"
        >
          Carica il primo file
        </button>
      </div>

      <!-- Griglia -->
      <template v-else>
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4">
          <button
            v-for="m in items"
            :key="m.id"
            type="button"
            @click="openDetail(m)"
            :class="detail?.id === m.id ? 'ring-2 ring-primary-500' : 'hover:shadow-md'"
            class="text-left bg-white rounded-lg overflow-hidden border border-gray-200 transition-shadow"
          >
            <div class="aspect-square">
              <MediaThumb :media="m" />
            </div>
            <div class="px-2.5 py-2">
              <p class="text-xs font-medium text-gray-800 truncate" :title="m.original_name">{{ m.original_name }}</p>
              <p class="text-[11px] text-gray-500 mt-0.5">
                <span v-if="m.width">{{ m.width }}×{{ m.height }} · </span>{{ formatBytes(m.size) }}
              </p>
            </div>
          </button>
        </div>

        <div v-if="page < lastPage" class="text-center mt-6">
          <button
            @click="load(page + 1)"
            :disabled="loading"
            class="text-sm px-5 py-2 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 disabled:opacity-50"
          >
            {{ loading ? 'Caricamento...' : 'Carica altri' }}
          </button>
        </div>
      </template>
    </div>

    <!-- Pannello dettaglio -->
    <Teleport to="body">
      <div v-if="detail" class="fixed inset-0 z-[1000] flex justify-end">
        <div class="absolute inset-0 bg-black/30" @click="closeDetail"></div>
        <aside class="relative w-full max-w-md bg-white shadow-2xl h-full overflow-y-auto">
          <div class="flex items-center justify-between px-5 py-4 border-b border-gray-200 sticky top-0 bg-white z-10">
            <h3 class="font-semibold text-gray-900">Dettagli file</h3>
            <button @click="closeDetail" class="text-gray-400 hover:text-gray-600" title="Chiudi">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
            </button>
          </div>

          <div class="p-5 space-y-5">
            <!-- Anteprima -->
            <div class="bg-gray-100 rounded-lg overflow-hidden">
              <video
                v-if="detail.type === 'video'"
                :src="detail.url"
                :poster="detail.thumbnail_url || undefined"
                controls
                preload="metadata"
                class="w-full max-h-72 bg-black"
              ></video>
              <img v-else :src="detail.url" :alt="detail.alt_text || detail.original_name" class="w-full max-h-72 object-contain" />
            </div>

            <!-- Info -->
            <dl class="grid grid-cols-2 gap-x-4 gap-y-2 text-sm">
              <dt class="text-gray-500">Tipo</dt>
              <dd class="text-gray-900">{{ detail.mime_type }}</dd>
              <template v-if="detail.width">
                <dt class="text-gray-500">Dimensioni</dt>
                <dd class="text-gray-900">{{ detail.width }} × {{ detail.height }} px</dd>
              </template>
              <template v-if="detail.duration">
                <dt class="text-gray-500">Durata</dt>
                <dd class="text-gray-900">{{ formatDuration(detail.duration) }}</dd>
              </template>
              <dt class="text-gray-500">Peso</dt>
              <dd class="text-gray-900">{{ formatBytes(detail.size) }}</dd>
              <dt class="text-gray-500">Caricato</dt>
              <dd class="text-gray-900">{{ formatDate(detail.created_at) }}</dd>
              <template v-if="detail.user">
                <dt class="text-gray-500">Da</dt>
                <dd class="text-gray-900">{{ detail.user.name }}</dd>
              </template>
            </dl>

            <!-- URL -->
            <div>
              <label class="block text-xs font-medium text-gray-700 mb-1.5">URL</label>
              <div class="flex gap-2">
                <input :value="detail.url" readonly class="flex-1 min-w-0 px-3 py-2 text-xs border border-gray-200 rounded-lg bg-gray-50 text-gray-600" />
                <button @click="copyUrl" class="px-3 py-2 text-xs font-medium border border-gray-300 rounded-lg hover:bg-gray-50 shrink-0">
                  {{ copied ? 'Copiato!' : 'Copia' }}
                </button>
              </div>
            </div>

            <!-- Modifica -->
            <div v-if="!isArchived" class="space-y-3">
              <div>
                <label class="block text-xs font-medium text-gray-700 mb-1.5">Nome</label>
                <input v-model="form.original_name" type="text" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-200 focus:border-primary-500" />
              </div>
              <div v-if="detail.type === 'image'">
                <label class="block text-xs font-medium text-gray-700 mb-1.5">Testo alternativo (alt)</label>
                <input v-model="form.alt_text" type="text" placeholder="Descrivi l'immagine per accessibilità e SEO" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-200 focus:border-primary-500" />
              </div>
              <div class="flex items-center gap-3">
                <button
                  @click="save"
                  :disabled="saving || !isDirty"
                  class="px-4 py-2 text-sm text-white bg-primary-600 rounded-lg hover:bg-primary-700 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                  {{ saving ? 'Salvataggio...' : 'Salva' }}
                </button>
                <span v-if="saveMessage" class="text-xs" :class="saveError ? 'text-red-600' : 'text-green-600'">{{ saveMessage }}</span>
              </div>
            </div>

            <!-- Utilizzo -->
            <div>
              <h4 class="text-xs font-medium text-gray-700 mb-2">Usato in</h4>
              <p v-if="usageLoading" class="text-xs text-gray-500">Verifica in corso...</p>
              <p v-else-if="!usage.length" class="text-xs text-gray-500">Non usato in nessuna pagina</p>
              <ul v-else class="space-y-1.5">
                <li v-for="u in usage" :key="u.page_id" class="flex items-center gap-2 text-sm">
                  <router-link
                    v-if="!u.archived"
                    :to="`/editor/${u.page_id}`"
                    class="text-primary-700 hover:underline truncate"
                  >{{ u.title }}</router-link>
                  <span v-else class="text-gray-600 truncate">{{ u.title }}</span>
                  <span v-if="u.is_published" class="text-[10px] px-1.5 py-0.5 rounded bg-green-100 text-green-700 shrink-0">Pubblicata</span>
                  <span v-if="u.archived" class="text-[10px] px-1.5 py-0.5 rounded bg-gray-100 text-gray-600 shrink-0">Archiviata</span>
                </li>
              </ul>
            </div>

            <!-- Azioni -->
            <div class="border-t border-gray-200 pt-4 space-y-3">
              <template v-if="!isArchived">
                <div v-if="confirmArchive" class="bg-amber-50 border border-amber-200 rounded-lg p-3 text-sm">
                  <p class="text-amber-800 mb-3">
                    Il file è usato in {{ usage.length }} {{ usage.length === 1 ? 'pagina' : 'pagine' }}. Archiviandolo resterà visibile nelle pagine ma non sarà più selezionabile dalla libreria.
                  </p>
                  <div class="flex gap-2">
                    <button @click="archive(true)" :disabled="busy" class="px-3 py-1.5 text-xs font-medium text-white bg-amber-600 rounded-md hover:bg-amber-700 disabled:opacity-50">Archivia comunque</button>
                    <button @click="confirmArchive = false" class="px-3 py-1.5 text-xs font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">Annulla</button>
                  </div>
                </div>
                <button
                  v-else
                  @click="archive(false)"
                  :disabled="busy"
                  class="w-full px-4 py-2 text-sm font-medium text-red-600 border border-red-200 rounded-lg hover:bg-red-50 disabled:opacity-50"
                >
                  Archivia
                </button>
              </template>
              <template v-else>
                <button
                  @click="restore"
                  :disabled="busy"
                  class="w-full px-4 py-2 text-sm font-medium text-primary-700 border border-primary-200 rounded-lg hover:bg-primary-50 disabled:opacity-50"
                >
                  Ripristina
                </button>
                <button
                  @click="forceDelete"
                  :disabled="busy"
                  class="w-full px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 disabled:opacity-50"
                >
                  Elimina definitivamente
                </button>
              </template>
              <p v-if="actionError" class="text-xs text-red-600">{{ actionError }}</p>
            </div>
          </div>
        </aside>
      </div>
    </Teleport>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { useAuthStore } from '../stores/authStore'
import { useMediaStore, mediaErrorMessage } from '../stores/mediaStore'
import MediaThumb from '../components/media/MediaThumb.vue'
import MediaDropzone from '../components/media/MediaDropzone.vue'
import { formatBytes, formatDuration } from '../utils/media'

const authStore = useAuthStore()
const mediaStore = useMediaStore()

const filters = [
  { id: 'all', label: 'Tutti' },
  { id: 'image', label: 'Immagini' },
  { id: 'video', label: 'Video' },
  { id: 'archived', label: 'Archiviati' }
]

const filter = ref('all')
const search = ref('')
const companyId = ref('')
const companies = ref([])
const showUpload = ref(false)

const items = ref([])
const total = ref(0)
const page = ref(1)
const lastPage = ref(1)
const loading = ref(false)
const error = ref('')

async function load(p = 1) {
  loading.value = true
  error.value = ''
  try {
    const res = await mediaStore.fetchMedia({
      type: ['image', 'video'].includes(filter.value) ? filter.value : null,
      archived: filter.value === 'archived' ? 1 : null,
      search: search.value.trim(),
      company_id: companyId.value,
      page: p,
      per_page: 48
    })
    items.value = p === 1 ? res.data : [...items.value, ...res.data]
    total.value = res.total
    page.value = res.page
    lastPage.value = res.last_page
  } catch (e) {
    error.value = mediaErrorMessage(e, 'Impossibile caricare la libreria')
  } finally {
    loading.value = false
  }
}

watch([filter, companyId], () => load(1))

let searchTimeout = null
watch(search, () => {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => load(1), 400)
})

function onUploaded(media) {
  // Aggiunge in testa solo se coerente col filtro attivo
  if (filter.value === 'all' || filter.value === media.type) {
    items.value = [media, ...items.value]
    total.value++
  }
}

// ===== Dettaglio =====
const detail = ref(null)
const form = ref({ original_name: '', alt_text: '' })
const saving = ref(false)
const saveMessage = ref('')
const saveError = ref(false)
const usage = ref([])
const usageLoading = ref(false)
const confirmArchive = ref(false)
const busy = ref(false)
const actionError = ref('')
const copied = ref(false)

const isArchived = computed(() => !!detail.value?.deleted_at)
const isDirty = computed(() => detail.value && (
  form.value.original_name !== detail.value.original_name ||
  (form.value.alt_text || '') !== (detail.value.alt_text || '')
))

async function openDetail(media) {
  detail.value = media
  form.value = { original_name: media.original_name, alt_text: media.alt_text || '' }
  saveMessage.value = ''
  actionError.value = ''
  confirmArchive.value = false
  copied.value = false
  usage.value = []
  usageLoading.value = true
  try {
    usage.value = await mediaStore.fetchUsage(media.id)
  } catch {
    usage.value = []
  } finally {
    usageLoading.value = false
  }
}

function closeDetail() {
  detail.value = null
}

function replaceItem(updated) {
  const i = items.value.findIndex((m) => m.id === updated.id)
  if (i !== -1) items.value[i] = { ...items.value[i], ...updated }
  detail.value = { ...detail.value, ...updated }
}

function removeItem(id) {
  items.value = items.value.filter((m) => m.id !== id)
  total.value = Math.max(0, total.value - 1)
  detail.value = null
}

async function save() {
  saving.value = true
  saveMessage.value = ''
  try {
    const updated = await mediaStore.update(detail.value.id, form.value)
    replaceItem(updated)
    saveError.value = false
    saveMessage.value = 'Salvato'
  } catch (e) {
    saveError.value = true
    saveMessage.value = mediaErrorMessage(e)
  } finally {
    saving.value = false
  }
}

async function archive(confirm) {
  busy.value = true
  actionError.value = ''
  try {
    const result = await mediaStore.archive(detail.value.id, confirm)
    if (result.inUse) {
      usage.value = result.usage
      confirmArchive.value = true
    } else {
      removeItem(detail.value.id)
    }
  } catch (e) {
    actionError.value = mediaErrorMessage(e)
  } finally {
    busy.value = false
  }
}

async function restore() {
  busy.value = true
  actionError.value = ''
  try {
    await mediaStore.restore(detail.value.id)
    removeItem(detail.value.id)
  } catch (e) {
    actionError.value = mediaErrorMessage(e)
  } finally {
    busy.value = false
  }
}

async function forceDelete() {
  const warning = usage.value.length
    ? `Il file è ancora usato in ${usage.value.length} pagina/e: lì comparirà un'immagine o un video mancante. Eliminarlo definitivamente?`
    : 'Eliminare definitivamente il file? L\'operazione non è reversibile.'
  if (!window.confirm(warning)) return

  busy.value = true
  actionError.value = ''
  try {
    await mediaStore.forceDelete(detail.value.id)
    removeItem(detail.value.id)
  } catch (e) {
    actionError.value = mediaErrorMessage(e)
  } finally {
    busy.value = false
  }
}

async function copyUrl() {
  try {
    await navigator.clipboard.writeText(detail.value.url)
    copied.value = true
    setTimeout(() => { copied.value = false }, 1500)
  } catch {
    actionError.value = 'Copia non riuscita: seleziona e copia l\'URL manualmente'
  }
}

function formatDate(value) {
  return value ? new Date(value).toLocaleString('it-IT', { dateStyle: 'medium', timeStyle: 'short' }) : '—'
}

onMounted(async () => {
  load(1)
  if (authStore.isAdmin) {
    const res = await authStore.fetchCompanies()
    companies.value = Array.isArray(res) ? res : []
  }
})
</script>
