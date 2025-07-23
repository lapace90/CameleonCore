<template>
  <div class="calendar-container">
    <!-- Header du calendrier -->
    <div class="calendar-header">
      <div class="header-left">
        <!-- <h2 class="calendar-title"></h2> -->
        <p class="calendar-subtitle">Gestion des réservations et événements</p>
      </div>
      <div class="header-actions">
        <div class="view-switcher">
          <button v-for="view in availableViews" :key="view.value" @click="changeView(view.value)" class="view-btn"
            :class="{ active: currentView === view.value }">
            <i :class="view.icon"></i>
            {{ view.label }}
          </button>
        </div>
        <button @click="openCreateModal" class="btn-create">
          <i class="fas fa-plus"></i>
          Nouvel événement
        </button>
      </div>
    </div>

    <!-- Stats rapides -->
    <div class="calendar-stats" v-if="showStats">
      <div class="stat-card">
        <div class="stat-icon reservations">
          <i class="fas fa-calendar-check"></i>
        </div>
        <div class="stat-content">
          <span class="stat-number">{{ stats.reservations }}</span>
          <span class="stat-label">Réservations ce mois</span>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon events">
          <i class="fas fa-star"></i>
        </div>
        <div class="stat-content">
          <span class="stat-number">{{ stats.events }}</span>
          <span class="stat-label">Événements prévus</span>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon occupancy">
          <i class="fas fa-chart-line"></i>
        </div>
        <div class="stat-content">
          <span class="stat-number">{{ stats.occupancy }}%</span>
          <span class="stat-label">Taux d'occupation</span>
        </div>
      </div>
    </div>

    <!-- Le calendrier FullCalendar -->
    <div class="calendar-wrapper">
      <FullCalendar ref="calendar" :options="calendarOptions" />
    </div>

    <!-- Modal création/édition événement -->
    <EventModal :show="showModal" :event="currentEvent" :is-editing="isEditing" @save="handleSaveEvent"
      @delete="handleDeleteEvent" @close="closeModal" />

    <!-- Modal de confirmation suppression -->
    <ConfirmModal :show="showConfirmDelete" title="Supprimer l'événement"
      message="Êtes-vous sûr de vouloir supprimer cet événement ? Cette action est irréversible."
      @confirm="confirmDelete" @cancel="showConfirmDelete = false" />
  </div>
</template>

<script>
import FullCalendar from '@fullcalendar/vue3'
import dayGridPlugin from '@fullcalendar/daygrid'
import timeGridPlugin from '@fullcalendar/timegrid'
import interactionPlugin from '@fullcalendar/interaction'
import frLocale from '@fullcalendar/core/locales/fr'
import EventModal from './EventModal.vue'
import ConfirmModal from './ConfirmModal.vue'

