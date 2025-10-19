/**
 * Carica dinamicamente un font da Google Fonts
 * @param {string} fontFamily - Nome del font da caricare
 */
export function loadGoogleFont(fontFamily) {
  if (!fontFamily) return

  // Rimuovi link precedenti con lo stesso font
  const existingLinks = document.querySelectorAll('link[data-google-font]')
  existingLinks.forEach(link => {
    if (link.getAttribute('data-font-family') === fontFamily) {
      return // Il font è già caricato
    }
  })

  // Crea il link per Google Fonts
  const link = document.createElement('link')
  link.rel = 'stylesheet'
  link.href = `https://fonts.googleapis.com/css2?family=${fontFamily.replace(/ /g, '+')}:wght@300;400;500;600;700&display=swap`
  link.setAttribute('data-google-font', 'true')
  link.setAttribute('data-font-family', fontFamily)

  document.head.appendChild(link)
}

/**
 * Rimuovi tutti i font Google Fonts caricati dinamicamente
 */
export function clearGoogleFonts() {
  const links = document.querySelectorAll('link[data-google-font]')
  links.forEach(link => link.remove())
}
