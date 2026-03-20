<template>
  <div class="form-block">
    <div :class="['max-w-7xl mx-auto px-6 py-16', roundedCorners ? 'rounded-lg' : '']" :style="blockStyles">
      <h2 class="text-3xl font-bold mb-2 text-center">
        {{ block.content.title }}
      </h2>

      <p v-if="block.content.caption" class="text-center text-gray-600 mb-8 max-w-2xl mx-auto">
        {{ block.content.caption }}
      </p>

      <p class="text-sm text-gray-500 mb-4 text-center">
        <span class="text-red-500">*</span> Campi obbligatori
      </p>

      <form @submit.prevent="submitForm" class="space-y-4">
        <!-- Campi input su 2 colonne -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div
            v-for="field in inputFields"
            :key="field.name"
            class="form-field"
          >
            <input
              :id="field.name"
              :type="field.type"
              v-model="formData[field.name]"
              :required="field.required"
              :placeholder="field.label + (field.required ? ' *' : '')"
              :class="['w-full px-4 py-3 border border-gray-300 focus:ring-2 focus:ring-primary-200 focus:border-primary-500 transition-all outline-none', fieldBorderRadiusClass]"
            />
          </div>
        </div>

        <!-- Campi textarea a tutta larghezza -->
        <div
          v-for="field in textareaFields"
          :key="field.name"
          class="form-field"
        >
          <textarea
            :id="field.name"
            v-model="formData[field.name]"
            :required="field.required"
            :placeholder="block.content.textareaPlaceholder || (field.label + (field.required ? ' *' : ''))"
            rows="4"
            :class="['w-full px-4 py-3 border border-gray-300 focus:ring-2 focus:ring-primary-200 focus:border-primary-500 transition-all outline-none', fieldBorderRadiusClass]"
          ></textarea>
        </div>

        <!-- Privacy Checkbox -->
        <div v-if="block.content.showPrivacy !== false" class="form-field">
          <label class="flex items-start space-x-2">
            <input
              type="checkbox"
              v-model="privacyAccepted"
              required
              class="mt-1 w-4 h-4 text-primary-600 border-gray-300 rounded focus:ring-primary-500"
            />
            <span class="text-sm" :style="{ color: block.content.privacyTextColor || '#374151' }">
              Accetto la
              <a
                :href="block.content.privacyLink || '#'"
                target="_blank"
                class="hover:underline"
              >
                privacy policy e il trattamento dei dati
              </a>
              <span class="text-red-500">*</span>
            </span>
          </label>
        </div>

        <!-- Google reCAPTCHA v2 -->
        <div v-if="recaptchaSiteKey" class="form-field">
          <div ref="recaptchaElement" class="g-recaptcha"></div>
          <p v-if="recaptchaError" class="text-red-600 text-sm mt-1">
            {{ recaptchaError }}
          </p>
        </div>

        <div :class="block.content.buttonLayout === 'centered' ? 'flex justify-center' : ''">
          <button
            type="submit"
            :disabled="submitting || !canSubmit"
            :style="buttonStyles"
            :class="[
              'font-medium transition-colors disabled:opacity-50 disabled:cursor-not-allowed',
              block.content.buttonLayout === 'centered' ? '' : 'w-full'
            ]"
          >
            {{ submitting ? 'Invio in corso...' : block.content.buttonText }}
          </button>
        </div>

        <div v-if="successMessage" :class="['bg-green-50 border border-green-200 text-green-800 px-4 py-3 text-sm', roundedCorners ? 'rounded-lg' : '']">
          {{ successMessage }}
        </div>

        <div v-if="errorMessage" :class="['bg-red-50 border border-red-200 text-red-700 px-4 py-3 text-sm', roundedCorners ? 'rounded-lg' : '']">
          {{ errorMessage }}
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, watch, computed, onMounted, onBeforeUnmount } from 'vue'
import { usePageStore } from '../../stores/pageStore'
import { useRouter } from 'vue-router'

const props = defineProps({
  block: {
    type: Object,
    required: true
  },
  editable: {
    type: Boolean,
    default: false
  },
  roundedCorners: {
    type: Boolean,
    default: true
  },
  page: {
    type: Object,
    default: null
  }
})

const pageStore = usePageStore()
const router = useRouter()

const blockStyles = computed(() => {
  const styles = props.block.styles || {}
  return {
    backgroundColor: styles.backgroundColor || 'transparent',
    color: styles.textColor || 'inherit',
    padding: styles.padding || undefined,
    fontFamily: styles.fontFamily || undefined
  }
})

