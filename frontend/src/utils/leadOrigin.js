/**
 * Sito di provenienza dei lead (campo origin_status calcolato dal backend).
 * Condiviso tra pannello aziendale e pannello admin: colonna, filtro, dettaglio, CSV.
 */

const STATUS = {
  associated: { label: 'Dominio associato', badge: '', classes: 'text-gray-700' },
  external: { label: 'Pagina senza domini associati', badge: '', classes: 'text-gray-700' },
  unexpected: {
    label: 'La pagina non è associata a questo sito',
    badge: 'Non previsto',
    classes: 'bg-amber-100 text-amber-800'
  },
  preview: { label: 'Inviato dall\'anteprima dell\'editor', badge: 'Anteprima', classes: 'bg-gray-100 text-gray-600' },
  inferred: {
    label: 'Pagina vecchia: dominio stimato, dato non certo',
    badge: 'Stimato',
    classes: 'bg-blue-50 text-blue-700'
  },
  unknown: { label: 'Origine non registrata', badge: '', classes: 'text-gray-400' }
}

/** Valore del filtro per i lead senza origine */
export const UNKNOWN_SITE = '__unknown__'

export function leadOrigin(lead) {
  const status = STATUS[lead.origin_status] || STATUS.unknown
  const host = lead.domain?.display_hostname || lead.origin_host || ''
  return { ...status, host, status: lead.origin_status || 'unknown' }
}

/** Testo per CSV e dettaglio: "cliente.it (stimato)" */
export function leadSiteText(lead) {
  const { host, status } = leadOrigin(lead)
  if (!host) return ''
  if (status === 'inferred') return `${host} (stimato, dato non certo)`
  if (status === 'preview') return `${host} (anteprima)`
  return host
}

/** Opzioni del filtro per sito, ricavate dai lead caricati */
export function siteOptions(leads) {
  const hosts = new Map()
  let hasUnknown = false
  for (const lead of leads) {
    if (lead.origin_host) hosts.set(lead.origin_host, lead.domain?.display_hostname || lead.origin_host)
    else hasUnknown = true
  }
  const options = [...hosts].sort(([a], [b]) => a.localeCompare(b)).map(([value, label]) => ({ value, label }))
  if (hasUnknown) options.push({ value: UNKNOWN_SITE, label: 'Origine sconosciuta' })
  return options
}

export function matchesSite(lead, value) {
  if (!value) return true
  if (value === UNKNOWN_SITE) return !lead.origin_host
  return lead.origin_host === value
}
