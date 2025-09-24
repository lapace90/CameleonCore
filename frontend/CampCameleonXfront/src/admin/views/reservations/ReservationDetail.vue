<template>
<div class="reservation-detail-container">
    <div v-if="loading" class="loading-state">
      <div class="spinner"></div>
      <p>Chargement des détails de la réservation...</p>
    </div>

    <div v-else-if="error" class="error-state">
      <i class="fas fa-exclamation-triangle error-icon"></i>
      <h3>{{ error }}</h3>
      <button class="btn btn-outline btn-sm" @click="fetchReservation">
        <i class="fas fa-sync"></i>
        Réessayer
      </button>
    </div>

    <div v-else-if="reservation" class="reservation-detail-content">
      <div class="detail-top-bar">
        <Breadcrumb :items="breadcrumbItems" />
        <div class="top-bar-actions">
          <router-link to="/admin/reservations" class="btn btn-outline btn-sm">
            <i class="fas fa-arrow-left"></i>
            Retour aux réservations
          </router-link>
        </div>
      </div>

      <PageHeader
        :title="`Réservation ${reservationDisplayId}`"
        :subtitle="pageSubtitle"
        icon="fas fa-calendar-check"
      >
        <template #actions>
          <router-link :to="editRoute" class="btn btn-primary btn-sm">
            <i class="fas fa-edit"></i>
            Modifier
          </router-link>
          <button
            class="btn btn-outline btn-sm"
            @click="handleSendConfirmation"
            :disabled="isSendingConfirmation"
          >
            <i class="fas fa-paper-plane"></i>
            <span v-if="isSendingConfirmation">Envoi...</span>
            <span v-else>Envoyer confirmation</span>
          </button>
          <button
            class="btn btn-danger btn-sm"
            @click="handleCancelReservation"
            :disabled="isCancelling"
          >
            <i class="fas fa-ban"></i>
            <span v-if="isCancelling">Annulation...</span>
            <span v-else>Annuler</span>
          </button>
        </template>
      </PageHeader>

      <div class="reservation-summary card">
        <div class="card-body">
          <div class="summary-badges">
            <span class="status-chip" :class="statusClass">
              <i class="fas fa-circle"></i>
              {{ statusLabel }}
            </span>
            <span class="status-chip" :class="paymentStatusClass">
              <i :class="paymentStatusIcon"></i>
              {{ paymentStatusLabel }}
            </span>
          </div>
          <div class="summary-grid">
            <div class="summary-item">
              <span class="summary-label">Séjour</span>
              <span class="summary-value">{{ staySummary }}</span>
            </div>
            <div class="summary-item">
              <span class="summary-label">Hébergement</span>
              <span class="summary-value">{{ accommodationLabel }}</span>
            </div>
            <div class="summary-item">
              <span class="summary-label">Montant total</span>
              <span class="summary-value">{{ totalAmount }}</span>
            </div>
            <div class="summary-item">
              <span class="summary-label">Montant payé</span>
              <span class="summary-value">{{ amountPaid }}</span>
            </div>
            <div class="summary-item">
              <span class="summary-label">Solde dû</span>
              <span class="summary-value">{{ balanceDue }}</span>
            </div>
            <div class="summary-item">
              <span class="summary-label">Dernière mise à jour</span>
              <span class="summary-value">{{ lastUpdated }}</span>
            </div>
          </div>
        </div>
      </div>

      <div class="detail-grid">
        <ReservationInfoCard title="Client" icon="fas fa-user">
          <ul class="info-list">
            <li class="info-line">
              <span class="info-label">Nom</span>
              <span class="info-value">{{ customerName }}</span>
            </li>
            <li class="info-line">
              <span class="info-label">Email</span>
              <span class="info-value">
                <a v-if="customerEmail" :href="`mailto:${customerEmail}`">{{ customerEmail }}</a>
                <span v-else>Non renseigné</span>
              </span>
            </li>
            <li class="info-line">
              <span class="info-label">Téléphone</span>
              <span class="info-value">
                <a v-if="customerPhone" :href="`tel:${customerPhone}`">{{ customerPhone }}</a>
                <span v-else>Non renseigné</span>
              </span>
            </li>
            <li v-if="customerAddress" class="info-line">
              <span class="info-label">Adresse</span>
              <span class="info-value">{{ customerAddress }}</span>
            </li>
          </ul>
        </ReservationInfoCard>

        <ReservationInfoCard title="Détails du séjour" icon="fas fa-suitcase-rolling">
          <div class="info-grid">
            <div class="info-item">
              <label>Arrivée</label>
              <span>{{ checkInDate }}</span>
            </div>
            <div class="info-item">
              <label>Départ</label>
              <span>{{ checkOutDate }}</span>
            </div>
            <div class="info-item">
              <label>Durée</label>
              <span>{{ stayDuration }}</span>
            </div>
            <div class="info-item">
              <label>Formule</label>
              <span>{{ ratePlan }}</span>
            </div>
            <div class="info-item full-width">
              <label>Demandes spéciales</label>
              <span>{{ specialRequests }}</span>
            </div>
          </div>
        </ReservationInfoCard>

        <ReservationInfoCard title="Hébergement" icon="fas fa-home">
          <div class="info-grid">
            <div class="info-item">
              <label>Type</label>
              <span>{{ accommodationType }}</span>
            </div>
            <div class="info-item">
              <label>Nom</label>
              <span>{{ accommodationName }}</span>
            </div>
            <div class="info-item">
              <label>Capacité</label>
              <span>{{ accommodationCapacity }}</span>
            </div>
            <div class="info-item">
              <label>Tarif nuit</label>
              <span>{{ nightlyRate }}</span>
            </div>
          </div>
        </ReservationInfoCard>

        <ReservationInfoCard title="Invités" icon="fas fa-users">
          <div v-if="guestList.length" class="guest-list">
            <div v-for="(guest, index) in guestList" :key="index" class="guest-item">
              <div class="guest-avatar">
                <i class="fas fa-user-circle"></i>
              </div>
              <div class="guest-details">
                <p class="guest-name">{{ guest.name || `Invité ${index + 1}` }}</p>
                <p v-if="guestMeta(guest)" class="guest-meta">{{ guestMeta(guest) }}</p>
              </div>
            </div>
          </div>
          <p v-else class="empty-state">Aucun invité renseigné.</p>
        </ReservationInfoCard>

        <ReservationInfoCard title="Paiement" icon="fas fa-credit-card">
          <div class="info-grid">
            <div class="info-item">
              <label>Statut</label>
              <span>{{ paymentStatusLabel }}</span>
            </div>
            <div class="info-item">
              <label>Total</label>
              <span>{{ totalAmount }}</span>
            </div>
            <div class="info-item">
              <label>Reçu</label>
              <span>{{ amountPaid }}</span>
            </div>
            <div class="info-item">
              <label>Solde dû</label>
              <span>{{ balanceDue }}</span>
            </div>
            <div v-if="paymentMethod" class="info-item full-width">
              <label>Méthode</label>
              <span>{{ paymentMethod }}</span>
            </div>
            <div v-if="nextPaymentDue" class="info-item full-width">
              <label>Prochain paiement</label>
              <span>{{ nextPaymentDue }}</span>
            </div>
          </div>
        </ReservationInfoCard>

        <ReservationInfoCard title="Sous-réservations & évènements liés" icon="fas fa-layer-group">
          <div v-if="linkedReservations.length" class="linked-reservations">
            <div v-for="(item, index) in linkedReservations" :key="index" class="linked-reservation-item">
              <div class="linked-reservation-header">
                <span class="linked-reservation-title">{{ item.title }}</span>
                <span class="linked-reservation-dates">{{ item.dates }}</span>
              </div>
              <p v-if="item.description" class="linked-reservation-description">{{ item.description }}</p>
              <div v-if="item.meta && Object.keys(item.meta).length" class="linked-reservation-meta">
                <span v-for="(value, label) in item.meta" :key="label">{{ label }} : {{ value }}</span>
              </div>
            </div>
          </div>
          <p v-else class="empty-state">Aucune sous-réservation associée.</p>
        </ReservationInfoCard>

        <ReservationInfoCard title="Notes internes" icon="fas fa-sticky-note">
          <div v-if="internalNotes.length" class="notes-list">
            <div v-for="(note, index) in internalNotes" :key="index" class="note-item">
              <div class="note-header">
                <span class="note-author">{{ note.author || 'Équipe' }}</span>
                <span v-if="note.date" class="note-date">{{ formatDate(note.date) }}</span>
              </div>
              <p class="note-content">{{ note.content }}</p>
            </div>
          </div>
          <p v-else class="empty-state">Aucune note enregistrée.</p>
        </ReservationInfoCard>

        <ReservationInfoCard title="Historique des communications" icon="fas fa-envelope-open-text">
          <ReservationTimeline :items="timelineItems" />
        </ReservationInfoCard>
      </div>
    </div>
  </div>
