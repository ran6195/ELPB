<template>
  <div class="cta-block">
    <div :class="['max-w-7xl mx-auto px-6 py-16 text-center', roundedCorners ? 'rounded-lg' : '']" :style="blockStyles">
      <!-- Titolo -->
      <h2
        v-if="block.content.title"
        ref="titleRef"
        :contenteditable="editable"
        @blur="updateContent('title', $event.target.innerText)"
        class="text-3xl md:text-4xl font-bold mb-6 outline-none focus:ring-2 focus:ring-primary-300 rounded px-2"
        :style="titleFontSize ? { fontSize: titleFontSize } : {}"
      >
        {{ block.content.title }}
      </h2>

      <!-- Descrizione -->
      <div
        v-if="block.content.description"
        class="text-lg prose max-w-none mx-auto mb-8"
        v-html="block.content.description"
      ></div>

      <!-- Pulsante CTA -->
      <a
        :href="block.content.buttonLink || '#'"
        :style="buttonStyles"
        class="inline-block font-semibold transition-colors"
      >
        <span
          ref="buttonTextRef"
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
        ref="secondaryTextRef"
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
import { computed, ref, onUpdated } from 'vue'

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

const titleRef = ref(null)
const buttonTextRef = ref(null)
const secondaryTextRef = ref(null)

// Sincronizza il DOM degli elementi contenteditable dopo ogni aggiornamento Vue.
// Necessario perché Vue non aggiorna il contenuto degli elementi contenteditable
// già modificati dal browser. Salta l'elemento se ha il focus (utente sta digitando).
onUpdated(() => {
  const syncEl = (el, value) => {
    if (el && document.activeElement !== el) {
      const current = el.innerText.replace(/\n$/, '')
      if (current !== (value || '')) el.innerText = value || ''
    }
  }
  syncEl(titleRef.value, props.block.content.title)
  syncEl(buttonTextRef.value, props.block.content.buttonText)
  syncEl(secondaryTextRef.value, props.block.content.secondaryText)
})

const titleSizeMap = { xl: '1.25rem', '2xl': '1.5rem', '3xl': '1.875rem', '4xl': '2.25rem', '5xl': '3rem', '6xl': '3.75rem' }
const titleFontSize = computed(() => titleSizeMap[props.block.content.titleSize] || null)

const blockStyles = computed(() => {
  const styles = props.block.styles || {}
  return {
    backgroundColor: styles.backgroundColor || 'transparent',
    color: styles.textColor || 'inherit',
    padding: styles.padding || undefined,
    fontFamily: styles.fontFamily || undefined
  }
})

const buttonStyles = computed(() => {
  const btnStyle = props.block.content.buttonStyle || {
    backgroundColor: '#4F46E5',
    textColor: '#FFFFFF',
    fontSize: '18px',
    padding: '16px 32px',
    borderRadius: '8px',
    borderWidth: '0px',
    borderColor: 'transparent',
    borderStyle: 'solid',
    shadow: 'lg'
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
    boxShadow: shadowMap[btnStyle.shadow] || shadowMap.lg
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
