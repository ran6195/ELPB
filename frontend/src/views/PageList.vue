<template>
  <div class="min-h-screen bg-gray-50">
    <!-- HEADER SEPARATO CON SFONDO BIANCO -->
    <div class="bg-white border-b border-gray-200 shadow-sm">
      <div class="max-w-7xl mx-auto px-8 py-6">
        <div class="flex justify-between items-center">
          <div>
            <h1 class="text-2xl font-semibold text-gray-900 mb-1">Le Mie Landing Pages</h1>
            <div class="flex items-center gap-3">
              <p class="text-gray-500 text-sm">Crea e gestisci le tue landing pages</p>

              <!-- Badge: versione corrente -->
              <span class="inline-flex items-center gap-1.5 text-xs text-gray-400 font-mono px-2 py-0.5 rounded-md bg-gray-50 border border-gray-100 select-none">
                <span class="w-1.5 h-1.5 rounded-full bg-green-400 opacity-70"></span>
                v{{ currentVersion }}
              </span>
            </div>
          </div>
          <div class="flex items-center gap-4">
            <!-- User info -->
            <div class="flex items-center gap-3">
              <div class="text-right">
                <p class="text-sm font-medium text-gray-900">{{ authStore.user?.name }}</p>
                <div class="flex items-center gap-2 justify-end">
                  <span
                    :class="{
                      'bg-purple-100 text-purple-700': authStore.isAdmin,
                      'bg-blue-100 text-blue-700': authStore.isCompany,
                      'bg-gray-100 text-gray-700': authStore.isUser
                    }"
                    class="text-xs px-2 py-0.5 rounded-md font-medium"
                  >
                    {{ authStore.user?.role }}
                  </span>
                </div>
              </div>
            </div>
            <!-- Buttons -->
            <div class="flex gap-2">
              <router-link
                v-if="authStore.isAdmin"
                to="/admin"
                class="bg-purple-600 hover:bg-purple-700 text-white px-6 py-2.5 rounded-lg font-medium transition-colors shadow-md flex items-center gap-2"
              >
                <span>⚙️</span>
                <span>Admin</span>
              </router-link>
              <router-link
                v-if="authStore.isCompany"
                to="/company"
                class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2.5 rounded-lg font-medium transition-colors shadow-md flex items-center gap-2"
              >
                <span>👥</span>
                <span>Gestione</span>
              </router-link>
              <router-link
                to="/archived"
                class="bg-gray-600 hover:bg-gray-700 text-white px-6 py-2.5 rounded-lg font-medium transition-colors shadow-md flex items-center gap-2"
              >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
                </svg>
                <span>Archivio</span>
              </router-link>
              <button
                @click="showImportDialog = true"
                class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2.5 rounded-lg font-medium transition-colors shadow-md flex items-center gap-2"
              >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                </svg>
                <span>Importa</span>
              </button>
              <button
                @click="createNewPage"
                class="bg-primary-600 hover:bg-primary-700 text-white px-6 py-2.5 rounded-lg font-medium transition-colors shadow-md flex items-center gap-2"
              >
                <span class="text-lg">+</span>
                <span>Nuova Pagina</span>
              </button>
              <button
                @click="handleLogout"
                class="bg-white hover:bg-gray-50 text-gray-700 px-4 py-2.5 rounded-lg font-medium border border-gray-300 transition-colors"
              >
                Logout
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- FILTRI -->
    <div class="bg-white border-b border-gray-200">
      <div class="max-w-7xl mx-auto px-8 py-3">
        <div class="flex items-center gap-3 flex-wrap">
          <!-- Filtro stato -->
          <div class="flex items-center bg-gray-100 rounded-lg p-1 gap-1">
            <button
              @click="filterStatus = 'all'"
              :class="filterStatus === 'all' ? 'bg-white shadow text-gray-900' : 'text-gray-500 hover:text-gray-700'"
              class="px-3 py-1.5 rounded-md text-sm font-medium transition-all"
            >
              Tutte
            </button>
            <button
              @click="filterStatus = 'published'"
              :class="filterStatus === 'published' ? 'bg-white shadow text-green-700' : 'text-gray-500 hover:text-gray-700'"
              class="px-3 py-1.5 rounded-md text-sm font-medium transition-all"
            >
              Pubblicate
            </button>
            <button
              @click="filterStatus = 'draft'"
              :class="filterStatus === 'draft' ? 'bg-white shadow text-gray-700' : 'text-gray-500 hover:text-gray-700'"
              class="px-3 py-1.5 rounded-md text-sm font-medium transition-all"
            >
              Bozze
            </button>
          </div>

          <!-- Filtro azienda (solo admin) -->
          <select
            v-if="authStore.isAdmin"
            v-model="filterCompany"
            class="text-sm border border-gray-200 rounded-lg px-3 py-2 bg-white text-gray-700 focus:outline-none focus:ring-2 focus:ring-primary-500"
          >
            <option value="">Tutte le aziende</option>
            <option
              v-for="company in availableCompanies"
              :key="company.id"
              :value="company.id"
            >
              {{ company.name }}
            </option>
          </select>

          <!-- Contatore risultati -->
          <span class="text-sm text-gray-400 ml-auto">
            {{ filteredPages.length }} {{ filteredPages.length === 1 ? 'pagina' : 'pagine' }}
          </span>
        </div>
      </div>
    </div>

    <!-- CONTENUTO CON PADDING -->
    <div class="max-w-7xl mx-auto px-8 py-8">

      <div v-if="pageStore.loading" class="text-center py-12">
        <p class="text-gray-600">Caricamento...</p>
      </div>

      <div v-else-if="pageStore.error" class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
        Errore: {{ pageStore.error }}
      </div>

      <!-- GRIGLIA CARDS CON SPAZIATURA -->
      <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-5">
        <div
          v-for="page in filteredPages"
          :key="page.id"
          class="bg-white rounded-lg border border-gray-200 hover:shadow-lg transition-shadow"
        >
          <div class="p-4">
            <!-- TITOLO E BADGE -->
            <div class="flex justify-between items-start mb-3 gap-2">
              <h2 class="text-base font-semibold text-gray-900 flex-1 line-clamp-2">{{ page.title }}</h2>
              <div class="flex items-center gap-2">
                <span
                  v-if="page.is_published"
                  class="bg-green-50 text-green-700 text-xs px-2.5 py-1 rounded-md font-medium whitespace-nowrap"
                >
                  Pubblicata
                </span>
                <span
                  v-else
                  class="bg-gray-100 text-gray-600 text-xs px-2.5 py-1 rounded-md font-medium whitespace-nowrap"
                >
                  Bozza
                </span>

                <!-- Kebab menu -->
                <div class="relative kebab-menu-container">
                  <button
                    @click.stop="toggleMenu(page.id)"
                    class="p-1.5 hover:bg-gray-100 rounded-lg transition-colors"
                    :class="{ 'bg-gray-100': openMenuId === page.id }"
                  >
                    <svg class="w-5 h-5 text-gray-600" fill="currentColor" viewBox="0 0 24 24">
                      <circle cx="12" cy="5" r="2" />
                      <circle cx="12" cy="12" r="2" />
                      <circle cx="12" cy="19" r="2" />
                    </svg>
                  </button>

                  <!-- Dropdown menu -->
                  <div
                    v-if="openMenuId === page.id"
                    @click.stop
                    class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-xl border border-gray-200 py-1 z-50"
                  >
                    <button
                      @click="previewPage(page.id); closeMenu()"
                      class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 flex items-center gap-3"
                    >
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                      </svg>
                      Anteprima
                    </button>

                    <button
                      @click="duplicatePage(page.id); closeMenu()"
                      class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 flex items-center gap-3"
                    >
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                      </svg>
                      Duplica
                    </button>

                    <button
                      @click="exportPage(page.id); closeMenu()"
                      class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 flex items-center gap-3"
                    >
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                      </svg>
                      Esporta
                    </button>

                    <div class="border-t border-gray-100 my-1"></div>

                    <button
                      @click="deletePage(page.id); closeMenu()"
                      class="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50 flex items-center gap-3"
                    >
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                      </svg>
                      Elimina
                    </button>
                  </div>
                </div>
              </div>
            </div>

            <!-- INFO -->
            <div class="space-y-1.5 mb-4">
              <p class="text-gray-500 text-sm truncate">{{ page.slug }}</p>
              <p class="text-gray-400 text-xs">
                {{ page.blocks?.length || 0 }} blocchi
              </p>
              <!-- Creata da -->
              <div class="pt-1.5 border-t border-gray-100 space-y-0.5">
                <p class="text-xs text-gray-500">
                  <span class="font-medium">Creata il:</span> {{ formatDate(page.created_at) }}
                </p>
                <p class="text-xs text-gray-500">
                  <span class="font-medium">Creata da:</span> {{ page.user?.name || 'Sconosciuto' }}
                </p>
                <p v-if="page.company || authStore.isAdmin" class="text-xs text-gray-500">
                  <span class="font-medium">Società:</span> {{ page.company?.name || 'Nessuna' }}
                </p>
              </div>
            </div>

            <!-- BOTTONE MODIFICA -->
            <button
              @click="editPage(page.id)"
              class="w-full bg-primary-600 hover:bg-primary-700 text-white px-4 py-2.5 rounded-lg text-sm font-medium transition-colors"
            >
              Modifica
            </button>
          </div>
        </div>
      </div>

      <!-- EMPTY STATE -->
      <div v-if="!pageStore.loading && filteredPages.length === 0" class="text-center py-20">
        <div class="bg-white rounded-lg border border-gray-200 p-12 max-w-md mx-auto shadow-sm">
          <template v-if="filterStatus !== 'all' || filterCompany">
            <p class="text-gray-600 mb-6 text-lg">Nessuna pagina corrisponde ai filtri</p>
            <button
              @click="filterStatus = 'all'; filterCompany = ''"
              class="bg-gray-600 hover:bg-gray-700 text-white px-6 py-2.5 rounded-lg font-medium transition-colors shadow-md mx-auto"
            >
              Rimuovi filtri
            </button>
          </template>
          <template v-else>
            <p class="text-gray-600 mb-6 text-lg">Nessuna pagina trovata</p>
            <button
              @click="createNewPage"
              class="bg-primary-600 hover:bg-primary-700 text-white px-6 py-2.5 rounded-lg font-medium transition-colors shadow-md flex items-center gap-2 mx-auto"
            >
              <span class="text-lg">+</span>
              <span>Crea la tua prima pagina</span>
            </button>
          </template>
        </div>
      </div>
    </div>

    <!-- Import Dialog -->
    <div
      v-if="showImportDialog"
      class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
      @click.self="closeImportDialog"
    >
      <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4 p-6">
        <h3 class="text-xl font-semibold text-gray-900 mb-4">Importa Pagina</h3>

        <div class="mb-4">
          <p class="text-sm text-gray-600 mb-4">
            Seleziona un file JSON esportato in precedenza per importare una pagina.
          </p>

          <div class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center hover:border-primary-400 transition-colors">
            <input
              type="file"
              ref="fileInput"
              accept=".json"
              @change="handleFileSelect"
              class="hidden"
            />
            <button
              @click="$refs.fileInput.click()"
              class="bg-primary-600 hover:bg-primary-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors"
            >
              Seleziona File JSON
            </button>
            <p v-if="selectedFile" class="mt-3 text-sm text-gray-700">
              File selezionato: <span class="font-medium">{{ selectedFile.name }}</span>
            </p>
          </div>
        </div>

        <div v-if="importError" class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm">
          {{ importError }}
        </div>

        <div class="flex gap-3">
          <button
            @click="closeImportDialog"
            class="flex-1 bg-white hover:bg-gray-50 text-gray-700 px-4 py-2 rounded-lg font-medium border border-gray-300 transition-colors"
          >
            Annulla
          </button>
          <button
            @click="importPageFromFile"
            :disabled="!selectedFile || importing"
            class="flex-1 bg-primary-600 hover:bg-primary-700 text-white px-4 py-2 rounded-lg font-medium transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
          >
            {{ importing ? 'Importazione...' : 'Importa' }}
          </button>
        </div>
      </div>
    </div>

  </div>
