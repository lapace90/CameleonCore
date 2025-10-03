<template>
  <div class="reservation-detail content-wrapper">
    <!-- Loading -->
    <LoadingState v-if="loading" state="loading" variant="fullscreen" loading-text="Chargement de la réservation..." />

    <LoadingState v-else-if="error" state="error" variant="card" error-title="Erreur" :error-message="error"
      @retry="fetchReservation" />

    <!-- Content -->
    <div v-else-if="reservation">
      <!-- Header existant -->
      <div class="page-header">
        <div>
          <h1>Réservation #{{ reservation.id }}</h1>
          <p class="subtitle">{{ formatDateTime(reservation.created_at) }}</p>
        </div>
        <div class="header-actions">
          <button @click="goBack" class="btn btn-secondary btn-sm">
            <i class="fas fa-arrow-left"></i> Retour
          </button>

          <!-- Boutons conditionnels selon permission ET statut -->
          <button v-if="reservation.status === 'confirmed' && canCheckIn" class="btn btn-success btn-sm"
            @click="onCheckIn">
            <i class="fas fa-door-open"></i> Faire check-in
          </button>

          <button v-if="reservation.status === 'checked_in' && canCheckOut" class="btn btn-info btn-sm"
            @click="onCheckOut">
            <i class="fas fa-door-closed"></i> Faire check-out
          </button>

          <button @click="editReservation" class="btn btn-primary btn-sm">
            <i class="fas fa-edit"></i> Modifier
          </button>
        </div>
      </div>

      <!-- Status badges existant -->
      <div class="status-row">
        <div :class="['status-badge', getStatusClass(reservation.status)]">
          <i :class="getStatusIcon(reservation.status)"></i>
          {{ getStatusLabel(reservation.status) }}
        </div>
        <div :class="['status-badge', getPaymentStatusClass(reservation.payment_status)]">
          <i :class="getPaymentStatusIcon(reservation.payment_status)"></i>
          {{ getPaymentStatusLabel(reservation.payment_status) }}
        </div>
      </div>

      <!-- Grid existante avec données complètes -->
      <div class="info-grid">
        <!-- Client -->
        <ReservationInfoCard title="Client" icon="fas fa-user">
          <div class="info-item">
            <label>Nom:</label>
            <span>{{ getCustomerName() }}</span>
          </div>
          <div class="info-item" v-if="getCustomerEmail()">
            <label>Email:</label>
            <span>{{ getCustomerEmail() }}</span>
          </div>
          <div class="info-item" v-if="getCustomerPhone()">
            <label>Téléphone:</label>
            <span>{{ getCustomerPhone() }}</span>
          </div>
        </ReservationInfoCard>

        <!-- Séjour -->
        <ReservationInfoCard title="Séjour" icon="fas fa-calendar">
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
        </ReservationInfoCard>

        <!-- Invités -->
        <ReservationInfoCard title="Invités" icon="fas fa-users">
          <div class="info-item">
            <label>Adultes:</label>
            <span>{{ reservation.number_of_adults || 0 }}</span>
          </div>
          <div class="info-item">
            <label>Enfants:</label>
            <span>{{ reservation.number_of_children || 0 }}</span>
          </div>
          <div class="info-item">
            <label>Total:</label>
            <span>{{ getTotalGuests() }} personnes</span>
          </div>
        </ReservationInfoCard>

        <!-- Logement -->
        <ReservationInfoCard title="Services" icon="fas fa-bed">
          <div class="info-item">
            <label>Nom:</label>
            <span>{{ getProductName() }}</span>
          </div>
          <div class="info-item">
            <label>Type:</label>
            <span>{{ getProductTypeLabel() }}</span>
          </div>
        </ReservationInfoCard>

        <!-- Paiement -->
        <ReservationInfoCard title="Paiement" icon="fas fa-credit-card">
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
            <span>{{ getPaymentStatusLabel(reservation.payment_status) }}</span>
          </div>
        </ReservationInfoCard>

        <!-- Références -->
        <ReservationInfoCard title="Références" icon="fas fa-hashtag">
          <div class="info-item">
            <label>Facture:</label>
            <span>{{ reservation.invoice_number || 'Non générée' }}</span>
          </div>
          <div class="info-item">
            <label>Devis:</label>
            <span>{{ reservation.quote_reference || 'Aucun' }}</span>
          </div>
          <div class="info-item">
            <label>Source:</label>
            <span>{{ getBookingSourceLabel() }}</span>
          </div>
        </ReservationInfoCard>
      </div>

      <!-- Notes -->
      <ReservationInfoCard v-if="reservation.comment" title="Commentaires" icon="fas fa-sticky-note">
        <div class="comment-box">
          {{ reservation.comment }}
        </div>
      </ReservationInfoCard>
      <!-- À ajouter dans le template après les cartes existantes -->
      <div v-if="reservation.actual_checkin || reservation.actual_checkout" class="info-grid">
        <ReservationInfoCard title="Historique Check-in/out" icon="fas fa-history">
          <div v-if="reservation.actual_checkin" class="info-row">
            <span class="info-label">
              <i class="fas fa-door-open text-success"></i> Check-in réel:
            </span>
            <span class="info-value">{{ formatDateTime(reservation.actual_checkin) }}</span>
          </div>
          <div v-if="reservation.actual_checkout" class="info-row">
            <span class="info-label">
              <i class="fas fa-door-closed text-info"></i> Check-out réel:
            </span>
            <span class="info-value">{{ formatDateTime(reservation.actual_checkout) }}</span>
          </div>
        </ReservationInfoCard>
      </div>

      <div class="timeline-section">
        <ReservationTimeline :items="reservation.timeline || []" />
      </div>
    </div>
  </div>
