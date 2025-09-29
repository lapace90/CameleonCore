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
          <i class="fas fa-exclamation-circle"></i>
        </div>
        <div class="stat-content">
          <div class="stat-label">En retard</div>
          <div class="stat-value">{{ stats.counts?.overdue || 0 }}</div>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon" style="background: var(--info);">
          <i class="fas fa-euro-sign"></i>
        </div>
        <div class="stat-content">
          <div class="stat-label">Revenus</div>
          <div class="stat-value">{{ formatCurrency(stats.revenue?.total || 0) }}</div>
        </div>
      </div>
    </div>

    <!-- Filtres -->
    <div class="filters-card">
      <div class="filters-row">
        <div class="search-field">
          <label for="search">Rechercher</label>
          <div class="search-box">
            <i class="fas fa-search"></i>
            <input id="search" v-model="filters.search" type="text" placeholder="Numéro, client..."
              @input="onSearchInput" />
          </div>
        </div>

        <div class="filter-control">
          <label for="status">Statut</label>
          <select id="status" v-model="filters.status" @change="applyFilters">
            <option value="">Tous les statuts</option>
            <option value="paid">Payées</option>
            <option value="pending">En attente</option>
            <option value="overdue">En retard</option>
            <option value="canceled">Annulées</option>
          </select>
        </div>

        <div class="filter-control">
          <label for="start-date">Date début</label>
          <input id="start-date" v-model="filters.start_date" type="date" @change="applyFilters" />
        </div>

        <div class="filter-control">
          <label for="end-date">Date fin</label>
          <input id="end-date" v-model="filters.end_date" type="date" @change="applyFilters" />
        </div>

        <div class="reset-control">
          <button type="button" class="btn btn-outline btn-sm" @click="resetFilters" :disabled="!hasActiveFilters">
            <i class="fas fa-times"></i>
            Reset
          </button>
        </div>
      </div>
    </div>
    <!-- Toolbar -->
    <div class="list-toolbar">
      <div class="list-toolbar-left">
        <span class="results-count">
          <i class="fas fa-database"></i>
          {{ pagination.total }} résultat<span v-if="pagination.total > 1">s</span>
        </span>
      </div>
    </div>

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
            <th class="sortable" @click="changeSort('issue_date')">
              Dates
              <i :class="getSortIcon('issue_date')"></i>
            </th>
            <th class="sortable" @click="changeSort('status')">
              Statut
              <i :class="getSortIcon('status')"></i>
            </th>
            <th class="sortable" @click="changeSort('amount')">
              Montant
              <i :class="getSortIcon('amount')"></i>
            </th>
            <th class="actions-col">Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="invoice in invoices" :key="invoice.id">
            <!-- Facture -->
            <td>
              <strong>{{ invoice.invoice_number }}</strong><br>
              <small class="text-muted">{{ formatDate(invoice.created_at) }}</small>
            </td>

            <!-- Client -->
            <td>
              <strong>{{ invoice.customer_name }}</strong><br>
              <small v-if="invoice.customer?.email" class="text-muted">
                <i class="fas fa-envelope"></i> {{ invoice.customer.email }}
              </small>
            </td>

            <!-- Dates -->
            <td>
              <strong>Émission: {{ formatDate(invoice.issue_date) }}</strong><br>
              <small class="text-muted" :class="{ 'text-danger': invoice.is_overdue }">
                <i class="fas fa-calendar"></i> Échéance: {{ formatDate(invoice.due_date) }}
              </small>
            </td>

            <!-- Statut -->
            <td>
              <span :class="['status-badge', getStatusClass(invoice.status)]">
                {{ getStatusLabel(invoice.status) }}
              </span>
            </td>

            <!-- Montant -->
            <td>
              <span class="price-value">{{ formatCurrency(invoice.amount) }}</span>
            </td>

            <!-- Actions -->
            <td class="actions-col">
              <div class="table-actions">
                <button type="button" class="btn-icon" title="Voir" @click="viewInvoice(invoice)">
                  <i class="fas fa-eye"></i>
                </button>
                <button v-if="invoice.status === 'pending' || invoice.status === 'overdue'" type="button"
                  class="btn-icon" title="Marquer comme payée" @click="markAsPaid(invoice)">
                  <i class="fas fa-check"></i>
                </button>
                <button type="button" class="btn-icon" title="Envoyer par email" @click="sendEmail(invoice)">
                  <i class="fas fa-envelope"></i>
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
    <Pagination v-if="invoices.length > 0 && pagination.lastPage > 1" :pagination="pagination"
      @page-change="changePage" />
  </div>
