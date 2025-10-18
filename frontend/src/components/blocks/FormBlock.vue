<template>
  <div class="form-block">
    <div class="max-w-2xl mx-auto px-4">
      <h2 class="text-3xl font-bold mb-8 text-center">
        {{ block.content.title }}
      </h2>

      <form @submit.prevent="submitForm" class="space-y-4">
        <div
          v-for="field in block.content.fields"
          :key="field.name"
          class="form-field"
        >
          <label :for="field.name" class="block text-sm font-medium mb-2">
            {{ field.label }}
            <span v-if="field.required" class="text-red-500">*</span>
          </label>

          <textarea
            v-if="field.type === 'textarea'"
            :id="field.name"
            v-model="formData[field.name]"
            :required="field.required"
            rows="4"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-200 focus:border-primary-500 transition-all outline-none"
          ></textarea>

          <input
            v-else
            :id="field.name"
            :type="field.type"
            v-model="formData[field.name]"
            :required="field.required"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-200 focus:border-primary-500 transition-all outline-none"
          />
        </div>

        <button
          type="submit"
          :disabled="submitting"
          class="w-full bg-primary-600 hover:bg-primary-700 text-white py-2.5 rounded-lg font-medium transition-colors disabled:opacity-50"
        >
          {{ submitting ? 'Invio in corso...' : block.content.buttonText }}
        </button>

        <div v-if="successMessage" class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg text-sm">
          {{ successMessage }}
        </div>

        <div v-if="errorMessage" class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm">
          {{ errorMessage }}
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, watch } from 'vue'
import { usePageStore } from '../../stores/pageStore'

const props = defineProps({
  block: {
    type: Object,
    required: true
  },
  editable: {
    type: Boolean,
    default: false
  }
})

const pageStore = usePageStore()
const formData = reactive({})
const submitting = ref(false)
const successMessage = ref('')
const errorMessage = ref('')

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

const submitForm = async () => {
  if (props.editable) {
    alert('Il form è disabilitato in modalità editing')
    return
  }

  submitting.value = true
  successMessage.value = ''
  errorMessage.value = ''

  try {
    await pageStore.submitLead({
      page_id: pageStore.currentPage?.id,
      ...formData
    })

    successMessage.value = 'Grazie! La tua richiesta è stata inviata con successo.'

    // Reset form
    Object.keys(formData).forEach(key => {
      formData[key] = ''
    })
  } catch (error) {
    errorMessage.value = 'Si è verificato un errore. Riprova più tardi.'
  } finally {
    submitting.value = false
  }
}
</script>
