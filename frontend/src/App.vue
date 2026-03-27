<template>
  <div id="app">
    <router-view />

    <!-- Bottone aggiornamento disponibile (globale, floating in basso a destra) -->
    <button
      v-if="updateAvailable"
      @click="reload"
      title="Nuova versione disponibile — clicca per aggiornare"
      class="fixed bottom-5 right-5 z-[9999] flex items-center gap-2 bg-amber-500 hover:bg-amber-600 text-white text-xs font-semibold px-3.5 py-2 rounded-full shadow-lg transition-colors"
    >
      <span class="relative flex h-1.5 w-1.5">
        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-white opacity-75"></span>
        <span class="relative inline-flex rounded-full h-1.5 w-1.5 bg-white"></span>
      </span>
      <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
      </svg>
      Aggiorna
    </button>
  </div>
</template>

<script setup>
import { useVersionCheck } from './composables/useVersionCheck'
const { updateAvailable, reload } = useVersionCheck()
</script>

<style scoped>
#app {
  min-height: 100vh;
}
</style>
