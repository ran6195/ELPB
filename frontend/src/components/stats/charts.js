/**
 * ECharts per la scheda Statistiche: solo i moduli usati (il pacchetto si carica
 * con la scheda, non con il resto del pannello), palette e formattazione in italiano.
 *
 * Palette categoriale in ordine fisso (validata per daltonismo sulle coppie adiacenti),
 * colori della serie legati all'entità e non alla posizione in classifica: vedi seriesColors().
 */
import { use } from 'echarts/core'
import { CanvasRenderer } from 'echarts/renderers'
import { BarChart, LineChart, HeatmapChart } from 'echarts/charts'
import {
  GridComponent, TooltipComponent, LegendComponent, CalendarComponent, VisualMapComponent
} from 'echarts/components'

use([
  CanvasRenderer, BarChart, LineChart, HeatmapChart,
  GridComponent, TooltipComponent, LegendComponent, CalendarComponent, VisualMapComponent
])

export const SERIES = ['#2a78d6', '#eb6834', '#1baf7a', '#eda100', '#e87ba4', '#008300', '#6250d6', '#e34948']
/** "Altre" e "Nessun dato": grigio, mai un colore di serie */
export const OTHERS = '#b4b2a9'
/** Sequenziale blu, dal quasi zero al massimo (calendario) */
export const SEQUENTIAL = ['#eceae4', '#b7d3f6', '#6da7ec', '#2a78d6', '#184f95']

export const INK = {
  primary: '#0b0b0b',
  secondary: '#52514e',
  muted: '#898781',
  grid: '#e1e0d9',
  axis: '#c3c2b7',
  up: '#006300',
  down: '#d03b3b'
}

const FONT = 'system-ui, -apple-system, "Segoe UI", Roboto, sans-serif'

/** Asse, griglia e tooltip comuni: assi recessivi, griglia a filo */
export function baseOption() {
  return {
    textStyle: { fontFamily: FONT, color: INK.secondary },
    animationDuration: 400,
    grid: { left: 8, right: 16, top: 16, bottom: 8, containLabel: true },
    tooltip: {
      trigger: 'axis',
      backgroundColor: '#ffffff',
      borderColor: 'rgba(11,11,11,0.10)',
      textStyle: { color: INK.primary, fontSize: 13 },
      axisPointer: { type: 'shadow', shadowStyle: { color: 'rgba(11,11,11,0.04)' } }
    }
  }
}

export function categoryAxis(labels) {
  return {
    type: 'category',
    data: labels,
    axisLine: { lineStyle: { color: INK.axis } },
    axisTick: { show: false },
    axisLabel: { color: INK.muted, hideOverlap: true }
  }
}

export function valueAxis() {
  return {
    type: 'value',
    minInterval: 1,
    splitLine: { lineStyle: { color: INK.grid } },
    axisLabel: { color: INK.muted }
  }
}

const number = new Intl.NumberFormat('it-IT')
const decimal = new Intl.NumberFormat('it-IT', { maximumFractionDigits: 2 })

export const formatNumber = value => (value == null ? '—' : number.format(value))
export const formatPercent = value => (value == null ? '—' : `${decimal.format(value)}%`)

/** Etichetta del periodo dall'inizio "2026-10-26T00:00:00+01:00" (ora italiana dell'API) */
export function periodLabel(time, interval, long = false) {
  const [y, m, d] = time.slice(0, 10).split('-').map(Number)
  const date = new Date(y, m - 1, d)
  if (interval === 'month') {
    return date.toLocaleDateString('it-IT', { month: long ? 'long' : 'short', year: 'numeric' })
  }
  const day = date.toLocaleDateString('it-IT', long
    ? { weekday: 'short', day: 'numeric', month: 'long', year: 'numeric' }
    : { day: 'numeric', month: 'short' })
  return interval === 'week' ? `${long ? 'Settimana dal' : 'sett.'} ${day}` : day
}

/** Data locale in AAAA-MM-GG (non toISOString: sarebbe UTC) */
export function isoDate(date) {
  const pad = n => String(n).padStart(2, '0')
  return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`
}

/**
 * Colori legati alla serie: una serie mantiene il suo colore cambiando periodo o
 * raggruppamento finché resta visibile; le nuove prendono i posti liberi in ordine.
 */
export function seriesColors(memory, ids) {
  for (const id of [...memory.keys()]) {
    if (!ids.includes(id)) memory.delete(id)
  }
  const used = new Set(memory.values())
  for (const id of ids) {
    if (memory.has(id)) continue
    const slot = SERIES.findIndex(color => !used.has(color))
    const color = slot === -1 ? OTHERS : SERIES[slot]
    memory.set(id, color)
    used.add(color)
  }
  return memory
}
