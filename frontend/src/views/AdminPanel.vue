<template>
  <div class="min-h-screen bg-gray-50">
    <!-- HEADER -->
    <div class="bg-white border-b border-gray-200 shadow-sm">
      <div class="max-w-7xl mx-auto px-8 py-6">
        <div class="flex justify-between items-center">
          <div>
            <h1 class="text-2xl font-semibold text-gray-900 mb-1">Pannello Amministrazione</h1>
            <p class="text-gray-500 text-sm">Gestione utenti e società</p>
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
            @click="activeTab = 'companies'"
            :class="[
              'py-4 px-1 border-b-2 font-medium text-sm',
              activeTab === 'companies'
                ? 'border-blue-500 text-blue-600'
                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
            ]"
          >
            Società
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
            Crea Nuovo
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
          <h2 class="text-lg font-medium text-gray-900">Gestione Utenti</h2>
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
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Società</th>
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
                        'bg-purple-100 text-purple-700': user.role === 'admin',
                        'bg-blue-100 text-blue-700': user.role === 'company',
                        'bg-gray-100 text-gray-700': user.role === 'user'
                      }"
                      class="px-2 py-1 rounded-md text-xs font-medium"
                    >
                      {{ user.role }}
                    </span>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    <span v-if="editingUser && editingUser.id === user.id">
                      <select
                        v-model="editingUser.company_id"
                        class="block w-full px-3 py-1 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                      >
                        <option :value="null">Nessuna</option>
                        <option v-for="company in companies" :key="company.id" :value="company.id">
                          {{ company.name }}
                        </option>
                      </select>
                    </span>
                    <span v-else>
                      {{ user.company?.name || '-' }}
                    </span>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
                    <template v-if="editingUser && editingUser.id === user.id">
                      <button
                        @click="saveUser"
                        class="text-green-600 hover:text-green-900"
                      >
                        Salva
                      </button>
                      <button
                        @click="editingUser = null"
                        class="text-gray-600 hover:text-gray-900"
                      >
                        Annulla
                      </button>
                    </template>
                    <template v-else>
                      <button
                        @click="startEditUser(user)"
                        class="text-blue-600 hover:text-blue-900"
                      >
                        Modifica
                      </button>
                      <button
                        v-if="user.id !== authStore.user.id"
                        @click="handleDeleteUser(user.id)"
                        class="text-red-600 hover:text-red-900"
                      >
                        Elimina
                      </button>
                    </template>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- TAB: COMPANIES -->
      <div v-if="activeTab === 'companies'" class="bg-white rounded-lg shadow">
        <div class="px-6 py-4 border-b border-gray-200">
          <h2 class="text-lg font-medium text-gray-900">Gestione Società</h2>
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
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Utenti</th>
                  <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Azioni</th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-gray-200">
                <tr v-for="company in companies" :key="company.id">
                  <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ company.name }}</td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ company.email }}</td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    {{ company.users?.length || 0 }}
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                    <button
                      @click="handleDeleteCompany(company.id)"
                      :disabled="company.users && company.users.length > 0"
                      :class="[
                        'text-red-600 hover:text-red-900',
                        company.users && company.users.length > 0 ? 'opacity-50 cursor-not-allowed' : ''
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

      <!-- TAB: CREATE -->
      <div v-if="activeTab === 'create'">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <!-- CREATE USER -->
          <div class="bg-white rounded-lg shadow">
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
              <div>
                <label class="block text-sm font-medium text-gray-700">Ruolo</label>
                <select
                  v-model="newUser.role"
                  class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                >
                  <option value="user">User</option>
                  <option value="company">Company</option>
                  <option value="admin">Admin</option>
                </select>
              </div>
              <div v-if="newUser.role === 'user' || newUser.role === 'company'">
                <label class="block text-sm font-medium text-gray-700">Società</label>
                <select
                  v-model="newUser.company_id"
                  class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                >
                  <option :value="null">Nessuna</option>
                  <option v-for="company in companies" :key="company.id" :value="company.id">
                    {{ company.name }}
                  </option>
                </select>
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

          <!-- CREATE COMPANY -->
          <div class="bg-white rounded-lg shadow">
            <div class="px-6 py-4 border-b border-gray-200">
              <h2 class="text-lg font-medium text-gray-900">Crea Nuova Società</h2>
              <p class="text-sm text-gray-500 mt-1">Crea società e utente manager associato</p>
            </div>
            <form @submit.prevent="handleCreateCompany" class="p-6 space-y-4">
              <!-- Dati Società -->
              <div class="pb-4 border-b border-gray-200">
                <h3 class="text-sm font-semibold text-gray-700 mb-3">Dati Società</h3>
                <div class="space-y-3">
                  <div>
                    <label class="block text-sm font-medium text-gray-700">Nome Società</label>
                    <input
                      v-model="newCompany.name"
                      type="text"
                      required
                      class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                      placeholder="Es: Acme Inc."
                    />
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700">Email Società</label>
                    <input
                      v-model="newCompany.email"
                      type="email"
                      required
                      class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                      placeholder="info@azienda.com"
                    />
                  </div>
                </div>
              </div>

              <!-- Dati Manager -->
              <div>
                <h3 class="text-sm font-semibold text-gray-700 mb-3">Dati Manager (Utente Company)</h3>
                <div class="space-y-3">
                  <div>
                    <label class="block text-sm font-medium text-gray-700">Nome Manager</label>
                    <input
                      v-model="newCompany.manager_name"
                      type="text"
                      required
                      class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                      placeholder="Mario Rossi"
                    />
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700">Email Manager</label>
                    <input
                      v-model="newCompany.manager_email"
                      type="email"
                      required
                      class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                      placeholder="mario.rossi@azienda.com"
                    />
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700">Password Manager</label>
                    <input
                      v-model="newCompany.manager_password"
                      type="password"
                      required
                      minlength="6"
                      class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                      placeholder="Min. 6 caratteri"
                    />
                  </div>
                </div>
              </div>

              <div class="bg-blue-50 p-3 rounded-md">
                <p class="text-xs text-blue-800">
                  Verrà creata la società e automaticamente un utente con ruolo "Company Manager" associato alla società.
                </p>
              </div>

              <button
                type="submit"
                :disabled="authStore.loading"
                class="w-full bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md font-medium disabled:opacity-50"
              >
                Crea Società e Manager
              </button>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/authStore'

