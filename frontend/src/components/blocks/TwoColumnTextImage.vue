<template>
  <div>
    <div
      :class="[
        'max-w-7xl mx-auto px-4 sm:px-6 py-8 sm:py-12',
        roundedCorners ? 'rounded-lg' : ''
      ]"
      :style="blockStyles"
    >
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 md:gap-8 items-center">
      <!-- Colonna testo (sinistra) -->
      <div>
        <h2
          :contenteditable="editable"
          @blur="updateContent('title', $event.target.innerText)"
          class="text-2xl sm:text-3xl font-bold text-gray-900 mb-3 sm:mb-4 outline-none focus:ring-2 focus:ring-primary-300 rounded px-2"
          :style="titleFontSize ? { fontSize: titleFontSize } : {}"
        >
          {{ block.content.title }}
        </h2>
        <p
          :contenteditable="editable"
          @blur="updateContent('text', $event.target.innerText)"
          class="text-sm sm:text-base text-gray-600 leading-relaxed outline-none focus:ring-2 focus:ring-primary-300 rounded px-2"
        >
          {{ block.content.text }}
        </p>
      </div>

      <!-- Colonna immagine (destra) -->
      <div class="relative">
        <img
          v-if="block.content.image"
          :src="block.content.image"
          alt="Immagine sezione"
          :class="[
            'w-full h-auto shadow-lg',
            roundedCorners ? 'rounded-lg' : ''
          ]"
        />
        <div
          v-else
          :class="[
            'w-full h-64 bg-gray-200 flex items-center justify-center',
            roundedCorners ? 'rounded-lg' : ''
          ]"
        >
          <p class="text-gray-400">Carica un'immagine</p>
        </div>
      </div>
    </div>
    </div>
  </div>
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
  }
})

const emit = defineEmits(['update'])

const titleSizeMap = { xl: '1.25rem', '2xl': '1.5rem', '3xl': '1.875rem', '4xl': '2.25rem', '5xl': '3rem', '6xl': '3.75rem' }
const titleFontSize = computed(() => titleSizeMap[props.block.content.titleSize] || null)

const blockStyles = computed(() => {
  const styles = props.block.styles || {}
  return {
    backgroundColor: styles.backgroundColor || 'transparent',
    color: styles.textColor || 'inherit',
    padding: styles.padding || undefined,
    fontFamily: styles.fontFamily || undefined
  }
})

const updateContent = (field, value) => {
  const updatedBlock = {
    ...props.block,
    content: {
      ...props.block.content,
      [field]: value
    }
  }
  emit('update', updatedBlock)
}
</script>
