<template>
  <div class="reservations-page">
    <div class="page-header">
      <div class="header-left">
        <h1 class="page-title">
          <i class="fas fa-calendar-check"></i>
          Réservations
        </h1>
        <p class="page-subtitle">
          Suivez et gérez toutes les réservations clients
        </p>
      </div>

      <div class="header-actions">
        <button type="button" class="btn btn-outline btn-sm" @click="refresh" :disabled="loading">
          <i :class="['fas', 'fa-sync-alt', { 'fa-spin': loading }]"></i>
          Rafraîchir
        </button>

        <router-link to="/admin/agenda" class="btn btn-primary btn-sm">
          <i class="fas fa-calendar-alt"></i>
          Ouvrir le calendrier
        </router-link>
      </div>
    </div>

    <div class="filters-card">
      <div class="filters-row">
        <div class="search-field">
          <label for="search-reservations">Recherche</label>
          <div class="search-box">
            <i class="fas fa-search"></i>
            <input
              id="search-reservations"
              v-model="filters.search"
              type="text"
              placeholder="Nom du client, email, numéro de réservation..."
              @input="onSearchInput"
            />
          </div>
        </div>

        <div class="filter-control">
          <label for="status-filter">Statut</label>
          <select id="status-filter" v-model="filters.status" @change="applyFilters">
            <option value="">Tous les statuts</option>
            <option v-for="option in statusOptions" :key="option.value" :value="option.value">
              {{ option.label }}
            </option>
          </select>
        </div>

        <div class="filter-control">
          <label for="start-date">Arrivée après le</label>
          <input id="start-date" type="date" v-model="filters.startDate" @change="applyFilters" />
        </div>

        <div class="filter-control">
          <label for="end-date">Départ avant le</label>
          <input id="end-date" type="date" v-model="filters.endDate" @change="applyFilters" />
        </div>

        <div class="filter-control reset-control">
          <label>&nbsp;</label>
          <button
            type="button"
            class="btn btn-outline btn-sm"
            @click="resetFilters"
            :disabled="!hasActiveFilters"
          >
            <i class="fas fa-times"></i>
            Réinitialiser
          </button>
        </div>
      </div>
    </div>

    <div class="list-toolbar">
      <div class="list-toolbar-left">
        <span class="results-count">
          <i class="fas fa-database"></i>
          {{ pagination.total }} résultat<span v-if="pagination.total > 1">s</span>
        </span>
        <span v-if="hasActiveFilters" class="filters-indicator">
          <i class="fas fa-filter"></i>
          Filtres actifs
        </span>
      </div>

      <div class="list-toolbar-right">
        <label for="per-page">Résultats par page</label>
        <select id="per-page" v-model.number="pagination.perPage" @change="changePerPage">
          <option v-for="option in perPageOptions" :key="option" :value="option">
            {{ option }}
          </option>
        </select>
      </div>
    </div>

    <div v-if="loading" class="state-card">
      <i class="fas fa-spinner fa-spin"></i>
      <p>Chargement des réservations...</p>
    </div>

    <div v-else-if="error" class="state-card error">
      <i class="fas fa-exclamation-triangle"></i>
      <p>{{ error }}</p>
      <button type="button" class="btn btn-outline btn-sm" @click="fetchReservations">
        <i class="fas fa-redo"></i>
        Réessayer
      </button>
    </div>

    <div v-else-if="reservations.length === 0" class="state-card empty">
      <i class="fas fa-clipboard-list"></i>
      <h3>Aucune réservation trouvée</h3>
      <p v-if="hasActiveFilters">
        Ajustez vos filtres pour élargir la recherche.
      </p>
      <p v-else>
        Les réservations apparaîtront dès qu'un client effectuera une demande.
      </p>
    </div>

    <div v-else class="table-wrapper">
      <table class="reservations-table table">
        <thead>
          <tr>
            <th>
              <button type="button" class="sort-button" @click="changeSort('date')">
                Réservation
                <i :class="getSortIcon('date')"></i>
              </button>
            </th>
            <th>Client</th>
            <th>
              <button type="button" class="sort-button" @click="changeSort('checkin')">
                Séjour
                <i :class="getSortIcon('checkin')"></i>
              </button>
            </th>
            <th>
              <button type="button" class="sort-button" @click="changeSort('status')">
                Statut
                <i :class="getSortIcon('status')"></i>
              </button>
            </th>
            <th>
              <button type="button" class="sort-button" @click="changeSort('amount')">
                Montant
                <i :class="getSortIcon('amount')"></i>
              </button>
            </th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="reservation in reservations" :key="reservation.id">
            <td class="reservation-meta">
              <div class="reservation-id">#{{ reservation.id }}</div>
              <div class="reservation-created">
                Créée le {{ formatDate(reservation.date) }}
              </div>
              <div v-if="reservation.invoice_number" class="reservation-invoice">
                <i class="fas fa-file-invoice"></i>
                {{ reservation.invoice_number }}
              </div>
            </td>
            <td class="customer-cell">
              <div class="customer-name">{{ getCustomerName(reservation) }}</div>
              <div class="customer-contact">
                <span v-if="reservation.customer?.email">
                  <i class="fas fa-envelope"></i>
                  {{ reservation.customer.email }}
                </span>
                <span v-if="reservation.customer?.phone">
                  <i class="fas fa-phone"></i>
                  {{ reservation.customer.phone }}
                </span>
              </div>
            </td>
            <td class="stay-cell">
              <div class="stay-dates">{{ formatDateRange(reservation.checkin, reservation.checkout) }}</div>
              <div class="stay-meta">
                <span v-if="getNights(reservation)">
                  <i class="fas fa-moon"></i>
                  {{ getNights(reservation) }} nuit<span v-if="getNights(reservation) > 1">s</span>
                </span>
                <span>
                  <i class="fas fa-user-friends"></i>
                  {{ getGuestsLabel(reservation) }}
                </span>
              </div>
            </td>
            <td class="status-cell">
              <span :class="['status-badge', getStatusClass(reservation.status)]">
                <i :class="getStatusIcon(reservation.status)"></i>
                {{ getStatusLabel(reservation.status) }}
              </span>
              <span v-if="getPaymentStatusLabel(reservation.payment_status)" class="payment-badge">
                {{ getPaymentStatusLabel(reservation.payment_status) }}
              </span>
            </td>
            <td class="amount-cell">
              <div class="amount-primary">{{ formatAmount(reservation.amount) }}</div>
              <div class="amount-secondary">
                <i class="fas fa-map-marker-alt"></i>
                {{ getBookingSourceLabel(reservation.booking_source) }}
              </div>
            </td>
            <td class="actions-cell">
              <div class="action-buttons">
                <button
                  type="button"
                  class="btn-icon"
                  title="Voir la réservation"
                  @click="viewReservation(reservation)"
                >
                  <i class="fas fa-eye"></i>
                </button>
                <button
                  type="button"
                  class="btn-icon"
                  title="Modifier"
                  @click="editReservation(reservation)"
                >
                  <i class="fas fa-edit"></i>
                </button>
                <button
                  type="button"
                  class="btn-icon text-danger"
                  title="Supprimer"
                  @click="deleteReservation(reservation)"
                >
                  <i class="fas fa-trash"></i>
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <Pagination
      v-if="reservations.length > 0 && pagination.lastPage > 1"
      :pagination="pagination"
      @page-change="changePage"
    />
  </div>
