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
          <button
            @click="activeTab = 'leads'"
            :class="[
              'py-4 px-1 border-b-2 font-medium text-sm',
              activeTab === 'leads'
                ? 'border-blue-500 text-blue-600'
                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
            ]"
          >
            <span class="flex items-center gap-2">
              Leads
              <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700">
                <span class="w-1.5 h-1.5 rounded-full bg-green-500 animate-pulse"></span>
                live
              </span>
            </span>
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
                        class="text-blue-600 hover:text-blue-900 inline-flex items-center"
                        title="Modifica"
                      >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                      </button>
                      <button
                        v-if="user.id !== authStore.user.id"
                        @click="handleDeleteUser(user.id)"
                        class="text-red-600 hover:text-red-900 inline-flex items-center"
                        title="Elimina"
                      >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
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
                        'text-red-600 hover:text-red-900 inline-flex items-center',
                        company.users && company.users.length > 0 ? 'opacity-50 cursor-not-allowed' : ''
                      ]"
                      title="Elimina"
                    >
                      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                      </svg>
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

      <!-- TAB: LEADS -->
      <div v-if="activeTab === 'leads'" class="bg-white rounded-lg shadow">
        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
          <div>
            <h2 class="text-lg font-medium text-gray-900">Gestione Leads</h2>
            <p class="text-sm text-gray-500 mt-1">Visualizza e gestisci i contatti acquisiti dai form</p>
          </div>
          <div class="flex rounded-lg border border-gray-300 overflow-hidden text-sm font-medium">
            <button
              @click="leadsView = 'lista'"
              :class="leadsView === 'lista' ? 'bg-blue-600 text-white' : 'bg-white text-gray-600 hover:bg-gray-50'"
              class="px-4 py-2 transition-colors"
            >Lista</button>
            <button
              @click="leadsView = 'calendario'"
              :class="leadsView === 'calendario' ? 'bg-blue-600 text-white' : 'bg-white text-gray-600 hover:bg-gray-50'"
              class="px-4 py-2 border-l border-gray-300 transition-colors"
            >Calendario</button>
          </div>
        </div>
        <div class="p-6">
          <!-- Vista Calendario -->
          <LeadsCalendar
            v-if="leadsView === 'calendario'"
            :leads="leads"
            @show-lead="showLeadDetails"
          />

          <template v-if="leadsView === 'lista'">
          <div v-if="loading" class="text-center py-8">
            <p class="text-gray-500">Caricamento...</p>
          </div>
          <div v-else-if="!leads || leads.length === 0" class="text-center py-8">
            <p class="text-gray-500">Nessun lead presente</p>
          </div>
          <div v-else class="overflow-x-auto">
            <div class="mb-4 space-y-3">
              <!-- Riga 1: search + export + contatore -->
              <div class="flex items-center gap-3">
                <div class="relative flex-1 max-w-sm">
                  <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                  </svg>
                  <input
                    v-model="filterText"
                    type="text"
                    placeholder="Cerca per nome, email, telefono..."
                    class="w-full pl-9 pr-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                  />
                </div>
                <span class="text-sm text-gray-600 whitespace-nowrap">
                  <span v-if="hasActiveFilters">{{ filteredLeads.length }} di </span>{{ leads.length }} leads
                </span>
                <button
                  v-if="hasActiveFilters"
                  @click="resetFilters"
                  class="text-sm text-gray-500 hover:text-gray-700 underline whitespace-nowrap"
                >Azzera filtri</button>
                <div class="flex-1"></div>
                <button
                  @click="exportLeads"
                  class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors whitespace-nowrap"
                >
                  Esporta CSV
                </button>
              </div>
              <!-- Riga 2: filtri colonna -->
              <div class="flex items-center gap-3 flex-wrap">
                <select
                  v-model="filterPage"
                  class="border border-gray-300 rounded-lg text-sm px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
                  <option value="">Tutte le pagine</option>
                  <option v-for="p in availablePages" :key="p.id" :value="p.id">{{ p.title }}</option>
                </select>
                <select
                  v-model="filterStatus"
                  class="border border-gray-300 rounded-lg text-sm px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
                  <option value="">Tutti gli stati</option>
                  <option value="published">Pubblicata</option>
                  <option value="unpublished">Non pubblicata</option>
                </select>
                <select
                  v-model="filterAppointment"
                  class="border border-gray-300 rounded-lg text-sm px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
                  <option value="">Tutti i lead</option>
                  <option value="with">Con appuntamento</option>
                  <option value="without">Senza appuntamento</option>
                </select>
                <select
                  v-model="leadsPerPage"
                  class="border border-gray-300 rounded-lg text-sm px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
                  <option :value="10">10 per pagina</option>
                  <option :value="20">20 per pagina</option>
                  <option :value="50">50 per pagina</option>
                  <option :value="100">100 per pagina</option>
                </select>
              </div>
              <p class="text-xs text-gray-500 italic">
                ℹ️ L'accettazione della privacy policy è gestita lato client prima dell'invio del form
              </p>
            </div>
            <table class="w-full divide-y divide-gray-200 table-auto">
              <thead class="bg-gray-50">
                <tr>
                  <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Data</th>
                  <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nome</th>
                  <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                  <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Telefono</th>
                  <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pagina</th>
                  <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Stato</th>
                  <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Azioni</th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-gray-200">
                <tr v-for="lead in paginatedLeads" :key="lead.id" class="hover:bg-gray-50">
                  <td class="px-4 py-4 text-sm text-gray-500">
                    {{ formatDate(lead.created_at) }}
                  </td>
                  <td class="px-4 py-4 text-sm font-medium text-gray-900">
                    <div class="flex items-center gap-2">
                      <span>{{ lead.name || '-' }}</span>
                      <span
                        v-if="lead.metadata?._appointment?.date"
                        class="inline-flex items-center gap-1 bg-blue-100 text-blue-700 text-xs font-medium px-1.5 py-0.5 rounded-full"
                        :title="`Appuntamento: ${lead.metadata._appointment.date}${lead.metadata._appointment.time ? ' ' + lead.metadata._appointment.time : ''}`"
                      >
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        {{ lead.metadata._appointment.date }}
                      </span>
                    </div>
                  </td>
                  <td class="px-4 py-4 text-sm text-gray-500">
                    <a :href="`mailto:${lead.email}`" class="text-blue-600 hover:text-blue-800 break-words">
                      {{ lead.email }}
                    </a>
                  </td>
                  <td class="px-4 py-4 text-sm text-gray-500">
                    <a v-if="lead.phone" :href="`tel:${lead.phone}`" class="text-blue-600 hover:text-blue-800">
                      {{ lead.phone }}
                    </a>
                    <span v-else>-</span>
                  </td>
                  <td class="px-4 py-4 text-sm text-gray-500">
                    <span v-if="lead.page" class="text-gray-700">{{ lead.page.title }}</span>
                    <span v-else class="text-gray-400">-</span>
                  </td>
                  <td class="px-4 py-4 text-sm">
                    <span
                      :class="{
                        'bg-green-100 text-green-700': lead.page?.is_published,
                        'bg-gray-100 text-gray-700': !lead.page?.is_published
                      }"
                      class="px-2 py-1 rounded-md text-xs font-medium whitespace-nowrap inline-block"
                    >
                      {{ lead.page?.is_published ? 'Pubblicata' : 'Non pubblicata' }}
                    </span>
                  </td>
                  <td class="px-4 py-4 text-right text-sm font-medium space-x-2">
                    <button
                      @click="showLeadDetails(lead)"
                      class="text-blue-600 hover:text-blue-900 inline-flex items-center"
                      title="Dettagli"
                    >
                      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                      </svg>
                    </button>
                    <button
                      @click="handleDeleteLead(lead.id)"
                      class="text-red-600 hover:text-red-900 inline-flex items-center"
                      title="Elimina"
                    >
                      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                      </svg>
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
            <!-- Paginazione -->
            <div v-if="totalPages > 1" class="mt-4 flex items-center justify-between">
              <p class="text-sm text-gray-500">
                Pagina {{ currentPage }} di {{ totalPages }}
              </p>
              <div class="flex items-center gap-1">
                <button
                  @click="currentPage = 1"
                  :disabled="currentPage === 1"
                  class="px-2 py-1.5 rounded-md text-sm border border-gray-300 disabled:opacity-40 disabled:cursor-not-allowed hover:bg-gray-50 transition-colors"
                  title="Prima pagina"
                >«</button>
                <button
                  @click="currentPage--"
                  :disabled="currentPage === 1"
                  class="px-3 py-1.5 rounded-md text-sm border border-gray-300 disabled:opacity-40 disabled:cursor-not-allowed hover:bg-gray-50 transition-colors"
                >‹</button>
                <template v-for="p in totalPages" :key="p">
                  <button
                    v-if="p === 1 || p === totalPages || Math.abs(p - currentPage) <= 2"
                    @click="currentPage = p"
                    :class="[
                      'px-3 py-1.5 rounded-md text-sm border transition-colors',
                      p === currentPage
                        ? 'bg-blue-600 text-white border-blue-600'
                        : 'border-gray-300 hover:bg-gray-50'
                    ]"
                  >{{ p }}</button>
                  <span
                    v-else-if="p === currentPage - 3 || p === currentPage + 3"
                    class="px-1 text-gray-400"
                  >…</span>
                </template>
                <button
                  @click="currentPage++"
                  :disabled="currentPage === totalPages"
                  class="px-3 py-1.5 rounded-md text-sm border border-gray-300 disabled:opacity-40 disabled:cursor-not-allowed hover:bg-gray-50 transition-colors"
                >›</button>
                <button
                  @click="currentPage = totalPages"
                  :disabled="currentPage === totalPages"
                  class="px-2 py-1.5 rounded-md text-sm border border-gray-300 disabled:opacity-40 disabled:cursor-not-allowed hover:bg-gray-50 transition-colors"
                  title="Ultima pagina"
                >»</button>
              </div>
            </div>
          </div>
          </template>
        </div>
      </div>

      <!-- MODAL DETTAGLI LEAD -->
      <div
        v-if="selectedLead"
        class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
        @click="selectedLead = null"
      >
        <div
          class="bg-white rounded-lg shadow-xl max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto"
          @click.stop
        >
          <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
            <h3 class="text-lg font-medium text-gray-900">Dettagli Lead</h3>
            <button
              @click="selectedLead = null"
              class="text-gray-400 hover:text-gray-600"
            >
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
              </svg>
            </button>
          </div>
          <div class="p-6 space-y-4">
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="text-sm font-medium text-gray-500">Data</label>
                <p class="text-gray-900">{{ formatDate(selectedLead.created_at) }}</p>
              </div>
              <div>
                <label class="text-sm font-medium text-gray-500">Nome</label>
                <p class="text-gray-900">{{ selectedLead.name || '-' }}</p>
              </div>
              <div>
                <label class="text-sm font-medium text-gray-500">Email</label>
                <p class="text-gray-900">
                  <a :href="`mailto:${selectedLead.email}`" class="text-blue-600 hover:text-blue-800">
                    {{ selectedLead.email }}
                  </a>
                </p>
              </div>
              <div>
                <label class="text-sm font-medium text-gray-500">Telefono</label>
                <p class="text-gray-900">
                  <a v-if="selectedLead.phone" :href="`tel:${selectedLead.phone}`" class="text-blue-600 hover:text-blue-800">
                    {{ selectedLead.phone }}
                  </a>
                  <span v-else>-</span>
                </p>
              </div>
              <div>
                <label class="text-sm font-medium text-gray-500">Pagina</label>
                <p class="text-gray-900">{{ selectedLead.page?.title || '-' }}</p>
              </div>
              <div>
                <label class="text-sm font-medium text-gray-500">Stato Pagina</label>
                <p class="text-gray-900">{{ selectedLead.page?.is_published ? 'Pubblicata' : 'Non pubblicata' }}</p>
              </div>
            </div>
            <div v-if="selectedLead.message">
              <label class="text-sm font-medium text-gray-500">Messaggio</label>
              <p class="text-gray-900 mt-1 p-3 bg-gray-50 rounded-md">{{ selectedLead.message }}</p>
            </div>
            <div v-if="selectedLead.metadata && Object.keys(selectedLead.metadata).filter(k => k !== '_notes' && k !== '_appointment').length > 0">
              <label class="text-sm font-medium text-gray-500">Dati Extra</label>
              <div class="mt-1 p-3 bg-gray-50 rounded-md space-y-2">
                <div v-for="(value, key) in selectedLead.metadata" :key="key" class="flex justify-between">
                  <template v-if="key !== '_notes' && key !== '_appointment'">
                    <span class="text-gray-600 font-medium">{{ key }}:</span>
                    <span class="text-gray-900">{{ value }}</span>
                  </template>
                </div>
              </div>
            </div>
          </div>
          <!-- Appuntamento -->
          <div class="px-6 pb-4 space-y-3 border-b border-gray-100">
            <div class="flex items-center justify-between">
              <label class="text-sm font-medium text-gray-500">Appuntamento</label>
              <button
                v-if="selectedLead.metadata?._appointment && !editingAppointment"
                @click="editingAppointment = true"
                class="text-xs text-primary-600 hover:text-primary-800 font-medium"
              >Modifica</button>
            </div>

            <!-- Appuntamento esistente (sola lettura) -->
            <div
              v-if="selectedLead.metadata?._appointment && !editingAppointment"
              class="p-3 bg-blue-50 border border-blue-100 rounded-md space-y-1"
            >
              <p class="text-sm font-medium text-blue-900">
                📅 {{ selectedLead.metadata._appointment.date }}
                <span v-if="selectedLead.metadata._appointment.time"> — 🕐 {{ selectedLead.metadata._appointment.time }}</span>
              </p>
              <p v-if="selectedLead.metadata._appointment.notes" class="text-sm text-blue-700">{{ selectedLead.metadata._appointment.notes }}</p>
              <p class="text-xs text-gray-400">Impostato da {{ selectedLead.metadata._appointment.set_by }}</p>
            </div>

            <!-- Form aggiungi/modifica appuntamento -->
            <div v-if="!selectedLead.metadata?._appointment || editingAppointment" class="space-y-2">
              <div class="grid grid-cols-2 gap-2">
                <div>
                  <label class="block text-xs text-gray-500 mb-1">Data *</label>
                  <input
                    v-model="appointmentForm.date"
                    type="date"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-200 focus:border-primary-500 outline-none"
                  />
                </div>
                <div>
                  <label class="block text-xs text-gray-500 mb-1">Ora</label>
                  <input
                    v-model="appointmentForm.time"
                    type="time"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-200 focus:border-primary-500 outline-none"
                  />
                </div>
              </div>
              <input
                v-model="appointmentForm.notes"
                type="text"
                placeholder="Note appuntamento (opzionale)"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-200 focus:border-primary-500 outline-none"
              />
              <div class="flex gap-2">
                <button
                  @click="saveAppointment"
                  :disabled="!appointmentForm.date || savingAppointment"
                  class="flex items-center gap-2 px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 disabled:opacity-40 disabled:cursor-not-allowed transition-colors"
                >
                  {{ savingAppointment ? 'Salvataggio...' : (selectedLead.metadata?._appointment ? 'Aggiorna' : 'Salva appuntamento') }}
                </button>
                <button
                  v-if="editingAppointment"
                  @click="editingAppointment = false"
                  class="px-4 py-2 text-sm text-gray-600 hover:text-gray-800 font-medium"
                >Annulla</button>
              </div>
            </div>
          </div>

          <!-- Note -->
          <div class="px-6 pb-4 space-y-3">
            <label class="text-sm font-medium text-gray-500">Note</label>
            <div v-if="selectedLead.metadata?._notes?.length" class="space-y-2">
              <div
                v-for="note in selectedLead.metadata._notes"
                :key="note.timestamp"
                class="p-3 bg-amber-50 border border-amber-100 rounded-md"
              >
                <p class="text-sm text-gray-800 whitespace-pre-wrap">{{ note.text }}</p>
                <p class="text-xs text-gray-400 mt-1">{{ formatDate(note.timestamp) }} — {{ note.author }}</p>
              </div>
            </div>
            <div class="space-y-2">
              <textarea
                v-model="newNoteText"
                rows="2"
                placeholder="Aggiungi una nota..."
                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-200 focus:border-primary-500 outline-none resize-none"
              ></textarea>
              <button
                @click="saveNote"
                :disabled="!newNoteText.trim() || savingNote"
                class="flex items-center gap-2 px-4 py-2 bg-primary-600 text-white text-sm font-medium rounded-lg hover:bg-primary-700 disabled:opacity-40 disabled:cursor-not-allowed transition-colors"
              >
                <svg v-if="!savingNote" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                <svg v-else class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                </svg>
                {{ savingNote ? 'Salvataggio...' : 'Aggiungi nota' }}
              </button>
            </div>
          </div>

          <div class="px-6 py-4 border-t border-gray-200 flex gap-3">
            <a
              :href="`mailto:${selectedLead.email}`"
              class="flex items-center gap-2 px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
              </svg>
              Invia Email
            </a>
            <a
              v-if="selectedLead.phone"
              :href="`tel:${selectedLead.phone}`"
              class="flex items-center gap-2 px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 transition-colors"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
              </svg>
              Telefona
            </a>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/authStore'
