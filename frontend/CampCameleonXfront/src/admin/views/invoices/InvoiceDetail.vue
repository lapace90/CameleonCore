<template>
  <div class="product-detail-container">
    <!-- Loading -->
    <LoadingState v-if="loading" state="loading" variant="card" loading-text="Chargement de la facture..." />

    <LoadingState v-else-if="error" state="error" variant="card" error-title="Erreur de chargement"
      :error-message="error" :show-action="true" action-label="Retour aux factures" action-icon="fas fa-arrow-left"
      @action="$router.push('/admin/invoices')" />

    <!-- Contenu principal -->
    <div v-else-if="invoice">
      <!-- Header avec navigation -->
      <div class="detail-header">
        <div class="header-navigation">
          <router-link to="/admin/invoices" class="back-link">
            <i class="fas fa-arrow-left"></i>
            Retour aux factures
          </router-link>
        </div>

        <div class="header-actions">
          <button v-if="canBePaid" @click="handleMarkAsPaid" class="btn btn-success btn-sm" :disabled="actionLoading">
            <i class="fas fa-check-circle"></i>
            Marquer payée
          </button>

          <button @click="handleSendEmail" class="btn btn-primary btn-sm"
            :disabled="actionLoading || !invoice.customer?.email">
            <i class="fas fa-envelope"></i>
            {{ invoice.sent_count > 0 ? 'Renvoyer' : 'Envoyer' }}
          </button>

          <button @click="handleDownloadPdf" class="btn btn-secondary btn-sm" :disabled="actionLoading">
            <i class="fas fa-download"></i>
            Télécharger PDF
          </button>

          <button v-if="canBeCanceled" @click="handleCancel" class="btn btn-danger btn-sm" :disabled="actionLoading">
            <i class="fas fa-ban"></i>
            Annuler
          </button>
        </div>
      </div>

      <!-- Titre et statut -->
      <div class="product-title-section">
        <div class="title-left">
          <h1 class="product-title">{{ invoice.invoice_number }}</h1>
          <p class="product-category">Facture</p>
        </div>

        <div class="title-right">
          <span class="status-badge" :class="getStatusClass(invoice.status)">
            {{ getStatusLabel(invoice.status) }}
          </span>
          <div class="amount-display">
            {{ formatCurrency(invoice.amount) }}
          </div>
        </div>
      </div>

      <!-- Informations générales -->
      <div class="info-section">
        <h3>Informations générales</h3>
        <div class="info-grid">
          <div class="info-item">
            <label>Numéro de facture</label>
            <span>{{ invoice.invoice_number }}</span>
          </div>

          <div class="info-item">
            <label>Montant</label>
            <span class="amount-text">{{ formatCurrency(invoice.amount) }}</span>
          </div>

          <div class="info-item">
            <label>Date d'émission</label>
            <span>{{ formatDate(invoice.issue_date) }}</span>
          </div>

          <div class="info-item">
            <label>Date d'échéance</label>
            <span>{{ formatDate(invoice.due_date) }}</span>
          </div>

          <div v-if="invoice.payment_date" class="info-item">
            <label>Date de paiement</label>
            <span>{{ formatDate(invoice.payment_date) }}</span>
          </div>

          <div v-if="invoice.payment_method" class="info-item">
            <label>Méthode de paiement</label>
            <span>{{ getPaymentMethodLabel(invoice.payment_method) }}</span>
          </div>
        </div>
      </div>

      <!-- Informations client -->
      <div v-if="invoice.customer" class="info-section">
        <h3>Client</h3>
        <div class="info-grid">
          <div class="info-item">
            <label>Nom</label>
            <span>{{ invoice.customer.name }} {{ invoice.customer.last_name || '' }}</span>
          </div>

          <div v-if="invoice.customer.email" class="info-item">
            <label>Email</label>
            <span>{{ invoice.customer.email }}</span>
          </div>

          <div v-if="invoice.customer.phone" class="info-item">
            <label>Téléphone</label>
            <span>{{ invoice.customer.phone }}</span>
          </div>

          <div v-if="invoice.customer.address" class="info-item full-width">
            <label>Adresse</label>
            <span>{{ invoice.customer.address }}</span>
          </div>
        </div>
      </div>

      <!-- Informations réservation -->
      <div v-if="invoice.reservation" class="info-section">
        <h3>Réservation associée</h3>
        <div class="info-grid">
          <div class="info-item">
            <label>Référence réservation</label>
            <span>{{ invoice.reservation.invoice_number }}</span>
          </div>

          <div v-if="invoice.reservation.checkin" class="info-item">
            <label>Check-in</label>
            <span>{{ formatDate(invoice.reservation.checkin) }}</span>
          </div>

          <div v-if="invoice.reservation.checkout" class="info-item">
            <label>Check-out</label>
            <span>{{ formatDate(invoice.reservation.checkout) }}</span>
          </div>

          <div v-if="invoice.reservation.product" class="info-item">
            <label>Produit</label>
            <span>{{ invoice.reservation.product.name }}</span>
          </div>
        </div>
      </div>

      <!-- Historique -->
      <div class="info-section">
        <h3>Historique</h3>
        <div class="timeline">
          <div class="timeline-item">
            <div class="timeline-icon created">
              <i class="fas fa-plus-circle"></i>
            </div>
            <div class="timeline-content">
              <div class="timeline-title">Facture créée</div>
              <div class="timeline-date">{{ formatDate(invoice.created_at) }}</div>
            </div>
          </div>

          <div v-if="invoice.sent_at" class="timeline-item">
            <div class="timeline-icon sent">
              <i class="fas fa-envelope"></i>
            </div>
            <div class="timeline-content">
              <div class="timeline-title">
                Envoyée par email
                <span v-if="invoice.sent_count > 1" class="sent-count">({{ invoice.sent_count }}x)</span>
              </div>
              <div class="timeline-date">{{ formatDate(invoice.sent_at) }}</div>
            </div>
          </div>

          <div v-if="invoice.payment_date" class="timeline-item">
            <div class="timeline-icon paid">
              <i class="fas fa-check-circle"></i>
            </div>
            <div class="timeline-content">
              <div class="timeline-title">Paiement reçu</div>
              <div class="timeline-date">{{ formatDate(invoice.payment_date) }}</div>
            </div>
          </div>

          <div v-if="invoice.status === 'canceled'" class="timeline-item">
            <div class="timeline-icon canceled">
              <i class="fas fa-ban"></i>
            </div>
            <div class="timeline-content">
              <div class="timeline-title">Facture annulée</div>
              <div class="timeline-date">{{ formatDate(invoice.updated_at) }}</div>
            </div>
          </div>
        </div>
      </div>

      <!-- Notes -->
      <div v-if="invoice.notes" class="info-section">
        <h3>Notes</h3>
        <div class="notes-content">
          {{ invoice.notes }}
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useInvoiceStore } from '@/shared/stores/invoice'
import LoadingState from '@/admin/components/ui/LoadingState.vue'

