<template>
  <Teleport to="body">
    <div class="fixed inset-0 z-[1000] flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-black/50" @click="emit('close')"></div>

      <div class="relative bg-white rounded-xl shadow-2xl w-full max-w-4xl max-h-[90vh] flex flex-col">
        <!-- Header -->
        <div class="flex items-center justify-between px-5 py-4 border-b border-gray-200">
          <h3 class="text-lg font-semibold text-gray-900">
            {{ type === 'video' ? 'Scegli un video' : type === 'image' ? 'Scegli un\'immagine' : 'Scegli un file' }}
          </h3>
          <button @click="emit('close')" class="text-gray-400 hover:text-gray-600" title="Chiudi">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
          </button>
        </div>

        <!-- Tabs + ricerca -->
        <div class="flex items-center justify-between gap-3 px-5 pt-3 border-b border-gray-200">
          <div class="flex gap-1">
            <button
              v-for="t in tabs"
              :key="t.id"
              @click="tab = t.id"
              :class="tab === t.id ? 'border-primary-600 text-primary-700' : 'border-transparent text-gray-500 hover:text-gray-700'"
              class="px-3 py-2 text-sm font-medium border-b-2 -mb-px transition-colors"
            >
              {{ t.label }}
            </button>
          </div>
          <div v-if="tab === 'library'" class="relative pb-2">
            <svg class="absolute left-2.5 top-[9px] w-4 h-4 text-gray-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0" /></svg>
            <input
              v-model="search"
              type="text"
              placeholder="Cerca..."
              class="pl-8 pr-3 py-1.5 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 w-48"
            />
          </div>
        </div>

        <!-- Contenuto -->
        <div class="flex-1 overflow-y-auto p-5 min-h-[300px]">
          <!-- Libreria -->
          <template v-if="tab === 'library'">
            <div v-if="loading && !items.length" class="flex justify-center py-16">
              <div class="animate-spin rounded-full h-10 w-10 border-b-2 border-primary-600"></div>
            </div>
            <p v-else-if="error" class="text-sm text-red-600 text-center py-16">{{ error }}</p>
            <div v-else-if="!items.length" class="text-center py-16">
              <p class="text-gray-500 text-sm mb-3">{{ search ? 'Nessun risultato' : 'La libreria è vuota' }}</p>
              <button @click="tab = 'upload'" class="text-sm font-medium text-primary-600 hover:text-primary-700">Carica un file</button>
            </div>
            <template v-else>
              <div class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-5 gap-3">
                <button
                  v-for="m in items"
                  :key="m.id"
                  type="button"
                  @click="selected = m"
                  @dblclick="confirmSelection(m)"
                  :class="selected?.id === m.id ? 'ring-2 ring-primary-500 ring-offset-2' : 'hover:ring-2 hover:ring-gray-300'"
                  class="group text-left rounded-lg overflow-hidden border border-gray-200 transition-shadow"
                  :title="m.original_name"
                >
                  <div class="aspect-square">
                    <MediaThumb :media="m" />
                  </div>
                  <p class="px-2 py-1.5 text-xs text-gray-600 truncate">{{ m.original_name }}</p>
                </button>
              </div>
              <div v-if="page < lastPage" class="text-center mt-4">
                <button
                  @click="load(page + 1)"
                  :disabled="loading"
                  class="text-sm px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 disabled:opacity-50"
                >
                  {{ loading ? 'Caricamento...' : 'Carica altri' }}
                </button>
              </div>
            </template>
          </template>

          <!-- Upload -->
          <MediaDropzone
            v-else
            :type="type"
            :multiple="true"
            @uploaded="onUploaded"
            @finished="onUploadFinished"
          />
        </div>

        <!-- Footer -->
        <div class="flex items-center justify-between gap-3 px-5 py-3 border-t border-gray-200 bg-gray-50 rounded-b-xl">
          <p class="text-xs text-gray-500 truncate">
            <template v-if="selected">
              {{ selected.original_name }}
              <span v-if="selected.width"> · {{ selected.width }}×{{ selected.height }}</span>
              · {{ formatBytes(selected.size) }}
            </template>
          </p>
          <div class="flex gap-2 shrink-0">
            <button @click="emit('close')" class="px-4 py-2 text-sm text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
              Annulla
            </button>
            <button
              @click="confirmSelection(selected)"
              :disabled="!selected"
              class="px-4 py-2 text-sm text-white bg-primary-600 rounded-lg hover:bg-primary-700 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              Inserisci
            </button>
          </div>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { ref, watch, onMounted, onBeforeUnmount } from 'vue'
import MediaThumb from './MediaThumb.vue'
import MediaDropzone from './MediaDropzone.vue'
import { useMediaStore, mediaErrorMessage } from '../../stores/mediaStore'
import { formatBytes } from '../../utils/media'

const props = defineProps({
  type: { type: String, default: null } // 'image' | 'video' | null
})

const emit = defineEmits(['select', 'close'])

const mediaStore = useMediaStore()

const tabs = [
  { id: 'library', label: 'Libreria' },
  { id: 'upload', label: 'Carica nuovo' }
]
const tab = ref('library')
const search = ref('')
const items = ref([])
const page = ref(1)
const lastPage = ref(1)
const loading = ref(false)
const error = ref('')
const selected = ref(null)
let lastUploaded = null

async function load(p = 1) {
  loading.value = true
  error.value = ''
  try {
    const res = await mediaStore.fetchMedia({
      type: props.type,
      search: search.value.trim(),
      page: p,
      per_page: 30
    })
    items.value = p === 1 ? res.data : [...items.value, ...res.data]
    page.value = res.page
    lastPage.value = res.last_page
  } catch (e) {
    error.value = mediaErrorMessage(e, 'Impossibile caricare la libreria')
  } finally {
    loading.value = false
  }
}

let searchTimeout = null
watch(search, () => {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => load(1), 400)
})

function onUploaded(media) {
  lastUploaded = media
}

// Al termine degli upload si torna alla libreria con l'ultimo file già selezionato
async function onUploadFinished() {
  if (!lastUploaded) return
  search.value = ''
  await load(1)
  selected.value = items.value.find((m) => m.id === lastUploaded.id) || lastUploaded
  lastUploaded = null
  tab.value = 'library'
}

function confirmSelection(media) {
  if (!media) return
  emit('select', media)
}

const onKeydown = (e) => {
  if (e.key === 'Escape') emit('close')
}

onMounted(() => {
  load(1)
  window.addEventListener('keydown', onKeydown)
})
onBeforeUnmount(() => window.removeEventListener('keydown', onKeydown))
</script>
