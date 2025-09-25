<template>
  <div class="reservation-detail-page">
    <!-- Loading -->
    <div v-if="loading" class="loading-state">
      <div class="spinner"></div>
      <p>Chargement de la réservation...</p>
      <p class="loading-hint">⏳ Cette opération peut prendre jusqu'à 30 secondes</p>
    </div>

    <!-- Error -->
    <div v-else-if="error" class="error-state">
      <div class="error-icon">❌</div>
      <h3>Erreur de chargement</h3>
      <p>{{ error }}</p>
      <button @click="fetchReservation" class="btn btn-primary">Réessayer</button>
    </div>

    <!-- Content -->
    <div v-else-if="reservation" class="reservation-content">
      <!-- Header -->
      <div class="page-header">
        <div class="header-left">
          <h1>Réservation #{{ reservation.id }}</h1>
          <p class="subtitle">{{ formatDate(reservation.checkin) }} → {{ formatDate(reservation.checkout) }} • {{ calculateNights() }} nuits</p>
        </div>
        <div class="header-actions">
          <button class="btn btn-secondary">
            <i class="fas fa-edit"></i>
            Modifier
          </button>
          <button @click="cancelReservation" :disabled="reservation.status === 'cancelled'" class="btn btn-danger">
            <i class="fas fa-times"></i>
            Annuler
          </button>
        </div>
      </div>

      <!-- Status badges -->
      <div class="status-row">
        <div class="status-badge" :class="getStatusClass(reservation.status)">
          <i :class="getStatusIcon(reservation.status)"></i>
          {{ getStatusLabel(reservation.status) }}
        </div>
        <div class="status-badge" :class="getPaymentStatusClass(reservation.paymentStatus)">
          <i :class="getPaymentStatusIcon(reservation.paymentStatus)"></i>
          {{ getPaymentStatusLabel(reservation.paymentStatus) }}
        </div>
      </div>

      <!-- Main info grid -->
      <div class="info-grid">
        <!-- Client -->
        <div class="info-card">
          <h3><i class="fas fa-user"></i> Client</h3>
          <div class="info-item">
            <label>Client:</label>
            <span>{{ getCustomerDisplay() }}</span>
          </div>
        </div>

        <!-- Séjour -->
        <div class="info-card">
          <h3><i class="fas fa-calendar"></i> Séjour</h3>
          <div class="info-item">
            <label>Arrivée:</label>
            <span>{{ formatDate(reservation.checkin) }}</span>
          </div>
          <div class="info-item">
            <label>Départ:</label>
            <span>{{ formatDate(reservation.checkout) }}</span>
          </div>
          <div class="info-item">
            <label>Durée:</label>
            <span>{{ calculateNights() }} nuits</span>
          </div>
        </div>

        <!-- Invités -->
        <div class="info-card">
          <h3><i class="fas fa-users"></i> Invités</h3>
          <div class="info-item">
            <label>Adultes:</label>
            <span>{{ reservation.numberOfAdults || 0 }}</span>
          </div>
          <div class="info-item">
            <label>Enfants:</label>
            <span>{{ reservation.numberOfChildren || 0 }}</span>
          </div>
          <div class="info-item">
            <label>Total:</label>
            <span>{{ getTotalGuests() }} personnes</span>
          </div>
        </div>

        <!-- Logement -->
        <div class="info-card">
          <h3><i class="fas fa-bed"></i> Logement</h3>
          <div class="info-item">
            <label>Type:</label>
            <span>{{ getProductTypeLabel() }}</span>
          </div>
        </div>

        <!-- Paiement -->
        <div class="info-card">
          <h3><i class="fas fa-credit-card"></i> Paiement</h3>
          <div class="info-item">
            <label>Montant:</label>
            <span class="amount">{{ formatCurrency(reservation.amount) }}</span>
          </div>
          <div class="info-item">
            <label>Méthode:</label>
            <span>{{ getPaymentMethodLabel() }}</span>
          </div>
          <div class="info-item">
            <label>Statut:</label>
            <span>{{ getPaymentStatusLabel(reservation.paymentStatus) }}</span>
          </div>
        </div>

        <!-- Références -->
        <div class="info-card">
          <h3><i class="fas fa-hashtag"></i> Références</h3>
          <div class="info-item">
            <label>Facture:</label>
            <span>{{ reservation.invoiceNumber || 'Non générée' }}</span>
          </div>
          <div class="info-item">
            <label>Devis:</label>
            <span>{{ reservation.quoteReference || 'Aucun' }}</span>
          </div>
          <div class="info-item">
            <label>Source:</label>
            <span>{{ getBookingSourceLabel() }}</span>
          </div>
        </div>
      </div>

      <!-- Notes -->
      <div v-if="reservation.comment" class="notes-section">
        <h3><i class="fas fa-sticky-note"></i> Commentaires</h3>
        <div class="comment-box">
          {{ reservation.comment }}
        </div>
      </div>

      <!-- Dates importantes -->
      <div class="dates-section">
        <h3><i class="fas fa-clock"></i> Historique</h3>
        <div class="timeline">
          <div class="timeline-item">
            <div class="timeline-icon success">
              <i class="fas fa-plus"></i>
            </div>
            <div class="timeline-content">
              <strong>Réservation créée</strong>
              <span class="timeline-date">{{ formatDateTime(reservation.createdAt) }}</span>
            </div>
          </div>
          <div v-if="reservation.updatedAt !== reservation.createdAt" class="timeline-item">
            <div class="timeline-icon info">
              <i class="fas fa-edit"></i>
            </div>
            <div class="timeline-content">
              <strong>Dernière modification</strong>
              <span class="timeline-date">{{ formatDateTime(reservation.updatedAt) }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import AdminApi from '@/services/AdminApi'

export default {
  name: 'ReservationDetail',
  
  data() {
    return {
      reservation: null,
      loading: true,
      error: null
    }
  },

  created() {
    this.fetchReservation()
  },

  watch: {
    '$route.params.id'() {
      this.fetchReservation()
    }
  },

  methods: {
    async fetchReservation() {
      this.loading = true
      this.error = null
      
      try {
        const data = await AdminApi.getReservation(this.$route.params.id)
        this.reservation = data
      } catch (error) {
        this.error = error.message || 'Impossible de charger la réservation'
      } finally {
        this.loading = false
      }
    },

    async cancelReservation() {
      if (!confirm('Êtes-vous sûr de vouloir annuler cette réservation ?')) {
        return
      }

      try {
        await AdminApi.updateReservation(this.reservation.id, {
          status: 'cancelled'
        })
        
        this.fetchReservation() // Recharger
        alert('Réservation annulée')
      } catch (error) {
        alert('Erreur lors de l\'annulation')
      }
    },

    // Formatage
    formatDate(dateString) {
      if (!dateString) return 'Non défini'
      
      try {
        const date = new Date(dateString)
        return date.toLocaleDateString('fr-FR', {
          weekday: 'short',
          year: 'numeric',
          month: 'short', 
          day: 'numeric'
        })
      } catch {
        return dateString
      }
    },

    formatDateTime(dateString) {
      if (!dateString) return 'Non défini'
      
      try {
        const date = new Date(dateString)
        return date.toLocaleString('fr-FR')
      } catch {
        return dateString
      }
    },

    formatCurrency(amount) {
      if (!amount) return '0 €'
      
      const value = typeof amount === 'string' ? parseFloat(amount) : amount
      return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'EUR'
      }).format(value)
    },

    calculateNights() {
      if (!this.reservation?.checkin || !this.reservation?.checkout) return 0
      
      try {
        const checkin = new Date(this.reservation.checkin)
        const checkout = new Date(this.reservation.checkout)
        const diffTime = checkout.getTime() - checkin.getTime()
        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24))
        return Math.max(0, diffDays)
      } catch {
        return 0
      }
    },

    getTotalGuests() {
      const adults = this.reservation?.numberOfAdults || 0
      const children = this.reservation?.numberOfChildren || 0
      return adults + children
    },

    getCustomerDisplay() {
      const customer = this.reservation?.customer
      if (typeof customer === 'string' && customer.startsWith('/api/customers/')) {
        const id = customer.split('/').pop()
        return `Client #${id}`
      }
      return customer?.name || 'Client non renseigné'
    },

    getProductTypeLabel() {
      const type = this.reservation?.productType
      if (type?.includes('Room')) return 'Chambre'
      if (type?.includes('Activity')) return 'Activité'
      return type || 'Non défini'
    },

    getPaymentMethodLabel() {
      const method = this.reservation?.paymentMethod
      const labels = {
        'stripe_card': 'Carte bancaire (Stripe)',
        'bank_transfer': 'Virement bancaire',
        'cash': 'Espèces',
        'check': 'Chèque'
      }
      return labels[method] || method || 'Non défini'
    },

    getBookingSourceLabel() {
      const source = this.reservation?.bookingSource
      const labels = {
        'website': 'Site web',
        'phone': 'Téléphone',
        'email': 'Email',
        'admin': 'Administration'
      }
      return labels[source] || source || 'Non défini'
    },

    // Status helpers
    getStatusLabel(status) {
      const labels = {
        'confirmed': 'Confirmée',
        'pending': 'En attente',
        'cancelled': 'Annulée',
        'completed': 'Terminée'
      }
      return labels[status] || status || 'Inconnu'
    },

    getStatusClass(status) {
      return `status-${status}`
    },

    getStatusIcon(status) {
      const icons = {
        'confirmed': 'fas fa-check-circle',
        'pending': 'fas fa-hourglass-half',
        'cancelled': 'fas fa-times-circle',
        'completed': 'fas fa-flag-checkered'
      }
      return icons[status] || 'fas fa-question-circle'
    },

    getPaymentStatusLabel(status) {
      const labels = {
        'paid': 'Payé',
        'pending': 'En attente',
        'failed': 'Échec',
        'refunded': 'Remboursé'
      }
      return labels[status] || status || 'Inconnu'
    },

    getPaymentStatusClass(status) {
      return `payment-${status}`
    },

    getPaymentStatusIcon(status) {
      const icons = {
        'paid': 'fas fa-check-circle',
        'pending': 'fas fa-clock',
        'failed': 'fas fa-exclamation-triangle',
        'refunded': 'fas fa-undo'
      }
      return icons[status] || 'fas fa-question-circle'
    }
  }
}
</script>

