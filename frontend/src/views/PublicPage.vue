<template>
  <div
    class="min-h-screen"
    :style="{
      backgroundColor: page?.styles?.backgroundColor || '#FFFFFF',
      fontFamily: page?.styles?.fontFamily || 'inherit'
    }"
  >
    <div v-if="loading" class="flex items-center justify-center h-screen">
      <p class="text-gray-500">Caricamento...</p>
    </div>

    <div v-else-if="error" class="flex items-center justify-center h-screen">
      <div class="text-center">
        <h1 class="text-4xl font-bold text-gray-900 mb-4">404</h1>
        <p class="text-gray-600">{{ error }}</p>
      </div>
    </div>

    <div v-else class="flex flex-col" :style="{ gap: `${page.styles?.blockGap ?? 15}px` }">
      <!-- Render blocks -->
      <div v-for="block in page.blocks" :key="block.id">
        <component
          :is="getBlockComponent(block.type)"
          :block="block"
          :editable="false"
        />
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import axios from 'axios'
import { loadGoogleFont } from '../utils/googleFonts'
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

const route = useRoute()
const page = ref({})
const loading = ref(true)
const error = ref(null)

const API_URL = import.meta.env.VITE_API_URL || 'http://localhost:8000/api'

onMounted(async () => {
  try {
    const response = await axios.get(`${API_URL}/page/${route.params.slug}`)
    page.value = response.data
    // Carica il font se specificato
    if (response.data.styles?.fontFamily) {
      loadGoogleFont(response.data.styles.fontFamily)
    }
  } catch (err) {
    error.value = err.response?.data?.error || 'Pagina non trovata'
  } finally {
    loading.value = false
  }
})

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
</script>
