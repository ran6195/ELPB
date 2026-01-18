<template>
  <div class="space-y-6">
    <div class="border-b border-gray-200 pb-4">
      <h4 class="text-xs font-semibold text-gray-500 uppercase mb-3 tracking-wide">
        Tipo Blocco
      </h4>
      <p class="text-sm text-gray-900 font-medium">{{ blockTypeName }}</p>
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

      <!-- Stile Pulsante -->
      <div class="border-t border-gray-200 pt-4">
        <h5 class="text-xs font-semibold text-gray-700 mb-3 uppercase tracking-wide">Stile Pulsante</h5>

        <div class="space-y-3">
          <div>
            <label class="block text-xs font-medium text-gray-700 mb-2">Colore Sfondo</label>
            <div class="flex items-center gap-3">
              <input
                v-model="localBlock.content.buttonStyle.backgroundColor"
                type="color"
                class="h-11 w-20 rounded-lg cursor-pointer border border-gray-300"
              />
              <input
                v-model="localBlock.content.buttonStyle.backgroundColor"
                type="text"
                placeholder="#4F46E5"
                class="flex-1 px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-200 focus:border-primary-500 transition-all outline-none text-sm font-mono"
              />
            </div>
          </div>

          <div>
            <label class="block text-xs font-medium text-gray-700 mb-2">Colore Testo</label>
            <div class="flex items-center gap-3">
              <input
                v-model="localBlock.content.buttonStyle.textColor"
                type="color"
                class="h-11 w-20 rounded-lg cursor-pointer border border-gray-300"
              />
              <input
                v-model="localBlock.content.buttonStyle.textColor"
                type="text"
                placeholder="#FFFFFF"
                class="flex-1 px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-200 focus:border-primary-500 transition-all outline-none text-sm font-mono"
              />
            </div>
          </div>

          <div>
            <label class="block text-xs font-medium text-gray-700 mb-2">Dimensione Testo</label>
            <select
              v-model="localBlock.content.buttonStyle.fontSize"
              class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-200 focus:border-primary-500 transition-all outline-none text-sm"
            >
              <option value="12px">Piccolo (12px)</option>
              <option value="14px">Normale (14px)</option>
              <option value="16px">Medio (16px)</option>
              <option value="18px">Grande (18px)</option>
              <option value="20px">Extra Large (20px)</option>
            </select>
          </div>

          <div>
            <label class="block text-xs font-medium text-gray-700 mb-2">Padding</label>
            <select
              v-model="localBlock.content.buttonStyle.padding"
              class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-200 focus:border-primary-500 transition-all outline-none text-sm"
            >
              <option value="8px 16px">Compatto (8px 16px)</option>
              <option value="10px 24px">Piccolo (10px 24px)</option>
              <option value="12px 32px">Medio (12px 32px)</option>
              <option value="16px 40px">Grande (16px 40px)</option>
              <option value="20px 48px">Extra Large (20px 48px)</option>
            </select>
          </div>

          <div>
            <label class="block text-xs font-medium text-gray-700 mb-2">Arrotondamento Angoli</label>
            <select
              v-model="localBlock.content.buttonStyle.borderRadius"
              class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-200 focus:border-primary-500 transition-all outline-none text-sm"
            >
              <option value="0px">Nessuno (Rettangolare)</option>
              <option value="4px">Leggero (4px)</option>
              <option value="8px">Medio (8px)</option>
              <option value="12px">Arrotondato (12px)</option>
              <option value="16px">Molto Arrotondato (16px)</option>
              <option value="9999px">Pillola (Completamente arrotondato)</option>
            </select>
          </div>

          <div>
            <label class="block text-xs font-medium text-gray-700 mb-2">Ombra</label>
            <select
              v-model="localBlock.content.buttonStyle.shadow"
              class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-200 focus:border-primary-500 transition-all outline-none text-sm"
            >
              <option value="none">Nessuna</option>
              <option value="sm">Piccola</option>
              <option value="md">Media</option>
              <option value="lg">Grande</option>
              <option value="xl">Extra Large</option>
            </select>
          </div>

          <!-- Bordo -->
          <div class="border-t border-gray-200 pt-3">
            <h6 class="text-xs font-medium text-gray-700 mb-3">Bordo (Opzionale)</h6>

            <div class="space-y-3">
              <div>
                <label class="block text-xs font-medium text-gray-700 mb-2">Larghezza Bordo</label>
                <select
                  v-model="localBlock.content.buttonStyle.borderWidth"
                  class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-200 focus:border-primary-500 transition-all outline-none text-sm"
                >
                  <option value="0px">Nessuno</option>
                  <option value="1px">1px</option>
                  <option value="2px">2px</option>
                  <option value="3px">3px</option>
                  <option value="4px">4px</option>
                </select>
              </div>

              <div v-if="localBlock.content.buttonStyle.borderWidth !== '0px'">
                <label class="block text-xs font-medium text-gray-700 mb-2">Colore Bordo</label>
                <div class="flex items-center gap-3">
                  <input
                    v-model="localBlock.content.buttonStyle.borderColor"
                    type="color"
                    class="h-11 w-20 rounded-lg cursor-pointer border border-gray-300"
                  />
                  <input
                    v-model="localBlock.content.buttonStyle.borderColor"
                    type="text"
                    placeholder="#000000"
                    class="flex-1 px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-200 focus:border-primary-500 transition-all outline-none text-sm font-mono"
                  />
                </div>
              </div>

              <div v-if="localBlock.content.buttonStyle.borderWidth !== '0px'">
                <label class="block text-xs font-medium text-gray-700 mb-2">Stile Bordo</label>
                <select
                  v-model="localBlock.content.buttonStyle.borderStyle"
                  class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-200 focus:border-primary-500 transition-all outline-none text-sm"
                >
                  <option value="solid">Solido</option>
                  <option value="dashed">Tratteggiato</option>
                  <option value="dotted">Punteggiato</option>
                </select>
              </div>
            </div>
          </div>
        </div>
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

    <!-- Video Block Editor -->
    <div v-else-if="block.type === 'video'" class="space-y-5">
      <div>
        <label class="block text-xs font-medium text-gray-700 mb-2">URL Video</label>
        <input
          v-model="localBlock.content.videoUrl"
          type="text"
          placeholder="https://www.youtube.com/watch?v=... o https://example.com/video.mp4"
          class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-200 focus:border-primary-500 transition-all outline-none text-sm"
        />
        <p class="text-xs text-gray-500 mt-1">Supporta: YouTube, Vimeo, MP4, WebM, OGG, MOV</p>
      </div>

      <div>
        <label class="block text-xs font-medium text-gray-700 mb-2">Oppure carica un video</label>
        <input
          type="file"
          accept="video/*"
          @change="handleVideoUpload"
          class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-primary-50 file:text-primary-700 hover:file:bg-primary-100"
        />
      </div>

      <!-- Anteprima video -->
      <div v-if="localBlock.content.videoUrl" class="mt-2">
        <video
          :src="localBlock.content.videoUrl"
          controls
          class="w-full h-48 object-cover rounded-lg border border-gray-300"
        >
          Il tuo browser non supporta il tag video.
        </video>
      </div>

      <div>
        <label class="block text-xs font-medium text-gray-700 mb-2">Altezza Video</label>
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
        <label class="block text-xs font-medium text-gray-700 mb-2">Larghezza Video</label>
        <select
          v-model="localBlock.content.fullWidth"
          class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-200 focus:border-primary-500 transition-all outline-none text-sm"
        >
          <option :value="true">Tutta larghezza (Full Width)</option>
          <option :value="false">Larghezza limitata (Contenuta)</option>
        </select>
      </div>

      <div class="border-t border-gray-200 pt-4">
        <h5 class="text-xs font-semibold text-gray-700 mb-3">Opzioni Video</h5>
        <div class="space-y-3">
          <div class="flex items-center justify-between">
            <label class="text-sm text-gray-700">Mostra controlli</label>
            <button
              @click="localBlock.content.showControls = !localBlock.content.showControls"
              :class="[
                'relative inline-flex h-6 w-11 items-center rounded-full transition-colors',
                localBlock.content.showControls !== false ? 'bg-primary-600' : 'bg-gray-200'
              ]"
            >
              <span
                :class="[
                  'inline-block h-4 w-4 transform rounded-full bg-white transition-transform',
                  localBlock.content.showControls !== false ? 'translate-x-6' : 'translate-x-1'
                ]"
              />
            </button>
          </div>

          <div class="flex items-center justify-between">
            <div>
              <label class="text-sm text-gray-700">Autoplay</label>
              <p class="text-xs text-gray-500">Parte automaticamente al caricamento</p>
            </div>
            <button
              @click="localBlock.content.autoplay = !localBlock.content.autoplay"
              :class="[
                'relative inline-flex h-6 w-11 items-center rounded-full transition-colors',
                localBlock.content.autoplay ? 'bg-primary-600' : 'bg-gray-200'
              ]"
            >
              <span
                :class="[
                  'inline-block h-4 w-4 transform rounded-full bg-white transition-transform',
                  localBlock.content.autoplay ? 'translate-x-6' : 'translate-x-1'
                ]"
              />
            </button>
          </div>

          <div class="flex items-center justify-between">
            <div>
              <label class="text-sm text-gray-700">Riproduci quando visibile</label>
              <p class="text-xs text-gray-500">Parte solo quando appare durante lo scroll</p>
            </div>
            <button
              @click="localBlock.content.playOnScroll = !localBlock.content.playOnScroll"
              :class="[
                'relative inline-flex h-6 w-11 items-center rounded-full transition-colors',
                localBlock.content.playOnScroll ? 'bg-primary-600' : 'bg-gray-200'
              ]"
            >
              <span
                :class="[
                  'inline-block h-4 w-4 transform rounded-full bg-white transition-transform',
                  localBlock.content.playOnScroll ? 'translate-x-6' : 'translate-x-1'
                ]"
              />
            </button>
          </div>

          <div class="flex items-center justify-between">
            <label class="text-sm text-gray-700">Loop (ripeti)</label>
            <button
              @click="localBlock.content.loop = !localBlock.content.loop"
              :class="[
                'relative inline-flex h-6 w-11 items-center rounded-full transition-colors',
                localBlock.content.loop ? 'bg-primary-600' : 'bg-gray-200'
              ]"
            >
              <span
                :class="[
                  'inline-block h-4 w-4 transform rounded-full bg-white transition-transform',
                  localBlock.content.loop ? 'translate-x-6' : 'translate-x-1'
                ]"
              />
            </button>
          </div>

          <div class="flex items-center justify-between">
            <label class="text-sm text-gray-700">Muted (senza audio)</label>
            <button
              @click="localBlock.content.muted = !localBlock.content.muted"
              :class="[
                'relative inline-flex h-6 w-11 items-center rounded-full transition-colors',
                localBlock.content.muted ? 'bg-primary-600' : 'bg-gray-200'
              ]"
            >
              <span
                :class="[
                  'inline-block h-4 w-4 transform rounded-full bg-white transition-transform',
                  localBlock.content.muted ? 'translate-x-6' : 'translate-x-1'
                ]"
              />
            </button>
          </div>
          <p class="text-xs text-gray-500 mt-2">
            Nota: Per l'autoplay è necessario attivare anche "Muted" in molti browser
          </p>
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

      <!-- Stile Pulsante -->
      <div class="border-t border-gray-200 pt-4">
        <h5 class="text-xs font-semibold text-gray-700 mb-3 uppercase tracking-wide">Stile Pulsante</h5>

        <div class="space-y-3">
          <div>
            <label class="block text-xs font-medium text-gray-700 mb-2">Colore Sfondo</label>
            <div class="flex items-center gap-3">
              <input
                v-model="localBlock.content.buttonStyle.backgroundColor"
                type="color"
                class="h-11 w-20 rounded-lg cursor-pointer border border-gray-300"
              />
              <input
                v-model="localBlock.content.buttonStyle.backgroundColor"
                type="text"
                placeholder="#4F46E5"
                class="flex-1 px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-200 focus:border-primary-500 transition-all outline-none text-sm font-mono"
              />
            </div>
          </div>

          <div>
            <label class="block text-xs font-medium text-gray-700 mb-2">Colore Testo</label>
            <div class="flex items-center gap-3">
              <input
                v-model="localBlock.content.buttonStyle.textColor"
                type="color"
                class="h-11 w-20 rounded-lg cursor-pointer border border-gray-300"
              />
              <input
                v-model="localBlock.content.buttonStyle.textColor"
                type="text"
                placeholder="#FFFFFF"
                class="flex-1 px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-200 focus:border-primary-500 transition-all outline-none text-sm font-mono"
              />
            </div>
          </div>

          <div>
            <label class="block text-xs font-medium text-gray-700 mb-2">Dimensione Testo</label>
            <select
              v-model="localBlock.content.buttonStyle.fontSize"
              class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-200 focus:border-primary-500 transition-all outline-none text-sm"
            >
              <option value="12px">Piccolo (12px)</option>
              <option value="14px">Normale (14px)</option>
              <option value="16px">Medio (16px)</option>
              <option value="18px">Grande (18px)</option>
              <option value="20px">Extra Large (20px)</option>
            </select>
          </div>

          <div>
            <label class="block text-xs font-medium text-gray-700 mb-2">Padding</label>
            <select
              v-model="localBlock.content.buttonStyle.padding"
              class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-200 focus:border-primary-500 transition-all outline-none text-sm"
            >
              <option value="8px 16px">Compatto (8px 16px)</option>
              <option value="10px 24px">Piccolo (10px 24px)</option>
              <option value="12px 32px">Medio (12px 32px)</option>
              <option value="16px 40px">Grande (16px 40px)</option>
              <option value="20px 48px">Extra Large (20px 48px)</option>
            </select>
          </div>

          <div>
            <label class="block text-xs font-medium text-gray-700 mb-2">Arrotondamento Angoli</label>
            <select
              v-model="localBlock.content.buttonStyle.borderRadius"
              class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-200 focus:border-primary-500 transition-all outline-none text-sm"
            >
              <option value="0px">Nessuno (Rettangolare)</option>
              <option value="4px">Leggero (4px)</option>
              <option value="8px">Medio (8px)</option>
              <option value="12px">Arrotondato (12px)</option>
              <option value="16px">Molto Arrotondato (16px)</option>
              <option value="9999px">Pillola (Completamente arrotondato)</option>
            </select>
          </div>

          <div>
            <label class="block text-xs font-medium text-gray-700 mb-2">Ombra</label>
            <select
              v-model="localBlock.content.buttonStyle.shadow"
              class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-200 focus:border-primary-500 transition-all outline-none text-sm"
            >
              <option value="none">Nessuna</option>
              <option value="sm">Piccola</option>
              <option value="md">Media</option>
              <option value="lg">Grande</option>
              <option value="xl">Extra Large</option>
            </select>
          </div>

          <!-- Bordo -->
          <div class="border-t border-gray-200 pt-3">
            <h6 class="text-xs font-medium text-gray-700 mb-3">Bordo (Opzionale)</h6>

            <div class="space-y-3">
              <div>
                <label class="block text-xs font-medium text-gray-700 mb-2">Larghezza Bordo</label>
                <select
                  v-model="localBlock.content.buttonStyle.borderWidth"
                  class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-200 focus:border-primary-500 transition-all outline-none text-sm"
                >
                  <option value="0px">Nessuno</option>
                  <option value="1px">1px</option>
                  <option value="2px">2px</option>
                  <option value="3px">3px</option>
                  <option value="4px">4px</option>
                </select>
              </div>

              <div v-if="localBlock.content.buttonStyle.borderWidth !== '0px'">
                <label class="block text-xs font-medium text-gray-700 mb-2">Colore Bordo</label>
                <div class="flex items-center gap-3">
                  <input
                    v-model="localBlock.content.buttonStyle.borderColor"
                    type="color"
                    class="h-11 w-20 rounded-lg cursor-pointer border border-gray-300"
                  />
                  <input
                    v-model="localBlock.content.buttonStyle.borderColor"
                    type="text"
                    placeholder="#000000"
                    class="flex-1 px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-200 focus:border-primary-500 transition-all outline-none text-sm font-mono"
                  />
                </div>
              </div>

              <div v-if="localBlock.content.buttonStyle.borderWidth !== '0px'">
                <label class="block text-xs font-medium text-gray-700 mb-2">Stile Bordo</label>
                <select
                  v-model="localBlock.content.buttonStyle.borderStyle"
                  class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-200 focus:border-primary-500 transition-all outline-none text-sm"
                >
                  <option value="solid">Solido</option>
                  <option value="dashed">Tratteggiato</option>
                  <option value="dotted">Punteggiato</option>
                </select>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="border-t border-gray-200 pt-4">
        <h5 class="text-xs font-semibold text-gray-700 mb-3">Privacy Policy</h5>
        <div class="space-y-3">
          <div class="flex items-center">
            <input
              type="checkbox"
              v-model="localBlock.content.showPrivacy"
              id="showPrivacy"
              class="w-4 h-4 text-primary-600 border-gray-300 rounded focus:ring-primary-500"
            />
            <label for="showPrivacy" class="ml-2 text-sm text-gray-700">
              Mostra checkbox privacy
            </label>
          </div>
          <div v-if="localBlock.content.showPrivacy !== false" class="space-y-3">
            <div>
              <label class="block text-xs font-medium text-gray-700 mb-2">Link Privacy Policy</label>
              <input
                v-model="localBlock.content.privacyLink"
                type="text"
                placeholder="/privacy-policy o https://example.com/privacy"
                class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-200 focus:border-primary-500 transition-all outline-none text-sm"
              />
            </div>
            <div>
              <label class="block text-xs font-medium text-gray-700 mb-2">Colore Testo Privacy</label>
              <div class="flex items-center gap-3">
                <input
                  v-model="localBlock.content.privacyTextColor"
                  type="color"
                  class="h-11 w-20 rounded-lg cursor-pointer border border-gray-300"
                />
                <input
                  v-model="localBlock.content.privacyTextColor"
                  type="text"
                  placeholder="#374151"
                  class="flex-1 px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-200 focus:border-primary-500 transition-all outline-none text-sm font-mono"
                />
              </div>
              <p class="text-xs text-gray-500 mt-1">Colore del testo dell'etichetta del checkbox privacy</p>
            </div>
          </div>
        </div>
      </div>

      <div class="border-t border-gray-200 pt-4">
        <h5 class="text-xs font-semibold text-gray-700 mb-3">Pagina di Ringraziamento</h5>
        <div>
          <label class="block text-xs font-medium text-gray-700 mb-2">URL Pagina Ringraziamento</label>
          <input
            v-model="localBlock.content.thankYouUrl"
            type="text"
            placeholder="https://example.com/grazie oppure /grazie"
            class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-200 focus:border-primary-500 transition-all outline-none text-sm"
          />
          <p class="text-xs text-gray-500 mt-1">
            Dopo l'invio del form, l'utente verrà reindirizzato a questo URL. Lascia vuoto per usare la pagina di ringraziamento standard.
          </p>
        </div>
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

    <!-- Video-Info Block Editor -->
    <div v-else-if="block.type === 'video-info'" class="space-y-5">
      <div>
        <label class="block text-xs font-medium text-gray-700 mb-2">URL Video</label>
        <input
          v-model="localBlock.content.videoUrl"
          type="text"
          placeholder="https://example.com/video.mp4"
          class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-200 focus:border-primary-500 transition-all outline-none text-sm"
        />
      </div>
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
          placeholder="Es: Indirizzo"
          class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-200 focus:border-primary-500 transition-all outline-none text-sm"
        />
      </div>
      <div>
        <label class="block text-xs font-medium text-gray-700 mb-2">URL Immagine Mappa</label>
        <input
          v-model="localBlock.content.mapImage"
          type="text"
          placeholder="https://example.com/map.png"
          class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-200 focus:border-primary-500 transition-all outline-none text-sm"
        />
      </div>
      <div>
        <label class="block text-xs font-medium text-gray-700 mb-2">Oppure carica immagine mappa</label>
        <input
          type="file"
          accept="image/*"
          @change="handleMapImageUpload"
          class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-primary-50 file:text-primary-700 hover:file:bg-primary-100"
        />
      </div>

      <!-- Anteprima immagine mappa -->
      <div v-if="localBlock.content.mapImage" class="mt-2">
        <img
          :src="localBlock.content.mapImage"
          alt="Anteprima mappa"
          class="w-full h-48 object-cover rounded-lg border border-gray-300"
        />
      </div>

      <div>
        <label class="block text-xs font-medium text-gray-700 mb-2">Link Mappa</label>
        <input
          v-model="localBlock.content.mapLink"
          type="text"
          placeholder="https://maps.google.com/..."
          class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-200 focus:border-primary-500 transition-all outline-none text-sm"
        />
      </div>
      <div>
        <label class="block text-xs font-medium text-gray-700 mb-2">Orari e Informazioni</label>
        <RichTextEditor v-model="localBlock.content.scheduleText" />
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

      <!-- Social Links Section -->
      <div class="border-t border-gray-200 pt-4">
        <h5 class="text-xs font-semibold text-gray-700 mb-3 uppercase tracking-wide">Social Media</h5>

        <div class="space-y-3">
          <div>
            <label class="block text-xs font-medium text-gray-700 mb-2">
              <svg class="w-4 h-4 inline mr-1" fill="currentColor" viewBox="0 0 24 24">
                <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
              </svg>
              Facebook URL
            </label>
            <input
              v-model="localBlock.content.socialLinks.facebook"
              type="text"
              placeholder="https://facebook.com/tuapagina"
              class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-200 focus:border-primary-500 transition-all outline-none text-sm"
            />
          </div>

          <div>
            <label class="block text-xs font-medium text-gray-700 mb-2">
              <svg class="w-4 h-4 inline mr-1" fill="currentColor" viewBox="0 0 24 24">
                <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/>
              </svg>
              Instagram URL
            </label>
            <input
              v-model="localBlock.content.socialLinks.instagram"
              type="text"
              placeholder="https://instagram.com/tuoprofilo"
              class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-200 focus:border-primary-500 transition-all outline-none text-sm"
            />
          </div>

          <div>
            <label class="block text-xs font-medium text-gray-700 mb-2">
              <svg class="w-4 h-4 inline mr-1" fill="currentColor" viewBox="0 0 24 24">
                <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/>
              </svg>
              X (Twitter) URL
            </label>
            <input
              v-model="localBlock.content.socialLinks.twitter"
              type="text"
              placeholder="https://x.com/tuoprofilo"
              class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-200 focus:border-primary-500 transition-all outline-none text-sm"
            />
          </div>
        </div>
      </div>

      <!-- Social Button Styling Section -->
      <div class="border-t border-gray-200 pt-4">
        <h5 class="text-xs font-semibold text-gray-700 mb-3 uppercase tracking-wide">Stile Bottoni Social</h5>

        <div class="space-y-3">
          <div>
            <label class="block text-xs font-medium text-gray-700 mb-2">Colore Sfondo</label>
            <div class="flex items-center gap-3">
              <input
                v-model="localBlock.content.socialButtonStyle.backgroundColor"
                type="color"
                class="h-11 w-20 rounded-lg cursor-pointer border border-gray-300"
              />
              <input
                v-model="localBlock.content.socialButtonStyle.backgroundColor"
                type="text"
                placeholder="transparent o #FFFFFF"
                class="flex-1 px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-200 focus:border-primary-500 transition-all outline-none text-sm font-mono"
              />
            </div>
          </div>

          <div>
            <label class="block text-xs font-medium text-gray-700 mb-2">Colore Icona</label>
            <div class="flex items-center gap-3">
              <input
                v-model="localBlock.content.socialButtonStyle.color"
                type="color"
                class="h-11 w-20 rounded-lg cursor-pointer border border-gray-300"
              />
              <input
                v-model="localBlock.content.socialButtonStyle.color"
                type="text"
                placeholder="#FFFFFF"
                class="flex-1 px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-200 focus:border-primary-500 transition-all outline-none text-sm font-mono"
              />
            </div>
          </div>

          <div>
            <label class="block text-xs font-medium text-gray-700 mb-2">Padding</label>
            <select
              v-model="localBlock.content.socialButtonStyle.padding"
              class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-200 focus:border-primary-500 transition-all outline-none text-sm"
            >
              <option value="6px">Piccolo (6px)</option>
              <option value="8px">Medio (8px)</option>
              <option value="10px">Grande (10px)</option>
              <option value="12px">Extra Large (12px)</option>
            </select>
          </div>

          <div>
            <label class="block text-xs font-medium text-gray-700 mb-2">Arrotondamento</label>
            <select
              v-model="localBlock.content.socialButtonStyle.borderRadius"
              class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-200 focus:border-primary-500 transition-all outline-none text-sm"
            >
              <option value="0px">Quadrato</option>
              <option value="4px">Leggero (4px)</option>
              <option value="8px">Medio (8px)</option>
              <option value="12px">Arrotondato (12px)</option>
              <option value="50%">Circolare</option>
            </select>
          </div>

          <div>
            <label class="block text-xs font-medium text-gray-700 mb-2">Bordo</label>
            <select
              v-model="localBlock.content.socialButtonStyle.borderWidth"
              class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-200 focus:border-primary-500 transition-all outline-none text-sm"
            >
              <option value="">Nessuno</option>
              <option value="1px">Sottile (1px)</option>
              <option value="2px">Medio (2px)</option>
              <option value="3px">Spesso (3px)</option>
            </select>
          </div>

          <div v-if="localBlock.content.socialButtonStyle.borderWidth">
            <label class="block text-xs font-medium text-gray-700 mb-2">Colore Bordo</label>
            <div class="flex items-center gap-3">
              <input
                v-model="localBlock.content.socialButtonStyle.borderColor"
                type="color"
                class="h-11 w-20 rounded-lg cursor-pointer border border-gray-300"
              />
              <input
                v-model="localBlock.content.socialButtonStyle.borderColor"
                type="text"
                placeholder="#FFFFFF"
                class="flex-1 px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-200 focus:border-primary-500 transition-all outline-none text-sm font-mono"
              />
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Map Block Editor -->
    <div v-else-if="block.type === 'map'" class="space-y-5">
      <div>
        <label class="block text-xs font-medium text-gray-700 mb-2">Titolo (opzionale)</label>
        <input
          v-model="localBlock.content.title"
          type="text"
          placeholder="es: Dove Siamo"
          class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-200 focus:border-primary-500 transition-all outline-none text-sm"
        />
      </div>

      <div>
        <label class="block text-xs font-medium text-gray-700 mb-2">Descrizione (opzionale)</label>
        <input
          v-model="localBlock.content.description"
          type="text"
          placeholder="es: Vieni a trovarci nel nostro showroom"
          class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-200 focus:border-primary-500 transition-all outline-none text-sm"
        />
      </div>

      <div>
        <label class="block text-xs font-medium text-gray-700 mb-2">URL Mappa Google</label>
        <textarea
          v-model="localBlock.content.mapUrl"
          rows="3"
          placeholder="Incolla qui l'URL di Google Maps o il codice iframe embed"
          class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-200 focus:border-primary-500 transition-all outline-none text-sm font-mono"
        ></textarea>
        <p class="text-xs text-gray-500 mt-1">
          Vai su <a href="https://www.google.com/maps" target="_blank" class="text-primary-600 underline">Google Maps</a>,
          cerca il luogo, clicca su "Condividi" → "Incorpora mappa" e copia l'URL o tutto il codice iframe
        </p>
      </div>

      <div>
        <label class="block text-xs font-medium text-gray-700 mb-2">Altezza Mappa</label>
        <select
          v-model="localBlock.content.height"
          class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-200 focus:border-primary-500 transition-all outline-none text-sm"
        >
          <option value="300px">Bassa (300px)</option>
          <option value="400px">Media (400px)</option>
          <option value="450px">Standard (450px)</option>
          <option value="500px">Alta (500px)</option>
          <option value="600px">Extra Alta (600px)</option>
        </select>
      </div>

      <!-- Informazioni di contatto -->
      <div class="border-t border-gray-200 pt-4">
        <div class="flex items-center justify-between mb-3">
          <h5 class="text-xs font-semibold text-gray-700">Informazioni di Contatto</h5>
          <label class="flex items-center gap-2 cursor-pointer">
            <input
              v-model="localBlock.content.showContactInfo"
              type="checkbox"
              class="w-4 h-4 text-primary-600 border-gray-300 rounded focus:ring-primary-500"
            />
            <span class="text-xs text-gray-600">Mostra sotto la mappa</span>
          </label>
        </div>

        <div v-if="localBlock.content.showContactInfo" class="space-y-3">
          <div>
            <label class="block text-xs font-medium text-gray-700 mb-2">Indirizzo</label>
            <input
              v-model="localBlock.content.address"
              type="text"
              placeholder="Via Example, 123 - 00100 Roma"
              class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-200 focus:border-primary-500 transition-all outline-none text-sm"
            />
          </div>

          <div>
            <label class="block text-xs font-medium text-gray-700 mb-2">Telefono</label>
            <input
              v-model="localBlock.content.phone"
              type="tel"
              placeholder="+39 123 456 7890"
              class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-200 focus:border-primary-500 transition-all outline-none text-sm"
            />
          </div>

          <div>
            <label class="block text-xs font-medium text-gray-700 mb-2">Email</label>
            <input
              v-model="localBlock.content.email"
              type="email"
              placeholder="info@example.com"
              class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-200 focus:border-primary-500 transition-all outline-none text-sm"
            />
          </div>
        </div>
      </div>
    </div>

    <!-- Social Block Editor -->
    <div v-else-if="block.type === 'social'" class="space-y-5">
      <!-- Link Social -->
      <div class="border-b border-gray-200 pb-4">
        <h4 class="text-xs font-semibold text-gray-500 uppercase mb-4 tracking-wide">
          Link Social Media
        </h4>
        <div class="space-y-3">
          <div>
            <label class="block text-xs font-medium text-gray-700 mb-2">Facebook URL</label>
            <input
              v-model="localBlock.content.facebookUrl"
              type="url"
              placeholder="https://facebook.com/tuapagina"
              class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-200 focus:border-primary-500 transition-all outline-none text-sm"
            />
          </div>
          <div>
            <label class="block text-xs font-medium text-gray-700 mb-2">Instagram URL</label>
            <input
              v-model="localBlock.content.instagramUrl"
              type="url"
              placeholder="https://instagram.com/tuapagina"
              class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-200 focus:border-primary-500 transition-all outline-none text-sm"
            />
          </div>
          <div>
            <label class="block text-xs font-medium text-gray-700 mb-2">X (Twitter) URL</label>
            <input
              v-model="localBlock.content.xUrl"
              type="url"
              placeholder="https://x.com/tuoaccount"
              class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-200 focus:border-primary-500 transition-all outline-none text-sm"
            />
          </div>
          <div>
            <label class="block text-xs font-medium text-gray-700 mb-2">LinkedIn URL</label>
            <input
              v-model="localBlock.content.linkedinUrl"
              type="url"
              placeholder="https://linkedin.com/company/tuaazienda"
              class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-200 focus:border-primary-500 transition-all outline-none text-sm"
            />
          </div>
          <div>
            <label class="block text-xs font-medium text-gray-700 mb-2">YouTube URL</label>
            <input
              v-model="localBlock.content.youtubeUrl"
              type="url"
              placeholder="https://youtube.com/@tuocanale"
              class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-200 focus:border-primary-500 transition-all outline-none text-sm"
            />
          </div>
        </div>
      </div>

      <!-- Stile Icone -->
      <div class="border-b border-gray-200 pb-4">
        <h4 class="text-xs font-semibold text-gray-500 uppercase mb-4 tracking-wide">
          Stile Icone
        </h4>
        <div class="space-y-3">
          <div>
            <label class="block text-xs font-medium text-gray-700 mb-2">Tipo Icone</label>
            <select
              v-model="localBlock.content.iconStyle"
              class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-200 focus:border-primary-500 transition-all outline-none text-sm"
            >
              <option value="colored">Colorate (Standard)</option>
              <option value="monochrome">Monocromatiche</option>
            </select>
          </div>

          <div>
            <label class="block text-xs font-medium text-gray-700 mb-2">Dimensione Icone (px)</label>
            <input
              v-model.number="localBlock.content.buttonSize"
              type="number"
              min="24"
              max="96"
              step="4"
              class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-200 focus:border-primary-500 transition-all outline-none text-sm"
            />
            <p class="text-xs text-gray-500 mt-1">Consigliato: 32-64px</p>
          </div>

          <div>
            <label class="block text-xs font-medium text-gray-700 mb-2">Spaziatura tra Icone (px)</label>
            <input
              v-model.number="localBlock.content.buttonSpacing"
              type="number"
              min="8"
              max="64"
              step="4"
              class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-200 focus:border-primary-500 transition-all outline-none text-sm"
            />
          </div>
        </div>
      </div>

      <!-- Contenitore -->
      <div class="border-b border-gray-200 pb-4">
        <h4 class="text-xs font-semibold text-gray-500 uppercase mb-4 tracking-wide">
          Stile Contenitore
        </h4>
        <div class="space-y-3">
          <div>
            <label class="block text-xs font-medium text-gray-700 mb-2">Sfondo Bottoni</label>
            <input
              v-model="localBlock.content.buttonBackground"
              type="color"
              class="w-full h-10 px-1 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-200 focus:border-primary-500 transition-all outline-none"
            />
            <p class="text-xs text-gray-500 mt-1">Trasparente se vuoi solo le icone</p>
          </div>

          <div>
            <label class="block text-xs font-medium text-gray-700 mb-2">Curvatura Bordi (px)</label>
            <input
              v-model.number="localBlock.content.borderRadius"
              type="number"
              min="0"
              max="50"
              class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-200 focus:border-primary-500 transition-all outline-none text-sm"
            />
            <p class="text-xs text-gray-500 mt-1">0 = squadrato, 50 = rotondo</p>
          </div>

          <div>
            <label class="block text-xs font-medium text-gray-700 mb-2">Ombra</label>
            <select
              v-model="localBlock.content.shadow"
              class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-200 focus:border-primary-500 transition-all outline-none text-sm"
            >
              <option value="none">Nessuna</option>
              <option value="sm">Piccola</option>
              <option value="md">Media</option>
              <option value="lg">Grande</option>
              <option value="xl">Extra Grande</option>
            </select>
          </div>

          <div>
            <label class="block text-xs font-medium text-gray-700 mb-2">Spessore Bordo (px)</label>
            <input
              v-model.number="localBlock.content.borderWidth"
              type="number"
              min="0"
              max="4"
              class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-200 focus:border-primary-500 transition-all outline-none text-sm"
            />
          </div>

          <div>
            <label class="block text-xs font-medium text-gray-700 mb-2">Colore Bordo</label>
            <input
              v-model="localBlock.content.borderColor"
              type="color"
              class="w-full h-10 px-1 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-200 focus:border-primary-500 transition-all outline-none"
            />
          </div>

          <div>
            <label class="block text-xs font-medium text-gray-700 mb-2">Stile Bordo</label>
            <select
              v-model="localBlock.content.borderStyle"
              class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-200 focus:border-primary-500 transition-all outline-none text-sm"
            >
              <option value="solid">Solido</option>
              <option value="dashed">Tratteggiato</option>
              <option value="dotted">Punteggiato</option>
            </select>
          </div>
        </div>
      </div>
    </div>

    <!-- Footer Block Editor -->
    <div v-else-if="block.type === 'footer'" class="space-y-5">
      <!-- Opzioni Layout -->
      <div class="border-b border-gray-200 pb-4">
        <h4 class="text-xs font-semibold text-gray-500 uppercase mb-4 tracking-wide">
          Layout
        </h4>
        <div class="flex items-center justify-between">
          <div>
            <label class="text-sm font-medium text-gray-700">Larghezza Completa</label>
            <p class="text-xs text-gray-500 mt-1">
              Lo sfondo si estende a tutta la larghezza della pagina
            </p>
          </div>
          <label class="relative inline-flex items-center cursor-pointer">
            <input
              type="checkbox"
              v-model="localBlock.content.fullWidth"
              class="sr-only peer"
            />
            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary-600"></div>
          </label>
        </div>
      </div>

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

    <!-- Slider Block Editor -->
    <div v-else-if="block.type === 'slider'" class="space-y-5">
      <div>
        <label class="block text-xs font-medium text-gray-700 mb-2">Titolo Slider (opzionale)</label>
        <input
          v-model="localBlock.content.title"
          type="text"
          placeholder="es: I Nostri Prodotti"
          class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-200 focus:border-primary-500 transition-all outline-none text-sm"
        />
      </div>

      <!-- Opzioni Slider -->
      <div class="border-t border-gray-200 pt-4">
        <h5 class="text-xs font-semibold text-gray-700 mb-3">Opzioni Slider</h5>
        <div class="space-y-3">
          <div>
            <label class="block text-xs font-medium text-gray-700 mb-2">Altezza slide</label>
            <select
              v-model="localBlock.content.slideHeight"
              class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-200 focus:border-primary-500 transition-all outline-none text-sm"
            >
              <option value="">Automatica (usa proporzioni)</option>
              <option value="300px">Piccola (300px)</option>
              <option value="400px">Media (400px)</option>
              <option value="500px">Grande (500px)</option>
              <option value="600px">Extra Large (600px)</option>
              <option value="80vh">Schermo intero (80vh)</option>
              <option value="100vh">Schermo intero pieno (100vh)</option>
            </select>
            <p class="text-xs text-gray-500 mt-1">Altezza fissa delle slide (lascia "Automatica" per usare le proporzioni)</p>
          </div>

          <div>
            <label class="block text-xs font-medium text-gray-700 mb-2">Proporzioni diapositive</label>
            <select
              v-model="localBlock.content.slideAspectRatio"
              class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-200 focus:border-primary-500 transition-all outline-none text-sm"
            >
              <option value="square">Quadrato (1:1)</option>
              <option value="16-9">Orizzontale 16:9 (Video)</option>
              <option value="4-3">Orizzontale 4:3 (Classico)</option>
              <option value="3-2">Orizzontale 3:2 (Fotografico)</option>
              <option value="21-9">Panoramico 21:9 (Ultrawide)</option>
              <option value="3-4">Verticale 3:4 (Ritratto)</option>
            </select>
            <p class="text-xs text-gray-500 mt-1">Proporzioni delle diapositive (usato quando altezza è "Automatica")</p>
          </div>

          <div>
            <label class="block text-xs font-medium text-gray-700 mb-2">Slide visibili su desktop</label>
            <input
              v-model.number="localBlock.content.slidesPerViewDesktop"
              type="number"
              min="1"
              max="6"
              class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-200 focus:border-primary-500 transition-all outline-none text-sm"
            />
            <p class="text-xs text-gray-500 mt-1">Quante slide mostrare contemporaneamente su desktop</p>
          </div>

          <div>
            <label class="block text-xs font-medium text-gray-700 mb-2">Spazio tra le slide (px)</label>
            <input
              v-model.number="localBlock.content.slideGap"
              type="number"
              min="0"
              max="100"
              step="5"
              class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-200 focus:border-primary-500 transition-all outline-none text-sm"
            />
            <p class="text-xs text-gray-500 mt-1">Spazio in pixel tra le diapositive (0 = nessuno spazio, 20 = default)</p>
          </div>

          <div>
            <label class="block text-xs font-medium text-gray-700 mb-2">Ritardo Autoplay (ms)</label>
            <input
              v-model.number="localBlock.content.autoplayDelay"
              type="number"
              min="1000"
              step="500"
              class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-200 focus:border-primary-500 transition-all outline-none text-sm"
            />
            <p class="text-xs text-gray-500 mt-1">Tempo tra una slide e l'altra (3000ms = 3 secondi)</p>
          </div>
        </div>
      </div>

      <!-- Diapositive -->
      <div class="border-t border-gray-200 pt-4">
        <h4 class="text-xs font-semibold text-gray-500 uppercase mb-4 tracking-wide">
          Diapositive
        </h4>

        <div
          v-for="(slide, index) in localBlock.content.slides"
          :key="index"
          class="border border-gray-300 rounded-lg p-4 mb-4"
        >
          <div class="flex justify-between items-center mb-3">
            <h5 class="font-medium text-sm text-gray-700">Slide {{ index + 1 }}</h5>
            <button
              v-if="localBlock.content.slides.length > 1"
              @click="removeSlide(index)"
              class="text-red-600 hover:text-red-800 text-xs font-medium"
            >
              Rimuovi
            </button>
          </div>

          <div class="space-y-3">
            <div>
              <label class="block text-xs font-medium text-gray-700 mb-2">URL Immagine</label>
              <input
                v-model="localBlock.content.slides[index].image"
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
                @change="(e) => handleSlideImageUpload(e, index)"
                class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-primary-50 file:text-primary-700 hover:file:bg-primary-100"
              />
            </div>

            <!-- Anteprima immagine -->
            <div v-if="localBlock.content.slides[index].image && localBlock.content.slides[index].image !== 'https://placehold.co/800x800'" class="mt-2">
              <img
                :src="localBlock.content.slides[index].image"
                :alt="localBlock.content.slides[index].alt"
                class="w-full h-32 object-cover rounded-lg border border-gray-300"
              />
            </div>

            <div>
              <label class="block text-xs font-medium text-gray-700 mb-2">Testo alternativo</label>
              <input
                v-model="localBlock.content.slides[index].alt"
                type="text"
                placeholder="Descrizione immagine"
                class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-200 focus:border-primary-500 transition-all outline-none text-sm"
              />
            </div>

            <div>
              <label class="block text-xs font-medium text-gray-700 mb-2">Titolo (opzionale)</label>
              <input
                v-model="localBlock.content.slides[index].title"
                type="text"
                placeholder="Titolo della slide"
                class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-200 focus:border-primary-500 transition-all outline-none text-sm"
              />
            </div>

            <div>
              <label class="block text-xs font-medium text-gray-700 mb-2">Descrizione (opzionale)</label>
              <textarea
                v-model="localBlock.content.slides[index].description"
                rows="2"
                placeholder="Descrizione della slide"
                class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-200 focus:border-primary-500 transition-all outline-none text-sm"
              ></textarea>
            </div>
          </div>
        </div>

        <button
          @click="addSlide"
          class="w-full px-4 py-2.5 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors text-sm font-medium"
        >
          + Aggiungi Diapositiva
        </button>
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

      <!-- Colore Testo (nascosto per blocchi senza testo) -->
      <div v-if="block.type !== 'video' && block.type !== 'social'">
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

      <div>
        <label class="block text-xs font-medium text-gray-700 mb-2">Font Google</label>
        <select
          v-model="localBlock.styles.fontFamily"
          @change="onFontChange"
          class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-200 focus:border-primary-500 transition-all outline-none text-sm"
        >
          <option
            v-for="font in popularGoogleFonts"
            :key="font.value"
            :value="font.value"
          >
            {{ font.name }}
          </option>
        </select>
        <p class="text-xs text-gray-500 mt-1">Scegli un font da Google Fonts per questo blocco</p>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, watch, computed } from 'vue'
