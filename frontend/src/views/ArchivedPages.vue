<template>
  <div class="min-h-screen bg-gray-50">
    <!-- Header -->
    <div class="bg-white shadow">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
        <div class="flex justify-between items-center">
          <div>
            <h1 class="text-3xl font-bold text-gray-900">Archivio Pagine</h1>
            <p class="mt-1 text-sm text-gray-500">Pagine eliminate che possono essere ripristinate</p>
          </div>
          <router-link
            to="/"
            class="bg-gray-600 hover:bg-gray-700 text-white px-6 py-2.5 rounded-lg font-medium transition-colors"
          >
            Torna alle Pagine
          </router-link>
        </div>
      </div>
    </div>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <!-- Loading State -->
      <div v-if="pageStore.loading" class="flex justify-center items-center py-12">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-primary-600"></div>
      </div>

      <!-- Empty State -->
      <div
        v-else-if="!pageStore.archivedPages || pageStore.archivedPages.length === 0"
        class="text-center py-16 bg-white rounded-lg shadow"
      >
        <svg
          class="mx-auto h-16 w-16 text-gray-400"
          fill="none"
          viewBox="0 0 24 24"
          stroke="currentColor"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"
          />
        </svg>
        <h3 class="mt-4 text-lg font-medium text-gray-900">Nessuna pagina archiviata</h3>
        <p class="mt-2 text-sm text-gray-500">Le pagine eliminate appariranno qui</p>
      </div>

      <!-- Pages Grid -->
      <div
        v-else
        class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6"
      >
        <div
          v-for="page in pageStore.archivedPages"
          :key="page.id"
          class="bg-white rounded-lg shadow hover:shadow-lg transition-shadow overflow-hidden"
        >
          <div class="p-6">
            <!-- Title -->
            <h3 class="text-xl font-semibold text-gray-900 mb-2 truncate">
              {{ page.title }}
            </h3>

            <!-- Metadata -->
            <div class="space-y-2 text-sm text-gray-600 mb-4">
              <p>
                <span class="font-medium">Slug:</span>
                <span class="text-gray-500 ml-1">{{ page.slug }}</span>
              </p>
              <p>
                <span class="font-medium">Eliminata:</span>
                <span class="text-gray-500 ml-1">{{ formatDate(page.deleted_at) }}</span>
              </p>
              <p v-if="page.user">
                <span class="font-medium">Proprietario:</span>
                <span class="text-gray-500 ml-1">{{ page.user.name }}</span>
              </p>
              <p v-if="page.company">
                <span class="font-medium">Azienda:</span>
                <span class="text-gray-500 ml-1">{{ page.company.name }}</span>
              </p>
            </div>

            <!-- Status Badge -->
            <div class="flex items-center gap-2 mb-4">
              <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                Archiviata
              </span>
              <span
                v-if="page.is_published"
                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800"
              >
                Era Pubblicata
              </span>
            </div>

            <!-- Actions -->
            <div class="space-y-2">
              <button
                @click="restorePage(page.id)"
                class="w-full bg-green-600 hover:bg-green-700 text-white px-4 py-2.5 rounded-lg text-sm font-medium transition-colors"
              >
                Ripristina
              </button>
              <button
                @click="forceDeletePage(page.id, page.title)"
                class="w-full bg-red-600 hover:bg-red-700 text-white px-4 py-2.5 rounded-lg text-sm font-medium transition-colors"
              >
                Elimina Definitivamente
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted } from 'vue'
import { usePageStore } from '../stores/pageStore'

const pageStore = usePageStore()

onMounted(async () => {
  await pageStore.fetchArchivedPages()
})

const restorePage = async (id) => {
  if (confirm('Sei sicuro di voler ripristinare questa pagina?')) {
    try {
      await pageStore.restorePage(id)
      alert('Pagina ripristinata con successo!')
    } catch (error) {
      const errorMessage = error.response?.data?.error || 'Errore durante il ripristino della pagina'
      alert(errorMessage)
    }
  }
}

const forceDeletePage = async (id, title) => {
  const confirmed = confirm(
    `⚠️ ATTENZIONE: Stai per eliminare DEFINITIVAMENTE la pagina "${title}".\n\n` +
    'Questa azione è IRREVERSIBILE e la pagina NON potrà essere ripristinata.\n\n' +
    'Sei assolutamente sicuro di voler procedere?'
  )

  if (confirmed) {
    try {
      await pageStore.forceDeletePage(id)
      alert('Pagina eliminata definitivamente')
    } catch (error) {
      const errorMessage = error.response?.data?.error || 'Errore durante l\'eliminazione definitiva della pagina'
      alert(errorMessage)
    }
  }
}

const formatDate = (dateString) => {
  if (!dateString) return 'Data non disponibile'

  const date = new Date(dateString)
  const now = new Date()

  // Calcola la differenza in giorni
  const diffTime = Math.abs(now - date)
  const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24))

  // Formattazione
  const options = {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  }

  const formattedDate = date.toLocaleDateString('it-IT', options)

  // Aggiungi info relativa
  if (diffDays === 0) {
    return `Oggi, ${formattedDate}`
  } else if (diffDays === 1) {
    return `Ieri, ${formattedDate}`
  } else if (diffDays < 7) {
    return `${diffDays} giorni fa, ${formattedDate}`
  } else {
    return formattedDate
  }
}
</script>
