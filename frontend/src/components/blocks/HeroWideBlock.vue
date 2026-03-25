<template>
  <div :style="outerWrapperStyles">
    <div class="hero-block" :style="wrapperStyles">
      <!-- Layer 1: immagine di sfondo con opacità -->
      <div
        v-if="block.content.backgroundImage"
        :style="bgImageStyles"
      ></div>
      <!-- Layer 2: overlay colorato -->
      <div
        v-if="block.content.overlayEnabled"
        :style="overlayStyles"
      ></div>
      <!-- Layer 3: contenuto posizionato -->
      <div
        :class="['px-6 py-20', roundedCorners ? 'rounded-lg' : '']"
        :style="contentWrapperStyles"
      >
        <h1
          v-if="editable"
          contenteditable="true"
          @blur="updateContent('title', $event.target.textContent)"
          class="text-5xl font-bold mb-4 outline-none"
          :style="titleStyles"
        >
          {{ block.content.title }}
        </h1>
        <h1 v-else class="text-5xl font-bold mb-4"
          :style="titleStyles"
        >
          {{ block.content.title }}
        </h1>

        <p
          v-if="editable"
          contenteditable="true"
          @blur="updateContent('subtitle', $event.target.textContent)"
          class="text-xl mb-8 outline-none"
          :style="block.content.subtitleColor ? { color: block.content.subtitleColor } : {}"
        >
          {{ block.content.subtitle }}
        </p>
        <p v-else class="text-xl mb-8"
          :style="block.content.subtitleColor ? { color: block.content.subtitleColor } : {}"
        >
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
const titleStyles = computed(() => {
  const s = {}
  if (props.block.content.titleColor) s.color = props.block.content.titleColor
  const fs = titleSizeMap[props.block.content.titleSize]
  if (fs) s.fontSize = fs
  return s
})

const justifyMap = { left: 'flex-start', center: 'center', right: 'flex-end' }
const alignMap   = { top: 'flex-start', middle: 'center', bottom: 'flex-end' }

// Wrapper esterno: controlla la larghezza del blocco e lo centra
const outerWrapperStyles = computed(() => {
  const blockWidth = props.block.content.blockWidth || '100%'
  return {
    width: blockWidth,
    margin: '0 auto'
  }
})

const wrapperStyles = computed(() => {
  const blockStyles = props.block.styles || {}
  const h = props.block.content.contentHorizontal || 'center'
  const v = props.block.content.contentVertical   || 'middle'

  const styles = {
    position: 'relative',
    overflow: 'hidden',
    width: '100%',
    minHeight: props.block.content.height || '400px',
    display: 'flex',
    justifyContent: justifyMap[h] || 'center',
    alignItems: alignMap[v] || 'center',
  }

  if (blockStyles.backgroundColor) styles.backgroundColor = blockStyles.backgroundColor
  if (blockStyles.fontFamily)       styles.fontFamily = blockStyles.fontFamily

  return styles
})

const bgImageStyles = computed(() => ({
  position: 'absolute',
  top: '0',
  left: '0',
  width: '100%',
  height: '100%',
  backgroundImage: `url(${props.block.content.backgroundImage})`,
  backgroundSize: 'cover',
  backgroundPosition: 'center',
  backgroundRepeat: 'no-repeat',
  opacity: props.block.content.backgroundImageOpacity ?? 1
}))

const overlayStyles = computed(() => ({
  position: 'absolute',
  top: '0',
  left: '0',
  width: '100%',
  height: '100%',
  backgroundColor: props.block.content.overlayColor || '#000000',
  opacity: props.block.content.overlayOpacity ?? 0.5
}))

const contentWrapperStyles = computed(() => {
  const blockStyles = props.block.styles || {}
  const h = props.block.content.contentHorizontal || 'center'
  const maxW = props.block.content.contentMaxWidth || '100%'

  const styles = {
    position: 'relative',
    zIndex: 1,
    width: maxW,
    textAlign: h,
  }

  if (blockStyles.textColor) styles.color = blockStyles.textColor
  if (blockStyles.padding)   styles.padding = blockStyles.padding

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