</template>

<script setup>
import { onMounted, onUnmounted, ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import { usePageStore } from '../stores/pageStore'
import { useAuthStore } from '../stores/authStore'
import pkg from '../../package.json'

const router = useRouter()
const pageStore = usePageStore()
const authStore = useAuthStore()
const currentVersion = pkg.version

// Filtri
const filterStatus = ref('all')
const filterCompany = ref('')

const availableCompanies = computed(() => {
  const map = new Map()
  for (const page of pageStore.pages) {
    if (page.company) {
      map.set(page.company.id, page.company)
    }
  }
  return Array.from(map.values()).sort((a, b) => a.name.localeCompare(b.name))
})

const filteredPages = computed(() => {
  return pageStore.pages.filter(page => {
    if (filterStatus.value === 'published' && !page.is_published) return false
    if (filterStatus.value === 'draft' && page.is_published) return false
    if (filterCompany.value && page.company?.id !== filterCompany.value) return false
    return true
  })
})

// Import dialog state
const showImportDialog = ref(false)
const selectedFile = ref(null)
const importing = ref(false)
const importError = ref(null)

// Kebab menu state
const openMenuId = ref(null)

const createNewPage = () => {
  router.push('/editor')
}

const editPage = (id) => {
  router.push(`/editor/${id}`)
}

const previewPage = (id) => {
  // Apri l'anteprima in una nuova scheda utilizzando il router
  const routeData = router.resolve(`/preview/${id}`)
  window.open(routeData.href, '_blank')
}

const deletePage = async (id) => {
  // Trova la pagina per verificare se è pubblicata
  const page = pageStore.pages.find(p => p.id === id)

  if (page && page.is_published) {
    // Chiedi all'utente se vuole togliere la pubblicazione
    const unpublishConfirm = confirm(
      'Questa pagina è pubblicata. Vuoi toglierla dalla pubblicazione ed eliminarla?\n\n' +
      'Clicca OK per togliere la pubblicazione ed eliminare.\n' +
      'Clicca Annulla per annullare l\'operazione.'
    )

    if (!unpublishConfirm) {
      return
    }

    try {
      // Prima toglie la pubblicazione
      page.is_published = false
      await pageStore.updatePage(id, page)

      // Poi elimina la pagina
      await pageStore.deletePage(id)
    } catch (error) {
      const errorMessage = error.response?.data?.error || 'Errore durante l\'eliminazione della pagina'
      alert(errorMessage)
    }
    return
  }

  // Se la pagina non è pubblicata, chiedi conferma normale
  if (confirm('Sei sicuro di voler eliminare questa pagina?')) {
    try {
      await pageStore.deletePage(id)
    } catch (error) {
      const errorMessage = error.response?.data?.error || 'Errore durante l\'eliminazione della pagina'
      alert(errorMessage)
    }
  }
}

const duplicatePage = async (id) => {
  if (confirm('Vuoi duplicare questa pagina? Verrà creata una copia non pubblicata.')) {
    try {
      const duplicatedPage = await pageStore.duplicatePage(id)
      alert(`Pagina duplicata con successo! Nuova pagina: "${duplicatedPage.title}"`)
    } catch (error) {
      alert('Errore durante la duplicazione della pagina')
    }
  }
}

const formatDate = (dateString) => {
  if (!dateString) return 'Data non disponibile'

  const date = new Date(dateString)
  const now = new Date()
  const diffTime = Math.abs(now - date)
  const diffDays = Math.floor(diffTime / (1000 * 60 * 60 * 24))

  // Se è oggi
  if (diffDays === 0) {
    const hours = date.getHours().toString().padStart(2, '0')
    const minutes = date.getMinutes().toString().padStart(2, '0')
    return `Oggi alle ${hours}:${minutes}`
  }

  // Se è ieri
  if (diffDays === 1) {
    return 'Ieri'
  }

  // Se è meno di una settimana fa
  if (diffDays < 7) {
    return `${diffDays} giorni fa`
  }

  // Altrimenti mostra la data completa
  const day = date.getDate().toString().padStart(2, '0')
  const month = (date.getMonth() + 1).toString().padStart(2, '0')
  const year = date.getFullYear()

  return `${day}/${month}/${year}`
}

const handleLogout = () => {
  if (confirm('Sei sicuro di voler uscire?')) {
    authStore.logout()
    router.push('/login')
  }
}

// Kebab menu functions
const toggleMenu = (pageId) => {
  openMenuId.value = openMenuId.value === pageId ? null : pageId
}

const closeMenu = () => {
  openMenuId.value = null
}

// Close menu when clicking outside
const handleClickOutside = (event) => {
  if (openMenuId.value && !event.target.closest('.kebab-menu-container')) {
    closeMenu()
  }
}

// Add/remove click listener
onMounted(() => {
  pageStore.fetchPages()
  document.addEventListener('click', handleClickOutside)
})

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside)
})