<style lang="scss" scoped>
.reservation-detail-page {
  padding: 2rem;
  max-width: 1200px;
  margin: 0 auto;
}

// Loading & Error states
.loading-state, .error-state {
  text-align: center;
  padding: 4rem 2rem;
  
  .spinner {
    width: 40px;
    height: 40px;
    border: 3px solid #f3f3f3;
    border-top: 3px solid #656C97;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin: 0 auto 1rem;
  }
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

.error-state {
  .error-icon {
    font-size: 3rem;
    margin-bottom: 1rem;
  }
}

// Page Header
.page-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 2rem;
  padding-bottom: 1rem;
  border-bottom: 1px solid #e9ecef;

  h1 {
    margin: 0 0 0.5rem 0;
    color: #32325d;
  }

  .subtitle {
    color: #8898aa;
    margin: 0;
  }
}

.header-actions {
  display: flex;
  gap: 1rem;
}

// Status badges
.status-row {
  display: flex;
  gap: 1rem;
  margin-bottom: 2rem;
}

.status-badge {
  padding: 0.5rem 1rem;
  border-radius: 20px;
  font-weight: 500;
  display: flex;
  align-items: center;
  gap: 0.5rem;

  &.status-confirmed {
    background: #d4edda;
    color: #155724;
  }

  &.status-pending {
    background: #fff3cd;
    color: #856404;
  }

  &.status-cancelled {
    background: #f8d7da;
    color: #721c24;
  }

  &.payment-paid {
    background: #d1ecf1;
    color: #0c5460;
  }

  &.payment-pending {
    background: #ffeaa7;
    color: #6c5ce7;
  }
}

