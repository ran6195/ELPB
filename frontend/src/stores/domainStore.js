import { defineStore } from 'pinia'
import apiClient from '../api/axios'
import { usePageStore } from './pageStore'

/**
 * Anagrafica dei domini (siti dei clienti) e associazione alle landing page.
 * L'associazione è facoltativa: una pagina senza domini funziona come sempre.
 */
export const useDomainStore = defineStore('domain', {
  state: () => ({
    domains: [],
    loaded: false
  }),

  actions: {
    async fetchDomains(force = false) {
      if (this.loaded && !force) return this.domains
      const response = await apiClient.get('/domains')
      this.domains = response.data.data
      this.loaded = true
      return this.domains
    },

    /**
     * @param {{hostname: string, label?: string, page_id?: number}} data
     *   con page_id il dominio prende il proprietario della pagina e le viene associato
     */
    async createDomain(data) {
      const response = await apiClient.post('/domains', data)
      const domain = response.data.data
      this.domains = [...this.domains.filter(d => d.id !== domain.id), domain]
        .sort((a, b) => a.hostname.localeCompare(b.hostname))
      if (data.page_id) {
        const pageStore = usePageStore()
        const page = pageStore.currentPage?.id === data.page_id
          ? pageStore.currentPage
          : pageStore.pages.find(p => p.id === data.page_id)
        const current = (page?.domains || []).filter(d => d.id !== domain.id)
        this._setPageDomains(data.page_id, [...current, domain].sort((a, b) => a.hostname.localeCompare(b.hostname)))
      }
      return domain
    },

    async updateDomain(id, data) {
      const response = await apiClient.post(`/domains/${id}`, data)
      const domain = response.data.data
      this.domains = this.domains.map(d => (d.id === id ? domain : d))
      return domain
    },

    async deleteDomain(id) {
      await apiClient.delete(`/domains/${id}`)
      this.domains = this.domains.filter(d => d.id !== id)
      // Il dominio sparisce anche dalle pagine già caricate
      const pageStore = usePageStore()
      for (const page of [...pageStore.pages, pageStore.currentPage].filter(Boolean)) {
        if (page.domains) page.domains = page.domains.filter(d => d.id !== id)
      }
    },

    /** Sostituisce i domini associati alla pagina; restituisce l'elenco aggiornato */
    async setPageDomains(pageId, domainIds) {
      const response = await apiClient.post(`/pages/${pageId}/domains`, { domain_ids: domainIds })
      this._setPageDomains(pageId, response.data.data)
      // Conteggi pagine/lead dell'anagrafica cambiati
      this.loaded = false
      return response.data.data
    },

    async fetchSuggestions(pageId) {
      const response = await apiClient.get(`/pages/${pageId}/domain-suggestions`)
      return response.data.data
    },

    /** Dove viene mostrata la pagina (registro renderer) e visite per sito degli ultimi 30 giorni */
    async fetchPageRenderers(pageId) {
      const response = await apiClient.get(`/pages/${pageId}/renderers`)
      return response.data.data
    },

    /** Siti e versioni dei renderer delle pagine visibili; current_version è l'ultima distribuita */
    async fetchRenderers() {
      const response = await apiClient.get('/renderers')
      return response.data
    },

    /** Diretta della scheda Renderer: righe con un accesso dal momento indicato */
    async fetchRendererUpdates(after) {
      const response = await apiClient.get('/renderers/new', { params: { dopo: after } })
      return response.data
    },

    /** Aggiorna i domini nella pagina corrente e nella lista dello store pagine */
    _setPageDomains(pageId, domains) {
      if (!domains) return
      const pageStore = usePageStore()
      if (pageStore.currentPage?.id === pageId) pageStore.currentPage.domains = domains
      const page = pageStore.pages.find(p => p.id === pageId)
      if (page) page.domains = domains
    }
  }
})

/** Il dominio appartiene allo stesso proprietario della pagina (stessa regola del backend) */
export function sameOwner(domain, page) {
  if (domain.company_id != null || page.company_id != null) {
    return Number(domain.company_id) === Number(page.company_id)
  }
  return Number(domain.user_id) === Number(page.user_id)
}
