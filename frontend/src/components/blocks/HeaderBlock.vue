<template>
  <nav :style="{ marginTop: block.content.marginTop || '0px' }">
    <div :class="['max-w-7xl mx-auto px-6 py-4', roundedCorners ? 'rounded-lg' : '']" :style="blockStyles">
      <div class="flex items-center justify-between">
        <!-- Logo -->
        <a
          :href="block.content.logoLink || '#'"
          class="flex items-center"
        >
          <img
            v-if="block.content.logoUrl"
            :src="block.content.logoUrl"
            :alt="block.content.logoAlt || 'Logo'"
            class="h-12 w-auto"
            :style="{ height: block.content.logoHeight || '50px', width: 'auto' }"
          />
          <div
            v-else
            class="h-12 w-48 bg-gray-300 rounded flex items-center justify-center text-sm text-gray-600"
          >
            Logo
          </div>
        </a>

        <!-- Menu links opzionali (per il futuro) -->
        <div v-if="block.content.showMenu" class="hidden md:flex items-center space-x-6">
          <a
            v-for="(link, index) in block.content.menuLinks"
            :key="index"
            :href="link.url"
            class="text-sm font-medium hover:opacity-80 transition-opacity"
          >
            {{ link.text }}
          </a>
        </div>
      </div>
    </div>
  </nav>
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
    backgroundColor: styles.backgroundColor || '#343a40', // bg-dark di Bootstrap
    color: styles.textColor || '#FFFFFF',
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