const router = useRouter()
const authStore = useAuthStore()

const activeTab = ref('users')
const loading = ref(false)
const users = ref([])
const companies = ref([])
const editingUser = ref(null)
const successMessage = ref('')

const newUser = ref({
  name: '',
  email: '',
  password: '',
  role: 'user',
  company_id: null
})

const newCompany = ref({
  name: '',
  email: '',
  manager_name: '',
  manager_email: '',
  manager_password: ''
})

// Verifica che l'utente sia admin
onMounted(async () => {
  if (!authStore.isAdmin) {
    router.push('/')
    return
  }
  await loadData()
})

const loadData = async () => {
  loading.value = true
  try {
    const [usersData, companiesData] = await Promise.all([
      authStore.fetchUsers(),
      authStore.fetchCompanies()
    ])
    users.value = usersData
    companies.value = companiesData
  } catch (error) {
    console.error('Error loading data:', error)
  } finally {
    loading.value = false
  }
}

const startEditUser = (user) => {
  editingUser.value = { ...user }
}

const saveUser = async () => {
  if (!editingUser.value) return

  try {
    await authStore.updateUser(editingUser.value.id, {
      company_id: editingUser.value.company_id
    })
    successMessage.value = 'Utente aggiornato con successo'
    setTimeout(() => successMessage.value = '', 3000)
    editingUser.value = null
    await loadData()
  } catch (error) {
    console.error('Error updating user:', error)
  }
}

const handleDeleteUser = async (userId) => {
  if (!confirm('Sei sicuro di voler eliminare questo utente?')) return

  try {
    await authStore.deleteUser(userId)
    successMessage.value = 'Utente eliminato con successo'
    setTimeout(() => successMessage.value = '', 3000)
    await loadData()
  } catch (error) {
    console.error('Error deleting user:', error)
  }
}

const handleDeleteCompany = async (companyId) => {
  if (!confirm('Sei sicuro di voler eliminare questa società?')) return

  try {
    await authStore.deleteCompany(companyId)
    successMessage.value = 'Società eliminata con successo'
    setTimeout(() => successMessage.value = '', 3000)
    await loadData()
  } catch (error) {
    console.error('Error deleting company:', error)
  }
}

const handleCreateUser = async () => {
  try {
    await authStore.register(newUser.value)
    successMessage.value = 'Utente creato con successo'
    setTimeout(() => successMessage.value = '', 3000)
    newUser.value = {
      name: '',
      email: '',
      password: '',
      role: 'user',
      company_id: null
    }
    await loadData()
  } catch (error) {
    console.error('Error creating user:', error)
  }
}

const handleCreateCompany = async () => {
  try {
    const result = await authStore.createCompany(newCompany.value)
    successMessage.value = `Società "${result.company.name}" e manager "${result.manager.name}" creati con successo`
    setTimeout(() => successMessage.value = '', 3000)
    newCompany.value = {
      name: '',
      email: '',
      manager_name: '',
      manager_email: '',
      manager_password: ''
    }
    await loadData()
  } catch (error) {
    console.error('Error creating company:', error)
  }
}
</script>
