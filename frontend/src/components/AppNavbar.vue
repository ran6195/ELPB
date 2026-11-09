<template>
  <header class="bg-white border-b border-gray-200 sticky top-0 z-40">
    <div class="w-full px-4 sm:px-6 h-14 flex items-center gap-4">
      <!-- Marchio -->
      <router-link to="/" class="shrink-0 flex items-center gap-2 font-semibold text-gray-900">
        <span class="w-7 h-7 rounded-lg bg-primary-600 text-white text-xs font-bold flex items-center justify-center">LP</span>
        <span class="hidden md:inline">Landing Page Builder</span>
      </router-link>

      <!-- Sezioni (su telefono scorrono in orizzontale) -->
      <nav class="flex-1 min-w-0 overflow-x-auto" aria-label="Sezioni">
        <ul class="flex items-center gap-1 whitespace-nowrap">
          <li v-for="link in links" :key="link.to">
            <router-link
              :to="link.to"
              class="flex items-center gap-1.5 px-3 py-2 rounded-lg text-sm font-medium transition-colors"
              :class="isActive(link) ? 'bg-primary-50 text-primary-700' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-100'"
              :aria-current="isActive(link) ? 'page' : undefined"
            >
              <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="link.icon" />
              </svg>
              <span>{{ link.label }}</span>
            </router-link>
          </li>
        </ul>
      </nav>

      <!-- Menu utente -->
      <div ref="menuRoot" class="relative shrink-0">
        <button
          @click="menuOpen = !menuOpen"
          class="flex items-center gap-2 pl-1 pr-2 py-1 rounded-lg hover:bg-gray-100 transition-colors"
          :aria-expanded="menuOpen"
          aria-haspopup="menu"
        >
          <span class="w-8 h-8 rounded-full bg-gray-200 text-gray-700 text-sm font-semibold flex items-center justify-center">
            {{ initials }}
          </span>
          <span class="hidden sm:block text-left leading-tight">
            <span class="block text-sm font-medium text-gray-900">{{ authStore.user?.name }}</span>
            <span class="block text-xs text-gray-500">{{ roleLabel }}</span>
          </span>
          <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
          </svg>
        </button>

        <div
          v-if="menuOpen"
          role="menu"
          class="absolute right-0 mt-2 w-60 bg-white rounded-lg border border-gray-200 shadow-lg py-1 text-sm"
        >
          <div class="px-4 py-2.5 border-b border-gray-100">
            <p class="font-medium text-gray-900 truncate">{{ authStore.user?.name }}</p>
            <p class="text-xs text-gray-500 truncate">{{ authStore.user?.email }}</p>
            <p class="text-xs text-gray-500 mt-1">
              {{ roleLabel }}<template v-if="authStore.user?.company?.name"> · {{ authStore.user.company.name }}</template>
            </p>
          </div>
          <button
            role="menuitem"
            @click="logout"
            class="w-full text-left px-4 py-2 text-gray-700 hover:bg-gray-50 flex items-center gap-2"
          >
            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
            </svg>
            Esci
          </button>
          <p class="px-4 pt-2 pb-1.5 mt-1 border-t border-gray-100 text-xs text-gray-400 font-mono">v{{ version }}</p>
        </div>
      </div>
    </div>
  </header>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '../stores/authStore'
import pkg from '../../package.json'

const authStore = useAuthStore()
const route = useRoute()
const router = useRouter()
const version = pkg.version

const icons = {
  pages: 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
  media: 'M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z',
  archive: 'M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4',
  guide: 'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253',
  admin: 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065zM15 12a3 3 0 11-6 0 3 3 0 016 0z',
  company: 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z'
}

const links = computed(() => [
  { to: '/', label: 'Pagine', icon: icons.pages, exact: true },
  { to: '/media', label: 'Media', icon: icons.media },
  { to: '/archived', label: 'Archivio', icon: icons.archive },
  { to: '/guida-pubblicazione', label: 'Guida', icon: icons.guide },
  ...(authStore.isAdmin ? [{ to: '/admin', label: 'Admin', icon: icons.admin }] : []),
  ...(authStore.isCompany ? [{ to: '/company', label: 'Gestione', icon: icons.company }] : [])
])

const isActive = (link) => (link.exact ? route.path === link.to : route.path.startsWith(link.to))

const roleLabels = { admin: 'Amministratore', company: 'Responsabile aziendale', user: 'Utente' }
const roleLabel = computed(() => roleLabels[authStore.user?.role] || authStore.user?.role || '')
const initials = computed(() => (authStore.user?.name || '?')
  .split(/\s+/).filter(Boolean).slice(0, 2).map(w => w[0].toUpperCase()).join(''))

// Menu utente: si chiude cliccando fuori, con Esc o cambiando pagina
const menuOpen = ref(false)
const menuRoot = ref(null)
const onDocumentClick = (e) => { if (menuRoot.value && !menuRoot.value.contains(e.target)) menuOpen.value = false }
const onKeydown = (e) => { if (e.key === 'Escape') menuOpen.value = false }
onMounted(() => {
  document.addEventListener('click', onDocumentClick)
  document.addEventListener('keydown', onKeydown)
})
onBeforeUnmount(() => {
  document.removeEventListener('click', onDocumentClick)
  document.removeEventListener('keydown', onKeydown)
})
watch(() => route.fullPath, () => { menuOpen.value = false })

const logout = () => {
  menuOpen.value = false
  if (confirm('Sei sicuro di voler uscire?')) {
    authStore.logout()
    router.push('/login')
  }
}
</script>
