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
        <RichTextEditor v-model="localBlock.content.text" />
      </div>
    </div>

    <!-- Image Slide Block Editor -->
    <div v-else-if="block.type === 'image-slide'" class="space-y-5">
      <div>
        <label class="block text-xs font-medium text-gray-700 mb-2">URL Immagine</label>
        <input
          v-model="localBlock.content.image"
          type="text"
          placeholder="https://example.com/image.jpg"
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

      <!-- Anteprima immagine -->
      <div v-if="localBlock.content.image" class="mt-2">
        <img
          :src="localBlock.content.image"
          alt="Anteprima"
          class="w-full h-48 object-cover rounded-lg border border-gray-300"
        />
      </div>

      <div>
        <label class="block text-xs font-medium text-gray-700 mb-2">Testo alternativo</label>
        <input
          v-model="localBlock.content.alt"
          type="text"
          placeholder="Descrizione immagine"
          class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-200 focus:border-primary-500 transition-all outline-none text-sm"
        />
      </div>

      <div>
        <label class="block text-xs font-medium text-gray-700 mb-2">Altezza Diapositiva</label>
        <select
          v-model="localBlock.content.height"
          class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-200 focus:border-primary-500 transition-all outline-none text-sm"
        >
          <option value="400px">Piccola (400px)</option>
          <option value="600px">Media (600px)</option>
          <option value="800px">Grande (800px)</option>
          <option value="100vh">Schermo intero</option>
        </select>
      </div>

      <div>
        <label class="block text-xs font-medium text-gray-700 mb-2">Larghezza Immagine</label>
        <select
          v-model="localBlock.content.fullWidth"
          class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-200 focus:border-primary-500 transition-all outline-none text-sm"
        >
          <option :value="true">Tutta larghezza (Full Width)</option>
          <option :value="false">Larghezza limitata (Contenuta)</option>
        </select>
        <p class="text-xs text-gray-500 mt-1">Scegli se l'immagine deve occupare tutta la larghezza o essere contenuta come gli altri blocchi</p>
      </div>

      <!-- Overlay opzionale -->
      <div class="border-t border-gray-200 pt-4">
        <div class="flex items-center justify-between mb-4">
          <label class="text-xs font-medium text-gray-700">Overlay con Testo</label>
          <button
            @click="localBlock.content.showOverlay = !localBlock.content.showOverlay"
            :class="[
              'relative inline-flex h-6 w-11 items-center rounded-full transition-colors',
              localBlock.content.showOverlay ? 'bg-primary-600' : 'bg-gray-200'
            ]"
          >
            <span
              :class="[
                'inline-block h-4 w-4 transform rounded-full bg-white transition-transform',
                localBlock.content.showOverlay ? 'translate-x-6' : 'translate-x-1'
              ]"
            />
          </button>
        </div>

        <div v-if="localBlock.content.showOverlay" class="space-y-3">
          <div>
            <label class="block text-xs font-medium text-gray-700 mb-2">Titolo Overlay</label>
            <input
              v-model="localBlock.content.overlayTitle"
              type="text"
              placeholder="Titolo grande sopra l'immagine"
              class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-200 focus:border-primary-500 transition-all outline-none text-sm"
            />
          </div>

          <div>
            <label class="block text-xs font-medium text-gray-700 mb-2">Testo Overlay</label>
            <textarea
              v-model="localBlock.content.overlayText"
              rows="2"
              placeholder="Testo descrittivo"
              class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-200 focus:border-primary-500 transition-all outline-none text-sm"
            ></textarea>
          </div>

          <div>
            <label class="block text-xs font-medium text-gray-700 mb-2">Colore Sfondo Overlay</label>
            <div class="flex items-center gap-3">
              <input
                v-model="localBlock.content.overlayColor"
                type="color"
                class="h-11 w-20 rounded-lg cursor-pointer border border-gray-300"
              />
              <input
                v-model="localBlock.content.overlayColor"
                type="text"
                placeholder="#000000"
                class="flex-1 px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-200 focus:border-primary-500 transition-all outline-none text-sm font-mono"
              />
            </div>
          </div>

          <div>
            <label class="block text-xs font-medium text-gray-700 mb-2">Opacità Overlay ({{ localBlock.content.overlayOpacity }})</label>
            <input
              v-model.number="localBlock.content.overlayOpacity"
              type="range"
              min="0"
              max="1"
              step="0.1"
              class="w-full"
            />
          </div>

          <div>
            <label class="block text-xs font-medium text-gray-700 mb-2">Colore Testo Overlay</label>
            <div class="flex items-center gap-3">
              <input
                v-model="localBlock.content.overlayTextColor"
                type="color"
                class="h-11 w-20 rounded-lg cursor-pointer border border-gray-300"
              />
              <input
                v-model="localBlock.content.overlayTextColor"
                type="text"
                placeholder="#FFFFFF"
                class="flex-1 px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-200 focus:border-primary-500 transition-all outline-none text-sm font-mono"
              />
            </div>
          </div>
        </div>
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

    <!-- Header Block Editor -->
    <div v-else-if="block.type === 'header'" class="space-y-5">
      <div>
        <label class="block text-xs font-medium text-gray-700 mb-2">URL Logo</label>
        <input
          v-model="localBlock.content.logoUrl"
          type="text"
          placeholder="https://example.com/logo.png"
          class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-200 focus:border-primary-500 transition-all outline-none text-sm"
        />
      </div>
      <div>
        <label class="block text-xs font-medium text-gray-700 mb-2">Oppure carica un logo</label>
        <input
          type="file"
          accept="image/*"
          @change="handleImageUpload"
          class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-primary-50 file:text-primary-700 hover:file:bg-primary-100"
        />
      </div>
      <div>
        <label class="block text-xs font-medium text-gray-700 mb-2">Testo alternativo Logo</label>
        <input
          v-model="localBlock.content.logoAlt"
          type="text"
          placeholder="es: Logo Azienda"
          class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-200 focus:border-primary-500 transition-all outline-none text-sm"
        />
      </div>
      <div>
        <label class="block text-xs font-medium text-gray-700 mb-2">Link Logo</label>
        <input
          v-model="localBlock.content.logoLink"
          type="text"
          placeholder="es: / o #home"
          class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-200 focus:border-primary-500 transition-all outline-none text-sm"
        />
      </div>
      <div>
        <label class="block text-xs font-medium text-gray-700 mb-2">Altezza Logo (px)</label>
        <input
          v-model="localBlock.content.logoHeight"
          type="text"
          placeholder="es: 50px"
          class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-200 focus:border-primary-500 transition-all outline-none text-sm"
        />
      </div>
      <div>
        <label class="block text-xs font-medium text-gray-700 mb-2">Margin Top (px)</label>
        <input
          v-model="localBlock.content.marginTop"
          type="text"
          placeholder="es: 0px o 20px"
          class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-200 focus:border-primary-500 transition-all outline-none text-sm"
        />
        <p class="text-xs text-gray-500 mt-1">Spazio sopra l'intestazione (default: 0px)</p>
      </div>
    </div>

    <!-- Footer Block Editor -->
    <div v-else-if="block.type === 'footer'" class="space-y-5">
      <!-- Sezione La Tua Azienda -->
      <div class="border-b border-gray-200 pb-4">
        <h4 class="text-xs font-semibold text-gray-500 uppercase mb-4 tracking-wide">
          Sezione La Tua Azienda
        </h4>
        <div class="space-y-3">
          <div>
            <label class="block text-xs font-medium text-gray-700 mb-2">Titolo Sezione</label>
            <input
              v-model="localBlock.content.companyTitle"
              type="text"
              placeholder="es: La Nostra Azienda"
              class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-200 focus:border-primary-500 transition-all outline-none text-sm"
            />
          </div>
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
        </div>
      </div>

      <!-- Sezione Link -->
      <div class="border-b border-gray-200 pb-4">
        <div class="flex justify-between items-center mb-4">
          <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wide">
            Sezione Link
          </h4>
          <button
            @click="addFooterLink"
            class="bg-primary-600 text-white px-3 py-1.5 rounded-lg text-xs font-medium hover:bg-primary-700 transition-colors"
          >
            + Aggiungi Link
          </button>
        </div>

        <div class="space-y-3 mb-4">
          <div>
            <label class="block text-xs font-medium text-gray-700 mb-2">Titolo Sezione</label>
            <input
              v-model="localBlock.content.linksTitle"
              type="text"
              placeholder="es: Link Utili"
              class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-200 focus:border-primary-500 transition-all outline-none text-sm"
            />
          </div>
        </div>

        <!-- Lista link -->
        <div class="space-y-3">
          <div
            v-for="(link, index) in localBlock.content.links"
            :key="index"
            class="border border-gray-300 rounded-lg p-3"
          >
            <div class="flex justify-between items-center mb-3">
              <span class="text-xs font-medium text-gray-700">Link {{ index + 1 }}</span>
              <button
                @click="removeFooterLink(index)"
                class="text-red-600 hover:text-red-800 text-xs font-medium"
              >
                Rimuovi
              </button>
            </div>
            <div class="space-y-2">
              <input
                v-model="localBlock.content.links[index].text"
                type="text"
                placeholder="Testo del link"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-200 focus:border-primary-500 transition-all outline-none text-sm"
              />
              <input
                v-model="localBlock.content.links[index].url"
                type="text"
                placeholder="URL (es: #servizi o https://...)"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-200 focus:border-primary-500 transition-all outline-none text-sm"
              />
            </div>
          </div>
          <p v-if="localBlock.content.links.length === 0" class="text-xs text-gray-500 text-center py-4">
            Nessun link aggiunto. Clicca "+ Aggiungi Link" per iniziare.
          </p>
        </div>
      </div>

      <!-- Sezione Contatti -->
      <div class="border-b border-gray-200 pb-4">
        <h4 class="text-xs font-semibold text-gray-500 uppercase mb-4 tracking-wide">
          Sezione Contatti
        </h4>
        <div class="space-y-3">
          <div>
            <label class="block text-xs font-medium text-gray-700 mb-2">Titolo Sezione</label>
            <input
              v-model="localBlock.content.contactTitle"
              type="text"
              placeholder="es: Contatti"
              class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-200 focus:border-primary-500 transition-all outline-none text-sm"
            />
          </div>
          <div>
            <label class="block text-xs font-medium text-gray-700 mb-2">Contenuto Contatti</label>
            <p class="text-xs text-gray-500 mb-2">Usa il rich text editor per formattare il testo e aggiungere link</p>
            <RichTextEditor v-model="localBlock.content.contactText" />
          </div>
        </div>
      </div>

      <!-- Copyright -->
      <div>
        <h4 class="text-xs font-semibold text-gray-500 uppercase mb-4 tracking-wide">
          Copyright
        </h4>
        <div>
          <label class="block text-xs font-medium text-gray-700 mb-2">Testo Copyright</label>
          <input
            v-model="localBlock.content.copyright"
            type="text"
            placeholder="es: © 2025 La Tua Azienda. Tutti i diritti riservati."
            class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-200 focus:border-primary-500 transition-all outline-none text-sm"
          />
        </div>
      </div>
    </div>

    <!-- Services Grid Block Editor -->
    <div v-else-if="block.type === 'services-grid'" class="space-y-5">
      <div>
        <label class="block text-xs font-medium text-gray-700 mb-2">Titolo Sezione</label>
        <input
          v-model="localBlock.content.title"
          type="text"
          class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-200 focus:border-primary-500 transition-all outline-none text-sm"
        />
      </div>

      <!-- Servizi -->
      <div class="border-t border-gray-200 pt-4">
        <h4 class="text-xs font-semibold text-gray-500 uppercase mb-4 tracking-wide">
          Servizi
        </h4>

        <div
          v-for="(service, index) in localBlock.content.services"
          :key="index"
          class="border border-gray-300 rounded-lg p-4 mb-4"
        >
          <div class="flex justify-between items-center mb-3">
            <h5 class="font-medium text-sm text-gray-700">Servizio {{ index + 1 }}</h5>
          </div>

          <div class="space-y-3">
            <div>
              <label class="block text-xs font-medium text-gray-700 mb-2">Titolo</label>
              <input
                v-model="localBlock.content.services[index].title"
                type="text"
                class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-200 focus:border-primary-500 transition-all outline-none text-sm"
              />
            </div>

            <div>
              <label class="block text-xs font-medium text-gray-700 mb-2">Descrizione</label>
              <textarea
                v-model="localBlock.content.services[index].description"
                rows="3"
                class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-200 focus:border-primary-500 transition-all outline-none text-sm"
              ></textarea>
            </div>

            <div>
              <label class="block text-xs font-medium text-gray-700 mb-2">URL Immagine</label>
              <input
                v-model="localBlock.content.services[index].image"
                type="text"
                placeholder="https://example.com/image.jpg"
                class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-200 focus:border-primary-500 transition-all outline-none text-sm"
              />
            </div>

            <div>
              <label class="block text-xs font-medium text-gray-700 mb-2">Oppure carica un'immagine</label>
              <input
                type="file"
                accept="image/*"
                @change="(e) => handleServiceImageUpload(e, index)"
                class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-primary-50 file:text-primary-700 hover:file:bg-primary-100"
              />
            </div>

            <!-- Anteprima immagine -->
            <div v-if="localBlock.content.services[index].image" class="mt-2">
              <img
                :src="localBlock.content.services[index].image"
                alt="Anteprima"
                class="w-full h-32 object-cover rounded-lg border border-gray-300"
              />
            </div>

            <div>
              <label class="block text-xs font-medium text-gray-700 mb-2">Link (opzionale)</label>
              <input
                v-model="localBlock.content.services[index].link"
                type="text"
                placeholder="https://example.com"
                class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-200 focus:border-primary-500 transition-all outline-none text-sm"
              />
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Stili comuni -->
    <div class="border-t border-gray-200 pt-6 space-y-5">
      <h4 class="text-xs font-semibold text-gray-500 uppercase mb-4 tracking-wide">
        Stili
      </h4>

      <div>
        <label class="block text-xs font-medium text-gray-700 mb-3">Colore Sfondo</label>

        <!-- Preset di colori -->
        <div class="grid grid-cols-6 gap-2 mb-3">
          <button
            v-for="preset in colorPresets"
            :key="preset.value"
            @click="localBlock.styles.backgroundColor = preset.value"
            :style="{ backgroundColor: preset.value }"
            :class="[
              'h-10 rounded-lg border-2 transition-all relative',
              localBlock.styles.backgroundColor === preset.value
                ? 'border-primary-500 ring-2 ring-primary-200'
                : 'border-gray-300 hover:border-gray-400'
            ]"
            :title="preset.label"
          >
            <!-- Icona check per colore selezionato -->
            <span
              v-if="localBlock.styles.backgroundColor === preset.value"
              class="absolute inset-0 flex items-center justify-center"
            >
              <svg class="w-5 h-5" :class="preset.value === '#FFFFFF' || preset.value === 'transparent' ? 'text-gray-800' : 'text-white'" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
              </svg>
            </span>
          </button>
        </div>

        <!-- Color picker personalizzato -->
        <div class="flex items-center gap-3">
          <input
            v-model="localBlock.styles.backgroundColor"
            type="color"
            class="h-11 w-20 rounded-lg cursor-pointer border border-gray-300"
          />
          <input
            v-model="localBlock.styles.backgroundColor"
            type="text"
            placeholder="es: #FFFFFF o transparent"
            class="flex-1 px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-200 focus:border-primary-500 transition-all outline-none text-sm font-mono"
          />
        </div>
      </div>

      <div>
        <label class="block text-xs font-medium text-gray-700 mb-3">Colore Testo</label>

        <!-- Preset colori testo -->
        <div class="grid grid-cols-6 gap-2 mb-3">
          <button
            v-for="textPreset in textColorPresets"
            :key="textPreset.value"
            @click="localBlock.styles.textColor = textPreset.value"
            :style="{ backgroundColor: textPreset.value }"
            :class="[
              'h-10 rounded-lg border-2 transition-all relative',
              localBlock.styles.textColor === textPreset.value
                ? 'border-primary-500 ring-2 ring-primary-200'
                : 'border-gray-300 hover:border-gray-400'
            ]"
            :title="textPreset.label"
          >
            <span
              v-if="localBlock.styles.textColor === textPreset.value"
              class="absolute inset-0 flex items-center justify-center"
            >
              <svg class="w-5 h-5" :class="textPreset.value === '#FFFFFF' ? 'text-gray-800' : 'text-white'" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
              </svg>
            </span>
          </button>
        </div>

        <div class="flex items-center gap-3">
          <input
            v-model="localBlock.styles.textColor"
            type="color"
            class="h-11 w-20 rounded-lg cursor-pointer border border-gray-300"
          />
          <input
            v-model="localBlock.styles.textColor"
            type="text"
            placeholder="es: #000000 o inherit"
            class="flex-1 px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-200 focus:border-primary-500 transition-all outline-none text-sm font-mono"
          />
        </div>
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

  </div>
