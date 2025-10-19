<template>
  <div class="cta-block">
    <div class="max-w-7xl mx-auto px-6 py-16 text-center rounded-lg" :style="blockStyles">
      <!-- Titolo -->
      <h2
        :contenteditable="editable"
        @blur="updateContent('title', $event.target.innerText)"
        class="text-3xl md:text-4xl font-bold mb-6 outline-none focus:ring-2 focus:ring-primary-300 rounded px-2"
      >
        {{ block.content.title }}
      </h2>

      <!-- Descrizione -->
      <p
        v-if="block.content.description"
        :contenteditable="editable"
        @blur="updateContent('description', $event.target.innerText)"
        class="text-lg md:text-xl text-gray-600 mb-8 outline-none focus:ring-2 focus:ring-primary-300 rounded px-2"
      >
        {{ block.content.description }}
      </p>

      <!-- Pulsante CTA -->
      <a
        :href="block.content.buttonLink || '#'"
        class="inline-block bg-primary-600 hover:bg-primary-700 text-white px-8 py-4 rounded-lg text-lg font-semibold transition-colors shadow-lg hover:shadow-xl"
      >
        <span
          :contenteditable="editable"
          @blur="updateContent('buttonText', $event.target.innerText)"
          class="outline-none"
        >
          {{ block.content.buttonText }}
        </span>
      </a>

      <!-- Testo secondario opzionale -->
      <p
        v-if="block.content.secondaryText"
        :contenteditable="editable"
        @blur="updateContent('secondaryText', $event.target.innerText)"
        class="mt-4 text-sm text-gray-500 outline-none focus:ring-2 focus:ring-primary-300 rounded px-2"
      >
        {{ block.content.secondaryText }}
      </p>
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
  }
})

const emit = defineEmits(['update'])

const blockStyles = computed(() => {
  const styles = props.block.styles || {}
  return {
    backgroundColor: styles.backgroundColor || 'transparent',
    color: styles.textColor || 'inherit',
    padding: styles.padding || undefined
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
