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
        <div class="flex gap-3">
          <button
            @click="showSettings = !showSettings"
            class="bg-white hover:bg-gray-50 text-gray-700 px-6 py-2.5 rounded-lg font-medium transition-colors border border-gray-300"
          >
            ⚙️ Impostazioni
          </button>
          <button
            @click="savePage"
            :disabled="saving"
            class="bg-primary-600 hover:bg-primary-700 text-white px-8 py-2.5 rounded-lg font-medium transition-colors disabled:opacity-50 shadow-sm"
          >
            {{ saving ? 'Salvataggio...' : 'Salva' }}
          </button>
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
      <div class="flex-1 overflow-y-auto p-8">
        <div class="max-w-5xl mx-auto bg-white rounded-lg border border-gray-200 min-h-screen shadow-sm">
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
            class="space-y-0"
          >
            <template #item="{ element, index }">
              <div
                class="relative group border-2 border-transparent hover:border-primary-300 transition-colors cursor-move my-4"
                :style="getBlockStyles(element)"
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
                />
              </div>
            </template>
          </draggable>
        </div>
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
import { ref, onMounted, computed } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { usePageStore } from '../stores/pageStore'
import draggable from 'vuedraggable'
import HeroBlock from '../components/blocks/HeroBlock.vue'
import TextBlock from '../components/blocks/TextBlock.vue'
import FormBlock from '../components/blocks/FormBlock.vue'
import TwoColumnTextImage from '../components/blocks/TwoColumnTextImage.vue'
import TwoColumnImageText from '../components/blocks/TwoColumnImageText.vue'
import FooterBlock from '../components/blocks/FooterBlock.vue'
import BlockEditor from '../components/BlockEditor.vue'
import PageSettings from '../components/PageSettings.vue'

const router = useRouter()
const route = useRoute()
const pageStore = usePageStore()

const page = ref({
  title: 'Nuova Pagina',
  slug: '',
  meta_title: '',
  meta_description: '',
  is_published: false,
  blocks: []
})

const selectedBlockIndex = ref(null)
const saving = ref(false)
const showSettings = ref(false)

const blockTypes = [
  { type: 'hero', name: 'Hero', description: 'Sezione principale con titolo e CTA' },
  { type: 'text', name: 'Testo', description: 'Blocco di testo semplice' },
  { type: 'two-column-text-image', name: 'Testo + Immagine', description: 'Testo a sinistra, immagine a destra' },
  { type: 'two-column-image-text', name: 'Immagine + Testo', description: 'Immagine a sinistra, testo a destra' },
  { type: 'form', name: 'Form', description: 'Form di contatto per lead' },
  { type: 'footer', name: 'Footer', description: 'Footer con info azienda e contatti' }
]

onMounted(async () => {
  if (route.params.id) {
    const data = await pageStore.fetchPage(route.params.id)
    if (data) {
      page.value = { ...data }
    }
  }
})

const addBlock = (type) => {
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
    styles: {},
    position: {},
    order: page.value.blocks.length
  }

  // Se è un footer, aggiungi sempre alla fine
  if (type === 'footer') {
    page.value.blocks.push(newBlock)
  } else {
    // Se esiste un footer, inserisci prima del footer
    const footerIndex = page.value.blocks.findIndex(block => block.type === 'footer')
    if (footerIndex !== -1) {
      page.value.blocks.splice(footerIndex, 0, newBlock)
      updateBlockOrder()
    } else {
      page.value.blocks.push(newBlock)
    }
  }
}

const getDefaultContent = (type) => {
  const defaults = {
    hero: {
      title: 'Titolo Hero',
      subtitle: 'Sottotitolo',
      buttonText: 'Call to Action',
      buttonLink: '#',
      backgroundImage: ''
    },
    text: {
      title: 'Titolo Sezione',
      text: 'Inserisci qui il tuo testo...'
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
      email: 'info@example.com',
      phone: '+39 123 456 7890',
      address: 'Via Example, 123 - 00100 Roma',
      copyright: '© 2025 La Tua Azienda. Tutti i diritti riservati.'
    }
  }
  return defaults[type] || {}
}

const getBlockComponent = (type) => {
  const components = {
    hero: HeroBlock,
    text: TextBlock,
    'two-column-text-image': TwoColumnTextImage,
    'two-column-image-text': TwoColumnImageText,
    form: FormBlock,
    footer: FooterBlock
  }
  return components[type] || TextBlock
}

const getBlockStyles = (block) => {
  const styles = block.styles || {}
  return {
    backgroundColor: styles.backgroundColor || 'transparent',
    color: styles.textColor || 'inherit',
    padding: styles.padding || '40px 20px'
  }
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

const updateBlockOrder = () => {
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

const savePage = async () => {
  saving.value = true
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
    } else {
      const newPage = await pageStore.createPage(page.value)
      router.push(`/editor/${newPage.id}`)
    }
    alert('Pagina salvata con successo!')
  } catch (error) {
    alert('Errore durante il salvataggio')
  } finally {
    saving.value = false
  }
}

const goBack = () => {
  router.push('/')
}

const updatePageSettings = (updatedPage) => {
  page.value = { ...page.value, ...updatedPage }
  showSettings.value = false
}
</script>
