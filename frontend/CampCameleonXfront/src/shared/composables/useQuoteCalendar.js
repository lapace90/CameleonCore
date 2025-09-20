import { computed } from 'vue'
import dayGridPlugin from '@fullcalendar/daygrid'
import interactionPlugin from '@fullcalendar/interaction'
import frLocale from '@fullcalendar/core/locales/fr'

export function useQuoteCalendar(selectedDates) {
  // Configuration FullCalendar (exactement comme dans QuoteModal)
  const fcOptions = computed(() => {
    return {
      plugins: [dayGridPlugin, interactionPlugin],
      locales: [frLocale],
      locale: 'fr',
      initialView: 'dayGridMonth',
      firstDay: 1,
      height: 380,
      contentHeight: 280,
      aspectRatio: 1.6,
      handleWindowResize: true,
      dayHeaderFormat: { weekday: 'short' },
      titleFormat: { year: 'numeric', month: 'short' },
      selectable: true,
      selectMirror: true,
      fixedWeekCount: false,
      validRange: { start: minDate.value },
      headerToolbar: {
        left: 'prev,next',
        center: 'title',
        right: 'today'
      },
      select: fcOnSelect,
      eventDisplay: 'block',
      dayMaxEvents: 2
    }
  })

  // Callback sélection dates (exactement comme dans QuoteModal)
  const fcOnSelect = (info) => {
    selectedDates.start = info.startStr
    selectedDates.endExclusive = info.endStr
  }

  // Date minimum (exactement comme dans QuoteModal)
  const minDate = computed(() => {
    const d = new Date()
    d.setDate(d.getDate() + 1)
    return d.toISOString().split('T')[0]
  })

  // Utilitaires dates (exactement comme dans QuoteModal)
  const toUTCDateParts = (yyyyMmDd) => {
    const [y, m, d] = (yyyyMmDd || '').split('-').map(Number)
    return isNaN(y) ? null : Date.UTC(y, (m || 1) - 1, d || 1)
  }

  const displayEndInclusive = (endExclusiveYmd) => {
    const e = toUTCDateParts(endExclusiveYmd)
    if (e == null) return ''
    const d = new Date(e - 24 * 60 * 60 * 1000)
    const yyyy = d.getUTCFullYear()
    const mm = String(d.getUTCMonth() + 1).padStart(2, '0')
    const dd = String(d.getUTCDate()).padStart(2, '0')
    return `${yyyy}-${mm}-${dd}`
  }

  const formatDate = (s) => {
    return s ? new Date(s).toLocaleDateString('fr-FR', { 
      day: 'numeric', 
      month: 'long', 
      year: 'numeric' 
    }) : ''
  }

  return {
    fcOptions,
    fcOnSelect,
    minDate,
    toUTCDateParts,
    displayEndInclusive,
    formatDate
  }
}
