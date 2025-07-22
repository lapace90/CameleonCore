<template>
  <div class="admin-card">
    <div class="admin-content-wrapper">
      <main class="admin-main-content">
        <div class="calendar-page">
          <FullCalendar
            title="Calendrier du Camp Caméléon X"
            :show-stats="true"
            mode="admin"
            :external-events="reservations"
            @event-created="saveEventToAPI"
            @event-updated="updateEventInAPI"
            @event-deleted="deleteEventFromAPI"
            @notification="showNotification"
          />
        </div>
      </main>
    </div>
    
  </div>
</template>

<script>

import FullCalendar from '@/shared/components/calendar/FullCalendar.vue'

export default {
  name: 'AdminCalendar',
  components: {

    FullCalendar
  },
  data() {
    return {
      reservations: []
    }
  },
  async mounted() {
    await this.loadReservations()
  },
  methods: {
    async loadReservations() {
      try {
        // Remplacez par votre API
        const response = await this.$http.get('/api/reservations')
        this.reservations = response.data.map(reservation => ({
          id: reservation.id,
          title: `Réservation - ${reservation.customer_name}`,
          start: reservation.check_in,
          end: reservation.check_out,
          backgroundColor: '#28a745',
          extendedProps: {
            type: 'reservation',
            customerName: reservation.customer_name,
            phone: reservation.phone,
            guests: reservation.guests
          }
        }))
      } catch (error) {
        console.error('Erreur lors du chargement des réservations:', error)
      }
    },
    
    async saveEventToAPI(event) {
      try {
        const response = await this.$http.post('/api/events', event)
        this.showNotification('Événement créé avec succès', 'success')
        return response.data
      } catch (error) {
        this.showNotification('Erreur lors de la création', 'error')
        throw error
      }
    },
    
    async updateEventInAPI(event) {
      try {
        await this.$http.put(`/api/events/${event.id}`, event)
        this.showNotification('Événement modifié avec succès', 'success')
      } catch (error) {
        this.showNotification('Erreur lors de la modification', 'error')
        throw error
      }
    },
    
    async deleteEventFromAPI(eventId) {
      try {
        await this.$http.delete(`/api/events/${eventId}`)
        this.showNotification('Événement supprimé', 'success')
      } catch (error) {
        this.showNotification('Erreur lors de la suppression', 'error')
        throw error
      }
    },
    
    showNotification(message, type) {
      // Intégrez avec votre système de notifications
      // Par exemple avec vue-toastification :
      // this.$toast[type](message)
      console.log(`${type.toUpperCase()}: ${message}`)
    }
  }
}
</script>

<style scoped>
.calendar-page {
  padding: 2rem;
  max-width: 1400px;
  margin: 0 auto;
}

@media (max-width: 768px) {
  .calendar-page {
    padding: 1rem;
  }
}
</style>