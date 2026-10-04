<template>
  <div>
    <div
      @dragenter.prevent="dragging = true"
      @dragover.prevent="dragging = true"
      @dragleave.prevent="onDragLeave"
      @drop.prevent="onDrop"
      @click="input.click()"
      :class="[
        dragging ? 'border-primary-500 bg-primary-50' : 'border-gray-300 bg-white hover:border-primary-400 hover:bg-gray-50',
        compact ? 'py-5' : 'py-10'
      ]"
      class="border-2 border-dashed rounded-xl px-4 text-center cursor-pointer transition-colors"
    >
      <input
        ref="input"
        type="file"
        class="hidden"
        :accept="ACCEPT[type || 'all']"
        :multiple="multiple"
        @change="onSelect"
      />
      <svg class="w-8 h-8 mx-auto text-gray-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
      </svg>
      <p class="text-sm text-gray-700">
        <span class="font-medium text-primary-600">Clicca per scegliere</span> oppure trascina qui {{ multiple ? 'i file' : 'il file' }}
      </p>
      <p class="text-xs text-gray-500 mt-1">{{ hint }}</p>
    </div>

    <!-- Coda upload -->
    <ul v-if="queue.length" class="mt-3 space-y-2">
      <li
        v-for="item in queue"
        :key="item.id"
        class="flex items-center gap-3 bg-white border border-gray-200 rounded-lg px-3 py-2 text-sm"
      >
        <div class="flex-1 min-w-0">
          <p class="truncate text-gray-800">{{ item.name }}</p>
          <div v-if="item.status === 'uploading'" class="mt-1 h-1.5 bg-gray-100 rounded-full overflow-hidden">
            <div class="h-full bg-primary-500 transition-all" :style="{ width: item.progress + '%' }"></div>
          </div>
          <p v-else-if="item.status === 'error'" class="text-xs text-red-600 mt-0.5">{{ item.error }}</p>
        </div>
        <span v-if="item.status === 'uploading'" class="text-xs text-gray-500 tabular-nums">{{ item.progress }}%</span>
        <span v-else-if="item.status === 'done'" class="text-green-600 text-xs font-medium">Caricato</span>
        <button
          v-else-if="item.status === 'error'"
          @click.stop="removeFromQueue(item.id)"
          class="text-gray-400 hover:text-gray-600"
          title="Chiudi"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
        </button>
      </li>
    </ul>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useMediaStore, mediaErrorMessage } from '../../stores/mediaStore'
import { ACCEPT, validateFile } from '../../utils/media'

const props = defineProps({
  type: { type: String, default: null }, // 'image' | 'video' | null (entrambi)
  multiple: { type: Boolean, default: true },
  compact: { type: Boolean, default: false },
  folderId: { type: Number, default: null } // cartella di destinazione (null = radice)
})

const emit = defineEmits(['uploaded', 'finished'])

const mediaStore = useMediaStore()
const input = ref(null)
const dragging = ref(false)
const queue = ref([])
let nextId = 0

const hint = computed(() => {
  if (props.type === 'image') return 'JPEG, PNG, GIF, WebP, AVIF — max 100MB'
  if (props.type === 'video') return 'MP4, WebM, MOV — max 100MB'
  return 'Immagini (JPEG, PNG, GIF, WebP, AVIF) e video (MP4, WebM, MOV) — max 100MB'
})

const onDragLeave = (e) => {
  if (!e.currentTarget.contains(e.relatedTarget)) dragging.value = false
}

const onDrop = (e) => {
  dragging.value = false
  handleFiles(e.dataTransfer.files)
}

const onSelect = (e) => {
  handleFiles(e.target.files)
  e.target.value = ''
}

const removeFromQueue = (id) => {
  queue.value = queue.value.filter((i) => i.id !== id)
}

async function handleFiles(fileList) {
  let files = Array.from(fileList || [])
  if (!props.multiple) files = files.slice(0, 1)
  if (!files.length) return

  // Upload in sequenza: evita di saturare la banda su hosting condiviso
  for (const file of files) {
    const item = { id: ++nextId, name: file.name, status: 'uploading', progress: 0, error: '' }
    queue.value.push(item)
    const entry = queue.value[queue.value.length - 1]

    const invalid = validateFile(file, props.type)
    if (invalid) {
      entry.status = 'error'
      entry.error = invalid
      continue
    }

    try {
      const media = await mediaStore.upload(file, (p) => { entry.progress = p }, props.folderId)
      entry.status = 'done'
      emit('uploaded', media)
      setTimeout(() => removeFromQueue(entry.id), 2500)
    } catch (error) {
      entry.status = 'error'
      entry.error = mediaErrorMessage(error, 'Caricamento non riuscito')
    }
  }

  emit('finished')
}
</script>