</template>

<script>
import Breadcrumb from '@/admin/components/Breadcrumb.vue'
import PageHeader from '@/shared/components/ui/PageHeader.vue'
import ReservationInfoCard from './ReservationInfoCard.vue'
import ReservationTimeline from './ReservationTimeline.vue'
import AdminApi from '@/services/AdminApi'

export default {
  name: 'ReservationDetail',
  components: {
    Breadcrumb,
    PageHeader,
    ReservationInfoCard,
    ReservationTimeline
  },
  data() {
    return {
      reservation: null,
      loading: true,
      error: null,
      isCancelling: false,
      isSendingConfirmation: false
    }
  },
  computed: {
    reservationId() {
      return this.$route.params.id
    },
    reservationReference() {
      const value = this.reservation?.reference || this.reservation?.code || this.reservation?.number || this.reservation?.id
      return value ? value.toString() : null
    },
    reservationDisplayId() {
      if (!this.reservationReference) {
        return `#${this.reservationId}`
      }
      return this.reservationReference.startsWith('#') ? this.reservationReference : `#${this.reservationReference}`
    },
    breadcrumbItems() {
      return [
        { name: 'Dashboard', path: '/admin/dashboard' },
        { name: 'Réservations', path: '/admin/reservations' },
        { name: this.reservationDisplayId, path: null }
      ]
    },
    editRoute() {
      return `/admin/reservations/${this.reservationId}/edit`
    },
    pageSubtitle() {
      const parts = []
      if (this.customerName && this.customerName !== 'Non renseigné') {
        parts.push(this.customerName)
      }
      if (this.staySummary && this.staySummary !== 'Dates non renseignées') {
        parts.push(this.staySummary)
      }
      return parts.join(' • ')
    },
    currencyCode() {
      return (
        this.reservation?.currency ||
        this.reservation?.currency_code ||
        this.reservation?.currencyCode ||
        'EUR'
      )
    },
    rawCheckIn() {
      return this.getNestedValue(this.reservation, [
        'check_in',
        'checkIn',
        'arrival_date',
        'arrivalDate',
        'start_date',
        'startDate'
      ])
    },
    rawCheckOut() {
      return this.getNestedValue(this.reservation, [
        'check_out',
        'checkOut',
        'departure_date',
        'departureDate',
        'end_date',
        'endDate'
      ])
    },
    checkInDate() {
      return this.rawCheckIn ? this.formatDate(this.rawCheckIn) : 'Non renseigné'
    },
    checkOutDate() {
      return this.rawCheckOut ? this.formatDate(this.rawCheckOut) : 'Non renseigné'
    },
    stayDuration() {
      const nights = this.calculateNights(this.rawCheckIn, this.rawCheckOut)
      if (nights === null) {
        return 'Non renseigné'
      }
      if (nights === 0) {
        return 'Séjour journée'
      }
      return `${nights} nuit${nights > 1 ? 's' : ''}`
    },
    staySummary() {
      const checkIn = this.rawCheckIn ? this.formatDate(this.rawCheckIn) : null
      const checkOut = this.rawCheckOut ? this.formatDate(this.rawCheckOut) : null
      const nights = this.calculateNights(this.rawCheckIn, this.rawCheckOut)
      const nightsLabel = nights !== null && nights >= 0 ? `${nights} nuit${nights > 1 ? 's' : ''}` : null

      if (checkIn && checkOut) {
        return [`${checkIn} → ${checkOut}`, nightsLabel].filter(Boolean).join(' • ')
      }
      if (checkIn) {
        return `Arrivée ${checkIn}`
      }
      if (checkOut) {
        return `Départ ${checkOut}`
      }
      return 'Dates non renseignées'
    },
    customerName() {
      const reservation = this.reservation || {}
      const fullName =
        reservation.customer?.full_name ||
        reservation.customer?.name ||
        reservation.client?.full_name ||
        reservation.client?.name ||
        reservation.guest_name ||
        reservation.contact_name ||
        [reservation.customer_first_name, reservation.customer_last_name].filter(Boolean).join(' ')

      return fullName ? fullName.trim() : 'Non renseigné'
    },
    customerEmail() {
      const reservation = this.reservation || {}
      return (
        reservation.customer?.email ||
        reservation.client?.email ||
        reservation.contact_email ||
        reservation.email ||
        ''
      )
    },
    customerPhone() {
      const reservation = this.reservation || {}
      return (
        reservation.customer?.phone ||
        reservation.client?.phone ||
        reservation.contact_phone ||
        reservation.phone ||
        ''
      )
    },
    customerAddress() {
      const reservation = this.reservation || {}
      const address =
        reservation.customer?.address ||
        reservation.client?.address ||
        reservation.billing_address ||
        reservation.address

      if (!address) {
        return ''
      }

      if (typeof address === 'string') {
        return address
      }

      if (typeof address === 'object') {
        const parts = [
          address.street,
          address.postal_code || address.zip,
          address.city,
          address.country
        ].filter(Boolean)
        return parts.join(', ')
      }

      return ''
    },
    accommodation() {
      return (
        this.reservation?.accommodation ||
        this.reservation?.room ||
        this.reservation?.unit ||
        this.reservation?.lodging ||
        null
      )
    },
    accommodationName() {
      if (!this.accommodation) {
        return this.reservation?.room_name || this.reservation?.unit_name || 'Non renseigné'
      }
      return this.accommodation.name || this.accommodation.title || 'Non renseigné'
    },
    accommodationType() {
      if (!this.accommodation) {
        return this.reservation?.accommodation_type || this.reservation?.room_type || 'Non renseigné'
      }
      return this.accommodation.type || this.accommodation.category || 'Non renseigné'
    },
    accommodationCapacity() {
      const capacity =
        this.accommodation?.capacity ||
        this.accommodation?.max_guests ||
        this.reservation?.guests_count ||
        this.reservation?.capacity

      return capacity ? `${capacity} personne${capacity > 1 ? 's' : ''}` : 'Non renseigné'
    },
    nightlyRate() {
      const rate = this.toNumber(
        this.reservation?.nightly_rate ||
        this.reservation?.rate_per_night ||
        this.accommodation?.nightly_rate ||
        null
      )
      return rate !== null ? this.formatCurrency(rate) : 'Non renseigné'
    },
    accommodationLabel() {
      const name = this.accommodationName
      const type = this.accommodationType
      return [name, type !== 'Non renseigné' ? type : null].filter(Boolean).join(' • ') || 'Non renseigné'
    },
    ratePlan() {
      const plan =
        this.reservation?.rate_plan?.name ||
        this.reservation?.rate_plan ||
        this.reservation?.package?.name ||
        this.reservation?.package ||
        this.reservation?.offer ||
        null
      return plan || 'Non renseigné'
    },
    specialRequests() {
      const request =
        this.reservation?.special_requests ||
        this.reservation?.notes?.special_requests ||
        this.reservation?.request ||
        this.reservation?.customer_note ||
        null
      return request || 'Aucune demande particulière.'
    },
    guestList() {
      const guests = this.reservation?.guests || this.reservation?.guest_list || this.reservation?.participants
      if (Array.isArray(guests)) {
        return guests.map((guest) => ({
          name:
            guest.name ||
            [guest.first_name, guest.last_name].filter(Boolean).join(' ') ||
            guest.nickname ||
            '',
          age: guest.age || guest.age_group || guest.birthdate,
          type: guest.type || guest.category || guest.role || ''
        }))
      }

      const fallback = []
      const adults = this.toNumber(this.reservation?.adults)
      const children = this.toNumber(this.reservation?.children)
      const infants = this.toNumber(this.reservation?.infants || this.reservation?.babies)

      if (adults) {
        fallback.push({ name: `${adults} adulte${adults > 1 ? 's' : ''}`, type: 'Adultes' })
      }
      if (children) {
        fallback.push({ name: `${children} enfant${children > 1 ? 's' : ''}`, type: 'Enfants' })
      }
      if (infants) {
        fallback.push({ name: `${infants} bébé${infants > 1 ? 's' : ''}`, type: 'Bébés' })
      }

      return fallback
    },
    paymentMethod() {
      const payment = this.reservation?.payment || {}
      return (
        payment.method ||
        payment.label ||
        this.reservation?.payment_method ||
        this.reservation?.paymentMode ||
        (Array.isArray(this.reservation?.payments) && this.reservation.payments[0]?.method) ||
        ''
      )
    },
    nextPaymentDue() {
      const value =
        this.reservation?.next_payment_due ||
        this.reservation?.payment?.next_due_date ||
        this.reservation?.next_due_date
      return value ? this.formatDate(value) : ''
    },
    totalAmountValue() {
      return this.toNumber(
        this.reservation?.total_amount ||
        this.reservation?.total ||
        this.reservation?.amount_total ||
        this.reservation?.pricing?.total ||
        null
      )
    },
    totalAmount() {
      return this.totalAmountValue !== null ? this.formatCurrency(this.totalAmountValue) : 'Non renseigné'
    },
    amountPaidValue() {
      const direct = this.toNumber(
        this.reservation?.amount_paid ||
        this.reservation?.paid_amount ||
        this.reservation?.payments_total ||
        null
      )
      if (direct !== null) {
        return direct
      }
      if (Array.isArray(this.reservation?.payments)) {
        return this.reservation.payments.reduce((sum, payment) => {
          const amount = this.toNumber(payment.amount)
          return sum + (amount || 0)
        }, 0)
      }
      return null
    },
    amountPaid() {
      return this.amountPaidValue !== null ? this.formatCurrency(this.amountPaidValue) : 'Non renseigné'
    },
    balanceDueValue() {
      const direct = this.toNumber(
        this.reservation?.balance_due ||
        this.reservation?.remaining_balance ||
        this.reservation?.balance ||
        null
      )
      if (direct !== null) {
        return direct
      }
      if (this.totalAmountValue !== null && this.amountPaidValue !== null) {
        return Math.max(this.totalAmountValue - this.amountPaidValue, 0)
      }
      return null
    },
    balanceDue() {
      return this.balanceDueValue !== null ? this.formatCurrency(this.balanceDueValue) : 'Non renseigné'
    },
    statusRaw() {
      return (
        this.reservation?.status ||
        this.reservation?.state ||
        this.reservation?.reservation_status ||
        ''
      )
    },
    statusLabel() {
      return this.mapStatusLabel(this.statusRaw)
    },
    statusClass() {
      return `status-chip--${this.mapStatusClass(this.statusRaw)}`
    },
    paymentStatusRaw() {
      return (
        this.reservation?.payment_status ||
        this.reservation?.paymentStatus ||
        this.reservation?.payment_state ||
        ''
      )
    },
    paymentStatusLabel() {
      return this.mapPaymentStatusLabel(this.paymentStatusRaw)
    },
    paymentStatusClass() {
      return `status-chip--${this.mapPaymentStatusClass(this.paymentStatusRaw)}`
    },
    paymentStatusIcon() {
      return this.mapPaymentStatusIcon(this.paymentStatusRaw)
    },
    lastUpdated() {
      const date =
        this.reservation?.updated_at ||
        this.reservation?.updatedAt ||
        this.reservation?.modified_at
      return date ? this.formatDate(date, { includeTime: true }) : 'Non renseigné'
    },
    internalNotes() {
      const notes =
        this.reservation?.internal_notes ||
        this.reservation?.notes_internal ||
        this.reservation?.notes

      if (!notes) {
        return []
      }

      if (Array.isArray(notes)) {
        return notes
          .map((note) => {
            if (typeof note === 'string') {
              return { content: note }
            }
            return {
              author: note.author || note.user || note.created_by || '',
              date: note.date || note.created_at || note.createdAt,
              content: note.content || note.text || ''
            }
          })
          .filter((note) => note.content)
      }

      if (typeof notes === 'string') {
        return [{ content: notes }]
      }

      if (typeof notes === 'object') {
        return [
          {
            author: notes.author || notes.user || '',
            date: notes.date || notes.created_at,
            content: notes.content || notes.text || ''
          }
        ].filter((note) => note.content)
      }

      return []
    },
    linkedReservations() {
      const raw =
        this.reservation?.children ||
        this.reservation?.sub_reservations ||
        this.reservation?.related_reservations ||
        this.reservation?.events ||
        []

      if (!Array.isArray(raw)) {
        return []
      }

      return raw.map((item) => {
        const title =
          item.title ||
          item.name ||
          item.reference ||
          (item.id ? `Réservation ${item.id}` : 'Réservation liée')
        const start = item.start || item.start_date || item.check_in
        const end = item.end || item.end_date || item.check_out
        const dates = this.buildDatesRange(start, end)
        const meta = {}

        if (item.status) {
          meta.Statut = this.mapStatusLabel(item.status)
        }
        if (item.guests_count || item.guests) {
          const count = item.guests_count || item.guests
          meta.Invités = `${count} personne${count > 1 ? 's' : ''}`
        }
        if (item.type) {
          meta.Type = item.type
        }

        return {
          title,
          dates,
          description: item.description || item.notes || '',
          meta: Object.fromEntries(Object.entries(meta).filter(([, value]) => Boolean(value)))
        }
      })
    },
    timelineItems() {
      const history =
        this.reservation?.communication_history ||
        this.reservation?.communications ||
        this.reservation?.timeline ||
        []

      if (!Array.isArray(history)) {
        return []
      }

      return history.map((entry) => ({
        title: entry.title,
        message: entry.message || entry.body || entry.note,
        date:
          entry.date ||
          entry.created_at ||
          entry.createdAt ||
          entry.sent_at ||
          entry.sentAt,
        type: entry.type || entry.channel || 'default',
        status: entry.status,
        user: entry.user,
        recipient: entry.recipient,
        meta: entry.meta
      }))
    }
  },
  created() {
    this.fetchReservation()
  },
  watch: {
    '$route.params.id'() {
      this.fetchReservation()
    }
  },
  methods: {
    async fetchReservation() {
      this.loading = true
      this.error = null
      try {
        const data = await AdminApi.getReservation(this.reservationId)
        this.reservation = data
      } catch (error) {
        this.error = error.message || "Impossible de charger la réservation."
      } finally {
        this.loading = false
      }
    },
    async handleCancelReservation() {
      if (this.isCancelling) {
        return
      }

      const confirmation = window.confirm('Confirmer l\'annulation de cette réservation ?')
      if (!confirmation) {
        return
      }

      this.isCancelling = true
      try {
        await AdminApi.updateReservation(this.reservationId, { status: 'cancelled' })
        await this.fetchReservation()
      } catch (error) {
        console.error('Annulation impossible:', error)
      } finally {
        this.isCancelling = false
      }
    },
    handleSendConfirmation() {
      if (this.isSendingConfirmation) {
        return
      }

      this.isSendingConfirmation = true
      window.setTimeout(() => {
        console.info(`Confirmation envoyée pour la réservation ${this.reservationDisplayId}`)
        this.isSendingConfirmation = false
      }, 800)
    },
    guestMeta(guest) {
      const parts = []
      if (guest.type) {
        parts.push(guest.type)
      }
      if (guest.age) {
        parts.push(`Âge : ${guest.age}`)
      }
      return parts.join(' • ')
    },
    formatDate(value, { includeTime = false } = {}) {
      if (!value) {
        return 'Non renseigné'
      }
      const date = new Date(value)
      if (Number.isNaN(date.getTime())) {
        return value
      }
      const options = includeTime
        ? { dateStyle: 'medium', timeStyle: 'short' }
        : { dateStyle: 'medium' }
      return new Intl.DateTimeFormat('fr-FR', options).format(date)
    },
    calculateNights(start, end) {
      if (!start || !end) {
        return null
      }
      const startDate = new Date(start)
      const endDate = new Date(end)
      if (Number.isNaN(startDate.getTime()) || Number.isNaN(endDate.getTime())) {
        return null
      }
      const diff = endDate.getTime() - startDate.getTime()
      if (diff < 0) {
        return null
      }
      return Math.round(diff / (1000 * 60 * 60 * 24))
    },
    formatCurrency(amount) {
      if (amount === null || amount === undefined || Number.isNaN(amount)) {
        return 'Non renseigné'
      }
      return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: this.currencyCode
      }).format(amount)
    },
    toNumber(value) {
      if (value === null || value === undefined || value === '') {
        return null
      }
      const number = Number(value)
      return Number.isFinite(number) ? number : null
    },
    mapStatusLabel(status) {
      const normalized = (status || '').toString().toLowerCase()
      switch (normalized) {
        case 'pending':
        case 'draft':
          return 'En attente'
        case 'confirmed':
        case 'confirmed_booking':
        case 'active':
          return 'Confirmée'
        case 'checked_in':
          return 'En cours'
        case 'completed':
        case 'checked_out':
          return 'Terminée'
        case 'cancelled':
        case 'canceled':
          return 'Annulée'
        default:
          return status || 'Statut inconnu'
      }
    },
    mapStatusClass(status) {
      const normalized = (status || '').toString().toLowerCase()
      if (['confirmed', 'active', 'completed', 'checked_in', 'checked_out'].includes(normalized)) {
        return 'success'
      }
      if (['pending', 'draft'].includes(normalized)) {
        return 'warning'
      }
      if (['cancelled', 'canceled'].includes(normalized)) {
        return 'danger'
      }
      return 'info'
    },
    mapPaymentStatusLabel(status) {
      const normalized = (status || '').toString().toLowerCase()
      switch (normalized) {
        case 'paid':
        case 'completed':
          return 'Payé'
        case 'partial':
        case 'partial_paid':
          return 'Partiellement payé'
        case 'pending':
        case 'due':
          return 'En attente de paiement'
        case 'overdue':
          return 'En retard'
        case 'refunded':
          return 'Remboursé'
        default:
          return status || 'Statut inconnu'
      }
    },
    mapPaymentStatusClass(status) {
      const normalized = (status || '').toString().toLowerCase()
      if (['paid', 'completed', 'refunded'].includes(normalized)) {
        return 'success'
      }
      if (['partial', 'partial_paid', 'pending', 'due'].includes(normalized)) {
        return 'warning'
      }
      if (['overdue', 'failed'].includes(normalized)) {
        return 'danger'
      }
      return 'info'
    },
    mapPaymentStatusIcon(status) {
      const normalized = (status || '').toString().toLowerCase()
      if (['paid', 'completed'].includes(normalized)) {
        return 'fas fa-check-circle'
      }
      if (['partial', 'partial_paid'].includes(normalized)) {
        return 'fas fa-adjust'
      }
      if (['overdue', 'failed'].includes(normalized)) {
        return 'fas fa-exclamation-circle'
      }
      if (['refunded'].includes(normalized)) {
        return 'fas fa-undo'
      }
      return 'fas fa-clock'
    },
    buildDatesRange(start, end) {
      if (!start && !end) {
        return 'Dates non renseignées'
      }
      if (start && end) {
        return `${this.formatDate(start)} → ${this.formatDate(end)}`
      }
      if (start) {
        return `Début ${this.formatDate(start)}`
      }
      return `Fin ${this.formatDate(end)}`
    },
    getNestedValue(source, keys) {
      if (!source || !keys) {
        return null
      }
      for (const key of keys) {
        if (!key) continue
        const value = key.split('.').reduce((acc, segment) => {
          if (acc && acc[segment] !== undefined && acc[segment] !== null) {
            return acc[segment]
          }
          return undefined
        }, source)
        if (value !== undefined && value !== null && value !== '') {
          return value
        }
      }
      return null
    }
  }
}
</script>

