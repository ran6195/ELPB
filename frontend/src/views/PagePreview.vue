<template>
  <div
    class="min-h-screen"
    :class="`container-width-${page?.styles?.containerWidth || 'max-w-7xl'}`"
    :style="{
      backgroundColor: page?.styles?.backgroundColor || '#FFFFFF',
      fontFamily: page?.styles?.fontFamily || 'inherit'
    }"
  >
    <div v-if="pageStore.loading" class="flex items-center justify-center h-screen">
      <p class="text-gray-600">Caricamento...</p>
    </div>

    <div v-else-if="page" class="flex flex-col" :style="{ gap: `${page.styles?.blockGap ?? 15}px` }">
      <!-- Render blocks -->
      <div
        v-for="block in sortedBlocks"
        :key="block.id"
      >
        <component
          :is="getBlockComponent(block.type)"
          :block="block"
          :editable="false"
          :rounded-corners="page.styles?.roundedCorners ?? true"
          :page="page"
        />
      </div>

      <!-- Quick Contacts -->
      <QuickContactBlock
        v-if="page.quickContacts"
        :block="{ type: 'quick-contact', content: page.quickContacts }"
        :editable="false"
      />
    </div>

    <div v-else class="flex items-center justify-center h-screen">
      <p class="text-gray-600">Pagina non trovata</p>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { usePageStore } from '../stores/pageStore'
import { loadGoogleFont } from '../utils/googleFonts'
import HeaderBlock from '../components/blocks/HeaderBlock.vue'
import HeroBlock from '../components/blocks/HeroBlock.vue'
import ImageSlideBlock from '../components/blocks/ImageSlideBlock.vue'
import TextBlock from '../components/blocks/TextBlock.vue'
import FormBlock from '../components/blocks/FormBlock.vue'
import TwoColumnTextImage from '../components/blocks/TwoColumnTextImage.vue'
import TwoColumnImageText from '../components/blocks/TwoColumnImageText.vue'
import VideoBlock from '../components/blocks/VideoBlock.vue'
import VideoInfoBlock from '../components/blocks/VideoInfoBlock.vue'
import FooterBlock from '../components/blocks/FooterBlock.vue'
import FeaturesBlock from '../components/blocks/FeaturesBlock.vue'
import ServicesGridBlock from '../components/blocks/ServicesGridBlock.vue'
import CtaBlock from '../components/blocks/CtaBlock.vue'
import SliderBlock from '../components/blocks/SliderBlock.vue'
import MapBlock from '../components/blocks/MapBlock.vue'
import QuickContactBlock from '../components/blocks/QuickContactBlock.vue'
import SocialBlock from '../components/blocks/SocialBlock.vue'
import LegalFooterBlock from '../components/blocks/LegalFooterBlock.vue'

const route = useRoute()
const pageStore = usePageStore()
const page = ref(null)

onMounted(async () => {
  const data = await pageStore.fetchPage(route.params.id)
  if (data) {
    page.value = data
    // Set meta tags
    document.title = data.meta_title || data.title
    if (data.meta_description) {
      const metaDesc = document.querySelector('meta[name="description"]')
      if (metaDesc) {
        metaDesc.setAttribute('content', data.meta_description)
      }
    }
    // Set robots noindex meta tag
    let metaRobots = document.querySelector('meta[name="robots"]')
    if (!metaRobots) {
      metaRobots = document.createElement('meta')
      metaRobots.setAttribute('name', 'robots')
      document.head.appendChild(metaRobots)
    }
    metaRobots.setAttribute('content', 'noindex')

    // Raccoglie tutti i font unici dalla pagina e dai blocchi
    const allFonts = new Set()

    // Font della pagina
    if (data.styles?.fontFamily) {
      allFonts.add(data.styles.fontFamily)
    }

    // Font dei blocchi
    if (data.blocks && Array.isArray(data.blocks)) {
      data.blocks.forEach(block => {
        if (block.styles?.fontFamily) {
          allFonts.add(block.styles.fontFamily)
        }
      })
    }

    // Carica tutti i font unici
    allFonts.forEach(font => {
      loadGoogleFont(font)
    })

    // Carica Cookie Consent CSS
    let cookieConsentLink = document.querySelector('link[href="/cookieconsent/cookieconsent.css"]')
    if (!cookieConsentLink) {
      cookieConsentLink = document.createElement('link')
      cookieConsentLink.setAttribute('rel', 'stylesheet')
      cookieConsentLink.setAttribute('href', '/cookieconsent/cookieconsent.css')
      document.head.appendChild(cookieConsentLink)
    }
  }
})

const sortedBlocks = computed(() => {
  if (!page.value?.blocks) return []
  return [...page.value.blocks].sort((a, b) => a.order - b.order)
})

const getBlockComponent = (type) => {
  const components = {
    header: HeaderBlock,
    hero: HeroBlock,
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
    form: FormBlock,
    footer: FooterBlock,
    social: SocialBlock,
    'legal-footer': LegalFooterBlock
  }
  return components[type] || TextBlock
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
</style>
