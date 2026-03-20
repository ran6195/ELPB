<template>
  <footer :class="footerWrapperClass" :style="footerWrapperStyle">
    <div :class="['px-6 py-4', containerClass, roundedCorners ? 'rounded-lg' : '']" :style="blockStyles">
      <div class="text-center">
        <!-- Lista link legali -->
        <ul class="list-none mb-2 flex flex-wrap justify-center gap-x-4 gap-y-1">
          <li
            v-for="(link, index) in defaultLegalLinks"
            :key="index"
            class="inline-block"
          >
            <a
              :href="link.url"
              :class="['hover:underline transition-colors', link.isCookiePreference ? 'cc-show' : '']"
              :style="{ color: linkColor }"
            >
              {{ link.text }}
            </a>
          </li>
        </ul>

        <!-- Testo legale -->
        <div
          class="text-xs leading-snug"
          :style="{ color: textColor }"
          v-html="block.content.legalText || defaultLegalText"
        ></div>
      </div>
    </div>
  </footer>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  block: {
    type: Object,
    required: true
  },
  editable: {
    type: Boolean,
    default: false
  },
  roundedCorners: {
    type: Boolean,
    default: true
  },
  page: {
    type: Object,
    default: null
  }
})

// Default content - genera link dinamici basati sullo slug della pagina e presenza dati legali
const defaultLegalLinks = computed(() => {
  const slug = props.page?.slug || ''
  const hasLegalInfo = props.page?.legal_info && Object.keys(props.page.legal_info).length > 0

  // Se non c'è slug o non ci sono dati legali, usa link di placeholder
  if (!slug || !hasLegalInfo) {
    return [
      { text: 'Privacy', url: '#', isCookiePreference: false },
      { text: "Condizioni d'uso", url: '#', isCookiePreference: false },
      { text: 'Cookies', url: '#', isCookiePreference: false },
      { text: 'Preferenze cookies', url: '#', isCookiePreference: true }
    ]
  }

  // Usa URL del backend per le pagine legali
  // In produzione: https://edysma.net/ELPB/backend/public/legal/{slug}/{type}
  // In sviluppo: http://localhost:8000/legal/{slug}/{type}
  // Nota: Sui renderer standalone e Joomla usano path relativi locali
  const apiUrl = import.meta.env.VITE_API_URL || 'http://localhost:8000/api'
  const backendUrl = apiUrl.replace(/\/api$/, '') // Rimuove /api dalla fine

  return [
    { text: 'Privacy', url: `${backendUrl}/legal/${slug}/privacy`, isCookiePreference: false },
    { text: "Condizioni d'uso", url: `${backendUrl}/legal/${slug}/condizioni`, isCookiePreference: false },
    { text: 'Cookies', url: `${backendUrl}/legal/${slug}/cookies`, isCookiePreference: false },
    { text: 'Preferenze cookies', url: '#', isCookiePreference: true }
  ]
})

const defaultLegalText = `ARAN CUCINE PALERMO - VIALE LAZIO, 124/126 90144 PALERMO (PA), <a href="mailto:info@arancucinepalermo.it">info@arancucinepalermo.it</a>, <a href="tel:+390915142890">091 514 289</a><br>è un'iniziativa INTERLINEA SRL, VIALE AIACE, 138 90151 PALERMO - P.IVA e C.F: 05472210821 - Tutti i diritti riservati - sito realizzato da <a href="https://www.edysma.com/dachi.php?mit=arancucinepalermo" target="_blank">EDYSMA</a> e <a href="https://www.fm-marketing.it/" target="_blank">FM MARKETING</a> in conformità agli standards di accessibilità e di Responsive Web Design (RWD)`

// Gestisci fullWidth (default true)
const fullWidth = computed(() => {
  return props.block.content.fullWidth !== false
})

// Classi per il wrapper esterno (footer tag)
const footerWrapperClass = computed(() => {
  if (!fullWidth.value) {
    return 'max-w-7xl mx-auto'
  }
  return ''
})

// Stili per il wrapper esterno
const footerWrapperStyle = computed(() => {
  if (fullWidth.value) {
    const styles = props.block.styles || {}
    return {
      backgroundColor: styles.backgroundColor || '#F3F4F6',
      color: styles.textColor || '#6B7280'
    }
  }
  return {}
})

// Classi per il container interno
const containerClass = computed(() => {
  if (fullWidth.value) {
    return 'max-w-7xl mx-auto'
  }
  return ''
})

// Stili per il div interno
const blockStyles = computed(() => {
  const styles = props.block.styles || {}

  if (!fullWidth.value) {
    return {
      backgroundColor: styles.backgroundColor || '#F3F4F6',
      color: styles.textColor || '#6B7280',
      padding: styles.padding || undefined,
      fontFamily: styles.fontFamily || undefined
    }
  }

  return {
    padding: styles.padding || undefined,
    fontFamily: styles.fontFamily || undefined
  }
})

// Colore del testo
const textColor = computed(() => {
  const styles = props.block.styles || {}
  return styles.textColor || '#6B7280'
})

// Colore dei link
const linkColor = computed(() => {
  const styles = props.block.styles || {}
  return styles.linkColor || styles.textColor || '#6B7280'
})
</script>

<style scoped>
/* Stile per i link nel testo legale */
:deep(a) {
  color: inherit;
  text-decoration: none;
}

:deep(a:hover) {
  text-decoration: underline;
}
</style>
