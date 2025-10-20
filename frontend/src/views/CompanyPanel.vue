<template>
  <div class="min-h-screen bg-gray-50">
    <!-- HEADER -->
    <div class="bg-white border-b border-gray-200 shadow-sm">
      <div class="max-w-7xl mx-auto px-8 py-6">
        <div class="flex justify-between items-center">
          <div>
            <h1 class="text-2xl font-semibold text-gray-900 mb-1">Pannello Azienda</h1>
            <p class="text-gray-500 text-sm">Gestione utenti e landing pages della tua azienda</p>
          </div>
          <router-link
            to="/"
            class="bg-white hover:bg-gray-50 text-gray-700 px-4 py-2.5 rounded-lg font-medium border border-gray-300 transition-colors"
          >
            ← Torna alle Pagine
          </router-link>
        </div>
      </div>
    </div>

    <!-- TABS -->
    <div class="max-w-7xl mx-auto px-8 py-6">
      <div class="border-b border-gray-200 mb-6">
        <nav class="-mb-px flex space-x-8">
          <button
            @click="activeTab = 'users'"
            :class="[
              'py-4 px-1 border-b-2 font-medium text-sm',
              activeTab === 'users'
                ? 'border-blue-500 text-blue-600'
                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
            ]"
          >
            Utenti
          </button>
          <button
            @click="activeTab = 'pages'"
            :class="[
              'py-4 px-1 border-b-2 font-medium text-sm',
              activeTab === 'pages'
                ? 'border-blue-500 text-blue-600'
                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
            ]"
          >
            Gestione Landing Pages
          </button>
          <button
            @click="activeTab = 'create'"
            :class="[
              'py-4 px-1 border-b-2 font-medium text-sm',
              activeTab === 'create'
                ? 'border-blue-500 text-blue-600'
                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
            ]"
          >
            Crea Utente
          </button>
        </nav>
      </div>

      <!-- ERROR MESSAGE -->
      <div v-if="authStore.error" class="mb-4 rounded-md bg-red-50 p-4">
        <p class="text-sm text-red-800">{{ authStore.error }}</p>
      </div>

      <!-- SUCCESS MESSAGE -->
      <div v-if="successMessage" class="mb-4 rounded-md bg-green-50 p-4">
        <p class="text-sm text-green-800">{{ successMessage }}</p>
      </div>

      <!-- TAB: USERS -->
      <div v-if="activeTab === 'users'" class="bg-white rounded-lg shadow">
        <div class="px-6 py-4 border-b border-gray-200">
          <h2 class="text-lg font-medium text-gray-900">Utenti Azienda</h2>
        </div>
        <div class="p-6">
          <div v-if="loading" class="text-center py-8">
            <p class="text-gray-500">Caricamento...</p>
          </div>
          <div v-else class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gray-50">
                <tr>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nome</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ruolo</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Landing Pages</th>
                  <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Azioni</th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-gray-200">
                <tr v-for="user in users" :key="user.id">
                  <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ user.name }}</td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ user.email }}</td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm">
                    <span
                      :class="{
                        'bg-blue-100 text-blue-700': user.role === 'company',
                        'bg-gray-100 text-gray-700': user.role === 'user'
                      }"
                      class="px-2 py-1 rounded-md text-xs font-medium"
                    >
                      {{ user.role }}
                    </span>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    <span class="font-semibold">{{ user.pages_count || 0 }}</span> pagine
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                    <button
                      v-if="user.id !== authStore.user.id && user.role !== 'company'"
                      @click="handleDeleteUser(user.id, user.pages_count)"
                      :disabled="user.pages_count > 0"
                      :class="[
                        'text-red-600 hover:text-red-900',
                        user.pages_count > 0 ? 'opacity-50 cursor-not-allowed' : ''
                      ]"
                    >
                      Elimina
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- TAB: PAGES -->
      <div v-if="activeTab === 'pages'" class="bg-white rounded-lg shadow">
        <div class="px-6 py-4 border-b border-gray-200">
          <h2 class="text-lg font-medium text-gray-900">Gestione Landing Pages</h2>
        </div>
        <div class="p-6">
          <div v-if="loading" class="text-center py-8">
            <p class="text-gray-500">Caricamento...</p>
          </div>
          <div v-else class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gray-50">
                <tr>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Titolo</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Slug</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Stato</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Assegnata a</th>
                  <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Azioni</th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-gray-200">
                <tr v-for="page in pages" :key="page.id">
                  <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ page.title }}</td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ page.slug }}</td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm">
                    <span
                      :class="page.is_published ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-700'"
                      class="px-2 py-1 rounded-md text-xs font-medium"
                    >
                      {{ page.is_published ? 'Pubblicata' : 'Bozza' }}
                    </span>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm">
                    <span v-if="reassigningPage && reassigningPage.id === page.id">
                      <select
                        v-model="reassigningPage.new_user_id"
                        class="block w-full px-3 py-1 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                      >
                        <option v-for="user in users.filter(u => u.role === 'user' || u.role === 'company')" :key="user.id" :value="user.id">
                          {{ user.name }}
                        </option>
                      </select>
                    </span>
                    <span v-else class="text-gray-900">
                      {{ page.user?.name || '-' }}
                    </span>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
                    <template v-if="reassigningPage && reassigningPage.id === page.id">
                      <button
                        @click="saveReassignment"
                        class="text-green-600 hover:text-green-900"
                      >
                        Salva
                      </button>
                      <button
                        @click="reassigningPage = null"
                        class="text-gray-600 hover:text-gray-900"
                      >
                        Annulla
                      </button>
                    </template>
                    <template v-else>
                      <button
                        @click="startReassign(page)"
                        class="text-blue-600 hover:text-blue-900"
                      >
                        Riassegna
                      </button>
                    </template>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- TAB: CREATE USER -->
      <div v-if="activeTab === 'create'" class="bg-white rounded-lg shadow max-w-2xl">
        <div class="px-6 py-4 border-b border-gray-200">
          <h2 class="text-lg font-medium text-gray-900">Crea Nuovo Utente</h2>
        </div>
        <form @submit.prevent="handleCreateUser" class="p-6 space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700">Nome</label>
            <input
              v-model="newUser.name"
              type="text"
              required
              class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
            />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700">Email</label>
            <input
              v-model="newUser.email"
              type="email"
              required
              class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
            />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700">Password</label>
            <input
              v-model="newUser.password"
              type="password"
              required
              minlength="6"
              class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
            />
          </div>
          <div class="bg-blue-50 p-4 rounded-md">
            <p class="text-sm text-blue-800">
              L'utente verrà creato automaticamente e associato alla tua azienda ({{ authStore.user?.company?.name }})
            </p>
          </div>
          <button
            type="submit"
            :disabled="authStore.loading"
            class="w-full bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md font-medium disabled:opacity-50"
          >
            Crea Utente
          </button>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/authStore'