</template>

<script setup>
import { ref, watch } from 'vue'
import RichTextEditor from './RichTextEditor.vue'

const props = defineProps({
  block: {
    type: Object,
    required: true
  }
})

const emit = defineEmits(['update'])

// Inizializza localBlock con styles se non esistono
const initBlock = (block) => {
  const clonedBlock = JSON.parse(JSON.stringify(block))
  // Assicura che styles sia un oggetto e abbia tutte le proprietà necessarie
  if (!clonedBlock.styles || Object.keys(clonedBlock.styles).length === 0) {
    clonedBlock.styles = {
      backgroundColor: 'transparent',
      textColor: 'inherit',
      padding: '40px 20px'
    }
  } else {
    // Aggiungi valori di default per proprietà mancanti
    clonedBlock.styles = {
      backgroundColor: clonedBlock.styles.backgroundColor || 'transparent',
      textColor: clonedBlock.styles.textColor || 'inherit',
      padding: clonedBlock.styles.padding || '40px 20px'
    }
  }

  // Per blocchi footer vecchi, converti la struttura se necessario
  if (clonedBlock.type === 'footer' && clonedBlock.content) {
    // Aggiungi companyTitle se non esiste
    if (!clonedBlock.content.companyTitle) {
      clonedBlock.content.companyTitle = ''
    }

    // Converti email/phone/address in contactText se non esiste
    if (!clonedBlock.content.contactText && (clonedBlock.content.email || clonedBlock.content.phone || clonedBlock.content.address)) {
      let contactParts = []
      if (clonedBlock.content.email) {
        contactParts.push(`<p><strong>Email:</strong> <a href="mailto:${clonedBlock.content.email}">${clonedBlock.content.email}</a></p>`)
      }
      if (clonedBlock.content.phone) {
        contactParts.push(`<p><strong>Telefono:</strong> <a href="tel:${clonedBlock.content.phone.replace(/\s/g, '')}">${clonedBlock.content.phone}</a></p>`)
      }
      if (clonedBlock.content.address) {
        contactParts.push(`<p><strong>Indirizzo:</strong> ${clonedBlock.content.address}</p>`)
      }
      clonedBlock.content.contactText = contactParts.join('')
    }

    // Assicura che links sia sempre un array
    if (!clonedBlock.content.links) {
      clonedBlock.content.links = []
    }
  }

  return clonedBlock
}

