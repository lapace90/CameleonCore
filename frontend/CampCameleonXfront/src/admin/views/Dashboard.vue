<template>
  <div class="dashboard">
    <!-- Stats Cards -->
    <div class="stats-grid">
      <div class="stat-card" v-for="stat in stats" :key="stat.id">
        <div class="stat-content">
          <div class="stat-icon" :style="{ backgroundColor: stat.color }">
            <i :class="stat.icon"></i>
          </div>
          <div class="stat-info">
            <h3 class="stat-number">{{ stat.value }}</h3>
            <p class="stat-label">{{ stat.label }}</p>
            <div class="stat-change" :class="stat.changeType">
              <i :class="stat.changeIcon"></i>
              {{ stat.change }}
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Main Content Grid -->
    <div class="admin-main-content">
      <!-- Charts Section -->
      <div class="charts-section">
        <div class="chart-card">
          <div class="card-header">
            <h3>Réservations par mois</h3>
            <span class="chart-subtitle">Évolution des réservations</span>
          </div>
          <div class="chart-placeholder">
            <i class="fas fa-chart-line chart-icon"></i>
            <p>Graphique des réservations</p>
            <small>Intégration Chart.js à venir</small>
          </div>
        </div>

        <div class="chart-card">
          <div class="card-header">
            <h3>Répartition des services</h3>
            <span class="chart-subtitle">Services les plus demandés</span>
          </div>
          <div class="chart-placeholder">
            <i class="fas fa-chart-pie chart-icon"></i>
            <p>Graphique circulaire</p>
            <small>Intégration Chart.js à venir</small>
          </div>
        </div>
      </div>

      <!-- Calendar Widget -->
      <div class="calendar-section">
        <CalendarWidget 
          title="Agenda de la semaine" 
          :show-actions="true" 
          :show-upcoming="true" 
          :max-upcoming-events="4"
          @event-clicked="handleEventClick" 
          @date-selected="handleDateSelect" 
          @event-created="handleEventCreated" 
        />
      </div>
    </div>

    <!-- Recent Activity -->
    <div class="activity-section">
      <div class="activity-card">
        <div class="card-header">
          <h3>Activité récente</h3>
          <a href="#" class="view-all">Voir tout</a>
        </div>
        <div class="activity-list">
          <div class="activity-item" v-for="activity in recentActivity" :key="activity.id">
            <div class="activity-icon" :style="{ backgroundColor: activity.color }">
              <i :class="activity.icon"></i>
            </div>
            <div class="activity-content">
              <p class="activity-text">{{ activity.text }}</p>
              <span class="activity-time">{{ activity.time }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Modales (en dehors du contenu principal) -->
    <EventModal 
      :show="showEventModal" 
      :event="selectedEvent" 
      :is-editing="isEditing" 
      @save="handleSaveEvent"
      @delete="handleDeleteEvent" 
      @close="closeEventModal" 
    />

    <ConfirmModal 
      :show="showConfirmDelete" 
      title="Supprimer l'événement"
      message="Êtes-vous sûr de vouloir supprimer cet événement ? Cette action est irréversible." 
      @confirm="confirmDelete"
      @cancel="showConfirmDelete = false" 
    />
  </div>
</template>

<script>
import CalendarWidget from './CalendarWidget.vue'
import EventModal from '@/shared/components/calendar/EventModal.vue'
import ConfirmModal from '@/shared/components/calendar/ConfirmModal.vue'

export default {
  name: 'Dashboard',
  components: {
    CalendarWidget,
    EventModal,
    ConfirmModal
  },
  data() {
    return {
      selectedEvent: null,
      showEventModal: false,
      isEditing: false,
      showConfirmDelete: false,
      eventToDelete: null,
      stats: [
        {
          id: 1,
          label: 'Réservations',
          value: '2,421',
          change: '+16%',
          changeType: 'positive',
          changeIcon: 'fas fa-arrow-up',
          icon: 'fas fa-calendar-check',
          color: '#5e72e4'
        },
        {
          id: 2,
          label: 'Revenus',
          value: '€24,300',
          change: '+8%',
          changeType: 'positive',
          changeIcon: 'fas fa-arrow-up',
          icon: 'fas fa-euro-sign',
          color: '#2dce89'
        },
        {
          id: 3,
          label: 'Utilisateurs',
          value: '1,893',
          change: '+12%',
          changeType: 'positive',
          changeIcon: 'fas fa-arrow-up',
          icon: 'fas fa-users',
          color: '#11cdef'
        },
        {
          id: 4,
          label: 'Taux occupation',
          value: '87%',
          change: '-2%',
          changeType: 'negative',
          changeIcon: 'fas fa-arrow-down',
          icon: 'fas fa-chart-bar',
          color: '#fb6340'
        }
      ],
      recentActivity: [
        {
          id: 1,
          text: 'Nouvelle réservation de Jean Dupont',
          time: 'Il y a 2 minutes',
          icon: 'fas fa-calendar-plus',
          color: '#5e72e4'
        },
        {
          id: 2,
          text: 'Paiement reçu - Réservation #1234',
          time: 'Il y a 15 minutes',
          icon: 'fas fa-credit-card',
          color: '#2dce89'
        },
        {
          id: 3,
          text: 'Nouvel utilisateur inscrit',
          time: 'Il y a 32 minutes',
          icon: 'fas fa-user-plus',
          color: '#11cdef'
        },
        {
          id: 4,
          text: 'Message de contact reçu',
          time: 'Il y a 1 heure',
          icon: 'fas fa-envelope',
          color: '#fb6340'
        }
      ]
    }
  },
  methods: {
    // Gestion du widget calendrier
    handleEventClick(event) {
      console.log('Événement cliqué:', event)
      this.selectedEvent = event
      this.isEditing = true
      this.showEventModal = true
    },

    handleQuickAdd() {
      this.selectedEvent = {
        id: null,
        title: '',
        start: new Date().toISOString(),
        end: new Date(Date.now() + 60 * 60 * 1000).toISOString(), // 1h après
        backgroundColor: '#3788d8',
        type: 'reservation'
      }
      this.isEditing = false
      this.showEventModal = true
    },

    handleSaveEvent(eventData) {
      if (this.isEditing) {
        console.log('Événement modifié :', eventData)
      } else {
        console.log('Événement ajouté :', eventData)
      }
      this.closeEventModal()
    },

    handleDeleteEvent(eventData) {
      this.eventToDelete = eventData
      this.showConfirmDelete = true
    },

    confirmDelete() {
      console.log('Suppression de :', this.eventToDelete)
      this.showConfirmDelete = false
      this.closeEventModal()
    },

    handleDateSelect(date) {
      console.log('Date sélectionnée:', date)
    },

    handleEventCreated(event) {
      console.log('Création d\'événement demandée:', event)
      // Si event contient une date/heure, l'utiliser, sinon utiliser l'heure actuelle
      const startDate = event?.start || event?.date || new Date()
      
      this.selectedEvent = {
        id: null,
        title: '',
        start: startDate instanceof Date ? startDate.toISOString() : startDate,
        end: new Date(new Date(startDate).getTime() + 60 * 60 * 1000).toISOString(), // 1h après
        backgroundColor: '#3788d8',
        type: 'reservation'
      }
      this.isEditing = false
      this.showEventModal = true
    },

    closeEventModal() {
      this.selectedEvent = null
      this.showEventModal = false
      this.isEditing = false
    },

    formatEventDate(dateStr) {
      return new Date(dateStr).toLocaleDateString('fr-FR', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
      })
    }
  }
}
</script>

