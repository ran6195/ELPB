import axios from 'axios'

// Crea un'istanza axios centralizzata
const apiClient = axios.create({
  baseURL: import.meta.env.VITE_API_URL || 'http://localhost:8000/api',
  headers: {
    'Content-Type': 'application/json'
  }
})

// Interceptor per aggiungere automaticamente il token ad ogni richiesta
apiClient.interceptors.request.use(
  (config) => {
    // Leggi il token da localStorage
    const token = localStorage.getItem('token')

    // Se il token esiste, aggiungilo all'header Authorization
    if (token) {
      config.headers.Authorization = `Bearer ${token}`
      console.log('[Axios] Token inviato:', token.substring(0, 20) + '...')
    } else {
      console.log('[Axios] Nessun token trovato in localStorage')
    }

    // Per FormData (upload file) rimuovi il Content-Type fisso
    // così il browser imposta automaticamente multipart/form-data con boundary
    if (config.data instanceof FormData) {
      delete config.headers['Content-Type']
    }

    console.log('[Axios] Richiesta:', config.method.toUpperCase(), config.url)

    return config
  },
  (error) => {
    return Promise.reject(error)
  }
)

// Interceptor per gestire errori 401 (token scaduto/invalido)
apiClient.interceptors.response.use(
  (response) => {
    console.log('[Axios] Risposta OK:', response.config.method.toUpperCase(), response.config.url)
    return response
  },
  (error) => {
    console.error('[Axios] Errore:', error.response?.status, error.config?.url, error.response?.data)

    if (error.response?.status === 401) {
      const currentPath = window.location.pathname
      console.log('[Axios] 401 ricevuto, path corrente:', currentPath)

      // Se non siamo già nella pagina di login, pulisci e ricarica
      if (currentPath !== '/login' && currentPath !== '/register') {
        console.log('[Axios] Pulisco localStorage e ricarico...')
        // Token scaduto o invalido, pulisci localStorage
        localStorage.removeItem('token')
        localStorage.removeItem('user')

        // Ricarica la pagina per reinizializzare lo state
        // Il router guard si accorgerà che non c'è il token e reindirizzerà a /login
        window.location.reload()
      }
    }
    return Promise.reject(error)
  }
)

export default apiClient
