<template>
  <div class="hero-block">
    <div :class="['max-w-7xl mx-auto px-6 py-20 text-center', roundedCorners ? 'rounded-lg' : '']" :style="combinedStyles">
      <h1
        v-if="editable"
        contenteditable="true"
        @blur="updateContent('title', $event.target.textContent)"
        class="text-5xl font-bold mb-4 outline-none"
      >
        {{ block.content.title }}
      </h1>
      <h1 v-else class="text-5xl font-bold mb-4">
        {{ block.content.title }}
      </h1>

      <p
        v-if="editable"
        contenteditable="true"
        @blur="updateContent('subtitle', $event.target.textContent)"
        class="text-xl mb-8 outline-none"
      >
        {{ block.content.subtitle }}
      </p>
      <p v-else class="text-xl mb-8">
        {{ block.content.subtitle }}
      </p>

      <a
        :href="block.content.buttonLink"
        :class="['inline-block bg-white text-indigo-600 px-8 py-3 font-semibold hover:bg-gray-100 transition', roundedCorners ? 'rounded-lg' : '']"
      >
        {{ block.content.buttonText }}
      </a>
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

const combinedStyles = computed(() => {
  const blockStyles = props.block.styles || {}
  const styles = {
    minHeight: '400px'
  }

  // Aggiungi stili del blocco
  if (blockStyles.backgroundColor) {
    styles.backgroundColor = blockStyles.backgroundColor
  }
  if (blockStyles.textColor) {
    styles.color = blockStyles.textColor
  }
  if (blockStyles.padding) {
    styles.padding = blockStyles.padding
  }

  // Aggiungi background image se presente
  if (props.block.content.backgroundImage) {
    styles.backgroundImage = `url(${props.block.content.backgroundImage})`
    styles.backgroundSize = 'cover'
    styles.backgroundPosition = 'center'
  }

  return styles
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
