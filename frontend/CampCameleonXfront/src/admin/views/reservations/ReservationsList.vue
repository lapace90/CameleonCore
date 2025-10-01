<template>
  <div class="reservations-page">
    <!-- Header -->
    <div class="page-header">
      <div class="header-left">
        <h1 class="page-title">
          <i class="fas fa-calendar-check"></i>
          Réservations
        </h1>
        <p class="page-subtitle">Gestion des réservations et demandes client</p>
      </div>
      <div class="header-actions">
        <button type="button" class="btn btn-outline btn-sm" @click="refresh">
          <i class="fas fa-sync"></i>
          Actualiser
        </button>
        <button type="button" class="btn btn-primary btn-sm" @click="$router.push({ name: 'ReservationCreate' })">
          <i class="fas fa-plus"></i>
          Nouvelle Réservation
        </button>
      </div>
    </div>

    <!-- Filtres -->
    <div class="filters-card">
      <div class="filters-row">
        <div class="search-field">
          <label for="search">Rechercher</label>
          <div class="search-box">
            <i class="fas fa-search"></i>
            <input id="search" v-model="filters.search" type="text" placeholder="Nom client, email, ID réservation..."
              @input="onSearchInput" />
          </div>
        </div>

        <div class="filter-control">
          <label for="status">Statut</label>
          <select id="status" v-model="filters.status" @change="applyFilters">
            <option value="">Tous les statuts</option>
            <option v-for="option in statusOptions" :key="option.value" :value="option.value">
              {{ option.label }}
            </option>
          </select>
        </div>

        <div class="filter-control">
          <label for="start-date">Date début</label>
          <input id="start-date" v-model="filters.startDate" type="date" @change="applyFilters" />
        </div>

        <div class="filter-control">
          <label for="end-date">Date fin</label>
          <input id="end-date" v-model="filters.endDate" type="date" @change="applyFilters" />
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

    <!-- Loading -->
    <div v-if="loading" class="state-card">
      <i class="fas fa-spinner fa-spin"></i>
      <p>Chargement des réservations...</p>
    </div>

    <!-- Error -->
    <div v-else-if="error" class="state-card error">
      <i class="fas fa-exclamation-triangle"></i>
      <p>{{ error }}</p>
      <button type="button" class="btn btn-outline btn-sm" @click="fetchReservations">
        <i class="fas fa-redo"></i>
        Réessayer
      </button>
    </div>

    <!-- Empty -->
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

    <!-- Table -->
    <div v-else class="table-container">
      <table class="table">
        <thead>
          <tr>
            <th @click="changeSort('created_at')" class="sortable">
              Réservation
              <i :class="getSortIcon('created_at')"></i>
            </th>
            <th>Client</th>
            <th class="sortable" @click="changeSort('checkin')">
              Séjour
              <i :class="getSortIcon('checkin')"></i>
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
          <tr v-for="reservation in reservations" :key="reservation.id">
            <!-- Réservation -->
            <td>
              <strong>#{{ reservation.id }}</strong><br>
              <small class="text-muted">{{ formatDate(reservation.created_at) }}</small><br>
              <small v-if="reservation.invoice_number" class="text-muted">
                <i class="fas fa-file-invoice"></i> {{ reservation.invoice_number }}
              </small>
            </td>

            <!-- Client -->
            <td>
              <strong>{{ getCustomerName(reservation) }}</strong><br>
              <small v-if="getCustomerEmail(reservation)" class="text-muted">
                <i class="fas fa-envelope"></i> {{ getCustomerEmail(reservation) }}
              </small><br>
              <small v-if="getCustomerPhone(reservation)" class="text-muted">
                <i class="fas fa-phone"></i> {{ getCustomerPhone(reservation) }}
              </small>
            </td>

            <!-- Séjour -->
            <td>
              <strong>{{ formatDateRange(reservation.checkin, reservation.checkout) }}</strong><br>
              <small v-if="getNights(reservation)" class="text-muted">
                <i class="fas fa-moon"></i> {{ getNights(reservation) }} nuit{{ getNights(reservation) > 1 ? 's' : '' }}
              </small><br>
              <small class="text-muted">
                <i class="fas fa-user-friends"></i> {{ getGuestsLabel(reservation) }}
              </small>
            </td>

            <!-- Statut -->
            <td>
              <span :class="['status-badge', getStatusClass(reservation.status)]">
                {{ getStatusLabel(reservation.status) }}
              </span><br>
              <small v-if="getPaymentStatusLabel(reservation.payment_status)" class="text-muted">
                Paiement: {{ getPaymentStatusLabel(reservation.payment_status) }}
              </small>
            </td>

            <!-- Montant -->
            <td>
              <span class="price-value">{{ formatAmount(reservation.amount) }}</span><br>
              <small class="text-muted">
                <i class="fas fa-map-marker-alt"></i> {{ getBookingSourceLabel(reservation.booking_source) }}
              </small>
            </td>

            <!-- Actions -->
            <td class="actions-col">
              <div class="table-actions">
                <button type="button" class="btn-icon" title="Voir" @click="viewReservation(reservation)">
                  <i class="fas fa-eye"></i>
                </button>
                <button type="button" class="btn-icon" title="Modifier" @click="editReservation(reservation)">
                  <i class="fas fa-edit"></i>
                </button>
                <button type="button" class="btn-icon text-danger" title="Supprimer"
                  @click="deleteReservation(reservation)">
                  <i class="fas fa-trash"></i>
                </button>

                <!-- Quick actions for check-in/check-out -->
                <button v-if="reservation.status === 'confirmed' && canCheckIn" type="button"
                  class="btn-icon text-success" title="Check-in" @click.stop="quickCheckIn(reservation)">
                  <i class="fas fa-door-open"></i>
                </button>

                <button v-if="reservation.status === 'checked_in' && canCheckOut" type="button"
                  class="btn-icon text-info" title="Check-out" @click.stop="quickCheckOut(reservation)">
                  <i class="fas fa-door-closed"></i>
                </button>

              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <Pagination v-if="reservations.length > 0 && pagination.lastPage > 1" :pagination="pagination"
      @page-change="changePage" />
  </div>