import { usePageStore } from '../stores/pageStore'
import LeadsCalendar from '../components/LeadsCalendar.vue'

const router = useRouter()
const authStore = useAuthStore()
const pageStore = usePageStore()

const activeTab = ref('users')
const leadsView = ref('lista')
const loading = ref(false)
const users = ref([])
const companies = ref([])
const leads = ref([])
const editingUser = ref(null)
const selectedLead = ref(null)
const newNoteText = ref('')
const savingNote = ref(false)
const editingAppointment = ref(false)
const savingAppointment = ref(false)
const appointmentForm = ref({ date: '', time: '', notes: '' })

const saveAppointment = async () => {
  if (!appointmentForm.value.date || !selectedLead.value) return
  savingAppointment.value = true
  try {
    const result = await pageStore.updateLeadAppointment(selectedLead.value.id, appointmentForm.value)
    selectedLead.value.metadata = result.metadata
    editingAppointment.value = false
    appointmentForm.value = { date: '', time: '', notes: '' }
  } catch (e) {
    console.error('Errore salvataggio appuntamento:', e)
  } finally {
    savingAppointment.value = false
  }
}

const saveNote = async () => {
  if (!newNoteText.value.trim() || !selectedLead.value) return
  savingNote.value = true
  try {
    const result = await pageStore.addLeadNote(selectedLead.value.id, newNoteText.value.trim())
    selectedLead.value.metadata = result.metadata
    newNoteText.value = ''
  } catch (e) {
    console.error('Errore salvataggio nota:', e)
  } finally {
    savingNote.value = false
  }
}
const successMessage = ref('')

