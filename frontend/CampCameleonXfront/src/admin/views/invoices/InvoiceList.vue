<template>
  <div class="invoices-page">
    <!-- Header -->
    <div class="page-header">
      <div class="header-left">
        <h1 class="page-title">
          <i class="fas fa-file-invoice"></i>
          Factures
        </h1>
        <p class="page-subtitle">Gestion des factures et paiements</p>
      </div>
      <div class="header-actions">
        <button type="button" class="btn btn-outline btn-sm" @click="refresh">
          <i class="fas fa-sync"></i>
          Actualiser
        </button>
        <button type="button" class="btn btn-primary btn-sm" @click="$router.push({ name: 'InvoiceCreate' })">
          <i class="fas fa-plus"></i>
          Nouvelle Facture
        </button>
      </div>
    </div>

    <!-- Stats Cards -->
    <div class="stats-grid" v-if="stats">
      <div class="stat-card">
        <div class="stat-icon" style="background: var(--primary);">
          <i class="fas fa-file-invoice-dollar"></i>
        </div>
        <div class="stat-content">
          <div class="stat-label">Total Factures</div>
          <div class="stat-value">{{ stats.counts?.total || 0 }}</div>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon" style="background: var(--success);">
          <i class="fas fa-check-circle"></i>
        </div>
        <div class="stat-content">
          <div class="stat-label">Payées</div>
          <div class="stat-value">{{ stats.counts?.paid || 0 }}</div>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon" style="background: var(--warning);">
          <i class="fas fa-clock"></i>
        </div>
        <div class="stat-content">
          <div class="stat-label">En attente</div>
          <div class="stat-value">{{ stats.counts?.pending || 0 }}</div>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon" style="background: var(--danger);">
          <i class="fas fa-exclamation-circle"></i>
        </div>
        <div class="stat-content">
          <div class="stat-label">En retard</div>
          <div class="stat-value">{{ stats.counts?.overdue || 0 }}</div>
        </div>
      </div>
    </div>

    <!-- ✅ AdminFilterBar remplace le bloc filters-card -->
    <AdminFilterBar
      v-model="filters"
      :default-filters="defaultFilters"
      :fields="invoiceFilterFields"
      search-placeholder="Numéro, client, email..."
      :search-debounce="500"
      reset-label="Réinitialiser"
      @apply="applyFilters"
      @reset="resetFilters"
    />

    <!-- Loading -->
    <div v-if="loading" class="loading-container">
      <div class="spinner"></div>
      <p class="text-muted">Chargement des factures...</p>
    </div>

    <!-- Empty State -->
    <div v-else-if="invoices.length === 0" class="empty-state">
      <i class="fas fa-file-invoice fa-3x"></i>
      <h3>Aucune facture</h3>
      <p v-if="hasActiveFilters">
        Aucune facture ne correspond à vos critères de recherche.
      </p>
      <p v-else>
        Les factures apparaîtront dès qu'une réservation sera confirmée.
      </p>
    </div>

    <!-- Table -->
    <div v-else class="table-container">
      <table class="table">
        <thead>
          <tr>
            <th @click="changeSort('invoice_number')" class="sortable">
              Facture
              <i :class="getSortIcon('invoice_number')"></i>
            </th>
            <th>Client</th>
            <th @click="changeSort('created_at')" class="sortable">
              Date
              <i :class="getSortIcon('created_at')"></i>
            </th>
            <th @click="changeSort('status')" class="sortable">
              Statut
              <i :class="getSortIcon('status')"></i>
            </th>
            <th @click="changeSort('amount')" class="sortable text-right">
              Montant
              <i :class="getSortIcon('amount')"></i>
            </th>
            <th class="actions-col">Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="invoice in invoices" :key="invoice.id">
            <td>
              <strong>{{ invoice.invoice_number }}</strong><br>
              <small v-if="invoice.reservation" class="text-muted">
                <i class="fas fa-calendar"></i> Rés. #{{ invoice.reservation.id }}
              </small>
            </td>

            <td>
              <strong>{{ getCustomerName(invoice) }}</strong><br>
              <small v-if="getCustomerEmail(invoice)" class="text-muted">
                <i class="fas fa-envelope"></i> {{ getCustomerEmail(invoice) }}
              </small>
            </td>

            <td>
              <span>{{ formatDate(invoice.created_at) }}</span><br>
              <small v-if="invoice.due_date" class="text-muted">
                Échéance: {{ formatDate(invoice.due_date) }}
              </small>
            </td>

            <td>
              <span :class="['status-badge', getStatusClass(invoice.status)]">
                {{ getStatusLabel(invoice.status) }}
              </span>
            </td>

            <td class="text-right">
              <strong class="price-value">{{ formatCurrency(invoice.amount) }}</strong>
            </td>

            <td class="actions-col">
              <div class="table-actions">
                <button type="button" class="btn-icon" title="Voir" @click="viewInvoice(invoice)">
                  <i class="fas fa-eye"></i>
                </button>
                <button type="button" class="btn-icon" title="Télécharger PDF" @click="downloadPdf(invoice)">
                  <i class="fas fa-download"></i>
                </button>
                <button type="button" class="btn-icon text-danger" title="Supprimer" @click="deleteInvoice(invoice)">
                  <i class="fas fa-trash"></i>
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <Pagination 
      v-if="invoices.length > 0 && pagination.lastPage > 1" 
      :pagination="pagination"
      @page-change="changePage" 
    />
  </div>
