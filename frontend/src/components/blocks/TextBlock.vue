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
        v-if="editable"
        contenteditable="true"
        @blur="updateContent('title', $event.target.textContent)"
        class="text-3xl font-bold mb-4 outline-none"
      >
        {{ block.content.title }}
      </h2>
      <h2 v-else class="text-3xl font-bold mb-4">
        {{ block.content.title }}
      </h2>

      <div class="text-lg leading-relaxed prose max-w-none" v-html="block.content.text"></div>
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
    padding: styles.padding || undefined // usa il padding di Tailwind (py-12 px-6) se non specificato
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
  font-weight: bold;
  margin-bottom: 0.5em;
  margin-top: 1em;
}

:deep(.prose h2) {
  font-size: 1.5em;
  font-weight: bold;
  margin-bottom: 0.5em;
  margin-top: 0.8em;
}

:deep(.prose h3) {
  font-size: 1.25em;
  font-weight: bold;
  margin-bottom: 0.5em;
  margin-top: 0.6em;
}

:deep(.prose ul),
:deep(.prose ol) {
  padding-left: 1.5em;
  margin-bottom: 1em;
}

:deep(.prose li) {
  margin-bottom: 0.25em;
}

:deep(.prose a) {
  color: #3b82f6;
  text-decoration: underline;
}

:deep(.prose a:hover) {
  color: #2563eb;
}

:deep(.prose strong) {
  font-weight: bold;
}

:deep(.prose em) {
  font-style: italic;
}

:deep(.prose u) {
  text-decoration: underline;
}
</style>
