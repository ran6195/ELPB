<template>
  <div class="text-block">
    <div
      :class="[
        'max-w-7xl mx-auto px-6 py-12',
        roundedCorners ? 'rounded-lg' : ''
      ]"
      :style="blockStyles"
    >
      <h2
        v-if="editable || block.content.title"
        :contenteditable="editable"
        @blur="editable && updateContent('title', $event.target.textContent)"
        class="text-3xl font-bold mb-4 outline-none"
      >
        {{ block.content.title }}
      </h2>

      <div class="text-lg prose max-w-none" :style="textStyles" v-html="block.content.text"></div>
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
    padding: styles.padding || undefined, // usa il padding di Tailwind (py-12 px-6) se non specificato
    fontFamily: styles.fontFamily || undefined
  }
})

const textStyles = computed(() => {
  const lineHeight = props.block.content.lineHeight || '1.625'
  return {
    lineHeight: lineHeight
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

/* Stili per il contenuto HTML renderizzato */
:deep(.prose) {
  color: inherit;
}

:deep(.prose p) {
  margin-bottom: 1em;
}

:deep(.prose h1) {
  font-size: 2em;
  font-weight: 600; /* Semibold di default */
  margin-bottom: 0.5em;
  margin-top: 1em;
}

:deep(.prose h2) {
  font-size: 1.5em;
  font-weight: 600; /* Semibold di default */
  margin-bottom: 0.5em;
  margin-top: 0.8em;
}

:deep(.prose h3) {
  font-size: 1.25em;
  font-weight: 600; /* Semibold di default */
  margin-bottom: 0.5em;
  margin-top: 0.6em;
}

/* Grassetto normale per paragrafi */
:deep(.prose p strong),
:deep(.prose p b) {
  font-weight: 700 !important;
}

/* Grassetto extra per heading */
:deep(.prose h1 strong),
:deep(.prose h1 b) {
  font-weight: 900 !important; /* Extra bold */
}

:deep(.prose h2 strong),
:deep(.prose h2 b) {
  font-weight: 900 !important; /* Extra bold */
}

:deep(.prose h3 strong),
:deep(.prose h3 b) {
  font-weight: 900 !important; /* Extra bold */
}

:deep(.prose ul),
:deep(.prose ol) {
  padding-left: 1.5em;
  margin-bottom: 1em;
}

:deep(.prose li) {
  margin-bottom: 0.25em;
}

:deep(.prose li strong),
:deep(.prose li b) {
  font-weight: 700 !important;
}

:deep(.prose a) {
  color: #3b82f6;
  text-decoration: underline;
}

:deep(.prose a:hover) {
  color: #2563eb;
}

:deep(.prose strong) {
  font-weight: 700 !important;
}

:deep(.prose b) {
  font-weight: 700 !important;
}

:deep(.prose em) {
  font-style: italic;
}

:deep(.prose u) {
  text-decoration: underline;
}

:deep(.prose s),
:deep(.prose del) {
  text-decoration: line-through;
}

:deep(.prose blockquote) {
  border-left: 4px solid #d1d5db;
  padding-left: 1em;
  margin-left: 0;
  margin-bottom: 1em;
  font-style: italic;
  color: #6b7280;
}

:deep(.prose pre.ql-syntax) {
  background: #1e293b;
  color: #e2e8f0;
  padding: 1em;
  border-radius: 0.375rem;
  overflow-x: auto;
  margin-bottom: 1em;
  font-family: 'Courier New', monospace;
  font-size: 0.875em;
}

:deep(.prose code) {
  background: #f3f4f6;
  padding: 0.2em 0.4em;
  border-radius: 0.25rem;
  font-family: 'Courier New', monospace;
  font-size: 0.875em;
}

/* Allineamento testo */
:deep(.prose .ql-align-center) {
  text-align: center !important;
}

:deep(.prose .ql-align-right) {
  text-align: right !important;
}

:deep(.prose .ql-align-justify) {
  text-align: justify !important;
}
</style>