</template>

<script>
import { useInvoiceStore } from '@/shared/stores/invoice'
import Pagination from '@/admin/views/products/components/Pagination.vue'
import AdminFilterBar from '@/admin/components/ui/AdminFilterBar.vue'

export default {
  name: 'InvoicesList',
  components: {
    Pagination,
    AdminFilterBar
  },

  data() {
    return {
      invoiceStore: useInvoiceStore(),
      loading: false,
      error: null,
      
      // ✅ Filtres pour AdminFilterBar
      defaultFilters: {
        search: '',
        status: '',
        start_date: '',
        end_date: ''
      },
      
      filters: {
        search: '',
        status: '',
        start_date: '',
        end_date: ''
      },
      
      sortBy: 'created_at',
      sortOrder: 'desc'
    }
  },

  computed: {
    // ✅ Config AdminFilterBar
    invoiceFilterFields() {
      return [
        {
          key: 'status',
          type: 'select',
          placeholder: 'Tous les statuts',
          options: [
            { value: 'paid', label: 'Payées' },
            { value: 'pending', label: 'En attente' },
            { value: 'overdue', label: 'En retard' },
            { value: 'canceled', label: 'Annulées' }
          ]
        },
        {
          key: 'start_date',
          type: 'date',
          placeholder: 'Date début'
        },
        {
          key: 'end_date',
          type: 'date',
          placeholder: 'Date fin'
        }
      ]
    },

    invoices() {
      return this.invoiceStore.invoices
    },

    stats() {
      return this.invoiceStore.stats
    },

    pagination() {
      return this.invoiceStore.pagination
    },

    hasActiveFilters() {
      return this.filters.search || this.filters.status || this.filters.start_date || this.filters.end_date
    }
  },

  async mounted() {
    await this.loadInvoices()
    await this.loadStats()
  },

  methods: {
    async loadInvoices(page = 1) {
      this.loading = true
      this.error = null

      try {
        await this.invoiceStore.fetchInvoices(page)
      } catch (err) {
        this.error = err.message
        console.error('Erreur chargement factures:', err)
      } finally {
        this.loading = false
      }
    },

    async loadStats() {
      try {
        await this.invoiceStore.fetchStats()
      } catch (err) {
        console.error('Erreur chargement stats:', err)
      }
    },

    applyFilters() {
      this.invoiceStore.setFilters({
        search: this.filters.search,
        status: this.filters.status,
        start_date: this.filters.start_date,
        end_date: this.filters.end_date,
        sort_by: this.sortBy,
        sort_order: this.sortOrder
      })
      this.loadInvoices(1)
    },

    resetFilters() {
      this.filters = { ...this.defaultFilters }
      this.invoiceStore.resetFilters()
      this.loadInvoices(1)
    },

    changeSort(field) {
      if (this.sortBy === field) {
        this.sortOrder = this.sortOrder === 'asc' ? 'desc' : 'asc'
      } else {
        this.sortBy = field
        this.sortOrder = 'desc'
      }
      this.applyFilters()
    },

    getSortIcon(field) {
      if (this.sortBy !== field) return 'fas fa-sort'
      return this.sortOrder === 'asc' ? 'fas fa-sort-up' : 'fas fa-sort-down'
    },

    changePage(page) {
      this.loadInvoices(page)
    },

    refresh() {
      this.loadInvoices()
      this.loadStats()
    },

    // Helpers
    getCustomerName(invoice) {
      if (invoice.customer) {
        return invoice.customer.name || invoice.customer.email
      }
      return 'Client inconnu'
    },

    getCustomerEmail(invoice) {
      return invoice.customer?.email
    },

    formatDate(dateString) {
      if (!dateString) return '-'
      return new Date(dateString).toLocaleDateString('fr-FR')
    },

    formatCurrency(amount) {
      if (!amount) return '0,00 €'
      return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'EUR'
      }).format(amount)
    },

    getStatusLabel(status) {
      const labels = {
        paid: 'Payée',
        pending: 'En attente',
        overdue: 'En retard',
        canceled: 'Annulée'
      }
      return labels[status] || status
    },

    getStatusClass(status) {
      const classes = {
        paid: 'status-success',
        pending: 'status-warning',
        overdue: 'status-danger',
        canceled: 'status-secondary'
      }
      return classes[status] || ''
    },

    // Actions
    viewInvoice(invoice) {
      this.$router.push({ name: 'InvoiceDetail', params: { id: invoice.id } })
    },

    async downloadPdf(invoice) {
      try {
        // TODO: Implémenter téléchargement PDF
        alert(`Téléchargement PDF de la facture ${invoice.invoice_number}`)
      } catch (error) {
        console.error('Erreur téléchargement PDF:', error)
      }
    },

    async deleteInvoice(invoice) {
      if (!confirm(`Supprimer la facture ${invoice.invoice_number} ?`)) {
        return
      }

      try {
        await this.invoiceStore.deleteInvoice(invoice.id)
        await this.loadInvoices()
      } catch (error) {
        console.error('Erreur suppression:', error)
        alert('Erreur lors de la suppression')
      }
    }
  }
}
</script>