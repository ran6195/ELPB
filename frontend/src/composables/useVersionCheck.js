import { ref, onMounted, onUnmounted } from 'vue'

export function useVersionCheck() {
  const currentVersion = ref('')
  const updateAvailable = ref(false)
  let initialBuildTime = null
  let intervalId = null

  async function checkVersion() {
    try {
      const res = await fetch('/version.json?t=' + Date.now(), { cache: 'no-store' })
      if (!res.ok) return
      const data = await res.json()

      if (initialBuildTime === null) {
        // Prima fetch: memorizza il buildTime corrente e mostra la versione
        initialBuildTime = data.buildTime
        currentVersion.value = data.version || ''
      } else if (data.buildTime !== initialBuildTime) {
        // buildTime cambiato: è stato deployato un nuovo build
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

  return { currentVersion, updateAvailable, reload }
}