// Filtri leads
const filterText = ref('')
const filterPage = ref('')
const filterStatus = ref('')
const filterAppointment = ref('')
const leadsPerPage = ref(20)
const currentPage = ref(1)

const availablePages = computed(() => {
  const pages = {}
  leads.value.forEach(l => {
    if (l.page) pages[l.page.id] = l.page.title
  })
  return Object.entries(pages).map(([id, title]) => ({ id, title }))
})

const hasActiveFilters = computed(() =>
  filterText.value || filterPage.value || filterStatus.value || filterAppointment.value
)

const filteredLeads = computed(() => {
  let result = leads.value
  if (filterText.value) {
    const q = filterText.value.toLowerCase()
    result = result.filter(l =>
      (l.name || '').toLowerCase().includes(q) ||
      (l.email || '').toLowerCase().includes(q) ||
      (l.phone || '').toLowerCase().includes(q)
    )
  }
  if (filterPage.value) {
    result = result.filter(l => l.page?.id == filterPage.value)
  }
  if (filterStatus.value === 'published') {
    result = result.filter(l => l.page?.is_published)
  } else if (filterStatus.value === 'unpublished') {
    result = result.filter(l => !l.page?.is_published)
  }
  if (filterAppointment.value === 'with') {
    result = result.filter(l => l.metadata?._appointment?.date)
  } else if (filterAppointment.value === 'without') {
    result = result.filter(l => !l.metadata?._appointment?.date)
  }
  return result
})

