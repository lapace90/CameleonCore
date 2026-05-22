<template>
  <div class="calendar-widget">
    <!-- Header compact -->
    <div class="widget-header">
      <div class="header-left">
        <h4 class="widget-title">
          <AppIcon name="calendar-days" />
          {{ title }}
        </h4>
        <span class="current-month">{{ currentMonthText }}</span>
      </div>
      <div class="header-actions">
        <button @click="goToPreviousMonth" class="nav-btn">
          <AppIcon name="chevron-left" />
        </button>
        <button @click="goToToday" class="today-btn" title="Aujourd'hui">
          <AppIcon name="circle-dot" />
        </button>
        <button @click="goToNextMonth" class="nav-btn">
          <AppIcon name="chevron-right" />
        </button>
      </div>
    </div>

    <!-- Calendrier compact -->
    <div class="widget-calendar">
      <FullCalendar ref="calendar" :options="calendarOptions" />
    </div>

    <!-- Actions rapides -->
    <div class="widget-actions" v-if="showActions">
      <button @click="handleQuickAdd" class="action-btn add-btn">
        <AppIcon name="plus" />
        Ajouter
      </button>
      <router-link to="/admin/agenda" class="action-btn view-btn">
        <AppIcon name="external-link" />
        Voir tout
      </router-link>
    </div>

    <!-- Mini événements à venir -->
    <div class="upcoming-events" v-if="showUpcoming">
      <h5 class="upcoming-title">
        <AppIcon name="clock" />
        Prochains événements
      </h5>
      <div class="event-list">
        <div v-for="event in upcomingEvents" :key="event.id" class="mini-event" @click="$emit('event-clicked', event)">
          <div class="event-indicator" :style="{ backgroundColor: event.backgroundColor }"></div>
          <div class="event-info">
            <span class="event-title">{{ event.title }}</span>
            <span class="event-date">{{ formatEventDate(event.start) }}</span>
          </div>
        </div>

        <div v-if="!upcomingEvents.length" class="no-events">
          <AppIcon name="calendar-check" />
          <span>Aucun événement prévu</span>
        </div>
      </div>
    </div>

    <!-- Notification mini -->
    <div v-if="notification" class="mini-notification" :class="`notification-${notification.type}`">
      {{ notification.message }}
    </div>
  </div>
</template>

<script>
import FullCalendar from '@fullcalendar/vue3'
import dayGridPlugin from '@fullcalendar/daygrid'
import interactionPlugin from '@fullcalendar/interaction'
import frLocale from '@fullcalendar/core/locales/fr'
import AdminApi from '@/services/AdminApi'

