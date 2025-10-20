import { defineStore } from 'pinia'
import axios from 'axios'

const API_URL = import.meta.env.VITE_API_URL || 'http://localhost:8000/api'

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: null,
    token: null,
    loading: false,
    error: null
  }),

  getters: {
    isAuthenticated: (state) => !!state.token,
    isAdmin: (state) => state.user?.role === 'admin',
    isCompany: (state) => state.user?.role === 'company',
    isUser: (state) => state.user?.role === 'user',
    userRole: (state) => state.user?.role || null
  },

  actions: {
    // Inizializza lo store dal localStorage
    init() {
      const token = localStorage.getItem('token')
      const user = localStorage.getItem('user')

      if (token && user) {
        this.token = token
        this.user = JSON.parse(user)
        // Imposta l'header di default per axios
        axios.defaults.headers.common['Authorization'] = `Bearer ${token}`
      }
    },

    // Login
    async login(email, password) {
      this.loading = true
      this.error = null

      try {
        const response = await axios.post(`${API_URL}/auth/login`, {
          email,
          password
        })

        if (response.data.success) {
          this.token = response.data.token
          this.user = response.data.user

          // Salva in localStorage
          localStorage.setItem('token', this.token)
          localStorage.setItem('user', JSON.stringify(this.user))

          // Imposta l'header di default per axios
          axios.defaults.headers.common['Authorization'] = `Bearer ${this.token}`

          return true
        } else {
          this.error = response.data.message || 'Login fallito'
          return false
        }
      } catch (error) {
        this.error = error.response?.data?.message || 'Errore durante il login'
        console.error('Login error:', error)
        return false
      } finally {
        this.loading = false
      }
    },

    // Register (solo per admin)
    async register(userData) {
      this.loading = true
      this.error = null

      try {
        const response = await axios.post(`${API_URL}/auth/register`, userData, {
          headers: {
            'Authorization': `Bearer ${this.token}`
          }
        })

        if (response.data.success) {
          return response.data.user
        } else {
          this.error = response.data.message || 'Registrazione fallita'
          return null
        }
      } catch (error) {
        this.error = error.response?.data?.message || 'Errore durante la registrazione'
        console.error('Register error:', error)
        return null
      } finally {
        this.loading = false
      }
    },

    // Get current user
    async fetchCurrentUser() {
      if (!this.token) return false

      this.loading = true
      try {
        const response = await axios.get(`${API_URL}/auth/me`, {
          headers: {
            'Authorization': `Bearer ${this.token}`
          }
        })

        if (response.data.success) {
          this.user = response.data.user
          localStorage.setItem('user', JSON.stringify(this.user))
          return true
        }
        return false
      } catch (error) {
        console.error('Fetch user error:', error)
        // Se il token non è valido, effettua il logout
        if (error.response?.status === 401) {
          this.logout()
        }
        return false
      } finally {
        this.loading = false
      }
    },

    // Logout
    logout() {
      this.token = null
      this.user = null
      this.error = null

      localStorage.removeItem('token')
      localStorage.removeItem('user')

      delete axios.defaults.headers.common['Authorization']
    },

    // Get companies (solo admin)
    async fetchCompanies() {
      try {
        const response = await axios.get(`${API_URL}/auth/companies`, {
          headers: {
            'Authorization': `Bearer ${this.token}`
          }
        })
        return response.data
      } catch (error) {
        console.error('Fetch companies error:', error)
        return []
      }
    },

    // Get users (admin o company manager)
    async fetchUsers() {
      try {
        const response = await axios.get(`${API_URL}/auth/users`, {
          headers: {
            'Authorization': `Bearer ${this.token}`
          }
        })
        return response.data
      } catch (error) {
        console.error('Fetch users error:', error)
        return []
      }
    },

    // Admin: Create company
    async createCompany(companyData) {
      this.loading = true
      this.error = null
      try {
        const response = await axios.post(`${API_URL}/admin/companies`, companyData, {
          headers: {
            'Authorization': `Bearer ${this.token}`
          }
        })
        return response.data
      } catch (error) {
        this.error = error.response?.data?.message || 'Errore durante la creazione dell\'azienda'
        console.error('Create company error:', error)
        throw error
      } finally {
        this.loading = false
      }
    },

    // Admin: Delete company
    async deleteCompany(companyId) {
      this.loading = true
      this.error = null
      try {
        const response = await axios.delete(`${API_URL}/admin/companies/${companyId}`, {
          headers: {
            'Authorization': `Bearer ${this.token}`
          }
        })
        return response.data
      } catch (error) {
        this.error = error.response?.data?.message || 'Errore durante l\'eliminazione dell\'azienda'
        console.error('Delete company error:', error)
        throw error
      } finally {
        this.loading = false
      }
    },

    // Admin: Update user
    async updateUser(userId, userData) {
      this.loading = true
      this.error = null
      try {
        const response = await axios.put(`${API_URL}/admin/users/${userId}`, userData, {
          headers: {
            'Authorization': `Bearer ${this.token}`
          }
        })
        return response.data
      } catch (error) {
        this.error = error.response?.data?.message || 'Errore durante l\'aggiornamento dell\'utente'
        console.error('Update user error:', error)
        throw error
      } finally {
        this.loading = false
      }
    },

    // Admin: Delete user
    async deleteUser(userId) {
      this.loading = true
      this.error = null
      try {
        const response = await axios.delete(`${API_URL}/admin/users/${userId}`, {
          headers: {
            'Authorization': `Bearer ${this.token}`
          }
        })
        return response.data
      } catch (error) {
        this.error = error.response?.data?.message || 'Errore durante l\'eliminazione dell\'utente'
        console.error('Delete user error:', error)
        throw error
      } finally {
        this.loading = false
      }
    },

    // Company: Create user in company
    async createUserInCompany(userData) {
      this.loading = true
      this.error = null
      try {
        const response = await axios.post(`${API_URL}/company/users`, userData, {
          headers: {
            'Authorization': `Bearer ${this.token}`
          }
        })
        return response.data
      } catch (error) {
        this.error = error.response?.data?.message || 'Errore durante la creazione dell\'utente'
        console.error('Create user in company error:', error)
        throw error
      } finally {
        this.loading = false
      }
    },

    // Company: Delete user from company
    async deleteUserFromCompany(userId) {
      this.loading = true
      this.error = null
      try {
        const response = await axios.delete(`${API_URL}/company/users/${userId}`, {
          headers: {
            'Authorization': `Bearer ${this.token}`
          }
        })
        return response.data
      } catch (error) {
        this.error = error.response?.data?.message || 'Errore durante l\'eliminazione dell\'utente'
        console.error('Delete user from company error:', error)
        throw error
      } finally {
        this.loading = false
      }
    },

    // Company: Get users with pages count
    async getUsersWithPagesCount() {
      try {
        const response = await axios.get(`${API_URL}/company/users-with-pages`, {
          headers: {
            'Authorization': `Bearer ${this.token}`
          }
        })
        return response.data
      } catch (error) {
        console.error('Get users with pages count error:', error)
        return []
      }
    }
  }
})