<style scoped>
.reservation-detail-container {
  padding: 2rem;
  min-width: 1280px;
  margin: 0 auto;
}

.detail-top-bar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 1.5rem;
}

.top-bar-actions {
  display: flex;
  gap: 0.75rem;
}

.reservation-summary {
  margin-bottom: 2rem;
  border-radius: 1rem;
  box-shadow: var(--shadow);
}

.summary-badges {
  display: flex;
  flex-wrap: wrap;
  gap: 0.75rem;
  align-items: center;
  margin-bottom: 1.5rem;
}

.status-chip {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.35rem 1rem;
  border-radius: 999px;
  font-weight: 600;
  font-size: 0.85rem;
  background: #f3f4f6;
  color: var(--text-secondary);
}

.status-chip i {
  font-size: 0.65rem;
}

.status-chip--success {
  background: #d1fae5;
  color: #047857;
}

.status-chip--warning {
  background: #fef3c7;
  color: #92400e;
}

.status-chip--danger {
  background: #fee2e2;
  color: #991b1b;
}

.status-chip--info {
  background: #e0e7ff;
  color: #3730a3;
}

.summary-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
  gap: 1.5rem;
}

.summary-item {
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
}

.summary-label {
  font-size: 0.85rem;
  color: var(--text-muted);
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.summary-value {
  font-weight: 600;
  color: var(--text-primary);
}

.detail-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
  gap: 1.75rem;
}

