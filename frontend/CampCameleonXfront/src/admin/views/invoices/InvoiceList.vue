<template>
  <div class="invoices-list">
    <!-- Header -->
    <div class="page-header">
      <div class="header-left">
        <h1><i class="fas fa-file-invoice"></i> Factures</h1>
        <p class="subtitle">Gérez vos factures et suivez les paiements</p>
      </div>
      <div class="header-right">
        <router-link to="/admin/invoices/new" class="btn btn-primary">
          <i class="fas fa-plus"></i> Nouvelle facture
        </router-link>
      </div>
    </div>

    <!-- Quick Stats -->
    <div class="quick-stats" v-if="stats">
      <div class="stat-card">
        <div class="stat-icon" style="background: #5e72e4;">
          <i class="fas fa-file-invoice-dollar"></i>
        </div>
        <div class="stat-content">
          <div class="stat-label">Total Factures</div>
          <div class="stat-value">{{ stats.counts?.total || 0 }}</div>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon" style="background: #2dce89;">
          <i class="fas fa-check-circle"></i>
        </div>
        <div class="stat-content">
          <div class="stat-label">Payées</div>
          <div class="stat-value">{{ stats.counts?.paid || 0 }}</div>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon" style="background: #fb6340;">
          <i class="fas fa-exclamation-circle"></i>
        </div>
        <div class="stat-content">
          <div class="stat-label">En retard</div>
          <div class="stat-value">{{ stats.counts?.overdue || 0 }}</div>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon" style="background: #11cdef;">
          <i class="fas fa-euro-sign"></i>
        </div>
        <div class="stat-content">
          <div class="stat-label">Revenus</div>
          <div class="stat-value">{{ formatCurrency(stats.revenue?.total || 0) }}</div>
        </div>
      </div>
    </div>

    <!-- Filters -->
    <div class="filters-section card">
      <div class="filters-grid">
        <div class="filter-item">
          <label>Statut</label>
          <select v-model="localFilters.status" @change="applyFilters">
            <option value="">Tous</option>
            <option value="paid">Payées</option>
            <option value="pending">Non payées</option>
            <option value="overdue">En retard</option>
            <option value="canceled">Annulées</option>
          </select>
        </div>

        <div class="filter-item">
          <label>Recherche</label>
          <input
            type="text"
            v-model="localFilters.search"
            @input="debouncedSearch"
            placeholder="Numéro, client..."
          />
        </div>

        <div class="filter-item">
          <label>Date début</label>
          <input type="date" v-model="localFilters.start_date" @change="applyFilters" />
        </div>

        <div class="filter-item">
          <label>Date fin</label>
          <input type="date" v-model="localFilters.end_date" @change="applyFilters" />
        </div>

        <div class="filter-item">
          <label>Par page</label>
          <select v-model.number="localFilters.per_page" @change="applyFilters">
            <option :value="10">10</option>
            <option :value="15">15</option>
            <option :value="25">25</option>
            <option :value="50">50</option>
          </select>
        </div>

        <div class="filter-actions">
          <button @click="resetFilters" class="btn btn-outline btn-sm">
            <i class="fas fa-redo"></i> Réinitialiser
          </button>
        </div>
      </div>
    </div>

    <!-- Error Message -->
    <div v-if="error" class="alert alert-danger">
      <i class="fas fa-exclamation-triangle"></i>
      {{ error }}
    </div>

    <!-- Loading -->
    <div v-if="loading" class="loading-container">
      <div class="spinner"></div>
      <p>Chargement des factures...</p>
    </div>

    <!-- Table -->
    <div v-else-if="invoices.length > 0" class="card">
      <div class="table-responsive">
        <table class="data-table">
          <thead>
            <tr>
              <th @click="sort('invoice_number')" class="sortable">
                Numéro
                <i class="fas fa-sort"></i>
              </th>
              <th>Client</th>
              <th @click="sort('issue_date')" class="sortable">
                Date émission
                <i class="fas fa-sort"></i>
              </th>
              <th>Date échéance</th>
              <th @click="sort('amount')" class="sortable">
                Montant
                <i class="fas fa-sort"></i>
              </th>
              <th>Statut</th>
              <th class="text-right">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="invoice in invoices" :key="invoice.id">
              <td>
                <router-link 
                  :to="`/admin/invoices/${invoice.id}`"
                  class="invoice-link"
                >
                  <strong>{{ invoice.invoice_number }}</strong>
                </router-link>
              </td>
              <td>
                <div class="customer-info">
                  <div class="customer-name">{{ invoice.customer_name }}</div>
                  <div class="customer-email">{{ invoice.customer?.email }}</div>
                </div>
              </td>
              <td>{{ formatDate(invoice.issue_date) }}</td>
              <td>
                <span :class="{ 'text-danger': invoice.is_overdue }">
                  {{ formatDate(invoice.due_date) }}
                  <i v-if="invoice.is_overdue" class="fas fa-exclamation-circle"></i>
                </span>
              </td>
              <td>
                <strong>{{ invoice.formatted_amount }}</strong>
              </td>
              <td>
                <span :class="['badge', `badge-${invoice.status_color}`]">
                  {{ invoice.status_label }}
                </span>
              </td>
              <td class="text-right">
                <div class="action-buttons">
                  <router-link
                    :to="`/admin/invoices/${invoice.id}`"
                    class="btn btn-sm btn-icon"
                    title="Détails"
                  >
                    <i class="fas fa-eye"></i>
                  </router-link>

                  <button
                    @click="handleDownloadPdf(invoice.id)"
                    class="btn btn-sm btn-icon"
                    title="Télécharger PDF"
                  >
                    <i class="fas fa-file-pdf"></i>
                  </button>

                  <button
                    v-if="invoice.can_be_paid"
                    @click="handleMarkAsPaid(invoice)"
                    class="btn btn-sm btn-icon btn-success"
                    title="Marquer comme payée"
                  >
                    <i class="fas fa-check"></i>
                  </button>

                  <button
                    @click="handleSendEmail(invoice)"
                    class="btn btn-sm btn-icon btn-primary"
                    title="Envoyer par email"
                  >
                    <i class="fas fa-envelope"></i>
                  </button>

                  <button
                    v-if="invoice.can_be_modified"
                    @click="handleDelete(invoice)"
                    class="btn btn-sm btn-icon btn-danger"
                    title="Supprimer"
                  >
                    <i class="fas fa-trash"></i>
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div class="pagination" v-if="pagination.last_page > 1">
        <button
          @click="goToPage(pagination.current_page - 1)"
          :disabled="pagination.current_page === 1"
          class="btn btn-sm btn-outline"
        >
          <i class="fas fa-chevron-left"></i>
        </button>

        <span class="page-info">
          Page {{ pagination.current_page }} sur {{ pagination.last_page }}
          ({{ pagination.total }} factures)
        </span>

        <button
          @click="goToPage(pagination.current_page + 1)"
          :disabled="pagination.current_page === pagination.last_page"
          class="btn btn-sm btn-outline"
        >
          <i class="fas fa-chevron-right"></i>
        </button>
      </div>
    </div>

    <!-- Empty State -->
    <div v-else class="empty-state card">
      <i class="fas fa-file-invoice fa-3x"></i>
      <h3>Aucune facture trouvée</h3>
      <p>Créez votre première facture ou ajustez vos filtres</p>
      <router-link to="/admin/invoices/new" class="btn btn-primary">
        <i class="fas fa-plus"></i> Créer une facture
      </router-link>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useInvoiceStore } from '@/shared/stores/invoice'