const localBlock = ref(initBlock(props.block))
let updateTimeout = null

// Preset di colori per lo sfondo
const colorPresets = [
  { label: 'Trasparente', value: 'transparent' },
  { label: 'Bianco', value: '#FFFFFF' },
  { label: 'Grigio Chiaro', value: '#F3F4F6' },
  { label: 'Grigio', value: '#E5E7EB' },
  { label: 'Grigio Scuro', value: '#374151' },
  { label: 'Nero', value: '#1F2937' },
  { label: 'Blu Chiaro', value: '#DBEAFE' },
  { label: 'Blu', value: '#3B82F6' },
  { label: 'Blu Scuro', value: '#1E40AF' },
  { label: 'Verde Chiaro', value: '#D1FAE5' },
  { label: 'Verde', value: '#10B981' },
  { label: 'Rosso Chiaro', value: '#FEE2E2' }
]

// Preset di colori per il testo
const textColorPresets = [
  { label: 'Ereditato', value: 'inherit' },
  { label: 'Nero', value: '#000000' },
  { label: 'Grigio Scuro', value: '#374151' },
  { label: 'Grigio', value: '#6B7280' },
  { label: 'Bianco', value: '#FFFFFF' },
  { label: 'Blu', value: '#1E40AF' }
]

// Aggiorna localBlock solo quando cambia il blocco selezionato (non il contenuto)
watch(() => props.block.id, () => {
  localBlock.value = initBlock(props.block)
})

