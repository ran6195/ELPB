import { defineStore } from 'pinia'
import apiClient from '../api/axios'
import { readVideoInfo } from '../utils/media'

/**
 * Libreria media del cliente. Le liste sono gestite dai singoli componenti
 * (pagina libreria e picker hanno filtri diversi): lo store espone le azioni API.
 */
export const useMediaStore = defineStore('media', {
  actions: {
    /**
     * @param {Object} params type, search, company_id, archived, page, per_page
     * @returns {Promise<{data: Array, total: number, page: number, per_page: number, last_page: number}>}
     */
    async fetchMedia(params = {}) {
      const query = Object.fromEntries(
        Object.entries(params).filter(([, v]) => v !== '' && v !== null && v !== undefined && v !== false)
      )
      const response = await apiClient.get('/media', { params: query })
      return response.data
    },

    /**
     * Carica un file nella libreria. Per i video cattura anche una miniatura
     * e le dimensioni nel browser.
     * @param {File} file
     * @param {(percent: number) => void} onProgress
     * @param {number|null} folderId cartella di destinazione (null = radice)
     */
    async upload(file, onProgress = null, folderId = null) {
      const formData = new FormData()
      formData.append('file', file)
      if (folderId) formData.append('folder_id', folderId)

      if (file.type.startsWith('video/')) {
        const info = await readVideoInfo(file)
        if (info.poster) formData.append('poster', info.poster, 'poster.jpg')
        if (info.width) formData.append('width', info.width)
        if (info.height) formData.append('height', info.height)
        if (info.duration) formData.append('duration', info.duration)
      }

      const response = await apiClient.post('/media', formData, {
        onUploadProgress: (e) => {
          if (onProgress && e.total) onProgress(Math.round((e.loaded / e.total) * 100))
        }
      })
      return response.data.data
    },

    async fetchOne(id) {
      const response = await apiClient.get(`/media/${id}`)
      return response.data.data
    },

    /** Contenuto del file come Blob, servito dall'API (evita il blocco CORS sul canvas) */
    async fetchFileBlob(id) {
      const response = await apiClient.get(`/media/${id}/file`, { responseType: 'blob' })
      return response.data
    },

    /**
     * Salva un'immagine modificata come nuova versione di `parentId` (l'originale resta intatto).
     * @param {Blob} blob
     * @param {string} name nome del nuovo file
     */
    async saveVersion(parentId, blob, name, onProgress = null) {
      const formData = new FormData()
      formData.append('file', blob, name)
      formData.append('name', name)
      const response = await apiClient.post(`/media/${parentId}/versions`, formData, {
        onUploadProgress: (e) => {
          if (onProgress && e.total) onProgress(Math.round((e.loaded / e.total) * 100))
        }
      })
      return response.data.data
    },

    async update(id, data) {
      const response = await apiClient.put(`/media/${id}`, data)
      return response.data.data
    },

    async fetchUsage(id) {
      const response = await apiClient.get(`/media/${id}/usage`)
      return response.data.data
    },

    /**
     * Archivia un media. Se è usato in qualche pagina e confirm=false
     * il backend risponde 409: ritorna { inUse: true, usage } senza archiviare.
     */
    async archive(id, confirm = false) {
      try {
        await apiClient.delete(`/media/${id}`, { params: confirm ? { confirm: 1 } : {} })
        return { inUse: false }
      } catch (error) {
        if (error.response?.status === 409) {
          return { inUse: true, usage: error.response.data.usage || [] }
        }
        throw error
      }
    },

    /** Sposta file in una cartella (null = radice). Ritorna { moved, skipped } */
    async moveMedia(ids, folderId) {
      const response = await apiClient.post('/media/move', { ids, folder_id: folderId })
      return response.data
    },

    // ===== Cartelle (solo logiche: i file non si spostano su disco) =====

    async fetchFolders(params = {}) {
      const query = Object.fromEntries(Object.entries(params).filter(([, v]) => v !== '' && v != null))
      const response = await apiClient.get('/media-folders', { params: query })
      return response.data.data
    },

    async createFolder(data) {
      const response = await apiClient.post('/media-folders', data)
      return response.data.data
    },

    async updateFolder(id, data) {
      const response = await apiClient.put(`/media-folders/${id}`, data)
      return response.data.data
    },

    async deleteFolder(id) {
      const response = await apiClient.delete(`/media-folders/${id}`)
      return response.data
    },

    async restore(id) {
      await apiClient.post(`/media/${id}/restore`)
    },

    async forceDelete(id) {
      await apiClient.delete(`/media/${id}/force`)
    }
  }
})

/** Messaggio di errore leggibile da una risposta axios */
export function mediaErrorMessage(error, fallback = 'Operazione non riuscita') {
  return error?.response?.data?.error || error?.message || fallback
}