export default {
  name: 'FullAgenda',
  components: {
    FullCalendar,
    EventModal,
    ConfirmModal
  },
  props: {
    title: {
      type: String,
      default: 'Calendrier du Camping'
    },
    showStats: {
      type: Boolean,
      default: true
    },
    // Mode : 'admin' pour toutes les fonctionnalités, 'client' pour lecture seule
    mode: {
      type: String,
      default: 'admin',
      validator: value => ['admin', 'client'].includes(value)
    }
  },
  data() {
    return {
      currentView: 'dayGridMonth',
      showModal: false,
      showConfirmDelete: false,
      isEditing: false,
      eventToDelete: null,
      currentEvent: this.getEmptyEvent(),

      // Vues disponibles selon le mode
      availableViews: [
        { value: 'dayGridMonth', label: 'Mois', icon: 'fas fa-calendar' },
        { value: 'timeGridWeek', label: 'Semaine', icon: 'fas fa-calendar-week' },
        { value: 'timeGridDay', label: 'Jour', icon: 'fas fa-calendar-day' }
      ],

      // Stats du camping
      stats: {
        reservations: 24,
        events: 8,
        occupancy: 78
      },

      // Configuration FullCalendar
      calendarOptions: {
        plugins: [dayGridPlugin, timeGridPlugin, interactionPlugin],
        locale: frLocale,
        initialView: 'dayGridMonth',
        headerToolbar: false, // On utilise notre propre header
        height: 'auto',

        // Apparence
        dayMaxEvents: 3,
        moreLinkClick: 'popover',
        nowIndicator: true,
        weekNumbers: false,

        // Heures d'ouverture du camping
        businessHours: {
          daysOfWeek: [0, 1, 2, 3, 4, 5, 6], // Tous les jours
          startTime: '08:00',
          endTime: '22:00'
        },

        // Interactions (selon le mode)
        editable: this.mode === 'admin',
        selectable: this.mode === 'admin',
        selectMirror: true,
        droppable: this.mode === 'admin',

        // Events sources
        events: this.fetchEvents,

        // Callbacks
        select: this.handleDateSelect,
        eventClick: this.handleEventClick,
        eventDrop: this.handleEventDrop,
        eventResize: this.handleEventResize,
        datesSet: this.handleDatesChange,

        // Custom rendering
        eventClassNames: this.getEventClasses,
        eventContent: this.renderEventContent
      }
    }
  },

  computed: {
    calendarApi() {
      return this.$refs.calendar?.getApi()
    }
  },

  methods: {
    // === GESTION DES ÉVÉNEMENTS ===
    fetchEvents(fetchInfo, successCallback, failureCallback) {
      // Simulation - remplacez par votre API
      const events = this.getSimulatedEvents(fetchInfo.start, fetchInfo.end)

      // En production, remplacez par :
      // this.$http.get('/api/events', {
      //   params: {
      //     start: fetchInfo.startStr,
      //     end: fetchInfo.endStr
      //   }
      // }).then(response => {
      //   successCallback(response.data)
      // }).catch(error => {
      //   failureCallback(error)
      // })

      successCallback(events)
    },

    getSimulatedEvents(start, end) {
      return [
        {
          id: '1',
          title: 'Réservation - Famille Dupont',
          start: '2025-07-22T15:00:00',
          end: '2025-07-25T11:00:00',
          backgroundColor: '#28a745',
          borderColor: '#1e7e34',
          extendedProps: {
            type: 'reservation',
            customerName: 'Famille Dupont',
            phone: '06 12 34 56 78',
            notes: 'Emplacement proche sanitaires demandé'
          }
        },
        {
          id: '2',
          title: 'Animation - Soirée karaoké',
          start: '2025-07-23T20:00:00',
          end: '2025-07-23T23:00:00',
          backgroundColor: '#ffc107',
          borderColor: '#e0a800',
          extendedProps: {
            type: 'animation',
            location: 'Salle principale',
            capacity: 50
          }
        },
        {
          id: '3',
          title: 'Maintenance - Piscine',
          start: '2025-07-24T08:00:00',
          end: '2025-07-24T12:00:00',
          backgroundColor: '#dc3545',
          borderColor: '#c82333',
          extendedProps: {
            type: 'maintenance',
            priority: 'high'
          }
        },
        {
          id: '4',
          title: 'Formation équipe',
          start: '2025-07-25T14:00:00',
          end: '2025-07-25T17:00:00',
          backgroundColor: '#6f42c1',
          borderColor: '#59359a',
          extendedProps: {
            type: 'formation',
            participants: 8
          }
        }
      ]
    },

    // === INTERACTIONS UTILISATEUR ===
    handleDateSelect(selectInfo) {
      if (this.mode !== 'admin') return

      this.currentEvent = {
        ...this.getEmptyEvent(),
        start: selectInfo.start.toISOString(),
        end: selectInfo.end.toISOString()
      }
      this.isEditing = false
      this.showModal = true

      // Déselectionner après ouverture modal
      selectInfo.view.calendar.unselect()
    },

    handleEventClick(clickInfo) {
      this.currentEvent = this.extractEventData(clickInfo.event)
      this.isEditing = true

      if (this.mode === 'admin') {
        this.showModal = true
      } else {
        // Mode client : juste afficher les détails
        this.showEventDetails(this.currentEvent)
      }
    },

    handleEventDrop(dropInfo) {
      this.updateEvent({
        ...this.extractEventData(dropInfo.event),
        start: dropInfo.event.start.toISOString(),
        end: dropInfo.event.end?.toISOString()
      })
    },

    handleEventResize(resizeInfo) {
      this.updateEvent({
        ...this.extractEventData(resizeInfo.event),
        start: resizeInfo.event.start.toISOString(),
        end: resizeInfo.event.end?.toISOString()
      })
    },

    handleDatesChange(dateInfo) {
      this.currentView = dateInfo.view.type
      // Mettre à jour les stats pour la période visible
      this.updateStatsForPeriod(dateInfo.start, dateInfo.end)
    },

    // === CRUD OPERATIONS ===
    handleSaveEvent(eventData) {
      if (this.isEditing) {
        this.updateEvent(eventData)
      } else {
        this.createEvent(eventData)
      }
      this.closeModal()
    },

    async createEvent(eventData) {
      try {
        // Simulation - remplacez par votre API
        const newEvent = {
          ...eventData,
          id: this.generateId()
        }

        // En production :
        // const response = await this.$http.post('/api/events', eventData)
        // this.calendarApi.addEvent(response.data)

        this.calendarApi.addEvent(newEvent)
        this.$emit('event-created', newEvent)

        this.showNotification('Événement créé avec succès', 'success')
      } catch (error) {
        this.showNotification('Erreur lors de la création', 'error')
        console.error('Error creating event:', error)
      }
    },

    async updateEvent(eventData) {
      try {
        // En production :
        // await this.$http.put(`/api/events/${eventData.id}`, eventData)

        const calendarEvent = this.calendarApi.getEventById(eventData.id)
        if (calendarEvent) {
          calendarEvent.setProp('title', eventData.title)
          calendarEvent.setStart(eventData.start)
          calendarEvent.setEnd(eventData.end)
          calendarEvent.setProp('backgroundColor', eventData.backgroundColor)
          calendarEvent.setExtendedProp('notes', eventData.notes)
        }

        this.$emit('event-updated', eventData)
        this.showNotification('Événement modifié avec succès', 'success')
      } catch (error) {
        this.showNotification('Erreur lors de la modification', 'error')
        console.error('Error updating event:', error)
      }
    },

    handleDeleteEvent(eventData) {
      this.eventToDelete = eventData
      this.showConfirmDelete = true
    },

    async confirmDelete() {
      try {
        // En production :
        // await this.$http.delete(`/api/events/${this.eventToDelete.id}`)

        const calendarEvent = this.calendarApi.getEventById(this.eventToDelete.id)
        if (calendarEvent) {
          calendarEvent.remove()
        }

        this.$emit('event-deleted', this.eventToDelete.id)
        this.showNotification('Événement supprimé', 'success')
      } catch (error) {
        this.showNotification('Erreur lors de la suppression', 'error')
        console.error('Error deleting event:', error)
      }

      this.showConfirmDelete = false
      this.closeModal()
    },

    // === NAVIGATION ET VUES ===
    changeView(viewName) {
      this.currentView = viewName
      this.calendarApi.changeView(viewName)
    },

    goToDate(date) {
      this.calendarApi.gotoDate(date)
    },

    // === HELPERS ===
    getEmptyEvent() {
      return {
        id: null,
        title: '',
        start: '',
        end: '',
        backgroundColor: '#3788d8',
        notes: '',
        type: 'reservation'
      }
    },

    extractEventData(calendarEvent) {
      return {
        id: calendarEvent.id,
        title: calendarEvent.title,
        start: calendarEvent.start?.toISOString(),
        end: calendarEvent.end?.toISOString(),
        backgroundColor: calendarEvent.backgroundColor,
        notes: calendarEvent.extendedProps?.notes || '',
        type: calendarEvent.extendedProps?.type || 'reservation',
        ...calendarEvent.extendedProps
      }
    },

    generateId() {
      return `event_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`
    },

    getEventClasses(eventInfo) {
      const type = eventInfo.event.extendedProps?.type || 'default'
      return [`event-${type}`]
    },

    renderEventContent(eventInfo) {
      const type = eventInfo.event.extendedProps?.type
      const icons = {
        reservation: 'fas fa-bed',
        animation: 'fas fa-music',
        maintenance: 'fas fa-tools',
        formation: 'fas fa-graduation-cap'
      }

      return {
        html: `
          <div class="event-content">
            <i class="${icons[type] || 'fas fa-calendar'}"></i>
            <span class="event-title">${eventInfo.event.title}</span>
            <span class="event-time">${eventInfo.timeText}</span>
          </div>
        `
      }
    },

    openCreateModal() {
      this.currentEvent = this.getEmptyEvent()
      this.isEditing = false
      this.showModal = true
    },

    closeModal() {
      this.showModal = false
      this.currentEvent = this.getEmptyEvent()
    },

    showEventDetails(event) {
      // Pour le mode client - afficher les détails en lecture seule
      this.$emit('show-event-details', event)
    },

    updateStatsForPeriod(start, end) {
      // Calculer les stats pour la période visible
      // En production, faire un appel API
    },

    showNotification(message, type) {
      // Intégrer avec votre système de notifications
      this.$emit('notification', { message, type })
    }
  }
}
</script>