const router = useRouter()
const invoiceStore = useInvoiceStore()

// Refs
const localFilters = ref({ ...invoiceStore.filters })
const searchTimeout = ref(null)

// Computed
const invoices = computed(() => invoiceStore.invoices)
const stats = computed(() => invoiceStore.stats)
const loading = computed(() => invoiceStore.loading)
const error = computed(() => invoiceStore.error)
const pagination = computed(() => invoiceStore.pagination)

// Methods
async function loadInvoices(page = 1) {
  try {
    await invoiceStore.fetchInvoices(page)
  } catch (err) {
    console.error('Erreur chargement factures:', err)
  }
}

async function loadStats() {
  try {
    await invoiceStore.fetchStats()
  } catch (err) {
    console.error('Erreur chargement stats:', err)
  }
}

function applyFilters() {
  invoiceStore.setFilters(localFilters.value)
  loadInvoices(1)
}

function debouncedSearch() {
  clearTimeout(searchTimeout.value)
  searchTimeout.value = setTimeout(() => {
    applyFilters()
  }, 500)
}

function resetFilters() {
  invoiceStore.resetFilters()
  localFilters.value = { ...invoiceStore.filters }
  loadInvoices(1)
}

function sort(field) {
  if (localFilters.value.sort_by === field) {
    localFilters.value.sort_order = localFilters.value.sort_order === 'asc' ? 'desc' : 'asc'
  } else {
    localFilters.value.sort_by = field
    localFilters.value.sort_order = 'desc'
  }
  applyFilters()
}

function goToPage(page) {
  if (page >= 1 && page <= pagination.value.last_page) {
    loadInvoices(page)
  }
}

async function handleMarkAsPaid(invoice) {
  if (!confirm(`Marquer la facture ${invoice.invoice_number} comme payée ?`)) {
    return
  }

  try {
    await invoiceStore.markAsPaid(invoice.id, 'card')
    alert('✅ Facture marquée comme payée')
    loadInvoices(pagination.value.current_page)
  } catch (err) {
    alert(`❌ Erreur : ${err.message}`)
  }
}

async function handleSendEmail(invoice) {
  if (!confirm(`Envoyer la facture ${invoice.invoice_number} par email à ${invoice.customer?.email} ?`)) {
    return
  }

  try {
    await invoiceStore.sendEmail(invoice.id)
    alert('✅ Email envoyé avec succès')
  } catch (err) {
    alert(`❌ Erreur : ${err.message}`)
  }
}

