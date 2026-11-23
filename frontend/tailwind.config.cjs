/** @type {import('tailwindcss').Config} */
const plugin = require('tailwindcss/plugin')
const defaults = require('tailwindcss/colors')

/*
 * Grigi, blu, primary, colori di stato (verde, rosso, ambra/giallo), angoli e ombre sono variabili CSS, così l'interfaccia dell'app può usare
 * il Sistema grafico Edysma senza cambiare le classi nei componenti.
 *
 * - :root e .lp-scope  → valori di sempre (Tailwind standard e primary azzurro).
 *   Le landing (canvas dell'editor, anteprima, /p/:slug) restano identiche a quelle
 *   dei renderer pubblici, che usano Tailwind standard.
 * - .ed-ui             → valori Edysma (grigi freddi, blu d'azione #2a68a1).
 *   Messa su #app nelle schermate di gestione (App.vue).
 */
const SHADES = ['50', '100', '200', '300', '400', '500', '600', '700', '800', '900', '950']

const landing = {
  gray: defaults.gray,
  blue: defaults.blue,
  green: defaults.green,
  red: defaults.red,
  amber: defaults.amber,
  yellow: defaults.yellow,
  primary: {
    50: '#f0f9ff', 100: '#e0f2fe', 200: '#bae6fd', 300: '#7dd3fc', 400: '#38bdf8',
    500: '#0ea5e9', 600: '#0284c7', 700: '#0369a1', 800: '#075985', 900: '#0c4a6e', 950: '#082f49',
  },
}

// Sistema grafico Edysma Tools (tokens.css v0.6): 50 bg, 100 line-soft, 200 line,
// 400 faint, 500 muted, 700 ink, 900 fg; blu: 100 brand-soft, 600 brand, 700 brand-hover
const edGray = {
  50: '#f4f7fa', 100: '#e9eef3', 200: '#dbe2ea', 300: '#c3ccd6', 400: '#7b8592',
  500: '#5a6675', 600: '#46525f', 700: '#2d4156', 800: '#1f2d3b', 900: '#15202b', 950: '#0b1219',
}
const edBlue = {
  50: '#eef4fa', 100: '#dbe8f5', 200: '#b9d2ea', 300: '#8cb4da', 400: '#5a90c4',
  500: '#3d7cb6', 600: '#2a68a1', 700: '#22557f', 800: '#1c4567', 900: '#173853', 950: '#0f2438',
}
// Stati: sempre colore + parola. 100 = fondo del badge, 700 = testo (ok #2f6b4f su #e1efe7,
// errore #a3412c su #f8e6e1, attenzione #7a5a12 su #f6edd5). In verde e rosso il 600 è
// già il colore del testo, perché i componenti usano text-green-600 / text-red-600.
const edGreen = {
  50: '#f0f7f3', 100: '#e1efe7', 200: '#c3dfcf', 300: '#9cc9b0', 400: '#6aab88',
  500: '#3f8a63', 600: '#2f6b4f', 700: '#2f6b4f', 800: '#245440', 900: '#1a3c2d', 950: '#10261c',
}
const edRed = {
  50: '#fcf3f0', 100: '#f8e6e1', 200: '#f0cbc1', 300: '#e3a594', 400: '#cf7660',
  500: '#b9563f', 600: '#a3412c', 700: '#a3412c', 800: '#8a3624', 900: '#712c1e', 950: '#3a160f',
}
const edAmber = {
  50: '#fbf7ec', 100: '#f6edd5', 200: '#ecdbab', 300: '#dfc27a', 400: '#cfa548',
  500: '#b8862a', 600: '#9a6e1c', 700: '#7a5a12', 800: '#7a5a12', 900: '#5c430d', 950: '#2f2208',
}
const edysma = { gray: edGray, blue: edBlue, primary: edBlue, green: edGreen, red: edRed, amber: edAmber, yellow: edAmber }

// Forme: angoli e ombre del sistema (6 px per controlli e schede, una sola ombra leggera;
// quella "pop" per menu e tendine). Le finestre di dialogo (shadow-xl / 2xl) restano come sono.
const shapes = {
  landing: {
    '--r-lg': '0.5rem', '--r-xl': '0.75rem',
    '--sh-sm': '0 1px 2px 0 rgb(0 0 0 / 0.05)',
    '--sh': '0 1px 3px 0 rgb(0 0 0 / 0.1), 0 1px 2px -1px rgb(0 0 0 / 0.1)',
    '--sh-md': '0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1)',
    '--sh-lg': '0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1)',
  },
  edysma: {
    '--r-lg': '6px', '--r-xl': '8px',
    '--sh-sm': '0 1px 2px rgba(21, 32, 43, 0.06)',
    '--sh': '0 1px 2px rgba(21, 32, 43, 0.06)',
    '--sh-md': '0 4px 14px rgba(0, 0, 0, 0.14)',
    '--sh-lg': '0 4px 14px rgba(0, 0, 0, 0.14)',
  },
}

const rgb = (hex) => {
  const n = parseInt(hex.slice(1), 16)
  return `${(n >> 16) & 255} ${(n >> 8) & 255} ${n & 255}`
}

const vars = (palette) => {
  const out = {}
  for (const [name, scale] of Object.entries(palette)) {
    for (const s of SHADES) out[`--c-${name}-${s}`] = rgb(scale[s])
  }
  return out
}

const varColors = (name) =>
  Object.fromEntries(SHADES.map((s) => [s, `rgb(var(--c-${name}-${s}) / <alpha-value>)`]))

const FONT_LANDING = "-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif"
const FONT_EDYSMA = "'Nunito Variable', 'Nunito', 'Segoe UI', system-ui, -apple-system, sans-serif"

module.exports = {
  content: [
    "./index.html",
    "./src/**/*.{vue,js,ts,jsx,tsx}",
  ],
  theme: {
    extend: {
      colors: {
        gray: varColors('gray'),
        blue: varColors('blue'),
        primary: varColors('primary'),
        green: varColors('green'),
        red: varColors('red'),
        amber: varColors('amber'),
        yellow: varColors('yellow'),
      },
      borderRadius: {
        lg: 'var(--r-lg)',
        xl: 'var(--r-xl)',
      },
      boxShadow: {
        sm: 'var(--sh-sm)',
        DEFAULT: 'var(--sh)',
        md: 'var(--sh-md)',
        lg: 'var(--sh-lg)',
      },
    },
  },
  plugins: [
    plugin(({ addBase }) => {
      addBase({
        ':root, .lp-scope': { ...vars(landing), ...shapes.landing, fontFamily: FONT_LANDING },
        '.lp-scope': { color: '#000000' },
        '.ed-ui': { ...vars(edysma), ...shapes.edysma, fontFamily: FONT_EDYSMA, backgroundColor: edGray[50], color: edGray[900] },
      })
    }),
  ],
}
