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
      <div class="mb-5">
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

      <!-- Rounded Corners -->
      <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
        <div>
          <p class="text-sm font-medium text-gray-900">Angoli Arrotondati</p>
          <p class="text-xs text-gray-500 mt-0.5">
            {{ roundedCorners ? 'Blocchi con angoli arrotondati' : 'Blocchi con angoli quadrati' }}
          </p>
        </div>
        <button
          @click="roundedCorners = !roundedCorners"
          :class="[
            'relative inline-flex h-6 w-11 items-center rounded-full transition-colors',
            roundedCorners ? 'bg-primary-600' : 'bg-gray-200'
          ]"
        >
          <span
            :class="[
              'inline-block h-4 w-4 transform rounded-full bg-white transition-transform',
              roundedCorners ? 'translate-x-6' : 'translate-x-1'
            ]"
          />
        </button>
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

    <!-- Integrazioni -->
    <div class="border-t border-gray-200 pt-6">
      <h4 class="text-xs font-semibold text-gray-500 uppercase mb-4 tracking-wide">
        Integrazioni
      </h4>

      <!-- Google reCAPTCHA Toggle -->
      <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg mb-4">
        <div>
          <p class="text-sm font-medium text-gray-900">Google reCAPTCHA</p>
          <p class="text-xs text-gray-500 mt-0.5">
            {{ recaptchaEnabled ? 'Protezione anti-spam attiva' : 'Protezione anti-spam disattivata' }}
          </p>
        </div>
        <button
          @click="recaptchaEnabled = !recaptchaEnabled"
          :class="[
            'relative inline-flex h-6 w-11 items-center rounded-full transition-colors',
            recaptchaEnabled ? 'bg-primary-600' : 'bg-gray-200'
          ]"
        >
          <span
            :class="[
              'inline-block h-4 w-4 transform rounded-full bg-white transition-transform',
              recaptchaEnabled ? 'translate-x-6' : 'translate-x-1'
            ]"
          />
        </button>
      </div>

      <!-- Google reCAPTCHA Keys (mostrati solo se abilitato) -->
      <div v-if="recaptchaEnabled" class="space-y-4">
        <div>
          <label class="block text-xs font-medium text-gray-700 mb-2">reCAPTCHA Site Key (Pubblica)</label>
          <input
            v-model="recaptchaSiteKey"
            type="text"
            placeholder="6LeIxAcTAAAAAJcZVRqyHh71UMIEGNQ_MXjiZKhI"
            class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-200 focus:border-primary-500 transition-all outline-none text-sm font-mono"
          />
          <p class="text-xs text-gray-500 mt-1">
            Chiave pubblica per reCAPTCHA v2 Checkbox
          </p>
        </div>

        <div>
          <label class="block text-xs font-medium text-gray-700 mb-2">reCAPTCHA Secret Key (Privata)</label>
          <input
            v-model="recaptchaSecretKey"
            type="password"
            placeholder="6LeIxAcTAAAAAGG-vFI1TnRWxMZNFuojJ4WifJWe"
            class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-200 focus:border-primary-500 transition-all outline-none text-sm font-mono"
          />
          <p class="text-xs text-gray-500 mt-1">
            Chiave privata per validazione server-side.
            <a href="https://www.google.com/recaptcha/admin" target="_blank" class="text-primary-600 underline">Ottieni le chiavi</a>
          </p>
        </div>

        <div class="bg-blue-50 border border-blue-200 rounded-lg p-3">
          <p class="text-xs text-blue-700">
            <strong>Nota:</strong> Le chiavi reCAPTCHA vengono applicate a tutti i form della pagina.
            Per testare, puoi usare le chiavi di test di Google (sopra).
          </p>
        </div>
      </div>

      <!-- Google Tag Manager -->
      <div class="mt-6 pt-6 border-t border-gray-200">
        <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg mb-4">
          <div>
            <p class="text-sm font-medium text-gray-900">Google Tag Manager</p>
            <p class="text-xs text-gray-500 mt-0.5">
              {{ gtmEnabled ? 'Tracciamento attivo' : 'Tracciamento disattivato' }}
            </p>
          </div>
          <button
            @click="gtmEnabled = !gtmEnabled"
            :class="[
              'relative inline-flex h-6 w-11 items-center rounded-full transition-colors',
              gtmEnabled ? 'bg-primary-600' : 'bg-gray-200'
            ]"
          >
            <span
              :class="[
                'inline-block h-4 w-4 transform rounded-full bg-white transition-transform',
                gtmEnabled ? 'translate-x-6' : 'translate-x-1'
              ]"
            />
          </button>
        </div>

        <!-- GTM ID Field (mostrato solo se abilitato) -->
        <div v-if="gtmEnabled" class="space-y-4">
          <div>
            <label class="block text-xs font-medium text-gray-700 mb-2">GTM Container ID</label>
            <input
              v-model="gtmId"
              type="text"
              placeholder="GTM-XXXXXXX"
              class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-200 focus:border-primary-500 transition-all outline-none text-sm font-mono"
            />
            <p class="text-xs text-gray-500 mt-1">
              Inserisci il tuo ID Container di Google Tag Manager (es. GTM-XXXXXXX).
              <a href="https://tagmanager.google.com/" target="_blank" class="text-primary-600 underline">Crea un container</a>
            </p>
          </div>

          <div class="bg-blue-50 border border-blue-200 rounded-lg p-3">
            <p class="text-xs text-blue-700">
              <strong>Info:</strong> Google Tag Manager permette di gestire tutti i tag di tracciamento (Analytics, Ads, Facebook Pixel, ecc.) da un'unica interfaccia, senza modificare il codice della landing page.
            </p>
          </div>
        </div>
      </div>
    </div>

    <!-- Contatti Rapidi -->
    <div class="border-t border-gray-200 pt-6">
      <h4 class="text-xs font-semibold text-gray-500 uppercase mb-4 tracking-wide">
        Contatti Rapidi
      </h4>

      <!-- WhatsApp Button -->
      <div class="mb-5">
        <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg mb-3">
          <div>
            <p class="text-sm font-medium text-gray-900">WhatsApp</p>
            <p class="text-xs text-gray-500 mt-0.5">
              {{ whatsappEnabled ? 'Pulsante visibile in basso a destra' : 'Pulsante disattivato' }}
            </p>
          </div>
          <button
            @click="whatsappEnabled = !whatsappEnabled"
            :class="[
              'relative inline-flex h-6 w-11 items-center rounded-full transition-colors',
              whatsappEnabled ? 'bg-green-600' : 'bg-gray-200'
            ]"
          >
            <span
              :class="[
                'inline-block h-4 w-4 transform rounded-full bg-white transition-transform',
                whatsappEnabled ? 'translate-x-6' : 'translate-x-1'
              ]"
            />
          </button>
        </div>

        <div v-if="whatsappEnabled" class="space-y-3 pl-2">
          <div>
            <label class="block text-xs font-medium text-gray-700 mb-2">Numero WhatsApp (senza +)</label>
            <input
              v-model="whatsappNumber"
              type="text"
              placeholder="es: 393331234567"
              class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-200 focus:border-primary-500 transition-all outline-none text-sm"
            />
            <p class="text-xs text-gray-500 mt-1">Solo numeri, senza spazi o simboli</p>
          </div>

          <div>
            <label class="block text-xs font-medium text-gray-700 mb-2">Messaggio Pre-compilato (opzionale)</label>
            <textarea
              v-model="whatsappMessage"
              rows="2"
              placeholder="es: Ciao! Vorrei maggiori informazioni"
              class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-200 focus:border-primary-500 transition-all outline-none text-sm"
            ></textarea>
          </div>

          <div>
            <label class="block text-xs font-medium text-gray-700 mb-2">Colore Sfondo</label>
            <div class="flex items-center gap-3">
              <input
                v-model="whatsappColor"
                type="color"
                class="h-10 w-20 rounded border border-gray-300 cursor-pointer"
              />
              <input
                v-model="whatsappColor"
                type="text"
                class="flex-1 px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-200 focus:border-primary-500 transition-all outline-none text-sm font-mono"
              />
            </div>
          </div>
        </div>
      </div>

      <!-- Phone Button -->
      <div class="mb-5">
        <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg mb-3">
          <div>
            <p class="text-sm font-medium text-gray-900">Telefono</p>
            <p class="text-xs text-gray-500 mt-0.5">
              {{ phoneEnabled ? 'Pulsante visibile in basso a sinistra' : 'Pulsante disattivato' }}
            </p>
          </div>
          <button
            @click="phoneEnabled = !phoneEnabled"
            :class="[
              'relative inline-flex h-6 w-11 items-center rounded-full transition-colors',
              phoneEnabled ? 'bg-blue-600' : 'bg-gray-200'
            ]"
          >
            <span
              :class="[
                'inline-block h-4 w-4 transform rounded-full bg-white transition-transform',
                phoneEnabled ? 'translate-x-6' : 'translate-x-1'
              ]"
            />
          </button>
        </div>

        <div v-if="phoneEnabled" class="space-y-3 pl-2">
          <div>
            <label class="block text-xs font-medium text-gray-700 mb-2">Numero Telefono</label>
            <input
              v-model="phoneNumber"
              type="text"
              placeholder="es: +390512345678"
              class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-200 focus:border-primary-500 transition-all outline-none text-sm"
            />
            <p class="text-xs text-gray-500 mt-1">Con prefisso internazionale (+39 per Italia)</p>
          </div>

          <div>
            <label class="block text-xs font-medium text-gray-700 mb-2">Colore Sfondo</label>
            <div class="flex items-center gap-3">
              <input
                v-model="phoneColor"
                type="color"
                class="h-10 w-20 rounded border border-gray-300 cursor-pointer"
              />
              <input
                v-model="phoneColor"
                type="text"
                class="flex-1 px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-200 focus:border-primary-500 transition-all outline-none text-sm font-mono"
              />
            </div>
          </div>
        </div>
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
  console.log('PageSettings watch triggered, emitting update with:', JSON.parse(JSON.stringify(localPage.value)))
  if (updateTimeout) clearTimeout(updateTimeout)
  updateTimeout = setTimeout(() => {
    console.log('PageSettings emit update after debounce')
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

const roundedCorners = computed({
  get() {
    return localPage.value.styles?.roundedCorners ?? true
  },
  set(value) {
    if (!localPage.value.styles) {
      localPage.value.styles = {}
    }
    localPage.value.styles.roundedCorners = value
  }
})

// reCAPTCHA Settings
const recaptchaEnabled = computed({
  get() {
    return localPage.value.recaptcha_settings?.enabled ?? false
  },
  set(value) {
    if (!localPage.value.recaptcha_settings) {
      localPage.value.recaptcha_settings = {}
    }
    localPage.value.recaptcha_settings.enabled = value
  }
})

const recaptchaSiteKey = computed({
  get() {
    return localPage.value.recaptcha_settings?.site_key || ''
  },
  set(value) {
    if (!localPage.value.recaptcha_settings) {
      localPage.value.recaptcha_settings = {}
    }
    localPage.value.recaptcha_settings.site_key = value
  }
})

const recaptchaSecretKey = computed({
  get() {
    return localPage.value.recaptcha_settings?.secret_key || ''
  },
  set(value) {
    if (!localPage.value.recaptcha_settings) {
      localPage.value.recaptcha_settings = {}
    }
    localPage.value.recaptcha_settings.secret_key = value
  }
})

// Google Tag Manager Settings
const gtmEnabled = computed({
  get() {
    return localPage.value.tracking_settings?.gtm_enabled ?? false
  },
  set(value) {
    if (!localPage.value.tracking_settings) {
      localPage.value.tracking_settings = {}
    }
    localPage.value.tracking_settings.gtm_enabled = value
  }
})

const gtmId = computed({
  get() {
    return localPage.value.tracking_settings?.gtm_id || ''
  },
  set(value) {
    if (!localPage.value.tracking_settings) {
      localPage.value.tracking_settings = {}
    }
    localPage.value.tracking_settings.gtm_id = value
  }
})

const togglePublish = () => {
  localPage.value.is_published = !localPage.value.is_published
}

// Quick Contacts - WhatsApp
const whatsappEnabled = computed({
  get() {
    const value = localPage.value.quickContacts?.whatsapp?.enabled ?? false
    console.log('whatsappEnabled GET:', value)
    return value
  },
  set(value) {
    console.log('whatsappEnabled SET:', value)
    if (!localPage.value.quickContacts) {
      localPage.value.quickContacts = { whatsapp: {}, phone: {} }
    }
    if (!localPage.value.quickContacts.whatsapp) {
      localPage.value.quickContacts.whatsapp = {}
    }
    localPage.value.quickContacts.whatsapp.enabled = value
    console.log('localPage after SET:', JSON.parse(JSON.stringify(localPage.value)))
  }
})

const whatsappNumber = computed({
  get() {
    return localPage.value.quickContacts?.whatsapp?.number || ''
  },
  set(value) {
    if (!localPage.value.quickContacts) {
      localPage.value.quickContacts = { whatsapp: {}, phone: {} }
    }
    if (!localPage.value.quickContacts.whatsapp) {
      localPage.value.quickContacts.whatsapp = {}
    }
    localPage.value.quickContacts.whatsapp.number = value
  }
})

const whatsappMessage = computed({
  get() {
    return localPage.value.quickContacts?.whatsapp?.message || 'Ciao! Vorrei maggiori informazioni'
  },
  set(value) {
    if (!localPage.value.quickContacts) {
      localPage.value.quickContacts = { whatsapp: {}, phone: {} }
    }
    if (!localPage.value.quickContacts.whatsapp) {
      localPage.value.quickContacts.whatsapp = {}
    }
    localPage.value.quickContacts.whatsapp.message = value
  }
})

const whatsappColor = computed({
  get() {
    return localPage.value.quickContacts?.whatsapp?.style?.backgroundColor || '#25D366'
  },
  set(value) {
    if (!localPage.value.quickContacts) {
      localPage.value.quickContacts = { whatsapp: {}, phone: {} }
    }
    if (!localPage.value.quickContacts.whatsapp) {
      localPage.value.quickContacts.whatsapp = {}
    }
    if (!localPage.value.quickContacts.whatsapp.style) {
      localPage.value.quickContacts.whatsapp.style = {}
    }
    localPage.value.quickContacts.whatsapp.style.backgroundColor = value
  }
})

// Quick Contacts - Phone
const phoneEnabled = computed({
  get() {
    return localPage.value.quickContacts?.phone?.enabled ?? false
  },
  set(value) {
    if (!localPage.value.quickContacts) {
      localPage.value.quickContacts = { whatsapp: {}, phone: {} }
    }
    if (!localPage.value.quickContacts.phone) {
      localPage.value.quickContacts.phone = {}
    }
    localPage.value.quickContacts.phone.enabled = value
  }
})

const phoneNumber = computed({
  get() {
    return localPage.value.quickContacts?.phone?.number || ''
  },
  set(value) {
    if (!localPage.value.quickContacts) {
      localPage.value.quickContacts = { whatsapp: {}, phone: {} }
    }
    if (!localPage.value.quickContacts.phone) {
      localPage.value.quickContacts.phone = {}
    }
    localPage.value.quickContacts.phone.number = value
  }
})

const phoneColor = computed({
  get() {
    return localPage.value.quickContacts?.phone?.style?.backgroundColor || '#007BFF'
  },
  set(value) {
    if (!localPage.value.quickContacts) {
      localPage.value.quickContacts = { whatsapp: {}, phone: {} }
    }
    if (!localPage.value.quickContacts.phone) {
      localPage.value.quickContacts.phone = {}
    }
    if (!localPage.value.quickContacts.phone.style) {
      localPage.value.quickContacts.phone.style = {}
    }
    localPage.value.quickContacts.phone.style.backgroundColor = value
  }
})
</script>