<style scoped>
.calendar-container {
  background: white;
  border-radius: 12px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
  overflow: hidden;
}

/* Header */
.calendar-header {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  padding: 1rem;
  color: white;
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 1rem;
}

.header-left .calendar-title {
  font-size: 1.75rem;
  font-weight: 600;
  margin: 0 0 0.5rem 0;
}

.calendar-subtitle {
  margin: 0;
  opacity: 0.9;
  font-size: 0.95rem;
}

.header-actions {
  display: flex;
  gap: 1rem;
  align-items: center;
  flex-wrap: wrap;
}

.view-switcher {
  display: flex;
  background: rgba(255, 255, 255, 0.1);
  border-radius: 8px;
  padding: 4px;
}

.view-btn {
  background: none;
  border: none;
  color: white;
  padding: 0.5rem 1rem;
  border-radius: 6px;
  font-size: 0.875rem;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s ease;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.view-btn:hover {
  background: rgba(255, 255, 255, 0.1);
}

.view-btn.active {
  background: rgba(255, 255, 255, 0.2);
  color: white;
}

.btn-create {
  background: rgba(255, 255, 255, 0.15);
  border: 1px solid rgba(255, 255, 255, 0.3);
  color: white;
  padding: 0.6rem 1.2rem;
  border-radius: 8px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.btn-create:hover {
  background: rgba(255, 255, 255, 0.25);
  transform: translateY(-1px);
}

/* Stats Cards */
.calendar-stats {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1.5rem;
  padding: 2rem;
  background: #f8f9fa;
}

.stat-card {
  background: white;
  padding: 1.5rem;
  border-radius: 10px;
  display: flex;
  align-items: center;
  gap: 1rem;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
}

.stat-icon {
  width: 50px;
  height: 50px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 1.2rem;
}

.stat-icon.reservations {
  background: linear-gradient(135deg, #28a745, #20c997);
}

.stat-icon.events {
  background: linear-gradient(135deg, #ffc107, #fd7e14);
}

.stat-icon.occupancy {
  background: linear-gradient(135deg, #6f42c1, #e83e8c);
}

.stat-content {
  display: flex;
  flex-direction: column;
}

.stat-number {
  font-size: 1.5rem;
  font-weight: 700;
  color: #2d3748;
}

.stat-label {
  font-size: 0.875rem;
  color: #718096;
}

/* Calendar */
.calendar-wrapper {
  padding: 2rem;
}

/* Personnalisation FullCalendar */
:deep(.fc) {
  font-family: var(--font-family-primary);
}

:deep(.fc-event) {
  border-radius: 6px;
  border: none;
  padding: 2px 6px;
  font-weight: 500;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

:deep(.fc-daygrid-event) {
  margin: 1px;
}

:deep(.fc-day-today) {
  background-color: rgba(102, 126, 234, 0.08) !important;
}

:deep(.fc-highlight) {
  background-color: rgba(102, 126, 234, 0.15) !important;
}

/* Event Types */
:deep(.event-reservation) {
  background: linear-gradient(135deg, #28a745, #20c997) !important;
}

:deep(.event-animation) {
  background: linear-gradient(135deg, #ffc107, #fd7e14) !important;
}

:deep(.event-maintenance) {
  background: linear-gradient(135deg, #dc3545, #e83e8c) !important;
}

:deep(.event-formation) {
  background: linear-gradient(135deg, #6f42c1, #6610f2) !important;
}

.event-content {
  display: flex;
  align-items: center;
  gap: 0.25rem;
  font-size: 0.75rem;
}

.event-title {
  font-weight: 600;
}

.event-time {
  opacity: 0.8;
  font-size: 0.7rem;
}

/* Responsive */
@media (max-width: 768px) {
  .calendar-header {
    flex-direction: column;
    text-align: center; 
  }

  .view-switcher {
    order: 1;
  }

  .btn-create {
    order: 2;
  }

  .calendar-stats {
    grid-template-columns: 1fr;
    padding: 1rem;
  }

  .calendar-wrapper {
    padding: 1rem;
  }

  :deep(.fc-toolbar) {
    flex-direction: column;
    gap: 0.5rem;
  }
}

/* === LARGEUR SUFFISANTE POUR LIRE "TOUTE LA JOURNÉE" === */
:deep(.fc-timegrid-axis) {
  min-width: 190px !important;
  width: 190px !important;
  max-width: fit-content !important;
}

:deep(.fc-timegrid-axis-cushion) {
  text-align: center !important;
  font-size: 0.9rem !important;
  line-height: .9 !important;
  font-weight: 700;
  color: #667eea !important;
}

:deep(.fc-timegrid-slot-label-cushion) {
    font-weight: 700;
    padding: .7rem;
}
</style>