const route = useRoute()
const router = useRouter()
const invoiceStore = useInvoiceStore()

// Refs
const loading = ref(false)
const error = ref(null)
const actionLoading = ref(false)

// Computed
const invoice = computed(() => invoiceStore.currentInvoice)

const canBePaid = computed(() => {
  return invoice.value &&
    (invoice.value.status === 'pending' || invoice.value.status === 'overdue')
})

const canBeCanceled = computed(() => {
  return invoice.value && invoice.value.status !== 'canceled' && invoice.value.status !== 'paid'
})

// Methods
async function loadInvoice() {
  loading.value = true
  error.value = null

  try {
    await invoiceStore.fetchInvoiceById(route.params.id, true)
  } catch (err) {
    error.value = err.message || 'Erreur lors du chargement de la facture'
  } finally {
    loading.value = false
  }
}

async function handleMarkAsPaid() {
  if (!confirm(`Marquer la facture ${invoice.value.invoice_number} comme payée ?`)) {
    return
  }

  actionLoading.value = true
  try {
    await invoiceStore.markAsPaid(invoice.value.id, 'card')
    alert('✅ Facture marquée comme payée')
    await loadInvoice()
  } catch (err) {
    alert(`❌ Erreur : ${err.message}`)
  } finally {
    actionLoading.value = false
  }
}

async function handleSendEmail() {
  if (!invoice.value.customer?.email) {
    alert('❌ Le client n\'a pas d\'adresse email')
    return
  }

  const action = invoice.value.sent_count > 0 ? 'Renvoyer' : 'Envoyer'
  if (!confirm(`${action} la facture ${invoice.value.invoice_number} par email à ${invoice.value.customer.email} ?`)) {
    return
  }

  actionLoading.value = true
  try {
    await invoiceStore.sendEmail(invoice.value.id)
    alert('✅ Email envoyé avec succès')
    await loadInvoice()
  } catch (err) {
    alert(`❌ Erreur : ${err.message}`)
  } finally {
    actionLoading.value = false
  }
}

async function handleDownloadPdf() {
  actionLoading.value = true
  try {
    await invoiceStore.downloadPdf(invoice.value.id)
  } catch (err) {
    alert(`❌ Erreur : ${err.message}`)
  } finally {
    actionLoading.value = false
  }
}

async function handleCancel() {
  if (!confirm(`Annuler la facture ${invoice.value.invoice_number} ?\n\nCette action ne peut pas être annulée.`)) {
    return
  }

  actionLoading.value = true
  try {
    await invoiceStore.cancelInvoice(invoice.value.id, 'Annulée manuellement')
    alert('✅ Facture annulée')
    await loadInvoice()
  } catch (err) {
    alert(`❌ Erreur : ${err.message}`)
  } finally {
    actionLoading.value = false
  }
}

