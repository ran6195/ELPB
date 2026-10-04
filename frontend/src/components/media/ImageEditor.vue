<template>
  <Teleport to="body">
    <div class="fixed inset-0 z-[1100] bg-gray-900 flex flex-col">
      <!-- Header -->
      <div class="flex items-center justify-between gap-4 px-5 py-3 bg-gray-800 text-white">
        <div class="min-w-0">
          <h3 class="font-semibold">Modifica immagine</h3>
          <p class="text-xs text-gray-400 truncate">{{ media.original_name }} · l'originale non viene modificato</p>
        </div>
        <div class="flex gap-2 shrink-0">
          <button
            @click="emit('close')"
            :disabled="saving"
            class="px-4 py-2 text-sm rounded-lg bg-gray-700 hover:bg-gray-600 disabled:opacity-50"
          >
            Annulla
          </button>
          <button
            @click="save"
            :disabled="!ready || saving || transforming"
            class="px-4 py-2 text-sm font-medium rounded-lg bg-primary-600 hover:bg-primary-700 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            {{ saving ? `Salvataggio... ${progress}%` : 'Salva come nuova immagine' }}
          </button>
        </div>
      </div>

      <div class="flex-1 flex flex-col lg:flex-row min-h-0">
        <!-- Area immagine -->
        <div class="flex-1 min-h-[300px] p-4 flex items-center justify-center relative">
          <div v-if="loadError" class="text-red-300 text-sm">{{ loadError }}</div>
          <div v-else-if="!ready" class="absolute inset-0 flex items-center justify-center">
            <div class="animate-spin rounded-full h-10 w-10 border-b-2 border-white"></div>
          </div>
          <div class="w-full h-full image-editor-stage" :style="{ '--img-filter': previewFilter }">
            <img ref="imageEl" :src="objectUrl || undefined" alt="" class="block max-w-full" />
          </div>
        </div>

        <!-- Pannello controlli -->
        <aside class="w-full lg:w-80 bg-white overflow-y-auto shrink-0 lg:max-h-none max-h-[45vh]">
          <div class="p-5 space-y-6 text-sm">
            <!-- Ritaglio -->
            <section>
              <h4 class="text-xs font-semibold text-gray-700 uppercase tracking-wide mb-3">Ritaglio</h4>
              <div class="grid grid-cols-3 gap-1.5">
                <button
                  v-for="r in ratios"
                  :key="r.label"
                  @click="setRatio(r.value)"
                  :class="aspect === r.value ? 'bg-primary-600 text-white border-primary-600' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50'"
                  class="px-2 py-1.5 text-xs font-medium border rounded-md"
                >
                  {{ r.label }}
                </button>
              </div>
              <div class="flex gap-1.5 mt-2">
                <button @click="rotate(-90)" class="px-2.5 py-1.5 text-sm border border-gray-300 rounded-md bg-white text-gray-700 hover:bg-gray-50 flex items-center justify-center disabled:opacity-50" title="Ruota a sinistra" :disabled="!ready || transforming">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" /></svg>
                </button>
                <button @click="rotate(90)" class="px-2.5 py-1.5 text-sm border border-gray-300 rounded-md bg-white text-gray-700 hover:bg-gray-50 flex items-center justify-center disabled:opacity-50" title="Ruota a destra" :disabled="!ready || transforming">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 10H11a8 8 0 00-8 8v2m18-10l-6 6m6-6l-6-6" /></svg>
                </button>
                <button @click="flip('x')" class="px-2.5 py-1.5 text-sm border border-gray-300 rounded-md bg-white text-gray-700 hover:bg-gray-50 flex items-center justify-center disabled:opacity-50" title="Specchia orizzontalmente" :disabled="!ready || transforming">↔</button>
                <button @click="flip('y')" class="px-2.5 py-1.5 text-sm border border-gray-300 rounded-md bg-white text-gray-700 hover:bg-gray-50 flex items-center justify-center disabled:opacity-50" title="Specchia verticalmente" :disabled="!ready || transforming">↕</button>
                <button @click="resetCrop" class="px-2.5 py-1.5 text-xs border border-gray-300 rounded-md bg-white text-gray-700 hover:bg-gray-50 flex items-center justify-center disabled:opacity-50 flex-1" title="Annulla ritaglio e rotazioni" :disabled="!ready || transforming">Reimposta</button>
              </div>
              <p class="text-xs text-gray-500 mt-2">Area selezionata: {{ cropW }} × {{ cropH }} px</p>
            </section>

            <!-- Filtri -->
            <section>
              <h4 class="text-xs font-semibold text-gray-700 uppercase tracking-wide mb-3">Filtri</h4>
              <p v-if="!filtersSupported" class="text-xs text-amber-700 bg-amber-50 border border-amber-200 rounded-md px-2.5 py-2">
                Il tuo browser non supporta l'esportazione dei filtri: aggiorna il browser per usarli. Ritaglio e ridimensionamento funzionano.
              </p>
              <template v-else>
                <div class="flex flex-wrap gap-1.5 mb-4">
                  <button
                    v-for="p in FILTER_PRESETS"
                    :key="p.id"
                    @click="applyPreset(p)"
                    :class="activePreset === p.id ? 'bg-primary-600 text-white border-primary-600' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50'"
                    class="px-2.5 py-1 text-xs font-medium border rounded-full"
                  >
                    {{ p.label }}
                  </button>
                </div>
                <div class="space-y-3">
                  <label v-for="s in sliders" :key="s.key" class="block">
                    <span class="flex justify-between text-xs text-gray-600 mb-1">
                      <span>{{ s.label }}</span>
                      <span class="tabular-nums">{{ adjustments[s.key] }}{{ s.unit }}</span>
                    </span>
                    <input
                      v-model.number="adjustments[s.key]"
                      type="range"
                      :min="s.min"
                      :max="s.max"
                      :step="s.step"
                      class="w-full accent-primary-600"
                      @input="activePreset = null"
                    />
                  </label>
                </div>
              </template>
            </section>

            <!-- Dimensioni -->
            <section>
              <h4 class="text-xs font-semibold text-gray-700 uppercase tracking-wide mb-3">Dimensioni</h4>
              <div class="flex flex-wrap gap-1.5 mb-2">
                <button
                  v-for="w in widthPresets"
                  :key="w"
                  @click="outputWidth = w"
                  :disabled="w > cropW"
                  :class="outputWidth === w ? 'bg-primary-600 text-white border-primary-600' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50'"
                  class="px-2.5 py-1 text-xs font-medium border rounded-md disabled:opacity-40 disabled:cursor-not-allowed"
                >
                  {{ w }}px
                </button>
                <button
                  @click="outputWidth = cropW"
                  :class="outputWidth === cropW ? 'bg-primary-600 text-white border-primary-600' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50'"
                  class="px-2.5 py-1 text-xs font-medium border rounded-md"
                >
                  Originale
                </button>
              </div>
              <div class="flex items-center gap-2">
                <input
                  v-model.number="outputWidth"
                  type="number"
                  min="16"
                  :max="cropW"
                  class="w-24 px-2 py-1.5 border border-gray-300 rounded-md text-sm"
                />
                <span class="text-gray-500">× {{ outputHeight }} px</span>
              </div>
              <p class="text-xs text-gray-500 mt-1">Larghezza massima {{ cropW }} px: l'immagine non viene mai ingrandita.</p>
            </section>

            <!-- Formato -->
            <section>
              <h4 class="text-xs font-semibold text-gray-700 uppercase tracking-wide mb-3">Formato</h4>
              <div class="flex gap-1.5">
                <button
                  v-for="f in formats"
                  :key="f.mime"
                  @click="format = f.mime"
                  :class="format === f.mime ? 'bg-primary-600 text-white border-primary-600' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50'"
                  class="flex-1 px-2 py-1.5 text-xs font-medium border rounded-md"
                >
                  {{ f.label }}
                </button>
              </div>
              <label v-if="format !== 'image/png'" class="block mt-3">
                <span class="flex justify-between text-xs text-gray-600 mb-1">
                  <span>Qualità</span>
                  <span class="tabular-nums">{{ quality }}%</span>
                </span>
                <input v-model.number="quality" type="range" min="50" max="100" step="1" class="w-full accent-primary-600" />
              </label>
              <p class="text-xs text-gray-500 mt-2">{{ formatHint }}</p>
            </section>

            <!-- Nome -->
            <section>
              <h4 class="text-xs font-semibold text-gray-700 uppercase tracking-wide mb-2">Nome del nuovo file</h4>
              <input v-model="name" type="text" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm" />
              <p v-if="isGif" class="text-xs text-amber-700 mt-2">Le GIF animate vengono salvate come immagine statica (primo fotogramma).</p>
            </section>

            <p v-if="saveError" class="text-xs text-red-600">{{ saveError }}</p>
          </div>
        </aside>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { ref, reactive, computed, watch, onMounted, onBeforeUnmount, nextTick } from 'vue'