<style scoped>
.dashboard {
  display: flex;
  flex-direction: column;
  gap: 2rem;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 1.5rem;
}

.admin-main-content {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 2rem;
  min-height: fit-content;
}

/* .stat-card {
  background: white;
  border-radius: 0.75rem;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.12);
  border: 1px solid #e9ecef;
  overflow: hidden;
  transition: all 0.15s ease;
} */

.stat-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.15);
}

.stat-content {
  padding: 1.5rem;
  display: flex;
  align-items: center;
  gap: 1rem;
}

.stat-icon {
  width: 3rem;
  height: 3rem;
  border-radius: 0.5rem;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 1.25rem;
  flex-shrink: 0;
}

.stat-info {
  flex: 1;
}

.stat-number {
  font-size: 1.75rem;
  font-weight: 700;
  color: #32325d;
  margin: 0 0 0.25rem 0;
}

.stat-label {
  color: #8898aa;
  margin: 0 0 0.5rem 0;
  font-weight: 500;
}

.stat-change {
  font-size: 0.875rem;
  font-weight: 600;
  display: flex;
  align-items: center;
  gap: 0.25rem;
}

.stat-change.positive {
  color: #2dce89;
}

.stat-change.negative {
  color: #f5365c;
}

.charts-section {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.chart-card,
.activity-card {
  background: white;
  border-radius: 0.75rem;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.12);
  border: 1px solid #e9ecef;
  overflow: hidden;
}

.card-header {
  padding: 1.5rem 1.5rem 1rem;
  border-bottom: 1px solid #f8f9fe;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.card-header h3 {
  color: #32325d;
  font-size: 1.125rem;
  font-weight: 600;
  margin: 0;
}

.chart-subtitle {
  color: #8898aa;
  font-size: 0.875rem;
}

.view-all {
  color: #5e72e4;
  text-decoration: none;
  font-weight: 500;
  font-size: 0.875rem;
}

.chart-placeholder {
  padding: 3rem;
  text-align: center;
  color: #8898aa;
  background: linear-gradient(135deg, #f8f9fe 0%, #f1f2f6 100%);
}

.chart-icon {
  font-size: 3rem;
  margin-bottom: 1rem;
  opacity: 0.5;
}

.chart-placeholder p {
  margin: 0 0 0.5rem 0;
  font-weight: 500;
}

.chart-placeholder small {
  color: #adb5bd;
}

.activity-section {
  grid-column: 1 / -1;
}

.activity-list {
  padding: 0;
}

.activity-item {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1rem 1.5rem;
  border-bottom: 1px solid #f8f9fe;
  transition: background-color 0.15s ease;
}

.activity-item:hover {
  background-color: #f8f9fe;
}

.activity-item:last-child {
  border-bottom: none;
}

.activity-icon {
  width: 2.5rem;
  height: 2.5rem;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 0.875rem;
  flex-shrink: 0;
}

.activity-content {
  flex: 1;
}

.activity-text {
  margin: 0 0 0.25rem 0;
  color: #32325d;
  font-weight: 500;
}

.activity-time {
  color: #8898aa;
  font-size: 0.875rem;
}

/* Responsive */
@media (max-width: 1024px) {
  .main-content {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 768px) {
  .stats-grid {
    grid-template-columns: 1fr;
  }

  .charts-section {
    gap: 1rem;
  }

  .stat-content {
    padding: 1rem;
  }

  .chart-placeholder {
    padding: 2rem;
  }
}
</style>