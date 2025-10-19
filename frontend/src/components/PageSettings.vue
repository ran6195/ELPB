<template>
  <div class="space-y-6">
    <div>
      <h3 class="text-xs font-semibold text-gray-500 uppercase mb-4 tracking-wide">
        Impostazioni Pagina
      </h3>
    </div>

    <!-- Titolo -->
    <div>
      <label class="block text-xs font-medium text-gray-700 mb-2">Titolo Pagina</label>
      <input
        v-model="localPage.title"
        type="text"
        placeholder="Es: Homepage, Chi Siamo, Contatti"
        class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-200 focus:border-primary-500 transition-all outline-none text-sm"
      />
    </div>

    <!-- Slug URL -->
    <div>
      <label class="block text-xs font-medium text-gray-700 mb-2">Slug URL</label>
      <input
        v-model="localPage.slug"
        type="text"
        placeholder="es: homepage, chi-siamo, contatti"
        class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-200 focus:border-primary-500 transition-all outline-none text-sm font-mono"
      />
      <p class="text-xs text-gray-500 mt-1">URL: /{{ localPage.slug }}</p>
    </div>

    <!-- Stili Pagina -->
    <div class="border-t border-gray-200 pt-6">
      <h4 class="text-xs font-semibold text-gray-500 uppercase mb-4 tracking-wide">
        Stili Pagina
      </h4>

      <!-- Background Color -->
      <div class="mb-5">
        <label class="block text-xs font-medium text-gray-700 mb-2">Colore di Sfondo</label>
        <div class="flex items-center gap-3">
          <input
            v-model="pageBackgroundColor"
            type="color"
            class="h-10 w-20 rounded border border-gray-300 cursor-pointer"
          />
          <input
            v-model="pageBackgroundColor"
            type="text"
            placeholder="#FFFFFF"
            class="flex-1 px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-200 focus:border-primary-500 transition-all outline-none text-sm font-mono"
          />
        </div>
      </div>

      <!-- Block Gap -->
      <div class="mb-5">
        <label class="block text-xs font-medium text-gray-700 mb-2">Spaziatura tra Blocchi (px)</label>
        <input
          v-model.number="blockGap"
          type="number"
          min="0"
          max="100"
          placeholder="15"
          class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-200 focus:border-primary-500 transition-all outline-none text-sm"
        />
        <p class="text-xs text-gray-500 mt-1">Spazio verticale tra i blocchi (default: 15px)</p>
      </div>

      <!-- Font Family -->
      <div>
        <label class="block text-xs font-medium text-gray-700 mb-2">Font della Pagina</label>
        <select
          v-model="fontFamily"
          class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-200 focus:border-primary-500 transition-all outline-none text-sm"
        >
          <option value="">Default (System)</option>
          <option value="Roboto">Roboto</option>
          <option value="Open Sans">Open Sans</option>
          <option value="Lato">Lato</option>
          <option value="Montserrat">Montserrat</option>
          <option value="Poppins">Poppins</option>
          <option value="Raleway">Raleway</option>
          <option value="Playfair Display">Playfair Display</option>
          <option value="Merriweather">Merriweather</option>
          <option value="Inter">Inter</option>
          <option value="Nunito">Nunito</option>
          <option value="Oswald">Oswald</option>
          <option value="PT Sans">PT Sans</option>
          <option value="Source Sans Pro">Source Sans Pro</option>
          <option value="Ubuntu">Ubuntu</option>
        </select>
        <p class="text-xs text-gray-500 mt-1">Font da Google Fonts applicato a tutta la pagina</p>
      </div>
    </div>

    <!-- SEO Section -->
    <div class="border-t border-gray-200 pt-6">
      <h4 class="text-xs font-semibold text-gray-500 uppercase mb-4 tracking-wide">
        SEO & Meta Tags
      </h4>

      <!-- Meta Title -->
      <div class="mb-5">
        <label class="block text-xs font-medium text-gray-700 mb-2">Meta Title</label>
        <input
          v-model="localPage.meta_title"
          type="text"
          placeholder="Titolo per i motori di ricerca (max 60 caratteri)"
          maxlength="60"
          class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-200 focus:border-primary-500 transition-all outline-none text-sm"
        />
        <p class="text-xs text-gray-500 mt-1">{{ localPage.meta_title?.length || 0 }}/60 caratteri</p>
      </div>

      <!-- Meta Description -->
      <div>
        <label class="block text-xs font-medium text-gray-700 mb-2">Meta Description</label>
        <textarea
          v-model="localPage.meta_description"
          placeholder="Descrizione per i motori di ricerca (max 160 caratteri)"
          maxlength="160"
          rows="3"
          class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-200 focus:border-primary-500 transition-all outline-none text-sm"
        ></textarea>
        <p class="text-xs text-gray-500 mt-1">{{ localPage.meta_description?.length || 0 }}/160 caratteri</p>
      </div>
    </div>

    <!-- Pubblicazione -->
    <div class="border-t border-gray-200 pt-6">
      <h4 class="text-xs font-semibold text-gray-500 uppercase mb-4 tracking-wide">
        Pubblicazione
      </h4>

      <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
        <div>
          <p class="text-sm font-medium text-gray-900">Stato Pubblicazione</p>
          <p class="text-xs text-gray-500 mt-0.5">
            {{ localPage.is_published ? 'Pagina visibile pubblicamente' : 'Pagina in bozza' }}
          </p>
        </div>
        <button
          @click="togglePublish"
          :class="[
            'relative inline-flex h-6 w-11 items-center rounded-full transition-colors',
            localPage.is_published ? 'bg-primary-600' : 'bg-gray-200'
          ]"
        >
          <span
            :class="[
              'inline-block h-4 w-4 transform rounded-full bg-white transition-transform',
              localPage.is_published ? 'translate-x-6' : 'translate-x-1'
            ]"
          />
        </button>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, watch, computed } from 'vue'

const props = defineProps({
  page: {
    type: Object,
    required: true
  }
})

const emit = defineEmits(['update'])

const localPage = ref({
  ...props.page,
  styles: props.page.styles || { backgroundColor: '#FFFFFF', blockGap: 15 }
})

let updateTimeout = null

// Aggiorna localPage solo quando cambia l'ID della pagina (non il contenuto)
watch(() => props.page.id, () => {
  localPage.value = {
    ...props.page,
    styles: props.page.styles || { backgroundColor: '#FFFFFF', blockGap: 15 }
  }
})

// Auto-save con debounce quando localPage cambia
watch(localPage, () => {
  if (updateTimeout) clearTimeout(updateTimeout)
  updateTimeout = setTimeout(() => {
    emit('update', localPage.value)
  }, 300) // 300ms debounce
}, { deep: true })

const pageBackgroundColor = computed({
  get() {
    return localPage.value.styles?.backgroundColor || '#FFFFFF'
  },
  set(value) {
    if (!localPage.value.styles) {
      localPage.value.styles = {}
    }
    localPage.value.styles.backgroundColor = value
  }
})

const blockGap = computed({
  get() {
    return localPage.value.styles?.blockGap ?? 15
  },
  set(value) {
    if (!localPage.value.styles) {
      localPage.value.styles = {}
    }
    localPage.value.styles.blockGap = value
  }
})

const fontFamily = computed({
  get() {
    return localPage.value.styles?.fontFamily || ''
  },
  set(value) {
    if (!localPage.value.styles) {
      localPage.value.styles = {}
    }
    localPage.value.styles.fontFamily = value
  }
})

const togglePublish = () => {
  localPage.value.is_published = !localPage.value.is_published
}
</script>