import Cropper from 'cropperjs'
import 'cropperjs/dist/cropper.css'
import { useMediaStore, mediaErrorMessage } from '../../stores/mediaStore'
import {
  DEFAULT_ADJUSTMENTS,
  FILTER_PRESETS,
  presetValues,
  isDefault,
  buildFilter,
  canvasFilterSupported
} from '../../utils/imageFilters'

const props = defineProps({
  media: { type: Object, required: true }
})

const emit = defineEmits(['close', 'saved'])

const mediaStore = useMediaStore()

const imageEl = ref(null)
const objectUrl = ref('')
const ready = ref(false)
const loadError = ref('')
let cropper = null

// ===== Ritaglio =====
const ratios = [
  { label: 'Libero', value: NaN },
  { label: '1:1', value: 1 },
  { label: '4:3', value: 4 / 3 },
  { label: '3:2', value: 3 / 2 },
  { label: '16:9', value: 16 / 9 },
  { label: '9:16', value: 9 / 16 }
]
const aspect = ref(NaN)
const cropW = ref(0)
const cropH = ref(0)

function setRatio(value) {
  aspect.value = value
  cropper?.setAspectRatio(value)
}

// Rotazioni e specchiature vengono applicate alla sorgente (canvas PNG, senza perdita)
// e ricaricate in Cropper: così l'immagine si adatta all'area e il ritaglio la copre tutta.
const transforming = ref(false)
let originalUrl = ''

