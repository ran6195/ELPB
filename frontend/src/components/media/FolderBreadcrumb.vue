<template>
  <nav class="flex items-center flex-wrap gap-1 text-sm" aria-label="Percorso cartella">
    <template v-for="(crumb, i) in crumbs" :key="crumb.id ?? 'root'">
      <span v-if="i > 0" class="text-gray-400">›</span>
      <button
        type="button"
        @click="emit('navigate', crumb.id)"
        @dragover.prevent="droppable && (dropTarget = crumb.id ?? 'root')"
        @dragleave="dropTarget = null"
        @drop.prevent="onDrop($event, crumb.id)"
        :class="[
          i === crumbs.length - 1 ? 'font-semibold text-gray-900' : 'text-primary-700 hover:underline',
          dropTarget === (crumb.id ?? 'root') ? 'bg-primary-100 ring-2 ring-primary-300' : ''
        ]"
        class="px-1.5 py-0.5 rounded"
      >
        {{ crumb.name }}
      </button>
    </template>
  </nav>
</template>

<script setup>
import { ref, computed } from 'vue'
import { readDragPayload } from '../../utils/mediaDrag'

const props = defineProps({
  path: { type: Array, required: true }, // cartelle dalla radice alla corrente
  rootLabel: { type: String, default: 'Tutti i media' },
  droppable: { type: Boolean, default: false }
})

const emit = defineEmits(['navigate', 'drop'])

const dropTarget = ref(null)

const crumbs = computed(() => [{ id: null, name: props.rootLabel }, ...props.path])

function onDrop(e, folderId) {
  dropTarget.value = null
  if (!props.droppable) return
  const payload = readDragPayload(e)
  if (payload) emit('drop', { payload, folderId: folderId ?? null })
}
</script>
