<template>
  <div class="cameleon-calendar">
    <FullCalendar ref="calendar" :options="calendarConfig" />
  </div>
</template>

<script>
import FullCalendar from '@fullcalendar/vue3'
import dayGridPlugin from '@fullcalendar/daygrid'
import timeGridPlugin from '@fullcalendar/timegrid'
import interactionPlugin from '@fullcalendar/interaction'
import frLocale from '@fullcalendar/core/locales/fr'

export default {
  name: 'CameleonCalendar',

  components: { FullCalendar },

  props: {
    // Données
    events: {
      type: [Array, Function],
      default: () => []
    },

    // Vue
    view: {
      type: String,
      default: 'dayGridMonth',
      validator: v => ['dayGridMonth', 'timeGridWeek', 'timeGridDay'].includes(v)
    },

    // Comportement
    editable: {
      type: Boolean,
      default: false
    },
    selectable: {
      type: Boolean,
      default: false
    },

    // Apparence
    height: {
      type: [String, Number],
      default: 'auto'
    },
    headerToolbar: {
      type: [Object, Boolean],
      default: false
    },
    dayMaxEvents: {
      type: [Number, Boolean],
      default: 3
    },
    nowIndicator: {
      type: Boolean,
      default: true
    },
    businessHours: {
      type: [Object, Boolean],
      default: false
    },

    // Rendu custom
    eventContent: {
      type: Function,
      default: null
    },
    eventDisplay: {
      type: String,
      default: 'auto'
    }
  },

  emits: [
    'event-click',
    'date-select',
    'event-drop',
    'event-resize',
    'dates-change'
  ],

  computed: {
    calendarConfig() {
      const config = {
        plugins: [dayGridPlugin, timeGridPlugin, interactionPlugin],
        locale: frLocale,
        initialView: this.view,
        headerToolbar: this.headerToolbar,
        height: this.height,
        dayMaxEvents: this.dayMaxEvents,
        nowIndicator: this.nowIndicator,
        businessHours: this.businessHours,
        editable: this.editable,
        selectable: this.selectable,
        selectMirror: this.selectable,
        droppable: this.editable,
        moreLinkClick: 'popover',

        // Source d'événements
        events: this.events,

        // Callbacks → émissions
        eventClick: (info) => this.$emit('event-click', info),
        select: (info) => this.$emit('date-select', info),
        eventDrop: (info) => this.$emit('event-drop', info),
        eventResize: (info) => this.$emit('event-resize', info),
        datesSet: (info) => this.$emit('dates-change', info),
      }

      if (this.eventContent) {
        config.eventContent = this.eventContent
      }
      if (this.eventDisplay !== 'auto') {
        config.eventDisplay = this.eventDisplay
      }

      return config
    }
  },

  methods: {
    // API publique — ce que les composants parents peuvent appeler via ref
    getApi() {
      return this.$refs.calendar?.getApi() ?? null
    },

    changeView(viewName) {
      this.getApi()?.changeView(viewName)
    },

    prev() {
      this.getApi()?.prev()
    },

    next() {
      this.getApi()?.next()
    },

    today() {
      this.getApi()?.today()
    },

    refetchEvents() {
      this.getApi()?.refetchEvents()
    },

    getDate() {
      return this.getApi()?.getDate() ?? new Date()
    }
  }
}
</script>