function loadImage(url) {
  return new Promise((resolve, reject) => {
    const img = new Image()
    img.onload = () => resolve(img)
    img.onerror = () => reject(new Error('Immagine non leggibile'))
    img.src = url
  })
}

async function transformSource(transform) {
  if (!cropper || transforming.value) return
  transforming.value = true
  try {
    const img = await loadImage(objectUrl.value)
    const canvas = document.createElement('canvas')
    transform(canvas, canvas.getContext('2d'), img.naturalWidth, img.naturalHeight, img)
    const blob = await new Promise((resolve) => canvas.toBlob(resolve, 'image/png'))
    if (!blob) throw new Error('Trasformazione non riuscita')
    replaceSource(URL.createObjectURL(blob))
  } catch (e) {
    saveError.value = e.message
  } finally {
    transforming.value = false
  }
}

function replaceSource(url) {
  if (objectUrl.value && objectUrl.value !== originalUrl) URL.revokeObjectURL(objectUrl.value)
  objectUrl.value = url
  ready.value = false
  cropper.replace(url)
}

function rotate(deg) {
  transformSource((canvas, ctx, w, h, img) => {
    canvas.width = h
    canvas.height = w
    ctx.translate(h / 2, w / 2)
    ctx.rotate((deg * Math.PI) / 180)
    ctx.drawImage(img, -w / 2, -h / 2)
  })
}

function flip(axis) {
  transformSource((canvas, ctx, w, h, img) => {
    canvas.width = w
    canvas.height = h
    if (axis === 'x') {
      ctx.translate(w, 0)
      ctx.scale(-1, 1)
    } else {
      ctx.translate(0, h)
      ctx.scale(1, -1)
    }
    ctx.drawImage(img, 0, 0)
  })
}

function resetCrop() {
  aspect.value = NaN
  cropper?.setAspectRatio(NaN)
  if (objectUrl.value !== originalUrl) replaceSource(originalUrl)
  else cropper?.reset()
}

// ===== Filtri =====
const filtersSupported = canvasFilterSupported()
const adjustments = reactive({ ...DEFAULT_ADJUSTMENTS })
const activePreset = ref('none')
const sliders = [
  { key: 'brightness', label: 'Luminosità', min: 50, max: 150, step: 1, unit: '%' },
  { key: 'contrast', label: 'Contrasto', min: 50, max: 150, step: 1, unit: '%' },
  { key: 'saturate', label: 'Saturazione', min: 0, max: 200, step: 1, unit: '%' },
  { key: 'grayscale', label: 'Bianco e nero', min: 0, max: 100, step: 1, unit: '%' },
  { key: 'sepia', label: 'Seppia', min: 0, max: 100, step: 1, unit: '%' },
  { key: 'blur', label: 'Sfocatura', min: 0, max: 8, step: 0.5, unit: 'px' }
]

function applyPreset(preset) {
  Object.assign(adjustments, presetValues(preset))
  activePreset.value = preset.id
}

const previewFilter = computed(() => (filtersSupported ? buildFilter(adjustments) : 'none'))

// ===== Dimensioni =====
const widthPresets = [1920, 1280, 800]
const outputWidth = ref(0)
const outputHeight = computed(() => (cropW.value ? Math.max(1, Math.round(outputWidth.value * cropH.value / cropW.value)) : 0))

// Il ritaglio cambia la larghezza massima: niente ingrandimenti
watch(cropW, (w, old) => {
  if (!w) return
  if (!outputWidth.value || outputWidth.value > w || outputWidth.value === old) {
    outputWidth.value = Math.min(w, 1920)
  }
})

