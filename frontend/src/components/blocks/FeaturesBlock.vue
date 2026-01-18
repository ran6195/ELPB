<template>
  <div class="features-block">
    <div :class="['max-w-7xl mx-auto px-4 sm:px-6 py-12 sm:py-16', roundedCorners ? 'rounded-lg' : '']" :style="blockStyles">
      <!-- Titolo opzionale -->
      <h2
        v-if="block.content.title"
        :contenteditable="editable"
        @blur="updateContent('title', $event.target.innerText)"
        class="text-2xl sm:text-3xl font-bold text-center mb-8 sm:mb-12 outline-none focus:ring-2 focus:ring-primary-300 rounded px-2"
      >
        {{ block.content.title }}
      </h2>

      <!-- Griglia Features -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6 md:gap-8">
        <div
          v-for="(feature, index) in block.content.features"
          :key="index"
          class="text-center"
        >
          <!-- Icona -->
          <div class="flex justify-center mb-4">
            <div class="w-16 h-16 rounded-full bg-primary-100 flex items-center justify-center">
              <svg
                class="w-8 h-8 text-primary-600"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M5 13l4 4L19 7"
                />
              </svg>
            </div>
          </div>

          <!-- Titolo Feature -->
          <h3
            :contenteditable="editable"
            @blur="updateFeature(index, 'title', $event.target.innerText)"
            class="text-lg sm:text-xl font-semibold mb-2 sm:mb-3 outline-none focus:ring-2 focus:ring-primary-300 rounded px-2"
          >
            {{ feature.title }}
          </h3>

          <!-- Descrizione -->
          <p
            :contenteditable="editable"
            @blur="updateFeature(index, 'description', $event.target.innerText)"
            class="text-sm sm:text-base text-gray-600 leading-relaxed outline-none focus:ring-2 focus:ring-primary-300 rounded px-2"
          >
            {{ feature.description }}
          </p>
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

const updateFeature = (index, field, value) => {
  const updatedFeatures = [...props.block.content.features]
  updatedFeatures[index] = {
    ...updatedFeatures[index],
    [field]: value
  }

  const updatedBlock = {
    ...props.block,
    content: {
      ...props.block.content,
      features: updatedFeatures
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
