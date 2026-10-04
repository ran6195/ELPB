// Utility condivise per la libreria media

export const MAX_UPLOAD_SIZE = 100 * 1024 * 1024 // 100MB (allineato a upload_max_filesize)

export const ACCEPT = {
  image: 'image/jpeg,image/png,image/gif,image/webp,image/avif',
  video: 'video/mp4,video/webm,video/quicktime',
  all: 'image/jpeg,image/png,image/gif,image/webp,image/avif,video/mp4,video/webm,video/quicktime'
}

const ALLOWED_TYPES = ACCEPT.all.split(',')

/**
 * Validazione lato client (il backend riverifica il tipo reale del file).
 * Ritorna un messaggio di errore oppure null.
 */
export function validateFile(file, type = null) {
  if (file.size > MAX_UPLOAD_SIZE) {
    return 'File troppo grande (max 100MB)'
  }
  // Alcuni browser non valorizzano file.type: in quel caso decide il backend
  if (file.type && !ALLOWED_TYPES.includes(file.type)) {
    return 'Formato non supportato. Immagini: JPEG, PNG, GIF, WebP, AVIF. Video: MP4, WebM, MOV.'
  }
  if (type && file.type && !file.type.startsWith(type + '/')) {
    return type === 'image' ? 'Il file non è un\'immagine' : 'Il file non è un video'
  }
  return null
}

export function formatBytes(bytes) {
  if (!bytes && bytes !== 0) return '—'
  if (bytes < 1024) return bytes + ' B'
  if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(0) + ' KB'
  return (bytes / (1024 * 1024)).toFixed(1) + ' MB'
}

export function formatDuration(seconds) {
  if (!seconds) return ''
  const m = Math.floor(seconds / 60)
  const s = Math.round(seconds % 60)
  return `${m}:${String(s).padStart(2, '0')}`
}

/**
 * Legge dimensioni/durata di un video e cattura un fotogramma come miniatura,
 * dato che sul server non c'è ffmpeg. Non fallisce mai: se il browser non
 * riesce a decodificare il video (es. alcuni MOV) ritorna valori nulli.
 */
export function readVideoInfo(file, maxSize = 800) {
  return new Promise((resolve) => {
    const empty = { poster: null, width: null, height: null, duration: null }
    const url = URL.createObjectURL(file)
    const video = document.createElement('video')
    let done = false

    const finish = (result) => {
      if (done) return
      done = true
      clearTimeout(timer)
      video.removeAttribute('src')
      video.load()
      URL.revokeObjectURL(url)
      resolve(result)
    }

    const timer = setTimeout(() => finish(empty), 10000)

    video.preload = 'metadata'
    video.muted = true
    video.playsInline = true
    video.onerror = () => finish(empty)

    video.onloadedmetadata = () => {
      const duration = Number.isFinite(video.duration) ? Math.round(video.duration) : null
      // Fotogramma a 1s (o a metà per video molto brevi)
      video.currentTime = Math.min(1, (video.duration || 0) / 2)
      video.onseeked = () => {
        const w = video.videoWidth
        const h = video.videoHeight
        if (!w || !h) {
          finish({ ...empty, duration })
          return
        }
        const ratio = Math.min(1, maxSize / Math.max(w, h))
        const canvas = document.createElement('canvas')
        canvas.width = Math.round(w * ratio)
        canvas.height = Math.round(h * ratio)
        try {
          canvas.getContext('2d').drawImage(video, 0, 0, canvas.width, canvas.height)
          canvas.toBlob(
            (blob) => finish({ poster: blob, width: w, height: h, duration }),
            'image/jpeg',
            0.8
          )
        } catch {
          finish({ poster: null, width: w, height: h, duration })
        }
      }
    }

    video.src = url
  })
}
