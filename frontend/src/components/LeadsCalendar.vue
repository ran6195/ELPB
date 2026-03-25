<template>
  <div class="w-full">
    <!-- Stato vuoto -->
    <div
      v-if="!hasAnyAppointment"
      class="flex flex-col items-center justify-center py-20 text-center"
    >
      <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mb-4">
        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
            d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
        </svg>
      </div>
      <p class="text-gray-600 font-medium mb-1">Nessun appuntamento trovato</p>
      <p class="text-gray-400 text-sm max-w-xs">
        Aggiungi campi data ai tuoi form avanzati per visualizzare gli appuntamenti qui.
      </p>
    </div>

    <!-- Calendario + pannello laterale -->
    <div v-else class="flex gap-6 items-start">

      <!-- Colonna sinistra: calendario -->
      <div class="flex-shrink-0 w-full" style="flex: 0 0 58%;">
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">

          <!-- Header navigazione mese -->
          <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
            <button
              @click="prevMonth"
              class="w-8 h-8 flex items-center justify-center rounded-lg text-gray-500 hover:text-gray-900 hover:bg-gray-100 transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-1"
              aria-label="Mese precedente"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
              </svg>
            </button>

            <h2 class="text-base font-semibold text-gray-900 select-none">
              {{ currentMonthLabel }}
            </h2>

            <button
              @click="nextMonth"
              class="w-8 h-8 flex items-center justify-center rounded-lg text-gray-500 hover:text-gray-900 hover:bg-gray-100 transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-1"
              aria-label="Mese successivo"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
              </svg>
            </button>
          </div>

          <!-- Intestazioni giorni settimana -->
          <div class="grid grid-cols-7 border-b border-gray-100">
            <div
              v-for="day in weekDays"
              :key="day"
              class="py-2.5 text-center text-xs font-medium text-gray-400 uppercase tracking-wide select-none"
            >
              {{ day }}
            </div>
          </div>

          <!-- Griglia giorni -->
          <div class="grid grid-cols-7">
            <div
              v-for="(cell, index) in calendarCells"
              :key="index"
              @click="cell.currentMonth && selectDay(cell)"
              :class="[
                'relative min-h-[72px] p-2 border-b border-r border-gray-100 transition-colors',
                // Rimuovi bordo destro ogni 7 celle
                (index + 1) % 7 === 0 ? 'border-r-0' : '',
                // Ultima riga senza bordo inferiore
                index >= calendarCells.length - 7 ? 'border-b-0' : '',
                // Giorno mese corrente
                cell.currentMonth
                  ? 'bg-white cursor-pointer hover:bg-blue-50'
                  : 'bg-gray-50 cursor-default',
                // Giorno selezionato
                cell.currentMonth && selectedDateStr === cell.dateStr
                  ? 'bg-blue-600 hover:bg-blue-600'
                  : '',
              ]"
              :aria-label="cell.dateStr"
              :role="cell.currentMonth ? 'button' : 'cell'"
              :tabindex="cell.currentMonth ? 0 : -1"
              @keydown.enter="cell.currentMonth && selectDay(cell)"
              @keydown.space.prevent="cell.currentMonth && selectDay(cell)"
            >
              <!-- Numero giorno -->
              <span
                :class="[
                  'inline-flex items-center justify-center w-7 h-7 rounded-full text-sm font-medium select-none transition-colors',
                  // Giorno selezionato
                  selectedDateStr === cell.dateStr && cell.currentMonth
                    ? 'text-white'
                    : '',
                  // Giorno corrente (non selezionato)
                  cell.isToday && selectedDateStr !== cell.dateStr
                    ? 'ring-2 ring-blue-500 text-blue-600 font-semibold'
                    : '',
                  // Giorni mese corrente normali
                  cell.currentMonth && selectedDateStr !== cell.dateStr && !cell.isToday
                    ? 'text-gray-900'
                    : '',
                  // Giorni mese precedente/successivo
                  !cell.currentMonth
                    ? 'text-gray-300'
                    : '',
                ]"
              >
                {{ cell.day }}
              </span>

              <!-- Badge numero appuntamenti -->
              <div
                v-if="cell.currentMonth && cell.count > 0"
                class="mt-1 flex flex-wrap gap-0.5"
              >
                <span
                  :class="[
                    'inline-flex items-center justify-center min-w-[20px] h-5 px-1.5 rounded-full text-xs font-semibold leading-none',
                    selectedDateStr === cell.dateStr
                      ? 'bg-white text-blue-700'
                      : 'bg-blue-700 text-white',
                  ]"
                >
                  {{ cell.count }}
                </span>
                <span
                  :class="[
                    'text-xs leading-5 truncate max-w-[70px]',
                    selectedDateStr === cell.dateStr
                      ? 'text-blue-100'
                      : 'text-gray-500',
                  ]"
                >
                  {{ cell.count === 1 ? 'appt.' : 'appt.' }}
                </span>
              </div>
            </div>
          </div>

        </div>

        <!-- Legenda -->
        <div class="flex items-center gap-5 mt-3 px-1">
          <div class="flex items-center gap-1.5">
            <div class="w-6 h-6 rounded-full ring-2 ring-blue-500 bg-white"></div>
            <span class="text-xs text-gray-500">Oggi</span>
          </div>
          <div class="flex items-center gap-1.5">
            <div class="w-6 h-6 rounded-full bg-blue-600"></div>
            <span class="text-xs text-gray-500">Selezionato</span>
          </div>
          <div class="flex items-center gap-1.5">
            <span class="inline-flex items-center justify-center min-w-[20px] h-5 px-1.5 rounded-full text-xs font-semibold bg-blue-700 text-white">N</span>
            <span class="text-xs text-gray-500">Appuntamenti</span>
          </div>
        </div>
      </div>

      <!-- Colonna destra: lista appuntamenti giorno -->
      <div class="flex-1 min-w-0">

        <!-- Nessun giorno selezionato -->
        <div
          v-if="!selectedDateStr"
          class="bg-white rounded-xl border border-gray-200 shadow-sm p-8 flex flex-col items-center justify-center text-center min-h-[200px]"
        >
          <div class="w-12 h-12 bg-blue-50 rounded-full flex items-center justify-center mb-3">
            <svg class="w-6 h-6 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5" />
            </svg>
          </div>
          <p class="text-gray-500 text-sm">Clicca su un giorno per vedere gli appuntamenti</p>
        </div>

        <!-- Giorno selezionato senza appuntamenti -->
        <div
          v-else-if="selectedLeads.length === 0"
          class="bg-white rounded-xl border border-gray-200 shadow-sm p-8 flex flex-col items-center justify-center text-center min-h-[200px]"
        >
          <div class="w-12 h-12 bg-gray-100 rounded-full flex items-center justify-center mb-3">
            <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
            </svg>
          </div>
          <p class="text-gray-600 font-medium text-sm mb-1">Nessun appuntamento</p>
          <p class="text-gray-400 text-xs">{{ selectedDayLabel }}</p>
        </div>

        <!-- Lista appuntamenti -->
        <div v-else class="space-y-3">
          <!-- Intestazione -->
          <div class="flex items-center justify-between px-1">
            <h3 class="text-sm font-semibold text-gray-700">{{ selectedDayLabel }}</h3>
            <span class="text-xs text-gray-400">
              {{ selectedLeads.length }} {{ selectedLeads.length === 1 ? 'appuntamento' : 'appuntamenti' }}
            </span>
          </div>

          <!-- Card appuntamento -->
          <div
            v-for="lead in selectedLeads"
            :key="lead.id"
            class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 hover:border-blue-200 hover:shadow-md transition-all"
          >
            <div class="flex items-start gap-3">
              <!-- Avatar iniziali -->
              <div
                class="flex-shrink-0 w-10 h-10 rounded-full bg-gradient-to-br from-blue-500 to-blue-700 flex items-center justify-center text-white text-sm font-semibold select-none"
                aria-hidden="true"
              >
                {{ getInitials(lead.name) }}
              </div>

              <!-- Contenuto -->
              <div class="flex-1 min-w-0">
                <!-- Nome e ora -->
                <div class="flex items-start justify-between gap-2 mb-1">
                  <p class="text-sm font-semibold text-gray-900 truncate">{{ lead.name }}</p>
                  <span
                    v-if="getAppointmentTime(lead)"
                    class="flex-shrink-0 inline-flex items-center gap-1 bg-blue-50 text-blue-700 text-xs font-semibold px-2 py-0.5 rounded-full"
                  >
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    {{ getAppointmentTime(lead) }}
                  </span>
                </div>

                <!-- Email -->
                <div class="flex items-center gap-1.5 mb-0.5">
                  <svg class="w-3.5 h-3.5 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                  </svg>
                  <a
                    :href="`mailto:${lead.email}`"
                    class="text-xs text-gray-500 hover:text-blue-600 truncate transition-colors"
                  >{{ lead.email }}</a>
                </div>

                <!-- Telefono -->
                <div v-if="lead.phone" class="flex items-center gap-1.5 mb-0.5">
                  <svg class="w-3.5 h-3.5 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                  </svg>
                  <a
                    :href="`tel:${lead.phone}`"
                    class="text-xs text-gray-500 hover:text-blue-600 transition-colors"
                  >{{ lead.phone }}</a>
                </div>

                <!-- Pagina di provenienza -->
                <div v-if="lead.page?.title" class="flex items-center gap-1.5 mt-1.5">
                  <svg class="w-3.5 h-3.5 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                  </svg>
                  <span class="text-xs text-gray-400 truncate">{{ lead.page.title }}</span>
                </div>
              </div>
            </div>

            <!-- Pulsante dettagli -->
            <div class="mt-3 pt-3 border-t border-gray-100">
              <button
                @click="$emit('show-lead', lead)"
                class="w-full flex items-center justify-center gap-1.5 text-xs font-medium text-blue-600 hover:text-blue-800 hover:bg-blue-50 py-1.5 rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-1"
              >
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                </svg>
                Vedi dettagli
              </button>
            </div>
          </div>
        </div>
      </div>

    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'