export default {
  name: 'CalendarWidget',
  components: {
    FullCalendar
  },
  props: {
    title: {
      type: String,
      default: 'Agenda'
    },
    showActions: {
      type: Boolean,
      default: true
    },
    showUpcoming: {
      type: Boolean,
      default: true
    },
    maxUpcomingEvents: {
      type: Number,
      default: 5
    },
    height: {
      type: String,
      default: 'auto'
    }
  },
  data() {
    return {
      currentDate: new Date(),
      notification: null,
      events: [],

      // Configuration FullCalendar pour widget
      calendarOptions: {
        plugins: [dayGridPlugin, interactionPlugin],
        locale: frLocale,
        initialView: 'dayGridWeek', // Vue semaine
        headerToolbar: false,

        // Hauteur fixe pour éviter les variations
        height: 277, // Hauteur fixe en pixels
        // OU utiliser :
        //aspectRatio: 2.1, // Ratio largeur/hauteur fixe

        // Empêcher les lignes variables
        fixedWeekCount: false,
        dayMaxEvents: false, // Pas de limite d'événements par jour
        moreLinkClick: 'popover',

        // Apparence
        showNonCurrentDates: true,
        weekNumbers: false,

        // Interactions
        selectable: true,
        eventClick: this.handleEventClick,
        select: this.handleDateSelect,
        datesSet: this.handleDatesChange,

        // Events
        events: this.getWidgetEvents(),

        // Rendu des événements en points colorés
        eventContent: this.renderEventDot,

        // Style pour que les événements soient des points
        eventDisplay: 'block',

        // Header des jours personnalisé
        dayHeaderContent: (arg) => {
          return {
            html: `<span class="day-header">${arg.text}</span>`
          }
        }
      }
    }
  },

  mounted() {
    // Charger les événements après montage
    this.loadEvents()
  },

  computed: {
    calendarApi() {
      return this.$refs.calendar?.getApi()
    },

    currentMonthText() {
      return this.currentDate.toLocaleDateString('fr-FR', {
        month: 'long',
        year: 'numeric'
      })
    },

    upcomingEvents() {
      const now = new Date()
      return this.events
        .filter(event => new Date(event.start) >= now)
        .sort((a, b) => new Date(a.start) - new Date(b.start))
        .slice(0, this.maxUpcomingEvents)
    }
  },

  methods: {
    async getWidgetEvents() {
      try {
        // Obtenir les dates de début et fin pour la vue actuelle
        const start = new Date()
        const end = new Date()

        // Afficher les événements des 7 prochains jours
        start.setDate(start.getDate() - 7)
        end.setDate(end.getDate() + 14)

        // Formater les dates pour l'API
        const startStr = start.toISOString().split('T')[0]
        const endStr = end.toISOString().split('T')[0]

        // Appeler l'API
        const events = await AdminApi.getCalendarEvents(startStr, endStr)

        // Stocker pour les événements à venir
        this.events = events

        return events
      } catch (error) {
        console.error('❌ Erreur chargement événements widget:', error)
        return []
      }
    },

    async loadEvents() {
      const events = await this.getWidgetEvents()
      if (this.calendarApi) {
        this.calendarApi.removeAllEvents()
        this.calendarApi.addEventSource(events)
      }
    },

    // Navigation
    goToPreviousMonth() {
      this.calendarApi.prev()
    },

    goToNextMonth() {
      this.calendarApi.next()
    },

    goToToday() {
      this.calendarApi.today()
      this.currentDate = new Date()
    },

    // Interactions
    handleEventClick(clickInfo) {
      const event = {
        id: clickInfo.event.id,
        title: clickInfo.event.title,
        start: clickInfo.event.start,
        end: clickInfo.event.end,
        backgroundColor: clickInfo.event.backgroundColor,
        ...clickInfo.event.extendedProps
      }

      this.$emit('event-clicked', event)
    },

    handleDateSelect(selectInfo) {
      const dateStr = selectInfo.start.toLocaleDateString('fr-FR')
      this.showNotification(`Date sélectionnée : ${dateStr}`, 'info')
      this.$emit('date-selected', selectInfo.start)

      // Déselectionner
      selectInfo.view.calendar.unselect()
    },

    handleDatesChange(dateInfo) {
      this.currentDate = dateInfo.view.currentStart
    },

    handleQuickAdd() {
      const newEvent = {
        id: null,
        title: '',
        start: new Date().toISOString(),
        end: new Date(Date.now() + 60 * 60 * 1000).toISOString(),
        backgroundColor: '#3b82f6',
        type: 'reservation'
      }

      this.$emit('event-created', newEvent)
    },

    // Helpers
    renderMiniEvent(eventInfo) {
      return {
        html: `
          <div class="mini-event-content">
            <span class="event-dot" style="background-color: ${eventInfo.event.backgroundColor}"></span>
            <span class="event-text">${eventInfo.event.title}</span>
          </div>
        `
      }
    },

    formatEventDate(dateStr) {
      const date = new Date(dateStr)
      const today = new Date()
      const tomorrow = new Date(today)
      tomorrow.setDate(tomorrow.getDate() + 1)

      if (date.toDateString() === today.toDateString()) {
        return `Aujourd'hui ${date.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' })}`
      } else if (date.toDateString() === tomorrow.toDateString()) {
        return `Demain ${date.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' })}`
      } else {
        return date.toLocaleDateString('fr-FR', {
          weekday: 'short',
          day: 'numeric',
          month: 'short',
          hour: '2-digit',
          minute: '2-digit'
        })
      }
    },

    showNotification(message, type) {
      this.notification = { message, type }
      setTimeout(() => {
        this.notification = null
      }, 2000)
    }
  }
}
</script>

<style scoped>
.calendar-widget {
  background: white;
  border-radius: 12px;
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
  overflow: hidden;
  position: relative;
  transition: all 0.3s ease;
}

.calendar-widget:hover {
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.12);
}

