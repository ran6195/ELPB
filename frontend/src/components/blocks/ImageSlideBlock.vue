<template>
  <div class="image-slide-block">
    <!-- Wrapper condizionale per larghezza limitata -->
    <div :class="block.content.fullWidth === false ? 'max-w-7xl mx-auto' : ''">
      <div
        :class="[
          'relative w-full overflow-hidden',
          roundedCorners ? 'rounded-lg' : ''
        ]"
      >
        <!-- Immagine -->
        <img
          v-if="block.content.image"
          :src="block.content.image"
          :alt="block.content.alt || 'Immagine diapositiva'"
          class="w-full h-auto"
        />

        <!-- Placeholder se non c'è immagine -->
        <div
          v-else
          class="w-full flex items-center justify-center bg-gray-200 py-24"
        >
          <div class="text-center text-gray-400">
            <svg class="w-24 h-24 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
            </svg>
            <p class="text-lg font-medium">Carica un'immagine</p>
            <p class="text-sm mt-2">
              L'immagine manterrà le sue proporzioni originali
            </p>
          </div>
        </div>

        <!-- Overlay con testo opzionale -->
        <div
          v-if="block.content.showOverlay && (block.content.overlayTitle || block.content.overlayText)"
          class="absolute inset-0 flex items-center justify-center"
          :style="overlayStyles"
        >
          <div class="text-center px-6 max-w-4xl">
            <h2
              v-if="block.content.overlayTitle"
              :contenteditable="editable"
              @blur="updateContent('overlayTitle', $event.target.innerText)"
              class="text-4xl md:text-6xl font-bold mb-4 outline-none"
              :style="{ color: block.content.overlayTextColor || '#FFFFFF' }"
            >
              {{ block.content.overlayTitle }}
            </h2>
            <p
              v-if="block.content.overlayText"
              :contenteditable="editable"
              @blur="updateContent('overlayText', $event.target.innerText)"
              class="text-xl md:text-2xl outline-none"
              :style="{ color: block.content.overlayTextColor || '#FFFFFF' }"
            >
              {{ block.content.overlayText }}
            </p>
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

const overlayStyles = computed(() => {
  const opacity = props.block.content.overlayOpacity || 0.5
  const color = props.block.content.overlayColor || '#000000'

  // Converti hex a rgba
  const hex = color.replace('#', '')
  const r = parseInt(hex.substring(0, 2), 16)
  const g = parseInt(hex.substring(2, 4), 16)
  const b = parseInt(hex.substring(4, 6), 16)

  return {
    backgroundColor: `rgba(${r}, ${g}, ${b}, ${opacity})`
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

<style scoped>
[contenteditable="true"]:focus {
  outline: 2px solid #0ea5e9;
  outline-offset: 2px;
  border-radius: 4px;
}

[contenteditable="true"]:hover {
  background: rgba(14, 165, 233, 0.05);
}
</style>
