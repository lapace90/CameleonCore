<template>
  <div class="reservations-page">
    <!-- Header -->
    <div class="page-header">
      <div class="header-left">
        <h1 class="page-title">
          <AppIcon name="calendar-check" />
          Réservations
        </h1>
        <p class="page-subtitle">Gestion des réservations et demandes client</p>
      </div>
      <div class="header-actions">
        <button type="button" class="btn btn-outline btn-sm" @click="refresh">
          <AppIcon name="rotate-cw" />
          Actualiser
        </button>
        <button type="button" class="btn btn-primary btn-sm" @click="$router.push({ name: 'ReservationCreate' })">
          <AppIcon name="plus" />
          Nouvelle Réservation
        </button>
      </div>
    </div>

    <!-- AdminFilterBar remplace filters-card -->
    <AdminFilterBar
      v-model="filters"
      :default-filters="defaultFilters"
      :fields="reservationFilterFields"
      search-placeholder="Nom client, email, ID réservation..."
      @apply="applyFilters"
    >
      <template #results="{ activeCount }">
        <span class="results-info">
          <AppIcon name="calendar-check" />
          {{ pagination.total }} réservation(s)
          <span v-if="activeCount > 0" class="text-muted">
            · {{ activeCount }} filtre(s)
          </span>
        </span>
      </template>
    </AdminFilterBar>

    <!-- Loading -->
    <div v-if="loading" class="state-card">
      <AppIcon name="loader-circle" :spin="true" />
      <p>Chargement des réservations...</p>
    </div>

    <!-- Error -->
    <div v-else-if="error" class="state-card error">
      <AppIcon name="triangle-alert" />
      <p>{{ error }}</p>
      <button type="button" class="btn btn-outline btn-sm" @click="fetchReservations">
        <AppIcon name="rotate-cw" />
        Réessayer
      </button>
    </div>

    <!-- Empty -->
    <div v-else-if="reservations.length === 0" class="state-card empty">
      <AppIcon name="clipboard-list" />
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
              <AppIcon :name="getSortIcon('created_at')" />
            </th>
            <th>Client</th>
            <th class="sortable" @click="changeSort('checkin')">
              Séjour
              <AppIcon :name="getSortIcon('checkin')" />
            </th>
            <th class="sortable" @click="changeSort('status')">
              Statut
              <AppIcon :name="getSortIcon('status')" />
            </th>
            <th class="sortable" @click="changeSort('amount')">
              Montant
              <AppIcon :name="getSortIcon('amount')" />
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
                <AppIcon name="receipt" /> {{ reservation.invoice_number }}
              </small>
            </td>

            <!-- Client -->
            <td>
              <strong>{{ getCustomerName(reservation) }}</strong><br>
              <small v-if="getCustomerEmail(reservation)" class="text-muted">
                <i class="mail"  style="padding: .5rem;"> </i>{{ getCustomerEmail(reservation) }}
              </small><br>
              <small v-if="getCustomerPhone(reservation)" class="text-muted">
                <AppIcon name="phone" /> {{ getCustomerPhone(reservation) }}
              </small>
            </td>

            <!-- Séjour -->
            <td>
              <strong>{{ formatDateRange(reservation.checkin, reservation.checkout) }}</strong><br>
              <small v-if="getNights(reservation)" class="text-muted">
                <AppIcon name="moon" /> {{ getNights(reservation) }} nuit{{ getNights(reservation) > 1 ? 's' : '' }}
              </small><br>
              <small class="text-muted">
                <AppIcon name="users" /> {{ getGuestsLabel(reservation) }}
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
                <AppIcon name="map-pin" /> {{ getBookingSourceLabel(reservation.booking_source) }}
              </small>
            </td>

            <!-- Actions -->
            <td class="actions-col">
              <div class="table-actions">
                <button type="button" class="btn-icon" title="Voir" @click="viewReservation(reservation)">
                  <AppIcon name="eye" />
                </button>
                <button type="button" class="btn-icon" title="Modifier" @click="editReservation(reservation)">
                  <AppIcon name="pencil" />
                </button>
                <button type="button" class="btn-icon text-danger" title="Supprimer"
                  @click="deleteReservation(reservation)">
                  <AppIcon name="trash-2" />
                </button>

                <!-- Quick actions for check-in/check-out -->
                <button v-if="reservation.status === 'confirmed' && canCheckIn" type="button"
                  class="btn-icon text-success" title="Check-in" @click.stop="quickCheckIn(reservation)">
                  <AppIcon name="door-open" />
                </button>

                <button v-if="reservation.status === 'checked_in' && canCheckOut" type="button"
                  class="btn-icon text-info" title="Check-out" @click.stop="quickCheckOut(reservation)">
                  <AppIcon name="door-closed" />
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
import AdminFilterBar from '@/admin/components/ui/AdminFilterBar.vue'
import { debounce } from '@/shared/utils/helpers'
import { permissionMixin } from '@/plugins/permission-directives'

