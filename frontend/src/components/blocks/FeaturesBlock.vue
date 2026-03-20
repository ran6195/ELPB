<template>
  <div class="features-block">
    <div :class="['max-w-7xl mx-auto px-4 sm:px-6 py-12 sm:py-16', roundedCorners ? 'rounded-lg' : '']" :style="blockStyles">
      <!-- Titolo opzionale -->
      <h2
        v-if="block.content.title"
        :contenteditable="editable"
        @blur="updateContent('title', $event.target.innerText)"
        :style="{ color: block.content.titleColor || blockStyles.color }"
        class="text-2xl sm:text-3xl font-bold text-center mb-8 sm:mb-12 outline-none focus:ring-2 focus:ring-primary-300 rounded px-2"
      >
        {{ block.content.title }}
      </h2>

      <!-- Griglia Features -->
      <div :class="['grid grid-cols-1 gap-6 md:gap-8', columnsClass]">
        <div
          v-for="(feature, index) in block.content.features"
          :key="index"
          class="text-center"
        >
          <!-- Icona -->
          <div class="flex justify-center mb-4">
            <div
              :class="['w-16 h-16 flex items-center justify-center', iconShapeClass]"
              :style="iconContainerStyle"
            >
              <svg
                class="w-8 h-8"
                :style="{ color: block.content.iconColor || '#2563EB' }"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  :d="getIconPath(feature.icon || 'check')"
                />
              </svg>
            </div>
          </div>

          <!-- Titolo Feature -->
          <h3
            :contenteditable="editable"
            @blur="updateFeature(index, 'title', $event.target.innerText)"
            :style="{ color: block.content.featureTitleColor || blockStyles.color }"
            class="text-lg sm:text-xl font-semibold mb-2 sm:mb-3 outline-none focus:ring-2 focus:ring-primary-300 rounded px-2"
          >
            {{ feature.title }}
          </h3>

          <!-- Descrizione -->
          <p
            :contenteditable="editable"
            @blur="updateFeature(index, 'description', $event.target.innerText)"
            :style="{ color: block.content.featureTextColor || '#6B7280' }"
            class="text-sm sm:text-base leading-relaxed outline-none focus:ring-2 focus:ring-primary-300 rounded px-2"
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

const columnsClass = computed(() => {
  const columns = props.block.content.columns || 3
  return columns === 4 ? 'md:grid-cols-4' : 'md:grid-cols-3'
})

const iconShapeClass = computed(() => {
  const shape = props.block.content.iconShape || 'circle'
  const shapeMap = {
    circle: 'rounded-full',
    square: 'rounded-none',
    rounded: 'rounded-lg'
  }
  return shapeMap[shape] || 'rounded-full'
})

const iconContainerStyle = computed(() => {
  return {
    backgroundColor: props.block.content.iconBackgroundColor || '#DBEAFE'
  }
})

// Heroicons-style icon paths
const iconPaths = {
  check: 'M5 13l4 4L19 7',
  star: 'M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z',
  bolt: 'M13 10V3L4 14h7v7l9-11h-7z',
  shield: 'M20.618 5.984A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z',
  rocket: 'M15.632 3.368a8 8 0 00-11.264 0l-1.414 1.415a2 2 0 000 2.828l2.829 2.829-3.536 3.535a2 2 0 002.829 2.828l3.535-3.535 2.828 2.829a2 2 0 002.829 0l1.414-1.415a8 8 0 000-11.264l-1.414-1.414zM9 11a2 2 0 114 0 2 2 0 01-4 0z M7 21h10',
  heart: 'M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z',
  lightbulb: 'M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z',
  chart: 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z',
  clock: 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z',
  globe: 'M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
  users: 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z',
  cog: 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z',
  gift: 'M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7',
  trophy: 'M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z',
  sparkles: 'M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z',
  thumbup: 'M14 10h4.764a2 2 0 011.789 2.894l-3.5 7A2 2 0 0115.263 21h-4.017c-.163 0-.326-.02-.485-.06L7 20m7-10V5a2 2 0 00-2-2h-.095c-.5 0-.905.405-.905.905 0 .714-.211 1.412-.608 2.006L7 11v9m7-10h-2M7 20H5a2 2 0 01-2-2v-6a2 2 0 012-2h2.5'
}

const getIconPath = (iconName) => {
  return iconPaths[iconName] || iconPaths.check
}

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
