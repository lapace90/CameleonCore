<template>
  <div class="calendar-container content-wrapper">
    <!-- Header du calendrier -->
    <div class="calendar-header">
      <div class="header-left">
        <!-- <h2 class="calendar-title"></h2> -->
        <p class="calendar-subtitle">Gestion des réservations et événements</p>
      </div>
      <div class="header-actions">
        <!-- AJOUT : Boutons de navigation -->
        <div class="calendar-navigation">
          <button @click="navigatePrev" class="nav-btn" title="Période précédente">
            <i class="fas fa-chevron-left"></i>
          </button>

          <button @click="navigateToday" class="today-btn">
            Aujourd'hui
          </button>

          <button @click="navigateNext" class="nav-btn" title="Période suivante">
            <i class="fas fa-chevron-right"></i>
          </button>
        </div>
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
    <!-- Dans le template de FullCalendar.vue -->
    <div class="calendar-stats" v-if="showStats">
      <div class="stat-card">
        <div class="stat-icon reservations">
          <i class="fas fa-calendar-alt"></i>
        </div>
        <div class="stat-content">
          <span class="stat-number">{{ stats.totalReservations }}</span>
          <span class="stat-label">Réservations totales</span>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon occupancy">
          <i class="fas fa-users"></i>
        </div>
        <div class="stat-content">
          <span class="stat-number">{{ stats.currentGuests }}</span>
          <span class="stat-label">Clients présents</span>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon info">
          <i class="fas fa-sign-in-alt"></i>
        </div>
        <div class="stat-content">
          <span class="stat-number">{{ stats.checkinsToday }}</span>
          <span class="stat-label">Arrivées aujourd'hui</span>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon warning">
          <i class="fas fa-sign-out-alt"></i>
        </div>
        <div class="stat-content">
          <span class="stat-number">{{ stats.checkoutsToday }}</span>
          <span class="stat-label">Départs aujourd'hui</span>
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
    <!-- Modal détails événement (lecture seule) -->
    <EventDetailsModal v-model="showDetails" :event="selectedEvent" />

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
import EventDetailsModal from './EventDetailsModal.vue'