// ===== Formato =====
const formats = [
  { mime: 'image/jpeg', label: 'JPEG', ext: 'jpg' },
  { mime: 'image/webp', label: 'WebP', ext: 'webp' },
  { mime: 'image/png', label: 'PNG', ext: 'png' }
]
const hasAlpha = ['image/png', 'image/webp', 'image/gif', 'image/avif'].includes(props.media.mime_type)
const isGif = props.media.mime_type === 'image/gif'
const format = ref(hasAlpha ? 'image/webp' : 'image/jpeg')
const quality = ref(85)
const formatHint = computed(() => ({
  'image/jpeg': 'Il più compatibile. Le parti trasparenti diventano bianche.',
  'image/webp': 'File più leggeri a parità di qualità, mantiene la trasparenza.',
  'image/png': 'Senza perdita di qualità e con trasparenza, ma file più pesanti.'
}[format.value]))

// ===== Nome =====
const baseName = props.media.original_name.replace(/\.[^.]+$/, '')
const name = ref('')
watch(format, (mime) => {
  const ext = formats.find((f) => f.mime === mime).ext
  const current = name.value.replace(/\.[^.]+$/, '') || `${baseName} (modificata)`
  name.value = `${current}.${ext}`
}, { immediate: true })

// ===== Caricamento =====
onMounted(async () => {
  try {
    const blob = await mediaStore.fetchFileBlob(props.media.id)
    objectUrl.value = URL.createObjectURL(blob)
    originalUrl = objectUrl.value
    await nextTick()
    cropper = new Cropper(imageEl.value, {
      viewMode: 1,
      autoCropArea: 1,
      background: false,
      responsive: true,
      dragMode: 'move',
      ready() {
        ready.value = true
        updateCropSize()
      },
      crop: updateCropSize
    })
  } catch (e) {
    loadError.value = mediaErrorMessage(e, 'Impossibile caricare l\'immagine')
  }
})

function updateCropSize() {
  if (!cropper) return
  const { width, height } = cropper.getData(true)
  cropW.value = width
  cropH.value = height
}

onBeforeUnmount(() => {
  cropper?.destroy()
  if (objectUrl.value && objectUrl.value !== originalUrl) URL.revokeObjectURL(objectUrl.value)
  if (originalUrl) URL.revokeObjectURL(originalUrl)
})

// ===== Esportazione =====
const saving = ref(false)
const progress = ref(0)
const saveError = ref('')

function exportBlob() {
  const w = Math.max(1, Math.min(Math.round(outputWidth.value) || cropW.value, cropW.value))
  const h = Math.max(1, Math.round(w * cropH.value / cropW.value))
  const opaque = format.value === 'image/jpeg'

  const cropped = cropper.getCroppedCanvas({
    width: w,
    height: h,
    imageSmoothingEnabled: true,
    imageSmoothingQuality: 'high',
    fillColor: opaque ? '#ffffff' : 'transparent'
  })

  let canvas = cropped
  if (filtersSupported && !isDefault(adjustments)) {
    // La sfocatura è definita sui pixel dell'anteprima: va riportata alla scala dell'immagine esportata
    const canvasData = cropper.getCanvasData()
    const previewScale = canvasData.width / canvasData.naturalWidth
    const blurScale = (w / cropW.value) / (previewScale || 1)

    canvas = document.createElement('canvas')
    canvas.width = w
    canvas.height = h
    const ctx = canvas.getContext('2d')
    if (opaque) {
      ctx.fillStyle = '#ffffff'
      ctx.fillRect(0, 0, w, h)
    }
    ctx.filter = buildFilter(adjustments, blurScale)
    ctx.drawImage(cropped, 0, 0)
  }

  return new Promise((resolve, reject) => {
    canvas.toBlob(
      (blob) => (blob ? resolve(blob) : reject(new Error('Esportazione non riuscita'))),
      format.value,
      format.value === 'image/png' ? undefined : quality.value / 100
    )
  })
}

async function save() {
  if (!cropper || saving.value) return
  saving.value = true
  progress.value = 0
  saveError.value = ''
  try {
    const blob = await exportBlob()
    const fileName = name.value.trim() || `${baseName} (modificata).${formats.find((f) => f.mime === format.value).ext}`
    const media = await mediaStore.saveVersion(props.media.id, blob, fileName, (p) => { progress.value = p })
    emit('saved', media)
  } catch (e) {
    saveError.value = mediaErrorMessage(e, 'Salvataggio non riuscito')
  } finally {
    saving.value = false
  }
}
</script>

<style scoped>
/* Anteprima filtri sull'immagine dentro Cropper (non sulla cornice di ritaglio) */
.image-editor-stage :deep(.cropper-canvas img),
.image-editor-stage :deep(.cropper-view-box img) {
  filter: var(--img-filter);
}
</style>
