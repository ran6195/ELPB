<template>
  <div class="min-h-screen bg-gray-100 flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
      <div>
        <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
          Registra un nuovo account
        </h2>
        <p class="mt-2 text-center text-sm text-gray-600">
          Oppure
          <router-link to="/login" class="font-medium text-blue-600 hover:text-blue-500">
            accedi qui
          </router-link>
        </p>
      </div>

      <form class="mt-8 space-y-6" @submit.prevent="handleRegister">
        <div v-if="authStore.error" class="rounded-md bg-red-50 p-4">
          <div class="flex">
            <div class="ml-3">
              <h3 class="text-sm font-medium text-red-800">
                {{ authStore.error }}
              </h3>
            </div>
          </div>
        </div>

        <div v-if="successMessage" class="rounded-md bg-green-50 p-4">
          <div class="flex">
            <div class="ml-3">
              <h3 class="text-sm font-medium text-green-800">
                {{ successMessage }}
              </h3>
            </div>
          </div>
        </div>

        <div class="rounded-md shadow-sm space-y-4">
          <div>
            <label for="name" class="block text-sm font-medium text-gray-700">Nome</label>
            <input
              id="name"
              v-model="formData.name"
              name="name"
              type="text"
              required
              class="mt-1 appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
              placeholder="Nome completo"
            />
          </div>

          <div>
            <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
            <input
              id="email"
              v-model="formData.email"
              name="email"
              type="email"
              autocomplete="email"
              required
              class="mt-1 appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
              placeholder="email@example.com"
            />
          </div>

          <div>
            <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
            <input
              id="password"
              v-model="formData.password"
              name="password"
              type="password"
              autocomplete="new-password"
              required
              class="mt-1 appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
              placeholder="Password (min 6 caratteri)"
            />
          </div>

          <div>
            <label for="role" class="block text-sm font-medium text-gray-700">Ruolo</label>
            <select
              id="role"
              v-model="formData.role"
              name="role"
              required
              class="mt-1 block w-full px-3 py-2 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
            >
              <option value="user">Utente</option>
              <option value="company">Azienda</option>
              <option value="admin">Admin</option>
            </select>
          </div>

          <div v-if="formData.role === 'company'">
            <label for="companyName" class="block text-sm font-medium text-gray-700">Nome Azienda</label>
            <input
              id="companyName"
              v-model="formData.companyName"
              name="companyName"
              type="text"
              class="mt-1 appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
              placeholder="Nome azienda"
            />
          </div>

          <div v-if="formData.role === 'user'">
            <label for="companyId" class="block text-sm font-medium text-gray-700">Azienda</label>
            <select
              id="companyId"
              v-model="formData.company_id"
              name="companyId"
              class="mt-1 block w-full px-3 py-2 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
            >
              <option :value="null">Seleziona azienda</option>
              <option v-for="company in companies" :key="company.id" :value="company.id">
                {{ company.name }}
              </option>
            </select>
          </div>
        </div>

        <div>
          <button
            type="submit"
            :disabled="authStore.loading"
            class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            <span v-if="authStore.loading">Caricamento...</span>
            <span v-else>Registrati</span>
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/authStore'

const router = useRouter()
const authStore = useAuthStore()

const formData = ref({
  name: '',
  email: '',
  password: '',
  role: 'user',
  company_id: null,
  companyName: ''
})

const companies = ref([])
const successMessage = ref('')

onMounted(async () => {
  // Carica le aziende se l'utente è autenticato come admin
  if (authStore.isAuthenticated && authStore.isAdmin) {
    const data = await authStore.fetchCompanies()
    if (data.success) {
      companies.value = data.companies
    }
  }
})

const handleRegister = async () => {
  successMessage.value = ''

  const userData = {
    name: formData.value.name,
    email: formData.value.email,
    password: formData.value.password,
    role: formData.value.role,
  }

  // Se è company, aggiungi il nome dell'azienda
  if (formData.value.role === 'company' && formData.value.companyName) {
    userData.companyName = formData.value.companyName
  }

  // Se è user, aggiungi l'ID dell'azienda
  if (formData.value.role === 'user' && formData.value.company_id) {
    userData.company_id = formData.value.company_id
  }

  const user = await authStore.register(userData)

  if (user) {
    successMessage.value = 'Registrazione completata con successo!'
    setTimeout(() => {
      router.push('/login')
    }, 2000)
  }
}
</script>