</template>

<script>
import AdminApi from '@/services/AdminApi'
import Pagination from '@/admin/views/products/components/Pagination.vue'
import { debounce } from '@/shared/utils/helpers'
import { permissionMixin } from '@/plugins/permission-directives'

export default {
  name: 'ReservationsList',
  mixins: [permissionMixin],
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
        { label: 'Terminée', value: 'completed' },
        { label: 'Brouillon', value: 'draft' }
      ],
      sort: {
        field: 'created_at',
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
    },
    canCheckIn() {
      return this.$hasPermission('checkin')
    },
    canCheckOut() {
      return this.$hasPermission('checkout')
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

    async quickCheckIn(reservation) {
      if (!confirm(`Confirmer l'arrivée de ${reservation.customer_name || 'ce client'} ?`)) return

      try {
        await AdminApi.doReservationCheckIn(reservation.id)
        await this.fetchReservations() // Rafraîchir la liste
        alert('✅ Check-in effectué avec succès!')
      } catch (error) {
        console.error('Erreur check-in:', error)
        const msg = error.response?.data?.message || error.message || 'Erreur lors du check-in'
        alert(`❌ ${msg}`)
      }
    },

    async quickCheckOut(reservation) {
      if (!confirm(`Confirmer le départ de ${reservation.customer_name || 'ce client'} ?`)) return

      try {
        await AdminApi.doReservationCheckOut(reservation.id)
        await this.fetchReservations() // Rafraîchir la liste
        alert('✅ Check-out effectué avec succès!')
      } catch (error) {
        console.error('Erreur check-out:', error)
        const msg = error.response?.data?.message || error.message || 'Erreur lors du check-out'
        alert(`❌ ${msg}`)
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

    // Formatage
    formatDate(date) {
      if (!date) return '—'
      try {
        return new Intl.DateTimeFormat('fr-FR', {
          day: '2-digit',
          month: 'short',
          year: 'numeric'
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

    formatAmount(amount) {
      if (!amount) return '0,00 €'
      const value = typeof amount === 'string' ? parseFloat(amount) : amount
      return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'EUR'
      }).format(value)
    },

    // Getters pour données
    getCustomerName(reservation) {
      if (typeof reservation.customer === 'object' && reservation.customer) {
        const c = reservation.customer
        return c.name && c.last_name ? `${c.name} ${c.last_name}` : c.name || c.email
      }
      return reservation.customer_name || 'Client inconnu'
    },

    getCustomerEmail(reservation) {
      if (typeof reservation.customer === 'object' && reservation.customer) {
        return reservation.customer.email
      }
      return null
    },

    getCustomerPhone(reservation) {
      if (typeof reservation.customer === 'object' && reservation.customer) {
        return reservation.customer.phone
      }
      return null
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
      const total = adults + children

      if (total === 0) return 'Non défini'

      const parts = []
      if (adults) parts.push(`${adults} ${adults > 1 ? 'adultes' : 'adulte'}`)
      if (children) parts.push(`${children} ${children > 1 ? 'enfants' : 'enfant'}`)

      return parts.length ? parts.join(', ') : `${total} personne${total > 1 ? 's' : ''}`
    },

    // Labels statuts
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

    getBookingSourceLabel(source) {
      const labels = {
        website: 'Site web',
        direct: 'Direct',
        phone: 'Téléphone',
        booking: 'Booking.com',
        airbnb: 'Airbnb'
      }
      return labels[source] || source || 'Non défini'
    },

    // Actions
    viewReservation(reservation) {
      this.$router.push({ name: 'ReservationDetail', params: { id: reservation.id } })
    },

    editReservation(reservation) {
      this.$router.push({ name: 'ReservationDetail', params: { id: reservation.id }, query: { mode: 'edit' } })
    },

    async deleteReservation(reservation) {
      const confirmed = window.confirm(`Supprimer la réservation #${reservation.id} ?`)
      if (!confirmed) return

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

.page-title {
  display: flex;
  align-items: center;
  gap: 12px;
}

.page-title i {
  color: #2563eb;
}

.page-subtitle {
  margin: 0;
  color: #6b7280;
  font-size: 15px;
}

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

/* Styles pour les statuts - utilise les classes existantes */
.status-active {
  background: #d1fae5;
  color: #065f46;
}

.status-confirmed {
  background: #d1fae5;
  color: #065f46;
}

.status-pending {
  background: #fef3c7;
  color: #92400e;
}

.status-cancelled {
  background: #fee2e2;
  color: #991b1b;
}

.status-completed {
  background: #dbeafe;
  color: #1e40af;
}

.status-draft {
  background: #fef3c7;
  color: #92400e;
}

@media (max-width: 768px) {
  .filters-row {
    grid-template-columns: 1fr;
  }

  .list-toolbar {
    flex-direction: column;
    align-items: flex-start;
  }
}
</style>