// Export page
const exportPage = async (id) => {
  try {
    await pageStore.exportPage(id)
    // Il download viene gestito automaticamente dallo store
  } catch (error) {
    alert('Errore durante l\'esportazione della pagina')
  }
}

// Import page handlers
const closeImportDialog = () => {
  showImportDialog.value = false
  selectedFile.value = null
  importError.value = null
}

const handleFileSelect = (event) => {
  const file = event.target.files[0]
  if (file) {
    if (!file.name.endsWith('.json')) {
      importError.value = 'Seleziona un file JSON valido'
      return
    }
    selectedFile.value = file
    importError.value = null
  }
}

const importPageFromFile = async () => {
  if (!selectedFile.value) return

  importing.value = true
  importError.value = null

  try {
    // Leggi il contenuto del file JSON
    const fileContent = await selectedFile.value.text()
    const jsonData = JSON.parse(fileContent)

    // Importa la pagina
    const result = await pageStore.importPage(jsonData)

    // Chiudi il dialog
    closeImportDialog()

    // Mostra messaggio di successo
    let message = 'Pagina importata con successo!'
    if (result.slug_changed) {
      message += `\n\nLo slug è stato modificato da "${result.original_slug}" a "${result.new_slug}" perché esisteva già una pagina con lo stesso slug.`
    }
    alert(message)

  } catch (error) {
    console.error('Import error:', error)
    importError.value = error.response?.data?.message || 'Errore durante l\'importazione. Verifica che il file JSON sia valido.'
  } finally {
    importing.value = false
  }
}
</script>
