<template>
  <div class="min-h-screen bg-gray-50">
    <!-- HEADER SEPARATO CON SFONDO BIANCO -->
    <div class="bg-white border-b border-gray-200 shadow-sm">
      <div class="max-w-7xl mx-auto px-8 py-6">
        <div class="flex justify-between items-center">
          <div>
            <h1 class="text-2xl font-semibold text-gray-900 mb-1">Le Mie Landing Pages</h1>
            <p class="text-gray-500 text-sm">Crea e gestisci le tue landing pages</p>
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

    <!-- CONTENUTO CON PADDING -->
    <div class="max-w-7xl mx-auto px-8 py-8">

      <div v-if="pageStore.loading" class="text-center py-12">
        <p class="text-gray-600">Caricamento...</p>
      </div>

      <div v-else-if="pageStore.error" class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
        Errore: {{ pageStore.error }}
      </div>

      <!-- GRIGLIA CARDS CON SPAZIATURA -->
      <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <div
          v-for="page in pageStore.pages"
          :key="page.id"
          class="bg-white rounded-lg border border-gray-200 hover:shadow-lg transition-shadow overflow-hidden"
        >
          <div class="p-6">
            <!-- TITOLO E BADGE -->
            <div class="flex justify-between items-start mb-4">
              <h2 class="text-lg font-semibold text-gray-900 flex-1 mr-3">{{ page.title }}</h2>
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
            </div>

            <!-- INFO -->
            <div class="space-y-2 mb-6">
              <p class="text-gray-500 text-sm">{{ page.slug }}</p>
              <p class="text-gray-400 text-xs">
                {{ page.blocks?.length || 0 }} blocchi
              </p>
              <!-- Creata da -->
              <div class="pt-2 border-t border-gray-100">
                <p class="text-xs text-gray-500">
                  <span class="font-medium">Creata da:</span> {{ page.user?.name || 'Sconosciuto' }}
                </p>
                <p v-if="page.company || authStore.isAdmin" class="text-xs text-gray-500">
                  <span class="font-medium">Società:</span> {{ page.company?.name || 'Nessuna' }}
                </p>
              </div>
            </div>

            <!-- BOTTONI RIORGANIZZATI -->
            <div class="space-y-2">
              <!-- Bottone principale: Modifica -->
              <button
                @click="editPage(page.id)"
                class="w-full bg-primary-600 hover:bg-primary-700 text-white px-4 py-2.5 rounded-lg text-sm font-medium transition-colors"
              >
                Modifica
              </button>
              <!-- Bottoni secondari affiancati -->
              <div class="grid grid-cols-2 gap-2">
                <button
                  @click="previewPage(page.id)"
                  class="bg-white hover:bg-gray-50 text-gray-700 px-4 py-2 rounded-lg text-sm font-medium border border-gray-300 transition-colors"
                >
                  Anteprima
                </button>
                <button
                  @click="duplicatePage(page.id)"
                  class="bg-white hover:bg-blue-50 text-blue-600 px-4 py-2 rounded-lg text-sm font-medium border border-gray-300 hover:border-blue-200 transition-colors"
                >
                  Duplica
                </button>
              </div>
              <div class="grid grid-cols-2 gap-2">
                <button
                  v-if="page.is_published"
                  @click="viewPublicPage(page.slug)"
                  class="bg-white hover:bg-gray-50 text-gray-700 px-4 py-2 rounded-lg text-sm font-medium border border-gray-300 transition-colors"
                >
                  Vedi
                </button>
                <button
                  @click="deletePage(page.id)"
                  :class="page.is_published ? '' : 'col-span-2'"
                  class="bg-white hover:bg-red-50 text-red-600 px-4 py-2 rounded-lg text-sm font-medium border border-gray-300 hover:border-red-200 transition-colors"
                >
                  Elimina
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- EMPTY STATE -->
      <div v-if="!pageStore.loading && pageStore.pages.length === 0" class="text-center py-20">
        <div class="bg-white rounded-lg border border-gray-200 p-12 max-w-md mx-auto shadow-sm">
          <p class="text-gray-600 mb-6 text-lg">Nessuna pagina trovata</p>
          <button
            @click="createNewPage"
            class="bg-primary-600 hover:bg-primary-700 text-white px-6 py-2.5 rounded-lg font-medium transition-colors shadow-md flex items-center gap-2 mx-auto"
          >
            <span class="text-lg">+</span>
            <span>Crea la tua prima pagina</span>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { usePageStore } from '../stores/pageStore'
import { useAuthStore } from '../stores/authStore'

const router = useRouter()
const pageStore = usePageStore()
const authStore = useAuthStore()

onMounted(() => {
  pageStore.fetchPages()
})

const createNewPage = () => {
  router.push('/editor')
}

const editPage = (id) => {
  router.push(`/editor/${id}`)
}

const previewPage = (id) => {
  router.push(`/preview/${id}`)
}

const deletePage = async (id) => {
  if (confirm('Sei sicuro di voler eliminare questa pagina?')) {
    try {
      await pageStore.deletePage(id)
    } catch (error) {
      alert('Errore durante l\'eliminazione della pagina')
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

const viewPublicPage = (slug) => {
  router.push(`/p/${slug}`)
}

const handleLogout = () => {
  if (confirm('Sei sicuro di voler uscire?')) {
    authStore.logout()
    router.push('/login')
  }
}
</script>