</template>

<script>
import AdminApi from '@/services/AdminApi'
import ReservationInfoCard from './ReservationInfoCard.vue'
import ReservationTimeline from './ReservationTimeline.vue'
import { permissionMixin } from '@/plugins/permission-directives'
import LoadingState from '@/admin/components/ui/LoadingState.vue'

export default {
  name: 'ReservationDetail',
  mixins: [permissionMixin],
  components: {
    ReservationInfoCard,
    ReservationTimeline,
    LoadingState
  },

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

  computed: {
    canCheckIn() {
      return this.$hasPermission('checkin')
    },

    canCheckOut() {
      return this.$hasPermission('checkout')
    }
  },

  methods: {
    async fetchReservation() {
      this.loading = true
      this.error = null

      try {
        const data = await AdminApi.getReservation(this.$route.params.id)
        this.reservation = data
        console.log('✅ Réservation chargée:', data)
      } catch (error) {
        console.error('❌ Erreur:', error)
        this.error = error.message || 'Impossible de charger la réservation'
      } finally {
        this.loading = false
      }
    },

    // Actions de check-in/check-out
    async onCheckIn() {
      if (!confirm('Confirmer l\'arrivée du client ?')) return

      try {
        const updated = await AdminApi.doReservationCheckIn(this.reservation.id)
        this.reservation = updated
        alert('✅ Check-in effectué avec succès!')
      } catch (e) {
        const code = e?.response?.status
        const msg = e?.response?.data?.message || e.message || 'Erreur check-in'
        if (code === 403) alert('❌ Action non autorisée (permissions)')
        else alert(`❌ ${msg}`)
      }
    },

    async onCheckOut() {
      if (!confirm('Confirmer le départ du client ?')) return

      try {
        const updated = await AdminApi.doReservationCheckOut(this.reservation.id)
        this.reservation = updated
        alert('✅ Check-out effectué avec succès!')
      } catch (e) {
        const code = e?.response?.status
        const msg = e?.response?.data?.message || e.message || 'Erreur check-out'
        if (code === 403) alert('❌ Action non autorisée (permissions)')
        else alert(`❌ ${msg}`)
      }
    },

    // Navigation
    goBack() {
      this.$router.go(-1)
    },

    editReservation() {
      this.$router.push({
        name: 'ReservationDetail',
        params: { id: this.reservation.id },
        query: { mode: 'edit' }
      })
    },

    // Getters pour les données
    getCustomerName() {
      if (typeof this.reservation.customer === 'object') {
        const c = this.reservation.customer
        return c.name && c.last_name ? `${c.name} ${c.last_name}` : c.name || c.email
      }
      return this.reservation.customerName || 'Client inconnu'
    },

    getCustomerEmail() {
      if (typeof this.reservation.customer === 'object') {
        return this.reservation.customer.email
      }
      return null
    },

    getCustomerPhone() {
      if (typeof this.reservation.customer === 'object') {
        return this.reservation.customer.phone
      }
      return null
    },

    getProductName() {
      if (typeof this.reservation.product === 'object') {
        return this.reservation.product.name
      }
      return this.reservation.productName || 'Produit non défini'
    },

    // Formatage
    formatDate(dateString) {
      if (!dateString) return 'Non défini'
      return new Date(dateString).toLocaleDateString('fr-FR', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric'
      })
    },

    formatDateTime(dateString) {
      if (!dateString) return 'Non défini'
      return new Date(dateString).toLocaleString('fr-FR')
    },

    formatCurrency(amount) {
      if (!amount) return '0,00 €'
      const value = typeof amount === 'string' ? parseFloat(amount) : amount
      return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'EUR'
      }).format(value)
    },

    // Calculs
    calculateNights() {
      if (!this.reservation?.checkin || !this.reservation?.checkout) return 0
      const checkin = new Date(this.reservation.checkin)
      const checkout = new Date(this.reservation.checkout)
      return Math.ceil((checkout - checkin) / (1000 * 60 * 60 * 24))
    },

    getTotalGuests() {
      return (this.reservation?.number_of_adults || 0) + (this.reservation?.number_of_children || 0)
    },

    // Labels
    getStatusLabel(status) {
      const labels = {
        confirmed: 'Confirmée',
        pending: 'En attente',
        checked_in: 'Client présent',
        checked_out: 'Séjour terminé',
        cancelled: 'Annulée',
        completed: 'Terminée',
        no_show: 'No-show'
      }
      return labels[status] || status
    },

    getStatusClass(status) {
      return `status-${status}`
    },

    getStatusIcon(status) {
      const icons = {
        confirmed: 'fas fa-check-circle',
        pending: 'fas fa-hourglass-half',
        checked_in: 'fas fa-door-open',
        checked_out: 'fas fa-door-closed',
        completed: 'fas fa-flag-checkered',
        cancelled: 'fas fa-times-circle',
        no_show: 'fas fa-user-slash'
      }
      return icons[status] || 'fas fa-question-circle'
    },

    getPaymentStatusLabel(status) {
      const labels = {
        paid: 'Payé',
        pending: 'En attente',
        failed: 'Échec'
      }
      return labels[status] || status
    },

    getPaymentStatusClass(status) {
      return `payment-${status}`
    },

    getPaymentStatusIcon(status) {
      const icons = {
        paid: 'fas fa-check-circle',
        pending: 'fas fa-hourglass-half',
        failed: 'fas fa-times-circle'
      }
      return icons[status] || 'fas fa-credit-card'
    },

    getPaymentMethodLabel() {
      const method = this.reservation?.payment_method
      const labels = {
        stripe_card: 'Carte bancaire (Stripe)',
        card: 'Carte bancaire',
        cash: 'Espèces',
        stripe: 'Stripe'
      }
      return labels[method] || method || 'Non défini'
    },

    getProductTypeLabel() {
      const type = this.reservation?.product_type
      const labels = {
        'App\\Models\\Room': 'Hébergement',
        accommodation: 'Hébergement',
        activity: 'Activité'
      }
      return labels[type] || 'Non défini'
    },

    getBookingSourceLabel() {
      const source = this.reservation?.booking_source
      const labels = {
        website: 'Site web',
        direct: 'Direct',
        phone: 'Téléphone'
      }
      return labels[source] || source || 'Non défini'
    }
  }
}
</script>

<style scoped>
.reservation-detail {
  margin: 3rem;
  padding: 2rem;
  background: #fff;
  min-height: 100vh;
}
.comment-box {
  background: #f8f9fa;
  padding: 1rem;
  border-radius: 0.375rem;
  border-left: 4px solid var(--primary);
  white-space: pre-wrap;
  line-height: 1.5;
}
.status-row {
  display: flex;
  gap: 1rem;
  margin-bottom: 1.5rem;
}
</style>