<template>
  <div class="reservation-detail content-wrapper">
    <!-- Loading -->
    <LoadingState v-if="loading" state="loading" variant="card" loading-text="Chargement de la réservation..." />

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
            <AppIcon name="arrow-left" /> Retour
          </button>

          <!-- Boutons conditionnels selon permission ET statut -->
          <button v-if="reservation.status === 'confirmed' && canCheckIn" class="btn btn-success btn-sm"
            @click="onCheckIn">
            <AppIcon name="door-open" /> Faire check-in
          </button>

          <button v-if="reservation.status === 'checked_in' && canCheckOut" class="btn btn-info btn-sm"
            @click="onCheckOut">
            <AppIcon name="door-closed" /> Faire check-out
          </button>

          <button @click="editReservation" class="btn btn-primary btn-sm">
            <AppIcon name="pencil" /> Modifier
          </button>
        </div>
      </div>

      <!-- Status badges existant -->
      <div class="status-row">
        <div :class="['status-badge', getStatusClass(reservation.status)]">
          <AppIcon :name="getStatusIcon(reservation.status)" />
          {{ getStatusLabel(reservation.status) }}
        </div>
        <div :class="['status-badge', getPaymentStatusClass(reservation.payment_status)]">
          <AppIcon :name="getPaymentStatusIcon(reservation.payment_status)" />
          {{ getPaymentStatusLabel(reservation.payment_status) }}
        </div>
      </div>

      <!-- Grid existante avec données complètes -->
      <div class="info-grid">
        <!-- Client -->
        <ReservationInfoCard title="Client" icon="user">
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
        <ReservationInfoCard title="Séjour" icon="calendar">
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
        <ReservationInfoCard title="Invités" icon="users">
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

        <!-- Produits & Services -->
        <ReservationInfoCard title="Produits & Services" icon="shopping-cart">
          <!-- Produit principal (hébergement) -->
          <div class="info-item">
            <label>Hébergement principal:</label>
            <span>{{ getProductName() }}</span>
          </div>

          <!-- NOUVEAU : Liste complète des produits -->
          <div v-if="reservation.products && reservation.products.length > 0" class="products-list">
            <label class="products-label">Tous les produits :</label>
            <div class="product-items">
              <div v-for="product in reservation.products" :key="product.id" class="product-item">
                <span class="product-icon">
                  <AppIcon :name="getProductIcon(product.productable_type)" />
                </span>
                <span class="product-name">{{ product.name }}</span>
                <span class="product-quantity">x{{ product.quantity }}</span>
                <span class="product-price">{{ formatCurrency(product.price * product.quantity) }}</span>
              </div>
            </div>

            <!-- Total produits -->
            <div class="products-total">
              <strong>Total produits : {{ reservation.products.length }}</strong>
            </div>
          </div>

          <!-- Si pas de produits multiples, juste le type du produit principal -->
          <div v-else class="info-item">
            <label>Type:</label>
            <span>{{ getProductTypeLabel() }}</span>
          </div>
        </ReservationInfoCard>

        <!-- Paiement -->
        <!-- Paiement -->
        <ReservationInfoCard title="Paiement" icon="credit-card">
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

          <!-- Facturation acompte/solde -->
          <div v-if="reservation.deposit_invoice" class="deposit-info">
            <div class="info-item">
              <label>Acompte versé :</label>
              <span class="amount">{{ formatCurrency(reservation.deposit_invoice.amount) }}</span>
            </div>
            <div class="info-item">
              <label>Réf. acompte :</label>
              <span>{{ reservation.deposit_invoice.invoice_number }}</span>
            </div>
            <div class="info-item">
              <label>Solde restant :</label>
              <span class="amount">{{ formatCurrency(reservation.amount - reservation.deposit_invoice.amount) }}</span>
            </div>

            <button v-if="!reservation.balance_invoice" class="btn btn-primary btn-sm btn-balance"
              :disabled="creatingBalance" @click="createBalanceInvoice">
              <AppIcon name="file-text" />
              {{ creatingBalance ? 'Création...' : 'Créer facture de solde' }}
            </button>

            <div v-if="reservation.balance_invoice" class="info-item">
              <label>Facture solde :</label>
              <span>{{ reservation.balance_invoice.invoice_number }} — {{ reservation.balance_invoice.status_label
                }}</span>
            </div>
          </div>
        </ReservationInfoCard>

        <!-- Références -->
        <ReservationInfoCard title="Références" icon="hash">
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
      <ReservationInfoCard v-if="reservation.comment" title="Commentaires" icon="sticky-note">
        <div class="comment-box">
          {{ reservation.comment }}
        </div>
      </ReservationInfoCard>
      <!-- À ajouter dans le template après les cartes existantes -->
      <div v-if="reservation.actual_checkin || reservation.actual_checkout" class="info-grid">
        <ReservationInfoCard title="Historique Check-in/out" icon="history">
          <div v-if="reservation.actual_checkin" class="info-row">
            <span class="info-label">
              <AppIcon name="door-open" /> Check-in réel:
            </span>
            <span class="info-value">{{ formatDateTime(reservation.actual_checkin) }}</span>
          </div>
          <div v-if="reservation.actual_checkout" class="info-row">
            <span class="info-label">
              <AppIcon name="door-closed" /> Check-out réel:
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
      error: null,
      creatingBalance: false
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

    async createBalanceInvoice() {
      if (!this.reservation.deposit_invoice) return

      if (!confirm('Créer la facture de solde pour cette réservation ?')) return

      this.creatingBalance = true
      try {
        await AdminApi.createBalanceInvoice(this.reservation.deposit_invoice.id)
        await this.fetchReservation() // recharger pour voir la facture de solde
        alert('✅ Facture de solde créée avec succès')
      } catch (e) {
        console.error('❌ Erreur création facture de solde:', e)
        alert(`❌ ${e.message || 'Erreur lors de la création de la facture de solde'}`)
      } finally {
        this.creatingBalance = false
      }
    },

    getProductIcon(productableType) {
      if (!productableType) return 'box'

      if (productableType.includes('Room')) return 'bed'
      if (productableType.includes('Activity')) return 'footprints'
      if (productableType.includes('Menu')) return 'utensils'

      return 'box'
    },

    getProductTypeName(productableType) {
      if (!productableType) return 'Produit'

      if (productableType.includes('Room')) return 'Hébergement'
      if (productableType.includes('Activity')) return 'Activité'
      if (productableType.includes('Menu')) return 'Menu'

      return 'Produit'
    },
    // Navigation
    goBack() {
      this.$router.go(-1)
    },

    editReservation() {
      this.$router.push({
        name: 'ReservationEdit',
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
        confirmed: 'circle-check',
        pending: 'hourglass',
        checked_in: 'door-open',
        checked_out: 'door-closed',
        completed: 'flag',
        cancelled: 'circle-x',
        no_show: 'user-x'
      }
      return icons[status] || 'circle-help'
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
        paid: 'circle-check',
        pending: 'hourglass',
        failed: 'circle-x'
      }
      return icons[status] || 'credit-card'
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

/* Styles pour la liste des produits */
.products-list {
  margin-top: 1rem;
  padding-top: 1rem;
  border-top: 1px solid #e5e7eb;
}

.products-label {
  display: block;
  font-weight: 600;
  margin-bottom: 0.75rem;
  color: #374151;
}

.product-items {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.product-item {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.5rem;
  background: #f9fafb;
  border-radius: 6px;
  font-size: 14px;
}

.product-icon {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 32px;
  height: 32px;
  background: white;
  border-radius: 4px;
  color: #6b7280;
}

.product-name {
  flex: 1;
  font-weight: 500;
  color: #111827;
}

.product-quantity {
  padding: 0.25rem 0.5rem;
  background: #3b82f6;
  color: white;
  border-radius: 4px;
  font-size: 12px;
  font-weight: 600;
}

.product-price {
  font-weight: 600;
  color: #059669;
  min-width: 80px;
  text-align: right;
}

.products-total {
  margin-top: 0.75rem;
  padding-top: 0.75rem;
  border-top: 1px dashed #e5e7eb;
  text-align: right;
  color: #111827;
}

.deposit-info {
  margin-top: 1rem;
  padding-top: 1rem;
  border-top: 1px solid #e5e7eb;
}

.btn-balance {
  margin-top: 0.75rem;
  width: 100%;
}
</style>
