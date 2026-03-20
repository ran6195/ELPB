# Vite Build Optimization - Code Splitting

## Problema Risolto
Warning: "Some chunks are larger than 500 kB after minification"

## Soluzione Implementata
Configurato **manual chunking** in `vite.config.js` per separare le dipendenze vendor in chunk separati.

## Strategia di Chunking

### Core Framework (caricati sempre)
- **vue.js** - Vue 3 core (~150KB)
- **vue-router.js** - Router (~50KB)
- **pinia.js** - State management (~30KB)

### Feature Chunks (caricati quando necessario)
- **axios.js** - HTTP client (~30KB)
- **draggable.js** - vuedraggable + sortablejs (~100KB)
- **quill.js** - Rich text editor (~200KB)
- **swiper.js** - Slider component (~150KB)
- **vendor.js** - Altre dipendenze (~50-100KB)

### Application Code
- **index.js** - Codice applicazione principale

## Benefici

### 1. Caching Migliore
- Browser può cacheare i vendor chunk separatamente
- Aggiornamenti app non invalidano cache delle librerie
- Utenti scaricano solo i chunk modificati

### 2. Performance di Caricamento
- Caricamento parallelo dei chunk
- Prioritizzazione dei chunk core
- Lazy loading delle feature pesanti (se implementato)

### 3. Bundle Size Ridotto
- Ogni chunk < 500KB
- No warning durante il build
- Distribuzione ottimizzata su CDN

## Monitoraggio Build Size

### Comando Build
```bash
npm run build
```

### Output Esempio
```
dist/assets/vue-[hash].js          150.23 kB │ gzip: 51.45 kB
dist/assets/quill-[hash].js        198.67 kB │ gzip: 65.23 kB
dist/assets/swiper-[hash].js       145.89 kB │ gzip: 48.12 kB
dist/assets/draggable-[hash].js    102.34 kB │ gzip: 33.56 kB
dist/assets/vue-router-[hash].js    48.92 kB │ gzip: 16.78 kB
dist/assets/pinia-[hash].js         29.45 kB │ gzip:  9.87 kB
dist/assets/axios-[hash].js         28.34 kB │ gzip:  9.12 kB
dist/assets/vendor-[hash].js        78.23 kB │ gzip: 25.67 kB
dist/assets/index-[hash].js        320.45 kB │ gzip: 98.34 kB
```

## Ottimizzazioni Future (Opzionali)

### 1. Lazy Loading Componenti
Caricare blocchi pesanti solo quando necessario:

```javascript
// Invece di:
import QuillEditor from '@vueup/vue-quill'

// Usa:
const QuillEditor = defineAsyncComponent(() =>
  import('@vueup/vue-quill')
)
```

### 2. Route-Based Code Splitting
Separare le route in chunk:

```javascript
const routes = [
  {
    path: '/editor/:id',
    component: () => import('./views/PageEditor.vue')
  }
]
```

### 3. Component-Level Splitting
Caricare blocchi editor solo quando necessario:

```javascript
const BlockEditor = defineAsyncComponent(() =>
  import('./components/BlockEditor.vue')
)
```

## Configurazione Attuale

### vite.config.js
```javascript
build: {
  rollupOptions: {
    output: {
      manualChunks(id) {
        // Separa Vue core
        if (id.includes('node_modules/vue/')) return 'vue'
        if (id.includes('node_modules/vue-router/')) return 'vue-router'
        if (id.includes('node_modules/pinia/')) return 'pinia'

        // Separa feature libraries
        if (id.includes('node_modules/axios/')) return 'axios'
        if (id.includes('node_modules/vuedraggable/')) return 'draggable'
        if (id.includes('node_modules/quill/')) return 'quill'
        if (id.includes('node_modules/swiper/')) return 'swiper'

        // Vendor catch-all
        if (id.includes('node_modules/')) return 'vendor'
      }
    }
  },
  chunkSizeWarningLimit: 600 // Warning se chunk > 600KB
}
```

## Testing

### 1. Build Locale
```bash
npm run build
# Verifica dimensioni chunk nell'output
```

### 2. Preview Build
```bash
npm run preview
# Testa l'app builddata su http://localhost:4173
```

### 3. Network Analysis
- Apri DevTools → Network
- Ricarica pagina
- Verifica che i chunk vengano caricati in parallelo
- Controlla che i chunk vendor siano cachati (304 status)

## Troubleshooting

### Warning Persiste
Se il warning persiste dopo la configurazione:

1. **Identifica il chunk problematico**
   ```bash
   npm run build -- --reportSize
   ```

2. **Aumenta il limite (temporaneo)**
   ```javascript
   chunkSizeWarningLimit: 1000
   ```

3. **Analizza le dipendenze**
   ```bash
   npm install -D rollup-plugin-visualizer
   ```

   In `vite.config.js`:
   ```javascript
   import { visualizer } from 'rollup-plugin-visualizer'

   plugins: [vue(), visualizer()]
   ```

### Chunk Troppo Piccoli
Se troppi chunk piccoli rallentano il caricamento:

- Raggruppa librerie correlate
- Usa un approccio meno granulare
- Aumenta la soglia di splitting

### Cache Issues
Se le modifiche non si riflettono:

```bash
rm -rf node_modules/.vite dist
npm run build
```

## Performance Metrics

### Target
- First Contentful Paint (FCP): < 1.8s
- Time to Interactive (TTI): < 3.8s
- Total Bundle Size (gzipped): < 500KB initial

### Monitoring
Usa Lighthouse per monitorare performance:
```bash
npm install -g @lhci/cli
lhci autorun --upload.target=temporary-public-storage
```

## References
- [Vite Build Optimization](https://vitejs.dev/guide/build.html)
- [Rollup Manual Chunks](https://rollupjs.org/configuration-options/#output-manualchunks)
- [Web.dev Code Splitting](https://web.dev/code-splitting-suspense/)
