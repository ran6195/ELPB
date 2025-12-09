<template>
  <div>
    <div
      :class="[
        'w-full',
        roundedCorners ? 'rounded-lg overflow-hidden' : ''
      ]"
      :style="blockStyles"
    >
      <div class="max-w-7xl mx-auto">
        <div class="grid md:grid-cols-2 gap-0">
          <!-- Colonna video (sinistra) -->
          <div class="flex items-center justify-center p-8 bg-gray-900">
            <div class="w-full">
              <video
                v-if="block.content.videoUrl"
                controls
                autoplay
                muted
                playsinline
                loop
                class="w-full h-auto mx-auto max-w-md"
              >
                <source :src="block.content.videoUrl" type="video/mp4">
                Il tuo browser non supporta il tag video.
              </video>
              <div
                v-else
                class="w-full h-96 bg-gray-800 flex items-center justify-center rounded"
              >
                <p class="text-gray-400">Inserisci URL video</p>
              </div>
            </div>
          </div>

          <!-- Colonna info (destra) -->
          <div class="flex flex-col justify-center p-8 bg-gray-900 text-white">
            <h2
              :contenteditable="editable"
              @blur="updateContent('title', $event.target.innerText)"
              class="text-2xl font-bold mb-2 outline-none focus:ring-2 focus:ring-blue-500 rounded px-2"
            >
              {{ block.content.title }}
            </h2>
            <p
              :contenteditable="editable"
              @blur="updateContent('subtitle', $event.target.innerText)"
              class="text-lg mb-4 outline-none focus:ring-2 focus:ring-blue-500 rounded px-2"
            >
              {{ block.content.subtitle }}
            </p>

            <!-- Mappa -->
            <div class="mb-4">
              <a
                v-if="block.content.mapImage"
                :href="block.content.mapLink || '#'"
                target="_blank"
                class="inline-block"
              >
                <img
                  :src="block.content.mapImage"
                  alt="Mappa"
                  class="w-3/4 h-auto rounded shadow-lg hover:opacity-90 transition-opacity"
                />
              </a>
              <div
                v-else
                class="w-3/4 h-48 bg-gray-800 flex items-center justify-center rounded"
              >
                <p class="text-gray-400 text-sm">Carica immagine mappa</p>
              </div>
            </div>

            <!-- Orari -->
            <div class="border-t border-gray-700 pt-4">
              <div
                :contenteditable="editable"
                @blur="updateContent('scheduleText', $event.target.innerHTML)"
                class="text-sm leading-relaxed outline-none focus:ring-2 focus:ring-blue-500 rounded px-2"
                v-html="block.content.scheduleText"
              >
              </div>
            </div>
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

const blockStyles = computed(() => {
  const styles = props.block.styles || {}
  return {
    backgroundColor: styles.backgroundColor || '#1f2937',
    color: styles.textColor || '#ffffff',
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
