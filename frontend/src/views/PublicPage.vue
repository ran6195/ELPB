<template>
  <div v-if="loading" class="min-h-screen flex items-center justify-center bg-gray-50">
    <p class="text-gray-500">Caricamento...</p>
  </div>

  <div v-else-if="error" class="min-h-screen flex items-center justify-center bg-gray-50">
    <div class="text-center">
      <h1 class="text-4xl font-bold text-gray-900 mb-4">404</h1>
      <p class="text-gray-600">{{ error }}</p>
    </div>
  </div>

  <div v-else>
    <!-- Render blocks -->
    <div v-for="block in page.blocks" :key="block.id" :style="getBlockStyles(block)">
      <component
        :is="getBlockComponent(block.type)"
        :block="block"
        :editable="false"
      />
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import axios from 'axios'
import HeroBlock from '../components/blocks/HeroBlock.vue'
import TextBlock from '../components/blocks/TextBlock.vue'
import FormBlock from '../components/blocks/FormBlock.vue'
import TwoColumnTextImage from '../components/blocks/TwoColumnTextImage.vue'
import TwoColumnImageText from '../components/blocks/TwoColumnImageText.vue'
import FooterBlock from '../components/blocks/FooterBlock.vue'

const route = useRoute()
const page = ref({})
const loading = ref(true)
const error = ref(null)

onMounted(async () => {
  try {
    const response = await axios.get(`http://localhost:8000/api/page/${route.params.slug}`)
    page.value = response.data
  } catch (err) {
    error.value = err.response?.data?.error || 'Pagina non trovata'
  } finally {
    loading.value = false
  }
})

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
</script>