</template>

<script>
import AdminApi from '@/services/AdminApi'
import Pagination from '@/admin/views/products/components/Pagination.vue'
import { debounce } from '@/shared/utils/helpers'

export default {
  name: 'ReservationsList',
  components: {
    Pagination
  },
  data() {
    return {
      loading: false,
      error: null,
      reservations: [],
      filters: {
        search: '',
        status: '',
        startDate: '',
        endDate: ''
      },
      statusOptions: [
        { label: 'Confirmée', value: 'confirmed' },
        { label: 'En attente', value: 'pending' },
        { label: 'Annulée', value: 'cancelled' },
        { label: 'Terminée', value: 'completed' }
      ],
      sort: {
        field: 'checkin',
        direction: 'desc'
      },
      pagination: {
        currentPage: 1,
        perPage: 10,
        total: 0,
        lastPage: 1
      },
      perPageOptions: [10, 25, 50],
      debouncedSearch: null
    }
  },
  computed: {
    hasActiveFilters() {
      return Boolean(
        this.filters.search ||
        this.filters.status ||
        this.filters.startDate ||
        this.filters.endDate
      )
    }
  },
  created() {
    this.debouncedSearch = debounce(() => {
      this.pagination.currentPage = 1
      this.fetchReservations()
    }, 400)
  },
  mounted() {
    this.fetchReservations()
  },
  methods: {
    async fetchReservations() {
      if (this.filters.startDate && this.filters.endDate && this.filters.startDate > this.filters.endDate) {
        this.error = 'La date de début ne peut pas être postérieure à la date de fin.'
        return
      }

      this.loading = true
      this.error = null

      const params = {
        page: this.pagination.currentPage,
        itemsPerPage: this.pagination.perPage,
        search: this.filters.search?.trim() || undefined,
        status: this.filters.status || undefined,
        startDate: this.filters.startDate || undefined,
        endDate: this.filters.endDate || undefined,
        sortField: this.sort.field,
        sortDirection: this.sort.direction
      }

      try {
        const { data, meta } = await AdminApi.getReservations(params)
        this.reservations = data || []
        if (meta) {
          this.updatePagination(meta)
        } else {
          this.pagination.total = this.reservations.length
          this.pagination.lastPage = 1
        }
      } catch (error) {
        console.error('Erreur lors du chargement des réservations:', error)
        this.error = error.message || 'Erreur lors du chargement des réservations.'
        this.reservations = []
      } finally {
        this.loading = false
      }
    },
    updatePagination(meta) {
      this.pagination.total = Number(meta.total) || 0
      this.pagination.perPage = Number(meta.perPage) || this.pagination.perPage
      this.pagination.currentPage = Number(meta.currentPage) || 1
      this.pagination.lastPage = Number(meta.lastPage) || Math.max(1, Math.ceil(this.pagination.total / this.pagination.perPage))
    },
    refresh() {
      this.fetchReservations()
    },
    onSearchInput() {
      if (typeof this.debouncedSearch === 'function') {
        this.debouncedSearch()
      }
    },
    applyFilters() {
      this.pagination.currentPage = 1
      this.fetchReservations()
    },
    resetFilters() {
      this.filters.search = ''
      this.filters.status = ''
      this.filters.startDate = ''
      this.filters.endDate = ''
      this.applyFilters()
    },
    changePage(page) {
      if (page === this.pagination.currentPage) return
      this.pagination.currentPage = page
      this.fetchReservations()
    },
    changePerPage() {
      this.pagination.currentPage = 1
      this.fetchReservations()
    },
    changeSort(field) {
      if (this.sort.field === field) {
        this.sort.direction = this.sort.direction === 'asc' ? 'desc' : 'asc'
      } else {
        this.sort.field = field
        this.sort.direction = 'asc'
      }
      this.fetchReservations()
    },
    getSortIcon(field) {
      if (this.sort.field !== field) return 'fas fa-sort text-muted'
      return this.sort.direction === 'asc' ? 'fas fa-sort-up' : 'fas fa-sort-down'
    },
    formatDate(date, options = {}) {
      if (!date) {
        return '—'
      }
      try {
        return new Intl.DateTimeFormat('fr-FR', {
          day: '2-digit',
          month: 'short',
          year: 'numeric',
          ...options
        }).format(new Date(date))
      } catch (error) {
        return date
      }
    },
    formatDateRange(start, end) {
      const startFormatted = this.formatDate(start)
      const endFormatted = this.formatDate(end)
      return `${startFormatted} → ${endFormatted}`
    },
    getNights(reservation) {
      if (!reservation?.checkin || !reservation?.checkout) return null
      const start = new Date(reservation.checkin)
      const end = new Date(reservation.checkout)
      const diff = Math.round((end - start) / (1000 * 60 * 60 * 24))
      return diff > 0 ? diff : null
    },
    getGuestsLabel(reservation) {
      const adults = Number(reservation?.number_of_adults) || 0
      const children = Number(reservation?.number_of_children) || 0
      const parts = []
      if (adults) {
        parts.push(`${adults} ${adults > 1 ? 'adultes' : 'adulte'}`)
      }
      if (children) {
        parts.push(`${children} ${children > 1 ? 'enfants' : 'enfant'}`)
      }
      return parts.length ? parts.join(' • ') : '—'
    },
    formatAmount(amount) {
      if (amount === null || amount === undefined || amount === '') {
        return '—'
      }
      try {
        return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(Number(amount))
      } catch (error) {
        return `${amount} €`
      }
    },
    getBookingSourceLabel(source) {
      const labels = {
        website: 'Site web',
        phone: 'Téléphone',
        agent: 'Agence',
        email: 'Email',
        partner: 'Partenaire'
      }
      return labels[source] || source || '—'
    },
    getStatusLabel(status) {
      const labels = {
        confirmed: 'Confirmée',
        pending: 'En attente',
        cancelled: 'Annulée',
        completed: 'Terminée',
        draft: 'Brouillon'
      }
      return labels[status] || (status ? status.charAt(0).toUpperCase() + status.slice(1) : '—')
    },
    getStatusClass(status) {
      const classes = {
        confirmed: 'status-confirmed',
        pending: 'status-pending',
        cancelled: 'status-cancelled',
        completed: 'status-completed',
        draft: 'status-draft'
      }
      return classes[status] || 'status-unknown'
    },
    getStatusIcon(status) {
      const icons = {
        confirmed: 'fas fa-check-circle',
        pending: 'fas fa-hourglass-half',
        cancelled: 'fas fa-times-circle',
        completed: 'fas fa-flag-checkered',
        draft: 'fas fa-pencil-alt'
      }
      return icons[status] || 'fas fa-question-circle'
    },
    getPaymentStatusLabel(status) {
      const labels = {
        paid: 'Payée',
        pending: 'En attente de paiement',
        failed: 'Paiement échoué',
        refunded: 'Remboursée'
      }
      return labels[status] || null
    },
    getCustomerName(reservation) {
      const customer = reservation?.customer || {}
      if (customer.name && customer.last_name) {
        return `${customer.name} ${customer.last_name}`
      }
      return customer.name || customer.last_name || reservation?.customer_name || 'Client inconnu'
    },
    viewReservation(reservation) {
      this.$router.push({ name: 'ReservationDetail', params: { id: reservation.id } })
    },
    editReservation(reservation) {
      this.$router.push({ name: 'ReservationDetail', params: { id: reservation.id }, query: { mode: 'edit' } })
    },
    async deleteReservation(reservation) {
      const confirmed = window.confirm(`Supprimer la réservation #${reservation.id} ?`)
      if (!confirmed) {
        return
      }
      try {
        await AdminApi.deleteReservation(reservation.id)
        await this.fetchReservations()
      } catch (error) {
        console.error('Erreur lors de la suppression de la réservation:', error)
        this.error = error.message || 'Impossible de supprimer la réservation.'
      }
    }
  }
}
</script>