export default {
  name: 'FullAgenda',
  components: {
    FullCalendar,
    EventModal,
    EventDetailsModal,
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
      showDetails: false,
      selectedDetail: null,
      selectedEvent: null,

      availableViews: [
        { value: 'dayGridMonth', label: 'Mois', icon: 'fas fa-calendar' },
        { value: 'timeGridWeek', label: 'Semaine', icon: 'fas fa-calendar-week' },
        { value: 'timeGridDay', label: 'Jour', icon: 'fas fa-calendar-day' }
      ],

      stats: {
        totalReservations: 0,
        currentGuests: 0,
        checkinsToday: 0,
        checkoutsToday: 0
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
    // Navigation du calendrier
    navigatePrev() {
      const calendar = this.$refs.calendar
      if (calendar && calendar.calendar) {
        calendar.calendar.prev()
      }
    },

    navigateNext() {
      const calendar = this.$refs.calendar
      if (calendar && calendar.calendar) {
        calendar.calendar.next()
      }
    },

    navigateToday() {
      const calendar = this.$refs.calendar
      if (calendar && calendar.calendar) {
        calendar.calendar.today()
      }
    },

    // =============================
    // CHARGEMENT ÉVÉNEMENTS UNIFIÉ
    // =============================

    async fetchEventsFromApi(info, successCallback, failureCallback) {
      try {
        this.isLoading = true

        const startDate = info.start.toISOString().split('T')[0]
        const endDate = info.end.toISOString().split('T')[0]

        const events = await AdminApi.getCalendarEvents(startDate, endDate)

        // Calculer les stats depuis les événements
        this.updateStatsFromEvents(events)

        successCallback(events)
      } catch (error) {
        console.error('❌ Erreur chargement événements:', error)
        failureCallback(error)
      } finally {
        this.isLoading = false
      }
    },

    updateStatsFromEvents(events) {
      const reservations = events.filter(e => e.type === 'reservation')
      const otherEvents = events.filter(e => e.type !== 'reservation')

      this.stats = {
        reservations: reservations.length,
        events: otherEvents.length,
        occupancy: Math.round((reservations.length / 30) * 100) // Exemple: sur 30 chambres
      }
    },

    async loadInitialStats() {
      try {
        const stats = await AdminApi.getDashboardStats()

        console.log('📊 Stats reçues:', stats)

        this.stats = {
          totalReservations: stats.reservations.total || 0,
          currentGuests: stats.occupancy.current_guests || 0,
          checkinsToday: stats.occupancy.checkins_today || 0,
          checkoutsToday: stats.occupancy.checkouts_today || 0
        }
      } catch (error) {
        console.error('❌ Erreur chargement stats:', error)
        // Valeurs par défaut
        this.stats = {
          totalReservations: 0,
          currentGuests: 0,
          checkinsToday: 0,
          checkoutsToday: 0
        }
      }
    },

    // =============================
    // GESTION ÉVÉNEMENTS CALENDRIER
    // =============================

    handleDateSelect(selectInfo) {
      if (this.mode !== 'admin') return

      this.currentEvent = {
        ...this.getEmptyEvent(),
        start: selectInfo.start?.toISOString().slice(0, 16) || '',
        end: selectInfo.end?.toISOString().slice(0, 16) || ''
      }

      this.isEditing = false
      this.showModal = true

      console.log('📅 Sélection date:', selectInfo)
    },

    handleEventClick(info) {
      // ⛔️ block browser navigation / default FC behavior
      if (info && info.jsEvent) {
        info.jsEvent.preventDefault();
        info.jsEvent.stopPropagation();
      }

      const e = info.event
      this.selectedEvent = {
        title: e.title,
        id: e.id,
        start: e.start,
        end: e.end,
        props: e.extendedProps,
        backgroundColor: e.backgroundColor
      }
      this.showDetails = true
    },

    async handleEventDrop(dropInfo) {
      if (this.mode !== 'admin') return

      try {
        const eventId = dropInfo.event.id
        const newStart = dropInfo.event.start
        const newEnd = dropInfo.event.end

        console.log('📅 Déplacement événement:', { eventId, newStart, newEnd })

        // Mettre à jour via l'API
        await AdminApi.updateEventOrReservation(eventId, {
          start_date: newStart.toISOString(),
          end_date: newEnd?.toISOString(),
          checkin: newStart.toISOString(),
          checkout: newEnd?.toISOString()
        })

      } catch (error) {
        console.error('❌ Erreur déplacement:', error)
        dropInfo.revert() // Annuler le déplacement
      }
    },

    async handleEventResize(resizeInfo) {
      if (this.mode !== 'admin') return

      try {
        const eventId = resizeInfo.event.id
        const newEnd = resizeInfo.event.end

        await AdminApi.updateEventOrReservation(eventId, {
          end_date: newEnd.toISOString(),
          checkout: newEnd.toISOString()
        })

      } catch (error) {
        console.error('❌ Erreur redimensionnement:', error)
        resizeInfo.revert()
      }
    },

    // =============================
    // SAUVEGARDE ÉVÉNEMENTS
    // =============================
    async handleSaveEvent(eventData) {
      console.log('💾 Sauvegarde événement:', eventData)

      try {
        if (eventData.type === 'reservation') {
          // CRÉATION D'UNE RÉSERVATION ADMIN directe
          const reservationPayload = {
            // Informations de base
            checkin: eventData.start,
            checkout: eventData.end,
            amount: parseFloat(eventData.amount || 0).toFixed(2),
            comment: eventData.comment,

            // Informations client - CRÉATION AUTOMATIQUE
            customer_data: {
              email: eventData.customerEmail,
              name: eventData.customerName,
              last_name: eventData.customerLastName,
              phone: eventData.phone,
              address: eventData.customerAddress,
              city: eventData.customerCity,
              postal_code: eventData.customerPostalCode,
              country: eventData.customerCountry
            },

            // Détails séjour
            number_of_adults: eventData.numberOfAdults || 2,
            number_of_children: eventData.numberOfChildren || 0,

            // Produits sélectionnés
            product_ids: eventData.product_ids || [],
            product_id: eventData.product_ids?.[0],// Premier produit comme principal

            // Métadonnées admin
            booking_source: eventData.bookingSource || 'admin',
            payment_status: eventData.paymentStatus || 'pending',
            payment_method: eventData.paymentMethod,
            status: eventData.status || 'confirmed'
          }

          console.log('📝 Création réservation admin:', reservationPayload)

          if (this.isEditing && eventData.id) {
            // Mise à jour existante
            const reservationId = eventData.id.replace('reservation_', '') // Enlever préfixe
            await AdminApi.updateReservation(reservationId, reservationPayload)
          } else {
            // Nouvelle réservation
            await AdminApi.createReservation(reservationPayload)
          }

        } else {
          // Événements génériques (maintenance, animation, autre)
          const toISO = (s) => (s ? new Date(s).toISOString() : null);

          // juste au-dessus de la construction du payload
          const mapEventType = (t) => {
            const key = String(t || '').toLowerCase().trim();
            return ({
              'activite': 'animation',
              'activité': 'animation',
              'activity': 'animation',
              'maintenance': 'maintenance',
              'reservation': 'reservation',
              'réservation': 'reservation',
              'event': 'event',
              'evenement': 'event',
              'évènement': 'event',
              'autre': 'event',
              'other': 'event',
            }[key]) || 'event';
          };

          const eventPayload = {
            title: (eventData.title || '').trim(),
            description: (eventData.description || '').trim(),
            notes: (eventData.notes || '').trim() || null,
            start_date: toISO(eventData.start || eventData.start_date?.slice(0, 16)),
            end_date: toISO(eventData.end || eventData.end_date?.slice(0, 16)),
            type: mapEventType(eventData.type),
            location: eventData.location || '',
            capacity: Number.isFinite(+eventData.capacity) ? Number(eventData.capacity) : null,
            animator: eventData.responsible || eventData.animator || null,
            technician: eventData.technician || null,
            priority: eventData.priority || 'medium',
            status: (eventData.status && eventData.status !== 'pending') ? eventData.status : 'active',
            background_color: eventData.backgroundColor || eventData.background_color || '#28a745',
          };

          if (this.isEditing && eventData.id) {
            await AdminApi.updateEvent(eventData.id, eventPayload);
          } else {
            await AdminApi.createEvent(eventPayload);
          }
        }

        this.refreshCalendar()
        this.closeModal()
        console.log('✅ Événement sauvegardé avec succès')

      } catch (error) {
        console.error('❌ Erreur sauvegarde:', error)

        // Affichage de l'erreur à l'utilisateur
        const errorMessage = error.response?.data?.message || error.message || 'Erreur inconnue'
        alert(`Erreur lors de la sauvegarde: ${errorMessage}`)
      }
    },

    prepareEventData(eventData) {
      const baseData = {
        title: eventData.title,
        status: eventData.status || 'active',
        notes: eventData.notes || ''
      }

      // Si réservation
      if (eventData.type === 'reservation') {
        return {
          ...baseData,
          checkin: eventData.start ? new Date(eventData.start).toISOString() : null,
          checkout: eventData.end ? new Date(eventData.end).toISOString() : null,
          amount: parseFloat(eventData.amount || 0).toFixed(2),
          comment: eventData.notes,
          number_of_adults: parseInt(eventData.guests || 1),
          number_of_children: 0,
          payment_status: 'pending',
          booking_source: 'admin',
          customer_id: 1 // Temporaire
        }
      }

      // Sinon événement générique
      return {
        ...baseData,
        start_date: eventData.start ? new Date(eventData.start).toISOString() : null,
        end_date: eventData.end ? new Date(eventData.end).toISOString() : null,
        type: eventData.type || 'autre',
        location: eventData.location || '',
        responsible: eventData.responsible || '',
        background_color: eventData.backgroundColor || this.getColorForType(eventData.type)
      }
    },

    getColorForType(type) {
      const colors = {
        reservation: '#28a745',
        activite: '#ffc107',
        maintenance: '#dc3545',
        autre: '#17a2b8'
      }
      return colors[type] || colors.autre
    },

    // =============================
    // ACTIONS MODALES
    // =============================

    openCreateModal() {
      console.log('🆕 Ouverture modal création')
      this.currentEvent = this.getEmptyEvent()
      this.isEditing = false
      this.showModal = true
    },

    handleDeleteEvent() {
      this.eventToDelete = this.currentEvent
      this.showConfirmDelete = true
    },

    async confirmDelete() {
      if (!this.eventToDelete) return

      try {
        await AdminApi.deleteEventOrReservation(this.eventToDelete.id, this.eventToDelete.type)
        this.showConfirmDelete = false
        this.eventToDelete = null
        this.refreshCalendar()
        console.log('✅ Événement supprimé')
      } catch (error) {
        console.error('❌ Erreur suppression:', error)
      }
    },

    closeModal() {
      this.showModal = false
      this.currentEvent = this.getEmptyEvent()
    },

    // =============================
    // UTILITAIRES
    // =============================

    getEmptyEvent() {
      return {
        id: null,
        title: '',
        start: new Date().toISOString().slice(0, 16),
        end: '',
        type: 'autre',
        backgroundColor: '#17a2b8',

        // Champs réservation
        customerName: '',
        phone: '',
        email: '',
        guests: 2,
        amount: 0,
        status: 'pending',

        // Champs événement
        location: '',
        responsible: '',
        notes: ''
      }
    },

    changeView(viewName) {
      this.currentView = viewName
      this.$refs.calendar.getApi().changeView(viewName)
    },

    handleDatesChange() {
      // Appelé quand la vue change de période
    },

    refreshCalendar() {
      console.log('Actualisation calendrier')

      try {
        const calendarRef = this.$refs.calendar
        if (calendarRef && typeof calendarRef.getApi === 'function') {
          calendarRef.getApi().refetchEvents()
          console.log('✅ Calendrier actualisé avec succès')
        } else {
          console.warn('⚠️ API calendrier non disponible')
        }
      } catch (error) {
        console.error('❌ Erreur actualisation calendrier:', error)
      }
    }
  }
}
</script>


<style scoped>
/* Navigation du calendrier */
.calendar-navigation {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-right: 1rem;
}

.nav-btn {
  background: rgba(255, 255, 255, 0.1);
  border: none;
  color: white;
  width: 36px;
  height: 36px;
  border-radius: 6px;
  cursor: pointer;
  transition: all 0.2s ease;
  display: flex;
  align-items: center;
  justify-content: center;
}

.nav-btn:hover {
  background: rgba(255, 255, 255, 0.2);
}

.today-btn {
  background: rgba(255, 255, 255, 0.1);
  border: none;
  color: white;
  padding: 0.5rem 1rem;
  border-radius: 6px;
  cursor: pointer;
  transition: all 0.2s ease;
  font-weight: 500;
}

.today-btn:hover {
  background: rgba(255, 255, 255, 0.2);
}

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

  .fc .fc-toolbar-title #fc-dom-1 {
    padding: 1rem;
    font-size: 0.5rem !important;
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