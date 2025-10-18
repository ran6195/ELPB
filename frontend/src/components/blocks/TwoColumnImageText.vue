<template>
  <div class="container mx-auto px-6 py-12">
    <div class="grid md:grid-cols-2 gap-8 items-center">
      <!-- Colonna immagine (sinistra) -->
      <div class="relative">
        <img
          v-if="block.content.image"
          :src="block.content.image"
          alt="Immagine sezione"
          class="w-full h-auto rounded-lg shadow-lg"
        />
        <div
          v-else
          class="w-full h-64 bg-gray-200 rounded-lg flex items-center justify-center"
        >
          <p class="text-gray-400">Carica un'immagine</p>
        </div>
      </div>

      <!-- Colonna testo (destra) -->
      <div>
        <h2
          :contenteditable="editable"
          @blur="updateContent('title', $event.target.innerText)"
          class="text-3xl font-bold text-gray-900 mb-4 outline-none focus:ring-2 focus:ring-primary-300 rounded px-2"
        >
          {{ block.content.title }}
        </h2>
        <p
          :contenteditable="editable"
          @blur="updateContent('text', $event.target.innerText)"
          class="text-gray-600 leading-relaxed outline-none focus:ring-2 focus:ring-primary-300 rounded px-2"
        >
          {{ block.content.text }}
        </p>
      </div>
    </div>
  </div>
</template>

<script setup>
const props = defineProps({
  block: {
    type: Object,
    required: true
  },
  editable: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['update'])

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