<style scoped>
.reservations-page {
  display: flex;
  flex-direction: column;
  gap: 24px;
  padding: 24px;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 16px;
  background: #ffffff;
  padding: 24px;
  border-radius: 16px;
  box-shadow: 0 10px 30px rgba(15, 23, 42, 0.05);
  flex-wrap: wrap;
}

.header-left {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.page-title {
  display: flex;
  align-items: center;
  gap: 12px;
  font-size: 24px;
  margin: 0;
  color: #111827;
}

.page-title i {
  color: #2563eb;
}

.page-subtitle {
  margin: 0;
  color: #6b7280;
  font-size: 15px;
}

.header-actions {
  display: flex;
  align-items: center;
  gap: 12px;
  flex-wrap: wrap;
}

.filters-card {
  background: #ffffff;
  padding: 20px;
  border-radius: 16px;
  box-shadow: 0 6px 24px rgba(15, 23, 42, 0.04);
}

.filters-row {
  display: grid;
  grid-template-columns: minmax(260px, 1.4fr) repeat(3, minmax(160px, 1fr)) minmax(120px, auto);
  gap: 16px;
  align-items: end;
}

.search-field,
.filter-control {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.search-box {
  position: relative;
  display: flex;
  align-items: center;
  gap: 8px;
  background: #f9fafb;
  border: 1px solid #e5e7eb;
  border-radius: 10px;
  padding: 10px 14px;
}

.search-box i {
  color: #9ca3af;
}

.search-box input {
  width: 100%;
  border: none;
  outline: none;
  background: transparent;
  font-size: 14px;
  color: #111827;
}

.filter-control select,
.filter-control input {
  width: 100%;
  border: 1px solid #e5e7eb;
  border-radius: 10px;
  padding: 10px 12px;
  font-size: 14px;
  color: #111827;
  background: #f9fafb;
}

.filter-control label {
  font-size: 13px;
  font-weight: 600;
  color: #6b7280;
}

.reset-control {
  align-items: flex-end;
}

.list-toolbar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 16px;
  padding: 0 4px;
  flex-wrap: wrap;
}

.list-toolbar-left {
  display: flex;
  align-items: center;
  gap: 12px;
  color: #6b7280;
}

.results-count {
  font-weight: 600;
  color: #111827;
  display: inline-flex;
  align-items: center;
  gap: 8px;
}

.filters-indicator {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 6px 12px;
  background: #eef2ff;
  color: #4338ca;
  border-radius: 9999px;
  font-size: 13px;
}

.list-toolbar-right {
  display: flex;
  align-items: center;
  gap: 12px;
  color: #6b7280;
}

.list-toolbar-right select {
  border: 1px solid #e5e7eb;
  border-radius: 10px;
  padding: 8px 12px;
  background: #ffffff;
  font-size: 14px;
  color: #111827;
}

.state-card {
  background: #ffffff;
  border-radius: 16px;
  padding: 48px 24px;
  text-align: center;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 16px;
  color: #111827;
  box-shadow: 0 8px 32px rgba(15, 23, 42, 0.04);
}

.state-card i {
  font-size: 32px;
  color: #2563eb;
}

.state-card.error i {
  color: #dc2626;
}

.state-card.empty i {
  color: #6b7280;
}

.table-wrapper {
  background: #ffffff;
  border-radius: 16px;
  box-shadow: 0 12px 40px rgba(15, 23, 42, 0.05);
  overflow: hidden;
}

.reservations-table {
  width: 100%;
  border-collapse: collapse;
}

.reservations-table thead {
  background: #f9fafb;
}

.reservations-table th {
  padding: 16px;
  font-size: 13px;
  font-weight: 700;
  text-transform: uppercase;
  color: #6b7280;
  letter-spacing: 0.05em;
  text-align: left;
}

.reservations-table td {
  padding: 20px 16px;
  border-top: 1px solid #f3f4f6;
  vertical-align: top;
}

.sort-button {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  background: transparent;
  border: none;
  color: inherit;
  font: inherit;
  cursor: pointer;
  padding: 0;
}

.sort-button i {
  color: #9ca3af;
  transition: color 0.2s ease;
}

.sort-button:hover i {
  color: #111827;
}

.reservation-meta {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.reservation-id {
  font-weight: 700;
  color: #111827;
}

.reservation-created {
  font-size: 13px;
  color: #6b7280;
}

.reservation-invoice {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  font-size: 12px;
  color: #2563eb;
}

.customer-cell {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.customer-name {
  font-weight: 600;
  color: #111827;
}

.customer-contact {
  display: flex;
  flex-direction: column;
  gap: 4px;
  font-size: 13px;
  color: #6b7280;
}

.customer-contact span {
  display: inline-flex;
  align-items: center;
  gap: 6px;
}

.stay-cell {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.stay-dates {
  font-weight: 600;
  color: #111827;
}

.stay-meta {
  display: flex;
  flex-direction: column;
  gap: 6px;
  font-size: 13px;
  color: #6b7280;
}

.stay-meta span {
  display: inline-flex;
  align-items: center;
  gap: 6px;
}

.status-cell {
  display: flex;
  flex-direction: column;
  gap: 8px;
  align-items: flex-start;
}

.status-badge {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 6px 12px;
  border-radius: 9999px;
  font-size: 13px;
  font-weight: 600;
}

.status-confirmed {
  background: #dcfce7;
  color: #166534;
}

.status-pending {
  background: #fef3c7;
  color: #92400e;
}

.status-cancelled {
  background: #fee2e2;
  color: #b91c1c;
}

.status-completed {
  background: #e0f2fe;
  color: #075985;
}

.status-draft {
  background: #ede9fe;
  color: #5b21b6;
}

.status-unknown {
  background: #e5e7eb;
  color: #374151;
}

.payment-badge {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 4px 10px;
  border-radius: 9999px;
  background: #f3f4f6;
  color: #4b5563;
  font-size: 12px;
  font-weight: 500;
}

.amount-cell {
  display: flex;
  flex-direction: column;
  gap: 6px;
  align-items: flex-start;
}

.amount-primary {
  font-weight: 700;
  color: #111827;
}

.amount-secondary {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  font-size: 13px;
  color: #6b7280;
}

.actions-cell {
  text-align: right;
}

.action-buttons {
  display: inline-flex;
  gap: 8px;
}

.btn-icon {
  width: 36px;
  height: 36px;
  border-radius: 8px;
  border: 1px solid #e5e7eb;
  background: #ffffff;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  color: #4b5563;
  transition: all 0.2s ease;
}

.btn-icon:hover {
  border-color: #2563eb;
  color: #2563eb;
}

.btn-icon.text-danger {
  border-color: #fee2e2;
  color: #b91c1c;
}

.btn-icon.text-danger:hover {
  border-color: #dc2626;
  color: #ffffff;
  background: #dc2626;
}

@media (max-width: 1200px) {
  .filters-row {
    grid-template-columns: 1fr 1fr;
  }
}

@media (max-width: 768px) {
  .reservations-page {
    padding: 16px;
  }

  .page-header {
    padding: 20px;
  }

  .filters-row {
    grid-template-columns: 1fr;
  }

  .list-toolbar {
    flex-direction: column;
    align-items: flex-start;
  }

  .reservations-table th,
  .reservations-table td {
    padding: 12px;
  }

  .action-buttons {
    width: 100%;
    justify-content: flex-start;
  }
}
</style>