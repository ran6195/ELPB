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
        class="inline-block font-semibold transition-all hover:opacity-90"
        :style="buttonStyles"
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
  if (blockStyles.fontFamily) {
    styles.fontFamily = blockStyles.fontFamily
  }

  // Aggiungi background image se presente
  if (props.block.content.backgroundImage) {
    styles.backgroundImage = `url(${props.block.content.backgroundImage})`
    styles.backgroundSize = 'cover'
    styles.backgroundPosition = 'center'
  }

  return styles
})

const buttonStyles = computed(() => {
  const btnStyle = props.block.content.buttonStyle || {
    backgroundColor: '#4F46E5',
    textColor: '#FFFFFF',
    fontSize: '16px',
    padding: '12px 32px',
    borderRadius: '8px',
    borderWidth: '0px',
    borderColor: 'transparent',
    borderStyle: 'solid',
    shadow: 'md'
  }

  const shadowMap = {
    none: 'none',
    sm: '0 1px 2px 0 rgba(0, 0, 0, 0.05)',
    md: '0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06)',
    lg: '0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05)',
    xl: '0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04)'
  }

  return {
    backgroundColor: btnStyle.backgroundColor,
    color: btnStyle.textColor,
    fontSize: btnStyle.fontSize,
    padding: btnStyle.padding,
    borderRadius: btnStyle.borderRadius,
    borderWidth: btnStyle.borderWidth,
    borderColor: btnStyle.borderColor,
    borderStyle: btnStyle.borderStyle,
    boxShadow: shadowMap[btnStyle.shadow] || shadowMap.md
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
