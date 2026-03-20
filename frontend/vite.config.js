import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'

export default defineConfig(({ mode }) => ({
  plugins: [vue()],
  base: '/',
  server: {
    port: 3000,
    proxy: {
      // Proxy per le pagine legali
      '/legal': {
        target: 'http://localhost:8000',
        changeOrigin: true
      }
    }
  },
  build: {
    outDir: 'dist',
    assetsDir: 'assets',
    rollupOptions: {
      output: {
        manualChunks(id) {
          // Separa Vue core, router e pinia insieme per evitare problemi di dipendenze
          if (id.includes('node_modules/vue/') ||
              id.includes('node_modules/vue-router/') ||
              id.includes('node_modules/pinia/') ||
              id.includes('node_modules/@vue/')) {
            return 'vue'
          }
          // Separa axios
          if (id.includes('node_modules/axios/')) {
            return 'axios'
          }
          // Separa VueQuill
          if (id.includes('node_modules/@vueup/vue-quill/') || id.includes('node_modules/quill/')) {
            return 'quill'
          }
          // Separa Swiper
          if (id.includes('node_modules/swiper/')) {
            return 'swiper'
          }
          // Tutte le altre dipendenze vendor in un chunk comune (include vuedraggable)
          if (id.includes('node_modules/')) {
            return 'vendor'
          }
        }
      }
    },
    chunkSizeWarningLimit: 600
  }
}))
