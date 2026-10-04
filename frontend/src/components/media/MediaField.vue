<template>
  <div>
    <label v-if="label" class="block text-xs font-medium text-gray-700 mb-2">{{ label }}</label>
    <button
      type="button"
      @click="open = true"
      class="w-full flex items-center justify-center gap-2 px-3 py-2.5 border border-dashed border-gray-300 rounded-lg text-sm text-gray-600 hover:border-primary-400 hover:text-primary-700 hover:bg-primary-50 transition-colors"
    >
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path v-if="type === 'video'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
        <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
      </svg>
      <span>{{ buttonText }}</span>
    </button>

    <MediaPicker
      v-if="open"
      :type="type"
      @select="onSelect"
      @close="open = false"
    />
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import MediaPicker from './MediaPicker.vue'

/**
 * Pulsante "Scegli dalla libreria" che apre il MediaPicker.
 * Emette `select` con (url, media) quando l'utente sceglie un file.
 */
const props = defineProps({
  type: { type: String, default: 'image' }, // 'image' | 'video'
  label: { type: String, default: '' },
  text: { type: String, default: '' }
})

const emit = defineEmits(['select'])

const open = ref(false)

const buttonText = computed(() => props.text || (props.type === 'video'
  ? 'Scegli o carica un video dalla libreria'
  : 'Scegli o carica un\'immagine dalla libreria'))

function onSelect(media) {
  emit('select', media.url, media)
  open.value = false
}
</script>
