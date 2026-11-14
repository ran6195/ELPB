import { defineStore } from 'pinia'
import apiClient from '../api/axios'

/**
 * Storico della pubblicazione (solo admin): periodi online di ogni pagina per azienda,
 * base della fatturazione per pagina pubblicata.
 */
export const usePublicationStore = defineStore('publications', {
  actions: {
    /** { data: [{page, periods}], companies: {id: nome}, now } */
    async fetchPublications() {
      const response = await apiClient.get('/admin/publications')
      return response.data
    }
  }
})
