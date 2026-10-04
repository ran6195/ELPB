<template>
  <div class="relative w-full h-full bg-gray-100 overflow-hidden">
    <img
      v-if="previewUrl"
      :src="previewUrl"
      :alt="media.alt_text || media.original_name"
      loading="lazy"
      class="w-full h-full"
      :class="fit === 'contain' ? 'object-contain' : 'object-cover'"
    />
    <div v-else class="w-full h-full flex items-center justify-center text-gray-400">
      <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
      </svg>
    </div>

    <!-- Badge video -->
    <span
      v-if="media.type === 'video'"
      class="absolute bottom-1.5 left-1.5 inline-flex items-center gap-1 bg-black/70 text-white text-[10px] font-medium px-1.5 py-0.5 rounded"
    >
      <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path d="M6.3 2.84A1.5 1.5 0 004 4.11v11.78a1.5 1.5 0 002.3 1.27l9.34-5.89a1.5 1.5 0 000-2.54L6.3 2.84z" /></svg>
      {{ formatDuration(media.duration) || 'Video' }}
    </span>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { formatDuration } from '../../utils/media'

const props = defineProps({
  media: { type: Object, required: true },
  fit: { type: String, default: 'cover' }
})

const previewUrl = computed(() => {
  if (props.media.thumbnail_url) return props.media.thumbnail_url
  return props.media.type === 'image' ? props.media.url : null
})
</script>
