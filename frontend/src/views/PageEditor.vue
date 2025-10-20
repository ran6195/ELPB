<template>
  <div class="min-h-screen bg-gray-50">
    <!-- Header -->
    <div class="bg-white border-b border-gray-200 px-8 py-5">
      <div class="max-w-7xl mx-auto flex justify-between items-center">
        <div class="flex items-center gap-6">
          <button
            @click="goBack"
            class="text-gray-600 hover:text-gray-900 font-medium transition-colors"
          >
            ← Indietro
          </button>
          <input
            v-model="page.title"
            type="text"
            placeholder="Titolo pagina"
            class="text-xl font-semibold border border-gray-300 focus:border-primary-500 focus:ring-2 focus:ring-primary-200 rounded-lg px-4 py-2.5 transition-all outline-none min-w-[400px]"
          />
        </div>
        <div class="flex gap-3 items-center">
          <button
            @click="showSettings = !showSettings"
            class="bg-white hover:bg-gray-50 text-gray-700 px-6 py-2.5 rounded-lg font-medium transition-colors border border-gray-300"
          >
            ⚙️ Impostazioni
          </button>

          <!-- Indicatore stato salvataggio -->
          <div class="flex items-center gap-2 px-4 py-2.5 text-sm font-medium">
            <span v-if="saveStatus === 'saving'" class="flex items-center gap-2 text-gray-600">
              <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              Salvando...
            </span>
            <span v-else-if="saveStatus === 'saved'" class="flex items-center gap-2 text-green-600">
              <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
              </svg>
              Salvato
            </span>
            <span v-else-if="saveStatus === 'error'" class="flex items-center gap-2 text-red-600">
              <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
              </svg>
              Errore
            </span>
          </div>
        </div>
      </div>
    </div>

    <div class="flex h-[calc(100vh-73px)]">
      <!-- Sidebar blocchi -->
      <div class="w-80 bg-white border-r border-gray-200 p-6 overflow-y-auto">
        <h3 class="font-semibold text-gray-900 mb-6 text-sm uppercase tracking-wide">
          Blocchi Disponibili
        </h3>
        <div class="space-y-3">
          <button
            v-for="blockType in blockTypes"
            :key="blockType.type"
            @click="addBlock(blockType.type)"
            class="w-full text-left p-4 bg-gray-50 hover:bg-gray-100 rounded-lg border border-gray-200 transition-colors"
          >
            <div class="font-medium text-sm text-gray-900 mb-1">{{ blockType.name }}</div>
            <div class="text-xs text-gray-500">{{ blockType.description }}</div>
          </button>
        </div>
      </div>

      <!-- Canvas editor -->
      <div
        class="flex-1 overflow-y-auto"
        :style="{
          backgroundColor: page.styles?.backgroundColor || '#FFFFFF',
          fontFamily: page.styles?.fontFamily || 'inherit'
        }"
      >
        <div
          v-if="page.blocks.length === 0"
          class="flex items-center justify-center h-96 text-gray-400"
        >
          <p class="text-base">Clicca su un blocco nella sidebar per iniziare</p>
        </div>

        <!-- Blocchi -->
        <draggable
          v-model="page.blocks"
          item-key="id"
          @end="updateBlockOrder"
          @move="onBlockMove"
          class="min-h-screen flex flex-col"
          :style="{ gap: `${page.styles?.blockGap ?? 15}px` }"
        >
          <template #item="{ element, index }">
            <div
              :class="[
                'relative group border-2 border-transparent hover:border-primary-300 transition-colors',
                element.type === 'header' || element.type === 'footer' ? 'cursor-not-allowed' : 'cursor-move'
              ]"
            >
              <!-- Controlli blocco -->
              <div class="absolute top-4 right-4 opacity-0 group-hover:opacity-100 transition-opacity flex gap-3 z-10">
                <button
                  @click="editBlock(index)"
                  class="bg-white text-gray-700 px-4 py-2 rounded-lg shadow-md text-sm font-medium hover:bg-gray-50 border border-gray-300 transition-colors"
                >
                  Modifica
                </button>
                <button
                  @click="deleteBlock(index)"
                  class="bg-white text-red-600 px-4 py-2 rounded-lg shadow-md text-sm font-medium hover:bg-red-50 border border-gray-300 transition-colors"
                >
                  Elimina
                </button>
              </div>

              <!-- Render blocco -->
              <component
                :is="getBlockComponent(element.type)"
                :block="element"
                :editable="true"
                :rounded-corners="page.styles?.roundedCorners ?? true"
                @update="(updatedBlock) => updateBlockInline(index, updatedBlock)"
              />
            </div>
          </template>
        </draggable>
      </div>

      <!-- Pannello proprietà blocco -->
      <div
        v-if="selectedBlockIndex !== null"
        class="w-96 bg-white border-l border-gray-200 p-6 overflow-y-auto"
      >
        <div class="flex justify-between items-center mb-6">
          <h3 class="font-semibold text-gray-900 text-sm uppercase tracking-wide">
            Proprietà Blocco
          </h3>
          <button
            @click="selectedBlockIndex = null"
            class="text-gray-400 hover:text-gray-600 transition-colors text-xl"
          >
            ✕
          </button>
        </div>

        <BlockEditor
          v-if="selectedBlockIndex !== null"
          :block="page.blocks[selectedBlockIndex]"
          @update="updateBlock"
        />
      </div>

      <!-- Pannello impostazioni pagina -->
      <div
        v-else-if="showSettings"
        class="w-96 bg-white border-l border-gray-200 p-6 overflow-y-auto"
      >
        <div class="flex justify-between items-center mb-6">
          <h3 class="font-semibold text-gray-900 text-sm uppercase tracking-wide">
            Impostazioni Pagina
          </h3>
          <button
            @click="showSettings = false"
            class="text-gray-400 hover:text-gray-600 transition-colors text-xl"
          >
            ✕
          </button>
        </div>

        <PageSettings
          :page="page"
          @update="updatePageSettings"
        />
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed, watch } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { usePageStore } from '../stores/pageStore'
import draggable from 'vuedraggable'
import HeaderBlock from '../components/blocks/HeaderBlock.vue'
import HeroBlock from '../components/blocks/HeroBlock.vue'
import ImageSlideBlock from '../components/blocks/ImageSlideBlock.vue'
import TextBlock from '../components/blocks/TextBlock.vue'
import FormBlock from '../components/blocks/FormBlock.vue'
import TwoColumnTextImage from '../components/blocks/TwoColumnTextImage.vue'
import TwoColumnImageText from '../components/blocks/TwoColumnImageText.vue'
import FooterBlock from '../components/blocks/FooterBlock.vue'
import FeaturesBlock from '../components/blocks/FeaturesBlock.vue'
import ServicesGridBlock from '../components/blocks/ServicesGridBlock.vue'
import CtaBlock from '../components/blocks/CtaBlock.vue'
import BlockEditor from '../components/BlockEditor.vue'
import PageSettings from '../components/PageSettings.vue'
import { loadGoogleFont } from '../utils/googleFonts'