// ---------------------------------------------------------------------------
// Props & Emits
// ---------------------------------------------------------------------------
const props = defineProps({
  leads: {
    type: Array,
    required: true,
  },
})

const emit = defineEmits(['show-lead'])

// ---------------------------------------------------------------------------
// Costanti
// ---------------------------------------------------------------------------
const weekDays = ['Lun', 'Mar', 'Mer', 'Gio', 'Ven', 'Sab', 'Dom']

const monthNames = [
  'Gennaio', 'Febbraio', 'Marzo', 'Aprile', 'Maggio', 'Giugno',
  'Luglio', 'Agosto', 'Settembre', 'Ottobre', 'Novembre', 'Dicembre',
]

const DATE_RE = /^\d{4}-\d{2}-\d{2}$/
const TIME_RE = /^\d{2}:\d{2}$/

// ---------------------------------------------------------------------------
// Stato reattivo
// ---------------------------------------------------------------------------
const today = new Date()
const currentYear = ref(today.getFullYear())
const currentMonth = ref(today.getMonth()) // 0-based

const selectedDateStr = ref(null) // "YYYY-MM-DD"

// ---------------------------------------------------------------------------
// Rilevamento appuntamenti
// ---------------------------------------------------------------------------

/**
 * Restituisce la prima data (YYYY-MM-DD) trovata nei metadata di un lead.
 * Priorità: metadata._appointment.date, poi scansione flat dei valori stringa.
 */
