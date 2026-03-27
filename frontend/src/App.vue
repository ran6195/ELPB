<template>
  <div id="app">
    <router-view />

    <!-- Banner aggiornamento disponibile (globale, fisso in alto) -->
    <Transition name="update-banner">
      <div
        v-if="updateAvailable"
        class="fixed top-0 left-0 right-0 z-[9999] flex items-center justify-center gap-3 px-4 py-2.5 bg-amber-500 text-white text-sm font-medium shadow-lg"
      >
        <span class="relative flex h-2 w-2">
          <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-white opacity-75"></span>
          <span class="relative inline-flex rounded-full h-2 w-2 bg-white"></span>
        </span>
        <span>Nuova versione disponibile</span>
        <button
          @click="reload"
          class="inline-flex items-center gap-1.5 bg-white text-amber-700 font-semibold px-3 py-1 rounded-full text-xs hover:bg-amber-50 transition-colors"
        >
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
          </svg>
          Ricarica ora
        </button>
      </div>
    </Transition>
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

.update-banner-enter-active,
.update-banner-leave-active {
  transition: transform 0.3s ease, opacity 0.3s ease;
}
.update-banner-enter-from,
.update-banner-leave-to {
  transform: translateY(-100%);
  opacity: 0;
}
</style>
