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
        <button type="button" class="btn btn-primary btn-sm" @click="refresh">
          <i class="fas fa-sync"></i>
          Actualiser
        </button>
      </div>
    </div>

    <!-- Stats Cards -->
    <div v-if="stats" class="stats-grid">
      <div class="stat-card">
        <div class="stat-icon" style="background: var(--primary, #5e72e4);">
          <i class="fas fa-file-invoice-dollar"></i>
        </div>
        <div class="stat-content">
          <div class="stat-label">Total Factures</div>
          <div class="stat-value">{{ stats.counts?.total || 0 }}</div>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon" style="background: var(--success, #2dce89);">
          <i class="fas fa-check-circle"></i>
        </div>
        <div class="stat-content">
          <div class="stat-label">Payées</div>
          <div class="stat-value">{{ stats.counts?.paid || 0 }}</div>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon" style="background: var(--warning, #fb6340);">
          <i class="fas fa-clock"></i>
        </div>
        <div class="stat-content">
          <div class="stat-label">En attente</div>
          <div class="stat-value">{{ stats.counts?.pending || 0 }}</div>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon" style="background: var(--danger, #f5365c);">
          <i class="fas fa-exclamation-circle"></i>
        </div>
        <div class="stat-content">
          <div class="stat-label">En retard</div>
          <div class="stat-value">{{ stats.counts?.overdue || 0 }}</div>
        </div>
      </div>
    </div>

    <!-- ✅ AdminFilterBar -->
    <AdminFilterBar v-model="filters" :default-filters="defaultFilters" :fields="filterFields"
      search-placeholder="Rechercher par numéro, client, email..." search-key="search" :search-debounce="500"
      reset-label="Réinitialiser" @apply="applyFilters" @reset="resetFilters">
      <!-- Slot results personnalisé -->
      <template #results="{ activeCount }">
        <span class="results-info">
          <i class="fas fa-file-invoice"></i>
          {{ pagination.total }} facture(s)
          <span v-if="activeCount > 0" class="text-muted">
            · {{ activeCount }} filtre(s) actif(s)
          </span>
        </span>
      </template>
    </AdminFilterBar>

    <!-- ✅ LoadingState pour loading -->
    <LoadingState v-if="loading" state="loading" variant="card" loading-text="Chargement des factures..." />

    <!-- ✅ LoadingState pour empty state -->
    <LoadingState v-else-if="invoices.length === 0" state="empty" variant="card" empty-title="Aucune facture"
      :empty-message="hasActiveFilters
        ? 'Aucune facture ne correspond à vos critères.'
        : 'Les factures apparaîtront automatiquement dès qu\'une réservation sera confirmée.'"
      empty-icon="fas fa-file-invoice" />

    <!-- Liste des factures -->
    <div v-else class="card">
      <div class="table-responsive">
        <table class="data-table">
          <thead>
            <tr>
              <th @click="changeSort('invoice_number')" class="sortable">
                Numéro
                <i :class="getSortIcon('invoice_number')"></i>
              </th>
              <th>Client</th>
              <th @click="changeSort('created_at')" class="sortable">
                Date
                <i :class="getSortIcon('created_at')"></i>
              </th>
              <th @click="changeSort('amount')" class="sortable">
                Montant
                <i :class="getSortIcon('amount')"></i>
              </th>
              <th>Statut</th>
              <th class="actions-column">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="invoice in invoices" :key="invoice.id">
              <td>
                <router-link :to="`/admin/invoices/${invoice.id}`" class="invoice-link">
                  {{ invoice.invoice_number }}
                </router-link>
              </td>
              <td>
                <div class="customer-info">
                  <div class="customer-name">
                    <b>{{ getCustomerName(invoice.customer) }}</b>
                  </div>
                  <div class="customer-email">
                    {{ invoice.customer?.email || '' }}
                  </div>
                </div>
              </td>
              <td>{{ formatDate(invoice.created_at) }}</td>
              <td>{{ formatCurrency(invoice.amount) }}</td>
              <td>
                <span :class="['badge', getStatusBadgeClass(invoice.status)]">
                  {{ getStatusLabel(invoice.status) }}
                </span>
              </td>
              <td>
                <div class="action-buttons">
                  <button type="button" class="btn-icon text-primary" title="Voir" @click="viewInvoice(invoice)">
                    <i class="fas fa-eye"></i>
                  </button>
                  <button type="button" class="btn-icon text-success" title="Télécharger PDF"
                    @click="downloadPdf(invoice)">
                    <i class="fas fa-download"></i>
                  </button>
                  <button v-if="canMarkAsPaid(invoice)" type="button" class="btn-icon text-info" title="Marquer payée"
                    @click="markAsPaid(invoice)">
                    <i class="fas fa-check-circle"></i>
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
    </div>

    <!-- ✅ Pagination -->
    <Pagination v-if="invoices.length > 0 && pagination.lastPage > 1" :pagination="pagination"
      @page-change="changePage" />
  </div>
</template>

<script>
import { useInvoiceStore } from '@/shared/stores/invoice'
import Pagination from '@/admin/views/products/components/Pagination.vue'
import AdminFilterBar from '@/admin/components/ui/AdminFilterBar.vue'
import LoadingState from '@/admin/components/ui/LoadingState.vue'

export default {
  name: 'InvoicesList',
  components: {
    Pagination,
    AdminFilterBar,
    LoadingState
  },

  data() {
    return {
      invoiceStore: useInvoiceStore(),
      loading: false,
      error: null,

      // ✅ Filtres par défaut
      defaultFilters: {
        search: '',
        status: '',
        start_date: '',
        end_date: ''
      },

      // ✅ Filtres actuels (liés à AdminFilterBar)
      filters: {
        search: '',
        status: '',
        start_date: '',
        end_date: ''
      },

      // Tri
      sort: {
        field: 'created_at',
        direction: 'desc'
      },

      // ✅ Pagination (format attendu par le composant Pagination)
      pagination: {
        currentPage: 1,
        perPage: 15,
        total: 0,
        lastPage: 1
      }
    }
  },

  computed: {
    // ✅ Configuration des champs de filtre pour AdminFilterBar
    filterFields() {
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
      return this.invoiceStore.invoices || []
    },

    stats() {
      return this.invoiceStore.stats
    },

    hasActiveFilters() {
      return this.filters.search ||
        this.filters.status ||
        this.filters.start_date ||
        this.filters.end_date
    }
  },

  async created() {
    await this.loadInvoices()
    await this.loadStats()
  },

  methods: {
    async loadInvoices() {
      this.loading = true
      this.error = null

      try {
        const response = await this.invoiceStore.fetchInvoices({
          ...this.filters,
          sort_by: this.sort.field,
          sort_order: this.sort.direction,
          page: this.pagination.currentPage,
          per_page: this.pagination.perPage
        })

        // ✅ Mettre à jour la pagination depuis la réponse
        if (response?.meta) {
          this.pagination = {
            currentPage: response.meta.current_page || 1,
            perPage: response.meta.per_page || 15,
            total: response.meta.total || 0,
            lastPage: response.meta.last_page || 1
          }
        }
      } catch (error) {
        console.error('Erreur chargement factures:', error)
        this.error = error.message
      } finally {
        this.loading = false
      }
    },

    async loadStats() {
      try {
        await this.invoiceStore.fetchStats()
      } catch (error) {
        console.error('Erreur chargement stats:', error)
      }
    },

    async applyFilters() {
      this.pagination.currentPage = 1
      await this.loadInvoices()
    },

    async resetFilters() {
      this.filters = { ...this.defaultFilters }
      this.pagination.currentPage = 1
      await this.loadInvoices()
    },

    async refresh() {
      await this.loadInvoices()
      await this.loadStats()
    },

    async changePage(page) {
      this.pagination.currentPage = page
      await this.loadInvoices()
    },

    changeSort(field) {
      if (this.sort.field === field) {
        this.sort.direction = this.sort.direction === 'asc' ? 'desc' : 'asc'
      } else {
        this.sort.field = field
        this.sort.direction = 'desc'
      }
      this.applyFilters()
    },

    getSortIcon(field) {
      if (this.sort.field !== field) {
        return 'fas fa-sort text-muted'
      }
      return this.sort.direction === 'asc'
        ? 'fas fa-sort-up'
        : 'fas fa-sort-down'
    },

    // Actions
    viewInvoice(invoice) {
      this.$router.push(`/admin/invoices/${invoice.id}`)
    },

    createInvoice() {
      this.$router.push('/admin/invoices/create')
    },

    async downloadPdf(invoice) {
      // Débugger pour vérifier la structure de l'objet invoice
      console.log('Invoice object:', invoice)
      console.log('Invoice ID:', invoice.id)

      try {
        await this.invoiceStore.downloadPdf(invoice.id)
      } catch (err) {
        alert(`Erreur: ${err.message}`)
      }
    },

    canMarkAsPaid(invoice) {
      return invoice.status === 'pending' || invoice.status === 'overdue'
    },

    async markAsPaid(invoice) {
      if (!confirm(`Marquer la facture ${invoice.invoice_number} comme payée ?`)) {
        return
      }

      try {
        await this.invoiceStore.markAsPaid(invoice.id)
        alert('✅ Facture marquée comme payée')
        await this.loadInvoices()
      } catch (error) {
        alert('❌ Erreur lors de la mise à jour')
      }
    },

    async deleteInvoice(invoice) {
      if (!confirm(`Supprimer la facture ${invoice.invoice_number} ?\n\nCette action est irréversible.`)) {
        return
      }

      try {
        await this.invoiceStore.deleteInvoice(invoice.id)
        alert('✅ Facture supprimée')
        await this.loadInvoices()
      } catch (error) {
        alert('❌ Erreur lors de la suppression')
      }
    },

    // Formatage
    getCustomerName(customer) {
      if (!customer) return 'N/A'

      if (customer.name && customer.last_name) {
        return `${customer.name} ${customer.last_name}`
      }

      return customer.name || customer.email || 'N/A'
    },

    formatDate(date) {
      if (!date) return 'N/A'
      return new Date(date).toLocaleDateString('fr-FR', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
      })
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

    getStatusBadgeClass(status) {
      const classes = {
        paid: 'badge-success',
        pending: 'badge-warning',
        overdue: 'badge-danger',
        canceled: 'badge-secondary'
      }
      return classes[status] || 'badge-secondary'
    }
  }
}
</script>

<style scoped>
.invoices-page {
  margin: 2rem;
  padding: 1rem;
}

.data-table {
  text-align: center !important;
}

.card,
.table-responsive {
  width: 100%;
}

/* étale la table et évite l’auto-shrink */
.data-table {
  width: 100%;
  table-layout: fixed;
  /* répartit l'espace sur toutes les colonnes */
  text-align: left;
}

th {
  padding: .7rem;
  background-color: rgb(245, 230, 210);
}

.data-table th,
.data-table td {
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.data-table tbody td {
  padding: 15px;
  border-bottom: 1px solid #e9ecef;
  font-size: 14px;
  vertical-align: middle;
}

.data-table tbody tr:hover {
  background: #f8f9fa;
}

.action-buttons {
  display: flex;
  gap: 8px;
  justify-content: center;
}
</style>