<template>
  <div class="min-h-screen bg-gradient-to-br from-primary-50 to-white flex items-center justify-center px-4">
    <div class="max-w-2xl w-full">
      <!-- Card principale -->
      <div class="bg-white rounded-2xl shadow-xl p-8 md:p-12 text-center">
        <!-- Icona di successo -->
        <div class="flex justify-center mb-6">
          <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center">
            <svg class="w-10 h-10 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
            </svg>
          </div>
        </div>

        <!-- Messaggio principale -->
        <h1 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">
          Grazie!
        </h1>
        <p class="text-lg text-gray-600 mb-8">
          La tua richiesta è stata inviata con successo. Ti contatteremo al più presto.
        </p>

        <!-- Dettagli aggiuntivi -->
        <div class="bg-gray-50 rounded-lg p-6 mb-8">
          <p class="text-sm text-gray-700 mb-2">
            <strong>Cosa succede ora?</strong>
          </p>
          <ul class="text-sm text-gray-600 space-y-2">
            <li class="flex items-start justify-center">
              <svg class="w-5 h-5 text-primary-600 mr-2 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
              </svg>
              <span>Riceverai una email di conferma</span>
            </li>
            <li class="flex items-start justify-center">
              <svg class="w-5 h-5 text-primary-600 mr-2 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
              </svg>
              <span>Il nostro team esaminerà la tua richiesta</span>
            </li>
            <li class="flex items-start justify-center">
              <svg class="w-5 h-5 text-primary-600 mr-2 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
              </svg>
              <span>Ti contatteremo entro 24-48 ore</span>
            </li>
          </ul>
        </div>

        <!-- Pulsante per tornare indietro -->
        <div class="flex flex-col sm:flex-row gap-4 justify-center">
          <button
            @click="goBack"
            class="bg-primary-600 hover:bg-primary-700 text-white px-8 py-3 rounded-lg font-medium transition-colors"
          >
            Torna alla pagina
          </button>
          <a
            href="/"
            class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-8 py-3 rounded-lg font-medium transition-colors inline-block"
          >
            Vai alla home
          </a>
        </div>
      </div>

      <!-- Messaggio footer -->
      <p class="text-center text-sm text-gray-500 mt-6">
        Hai bisogno di aiuto immediato? Contattaci direttamente
      </p>
    </div>
  </div>
</template>

<script setup>
import { useRouter } from 'vue-router'

const router = useRouter()

const goBack = () => {
  // Torna alla pagina precedente se disponibile
  if (window.history.length > 1) {
    router.go(-1)
  } else {
    router.push('/')
  }
}
</script>
