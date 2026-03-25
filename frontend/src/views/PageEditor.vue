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
            v-if="page.id"
            @click="openPreview"
            class="bg-white hover:bg-gray-50 text-gray-700 px-6 py-2.5 rounded-lg font-medium transition-colors border border-gray-300 flex items-center gap-2"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
            </svg>
            Anteprima
          </button>

          <button
            @click="showSettings = !showSettings; selectedBlockIndex = null"
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

    <!-- Toolbar preview device -->
    <div class="bg-white border-b border-gray-200 px-8 py-3">
      <div class="max-w-7xl mx-auto flex items-center justify-between">
        <!-- Device selector -->
        <div class="flex items-center gap-2">
          <span class="text-xs font-medium text-gray-600 mr-2">Anteprima:</span>
          <button
            @click="viewMode = 'desktop'"
            :class="[
              'px-4 py-2 rounded-lg text-sm font-medium transition-all flex items-center gap-2',
              viewMode === 'desktop'
                ? 'bg-primary-600 text-white shadow-sm'
                : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
            ]"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
            </svg>
            Desktop
          </button>
          <button
            @click="viewMode = 'tablet'"
            :class="[
              'px-4 py-2 rounded-lg text-sm font-medium transition-all flex items-center gap-2',
              viewMode === 'tablet'
                ? 'bg-primary-600 text-white shadow-sm'
                : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
            ]"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
            </svg>
            Tablet
          </button>
          <button
            @click="viewMode = 'mobile'"
            :class="[
              'px-4 py-2 rounded-lg text-sm font-medium transition-all flex items-center gap-2',
              viewMode === 'mobile'
                ? 'bg-primary-600 text-white shadow-sm'
                : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
            ]"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
            </svg>
            Mobile
          </button>
        </div>

        <!-- Clean view toggle -->
        <button
          @click="cleanView = !cleanView"
          :class="[
            'px-4 py-2 rounded-lg text-sm font-medium transition-all flex items-center gap-2',
            cleanView
              ? 'bg-primary-600 text-white shadow-sm'
              : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
          ]"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
          </svg>
          {{ cleanView ? 'Mostra Pannelli' : 'Vista Pulita' }}
        </button>
      </div>
    </div>

    <div class="flex h-[calc(100vh-126px)]">
      <!-- Sidebar blocchi -->
      <div v-if="!cleanView" class="w-80 bg-white border-r border-gray-200 p-6 overflow-y-auto">
        <h3 class="font-semibold text-gray-900 mb-4 text-sm uppercase tracking-wide">
          Blocchi Disponibili
        </h3>

        <!-- Campo di ricerca -->
        <div class="mb-4">
          <input
            v-model="searchQuery"
            type="text"
            placeholder="Cerca blocchi..."
            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:border-primary-500 focus:ring-2 focus:ring-primary-200 outline-none transition-all"
          />
        </div>

        <!-- Lista blocchi filtrata -->
        <div class="space-y-3">
          <div v-if="filteredBlockTypes.length === 0" class="text-center py-8 text-gray-400 text-sm">
            Nessun blocco trovato
          </div>
          <button
            v-for="blockType in filteredBlockTypes"
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
      <div class="flex-1 overflow-y-auto bg-gray-100">
        <!-- Wrapper per simulare device -->
        <div
          v-if="viewMode === 'desktop'"
          class="w-full transition-all duration-300"
          :class="`container-width-${page.styles?.containerWidth || 'max-w-7xl'}`"
          :style="{
            backgroundColor: page.styles?.backgroundColor || '#FFFFFF',
            fontFamily: page.styles?.fontFamily || 'inherit',
            minHeight: '100%'
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
          :disabled="cleanView"
          class="min-h-screen flex flex-col"
          :style="{ gap: `${page.styles?.blockGap ?? 15}px` }"
        >
          <template #item="{ element, index }">
            <div
              :id="element.content?.anchor || undefined"
              :class="[
                'relative',
                !cleanView && 'group border-2 border-transparent hover:border-primary-300 transition-colors',
                !cleanView && (['header', 'footer', 'legal-footer'].includes(element.type) ? 'cursor-default' : 'cursor-move')
              ]"
            >
              <!-- Controlli blocco -->
              <div v-if="!cleanView" class="absolute top-4 right-4 opacity-0 group-hover:opacity-100 transition-opacity flex gap-3 z-10">
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
                :page="page"
                @update="(updatedBlock) => updateBlockInline(index, updatedBlock)"
              />
            </div>
          </template>
        </draggable>

        <!-- Quick Contacts (Desktop) -->
        <QuickContactBlock
          :block="{ type: 'quick-contact', content: page.quickContacts || {} }"
          :editable="true"
        />
        </div>

        <!-- Device Frame per Tablet -->
        <div
          v-else-if="viewMode === 'tablet'"
          class="mx-auto my-8 transition-all duration-300"
          style="width: 768px"
        >
          <!-- Device bezel/frame -->
          <div class="bg-gray-800 rounded-3xl shadow-2xl p-6 relative">
            <!-- Notch/camera tablet -->
            <div class="absolute top-2 left-1/2 transform -translate-x-1/2 w-20 h-1 bg-gray-600 rounded-full"></div>

            <!-- Screen area con scroll interno -->
            <div
              class="bg-white rounded-2xl overflow-y-auto viewport-tablet"
              :class="`container-width-${page.styles?.containerWidth || 'max-w-7xl'}`"
              style="height: 1024px"
              :style="{
                backgroundColor: page.styles?.backgroundColor || '#FFFFFF',
                fontFamily: page.styles?.fontFamily || 'inherit'
              }"
            >
              <div
                v-if="page.blocks.length === 0"
                class="flex items-center justify-center h-full text-gray-400"
              >
                <p class="text-base">Clicca su un blocco nella sidebar per iniziare</p>
              </div>

              <!-- Blocchi -->
              <draggable
                v-model="page.blocks"
                item-key="id"
                @end="updateBlockOrder"
                @move="onBlockMove"
                :disabled="cleanView"
                class="min-h-full flex flex-col"
                :style="{ gap: `${page.styles?.blockGap ?? 15}px` }"
              >
                <template #item="{ element, index }">
                  <div
                    :id="element.content?.anchor || undefined"
                    :class="[
                      'relative',
                      !cleanView && 'group border-2 border-transparent hover:border-primary-300 transition-colors',
                      !cleanView && (['header', 'footer', 'legal-footer'].includes(element.type) ? 'cursor-default' : 'cursor-move')
                    ]"
                  >
                    <!-- Controlli blocco -->
                    <div v-if="!cleanView" class="absolute top-4 right-4 opacity-0 group-hover:opacity-100 transition-opacity flex gap-3 z-10">
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
                      :page="page"
                      @update="(updatedBlock) => updateBlockInline(index, updatedBlock)"
                    />
                  </div>
                </template>
              </draggable>

              <!-- Quick Contacts (Tablet) -->
              <QuickContactBlock
                :block="{ type: 'quick-contact', content: page.quickContacts || {} }"
                :editable="true"
              />
            </div>
          </div>
        </div>

        <!-- Device Frame per Mobile -->
        <div
          v-else-if="viewMode === 'mobile'"
          class="mx-auto my-8 transition-all duration-300"
          style="width: 390px"
        >
          <!-- Device bezel/frame -->
          <div class="bg-gray-900 rounded-[3rem] shadow-2xl p-4 relative">
            <!-- Notch iPhone style -->
            <div class="absolute top-0 left-1/2 transform -translate-x-1/2 w-40 h-7 bg-gray-900 rounded-b-3xl z-20 flex items-center justify-center">
              <div class="w-16 h-4 bg-gray-800 rounded-full"></div>
            </div>

            <!-- Screen area con scroll interno -->
            <div
              class="bg-white rounded-[2.5rem] overflow-y-auto relative viewport-mobile"
              :class="`container-width-${page.styles?.containerWidth || 'max-w-7xl'}`"
              style="height: 844px"
              :style="{
                backgroundColor: page.styles?.backgroundColor || '#FFFFFF',
                fontFamily: page.styles?.fontFamily || 'inherit'
              }"
            >
              <div
                v-if="page.blocks.length === 0"
                class="flex items-center justify-center h-full text-gray-400"
              >
                <p class="text-sm text-center px-4">Clicca su un blocco nella sidebar per iniziare</p>
              </div>

              <!-- Blocchi -->
              <draggable
                v-model="page.blocks"
                item-key="id"
                @end="updateBlockOrder"
                @move="onBlockMove"
                :disabled="cleanView"
                class="min-h-full flex flex-col"
                :style="{ gap: `${page.styles?.blockGap ?? 15}px` }"
              >
                <template #item="{ element, index }">
                  <div
                    :id="element.content?.anchor || undefined"
                    :class="[
                      'relative',
                      !cleanView && 'group border-2 border-transparent hover:border-primary-300 transition-colors',
                      !cleanView && (['header', 'footer', 'legal-footer'].includes(element.type) ? 'cursor-default' : 'cursor-move')
                    ]"
                  >
                    <!-- Controlli blocco -->
                    <div v-if="!cleanView" class="absolute top-4 right-4 opacity-0 group-hover:opacity-100 transition-opacity flex gap-2 z-10">
                      <button
                        @click="editBlock(index)"
                        class="bg-white text-gray-700 px-3 py-1.5 rounded-lg shadow-md text-xs font-medium hover:bg-gray-50 border border-gray-300 transition-colors"
                      >
                        Modifica
                      </button>
                      <button
                        @click="deleteBlock(index)"
                        class="bg-white text-red-600 px-3 py-1.5 rounded-lg shadow-md text-xs font-medium hover:bg-red-50 border border-gray-300 transition-colors"
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
                      :page="page"
                      @update="(updatedBlock) => updateBlockInline(index, updatedBlock)"
                    />
                  </div>
                </template>
              </draggable>

              <!-- Quick Contacts (Mobile) -->
              <QuickContactBlock
                :block="{ type: 'quick-contact', content: page.quickContacts || {} }"
                :editable="true"
              />
            </div>

            <!-- Home indicator iOS -->
            <div class="absolute bottom-2 left-1/2 transform -translate-x-1/2 w-32 h-1 bg-gray-700 rounded-full"></div>
          </div>
        </div>
      </div>

      <!-- Pannello proprietà blocco -->
      <div
        v-if="selectedBlockIndex !== null && !cleanView"
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
          :page-blocks="page.blocks"
          @update="updateBlock"
        />
      </div>

      <!-- Pannello impostazioni pagina -->
      <div
        v-else-if="showSettings && !cleanView"
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
import HeroWideBlock from '../components/blocks/HeroWideBlock.vue'
import ImageSlideBlock from '../components/blocks/ImageSlideBlock.vue'
import TextBlock from '../components/blocks/TextBlock.vue'
import FormBlock from '../components/blocks/FormBlock.vue'
import AdvancedFormBlock from '../components/blocks/AdvancedFormBlock.vue'
import TwoColumnTextImage from '../components/blocks/TwoColumnTextImage.vue'
import TwoColumnImageText from '../components/blocks/TwoColumnImageText.vue'
import VideoBlock from '../components/blocks/VideoBlock.vue'
import VideoInfoBlock from '../components/blocks/VideoInfoBlock.vue'
import FooterBlock from '../components/blocks/FooterBlock.vue'
import LegalFooterBlock from '../components/blocks/LegalFooterBlock.vue'
import FeaturesBlock from '../components/blocks/FeaturesBlock.vue'
import ServicesGridBlock from '../components/blocks/ServicesGridBlock.vue'
import CtaBlock from '../components/blocks/CtaBlock.vue'
import SliderBlock from '../components/blocks/SliderBlock.vue'
import MapBlock from '../components/blocks/MapBlock.vue'
import SocialBlock from '../components/blocks/SocialBlock.vue'
import QuickContactBlock from '../components/blocks/QuickContactBlock.vue'
import BlockEditor from '../components/BlockEditor.vue'
import PageSettings from '../components/PageSettings.vue'
import { loadGoogleFont } from '../utils/googleFonts'

