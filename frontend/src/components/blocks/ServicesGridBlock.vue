<template>
  <div class="services-grid-block">
    <div :class="['max-w-7xl mx-auto px-4 sm:px-6 py-12 sm:py-16', roundedCorners ? 'rounded-lg' : '']" :style="blockStyles">
      <!-- Titolo sezione -->
      <h2
        v-if="block.content.title"
        :contenteditable="editable"
        @blur="updateContent('title', $event.target.innerText)"
        class="text-2xl sm:text-3xl font-bold text-center mb-8 sm:mb-12 outline-none focus:ring-2 focus:ring-primary-300 rounded px-2"
      >
        {{ block.content.title }}
      </h2>

      <!-- Griglia Servizi -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6 md:gap-8">
        <div
          v-for="(service, index) in block.content.services"
          :key="index"
          :class="['bg-white shadow-lg overflow-hidden transition-all duration-300 hover:shadow-2xl hover:-translate-y-2 hover:scale-105 border-2 border-transparent hover:border-primary-500', roundedCorners ? 'rounded-lg' : '']"
        >
          <!-- Immagine -->
          <div class="relative h-48 bg-gray-200 overflow-hidden">
            <img
              v-if="service.image"
              :src="service.image"
              :alt="service.title"
              class="w-full h-full object-cover transition-transform duration-500 hover:scale-110"
            />
            <div
              v-else
              class="w-full h-full flex items-center justify-center text-gray-400"
            >
              Carica immagine
            </div>
          </div>

          <!-- Contenuto Card -->
          <div class="p-5 sm:p-6">
            <h3
              :contenteditable="editable"
              @blur="updateService(index, 'title', $event.target.innerText)"
              class="text-lg sm:text-xl font-semibold mb-2 sm:mb-3 outline-none focus:ring-2 focus:ring-primary-300 rounded px-2"
            >
              {{ service.title }}
            </h3>

            <p
              :contenteditable="editable"
              @blur="updateService(index, 'description', $event.target.innerText)"
              class="text-sm sm:text-base text-gray-600 leading-relaxed outline-none focus:ring-2 focus:ring-primary-300 rounded px-2"
            >
              {{ service.description }}
            </p>

            <!-- Link opzionale -->
            <a
              v-if="service.link"
              :href="service.link"
              class="inline-block mt-3 sm:mt-4 text-sm sm:text-base text-primary-600 hover:text-primary-700 font-medium transition-all hover:translate-x-1 cursor-pointer"
            >
              Scopri di più →
            </a>
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

const updateService = (index, field, value) => {
  const updatedServices = [...props.block.content.services]
  updatedServices[index] = {
    ...updatedServices[index],
    [field]: value
  }

  const updatedBlock = {
    ...props.block,
    content: {
      ...props.block.content,
      services: updatedServices
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