import RichTextEditor from './RichTextEditor.vue'
import apiClient from '../api/axios'
import { popularGoogleFonts, loadGoogleFont } from '../utils/googleFonts'

const props = defineProps({
  block: {
    type: Object,
    required: true
  }
})

const emit = defineEmits(['update'])

// Mappatura nomi blocchi in italiano
const blockTypeNames = {
  'header': 'Intestazione',
  'hero': 'Hero',
  'image-slide': 'Diapositiva Immagine',
  'video': 'Video',
  'text': 'Testo',
  'features': 'Vantaggi',
  'services-grid': 'Griglia Servizi',
  'cta': 'Call to Action',
  'two-column-text-image': 'Due Colonne (Testo-Immagine)',
  'two-column-image-text': 'Due Colonne (Immagine-Testo)',
  'video-info': 'Video + Info',
  'slider': 'Slider',
  'map': 'Mappa Google',
  'social': 'Social Media',
  'form': 'Form',
  'footer': 'Footer'
}

// Nome blocco tradotto
const blockTypeName = computed(() => {
  return blockTypeNames[props.block.type] || props.block.type
})

// Inizializza localBlock con styles se non esistono
const initBlock = (block) => {
  const clonedBlock = JSON.parse(JSON.stringify(block))
  // Assicura che styles sia un oggetto e abbia tutte le proprietà necessarie
  if (!clonedBlock.styles || Object.keys(clonedBlock.styles).length === 0) {
    clonedBlock.styles = {
      backgroundColor: 'transparent',
      textColor: 'inherit',
      padding: '40px 20px',
      fontFamily: ''
    }
  } else {
    // Aggiungi valori di default per proprietà mancanti
    clonedBlock.styles = {
      backgroundColor: clonedBlock.styles.backgroundColor || 'transparent',
      textColor: clonedBlock.styles.textColor || 'inherit',
      padding: clonedBlock.styles.padding || '40px 20px',
      fontFamily: clonedBlock.styles.fontFamily || ''
    }
  }

  // Carica il font se specificato
  if (clonedBlock.styles.fontFamily) {
    loadGoogleFont(clonedBlock.styles.fontFamily)
  }

  // Per blocchi hero vecchi, aggiungi buttonStyle se non esiste
  if (clonedBlock.type === 'hero' && clonedBlock.content) {
    if (!clonedBlock.content.buttonStyle) {
      clonedBlock.content.buttonStyle = {
        backgroundColor: '#4F46E5',
        textColor: '#FFFFFF',
        fontSize: '16px',
        padding: '12px 32px',
        borderRadius: '8px',
        borderWidth: '0px',
        borderColor: 'transparent',
        borderStyle: 'solid',
        shadow: 'md'
      }
    }
  }

  // Per blocchi header, assicura che socialLinks e socialButtonStyle esistano
  if (clonedBlock.type === 'header' && clonedBlock.content) {
    if (!clonedBlock.content.socialLinks) {
      clonedBlock.content.socialLinks = {
        facebook: '',
        instagram: '',
        twitter: ''
      }
    }
    if (!clonedBlock.content.socialButtonStyle) {
      clonedBlock.content.socialButtonStyle = {
        backgroundColor: 'transparent',
        color: '#FFFFFF',
        padding: '8px',
        borderRadius: '8px',
        borderWidth: '',
        borderColor: '#FFFFFF'
      }
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

const handleImageUpload = async (event) => {
  const file = event.target.files[0]
  if (file) {
    const formData = new FormData()
    formData.append('image', file)

    try {
      const response = await apiClient.post('/upload/image', formData, {
        headers: {
          'Content-Type': 'multipart/form-data'
        }
      })

      if (response.data.success) {
        // Determina quale campo aggiornare in base al tipo di blocco
        if (localBlock.value.type === 'header') {
          localBlock.value.content.logoUrl = response.data.url
        } else {
          localBlock.value.content.image = response.data.url
        }
      } else {
        alert('Errore durante il caricamento dell\'immagine: ' + (response.data.error || 'Errore sconosciuto'))
      }
    } catch (error) {
      alert('Errore durante il caricamento dell\'immagine')
      console.error(error)
    }
  }
}

const handleVideoUpload = async (event) => {
  const file = event.target.files[0]
  if (file) {
    // Validazione dimensione file (max 100MB)
    const maxSize = 100 * 1024 * 1024 // 100MB in bytes
    if (file.size > maxSize) {
      alert('Il file è troppo grande. Dimensione massima: 100MB')
      event.target.value = '' // Reset input
      return
    }

    // Validazione tipo file
    const allowedTypes = ['video/mp4', 'video/mpeg', 'video/quicktime', 'video/x-msvideo', 'video/webm']
    if (!allowedTypes.includes(file.type)) {
      alert('Tipo file non supportato. Usa MP4, MOV, AVI o WebM')
      event.target.value = '' // Reset input
      return
    }

    const formData = new FormData()
    formData.append('video', file)

    try {
      const response = await apiClient.post('/upload/video', formData, {
        headers: {
          'Content-Type': 'multipart/form-data'
        }
      })

      if (response.data.success) {
        localBlock.value.content.videoUrl = response.data.url
      } else {
        alert('Errore durante il caricamento del video: ' + (response.data.error || 'Errore sconosciuto'))
      }
    } catch (error) {
      const errorMessage = error.response?.data?.error || 'Errore durante il caricamento del video'
      alert(errorMessage)
      console.error(error)
    }
  }
}

const handleMapImageUpload = async (event) => {
  const file = event.target.files[0]
  if (file) {
    const formData = new FormData()
    formData.append('image', file)

    try {
      const response = await apiClient.post('/upload/image', formData, {
        headers: {
          'Content-Type': 'multipart/form-data'
        }
      })

      if (response.data.success) {
        localBlock.value.content.mapImage = response.data.url
      } else {
        alert('Errore durante il caricamento dell\'immagine: ' + (response.data.error || 'Errore sconosciuto'))
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
      const response = await apiClient.post('/upload/image', formData, {
        headers: {
          'Content-Type': 'multipart/form-data'
        }
      })

      if (response.data.success) {
        localBlock.value.content.services[serviceIndex].image = response.data.url
      } else {
        alert('Errore durante il caricamento dell\'immagine: ' + (response.data.error || 'Errore sconosciuto'))
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

// Funzioni per gestire lo slider
const handleSlideImageUpload = async (event, slideIndex) => {
  const file = event.target.files[0]
  if (file) {
    const formData = new FormData()
    formData.append('image', file)

    try {
      const response = await apiClient.post('/upload/image', formData, {
        headers: {
          'Content-Type': 'multipart/form-data'
        }
      })

      if (response.data.success) {
        localBlock.value.content.slides[slideIndex].image = response.data.url
      } else {
        alert('Errore durante il caricamento dell\'immagine: ' + (response.data.error || 'Errore sconosciuto'))
      }
    } catch (error) {
      alert('Errore durante il caricamento dell\'immagine')
      console.error(error)
    }
  }
}

const addSlide = () => {
  if (!localBlock.value.content.slides) {
    localBlock.value.content.slides = []
  }
  localBlock.value.content.slides.push({
    image: 'https://placehold.co/800x800',
    alt: `Slide ${localBlock.value.content.slides.length + 1}`,
    title: '',
    description: ''
  })
}

const removeSlide = (index) => {
  if (localBlock.value.content.slides.length > 1) {
    localBlock.value.content.slides.splice(index, 1)
  }
}

// Gestione cambio font
const onFontChange = () => {
  if (localBlock.value.styles.fontFamily) {
    loadGoogleFont(localBlock.value.styles.fontFamily)
  }
}
</script>
