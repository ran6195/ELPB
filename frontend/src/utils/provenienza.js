/**
 * Provenienza della visita per il CRM Edysma: UTM e click ID letti
 * dall'indirizzo della pagina al momento dell'invio del form, più l'indirizzo
 * senza query (page_url). Niente cookie né salvataggi nel browser. Finiscono
 * nei metadata del lead; il backend li esclude dalle email
 * (EmailService::CHIAVI_PROVENIENZA). Stessa logica di provenienzaJs() nel
 * renderer standalone.
 */
export const CHIAVI_PROVENIENZA = ['utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content', 'gclid', 'fbclid']

export function datiProvenienza(posizione = window.location) {
  const parametri = new URLSearchParams(posizione.search)
  const dati = {}

  CHIAVI_PROVENIENZA.forEach(chiave => {
    const valore = parametri.get(chiave)
    if (valore) {
      dati[chiave] = valore.slice(0, 255)
    }
  })

  dati.page_url = posizione.origin + posizione.pathname

  return dati
}
