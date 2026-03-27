import { ref, onMounted } from 'vue'
import pkg from '../../package.json'

const bundleVersion = pkg.version

// Stato a livello di modulo: persiste per tutta la sessione browser
// indipendentemente dal ciclo di vita dei componenti
const updateAvailable = ref(false)
let initialized = false

async function checkVersion() {
  try {
    const res = await fetch('/version.json?t=' + Date.now(), { cache: 'no-store' })
    if (!res.ok) return
    const data = await res.json()
    if (data.version && data.version !== bundleVersion) {
      updateAvailable.value = true
    }
  } catch (_) {}
}

export function useVersionCheck() {
  onMounted(() => {
    if (initialized) return
    initialized = true
    checkVersion()
    setInterval(checkVersion, 5 * 60 * 1000)
    window.addEventListener('focus', checkVersion)
  })

  return {
    updateAvailable,
    reload: () => window.location.reload()
  }
}
