import { createApp } from 'vue'
import { createPinia } from 'pinia'
import router from './router'
import App from './App.vue'
import './style.css'
import { useAuthStore } from './stores/authStore'

const app = createApp(App)
const pinia = createPinia()

app.use(pinia)

// Inizializza authStore (ripristina sessione da localStorage)
const authStore = useAuthStore()
authStore.init()

app.use(router)
app.mount('#app')
