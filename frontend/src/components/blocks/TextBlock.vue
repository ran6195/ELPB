<template>
  <div class="text-block">
    <div class="max-w-4xl mx-auto px-4">
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

      <div
        v-if="editable"
        contenteditable="true"
        @blur="updateContent('text', $event.target.textContent)"
        class="text-lg leading-relaxed outline-none"
      >
        {{ block.content.text }}
      </div>
      <div v-else class="text-lg leading-relaxed">
        {{ block.content.text }}
      </div>
    </div>
  </div>
</template>

<script setup>
const props = defineProps({
  block: {
    type: Object,
    required: true
  },
  editable: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['update'])

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