import { usePageStore } from '../stores/pageStore'

const router = useRouter()
const authStore = useAuthStore()
const pageStore = usePageStore()

const activeTab = ref('users')
const loading = ref(false)
const users = ref([])
const pages = ref([])
const reassigningPage = ref(null)
const successMessage = ref('')

const newUser = ref({
  name: '',
  email: '',
  password: ''
})

// Verifica che l'utente sia company manager
onMounted(async () => {
  if (!authStore.isCompany) {
    router.push('/')
    return
  }
  await loadData()
})

const loadData = async () => {
  loading.value = true
  try {
    users.value = await authStore.getUsersWithPagesCount()
    await pageStore.fetchPages()
    pages.value = pageStore.pages
  } catch (error) {
    console.error('Error loading data:', error)
  } finally {
    loading.value = false
  }
}

const handleDeleteUser = async (userId, pagesCount) => {
  if (pagesCount > 0) {
    alert('Non puoi eliminare un utente con landing pages associate. Riassegna prima le pagine.')
    return
  }

  if (!confirm('Sei sicuro di voler eliminare questo utente?')) return

  try {
    await authStore.deleteUserFromCompany(userId)
    successMessage.value = 'Utente eliminato con successo'
    setTimeout(() => successMessage.value = '', 3000)
    await loadData()
  } catch (error) {
    console.error('Error deleting user:', error)
  }
}

const startReassign = (page) => {
  reassigningPage.value = {
    id: page.id,
    new_user_id: page.user_id
  }
}

const saveReassignment = async () => {
  if (!reassigningPage.value) return

  try {
    await pageStore.reassignPage(reassigningPage.value.id, reassigningPage.value.new_user_id)
    successMessage.value = 'Pagina riassegnata con successo'
    setTimeout(() => successMessage.value = '', 3000)
    reassigningPage.value = null
    await loadData()
  } catch (error) {
    console.error('Error reassigning page:', error)
  }
}

const handleCreateUser = async () => {
  try {
    await authStore.createUserInCompany(newUser.value)
    successMessage.value = 'Utente creato con successo'
    setTimeout(() => successMessage.value = '', 3000)
    newUser.value = {
      name: '',
      email: '',
      password: ''
    }
    await loadData()
  } catch (error) {
    console.error('Error creating user:', error)
  }
}
</script>