/* Header */
.widget-header {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  padding: 1rem 1.25rem;
  color: white;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.widget-title {
  margin: 0 0 0.25rem 0;
  font-size: 1rem;
  font-weight: 600;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.current-month {
  font-size: 0.75rem;
  opacity: 0.9;
  text-transform: capitalize;
}

.header-actions {
  display: flex;
  gap: 0.25rem;
}

.nav-btn,
.today-btn {
  background: rgba(255, 255, 255, 0.15);
  border: none;
  color: white;
  width: 28px;
  height: 28px;
  border-radius: 6px;
  cursor: pointer;
  transition: all 0.2s ease;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.75rem;
}

.nav-btn:hover,
.today-btn:hover {
  background: rgba(255, 255, 255, 0.25);
}

/* Calendrier */
.widget-calendar {
  padding: 0.75rem;
}

/* Personnalisation FullCalendar pour widget */
:deep(.fc) {
  font-family: 'Inter', sans-serif;
  font-size: 0.75rem;
}

:deep(.fc-daygrid-day-number) {
  font-size: 0.75rem;
  font-weight: 500;
  padding: 2px 4px;
}

:deep(.fc-col-header-cell) {
  background: #f8fafc;
  border-color: #e2e8f0;
  padding: 4px;
}

:deep(.fc-daygrid-day) {
  min-height: 30px;
}

:deep(.fc-day-today) {
  background-color: rgba(102, 126, 234, 0.08) !important;
}

:deep(.fc-day-today .fc-daygrid-day-number) {
  background: #667eea;
  color: white;
  border-radius: 4px;
  font-weight: 600;
}

.day-header {
  font-weight: 600;
  color: #64748b;
  font-size: 0.7rem;
}

/* Mini événements */
.mini-event-content {
  display: flex;
  align-items: center;
  gap: 0.25rem;
  padding: 1px 3px;
  font-size: 0.65rem;
  font-weight: 500;
}

.event-dot {
  width: 6px;
  height: 6px;
  border-radius: 50%;
  flex-shrink: 0;
}

.event-text {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  color: white;
}

:deep(.fc-event) {
  border: none;
  border-radius: 4px;
  margin: 1px;
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
  cursor: pointer;
}

/* Actions */
.widget-actions {
  padding: 0.75rem 1rem;
  background: #f8fafc;
  border-top: 1px solid #e2e8f0;
  display: flex;
  gap: 0.5rem;
}

.action-btn {
  flex: 1;
  padding: 0.5rem;
  border: none;
  border-radius: 6px;
  font-size: 0.75rem;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s ease;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.25rem;
  text-decoration: none;
}

.add-btn {
  background: #3b82f6;
  color: white;
}

.add-btn:hover {
  background: #2563eb;
  transform: translateY(-1px);
}

.view-btn {
  background: #64748b;
  color: white;
}

.view-btn:hover {
  background: #475569;
  transform: translateY(-1px);
}

/* Événements à venir */
.upcoming-events {
  border-top: 1px solid #e2e8f0;
  padding: 1rem;
}

.upcoming-title {
  margin: 0 0 0.75rem 0;
  font-size: 0.875rem;
  font-weight: 600;
  color: #374151;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.event-list {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.mini-event {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.5rem;
  border-radius: 6px;
  cursor: pointer;
  transition: all 0.2s ease;
  border: 1px solid transparent;
}

.mini-event:hover {
  background: #f8fafc;
  border-color: #e2e8f0;
  transform: translateX(2px);
}

.event-indicator {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  flex-shrink: 0;
}

.event-info {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 0.125rem;
}

.event-title {
  font-size: 0.8rem;
  font-weight: 500;
  color: #374151;
  line-height: 1.2;
}

.event-date {
  font-size: 0.7rem;
  color: #64748b;
}

.no-events {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.5rem;
  padding: 1rem;
  color: #9ca3af;
  font-size: 0.8rem;
}

.no-events .app-icon {
  font-size: 1.5rem;
  opacity: 0.5;
}

/* Notification */
.mini-notification {
  position: absolute;
  top: 10px;
  right: 10px;
  padding: 0.5rem 0.75rem;
  border-radius: 6px;
  color: white;
  font-size: 0.75rem;
  font-weight: 500;
  z-index: 100;
  animation: slideInRight 0.3s ease;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
}

.notification-success {
  background: #10b981;
}

.notification-error {
  background: #ef4444;
}

.notification-info {
  background: #3b82f6;
}

@keyframes slideInRight {
  from {
    transform: translateX(100%);
    opacity: 0;
  }

  to {
    transform: translateX(0);
    opacity: 1;
  }
}

/* Responsive */
@media (max-width: 480px) {
  .widget-header {
    padding: 0.75rem 1rem;
  }

  .widget-title {
    font-size: 0.9rem;
  }

  .current-month {
    font-size: 0.7rem;
  }

  .upcoming-events {
    padding: 0.75rem;
  }

  .widget-actions {
    flex-direction: column;
  }
}
</style>