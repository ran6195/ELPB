<template>
  <div class="min-h-screen">
    <div v-if="pageStore.loading" class="flex items-center justify-center h-screen">
      <p class="text-gray-600">Caricamento...</p>
    </div>

    <div v-else-if="page">
      <!-- Render blocks -->
      <div
        v-for="block in sortedBlocks"
        :key="block.id"
        :style="getBlockStyles(block)"
      >
        <component
          :is="getBlockComponent(block.type)"
          :block="block"
          :editable="false"
        />
      </div>
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
import HeroBlock from '../components/blocks/HeroBlock.vue'
import TextBlock from '../components/blocks/TextBlock.vue'
import FormBlock from '../components/blocks/FormBlock.vue'
import TwoColumnTextImage from '../components/blocks/TwoColumnTextImage.vue'
import TwoColumnImageText from '../components/blocks/TwoColumnImageText.vue'
import FooterBlock from '../components/blocks/FooterBlock.vue'

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
  }
})

const sortedBlocks = computed(() => {
  if (!page.value?.blocks) return []
  return [...page.value.blocks].sort((a, b) => a.order - b.order)
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