const router = useRouter()
const route = useRoute()
const pageStore = usePageStore()

const page = ref({
  title: 'Nuova Pagina',
  slug: '',
  meta_title: '',
  meta_description: '',
  is_published: false,
  styles: { backgroundColor: '#FFFFFF', blockGap: 15, fontFamily: '', roundedCorners: true },
  blocks: []
})

const selectedBlockIndex = ref(null)
const saveStatus = ref('saved') // 'saved', 'saving', 'error'
const showSettings = ref(false)
let saveTimeout = null
let isInitialLoad = ref(true)

const blockTypes = [
  { type: 'header', name: 'Intestazione', description: 'Header/Navbar con logo' },
  { type: 'hero', name: 'Hero', description: 'Sezione principale con titolo e CTA' },
  { type: 'image-slide', name: 'Diapositiva Immagine', description: 'Immagine a schermo intero con overlay opzionale' },
  { type: 'text', name: 'Testo', description: 'Blocco di testo semplice' },
  { type: 'features', name: 'Vantaggi', description: 'Griglia 3 colonne con icone e testo' },
  { type: 'services-grid', name: 'Servizi Grid', description: 'Griglia servizi con immagini' },
  { type: 'cta', name: 'Call to Action', description: 'Sezione con pulsante CTA centrato' },
  { type: 'two-column-text-image', name: 'Testo + Immagine', description: 'Testo a sinistra, immagine a destra' },
  { type: 'two-column-image-text', name: 'Immagine + Testo', description: 'Immagine a sinistra, testo a destra' },
  { type: 'form', name: 'Form', description: 'Form di contatto per lead' },
  { type: 'footer', name: 'Footer', description: 'Footer con info azienda e contatti' }
]

onMounted(async () => {
  if (route.params.id) {
    const data = await pageStore.fetchPage(route.params.id)
    if (data) {
      page.value = {
        ...data,
        styles: {
          backgroundColor: data.styles?.backgroundColor || '#FFFFFF',
          blockGap: data.styles?.blockGap ?? 15,
          fontFamily: data.styles?.fontFamily || '',
          roundedCorners: data.styles?.roundedCorners ?? true
        }
      }
      // Carica il font se specificato
      if (data.styles?.fontFamily) {
        loadGoogleFont(data.styles.fontFamily)
      }
    }
  }
  // Dopo il caricamento iniziale, abilita l'auto-save
  setTimeout(() => {
    isInitialLoad.value = false
  }, 1000)
})