const router = useRouter()
const route = useRoute()
const pageStore = usePageStore()

const page = ref({
  title: 'Nuova Pagina',
  slug: 'nuova-pagina',
  meta_title: '',
  meta_description: '',
  is_published: false,
  styles: { backgroundColor: '#FFFFFF', blockGap: 15, fontFamily: '', roundedCorners: true, containerWidth: 'max-w-7xl' },
  blocks: [],
  quickContacts: {
    whatsapp: {
      enabled: false,
      number: '',
      message: 'Ciao! Vorrei maggiori informazioni',
      tooltip: 'Contattaci su WhatsApp',
      showText: false,
      text: 'WhatsApp',
      style: {
        backgroundColor: '#25D366',
        color: '#FFFFFF',
        bottom: '20px',
        right: '20px',
        width: '60px',
        height: '60px',
        borderRadius: '50%',
        fontSize: '24px'
      }
    },
    phone: {
      enabled: false,
      number: '',
      tooltip: 'Chiamaci ora',
      showText: false,
      text: 'Chiama',
      style: {
        backgroundColor: '#007BFF',
        color: '#FFFFFF',
        bottom: '20px',
        left: '20px',
        width: '60px',
        height: '60px',
        borderRadius: '50%',
        fontSize: '24px'
      }
    }
  }
})

