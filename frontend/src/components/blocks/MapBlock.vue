<template>
  <div class="map-block">
    <div
      :class="[
        'max-w-7xl mx-auto px-6 py-16',
        roundedCorners ? 'rounded-lg' : ''
      ]"
      :style="blockStyles"
    >
      <!-- Titolo opzionale -->
      <h2
        v-if="block.content.title"
        :contenteditable="editable"
        @blur="updateContent('title', $event.target.innerText)"
        class="text-3xl font-bold text-center mb-8 outline-none focus:ring-2 focus:ring-primary-300 rounded px-2"
        :style="mapTitleStyles"
      >
        {{ block.content.title }}
      </h2>

      <!-- Descrizione opzionale -->
      <p
        v-if="block.content.description"
        :contenteditable="editable"
        @blur="updateContent('description', $event.target.innerText)"
        class="text-center text-gray-600 mb-8 outline-none focus:ring-2 focus:ring-primary-300 rounded px-2"
        :style="block.content.descriptionColor ? { color: block.content.descriptionColor } : {}"
      >
        {{ block.content.description }}
      </p>

      <!-- Mappa -->
      <div
        :class="[
          'overflow-hidden',
          roundedCorners ? 'rounded-lg' : ''
        ]"
        :style="{ height: block.content.height || '450px' }"
      >
        <iframe
          v-if="mapUrl"
          :src="mapUrl"
          width="100%"
          :height="block.content.height || '450px'"
          style="border:0;"
          allowfullscreen=""
          loading="lazy"
          referrerpolicy="no-referrer-when-downgrade"
        ></iframe>

        <!-- Placeholder quando non c'è mappa -->
        <div
          v-else
          class="w-full h-full bg-gray-200 flex items-center justify-center"
        >
          <div class="text-center text-gray-400">
            <svg class="w-16 h-16 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
            </svg>
            <p class="text-lg font-medium">Inserisci l'URL della mappa</p>
            <p class="text-sm mt-2">Usa il link di Google Maps</p>
          </div>
        </div>
      </div>

      <!-- Informazioni di contatto opzionali sotto la mappa -->
      <div v-if="block.content.showContactInfo" class="mt-8 grid md:grid-cols-3 gap-6">
        <!-- Indirizzo -->
        <div v-if="block.content.address" class="flex items-start gap-3">
          <svg class="w-6 h-6 text-primary-600 mt-1 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
          </svg>
          <div>
            <h4 class="font-semibold mb-1">Indirizzo</h4>
            <p
              :contenteditable="editable"
              @blur="updateContent('address', $event.target.innerText)"
              class="text-gray-600 outline-none focus:ring-2 focus:ring-primary-300 rounded px-1"
            >
              {{ block.content.address }}
            </p>
          </div>
        </div>

        <!-- Telefono -->
        <div v-if="block.content.phone" class="flex items-start gap-3">
          <svg class="w-6 h-6 text-primary-600 mt-1 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
          </svg>
          <div>
            <h4 class="font-semibold mb-1">Telefono</h4>
            <p
              :contenteditable="editable"
              @blur="updateContent('phone', $event.target.innerText)"
              class="text-gray-600 outline-none focus:ring-2 focus:ring-primary-300 rounded px-1"
            >
              {{ block.content.phone }}
            </p>
          </div>
        </div>

        <!-- Email -->
        <div v-if="block.content.email" class="flex items-start gap-3">
          <svg class="w-6 h-6 text-primary-600 mt-1 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
          </svg>
          <div>
            <h4 class="font-semibold mb-1">Email</h4>
            <p
              :contenteditable="editable"
              @blur="updateContent('email', $event.target.innerText)"
              class="text-gray-600 outline-none focus:ring-2 focus:ring-primary-300 rounded px-1"
            >
              {{ block.content.email }}
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

const titleSizeMap = { xl: '1.25rem', '2xl': '1.5rem', '3xl': '1.875rem', '4xl': '2.25rem', '5xl': '3rem', '6xl': '3.75rem' }
const mapTitleStyles = computed(() => {
  const s = {}
  if (props.block.content.titleColor) s.color = props.block.content.titleColor
  const fs = titleSizeMap[props.block.content.titleSize]
  if (fs) s.fontSize = fs
  return s
})

const blockStyles = computed(() => {
  const styles = props.block.styles || {}
  return {
    backgroundColor: styles.backgroundColor || 'transparent',
    color: styles.textColor || 'inherit',
    padding: styles.padding || undefined,
    fontFamily: styles.fontFamily || undefined
  }
})

// Genera URL iframe da vari formati di input (senza API key)
const mapUrl = computed(() => {
  const input = props.block.content.mapUrl || ''
  if (!input) return ''

  // Se è un iframe completo, estrai l'src
  const iframeMatch = input.match(/src=["']([^"']+)["']/)
  if (iframeMatch) {
    return iframeMatch[1]
  }

  // Se è già un URL embed diretto (da Google Maps > Condividi > Incorpora)
  if (input.includes('google.com/maps/embed')) {
    return input
  }

  // Per qualsiasi altro formato (URL normale, coordinate, testo indirizzo)
  // usa il formato senza API key: maps.google.com/maps?q=...&output=embed
  const query = input.includes('google.com/maps') ? extractQueryFromMapsUrl(input) : input
  return `https://maps.google.com/maps?q=${encodeURIComponent(query)}&output=embed&hl=it`
})

// Estrae una query leggibile da un URL di Google Maps
const extractQueryFromMapsUrl = (url) => {
  // Tenta di estrarre il nome del posto
  const placeMatch = url.match(/place\/([^/@]+)/)
  if (placeMatch) return decodeURIComponent(placeMatch[1].replace(/\+/g, ' '))

  // Tenta di estrarre le coordinate @lat,lng
  const coordMatch = url.match(/@(-?\d+\.\d+),(-?\d+\.\d+)/)
  if (coordMatch) return `${coordMatch[1]},${coordMatch[2]}`

  // Tenta parametro q=
  const qMatch = url.match(/[?&]q=([^&]+)/)
  if (qMatch) return decodeURIComponent(qMatch[1])

  return url
}

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