function getStatusClass(status) {
  const classes = {
    paid: 'status-active',
    pending: 'status-draft',
    overdue: 'status-inactive',
    canceled: 'status-inactive'
  }
  return classes[status] || 'status-draft'
}

function getStatusLabel(status) {
  const labels = {
    paid: 'Payée',
    pending: 'En attente',
    overdue: 'En retard',
    canceled: 'Annulée'
  }
  return labels[status] || status
}

function getPaymentMethodLabel(method) {
  const labels = {
    card: 'Carte bancaire',
    cash: 'Espèces',
    transfer: 'Virement',
    check: 'Chèque'
  }
  return labels[method] || method
}

function formatDate(date) {
  if (!date) return '-'
  return new Date(date).toLocaleDateString('fr-FR', {
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  })
}

function formatCurrency(amount) {
  return new Intl.NumberFormat('fr-FR', {
    style: 'currency',
    currency: 'EUR'
  }).format(amount || 0)
}

// Lifecycle
onMounted(() => {
  loadInvoice()
})
</script>

<style scoped>
/* Réutilisation des classes existantes */
.product-detail-container {
  padding: 20px;
  width: 70%;
}

.header-navigation {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.back-link:hover {
  color: #324cdd;
}

.header-actions {
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
}

.product-title-section {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 30px;
  padding: 20px;
  background: white;
  border-radius: 8px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.product-category {
  font-size: 14px;
  color: #8898aa;
  margin: 0;
}

.title-right {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  gap: 10px;
}

.amount-display {
  font-size: 32px;
  font-weight: 700;
  color: #2dce89;
}

.info-section h3 {
  font-size: 18px;
  font-weight: 600;
  color: #32325d;
  margin: 0 0 20px 0;
  padding-bottom: 10px;
  border-bottom: 1px solid #e9ecef;
}

.info-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 25px;
}

.info-item {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.info-item.full-width {
  grid-column: 1 / -1;
}

.info-item label {
  font-size: 12px;
  font-weight: 600;
  color: #8898aa;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.info-item span {
  font-size: 15px;
  color: #32325d;
  word-break: break-word;
}

.amount-text {
  font-size: 16px;
  font-weight: 600;
  color: #2dce89;
}

/* Timeline horizontale */
.timeline {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  position: relative;
  padding: 20px 0;
}

.timeline::before {
  content: '';
  position: absolute;
  top: 36px;
  left: 5%;
  right: 5%;
  height: 2px;
  background: #e9ecef;
  z-index: 0;
}

.timeline-item {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
  position: relative;
  z-index: 1;
}

.timeline-icon {
  width: 48px;
  height: 48px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  background: white;
  border: 3px solid #e9ecef;
  color: #8898aa;
  font-size: 18px;
  margin-bottom: 15px;
}

.timeline-icon.created {
  border-color: #5e72e4;
  color: #5e72e4;
  background: #f0f3ff;
}

.timeline-icon.sent {
  border-color: #11cdef;
  color: #11cdef;
  background: #e8f9fd;
}

.timeline-icon.paid {
  border-color: #2dce89;
  color: #2dce89;
  background: #e8f8f0;
}

.timeline-icon.canceled {
  border-color: #f5365c;
  color: #f5365c;
  background: #fdebed;
}

.timeline-content {
  max-width: 150px;
}

.timeline-title {
  font-size: 13px;
  font-weight: 600;
  color: #32325d;
  margin-bottom: 5px;
  line-height: 1.3;
}

.sent-count {
  font-size: 11px;
  color: #8898aa;
  font-weight: 400;
}

.timeline-date {
  font-size: 11px;
  color: #8898aa;
}

.notes-content {
  padding: 15px;
  background: #f8f9fe;
  border-radius: 6px;
  color: #32325d;
  line-height: 1.6;
  white-space: pre-wrap;
}

/* Status badges */
.status-badge {
  padding: 6px 12px;
  border-radius: 20px;
  font-size: 12px;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.status-active {
  background: #d4edda;
  color: #155724;
}

.status-draft {
  background: #fff3cd;
  color: #856404;
}

.status-inactive {
  background: #f8d7da;
  color: #721c24;
}

/* Responsive */
@media (max-width: 1200px) {
  .product-detail-container {
    width: 85%;
  }
}

@media (max-width: 768px) {
  .product-detail-container {
    width: 100%;
    padding: 15px;
  }

  .timeline {
    flex-direction: column;
    gap: 20px;
  }

  .timeline::before {
    display: none;
  }

  .timeline-content {
    max-width: none;
  }
}
</style>