// Info Grid
.info-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 1.5rem;
  margin-bottom: 2rem;
}

.info-card {
  background: white;
  border: 1px solid #e9ecef;
  border-radius: 8px;
  padding: 1.5rem;

  h3 {
    margin: 0 0 1rem 0;
    color: #495057;
    font-size: 1.1rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;

    i {
      color: #656C97;
    }
  }
}

.info-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 0.75rem;

  &:last-child {
    margin-bottom: 0;
  }

  label {
    font-weight: 500;
    color: #6c757d;
  }

  span {
    color: #495057;
    
    &.amount {
      font-weight: 600;
      color: #28a745;
      font-size: 1.1rem;
    }
  }
}

// Notes section
.notes-section, .dates-section {
  background: white;
  border: 1px solid #e9ecef;
  border-radius: 8px;
  padding: 1.5rem;
  margin-bottom: 1.5rem;

  h3 {
    margin: 0 0 1rem 0;
    color: #495057;
    display: flex;
    align-items: center;
    gap: 0.5rem;

    i {
      color: #656C97;
    }
  }
}

.comment-box {
  background: #f8f9fa;
  padding: 1rem;
  border-radius: 6px;
  border-left: 4px solid #656C97;
}

// Timeline
.timeline {
  .timeline-item {
    display: flex;
    gap: 1rem;
    margin-bottom: 1rem;

    &:last-child {
      margin-bottom: 0;
    }
  }

  .timeline-icon {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 0.875rem;

    &.success {
      background: #28a745;
    }

    &.info {
      background: #17a2b8;
    }
  }

  .timeline-content {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 0.25rem;

    strong {
      color: #495057;
    }

    .timeline-date {
      color: #6c757d;
      font-size: 0.875rem;
    }
  }
}

// Buttons
.btn {
  padding: 0.5rem 1rem;
  border: none;
  border-radius: 6px;
  font-weight: 500;
  cursor: pointer;
  text-decoration: none;
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  transition: all 0.2s ease;

  &.btn-primary {
    background: #656C97;
    color: white;

    &:hover {
      background: #5a6088;
    }
  }

  &.btn-secondary {
    background: #6c757d;
    color: white;

    &:hover {
      background: #545b62;
    }
  }

  &.btn-danger {
    background: #dc3545;
    color: white;

    &:hover {
      background: #c82333;
    }

    &:disabled {
      background: #6c757d;
      cursor: not-allowed;
    }
  }
}

// Responsive
@media (max-width: 768px) {
  .reservation-detail-page {
    padding: 1rem;
  }

  .page-header {
    flex-direction: column;
    gap: 1rem;
  }

  .info-grid {
    grid-template-columns: 1fr;
  }

  .status-row {
    flex-wrap: wrap;
  }
}
</style>