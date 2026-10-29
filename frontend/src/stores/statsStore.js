import { defineStore } from 'pinia'
import apiClient from '../api/axios'

/**
 * Statistiche aggregate (le stesse rotte di Grafana, /api/stats/*) per la scheda Statistiche.
 * Il backend filtra per le pagine visibili all'utente: admin tutto, gli altri la propria azienda.
 * Parametri: from/to (AAAA-MM-GG o epoch ms), interval (hour/day/week/month), group, limit.
 */
export const useStatsStore = defineStore('stats', {
  actions: {
    async fetchSummary(params) {
      const response = await apiClient.get('/stats/summary', { params })
      return response.data
    },

    /** [{time, series, series_id, leads}] con gli zeri per i periodi senza lead */
    async fetchLeads(params) {
      const response = await apiClient.get('/stats/leads', { params })
      return response.data
    },

    /** [{time, series, series_id, views, visitors}] */
    async fetchViews(params) {
      const response = await apiClient.get('/stats/views', { params })
      return response.data
    },

    /** [{series, series_id, leads, leads_tracked, views, visitors, conversion_pct}] */
    async fetchConversion(params) {
      const response = await apiClient.get('/stats/conversion', { params })
      return response.data
    }
  }
})