</template>

<script>
import { useInvoiceStore } from '@/shared/stores/invoice'
import Pagination from '@/admin/views/products/components/Pagination.vue'
import { debounce } from '@/shared/utils/helpers'

export default {
  name: 'InvoicesList',
  components: {
    Pagination
  },

  data() {
    return {
      invoiceStore: useInvoiceStore(),
      loading: false,
      error: null,
      filters: {
        search: '',
        status: '',
        start_date: '',
        end_date: ''
      },
      sortBy: 'created_at',
      sortOrder: 'desc',
      statusOptions: [
        { value: 'paid', label: 'Payée' },
        { value: 'pending', label: 'En attente' },
        { value: 'overdue', label: 'En retard' },
        { value: 'canceled', label: 'Annulée' }
      ]
    }
  },

  computed: {
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

  created() {
    this.onSearchInput = debounce(this.applyFilters, 500)
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
      this.filters = {
        search: '',
        status: '',
        start_date: '',
        end_date: ''
      }
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
      this.loadInvoices(this.pagination.currentPage)
      this.loadStats()
    },

    viewInvoice(invoice) {
      this.$router.push({ name: 'InvoiceDetail', params: { id: invoice.id } })
    },

    async markAsPaid(invoice) {
      if (!confirm(`Marquer la facture ${invoice.invoice_number} comme payée ?`)) return

      try {
        await this.invoiceStore.markAsPaid(invoice.id, 'card')
        this.loadInvoices(this.pagination.currentPage)
      } catch (err) {
        alert(`Erreur: ${err.message}`)
      }
    },

    async sendEmail(invoice) {
      if (!confirm(`Envoyer la facture ${invoice.invoice_number} par email à ${invoice.customer?.email} ?`)) return

      try {
        await this.invoiceStore.sendEmail(invoice.id)
        alert('Email envoyé avec succès')
      } catch (err) {
        alert(`Erreur: ${err.message}`)
      }
    },

    async downloadPdf(invoice) {
      try {
        await this.invoiceStore.downloadPdf(invoice.id)
      } catch (err) {
        alert(`Erreur: ${err.message}`)
      }
    },

    async deleteInvoice(invoice) {
      if (!confirm(`Supprimer la facture ${invoice.invoice_number} ?\n\nCette action est irréversible.`)) return

      try {
        await this.invoiceStore.deleteInvoice(invoice.id)
        this.loadInvoices(this.pagination.currentPage)
      } catch (err) {
        alert(`Erreur: ${err.message}`)
      }
    },

    getStatusClass(status) {
      return {
        paid: 'status-active',
        pending: 'status-draft',
        overdue: 'status-inactive',
        canceled: 'status-inactive'
      }[status] || 'status-draft'
    },

    getStatusLabel(status) {
      return {
        paid: 'Payée',
        pending: 'En attente',
        overdue: 'En retard',
        canceled: 'Annulée'
      }[status] || status
    },

    formatDate(date) {
      if (!date) return '-'
      return new Date(date).toLocaleDateString('fr-FR')
    },

    formatCurrency(amount) {
      return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'EUR'
      }).format(amount || 0)
    }
  }
}
</script>

<style scoped>

.search-box {
  display: flex;
  align-items: center;
  border: 1px solid #e5e7eb;
  border-radius: 10px;
}

.search-box input {
  width: 100%;
  border: none;
  outline: none;
  background: transparent;
  font-size: 14px;
  color: #111827;
}
</style>