function getAppointmentDate(lead) {
  if (!lead.metadata || typeof lead.metadata !== 'object') return null
  if (lead.metadata._appointment?.date && DATE_RE.test(lead.metadata._appointment.date)) {
    return lead.metadata._appointment.date
  }
  for (const value of Object.values(lead.metadata)) {
    if (typeof value === 'string' && DATE_RE.test(value)) return value
  }
  return null
}

/**
 * Restituisce il primo orario (HH:MM) trovato nei metadata di un lead.
 * Priorità: metadata._appointment.time, poi scansione flat dei valori stringa.
 */
function getAppointmentTime(lead) {
  if (!lead.metadata || typeof lead.metadata !== 'object') return null
  if (lead.metadata._appointment?.time && TIME_RE.test(lead.metadata._appointment.time)) {
    return lead.metadata._appointment.time
  }
  for (const value of Object.values(lead.metadata)) {
    if (typeof value === 'string' && TIME_RE.test(value)) return value
  }
  return null
}

/**
 * Mappa dateStr -> array di leads con appuntamento in quella data.
 */
const appointmentsByDate = computed(() => {
  const map = {}
  for (const lead of props.leads) {
    const date = getAppointmentDate(lead)
    if (!date) continue
    if (!map[date]) map[date] = []
    map[date].push(lead)
  }
  return map
})

