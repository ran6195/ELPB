import { ref } from 'vue'
import pkg from '../../package.json'

const bundleVersion = pkg.version
const updateAvailable = ref(false)

async function checkVersion() {
  try {
    const res = await fetch('/version.json?t=' + Date.now(), { cache: 'no-store' })
    if (!res.ok) return
    const { version } = await res.json()
    if (version && version !== bundleVersion) {
      updateAvailable.value = true
    }
  } catch (_) {}
}

// Avvia immediatamente al caricamento del modulo — nessuna dipendenza
// dal lifecycle dei componenti Vue, garantisce persistenza totale
checkVersion()
setInterval(checkVersion, 5 * 60 * 1000)
window.addEventListener('focus', checkVersion)

// Cleanup listener in dev mode (Vite HMR)
if (import.meta.hot) {
  import.meta.hot.dispose(() => {
    window.removeEventListener('focus', checkVersion)
  })
}

export function useVersionCheck() {
  return {
    updateAvailable,
    reload: () => window.location.reload()
  }
}
