<template>
  <div class="space-y-6">
    <div class="border-b border-gray-200 pb-4">
      <h4 class="text-xs font-semibold text-gray-500 uppercase mb-3 tracking-wide">
        Tipo Blocco
      </h4>
      <p class="text-sm text-gray-900 capitalize font-medium">{{ block.type }}</p>
    </div>

    <!-- Hero Block Editor -->
    <div v-if="block.type === 'hero'" class="space-y-5">
      <div>
        <label class="block text-xs font-medium text-gray-700 mb-2">Titolo</label>
        <input
          v-model="localBlock.content.title"
          type="text"
          class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-200 focus:border-primary-500 transition-all outline-none text-sm"
        />
      </div>
      <div>
        <label class="block text-xs font-medium text-gray-700 mb-2">Sottotitolo</label>
        <input
          v-model="localBlock.content.subtitle"
          type="text"
          class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-200 focus:border-primary-500 transition-all outline-none text-sm"
        />
      </div>
      <div>
        <label class="block text-xs font-medium text-gray-700 mb-2">Testo Pulsante</label>
        <input
          v-model="localBlock.content.buttonText"
          type="text"
          class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-200 focus:border-primary-500 transition-all outline-none text-sm"
        />
      </div>
      <div>
        <label class="block text-xs font-medium text-gray-700 mb-2">Link Pulsante</label>
        <input
          v-model="localBlock.content.buttonLink"
          type="text"
          class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-200 focus:border-primary-500 transition-all outline-none text-sm"
        />
      </div>
    </div>

    <!-- Text Block Editor -->
    <div v-else-if="block.type === 'text'" class="space-y-5">
      <div>
        <label class="block text-xs font-medium text-gray-700 mb-2">Titolo</label>
        <input
          v-model="localBlock.content.title"
          type="text"
          class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-200 focus:border-primary-500 transition-all outline-none text-sm"
        />
      </div>
      <div>
        <label class="block text-xs font-medium text-gray-700 mb-2">Testo</label>
        <textarea
          v-model="localBlock.content.text"
          rows="4"
          class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-200 focus:border-primary-500 transition-all outline-none text-sm"
        ></textarea>
      </div>
    </div>

    <!-- Form Block Editor -->
    <div v-else-if="block.type === 'form'" class="space-y-5">
      <div>
        <label class="block text-xs font-medium text-gray-700 mb-2">Titolo Form</label>
        <input
          v-model="localBlock.content.title"
          type="text"
          class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-200 focus:border-primary-500 transition-all outline-none text-sm"
        />
      </div>
      <div>
        <label class="block text-xs font-medium text-gray-700 mb-2">Testo Pulsante</label>
        <input
          v-model="localBlock.content.buttonText"
          type="text"
          class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-200 focus:border-primary-500 transition-all outline-none text-sm"
        />
      </div>
    </div>

    <!-- Two Column Blocks Editor -->
    <div v-else-if="block.type === 'two-column-text-image' || block.type === 'two-column-image-text'" class="space-y-5">
      <div>
        <label class="block text-xs font-medium text-gray-700 mb-2">Titolo</label>
        <input
          v-model="localBlock.content.title"
          type="text"
          class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-200 focus:border-primary-500 transition-all outline-none text-sm"
        />
      </div>
      <div>
        <label class="block text-xs font-medium text-gray-700 mb-2">Testo</label>
        <textarea
          v-model="localBlock.content.text"
          rows="4"
          class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-200 focus:border-primary-500 transition-all outline-none text-sm"
        ></textarea>
      </div>
      <div>
        <label class="block text-xs font-medium text-gray-700 mb-2">URL Immagine</label>
        <input
          v-model="localBlock.content.image"
          type="text"
          placeholder="https://example.com/image.jpg oppure carica un file"
          class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-200 focus:border-primary-500 transition-all outline-none text-sm"
        />
      </div>
      <div>
        <label class="block text-xs font-medium text-gray-700 mb-2">Oppure carica un'immagine</label>
        <input
          type="file"
          accept="image/*"
          @change="handleImageUpload"
          class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-primary-50 file:text-primary-700 hover:file:bg-primary-100"
        />
      </div>
    </div>

    <!-- Footer Block Editor -->
    <div v-else-if="block.type === 'footer'" class="space-y-5">
      <div>
        <label class="block text-xs font-medium text-gray-700 mb-2">Nome Azienda</label>
        <input
          v-model="localBlock.content.companyName"
          type="text"
          class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-200 focus:border-primary-500 transition-all outline-none text-sm"
        />
      </div>
      <div>
        <label class="block text-xs font-medium text-gray-700 mb-2">Descrizione Azienda</label>
        <textarea
          v-model="localBlock.content.companyDescription"
          rows="3"
          class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-200 focus:border-primary-500 transition-all outline-none text-sm"
        ></textarea>
      </div>
      <div>
        <label class="block text-xs font-medium text-gray-700 mb-2">Email</label>
        <input
          v-model="localBlock.content.email"
          type="email"
          class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-200 focus:border-primary-500 transition-all outline-none text-sm"
        />
      </div>
      <div>
        <label class="block text-xs font-medium text-gray-700 mb-2">Telefono</label>
        <input
          v-model="localBlock.content.phone"
          type="tel"
          class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-200 focus:border-primary-500 transition-all outline-none text-sm"
        />
      </div>
      <div>
        <label class="block text-xs font-medium text-gray-700 mb-2">Indirizzo</label>
        <input
          v-model="localBlock.content.address"
          type="text"
          class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-200 focus:border-primary-500 transition-all outline-none text-sm"
        />
      </div>
      <div>
        <label class="block text-xs font-medium text-gray-700 mb-2">Copyright</label>
        <input
          v-model="localBlock.content.copyright"
          type="text"
          class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-200 focus:border-primary-500 transition-all outline-none text-sm"
        />
      </div>
    </div>

    <!-- Stili comuni -->
    <div class="border-t border-gray-200 pt-6 space-y-5">
      <h4 class="text-xs font-semibold text-gray-500 uppercase mb-4 tracking-wide">
        Stili
      </h4>

      <div>
        <label class="block text-xs font-medium text-gray-700 mb-2">Colore Sfondo</label>
        <input
          v-model="localBlock.styles.backgroundColor"
          type="color"
          class="w-full h-11 rounded-lg cursor-pointer border border-gray-300"
        />
      </div>

      <div>
        <label class="block text-xs font-medium text-gray-700 mb-2">Colore Testo</label>
        <input
          v-model="localBlock.styles.textColor"
          type="color"
          class="w-full h-11 rounded-lg cursor-pointer border border-gray-300"
        />
      </div>

      <div>
        <label class="block text-xs font-medium text-gray-700 mb-2">Padding</label>
        <input
          v-model="localBlock.styles.padding"
          type="text"
          placeholder="es: 60px 20px"
          class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-200 focus:border-primary-500 transition-all outline-none text-sm"
        />
      </div>
    </div>

    <button
      @click="saveChanges"
      class="w-full bg-primary-600 hover:bg-primary-700 text-white py-2.5 rounded-lg text-sm font-medium transition-colors mt-6 shadow-sm"
    >
      Applica Modifiche
    </button>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue'

const props = defineProps({
  block: {
    type: Object,
    required: true
  }
})

const emit = defineEmits(['update'])

const localBlock = ref(JSON.parse(JSON.stringify(props.block)))

watch(() => props.block, (newBlock) => {
  localBlock.value = JSON.parse(JSON.stringify(newBlock))
}, { deep: true })

const saveChanges = () => {
  emit('update', localBlock.value)
}

const handleImageUpload = async (event) => {
  const file = event.target.files[0]
  if (file) {
    const formData = new FormData()
    formData.append('image', file)

    try {
      const response = await fetch('http://localhost:8000/api/upload/image', {
        method: 'POST',
        body: formData
      })

      const data = await response.json()

      if (data.success) {
        localBlock.value.content.image = data.url
      } else {
        alert('Errore durante il caricamento dell\'immagine: ' + (data.error || 'Errore sconosciuto'))
      }
    } catch (error) {
      alert('Errore durante il caricamento dell\'immagine')
      console.error(error)
    }
  }
}
</script>