const hasAnyAppointment = computed(() => {
  return Object.keys(appointmentsByDate.value).length > 0
})

// ---------------------------------------------------------------------------
// Navigazione mese
// ---------------------------------------------------------------------------
const currentMonthLabel = computed(() => {
  return `${monthNames[currentMonth.value]} ${currentYear.value}`
})

function prevMonth() {
  if (currentMonth.value === 0) {
    currentMonth.value = 11
    currentYear.value--
  } else {
    currentMonth.value--
  }
  selectedDateStr.value = null
}

function nextMonth() {
  if (currentMonth.value === 11) {
    currentMonth.value = 0
    currentYear.value++
  } else {
    currentMonth.value++
  }
  selectedDateStr.value = null
}

// ---------------------------------------------------------------------------
// Griglia calendario
// ---------------------------------------------------------------------------

/**
 * Formatta una data come "YYYY-MM-DD" senza problemi di timezone.
 */
function toDateStr(year, month, day) {
  const mm = String(month + 1).padStart(2, '0')
  const dd = String(day).padStart(2, '0')
  return `${year}-${mm}-${dd}`
}

const todayStr = toDateStr(today.getFullYear(), today.getMonth(), today.getDate())

const calendarCells = computed(() => {
  const year = currentYear.value
  const month = currentMonth.value

  // Primo giorno del mese (0=Dom, 1=Lun, ...)
  const firstDow = new Date(year, month, 1).getDay()
  // Converti in indice lunedi-based (0=Lun, 6=Dom)
  const startOffset = (firstDow + 6) % 7

  // Giorni nel mese corrente
  const daysInMonth = new Date(year, month + 1, 0).getDate()

  // Giorni nel mese precedente
  const prevMonth = month === 0 ? 11 : month - 1
  const prevYear = month === 0 ? year - 1 : year
  const daysInPrevMonth = new Date(prevYear, prevMonth + 1, 0).getDate()

  const cells = []

  // Celle mese precedente
  for (let i = startOffset - 1; i >= 0; i--) {
    const d = daysInPrevMonth - i
    const dateStr = toDateStr(prevYear, prevMonth, d)
    cells.push({
      day: d,
      dateStr,
      currentMonth: false,
      isToday: false,
      count: 0,
    })
  }

  // Celle mese corrente
  for (let d = 1; d <= daysInMonth; d++) {
    const dateStr = toDateStr(year, month, d)
    cells.push({
      day: d,
      dateStr,
      currentMonth: true,
      isToday: dateStr === todayStr,
      count: appointmentsByDate.value[dateStr]?.length ?? 0,
    })
  }

  // Celle mese successivo (riempi fino a multiplo di 7)
  const nextMonth = month === 11 ? 0 : month + 1
  const nextYear = month === 11 ? year + 1 : year
  const remaining = (7 - (cells.length % 7)) % 7
  for (let d = 1; d <= remaining; d++) {
    const dateStr = toDateStr(nextYear, nextMonth, d)
    cells.push({
      day: d,
      dateStr,
      currentMonth: false,
      isToday: false,
      count: 0,
    })
  }

  return cells
})

// ---------------------------------------------------------------------------
// Selezione giorno
// ---------------------------------------------------------------------------
function selectDay(cell) {
  if (selectedDateStr.value === cell.dateStr) {
    selectedDateStr.value = null
  } else {
    selectedDateStr.value = cell.dateStr
  }
}

const selectedLeads = computed(() => {
  if (!selectedDateStr.value) return []
  return appointmentsByDate.value[selectedDateStr.value] ?? []
})

const selectedDayLabel = computed(() => {
  if (!selectedDateStr.value) return ''
  const [year, month, day] = selectedDateStr.value.split('-').map(Number)
  const date = new Date(year, month - 1, day)
  return date.toLocaleDateString('it-IT', {
    weekday: 'long',
    day: 'numeric',
    month: 'long',
    year: 'numeric',
  })
})

// ---------------------------------------------------------------------------
// Utilities UI
// ---------------------------------------------------------------------------
function getInitials(name) {
  if (!name) return '?'
  const parts = name.trim().split(/\s+/)
  if (parts.length === 1) return parts[0].charAt(0).toUpperCase()
  return (parts[0].charAt(0) + parts[parts.length - 1].charAt(0)).toUpperCase()
}
</script>
