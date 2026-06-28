<template>
  <div class="border-t border-gray-200 pt-4">
    <h5 class="text-xs font-semibold text-gray-700 mb-3 uppercase tracking-wide">Ombra Titolo</h5>

    <label class="flex items-center gap-2 cursor-pointer mb-3">
      <input
        type="checkbox"
        :checked="enabled"
        @change="toggle($event.target.checked)"
        class="w-4 h-4 rounded accent-primary-500"
      />
      <span class="text-xs font-medium text-gray-700">Abilita ombra sul titolo</span>
    </label>

    <div v-if="enabled && shadow" class="space-y-3">
      <div>
        <label class="block text-xs font-medium text-gray-700 mb-2">Colore Ombra</label>
        <div class="flex items-center gap-3">
          <input
            v-model="shadow.color"
            type="color"
            class="h-11 w-20 rounded-lg cursor-pointer border border-gray-300"
          />
          <input
            v-model="shadow.color"
            type="text"
            placeholder="#000000"
            class="flex-1 px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-200 focus:border-primary-500 transition-all outline-none text-sm font-mono"
          />
        </div>
      </div>

      <div>
        <label class="block text-xs font-medium text-gray-700 mb-2">Sfocatura: {{ shadow.blur ?? 6 }}px</label>
        <input
          v-model.number="shadow.blur"
          type="range"
          min="0"
          max="30"
          step="1"
          class="w-full accent-primary-500"
        />
      </div>

      <div>
        <label class="block text-xs font-medium text-gray-700 mb-2">Offset Orizzontale: {{ shadow.offsetX ?? 2 }}px</label>
        <input
          v-model.number="shadow.offsetX"
          type="range"
          min="-20"
          max="20"
          step="1"
          class="w-full accent-primary-500"
        />
      </div>

      <div>
        <label class="block text-xs font-medium text-gray-700 mb-2">Offset Verticale: {{ shadow.offsetY ?? 2 }}px</label>
        <input
          v-model.number="shadow.offsetY"
          type="range"
          min="-20"
          max="20"
          step="1"
          class="w-full accent-primary-500"
        />
      </div>

      <div class="rounded-lg bg-gray-100 p-4 text-center">
        <span class="text-2xl font-bold text-white" :style="previewStyle">Anteprima Titolo</span>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { titleShadowStyle, defaultTitleShadow } from '../utils/titleStyle'

const props = defineProps({
  content: {
    type: Object,
    required: true
  }
})

const shadow = computed(() => props.content.titleShadow)
const enabled = computed(() => !!props.content.titleShadow?.enabled)

const toggle = (checked) => {
  if (checked) {
    if (!props.content.titleShadow) {
      props.content.titleShadow = defaultTitleShadow()
    } else {
      props.content.titleShadow.enabled = true
    }
  } else if (props.content.titleShadow) {
    props.content.titleShadow.enabled = false
  }
}

const previewStyle = computed(() => titleShadowStyle(props.content))
</script>
