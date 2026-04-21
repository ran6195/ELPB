import {
  defineStore
} from 'pinia'
import apiClient from '../api/axios'

export const usePageStore = defineStore('page', {
  state: () => ({
    pages: [],
    archivedPages: [],
    currentPage: null,
    loading: false,
    error: null
  }),

  actions: {
    async fetchPages() {
      this.loading = true
      this.error = null
      try {
        const response = await apiClient.get('/pages')
        this.pages = response.data
      } catch (error) {
        this.error = error.message
        console.error('Error fetching pages:', error)
      } finally {
        this.loading = false
      }
    },

    async fetchPage(id) {
      this.loading = true
      this.error = null
      try {
        const response = await apiClient.get(`/pages/${id}`)
        this.currentPage = response.data
        return response.data
      } catch (error) {
        this.error = error.message
        console.error('Error fetching page:', error)
      } finally {
        this.loading = false
      }
    },

    async createPage(pageData) {
      this.loading = true
      this.error = null
      try {
        const response = await apiClient.post('/pages', pageData)
        this.pages.unshift(response.data)
        return response.data
      } catch (error) {
        this.error = error.message
        console.error('Error creating page:', error)
        throw error
      } finally {
        this.loading = false
      }
    },

    async updatePage(id, pageData) {
      this.loading = true
      this.error = null
      try {
        const response = await apiClient.put(`/pages/${id}`, pageData)
        const index = this.pages.findIndex(p => p.id === id)
        if (index !== -1) {
          this.pages[index] = response.data
        }
        this.currentPage = response.data
        return response.data
      } catch (error) {
        this.error = error.message
        console.error('Error updating page:', error)
        throw error
      } finally {
        this.loading = false
      }
    },

    async deletePage(id) {
      this.loading = true
      this.error = null
      try {
        await apiClient.delete(`/pages/${id}`)
        this.pages = this.pages.filter(p => p.id !== id)
      } catch (error) {
        this.error = error.message
        console.error('Error deleting page:', error)
        throw error
      } finally {
        this.loading = false
      }
    },

    async duplicatePage(id) {
      this.loading = true
      this.error = null
      try {
        const response = await apiClient.post(`/pages/${id}/duplicate`)
        // Aggiungi la nuova pagina in cima alla lista
        this.pages.unshift(response.data)
        return response.data
      } catch (error) {
        this.error = error.message
        console.error('Error duplicating page:', error)
        throw error
      } finally {
        this.loading = false
      }
    },

    async submitLead(leadData) {
      try {
        const response = await apiClient.post('/leads', leadData)
        return response.data
      } catch (error) {
        console.error('Error submitting lead:', error)
        throw error
      }
    },

    async reassignPage(pageId, userId) {
      this.loading = true
      this.error = null
      try {
        const response = await apiClient.put(`/company/pages/${pageId}/reassign`, {
          user_id: userId
        })
        return response.data
      } catch (error) {
        this.error = error.message
        console.error('Error reassigning page:', error)
        throw error
      } finally {
        this.loading = false
      }
    },

    async fetchLeads() {
      this.loading = true
      this.error = null
      try {
        const response = await apiClient.get('/leads')
        return response.data
      } catch (error) {
        this.error = error.message
        console.error('Error fetching leads:', error)
        throw error
      } finally {
        this.loading = false
      }
    },

    async addLeadNote(leadId, note) {
      const response = await apiClient.post(`/leads/${leadId}/notes`, { note })
      return response.data
    },

    async updateLeadAppointment(leadId, appointment) {
      const response = await apiClient.put(`/leads/${leadId}/appointment`, appointment)
      return response.data
    },

    async deleteLead(leadId) {
      this.loading = true
      this.error = null
      try {
        await apiClient.delete(`/leads/${leadId}`)
      } catch (error) {
        this.error = error.message
        console.error('Error deleting lead:', error)
        throw error
      } finally {
        this.loading = false
      }
    },

    async fetchArchivedPages() {
      this.loading = true
      this.error = null
      try {
        const response = await apiClient.get('/pages/archived')
        this.archivedPages = response.data
      } catch (error) {
        this.error = error.message
        console.error('Error fetching archived pages:', error)
        throw error
      } finally {
        this.loading = false
      }
    },

    async restorePage(id) {
      this.loading = true
      this.error = null
      try {
        const response = await apiClient.post(`/pages/${id}/restore`)
        // Rimuovi dalla lista archiviate
        this.archivedPages = this.archivedPages.filter(p => p.id !== id)
        // Aggiungi alle pagine attive
        this.pages.unshift(response.data.page)
        return response.data
      } catch (error) {
        this.error = error.message
        console.error('Error restoring page:', error)
        throw error
      } finally {
        this.loading = false
      }
    },

    async checkSlug(slug, pageId = null) {
      try {
        const response = await apiClient.post('/pages/check-slug', {
          slug,
          page_id: pageId
        })
        return response.data
      } catch (error) {
        // Estrai il messaggio di errore dal backend
        const errorMessage = error.response?.data?.message || 'Errore nella verifica dello slug'

        return {
          available: false,
          message: errorMessage
        }
      }
    },

    async forceDeletePage(id) {
      this.loading = true
      this.error = null
      try {
        await apiClient.delete(`/pages/${id}/force`)
        // Rimuovi dalla lista archiviate
        this.archivedPages = this.archivedPages.filter(p => p.id !== id)
      } catch (error) {
        this.error = error.message
        console.error('Error force deleting page:', error)
        throw error
      } finally {
        this.loading = false
      }
    },

    async exportPage(id) {
      this.loading = true
      this.error = null
      try {
        const response = await apiClient.get(`/pages/${id}/export`, {
          responseType: 'blob'
        })

        // Crea un link per il download
        const url = window.URL.createObjectURL(new Blob([response.data]))
        const link = document.createElement('a')
        link.href = url

        // Estrai il nome file dall'header Content-Disposition
        // Axios normalizza gli headers in lowercase
        const contentDisposition = response.headers['content-disposition'] || response.headers['Content-Disposition']
        let filename = 'page-export.json'

        if (contentDisposition) {
          // Prova diversi pattern per estrarre il filename
          // Pattern 1: filename="nome.json"
          let filenameMatch = contentDisposition.match(/filename[^;=\n]*=["']?([^"';]+)["']?/i)
          if (filenameMatch && filenameMatch[1]) {
            filename = filenameMatch[1].trim()
          } else {
            // Pattern 2: filename*=UTF-8''nome.json (RFC 5987)
            filenameMatch = contentDisposition.match(/filename\*=UTF-8''([^;]+)/i)
            if (filenameMatch && filenameMatch[1]) {
              filename = decodeURIComponent(filenameMatch[1])
            }
          }
        }

        link.setAttribute('download', filename)
        document.body.appendChild(link)
        link.click()
        link.remove()
        window.URL.revokeObjectURL(url)

        return true
      } catch (error) {
        this.error = error.message
        console.error('Error exporting page:', error)
        throw error
      } finally {
        this.loading = false
      }
    },

    async importPage(jsonData) {
      this.loading = true
      this.error = null
      try {
        const response = await apiClient.post('/pages/import', jsonData)
        // Aggiungi la nuova pagina importata in cima alla lista
        this.pages.unshift(response.data.page)
        return response.data
      } catch (error) {
        this.error = error.message
        console.error('Error importing page:', error)
        throw error
      } finally {
        this.loading = false
      }
    },

    async updateLegalInfo(pageId, legalInfo) {
      this.loading = true
      this.error = null
      try {
        const response = await apiClient.post(`/pages/${pageId}/legal-info`, legalInfo)

        // Aggiorna la pagina nella lista locale
        const index = this.pages.findIndex(p => p.id === pageId)
        if (index !== -1) {
          this.pages[index] = response.data.page
        }

        // Aggiorna anche currentPage se è quella modificata
        if (this.currentPage && this.currentPage.id === pageId) {
          this.currentPage = response.data.page
        }

        return response.data
      } catch (error) {
        this.error = error.message
        console.error('Error updating legal info:', error)
        throw error
      } finally {
        this.loading = false
      }
    },

    async updateNotificationSettings(pageId, notificationSettings) {
      this.loading = true
      this.error = null
      try {
        const response = await apiClient.post(`/pages/${pageId}/notification-settings`, notificationSettings)

        // Aggiorna la pagina nella lista locale
        const index = this.pages.findIndex(p => p.id === pageId)
        if (index !== -1) {
          this.pages[index] = response.data.page
        }

        // Aggiorna anche currentPage se è quella modificata
        if (this.currentPage && this.currentPage.id === pageId) {
          this.currentPage = response.data.page
        }

        return response.data
      } catch (error) {
        this.error = error.message
        console.error('Error updating notification settings:', error)
        throw error
      } finally {
        this.loading = false
      }
    }
  }
})