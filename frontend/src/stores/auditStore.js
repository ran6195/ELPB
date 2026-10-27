import { defineStore } from 'pinia'
import apiClient from '../api/axios'

/**
 * Registro attività (solo admin): elenco, diretta ed export CSV.
 * I filtri sono quelli della query string del backend (azienda, utente, categoria,
 * esito, dal, al, richiesta, ip, senza_consultazioni); quelli vuoti non si inviano.
 */
function cleanFilters(filters) {
  const params = {}
  for (const [key, value] of Object.entries(filters)) {
    if (value !== '' && value !== null && value !== undefined && value !== false) {
      params[key] = value === true ? 1 : value
    }
  }
  return params
}

export const useAuditStore = defineStore('audit', {
  actions: {
    async fetchLogs(filters, page = 1) {
      const response = await apiClient.get('/admin/audit-logs', { params: { ...cleanFilters(filters), pagina: page } })
      return response.data
    },

    /** Voci successive a afterId (diretta) */
    async fetchNewer(filters, afterId) {
      const response = await apiClient.get('/admin/audit-logs/new', { params: { ...cleanFilters(filters), dopo: afterId } })
      return response.data
    },

    async exportCsv(filters) {
      const response = await apiClient.get('/admin/audit-logs/export', {
        params: cleanFilters(filters),
        responseType: 'blob'
      })
      const disposition = response.headers['content-disposition'] || ''
      const match = disposition.match(/filename="?([^";]+)"?/i)
      const url = window.URL.createObjectURL(new Blob([response.data], { type: 'text/csv;charset=utf-8' }))
      const link = document.createElement('a')
      link.href = url
      link.setAttribute('download', match ? match[1] : 'registro-attivita.csv')
      document.body.appendChild(link)
      link.click()
      link.remove()
      window.URL.revokeObjectURL(url)
    }
  }
})
