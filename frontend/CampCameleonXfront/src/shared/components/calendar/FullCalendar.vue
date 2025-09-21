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
import AdminApi from '@/services/AdminApi'

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
      isLoading: false,
      notification: null,
      lastError: null,

      availableViews: [
        { value: 'dayGridMonth', label: 'Mois', icon: 'fas fa-calendar' },
        { value: 'timeGridWeek', label: 'Semaine', icon: 'fas fa-calendar-week' },
        { value: 'timeGridDay', label: 'Jour', icon: 'fas fa-calendar-day' }
      ],

      stats: {
        reservations: 0,
        events: 0,
        occupancy: 0
      },

      calendarOptions: {
        plugins: [dayGridPlugin, timeGridPlugin, interactionPlugin],
        locale: frLocale,
        initialView: 'dayGridMonth',
        headerToolbar: false, // Header custom
        height: 'auto',
        dayMaxEvents: 3,
        moreLinkClick: 'popover',
        nowIndicator: true,
        weekNumbers: false,
        businessHours: {
          daysOfWeek: [0, 1, 2, 3, 4, 5, 6],
          startTime: '08:00',
          endTime: '22:00'
        },
        editable: true,
        selectable: true,
        selectMirror: true,
        droppable: true,

        // ✅ CORRECTION : Utilisation du nouveau service AdminApi
        events: this.fetchEventsFromApi,

        select: this.handleDateSelect,
        eventClick: this.handleEventClick,
        eventDrop: this.handleEventDrop,
        eventResize: this.handleEventResize,
        datesSet: this.handleDatesChange
      }
    }
  },

  mounted() {
    console.log('🗓️ FullCalendar monté en mode:', this.mode)
    this.loadInitialStats()
  },

  methods: {
    /**
     * ✅ NOUVELLE MÉTHODE : Chargement événements via AdminApi
     */
    async fetchEventsFromApi(info, successCallback, failureCallback) {
      try {
        this.isLoading = true
        const raw = await AdminApi.getReservationsForCalendar({
          start: info.startStr,
          end: info.endStr
        })

        // 🔧 Normalisation pour ton composant + classes CSS + end inclusif (allDay)
        const events = (raw || []).map(ev => {
          const xp = ev.extendedProps || {}
          const customer = xp.customer || {}
          const status = xp.status || 'pending'

          // end exclusif → +1 jour pour un allDay afin d’inclure la dernière nuit
          let startISO = ev.start
          let endISO = ev.end || ev.start
          try {
            const start = new Date(ev.start)
            const end = new Date(endISO)
            // on considère que ce sont des séjours allDay : +1 jour pour l’affichage
            end.setDate(end.getDate() + 1)
            startISO = start.toISOString()
            endISO = end.toISOString()
          } catch (_) { }

          return {
            ...ev,
            start: startISO,
            end: endISO,
            allDay: true,
            // ✅ classes pour tes dégradés/couleurs CSS
            classNames: ['event-reservation', `status-${status}`],
            // ✅ props attendues par handleEventClick
            extendedProps: {
              ...xp,
              type: 'reservation',
              customerName: customer.name || null,
              email: customer.email || null,
              phone: customer.phone || null,
              guests: xp.people ?? xp.people_count ?? 1,
              pitchType: xp.pitchType || '',   // garde vide si non fourni
              amount: xp.amount ?? 0,
              status: status,
              notes: xp.notes || ''
            }
          }
        })

        // 📊 mets à jour les stats (tu as déjà une fonction prévue)
        this.updateStatsFromEvents(events)

        successCallback(events)
      } catch (err) {
        console.error('❌ Erreur chargement événements:', err)
        this.showNotification(err?.message || 'Erreur de chargement', 'error')
        failureCallback(err)
      } finally {
        this.isLoading = false
      }
    }
    ,
    async onEventDrop(dropInfo) {
      const { event } = dropInfo
      try {
        const eventId = event.id
        const newStart = event.start
        const newEnd = event.end || newStart

        await AdminApi.updateReservation(eventId, {
          checkin: newStart.toISOString(),
          checkout: newEnd.toISOString()
        })

        this.showNotification('Événement déplacé avec succès', 'success')
      } catch (e) {
        console.error('❌ Erreur déplacement:', e)
        dropInfo.revert()
        this.showNotification(e?.message || 'Impossible de déplacer', 'error')
      }
    },
    /**
     * Mise à jour des statistiques depuis les événements
     */
    updateStatsFromEvents(events) {
      this.stats.events = events.length
      this.stats.reservations = events.filter(e => e.type === 'reservation').length

      // Calcul occupation approximatif
      const today = new Date()
      const todayEvents = events.filter(e => {
        const eventDate = new Date(e.start)
        return eventDate.toDateString() === today.toDateString()
      })

      this.stats.occupancy = Math.min(100, Math.round((todayEvents.length / 20) * 100))
    },

    /**
     * ✅ NOUVELLE MÉTHODE : Chargement stats initiales
     */
    async loadInitialStats() {
      try {
        const stats = await AdminApi.getDashboardStats()

        this.stats = {
          reservations: stats.reservations_today || 0,
          events: stats.total_events || 0,
          occupancy: stats.occupancy_rate || 0
        }

        console.log('📊 Stats calendrier chargées:', this.stats)

      } catch (error) {
        console.warn('⚠️ Impossible de charger les stats:', error)
        // Garder les stats par défaut
      }
    },

    /**
     * Gestion des événements FullCalendar
     */
    handleDateSelect(selectInfo) {
      if (this.mode !== 'admin') return

      this.currentEvent = {
        ...this.getEmptyEvent(),
        start: selectInfo.start,
        end: selectInfo.end || selectInfo.start
      }

      this.isEditing = false
      this.showModal = true

      console.log('📅 Sélection date:', selectInfo)
    },

    handleEventClick(clickInfo) {
      const event = clickInfo.event

      this.currentEvent = {
        id: event.id,
        title: event.title,
        start: event.start,
        end: event.end,
        type: event.extendedProps.type || 'reservation',
        customerName: event.extendedProps.customerName || '',
        email: event.extendedProps.email || '',
        phone: event.extendedProps.phone || '',
        guests: event.extendedProps.guests || 1,
        pitchType: event.extendedProps.pitchType || '',
        amount: event.extendedProps.amount || 0,
        status: event.extendedProps.status || 'pending',
        notes: event.extendedProps.notes || ''

      }

      this.isEditing = true
      this.showModal = true

      console.log('🖱️ Clic événement:', this.currentEvent)
    },

    async handleEventDrop(dropInfo) {
      if (this.mode !== 'admin') return

      try {
        const eventId = dropInfo.event.id
        const newStart = dropInfo.event.start
        const newEnd = dropInfo.event.end

        console.log('📅 Déplacement événement:', { eventId, newStart, newEnd })

        // Appel API pour mise à jour
        await AdminApi.updateReservation(eventId, {
          checkin: newStart.toISOString(),
          checkout: newEnd?.toISOString() || newStart.toISOString()
        })

        this.showNotification('Événement déplacé avec succès', 'success')

      } catch (error) {
        console.error('❌ Erreur déplacement:', error)
        dropInfo.revert() // Annuler le déplacement
        this.showNotification('Erreur lors du déplacement', 'error')
      }
    },

    async handleEventResize(resizeInfo) {
      if (this.mode !== 'admin') return

      try {
        const eventId = resizeInfo.event.id
        const newEnd = resizeInfo.event.end

        console.log('📏 Redimensionnement événement:', { eventId, newEnd })

        await AdminApi.updateReservation(eventId, {
          checkout: newEnd.toISOString()
        })

        this.showNotification('Durée modifiée avec succès', 'success')

      } catch (error) {
        console.error('❌ Erreur redimensionnement:', error)
        resizeInfo.revert()
        this.showNotification('Erreur lors de la modification', 'error')
      }
    },

    async handleEventSave(eventData) {
      try {
        this.isLoading = true

        if (this.isEditing) {
          // Mise à jour
          await AdminApi.updateReservation(eventData.id, eventData)
          console.log('✅ Événement mis à jour:', eventData.id)
        } else {
          // Création
          await AdminApi.createReservation(eventData)
          console.log('✅ Événement créé')
        }

        this.closeModal()
        this.refreshCalendar()
        this.showNotification(
          this.isEditing ? 'Événement mis à jour' : 'Événement créé',
          'success'
        )

      } catch (error) {
        console.error('❌ Erreur sauvegarde:', error)
        this.showNotification('Erreur lors de la sauvegarde', 'error')
      } finally {
        this.isLoading = false
      }
    },

    async confirmDelete() {
      if (!this.eventToDelete) return

      try {
        this.isLoading = true

        await AdminApi.deleteReservation(this.eventToDelete.id)

        this.showConfirmDelete = false
        this.eventToDelete = null
        this.refreshCalendar()
        this.showNotification('Événement supprimé', 'success')

      } catch (error) {
        console.error('❌ Erreur suppression:', error)
        this.showNotification('Erreur lors de la suppression', 'error')
      } finally {
        this.isLoading = false
      }
    },

         /**
     * Utilitaires
     */
    getEmptyEvent() {
      return {
        id: null,
        title: '',
        start: null,
        end: null,
        type: 'reservation',
        customerName: '',
        phone: '',
        email: '',
        guests: 1,
        pitchType: '',
        amount: 0,
        status: 'pending',
        notes: ''
      }
    },

    changeView(viewName) {
      this.currentView = viewName;
      this.$refs.fullCalendar.getApi().changeView(viewName);
    },

    handleDateSelect(selectInfo) {
      if (this.mode !== 'admin') return;
      this.currentEvent = {
        ...this.getEmptyEvent(),
        start: selectInfo.start,
        end: selectInfo.end
      };
      this.isEditing = false;
      this.showModal = true;
    },

    handleEventClick(clickInfo) {
      const event = clickInfo.event;
      this.currentEvent = {
        id: event.id,
        title: event.title,
        start: event.start,
        end: event.end,
        type: event.extendedProps.type || 'reservation',
        customerName: event.extendedProps.customer_name || '',
        phone: event.extendedProps.customer_phone || '',
        email: event.extendedProps.customer_email || '',
        guests: event.extendedProps.guests || 1,
        pitchType: event.extendedProps.product_type || '',
        comment: event.extendedProps.comment || '',
        amount: event.extendedProps.amount || 0
      };
      this.isEditing = true;
      this.showModal = true;
    },

    handleEventDrop(dropInfo) {
      if (this.mode !== 'admin') return;
      console.log('Événement déplacé:', dropInfo);
    },

    handleEventResize(resizeInfo) {
      if (this.mode !== 'admin') return;
      console.log('Événement redimensionné:', resizeInfo);
    },

    handleViewChange() {
      console.log('Vue changée:', this.currentView);
    },

    handleSaveEvent() {
      console.log('Sauvegarde événement:', this.currentEvent);
      this.showModal = false;
    },

    handleDeleteEvent() {
      this.eventToDelete = this.currentEvent;
      this.showConfirmDelete = true;
    },

    closeModal() {
      this.showModal = false;
      this.currentEvent = this.getEmptyEvent();
    },

    confirmDelete() {
      console.log('Suppression événement:', this.eventToDelete);
      this.showConfirmDelete = false;
      this.showModal = false;
      this.eventToDelete = null;
    },

    refreshCalendar() {
      console.log('Actualisation calendrier');
      this.$refs.fullCalendar.getApi().refetchEvents();
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