// Carica il font quando cambia
watch(() => page.value.styles?.fontFamily, (newFont) => {
  if (newFont) {
    loadGoogleFont(newFont)
  }
})

// Auto-save con debounce
watch(
  page,
  () => {
    // Non salvare durante il caricamento iniziale
    if (isInitialLoad.value) return

    // Cancella il timeout precedente
    if (saveTimeout) {
      clearTimeout(saveTimeout)
    }

    // Imposta lo stato a "salvando..."
    saveStatus.value = 'saving'

    // Debounce di 1 secondo
    saveTimeout = setTimeout(async () => {
      await autoSave()
    }, 1000)
  },
  { deep: true }
)

const addBlock = (type) => {
  // Verifica se è un header e se ne esiste già uno
  if (type === 'header') {
    const hasHeader = page.value.blocks.some(block => block.type === 'header')
    if (hasHeader) {
      alert('Puoi aggiungere solo un\'intestazione per pagina')
      return
    }
  }

  // Verifica se è un footer e se ne esiste già uno
  if (type === 'footer') {
    const hasFooter = page.value.blocks.some(block => block.type === 'footer')
    if (hasFooter) {
      alert('Puoi aggiungere solo un footer per pagina')
      return
    }
  }

  const newBlock = {
    id: Date.now(),
    type,
    content: getDefaultContent(type),
    styles: {
      backgroundColor: 'transparent',
      textColor: 'inherit',
      padding: '40px 20px'
    },
    position: {},
    order: page.value.blocks.length
  }

  // Se è un header, aggiungi sempre all'inizio
  if (type === 'header') {
    page.value.blocks.unshift(newBlock)
    updateBlockOrder()
  }
  // Se è un footer, aggiungi sempre alla fine
  else if (type === 'footer') {
    page.value.blocks.push(newBlock)
    updateBlockOrder()
  } else {
    // Inserisci dopo l'header (se esiste) e prima del footer (se esiste)
    const headerIndex = page.value.blocks.findIndex(block => block.type === 'header')
    const footerIndex = page.value.blocks.findIndex(block => block.type === 'footer')

    if (footerIndex !== -1) {
      // Se esiste un footer, inserisci prima del footer
      page.value.blocks.splice(footerIndex, 0, newBlock)
      updateBlockOrder()
    } else if (headerIndex !== -1) {
      // Se esiste un header ma non un footer, inserisci dopo l'header
      page.value.blocks.splice(headerIndex + 1, 0, newBlock)
      updateBlockOrder()
    } else {
      // Nessun header o footer, aggiungi alla fine
      page.value.blocks.push(newBlock)
    }
  }
}

const getDefaultContent = (type) => {
  const defaults = {
    header: {
      logoUrl: '',
      logoAlt: 'Logo',
      logoLink: '/',
      logoHeight: '50px',
      marginTop: '0px',
      showMenu: false,
      menuLinks: []
    },
    hero: {
      title: 'Titolo Hero',
      subtitle: 'Sottotitolo',
      buttonText: 'Call to Action',
      buttonLink: '#',
      backgroundImage: ''
    },
    text: {
      title: 'Titolo Sezione',
      text: '<p>Inserisci qui il tuo testo...</p>'
    },
    'image-slide': {
      image: '',
      alt: 'Immagine diapositiva',
      height: '600px',
      fullWidth: true,
      showOverlay: false,
      overlayTitle: '',
      overlayText: '',
      overlayColor: '#000000',
      overlayOpacity: 0.5,
      overlayTextColor: '#FFFFFF'
    },
    features: {
      title: 'I Nostri Vantaggi',
      features: [
        {
          title: 'Velocità',
          description: 'Risposte rapide e servizio efficiente per tutte le tue esigenze.'
        },
        {
          title: 'Qualità',
          description: 'Standard elevati e attenzione ai dettagli in ogni progetto.'
        },
        {
          title: 'Affidabilità',
          description: 'Supporto costante e professionalità garantita.'
        }
      ]
    },
    'services-grid': {
      title: 'I Nostri Servizi',
      services: [
        {
          title: 'Servizio 1',
          description: 'Descrizione dettagliata del primo servizio offerto.',
          image: '',
          link: '#'
        },
        {
          title: 'Servizio 2',
          description: 'Descrizione dettagliata del secondo servizio offerto.',
          image: '',
          link: '#'
        },
        {
          title: 'Servizio 3',
          description: 'Descrizione dettagliata del terzo servizio offerto.',
          image: '',
          link: '#'
        }
      ]
    },
    cta: {
      title: 'Pronto per iniziare?',
      description: 'Contattaci oggi per una consulenza gratuita e senza impegno.',
      buttonText: 'Richiedi Consulenza',
      buttonLink: '#contatti',
      secondaryText: 'Nessun obbligo, rispondiamo in 24 ore'
    },
    'two-column-text-image': {
      title: 'Titolo Sezione',
      text: 'Inserisci qui il tuo testo. Questa sezione mostra testo a sinistra e un\'immagine a destra.',
      image: ''
    },
    'two-column-image-text': {
      title: 'Titolo Sezione',
      text: 'Inserisci qui il tuo testo. Questa sezione mostra un\'immagine a sinistra e testo a destra.',
      image: ''
    },
    form: {
      title: 'Contattaci',
      fields: [
        { name: 'name', label: 'Nome', type: 'text', required: true },
        { name: 'email', label: 'Email', type: 'email', required: true }
      ],
      buttonText: 'Invia'
    },
    footer: {
      companyTitle: 'La Nostra Azienda',
      companyName: 'La Tua Azienda',
      companyDescription: 'Descrizione breve della tua azienda e dei servizi offerti.',
      linksTitle: 'Link Utili',
      links: [
        { text: 'Home', url: '#' },
        { text: 'Servizi', url: '#' },
        { text: 'Chi Siamo', url: '#' },
        { text: 'Contatti', url: '#' }
      ],
      contactTitle: 'Contatti',
      contactText: '<p><strong>Email:</strong> <a href="mailto:info@example.com">info@example.com</a></p><p><strong>Telefono:</strong> <a href="tel:+39123456789">+39 123 456 7890</a></p><p><strong>Indirizzo:</strong> Via Example, 123 - 00100 Roma</p>',
      copyright: '© 2025 La Tua Azienda. Tutti i diritti riservati.'
    }
  }
  return defaults[type] || {}
}

