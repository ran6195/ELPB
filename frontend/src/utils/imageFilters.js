// Filtri dell'editor immagini: stessi valori usati per l'anteprima (CSS filter)
// e per l'esportazione (CanvasRenderingContext2D.filter), così il risultato è identico.

export const DEFAULT_ADJUSTMENTS = Object.freeze({
  brightness: 100, // %
  contrast: 100,   // %
  saturate: 100,   // %
  grayscale: 0,    // %
  sepia: 0,        // %
  blur: 0          // px riferiti all'anteprima
})

export const FILTER_PRESETS = [
  { id: 'none', label: 'Nessuno', values: {} },
  { id: 'bw', label: 'Bianco e nero', values: { grayscale: 100, contrast: 110 } },
  { id: 'sepia', label: 'Seppia', values: { sepia: 80, contrast: 105 } },
  { id: 'vintage', label: 'Vintage', values: { sepia: 35, contrast: 110, brightness: 105, saturate: 80 } },
  { id: 'vivid', label: 'Vivace', values: { saturate: 140, contrast: 110 } },
  { id: 'bright', label: 'Luminoso', values: { brightness: 115, contrast: 95 } },
  { id: 'muted', label: 'Tenue', values: { saturate: 60, brightness: 105, contrast: 90 } }
]

export function presetValues(preset) {
  return { ...DEFAULT_ADJUSTMENTS, ...preset.values }
}

export function isDefault(adjustments) {
  return Object.keys(DEFAULT_ADJUSTMENTS).every((k) => adjustments[k] === DEFAULT_ADJUSTMENTS[k])
}

/**
 * Stringa CSS filter. `blurScale` converte la sfocatura dai pixel dell'anteprima
 * a quelli dell'immagine esportata (che ha dimensioni diverse).
 */
export function buildFilter(a, blurScale = 1) {
  const parts = []
  if (a.brightness !== 100) parts.push(`brightness(${a.brightness}%)`)
  if (a.contrast !== 100) parts.push(`contrast(${a.contrast}%)`)
  if (a.saturate !== 100) parts.push(`saturate(${a.saturate}%)`)
  if (a.grayscale) parts.push(`grayscale(${a.grayscale}%)`)
  if (a.sepia) parts.push(`sepia(${a.sepia}%)`)
  if (a.blur) parts.push(`blur(${(a.blur * blurScale).toFixed(2)}px)`)
  return parts.length ? parts.join(' ') : 'none'
}

/** Safari < 18 non supporta ctx.filter: in quel caso i filtri non sono esportabili */
export function canvasFilterSupported() {
  try {
    const ctx = document.createElement('canvas').getContext('2d')
    return !!ctx && 'filter' in ctx
  } catch {
    return false
  }
}
