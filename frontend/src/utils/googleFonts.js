/**
 * Lista di Google Fonts popolari da offrire come opzioni
 */
export const popularGoogleFonts = [
  { name: 'Default (Ereditato)', value: '' },
  { name: 'Inter', value: 'Inter' },
  { name: 'Roboto', value: 'Roboto' },
  { name: 'Open Sans', value: 'Open Sans' },
  { name: 'Lato', value: 'Lato' },
  { name: 'Montserrat', value: 'Montserrat' },
  { name: 'Poppins', value: 'Poppins' },
  { name: 'Raleway', value: 'Raleway' },
  { name: 'Nunito', value: 'Nunito' },
  { name: 'Playfair Display', value: 'Playfair Display' },
  { name: 'Merriweather', value: 'Merriweather' },
  { name: 'PT Sans', value: 'PT Sans' },
  { name: 'Oswald', value: 'Oswald' },
  { name: 'Source Sans Pro', value: 'Source Sans Pro' },
  { name: 'Rubik', value: 'Rubik' },
  { name: 'Work Sans', value: 'Work Sans' },
  { name: 'Ubuntu', value: 'Ubuntu' },
  { name: 'Quicksand', value: 'Quicksand' },
  { name: 'Bebas Neue', value: 'Bebas Neue' },
  { name: 'Dancing Script', value: 'Dancing Script' },
  { name: 'Caveat', value: 'Caveat' }
]

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