// Auto-save con debounce quando localBlock cambia
watch(localBlock, () => {
  if (updateTimeout) clearTimeout(updateTimeout)
  updateTimeout = setTimeout(() => {
    emit('update', localBlock.value)
  }, 300) // 300ms debounce
}, { deep: true })

const API_URL = import.meta.env.VITE_API_URL || 'http://localhost:8000/api'

const handleImageUpload = async (event) => {
  const file = event.target.files[0]
  if (file) {
    const formData = new FormData()
    formData.append('image', file)

    try {
      const response = await fetch(`${API_URL}/upload/image`, {
        method: 'POST',
        body: formData
      })

      const data = await response.json()

      if (data.success) {
        // Determina quale campo aggiornare in base al tipo di blocco
        if (localBlock.value.type === 'header') {
          localBlock.value.content.logoUrl = data.url
        } else {
          localBlock.value.content.image = data.url
        }
      } else {
        alert('Errore durante il caricamento dell\'immagine: ' + (data.error || 'Errore sconosciuto'))
      }
    } catch (error) {
      alert('Errore durante il caricamento dell\'immagine')
      console.error(error)
    }
  }
}

const handleServiceImageUpload = async (event, serviceIndex) => {
  const file = event.target.files[0]
  if (file) {
    const formData = new FormData()
    formData.append('image', file)

    try {
      const response = await fetch(`${API_URL}/upload/image`, {
        method: 'POST',
        body: formData
      })

      const data = await response.json()

      if (data.success) {
        localBlock.value.content.services[serviceIndex].image = data.url
      } else {
        alert('Errore durante il caricamento dell\'immagine: ' + (data.error || 'Errore sconosciuto'))
      }
    } catch (error) {
      alert('Errore durante il caricamento dell\'immagine')
      console.error(error)
    }
  }
}

// Funzioni per gestire i link del footer
const addFooterLink = () => {
  if (!localBlock.value.content.links) {
    localBlock.value.content.links = []
  }
  localBlock.value.content.links.push({
    text: 'Nuovo Link',
    url: '#'
  })
}

const removeFooterLink = (index) => {
  localBlock.value.content.links.splice(index, 1)
}
</script>