const totalPages = computed(() => Math.ceil(filteredLeads.value.length / leadsPerPage.value))

const paginatedLeads = computed(() => {
  const start = (currentPage.value - 1) * leadsPerPage.value
  return filteredLeads.value.slice(start, start + leadsPerPage.value)
})

const resetFilters = () => {
  filterText.value = ''
  filterPage.value = ''
  filterStatus.value = ''
  filterAppointment.value = ''
  currentPage.value = 1
}

watch([filterText, filterPage, filterStatus, filterAppointment], () => {
  currentPage.value = 1
})

// Polling leads
const LEADS_POLL_INTERVAL = 5000
let leadsPollingInterval = null

const refreshLeadsSilently = async () => {
  try {
    const leadsData = await pageStore.fetchLeads()
    leads.value = leadsData || []
  } catch (error) {
    console.error('Error polling leads:', error)
  }
}

const startLeadsPolling = () => {
  stopLeadsPolling()
  leadsPollingInterval = setInterval(refreshLeadsSilently, LEADS_POLL_INTERVAL)
}

const stopLeadsPolling = () => {
  if (leadsPollingInterval) {
    clearInterval(leadsPollingInterval)
    leadsPollingInterval = null
  }
}

onUnmounted(() => stopLeadsPolling())

// Watch activeTab per ricaricare i leads quando si clicca sul tab
watch(activeTab, async (newTab) => {
  if (newTab === 'leads') {
    loading.value = true
    try {
      const leadsData = await pageStore.fetchLeads()
      leads.value = leadsData || []
    } catch (error) {
      console.error('Error loading leads:', error)
      leads.value = []
    } finally {
      loading.value = false
    }
    startLeadsPolling()
  } else {
    stopLeadsPolling()
  }
})

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
    const [usersData, companiesData, leadsData] = await Promise.all([
      authStore.fetchUsers(),
      authStore.fetchCompanies(),
      pageStore.fetchLeads().catch(err => {
        console.error('Error fetching leads:', err)
        return []
      })
    ])
    users.value = usersData
    companies.value = companiesData
    leads.value = leadsData || []
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

