/**
 * Etichette per il registro dei renderer (siti che mostrano le pagine) e le visite.
 */

const RENDERER_NAMES = { standalone: 'Standalone', joomla: 'Joomla', altro: 'Sconosciuto' }

/** "Standalone 2.1.0", oppure solo il nome per i renderer precedenti alla 2.1 */
export function rendererLabel(hit) {
  const name = RENDERER_NAMES[hit.renderer] || hit.renderer || 'Sconosciuto'
  return hit.version ? `${name} ${hit.version}` : name
}

/** Data del database ("2026-10-26 20:05:09", ora italiana) come "2 ore fa" */
export function timeAgo(value) {
  if (!value) return '—'
  const date = new Date(String(value).replace(' ', 'T'))
  if (Number.isNaN(date.getTime())) return '—'
  const minutes = Math.round((Date.now() - date.getTime()) / 60000)
  if (minutes < 2) return 'adesso'
  if (minutes < 60) return `${minutes} minuti fa`
  const hours = Math.round(minutes / 60)
  if (hours < 24) return hours === 1 ? "un'ora fa" : `${hours} ore fa`
  const days = Math.round(hours / 24)
  if (days === 1) return 'ieri'
  if (days < 30) return `${days} giorni fa`
  return date.toLocaleDateString('it-IT', { day: '2-digit', month: '2-digit', year: 'numeric' })
}

/** Data breve "26/10/2026" */
export function formatDay(value) {
  if (!value) return ''
  const date = new Date(String(value).slice(0, 10) + 'T00:00:00')
  return date.toLocaleDateString('it-IT', { day: '2-digit', month: '2-digit', year: 'numeric' })
}
