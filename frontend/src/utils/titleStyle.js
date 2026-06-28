// Utility condivise per lo stile dei titoli dei blocchi

// Valori di default per una nuova ombra titolo
export const defaultTitleShadow = () => ({
  enabled: true,
  color: '#000000',
  blur: 6,
  offsetX: 2,
  offsetY: 2
})

// Ritorna l'oggetto style con text-shadow se l'ombra è abilitata, altrimenti {}
export function titleShadowStyle(content) {
  const ts = content && content.titleShadow
  if (!ts || !ts.enabled) return {}
  const x = ts.offsetX ?? 2
  const y = ts.offsetY ?? 2
  const blur = ts.blur ?? 6
  const color = ts.color || 'rgba(0, 0, 0, 0.5)'
  return { textShadow: `${x}px ${y}px ${blur}px ${color}` }
}
