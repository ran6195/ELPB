import { ref, onMounted, onUnmounted } from 'vue'
import pkg from '../../package.json'

// Versione del bundle correntemente caricato nel browser
const bundleVersion = pkg.version

export function useVersionCheck() {
  const updateAvailable = ref(false)
  let intervalId = null

  async function checkVersion() {
    try {
      const res = await fetch('/version.json?t=' + Date.now(), { cache: 'no-store' })
      if (!res.ok) return
      const data = await res.json()
      // Se il server ha una versione diversa da quella del bundle caricato → aggiornamento disponibile
      if (data.version && data.version !== bundleVersion) {
        updateAvailable.value = true
      }
    } catch (_) {}
  }

  function reload() {
    window.location.reload()
  }

  onMounted(() => {
    checkVersion()
    intervalId = setInterval(checkVersion, 5 * 60 * 1000)
    window.addEventListener('focus', checkVersion)
  })

  onUnmounted(() => {
    clearInterval(intervalId)
    window.removeEventListener('focus', checkVersion)
  })

  return { updateAvailable, reload }
}
