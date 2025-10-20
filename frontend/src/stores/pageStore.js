import {
  defineStore
} from 'pinia'
import axios from 'axios'

const API_URL = import.meta.env.VITE_API_URL || 'http://localhost:8000/api'

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
        const response = await axios.get(`${API_URL}/pages`)
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
        const response = await axios.get(`${API_URL}/pages/${id}`)
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
        const response = await axios.post(`${API_URL}/pages`, pageData)
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
        const response = await axios.put(`${API_URL}/pages/${id}`, pageData)
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
        await axios.delete(`${API_URL}/pages/${id}`)
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
        const response = await axios.post(`${API_URL}/leads`, leadData)
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
        const response = await axios.put(`${API_URL}/company/pages/${pageId}/reassign`, {
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
    }
  }
})