const getBlockComponent = (type) => {
  const components = {
    header: HeaderBlock,
    hero: HeroBlock,
    'image-slide': ImageSlideBlock,
    text: TextBlock,
    features: FeaturesBlock,
    'services-grid': ServicesGridBlock,
    cta: CtaBlock,
    'two-column-text-image': TwoColumnTextImage,
    'two-column-image-text': TwoColumnImageText,
    form: FormBlock,
    footer: FooterBlock
  }
  return components[type] || TextBlock
}

const editBlock = (index) => {
  selectedBlockIndex.value = index
}

const deleteBlock = (index) => {
  if (confirm('Eliminare questo blocco?')) {
    page.value.blocks.splice(index, 1)
    updateBlockOrder()
  }
}

const updateBlock = (updatedBlock) => {
  if (selectedBlockIndex.value !== null) {
    page.value.blocks[selectedBlockIndex.value] = updatedBlock
  }
}

const updateBlockInline = (index, updatedBlock) => {
  page.value.blocks[index] = updatedBlock
}

const onBlockMove = (evt) => {
  // Previeni lo spostamento di header e footer
  const draggedBlock = page.value.blocks[evt.draggedContext.index]
  if (draggedBlock.type === 'header' || draggedBlock.type === 'footer') {
    return false
  }
  return true
}

const updateBlockOrder = () => {
  // Assicura che l'header sia sempre all'inizio
  const headerIndex = page.value.blocks.findIndex(block => block.type === 'header')
  if (headerIndex !== -1 && headerIndex !== 0) {
    const header = page.value.blocks.splice(headerIndex, 1)[0]
    page.value.blocks.unshift(header)
  }

  // Assicura che il footer sia sempre alla fine
  const footerIndex = page.value.blocks.findIndex(block => block.type === 'footer')
  if (footerIndex !== -1 && footerIndex !== page.value.blocks.length - 1) {
    const footer = page.value.blocks.splice(footerIndex, 1)[0]
    page.value.blocks.push(footer)
  }

  page.value.blocks.forEach((block, index) => {
    block.order = index
  })
}

const autoSave = async () => {
  try {
    // Generate slug if empty
    if (!page.value.slug) {
      page.value.slug = page.value.title
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-|-$/g, '')
    }

    if (route.params.id) {
      await pageStore.updatePage(route.params.id, page.value)
      saveStatus.value = 'saved'
    } else {
      const newPage = await pageStore.createPage(page.value)
      router.push(`/editor/${newPage.id}`)
      saveStatus.value = 'saved'
    }
  } catch (error) {
    console.error('Errore auto-save:', error)
    saveStatus.value = 'error'
  }
}

const goBack = () => {
  router.push('/')
}

const updatePageSettings = (updatedPage) => {
  page.value = { ...page.value, ...updatedPage }
  // Non chiudiamo più il pannello dopo il salvataggio
  // showSettings.value = false
}
</script>