const selectedBlockIndex = ref(null)
const saveStatus = ref('saved') // 'saved', 'saving', 'error'
const showSettings = ref(false)
const searchQuery = ref('')
const viewMode = ref('desktop') // 'desktop', 'tablet', 'mobile'
const cleanView = ref(false) // modalità vista pulita
let saveTimeout = null
let isInitialLoad = ref(true)

const blockTypes = [
  { type: 'header', name: 'Intestazione', description: 'Header/Navbar con logo' },
  { type: 'hero', name: 'Hero', description: 'Sezione principale con titolo e CTA' },
  { type: 'hero-wide', name: 'Hero Larghezza Variabile', description: 'Hero con larghezza del blocco personalizzabile' },
  { type: 'image-slide', name: 'Diapositiva Immagine', description: 'Immagine a schermo intero con overlay opzionale' },
  { type: 'video', name: 'Video', description: 'Video a schermo intero senza testo' },
  { type: 'text', name: 'Testo', description: 'Blocco di testo semplice' },
  { type: 'features', name: 'Vantaggi', description: 'Griglia 3 colonne con icone e testo' },
  { type: 'services-grid', name: 'Griglia Servizi', description: 'Griglia servizi con immagini' },
  { type: 'cta', name: 'Call to Action', description: 'Sezione con pulsante CTA centrato' },
  { type: 'two-column-text-image', name: 'Testo + Immagine', description: 'Testo a sinistra, immagine a destra' },
  { type: 'two-column-image-text', name: 'Immagine + Testo', description: 'Immagine a sinistra, testo a destra' },
  { type: 'video-info', name: 'Video + Info', description: 'Video a sinistra, informazioni a destra' },
  { type: 'slider', name: 'Slider', description: 'Slider di immagini configurabile con autoplay' },
  { type: 'map', name: 'Mappa Google', description: 'Mappa di Google Maps con info contatto' },
  { type: 'social', name: 'Social Media', description: 'Link a social media con icone personalizzabili' },
  { type: 'form', name: 'Form', description: 'Form di contatto per lead' },
  { type: 'form-avanzato', name: 'Form Avanzato', description: 'Form con campi completamente personalizzabili' },
  { type: 'footer', name: 'Footer', description: 'Footer con info azienda e contatti' },
  { type: 'legal-footer', name: 'Footer Legale', description: 'Footer con link legali e informazioni aziendali' }
]