// Leads management
const handleDeleteLead = async (leadId) => {
  if (!confirm('Sei sicuro di voler eliminare questo lead?')) return

  try {
    await pageStore.deleteLead(leadId)
    successMessage.value = 'Lead eliminato con successo'
    setTimeout(() => successMessage.value = '', 3000)
    await loadData()
  } catch (error) {
    console.error('Error deleting lead:', error)
  }
}

const showLeadDetails = (lead) => {
  selectedLead.value = lead
  editingAppointment.value = false
  const existing = lead.metadata?._appointment
  appointmentForm.value = {
    date: existing?.date || '',
    time: existing?.time || '',
    notes: existing?.notes || ''
  }
}

const formatDate = (dateString) => {
  const date = new Date(dateString)
  return new Intl.DateTimeFormat('it-IT', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  }).format(date)
}

const exportLeads = () => {
  const EXCLUDE_META_KEYS = ['privacy_accepted', 'recaptcha_token', '_notes']

  // Raccogli tutte le chiavi metadata uniche tra i lead filtrati
  const metaKeys = []
  filteredLeads.value.forEach(lead => {
    if (lead.metadata && typeof lead.metadata === 'object') {
      Object.keys(lead.metadata).forEach(key => {
        if (!EXCLUDE_META_KEYS.includes(key) && !metaKeys.includes(key)) {
          metaKeys.push(key)
        }
      })
    }
  })

  const metaHeaders = metaKeys.map(k => k.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase()))

  const headers = ['Data', 'Nome', 'Email', 'Telefono', 'Messaggio', 'Pagina', ...metaHeaders, 'Note']

  const rows = filteredLeads.value.map(lead => {
    const meta = lead.metadata && typeof lead.metadata === 'object' ? lead.metadata : {}
    const notes = (meta._notes || []).map(n => `[${formatDate(n.timestamp)}] ${n.text}`).join(' | ')
    return [
      formatDate(lead.created_at),
      lead.name || '',
      lead.email,
      lead.phone || '',
      lead.message || '',
      lead.page?.title || '',
      ...metaKeys.map(k => meta[k] ?? ''),
      notes
    ]
  })

  const csvContent = [
    headers.join(','),
    ...rows.map(row => row.map(cell => `"${String(cell).replace(/"/g, '""')}"`).join(','))
  ].join('\n')

  const blob = new Blob(['\uFEFF' + csvContent], { type: 'text/csv;charset=utf-8;' })
  const link = document.createElement('a')
  const url = URL.createObjectURL(blob)
  link.setAttribute('href', url)
  link.setAttribute('download', `leads_${new Date().toISOString().split('T')[0]}.csv`)
  link.style.visibility = 'hidden'
  document.body.appendChild(link)
  link.click()
  document.body.removeChild(link)
}
</script>