async function handleDownloadPdf(id) {
  try {
    await invoiceStore.downloadPdf(id)
  } catch (err) {
    alert(`❌ Erreur téléchargement PDF : ${err.message}`)
  }
}

async function handleDelete(invoice) {
  if (!confirm(`Supprimer la facture ${invoice.invoice_number} ?\n\nCette action est irréversible.`)) {
    return
  }

  try {
    await invoiceStore.deleteInvoice(invoice.id)
    alert('✅ Facture supprimée')
    loadInvoices(pagination.value.current_page)
  } catch (err) {
    alert(`❌ Erreur : ${err.message}`)
  }
}

function formatDate(date) {
  if (!date) return '-'
  return new Date(date).toLocaleDateString('fr-FR')
}

function formatCurrency(amount) {
  return new Intl.NumberFormat('fr-FR', {
    style: 'currency',
    currency: 'EUR'
  }).format(amount || 0)
}

// Lifecycle
onMounted(() => {
  loadInvoices()
  loadStats()
})
</script>

<style scoped>
.invoices-list {
  padding: 20px;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 30px;
}

.page-header h1 {
  font-size: 28px;
  font-weight: 600;
  margin: 0;
  color: #32325d;
}

.subtitle {
  color: #8898aa;
  margin: 5px 0 0;
}

/* Quick Stats */
.quick-stats {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
  gap: 20px;
  margin-bottom: 30px;
}

.stat-card {
  background: white;
  border-radius: 8px;
  padding: 20px;
  display: flex;
  align-items: center;
  gap: 15px;
  box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.stat-icon {
  width: 50px;
  height: 50px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 20px;
}

.stat-label {
  font-size: 13px;
  color: #8898aa;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.stat-value {
  font-size: 24px;
  font-weight: 600;
  color: #32325d;
}

/* Filters */
.filters-section {
  margin-bottom: 20px;
  padding: 20px;
}

.filters-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 15px;
  align-items: end;
}

.filter-item label {
  display: block;
  font-size: 13px;
  font-weight: 600;
  color: #525f7f;
  margin-bottom: 5px;
}

.filter-item input,
.filter-item select {
  width: 100%;
  padding: 10px;
  border: 1px solid #dee2e6;
  border-radius: 4px;
  font-size: 14px;
}

/* Table */
.table-responsive {
  overflow-x: auto;
}

.data-table {
  width: 100%;
  border-collapse: collapse;
}

.data-table thead {
  background: #f6f9fc;
}

.data-table th {
  padding: 15px;
  text-align: left;
  font-size: 12px;
  font-weight: 600;
  color: #8898aa;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  border-bottom: 1px solid #e9ecef;
}

.data-table th.sortable {
  cursor: pointer;
  user-select: none;
}

.data-table th.sortable:hover {
  background: #e9ecef;
}

.data-table td {
  padding: 15px;
  border-bottom: 1px solid #e9ecef;
  font-size: 14px;
  color: #525f7f;
}

.invoice-link {
  color: #5e72e4;
  text-decoration: none;
  font-weight: 600;
}

.invoice-link:hover {
  text-decoration: underline;
}

.customer-info {
  display: flex;
  flex-direction: column;
  gap: 3px;
}

.customer-name {
  font-weight: 600;
  color: #32325d;
}

.customer-email {
  font-size: 12px;
  color: #8898aa;
}

.badge {
  padding: 5px 12px;
  border-radius: 20px;
  font-size: 12px;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.badge-success { background: #d4edda; color: #155724; }
.badge-warning { background: #fff3cd; color: #856404; }
.badge-danger { background: #f8d7da; color: #721c24; }
.badge-secondary { background: #e2e3e5; color: #383d41; }

/* Actions */
.action-buttons {
  display: flex;
  gap: 5px;
  justify-content: flex-end;
}

.btn-icon {
  width: 32px;
  height: 32px;
  padding: 0;
  display: flex;
  align-items: center;
  justify-content: center;
}

/* Pagination */
.pagination {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 15px;
  padding: 20px;
  border-top: 1px solid #e9ecef;
}

.page-info {
  font-size: 14px;
  color: #525f7f;
}

/* Empty State */
.empty-state {
  text-align: center;
  padding: 60px 20px;
  color: #8898aa;
}

.empty-state i {
  color: #dee2e6;
  margin-bottom: 20px;
}

.empty-state h3 {
  font-size: 20px;
  color: #32325d;
  margin: 15px 0;
}

/* Loading */
.loading-container {
  text-align: center;
  padding: 60px 20px;
}

.spinner {
  border: 3px solid #f3f3f3;
  border-top: 3px solid #5e72e4;
  border-radius: 50%;
  width: 40px;
  height: 40px;
  animation: spin 1s linear infinite;
  margin: 0 auto 20px;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}
</style>