// Computed property per filtrare i blocchi in base alla ricerca
const filteredBlockTypes = computed(() => {
  if (!searchQuery.value.trim()) {
    return blockTypes
  }

  const query = searchQuery.value.toLowerCase()
  return blockTypes.filter(block =>
    block.name.toLowerCase().includes(query) ||
    block.description.toLowerCase().includes(query)
  )
})

onMounted(async () => {
  if (route.params.id) {
    const data = await pageStore.fetchPage(route.params.id)
    if (data) {
      // Default quick contacts structure
      const defaultQuickContacts = {
        whatsapp: {
          enabled: false,
          number: '',
          message: 'Ciao! Vorrei maggiori informazioni',
          tooltip: 'Contattaci su WhatsApp',
          showText: false,
          text: 'WhatsApp',
          style: {
            backgroundColor: '#25D366',
            color: '#FFFFFF',
            bottom: '20px',
            right: '20px',
            width: '60px',
            height: '60px',
            borderRadius: '50%',
            fontSize: '24px'
          }
        },
        phone: {
          enabled: false,
          number: '',
          tooltip: 'Chiamaci ora',
          showText: false,
          text: 'Chiama',
          style: {
            backgroundColor: '#007BFF',
            color: '#FFFFFF',
            bottom: '20px',
            left: '20px',
            width: '60px',
            height: '60px',
            borderRadius: '50%',
            fontSize: '24px'
          }
        }
      }

      // Deep merge quickContacts with defaults
      const quickContacts = data.quickContacts ? {
        whatsapp: {
          ...defaultQuickContacts.whatsapp,
          ...(data.quickContacts.whatsapp || {}),
          style: {
            ...defaultQuickContacts.whatsapp.style,
            ...(data.quickContacts.whatsapp?.style || {})
          }
        },
        phone: {
          ...defaultQuickContacts.phone,
          ...(data.quickContacts.phone || {}),
          style: {
            ...defaultQuickContacts.phone.style,
            ...(data.quickContacts.phone?.style || {})
          }
        }
      } : defaultQuickContacts

      page.value = {
        ...data,
        blocks: data.blocks || [],  // Assicura che blocks sia sempre un array
        styles: {
          backgroundColor: data.styles?.backgroundColor || '#FFFFFF',
          blockGap: data.styles?.blockGap ?? 15,
          fontFamily: data.styles?.fontFamily || '',
          roundedCorners: data.styles?.roundedCorners ?? true
        },
        quickContacts: quickContacts
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

// Chiudi pannelli quando attivo vista pulita
watch(cleanView, (isClean) => {
  if (isClean) {
    selectedBlockIndex.value = null
    showSettings.value = false
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

  // Verifica se è un footer e se ne esiste già uno o un legal-footer
  if (type === 'footer') {
    const hasFooter = page.value.blocks.some(block => block.type === 'footer')
    if (hasFooter) {
      alert('Puoi aggiungere solo un footer per pagina')
      return
    }
    const hasLegalFooter = page.value.blocks.some(block => block.type === 'legal-footer')
    if (hasLegalFooter) {
      alert('Non puoi aggiungere un Footer standard se è già presente un Footer Legale.\n\nElimina prima il Footer Legale per aggiungere un Footer standard.')
      return
    }
  }

  // Verifica se è un legal-footer e se ne esiste già uno o un footer standard
  if (type === 'legal-footer') {
    const hasLegalFooter = page.value.blocks.some(block => block.type === 'legal-footer')
    if (hasLegalFooter) {
      alert('Puoi aggiungere solo un Footer Legale per pagina')
      return
    }
    const hasFooter = page.value.blocks.some(block => block.type === 'footer')
    if (hasFooter) {
      alert('Non puoi aggiungere un Footer Legale se è già presente un Footer standard.\n\nElimina prima il Footer standard per aggiungere un Footer Legale.')
      return
    }
  }

  // Verifica se è un form e se ne esiste già uno
  if (type === 'form') {
    const hasForm = page.value.blocks.some(block => block.type === 'form')
    if (hasForm) {
      alert('Puoi aggiungere solo un form per pagina')
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
      padding: '40px 20px',
      fontFamily: ''
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
  }
  // Se è un legal-footer, aggiungi sempre alla fine
  else if (type === 'legal-footer') {
    page.value.blocks.push(newBlock)
    updateBlockOrder()
  } else {
    // Inserisci dopo l'header (se esiste) e prima del footer/legal-footer (se esiste)
    const headerIndex = page.value.blocks.findIndex(block => block.type === 'header')
    const footerIndex = page.value.blocks.findIndex(block => block.type === 'footer')
    const legalFooterIndex = page.value.blocks.findIndex(block => block.type === 'legal-footer')

    // Trova l'indice del footer (normale o legale, quello che c'è)
    const anyFooterIndex = footerIndex !== -1 ? footerIndex : legalFooterIndex

    if (anyFooterIndex !== -1) {
      // Se esiste un footer (qualsiasi tipo), inserisci prima del footer
      page.value.blocks.splice(anyFooterIndex, 0, newBlock)
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
      menuLinks: [],
      socialLinks: {
        facebook: '',
        instagram: '',
        twitter: ''
      },
      iconStyle: 'monochrome',
      socialButtonStyle: {
        backgroundColor: 'transparent',
        color: '#FFFFFF',
        padding: '8px',
        borderWidth: '0px',
        borderColor: 'transparent',
        borderRadius: '50%'
      }
    },
    hero: {
      title: 'Titolo Hero',
      subtitle: 'Sottotitolo',
      buttonText: 'Call to Action',
      buttonLink: '#',
      backgroundImage: '',
      height: '400px',
      buttonStyle: {
        backgroundColor: '#4F46E5',
        textColor: '#FFFFFF',
        fontSize: '16px',
        padding: '12px 32px',
        borderRadius: '8px',
        borderWidth: '0px',
        borderColor: 'transparent',
        borderStyle: 'solid',
        shadow: 'md'
      }
    },
    'hero-wide': {
      title: 'Titolo Hero',
      subtitle: 'Sottotitolo',
      buttonText: 'Call to Action',
      buttonLink: '#',
      backgroundImage: '',
      height: '400px',
      blockWidth: '50%',
      buttonStyle: {
        backgroundColor: '#4F46E5',
        textColor: '#FFFFFF',
        fontSize: '16px',
        padding: '12px 32px',
        borderRadius: '8px',
        borderWidth: '0px',
        borderColor: 'transparent',
        borderStyle: 'solid',
        shadow: 'md'
      }
    },
    text: {
      title: 'Titolo Sezione',
      text: '<p>Inserisci qui il tuo testo...</p>',
      lineHeight: '1.625'
    },
    'image-slide': {
      image: '',
      alt: 'Immagine diapositiva',
      fullWidth: true,
      showOverlay: false,
      overlayTitle: '',
      overlayText: '',
      overlayColor: '#000000',
      overlayOpacity: 0.5,
      overlayTextColor: '#FFFFFF'
    },
    video: {
      videoUrl: '',
      height: '600px',
      fullWidth: true,
      autoplay: false,
      loop: false,
      muted: false,
      showControls: true,
      playOnScroll: false
    },
    features: {
      title: 'I Nostri Vantaggi',
      titleColor: '',
      featureTitleColor: '',
      featureTextColor: '#6B7280',
      features: [
        {
          icon: 'bolt',
          title: 'Velocità',
          description: 'Risposte rapide e servizio efficiente per tutte le tue esigenze.'
        },
        {
          icon: 'star',
          title: 'Qualità',
          description: 'Standard elevati e attenzione ai dettagli in ogni progetto.'
        },
        {
          icon: 'shield',
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
      description: '<p>Contattaci oggi per una consulenza gratuita e senza impegno.</p>',
      buttonText: 'Richiedi Consulenza',
      buttonLink: '#contatti',
      secondaryText: 'Nessun obbligo, rispondiamo in 24 ore',
      buttonStyle: {
        backgroundColor: '#4F46E5',
        textColor: '#FFFFFF',
        fontSize: '18px',
        padding: '16px 32px',
        borderRadius: '8px',
        borderWidth: '0px',
        borderColor: 'transparent',
        borderStyle: 'solid',
        shadow: 'lg'
      }
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
      caption: 'Compila il form e ti ricontatteremo al più presto',
      fields: [
        { name: 'name', label: 'Nome', type: 'text', required: true },
        { name: 'email', label: 'Email', type: 'email', required: true },
        { name: 'phone', label: 'Telefono', type: 'tel', required: true },
        { name: 'city', label: 'Città', type: 'text', required: true },
        { name: 'notes', label: 'Note', type: 'textarea', required: false }
      ],
      textareaPlaceholder: 'Scrivi qui il tuo messaggio...',
      buttonText: 'Invia',
      buttonLayout: 'full',
      fieldBorderRadius: 'lg',
      buttonStyle: {
        backgroundColor: '#4F46E5',
        textColor: '#FFFFFF',
        fontSize: '16px',
        padding: '12px 32px',
        borderRadius: '8px',
        borderWidth: '0px',
        borderColor: 'transparent',
        borderStyle: 'solid',
        shadow: 'md'
      },
      showPrivacy: true,
      privacyLink: '/privacy-policy',
      privacyTextColor: '#374151',
      thankYouUrl: ''
    },
    'form-avanzato': {
      title: 'Compila il form',
      caption: 'Ti ricontatteremo al più presto',
      fields: [
        { id: 'f1', name: 'nome_cognome', label: 'Nome e Cognome', type: 'text', required: true, colSpan: 'full', placeholder: 'Nome e Cognome *', standard_field: 'name' },
        { id: 'f2', name: 'email', label: 'Email', type: 'email', required: true, colSpan: 'half', placeholder: 'Email *', standard_field: 'email' },
        { id: 'f3', name: 'telefono', label: 'Telefono', type: 'tel', required: true, colSpan: 'half', placeholder: 'Telefono *', standard_field: 'phone' },
        { id: 'f4', name: 'messaggio', label: 'Messaggio', type: 'textarea', required: false, colSpan: 'full', placeholder: 'Scrivi il tuo messaggio qui (opzionale)', standard_field: 'message' }
      ],
      buttonText: 'Invia',
      buttonLayout: 'full',
      fieldBorderRadius: 'lg',
      buttonStyle: {
        backgroundColor: '#4F46E5',
        textColor: '#FFFFFF',
        fontSize: '16px',
        padding: '12px 32px',
        borderRadius: '8px',
        borderWidth: '0px',
        borderColor: 'transparent',
        borderStyle: 'solid',
        shadow: 'md'
      },
      showPrivacy: true,
      privacyLink: '/privacy-policy',
      privacyTextColor: '#374151',
      thankYouUrl: ''
    },
    'video-info': {
      videoUrl: '',
      title: 'Visita il nostro Showroom',
      subtitle: 'Viale Lazio 124/126',
      mapImage: '',
      mapLink: 'https://maps.google.com',
      scheduleText: '<p><strong>Orari di apertura</strong><br>Tutti i giorni dalle 09.30 alle 13.00 e dalle 16.00 alle 20.00.<br>Sabato dalle 9.30 alle 13.00 Pomeriggio chiusi.<br>Domenica chiuso.</p>'
    },
    slider: {
      title: 'La Nostra Gallery',
      slides: [
        {
          image: 'https://placehold.co/800x800',
          alt: 'Slide 1',
          title: 'Primo Progetto',
          description: 'Descrizione del primo progetto realizzato'
        },
        {
          image: 'https://placehold.co/800x800',
          alt: 'Slide 2',
          title: 'Secondo Progetto',
          description: 'Descrizione del secondo progetto realizzato'
        },
        {
          image: 'https://placehold.co/800x800',
          alt: 'Slide 3',
          title: 'Terzo Progetto',
          description: 'Descrizione del terzo progetto realizzato'
        }
      ],
      autoplay: true,
      autoplayDelay: 3000,
      loop: true,
      slidesPerViewDesktop: 3,
      slideGap: 20,
      slideHeight: '',
      slideAspectRatio: 'square',
      showNavigation: true,
      showPagination: true
    },
    map: {
      title: 'Dove Siamo',
      description: 'Vieni a trovarci nel nostro showroom',
      mapUrl: '',
      height: '450px',
      zoom: 15,
      showContactInfo: true,
      address: 'Via Example, 123 - 00100 Roma',
      phone: '+39 123 456 7890',
      email: 'info@example.com'
    },
    social: {
      facebookUrl: '',
      instagramUrl: '',
      xUrl: '',
      linkedinUrl: '',
      youtubeUrl: '',
      iconStyle: 'colored',
      buttonSize: 48,
      buttonSpacing: 16,
      buttonBackground: 'transparent',
      borderRadius: 8,
      shadow: 'md',
      borderWidth: 0,
      borderColor: '#e5e7eb',
      borderStyle: 'solid'
    },
    footer: {
      fullWidth: true, // Larghezza completa di default
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
    },
    'legal-footer': {
      fullWidth: true,
      // Non salvare legalLinks - verranno calcolati dinamicamente dal componente
      legalText: 'ARAN CUCINE PALERMO - VIALE LAZIO, 124/126 90144 PALERMO (PA), <a href="mailto:info@arancucinepalermo.it">info@arancucinepalermo.it</a>, <a href="tel:+390915142890">091 514 289</a><br>è un\'iniziativa INTERLINEA SRL, VIALE AIACE, 138 90151 PALERMO - P.IVA e C.F: 05472210821 - Tutti i diritti riservati - sito realizzato da <a href="https://www.edysma.com/dachi.php?mit=arancucinepalermo" target="_blank">EDYSMA</a> e <a href="https://www.fm-marketing.it/" target="_blank">FM MARKETING</a> in conformità agli standards di accessibilità e di Responsive Web Design (RWD)'
    }
  }
  return defaults[type] || {}
}

const getBlockComponent = (type) => {
  const components = {
    header: HeaderBlock,
    hero: HeroBlock,
    'hero-wide': HeroWideBlock,
    'image-slide': ImageSlideBlock,
    video: VideoBlock,
    text: TextBlock,
    features: FeaturesBlock,
    'services-grid': ServicesGridBlock,
    cta: CtaBlock,
    'two-column-text-image': TwoColumnTextImage,
    'two-column-image-text': TwoColumnImageText,
    'video-info': VideoInfoBlock,
    slider: SliderBlock,
    map: MapBlock,
    social: SocialBlock,
    form: FormBlock,
    'form-avanzato': AdvancedFormBlock,
    footer: FooterBlock,
    'legal-footer': LegalFooterBlock
  }
  return components[type] || TextBlock
}

const editBlock = (index) => {
  selectedBlockIndex.value = index
  showSettings.value = false // Chiude impostazioni pagina quando si seleziona un blocco
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
  // Previeni lo spostamento di header, footer e legal-footer
  const draggedBlock = page.value.blocks[evt.draggedContext.index]
  if (draggedBlock.type === 'header' || draggedBlock.type === 'footer' || draggedBlock.type === 'legal-footer') {
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

  // Assicura che il legal-footer sia sempre all'ultima posizione
  const legalFooterIndex = page.value.blocks.findIndex(block => block.type === 'legal-footer')
  if (legalFooterIndex !== -1 && legalFooterIndex !== page.value.blocks.length - 1) {
    const legalFooter = page.value.blocks.splice(legalFooterIndex, 1)[0]
    page.value.blocks.push(legalFooter)
  }

  // Assicura che il footer sia sempre alla fine (prima del legal-footer se presente)
  const footerIndex = page.value.blocks.findIndex(block => block.type === 'footer')
  const hasLegalFooter = page.value.blocks.some(block => block.type === 'legal-footer')
  const targetFooterPosition = hasLegalFooter ? page.value.blocks.length - 2 : page.value.blocks.length - 1
  if (footerIndex !== -1 && footerIndex !== targetFooterPosition) {
    const footer = page.value.blocks.splice(footerIndex, 1)[0]
    if (hasLegalFooter) {
      // Inserisci prima del legal-footer
      page.value.blocks.splice(page.value.blocks.length - 1, 0, footer)
    } else {
      page.value.blocks.push(footer)
    }
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
      // Update local page with data from backend (including unique slug)
      page.value = {
        ...page.value,
        ...newPage,
        blocks: newPage.blocks || page.value.blocks || [],  // Assicura che blocks sia sempre un array
        styles: {
          ...page.value.styles,
          ...(newPage.styles || {})
        },
        quickContacts: {
          ...page.value.quickContacts,
          ...(newPage.quickContacts || {})
        }
      }
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

const openPreview = () => {
  if (page.value.id) {
    const routeData = router.resolve(`/preview/${page.value.id}`)
    window.open(routeData.href, '_blank')
  }
}

const updatePageSettings = (updatedPage) => {
  console.log('PageEditor - updatePageSettings received:', JSON.parse(JSON.stringify(updatedPage)))
  page.value = { ...page.value, ...updatedPage }
  console.log('PageEditor - page.value after update:', JSON.parse(JSON.stringify(page.value)))
  // Non chiudiamo più il pannello dopo il salvataggio
  // showSettings.value = false
}
</script>

<style scoped>
/* Container width override - sovrascrive max-w-7xl nei blocchi */
.container-width-max-w-4xl :deep(.max-w-7xl) {
  max-width: 56rem !important; /* 896px */
}

.container-width-max-w-5xl :deep(.max-w-7xl) {
  max-width: 64rem !important; /* 1024px */
}

.container-width-max-w-6xl :deep(.max-w-7xl) {
  max-width: 72rem !important; /* 1152px */
}

.container-width-max-w-7xl :deep(.max-w-7xl) {
  max-width: 80rem !important; /* 1280px - default */
}

.container-width-max-w-full :deep(.max-w-7xl) {
  max-width: 100% !important;
}

/* Forza layout mobile/tablet nelle viste simulate */
.viewport-mobile :deep(.grid),
.viewport-tablet :deep(.grid) {
  /* Forza tutte le grid a 1 colonna nelle viste mobile/tablet */
  grid-template-columns: repeat(1, minmax(0, 1fr)) !important;
  gap: 1.5rem !important; /* 24px - equivalente a gap-6 */
}

/* Azzera col-span nelle viste simulate (evita colonne implicite) */
.viewport-mobile :deep([class*="col-span"]),
.viewport-tablet :deep([class*="col-span"]) {
  grid-column: span 1 / span 1 !important;
}

/* Forza anche padding e font size ridotti */
.viewport-mobile :deep(h2),
.viewport-mobile :deep(h3) {
  font-size: 1.5rem !important; /* text-2xl */
}

.viewport-mobile :deep(p) {
  font-size: 0.875rem !important; /* text-sm */
}

.viewport-tablet :deep(h2),
.viewport-tablet :deep(h3) {
  font-size: 1.5rem !important; /* text-2xl */
}

.viewport-tablet :deep(p) {
  font-size: 0.875rem !important; /* text-sm */
}
</style>
