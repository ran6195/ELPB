import {
  defineStore
} from 'pinia'
import apiClient from '../api/axios'

export const usePageStore = defineStore('page', {
  state: () => ({
    pages: [],
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
    }
  }
})