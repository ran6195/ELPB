<template>
  <div
    @click="emit('open', folder)"
    @dragover.prevent="onDragOver"
    @dragleave="dropActive = false"
    @drop.prevent="onDrop"
    :draggable="draggable"
    @dragstart="onDragStart"
    :class="dropActive ? 'border-primary-500 bg-primary-50 ring-2 ring-primary-200' : 'border-gray-200 bg-white hover:border-gray-300 hover:shadow-sm'"
    class="group relative flex items-center gap-3 px-3 py-2.5 border rounded-lg cursor-pointer transition-all select-none"
  >
    <svg class="w-8 h-8 shrink-0" :class="dropActive ? 'text-primary-500' : 'text-amber-400'" fill="currentColor" viewBox="0 0 24 24">
      <path d="M10 4H4a2 2 0 00-2 2v12a2 2 0 002 2h16a2 2 0 002-2V8a2 2 0 00-2-2h-8l-2-2z" />
    </svg>
    <div class="min-w-0 flex-1">
      <p class="text-sm font-medium text-gray-800 truncate" :title="folder.name">{{ folder.name }}</p>
      <p class="text-[11px] text-gray-500 truncate">
        {{ count }} {{ count === 1 ? 'elemento' : 'elementi' }}
        <span v-if="showCompany"> · {{ folder.company?.name || 'Senza azienda' }}</span>
      </p>
    </div>

    <!-- Menu azioni -->
    <div v-if="manageable" class="relative" @click.stop>
      <button
        @click="menuOpen = !menuOpen"
        class="p-1 rounded text-gray-400 hover:text-gray-700 hover:bg-gray-100 opacity-0 group-hover:opacity-100 focus:opacity-100"
        :class="{ 'opacity-100': menuOpen }"
        title="Azioni cartella"
      >
        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path d="M10 6a2 2 0 110-4 2 2 0 010 4zm0 6a2 2 0 110-4 2 2 0 010 4zm0 6a2 2 0 110-4 2 2 0 010 4z" /></svg>
      </button>
      <div v-if="menuOpen" class="absolute right-0 top-7 z-20 w-36 bg-white border border-gray-200 rounded-lg shadow-lg py-1 text-sm">
        <button @click="menuOpen = false; emit('rename', folder)" class="w-full text-left px-3 py-1.5 hover:bg-gray-50">Rinomina</button>
        <button @click="menuOpen = false; emit('delete', folder)" class="w-full text-left px-3 py-1.5 text-red-600 hover:bg-red-50">Elimina</button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue'
import { readDragPayload, writeDragPayload } from '../../utils/mediaDrag'

const props = defineProps({
  folder: { type: Object, required: true },
  count: { type: Number, default: 0 },
  manageable: { type: Boolean, default: false }, // menu + trascinamento
  showCompany: { type: Boolean, default: false }
})

const emit = defineEmits(['open', 'rename', 'delete', 'drop'])

const menuOpen = ref(false)
const dropActive = ref(false)
const draggable = props.manageable

function onDragStart(e) {
  writeDragPayload(e, { kind: 'folder', id: props.folder.id })
}

function onDragOver(e) {
  if (!props.manageable) return
  dropActive.value = true
  e.dataTransfer.dropEffect = 'move'
}

function onDrop(e) {
  dropActive.value = false
  if (!props.manageable) return
  const payload = readDragPayload(e)
  if (payload && !(payload.kind === 'folder' && payload.id === props.folder.id)) {
    emit('drop', { payload, folderId: props.folder.id })
  }
}

// Chiude il menu cliccando altrove
const closeMenu = () => { menuOpen.value = false }
onMounted(() => document.addEventListener('click', closeMenu))
onBeforeUnmount(() => document.removeEventListener('click', closeMenu))
</script>