.info-list {
  list-style: none;
  margin: 0;
  padding: 0;
  display: flex;
  flex-direction: column;
  gap: 0.85rem;
}

.info-line {
  display: flex;
  justify-content: space-between;
  gap: 1rem;
  flex-wrap: wrap;
}

.info-label {
  font-weight: 600;
  color: var(--text-muted);
}

.info-value {
  color: var(--text-primary);
}

.info-value a {
  color: var(--primary);
  text-decoration: none;
}

.info-value a:hover {
  text-decoration: underline;
}

.guest-list {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.guest-item {
  display: flex;
  gap: 1rem;
  align-items: center;
  padding: 0.75rem 1rem;
  background: #fdf9f5;
  border-radius: 0.85rem;
  border: 1px solid rgba(65, 36, 28, 0.08);
}

.guest-avatar {
  width: 2.5rem;
  height: 2.5rem;
  border-radius: 50%;
  background: linear-gradient(135deg, #656c97, #8691b8);
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 1.5rem;
}

.guest-name {
  margin: 0;
  font-weight: 600;
  color: var(--text-primary);
}

.guest-meta {
  margin: 0.25rem 0 0;
  color: var(--text-muted);
  font-size: 0.85rem;
}

.linked-reservations {
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
}

.linked-reservation-item {
  background: #fdf9f5;
  padding: 1rem 1.25rem;
  border-radius: 0.85rem;
  border: 1px solid rgba(65, 36, 28, 0.08);
  box-shadow: var(--shadow-sm);
}

.linked-reservation-header {
  display: flex;
  justify-content: space-between;
  gap: 1rem;
  margin-bottom: 0.5rem;
}

.linked-reservation-title {
  font-weight: 600;
  color: var(--text-primary);
}

.linked-reservation-dates {
  color: var(--text-muted);
  font-size: 0.85rem;
}

.linked-reservation-description {
  margin: 0.5rem 0;
  color: var(--text-secondary);
}

.linked-reservation-meta {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem 1.25rem;
  font-size: 0.85rem;
  color: var(--text-secondary);
}

.notes-list {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.note-item {
  background: #fff7ed;
  padding: 1rem 1.25rem;
  border-radius: 0.85rem;
  border: 1px solid rgba(206, 94, 26, 0.2);
}

.note-header {
  display: flex;
  justify-content: space-between;
  color: var(--text-secondary);
  margin-bottom: 0.5rem;
}

.note-content {
  margin: 0;
  color: var(--text-primary);
  line-height: 1.5;
}

@media (max-width: 1024px) {
  .detail-top-bar {
    flex-direction: column;
    align-items: flex-start;
    gap: 1rem;
  }

  .top-bar-actions {
    width: 100%;
    justify-content: flex-start;
  }
}

@media (max-width: 768px) {
  .reservation-detail-container {
    padding: 1.5rem;
  }

  .detail-grid {
    grid-template-columns: 1fr;
  }

  .summary-grid {
    grid-template-columns: 1fr;
  }

  .info-line {
    flex-direction: column;
    align-items: flex-start;
  }
}
</style>