const buttonStyles = computed(() => {
  const btnStyle = props.block.content.buttonStyle || {
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

  const shadowMap = {
    none: 'none',
    sm: '0 1px 2px 0 rgba(0, 0, 0, 0.05)',
    md: '0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06)',
    lg: '0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05)',
    xl: '0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04)'
  }

  return {
    backgroundColor: btnStyle.backgroundColor,
    color: btnStyle.textColor,
    fontSize: btnStyle.fontSize,
    padding: btnStyle.padding,
    borderRadius: btnStyle.borderRadius,
    borderWidth: btnStyle.borderWidth,
    borderColor: btnStyle.borderColor,
    borderStyle: btnStyle.borderStyle,
    boxShadow: shadowMap[btnStyle.shadow] || shadowMap.md
  }
})

// Border radius per i campi
const fieldBorderRadiusClass = computed(() => {
  const radiusMap = {
    'none': '',
    'sm': 'rounded',
    'md': 'rounded-md',
    'lg': 'rounded-lg',
    'xl': 'rounded-xl',
    'full': 'rounded-2xl'
  }
  const radius = props.block.content.fieldBorderRadius || 'lg'
  return radiusMap[radius] || 'rounded-lg'
})

// Separa campi input (text, email, tel, etc.) dai textarea
const inputFields = computed(() => {
  return (props.block.content.fields || []).filter(field => field.type !== 'textarea')
})

const textareaFields = computed(() => {
  return (props.block.content.fields || []).filter(field => field.type === 'textarea')
})

const formData = reactive({})
const submitting = ref(false)
const successMessage = ref('')
const errorMessage = ref('')
const privacyAccepted = ref(false)
const recaptchaToken = ref('')
const recaptchaError = ref('')
const recaptchaElement = ref(null)
let recaptchaWidgetId = null

const recaptchaSiteKey = computed(() => {
  // Usa le chiavi da recaptcha_settings se reCAPTCHA è abilitato
  if (props.page?.recaptcha_settings?.enabled) {
    return props.page.recaptcha_settings.site_key || ''
  }
  return ''
})

const canSubmit = computed(() => {
  if (props.block.content.showPrivacy !== false && !privacyAccepted.value) {
    return false
  }
  if (recaptchaSiteKey.value && !recaptchaToken.value) {
    return false
  }
  return !submitting.value
})

// Initialize form data
watch(
  () => props.block.content.fields,
  (fields) => {
    fields.forEach(field => {
      if (!(field.name in formData)) {
        formData[field.name] = ''
      }
    })
  },
  { immediate: true }
)

// Load reCAPTCHA script
const loadRecaptcha = () => {
  return new Promise((resolve, reject) => {
    if (window.grecaptcha) {
      resolve()
      return
    }

    const script = document.createElement('script')
    script.src = 'https://www.google.com/recaptcha/api.js?render=explicit'
    script.async = true
    script.defer = true
    script.onload = () => resolve()
    script.onerror = () => reject(new Error('Failed to load reCAPTCHA'))
    document.head.appendChild(script)
  })
}

// Initialize reCAPTCHA
const initRecaptcha = async () => {
  if (!recaptchaSiteKey.value) return

  try {
    await loadRecaptcha()

    // Wait for grecaptcha to be ready
    const checkReady = setInterval(() => {
      if (window.grecaptcha && window.grecaptcha.render) {
        clearInterval(checkReady)

        if (recaptchaElement.value) {
          recaptchaWidgetId = window.grecaptcha.render(recaptchaElement.value, {
            sitekey: recaptchaSiteKey.value,
            callback: (token) => {
              recaptchaToken.value = token
              recaptchaError.value = ''
            },
            'expired-callback': () => {
              recaptchaToken.value = ''
              recaptchaError.value = 'reCAPTCHA scaduto. Riprova.'
            },
            'error-callback': () => {
              recaptchaToken.value = ''
              recaptchaError.value = 'Errore reCAPTCHA. Riprova.'
            }
          })
        }
      }
    }, 100)

    // Timeout after 5 seconds
    setTimeout(() => clearInterval(checkReady), 5000)
  } catch (error) {
    console.error('Error loading reCAPTCHA:', error)
    recaptchaError.value = 'Impossibile caricare reCAPTCHA'
  }
}

const resetRecaptcha = () => {
  if (window.grecaptcha && recaptchaWidgetId !== null) {
    window.grecaptcha.reset(recaptchaWidgetId)
    recaptchaToken.value = ''
  }
}

const submitForm = async () => {
  if (props.editable) {
    alert('Il form è disabilitato in modalità editing')
    return
  }

  // Validate reCAPTCHA
  if (recaptchaSiteKey.value && !recaptchaToken.value) {
    recaptchaError.value = 'Per favore completa il reCAPTCHA'
    return
  }

  submitting.value = true
  successMessage.value = ''
  errorMessage.value = ''

  try {
    await pageStore.submitLead({
      page_id: pageStore.currentPage?.id,
      privacy_accepted: privacyAccepted.value,
      recaptcha_token: recaptchaToken.value,
      ...formData
    })

    // Reindirizza a pagina di ringraziamento
    if (props.block.content.thankYouUrl && props.block.content.thankYouUrl.trim() !== '') {
      // URL personalizzato configurato
      window.location.href = props.block.content.thankYouUrl
    } else {
      // Usa la pagina di ringraziamento di default
      router.push('/thank-you')
    }
  } catch (error) {
    errorMessage.value = 'Si è verificato un errore. Riprova più tardi.'
    resetRecaptcha()
  } finally {
    submitting.value = false
  }
}

onMounted(() => {
  initRecaptcha()
})

onBeforeUnmount(() => {
  // Cleanup reCAPTCHA if needed
  if (recaptchaWidgetId !== null && window.grecaptcha) {
    try {
      window.grecaptcha.reset(recaptchaWidgetId)
    } catch (e) {
      // Ignore errors on cleanup
    }
  }
})
</script>