export default {
  name: 'ReservationsList',
  mixins: [permissionMixin],
  components: {
    Pagination,
    AdminFilterBar
  },

  data() {
    return {
      loading: false,
      error: null,
      reservations: [],
      
      defaultFilters: {
        search: '',
        status: '',
        startDate: '',
        endDate: ''
      },
      
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
    reservationFilterFields() {
      return [
        {
          key: 'status',
          type: 'select',
          placeholder: 'Tous les statuts',
          options: this.statusOptions.map(opt => ({
            value: opt.value,
            label: opt.label
          }))
        },
        {
          key: 'startDate',
          type: 'date',
          placeholder: 'Date début'
        },
        {
          key: 'endDate',
          type: 'date',
          placeholder: 'Date fin'
        }
      ]
    },
    
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

    applyFilters() {
      this.pagination.currentPage = 1
      this.fetchReservations()
    },

    onSearchInput() {
      this.debouncedSearch()
    },

    resetFilters() {
      this.filters = { ...this.defaultFilters }
      this.applyFilters()
    },

    refresh() {
      this.fetchReservations()
    },

    updatePagination(meta) {
      this.pagination.currentPage = meta.current_page || meta.currentPage || 1
      this.pagination.perPage = meta.per_page || meta.perPage || 10
      this.pagination.total = meta.total || 0
      this.pagination.lastPage = meta.last_page || meta.lastPage || 1
    },

    changePage(page) {
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
        this.sort.direction = 'desc'
      }
      this.fetchReservations()
    },

    getSortIcon(field) {
      if (this.sort.field !== field) return 'arrow-up-down'
      return this.sort.direction === 'asc' ? 'chevron-up' : 'chevron-down'
    },

    viewReservation(reservation) {
      this.$router.push({ name: 'ReservationDetail', params: { id: reservation.id } })
    },

    editReservation(reservation) {
      this.$router.push({ name: 'ReservationEdit', params: { id: reservation.id } })
    },

    async deleteReservation(reservation) {
      if (!confirm(`Supprimer la réservation #${reservation.id} ?`)) return

      try {
        await AdminApi.deleteReservation(reservation.id)
        this.fetchReservations()
      } catch (error) {
        alert('Erreur lors de la suppression')
      }
    },

    async quickCheckIn(reservation) {
      try {
        await AdminApi.updateReservation(reservation.id, { status: 'checked_in' })
        this.fetchReservations()
      } catch (error) {
        alert('Erreur lors du check-in')
      }
    },

    async quickCheckOut(reservation) {
      try {
        await AdminApi.updateReservation(reservation.id, { status: 'checked_out' })
        this.fetchReservations()
      } catch (error) {
        alert('Erreur lors du check-out')
      }
    },

    formatDate(dateString) {
      if (!dateString) return ''
      return new Date(dateString).toLocaleDateString('fr-FR')
    },

    formatDateRange(start, end) {
      if (!start || !end) return ''
      const startDate = new Date(start).toLocaleDateString('fr-FR', { day: '2-digit', month: 'short' })
      const endDate = new Date(end).toLocaleDateString('fr-FR', { day: '2-digit', month: 'short' })
      return `${startDate} → ${endDate}`
    },

    formatAmount(amount) {
      if (!amount) return '0,00 €'
      return `${parseFloat(amount).toFixed(2)} €`
    },

    getNights(reservation) {
      if (!reservation.checkin || !reservation.checkout) return 0
      const start = new Date(reservation.checkin)
      const end = new Date(reservation.checkout)
      return Math.ceil((end - start) / (1000 * 60 * 60 * 24))
    },

    getCustomerName(reservation) {
      if (typeof reservation.customer === 'object' && reservation.customer) {
        const c = reservation.customer
        return c.name && c.last_name ? `${c.name} ${c.last_name}` : c.name || c.email
      }
      return reservation.customerName || 'Client inconnu'
    },

    getCustomerEmail(reservation) {
      if (typeof reservation.customer === 'object' && reservation.customer) {
        return reservation.customer.email
      }
      return reservation.customerEmail || ''
    },

    getCustomerPhone(reservation) {
      if (typeof reservation.customer === 'object' && reservation.customer) {
        return reservation.customer.phone
      }
      return reservation.customerPhone || ''
    },

    getGuestsLabel(reservation) {
      const adults = reservation.number_of_adults || 0
      const children = reservation.number_of_children || 0
      const total = adults + children
      return total > 0 ? `${total} personne${total > 1 ? 's' : ''}` : ''
    },

    getStatusLabel(status) {
      const labels = {
        'confirmed': 'Confirmée',
        'pending': 'En attente',
        'cancelled': 'Annulée',
        'completed': 'Terminée',
        'draft': 'Brouillon',
        'checked_in': 'Check-in',
        'checked_out': 'Check-out'
      }
      return labels[status] || status
    },

    getStatusClass(status) {
      return `status-${status}`
    },

    getPaymentStatusLabel(paymentStatus) {
      if (!paymentStatus) return ''
      const labels = {
        'paid': 'Payé',
        'pending': 'En attente',
        'partial': 'Partiel',
        'refunded': 'Remboursé'
      }
      return labels[paymentStatus] || paymentStatus
    },

    getBookingSourceLabel(source) {
      const labels = {
        'web': 'Site web',
        'phone': 'Téléphone',
        'email': 'Email',
        'direct': 'Direct',
        'booking': 'Booking.com',
        'airbnb': 'Airbnb'
      }
      return labels[source] || source || 'Direct'
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

.page-title .app-icon {
  color: #2563eb;
}

.page-subtitle {
  margin: 0;
  color: #6b7280;
  font-size: 15px;
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

.state-card .app-icon {
  font-size: 32px;
  color: #2563eb;
}

.state-card.error .app-icon {
  color: #dc2626;
}

.status-badge {
  padding: 4px 12px;
  border-radius: 12px;
  font-size: 12px;
  font-weight: 600;
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
  background: #f3f4f6;
  color: #374151;
}

.status-checked_in {
  background: #d1fae5;
  color: #065f46;
}

.status-checked_out {
  background: #dbeafe;
  color: #1e40af;
}

</style>