<template>
  <div class="reservation-form-container">

    <!-- Notification de brouillon -->
    <DraftNotification :show="showDraftNotification" :draft-info="draftInfo" variant="info" title="Brouillon trouvé"
      @restore="handleRestoreDraft" @discard="handleDiscardDraft" @close="showDraftNotification = false" />

    <!-- Message d'erreur -->
    <div v-if="error" class="error-message">
      <div class="alert alert-danger">
        <i class="fas fa-exclamation-triangle"></i>
        {{ error }}
        <button @click="error = null" class="btn-close">&times;</button>
      </div>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="loading-state">
      <div class="spinner"></div>
      <p>Chargement...</p>
    </div>

    <!-- Contenu principal -->
    <div v-else>
      <!-- Header -->
      <div class="form-header">
        <div class="header-navigation">
          <router-link :to="backRoute" class="back-link">
            <i class="fas fa-arrow-left"></i>
            Retour aux réservations
          </router-link>
        </div>
      </div>

      <!-- Titre -->
      <div class="page-title-section">
        <div class="reservation-badge">
          <i class="fas fa-calendar-check"></i>
          Réservation
        </div>
        <h1 class="page-title">
          {{ isEditing ? `Modifier la réservation #${reservationId}` : 'Nouvelle réservation' }}
        </h1>
      </div>

      <!-- Formulaire principal -->
      <form @submit.prevent="submitForm" class="reservation-form">
        <div class="form-content">

          <!-- Section Client -->
          <div class="form-section">
            <div class="section-header">
              <h3>
                <i class="fas fa-user"></i>
                Informations Client
              </h3>
              <p class="section-description">Sélectionnez un client existant ou créez-en un nouveau</p>
            </div>

            <div class="customer-selection">
              <div class="form-group">
                <label>Mode de sélection client</label>
                <div class="radio-group">
                  <label class="radio-option">
                    <input type="radio" v-model="customerMode" value="existing" />
                    <span>Client existant</span>
                  </label>
                  <label class="radio-option">
                    <input type="radio" v-model="customerMode" value="new" />
                    <span>Nouveau client</span>
                  </label>
                </div>
              </div>

              <!-- Client existant -->
              <div v-if="customerMode === 'existing'" class="form-group">
                <label for="customer_search">Rechercher un client</label>
                <div class="search-customer">
                  <input id="customer_search" type="text" v-model="customerSearch" @input="searchCustomers"
                    placeholder="Tapez un nom, email ou téléphone..." class="form-control" />
                  <div v-if="customerResults.length > 0" class="customer-results">
                    <div v-for="customer in customerResults" :key="customer.id" @click="selectCustomer(customer)"
                      class="customer-result-item">
                      <div class="customer-info">
                        <strong>{{ customer.name }} {{ customer.last_name }}</strong>
                        <span class="customer-meta">{{ customer.email }} • {{ customer.phone }}</span>
                      </div>
                    </div>
                  </div>
                </div>
                <div v-if="form.customer_id && selectedCustomer" class="selected-customer">
                  <div class="customer-card">
                    <i class="fas fa-user-check"></i>
                    <div>
                      <strong>{{ selectedCustomer.name }} {{ selectedCustomer.last_name }}</strong>
                      <div class="customer-details">
                        {{ selectedCustomer.email }} • {{ selectedCustomer.phone }}
                      </div>
                    </div>
                    <button type="button" @click="clearCustomer" class="btn-clear btn-sm">
                      <i class="fas fa-times"></i>
                    </button>
                  </div>
                </div>
                <div v-if="errors.customer_id" class="error-text">{{ errors.customer_id }}</div>
              </div>

              <!-- Nouveau client -->
              <div v-else class="new-customer-form">
                <div class="form-row">
                  <div class="form-group">
                    <label for="customer_name">Prénom *</label>
                    <input id="customer_name" type="text" v-model="form.newCustomer.name" class="form-control"
                      :class="{ 'is-invalid': errors['newCustomer.name'] }" />
                    <div v-if="errors['newCustomer.name']" class="error-text">{{ errors['newCustomer.name'] }}</div>
                  </div>
                  <div class="form-group">
                    <label for="customer_last_name">Nom *</label>
                    <input id="customer_last_name" type="text" v-model="form.newCustomer.last_name" class="form-control"
                      :class="{ 'is-invalid': errors['newCustomer.last_name'] }" />
                    <div v-if="errors['newCustomer.last_name']" class="error-text">{{ errors['newCustomer.last_name'] }}
                    </div>
                  </div>
                </div>
                <div class="form-row">
                  <div class="form-group">
                    <label for="customer_email">Email *</label>
                    <input id="customer_email" type="email" v-model="form.newCustomer.email" class="form-control"
                      :class="{ 'is-invalid': errors['newCustomer.email'] }" />
                    <div v-if="errors['newCustomer.email']" class="error-text">{{ errors['newCustomer.email'] }}</div>
                  </div>
                  <div class="form-group">
                    <label for="customer_phone">Téléphone *</label>
                    <input id="customer_phone" type="tel" v-model="form.newCustomer.phone" class="form-control"
                      :class="{ 'is-invalid': errors['newCustomer.phone'] }" />
                    <div v-if="errors['newCustomer.phone']" class="error-text">{{ errors['newCustomer.phone'] }}</div>
                  </div>
                </div>
                <div class="form-group">
                  <label for="customer_address">Adresse</label>
                  <textarea id="customer_address" v-model="form.newCustomer.address" class="form-control" rows="2"
                    placeholder="Adresse complète du client..."></textarea>
                </div>
              </div>
            </div>
            <!-- Section Dates et Détails -->
            <div class="form-section">
              <div class="section-header">
                <h3>
                  <i class="fas fa-calendar-alt"></i>
                  Dates et Détails de Séjour
                </h3>
                <p class="section-description">Définissez les dates et les détails de la réservation</p>
              </div>

              <div class="form-row">
                <div class="form-group">
                  <label for="checkin">Date d'arrivée *</label>
                  <input id="checkin" type="date" v-model="form.checkin" class="form-control"
                    :class="{ 'is-invalid': errors.checkin }" :min="minDate" />
                  <div v-if="errors.checkin" class="error-text">{{ errors.checkin }}</div>
                </div>
                <div class="form-group">
                  <label for="checkout">Date de départ *</label>
                  <input id="checkout" type="date" v-model="form.checkout" class="form-control"
                    :class="{ 'is-invalid': errors.checkout }" :min="form.checkin || minDate" />
                  <div v-if="errors.checkout" class="error-text">{{ errors.checkout }}</div>
                </div>
              </div>

              <div class="form-row">
                <div class="form-group">
                  <label for="guests">Nombre d'invités *</label>
                  <input id="guests" type="number" v-model.number="form.guests" class="form-control"
                    :class="{ 'is-invalid': errors.guests }" min="1" max="20" />
                  <div v-if="errors.guests" class="error-text">{{ errors.guests }}</div>
                </div>
                <div class="form-group">
                  <label for="amount">Montant (€) *</label>
                  <input id="amount" type="text" v-model.number="form.amount" class="form-control"
                    :class="{ 'is-invalid': errors.amount }" min="0" step="0.01" readonly />
                  <small class="form-text">Calculé automatiquement</small>
                  <div v-if="errors.amount" class="error-text">{{ errors.amount }}</div>
                </div>
              </div>

              <div class="form-group">
                <label for="special_requests">Demandes spéciales</label>
                <textarea id="special_requests" v-model="form.special_requests" class="form-control" rows="3"
                  placeholder="Allergies, préférences alimentaires, besoins particuliers..."></textarea>
              </div>
            </div>
          </div>

          <!-- Section Hébergements et Services -->
          <div class="form-section">
            <div class="section-header">
              <h3>
                <i class="fas fa-bed"></i>
                Hébergements et Services
              </h3>
              <p class="section-description">Sélectionnez les hébergements et services pour cette réservation</p>
            </div>

            <!-- Hébergements -->
            <div class="form-group">
              <label class="form-label">
                Hébergements
                <span class="required">*</span>
              </label>

              <div class="services-list">
                <div v-for="room in accommodations" :key="room.id" class="service-item">
                  <label class="checkbox-wrapper">
                    <input type="checkbox" :value="room.id" :checked="selectedAccommodations.includes(room.id)"
                      @change="toggleAccommodation(room.id)">
                    <div class="service-info">
                      <span class="service-name">{{ room.name }}</span>
                      <span class="service-price">{{ room.formatted_price || (room.price + ' €') }}</span>
                      <span class="service-meta" v-if="room.capacity">
                        Capacité: {{ room.capacity }} pers.
                      </span>
                    </div>
                  </label>
                  <div v-if="selectedAccommodations.includes(room.id)" class="quantity-control">
                    <label>Qté:</label>
                    <input type="number" min="1" max="5" :value="getAccommodationQuantity(room.id)"
                      @input="setAccommodationQuantity(room.id, $event.target.value)" class="qty-input">
                  </div>
                </div>

                <div v-if="loadingAccommodations" class="loading-item">
                  <i class="fas fa-spinner fa-spin"></i> Chargement des hébergements...
                </div>

                <div v-if="!loadingAccommodations && accommodations.length === 0" class="empty-item">
                  Aucun hébergement disponible
                </div>
              </div>

              <div v-if="errors.accommodations" class="error-text">
                {{ errors.accommodations }}
              </div>
            </div>

            <!-- Activités -->
            <div class="form-group">
              <label class="form-label">Activités</label>

              <div class="services-list">
                <div v-for="activity in availableActivities" :key="activity.id" class="service-item">
                  <label class="checkbox-wrapper">
                    <input type="checkbox" :value="activity.id" :checked="selectedActivities.includes(activity.id)"
                      @change="toggleActivity(activity.id)">
                    <div class="service-info">
                      <span class="service-name">{{ activity.name }}</span>
                      <span class="service-price">{{ activity.formatted_price || (activity.price + ' €') }}</span>
                      <span class="service-meta" v-if="activity.productableDetail?.duration">
                        Durée: {{ activity.productableDetail.duration }} min
                      </span>
                    </div>
                  </label>
                  <div v-if="selectedActivities.includes(activity.id)" class="quantity-control">
                    <label>Qté:</label>
                    <input type="number" min="1" max="10" :value="getActivityQuantity(activity.id)"
                      @input="setActivityQuantity(activity.id, $event.target.value)" class="qty-input">
                  </div>
                </div>

                <div v-if="loadingServices" class="loading-item">
                  <i class="fas fa-spinner fa-spin"></i> Chargement des activités...
                </div>

                <div v-if="!loadingServices && availableActivities.length === 0" class="empty-item">
                  Aucune activité disponible
                </div>
              </div>
            </div>

            <!-- Menus -->
            <div class="form-group">
              <label class="form-label">Menus</label>

              <div class="services-list">
                <div v-for="menu in availableMenus" :key="menu.id" class="service-item">
                  <label class="checkbox-wrapper">
                    <input type="checkbox" :value="menu.id" :checked="selectedMenus.includes(menu.id)"
                      @change="toggleMenu(menu.id)">
                    <div class="service-info">
                      <span class="service-name">{{ menu.name }}</span>
                      <span class="service-price">{{ menu.formatted_price || (menu.price + ' €') }}</span>
                    </div>
                  </label>
                  <div v-if="selectedMenus.includes(menu.id)" class="quantity-control">
                    <label>Qté:</label>
                    <input type="number" min="1" max="20" :value="getMenuQuantity(menu.id)"
                      @input="setMenuQuantity(menu.id, $event.target.value)" class="qty-input">
                  </div>
                </div>

                <div v-if="loadingServices" class="loading-item">
                  <i class="fas fa-spinner fa-spin"></i> Chargement des menus...
                </div>

                <div v-if="!loadingServices && availableMenus.length === 0" class="empty-item">
                  Aucun menu disponible
                </div>
              </div>
            </div>
          </div>


          <!-- Section Statuts -->
          <!-- 3️⃣ SECTION PAIEMENTS -->
          <div class="form-section">
            <div class="section-header">
              <h3>
                <i class="fas fa-credit-card"></i>
                Statuts et Paiement
              </h3>
              <p class="section-description">Gérez les statuts de réservation et de paiement</p>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label for="status">Statut réservation *</label>
                <select id="status" v-model="form.status" class="form-control" :class="{ 'is-invalid': errors.status }">
                  <option value="pending">En attente</option>
                  <option value="confirmed">Confirmée</option>
                  <option value="cancelled">Annulée</option>
                  <option value="completed">Terminée</option>
                </select>
                <div v-if="errors.status" class="error-text">{{ errors.status }}</div>
              </div>

              <div class="form-group">
                <label for="payment_status">Statut du paiement</label>
                <select id="payment_status" v-model="form.payment_status" class="form-control">
                  <option value="pending">En attente</option>
                  <option value="partial">Acompte versé</option>
                  <option value="paid">Payé</option>
                  <option value="failed">Échec</option>
                </select>
              </div>
            </div>

            <!-- Moyen de paiement (seulement si payé ou acompte) -->
            <div v-if="form.payment_status === 'paid' || form.payment_status === 'partial'" class="form-group">
              <label for="payment_method">Moyen de paiement</label>
              <select id="payment_method" v-model="form.payment_method" class="form-control">
                <option value="">Sélectionner...</option>
                <option value="cash">Espèces</option>
                <option value="card">Carte bancaire</option>
                <option value="transfer">Virement</option>
                <option value="check">Chèque</option>
                <option value="online">Paiement en ligne</option>
              </select>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label for="booking_source">Source de réservation</label>
                <select id="booking_source" v-model="form.booking_source" class="form-control">
                  <option value="website">Site web</option>
                  <option value="phone">Téléphone</option>
                  <option value="email">Email</option>
                  <option value="booking.com">Booking.com</option>
                  <option value="airbnb">Airbnb</option>
                  <option value="other">Autre</option>
                </select>
              </div>

              <!-- Badge facture -->
              <div class="form-group">
                <label>Statut de la facture</label>
                <div class="invoice-status-badges">
                  <span v-if="form.has_invoice" class="badge badge-success">
                    <i class="fas fa-file-invoice"></i>
                    Facture émise
                  </span>
                  <span v-else class="badge badge-warning">
                    <i class="fas fa-file-invoice"></i>
                    Pas de facture
                  </span>
                </div>
              </div>
            </div>
          </div>

          <!-- Notes internes -->
          <div class="row">
            <div class="form-section">
              <div class="section-header">
                <h3>
                  <i class="fas fa-sticky-note"></i>
                  Notes Internes
                </h3>
                <p class="section-description">Notes visibles uniquement par l'équipe</p>
              </div>

              <div class="form-group">
                <label for="internal_notes">Notes internes</label>
                <textarea id="internal_notes" v-model="form.internal_notes" class="form-control" rows="3"
                  style="margin-bottom: 1rem;"
                  placeholder="Notes pour l'équipe, instructions particulières..."></textarea>
              </div>
            </div>
          </div>
        </div>
        <!-- Actions du formulaire -->
        <div class="form-actions">
          <div class="actions-left">
            <button type="button" @click="resetForm" class="btn btn-outline btn-sm" :disabled="saving">
              <i class="fas fa-undo"></i>
              Réinitialiser
            </button>
          </div>
          <div class="actions-right">
            <router-link :to="backRoute" class="btn btn-outline btn-sm">
              Annuler
            </router-link>
            <button type="submit" class="btn btn-primary btn-sm" :disabled="!isFormValid || saving">
              <i v-if="saving" class="fas fa-spinner fa-spin"></i>
              <i v-else class="fas fa-save"></i>
              {{ saving ? 'Enregistrement...' : (isEditing ? 'Modifier' : 'Créer') }}
            </button>
          </div>
        </div>
      </form>
    </div>
  </div>
</template>

<script>
import AdminApi from '@/services/AdminApi'
import ProductsApi from '@/services/ProductsApi'
import { debounce } from '@/shared/utils/helpers'
import DraftNotification from '@/shared/components/ui/DraftNotification.vue'
import { useFormDraft } from '@/shared/composables/useFormDraft'
import { computeQuoteTotal, computeNights } from '@/shared/composables/useQuotePricing'

export default {
  name: 'ReservationForm',
  components: { DraftNotification },
  props: {
    action: { type: String, required: true } // 'create' | 'edit'
  },

  data() {
    return {
      loading: false,
      saving: false,
      error: null,
      reservation: null,
      showDraftNotification: false,

      // Mode de sélection client
      customerMode: 'existing',

      // Recherche clients
      customerSearch: '',
      customerResults: [],
      selectedCustomer: null,

      // Produits par catégorie
      accommodations: [],
      availableActivities: [],
      availableMenus: [],

      // Sélections et quantités
      selectedAccommodations: [],
      selectedActivities: [],
      selectedMenus: [],
      accommodationQuantities: {},
      activityQuantities: {},
      menuQuantities: {},

      loadingAccommodations: false,
      loadingServices: false,

      // Formulaire
      form: {
        customer_id: null,
        newCustomer: {
          name: '',
          last_name: '',
          email: '',
          phone: '',
          address: ''
        },
        checkin: '',
        checkout: '',
        guests: 2,
        amount: '0.00',
        status: 'pending',
        payment_status: 'pending',
        booking_source: 'website',
        special_requests: '',
        internal_notes: '',
        payment_method: null,
        has_invoice: false,
      },

      errors: {}
    }
  },

  computed: {
    isEditing() {
      return this.action === 'edit'
    },

    draftInfo() {
      return this._draft ? this._draft.getDraftInfo() : null
    },

    reservationId() {
      return this.$route.params.id
    },

    backRoute() {
      if (this.isEditing && this.reservationId) {
        return { name: 'ReservationDetail', params: { id: this.reservationId } }
      }
      return { name: 'AdminReservations' }
    },

    isFormValid() {
      const hasValidCustomer = this.customerMode === 'existing'
        ? this.form.customer_id
        : this.form.newCustomer.name &&
        this.form.newCustomer.last_name &&
        this.form.newCustomer.email &&
        this.form.newCustomer.phone

      const hasAccommodations = this.selectedAccommodations.length > 0

      return hasValidCustomer &&
        hasAccommodations &&
        this.form.checkin &&
        this.form.checkout &&
        this.form.guests > 0 &&
        this.form.amount >= 0 &&
        this.form.status &&
        Object.keys(this.errors).length === 0
    },

    minDate() {
      return new Date().toISOString().split('T')[0]
    },
    // 1) Construire l’entrée pour le composable
    pricingSelected() {
      return {
        room: this.selectedAccommodations,     // ex: [41]
        activity: this.selectedActivities,     // ex: [32,34]
        menu: this.selectedMenus               // ex: [28,29]
      }
    },

    pricingCatalog() {
      return {
        rooms: this.accommodations,            // [{id, name, price, …}]
        activities: this.availableActivities,  // idem
        menus: this.availableMenus             // idem
      }
    },

    pricingOverrides() {
      // le composable accepte les overrides pour activity/menu (pas room)
      return {
        activity: { ...this.activityQuantities }, // {32: 2, 34: 2, …}
        menu: { ...this.menuQuantities }          // {28: 2, 29: 2, …}
      }
    },

    // 2) Calcul complet via le composable
    quoteComputed() {
      return computeQuoteTotal({
        selected: this.pricingSelected,
        catalog: this.pricingCatalog,
        dates: { checkin: this.form.checkin, checkout: this.form.checkout, guests: this.form.guests },
        rules: {},        // si tu as des règles custom, mets-les ici
        overrides: this.pricingOverrides
      })
      // => { total (number), nights (number), lines: [{type,id,name,unit,qty,lineTotal}] }
    },

    // 3) Montant toujours en string avec 2 décimales (ce que veut ton BE)
    amountStr() {
      const n = Number(this.quoteComputed?.total ?? 0)
      return Number.isFinite(n) ? n.toFixed(2) : '0.00'
    },

    // 4) Payload produits agrégé à partir des lines du composable
    productsPayload() {
      const lines = this.quoteComputed?.lines || []
      return lines.map(l => ({ product_id: l.id, quantity: l.qty }))
    }
  },

  watch: {
    selectedAccommodations: { handler() { this.form.amount = this.amountStr }, deep: true },
    selectedActivities: { handler() { this.form.amount = this.amountStr }, deep: true },
    selectedMenus: { handler() { this.form.amount = this.amountStr }, deep: true },
    accommodationQuantities: { handler() { this.form.amount = this.amountStr }, deep: true },
    activityQuantities: { handler() { this.form.amount = this.amountStr }, deep: true },
    menuQuantities: { handler() { this.form.amount = this.amountStr }, deep: true },
    'form.checkin'(v) { this.form.amount = this.amountStr },
    'form.checkout'(v) { this.form.amount = this.amountStr },
    'form.guests'(v) { this.form.amount = this.amountStr },
  },

  async created() {
    await this.initializeComponent()
    this.debouncedSearchCustomers = debounce(this.performCustomerSearch, 300)
  },

  async mounted() {
    const key = this.action === 'edit'
      ? `reservation-form-edit-${this.$route.params.id || 'unknown'}`
      : 'reservation-form-create'

    this._draft = useFormDraft(key, this.form, {
      ttl: 1000 * 60 * 60 * 24 * 3,
      autoSave: true,
      autoSaveDelay: 800
    })

    if (this._draft.checkDraft()) {
      this.showDraftNotification = true
    }

    await this.loadAccommodations()
    await this.loadActivitiesAndMenus()
  },

  methods: {
    async initializeComponent() {
      this.loading = true
      this.error = null

      try {
        if (this.isEditing) {
          await this.fetchReservation()
        }
      } catch (error) {
        console.error('Erreur lors de l\'initialisation:', error)
        this.error = 'Erreur lors du chargement du formulaire'
      } finally {
        this.loading = false
      }
    },

    async fetchReservation() {
      try {
        const response = await AdminApi.get(`/admin/reservations/${this.reservationId}`)
        this.reservation = response.data
        this.populateForm()
      } catch (error) {
        throw new Error('Impossible de charger la réservation')
      }
    },

    populateForm() {
      if (!this.reservation) return

      this.form.checkin = this.reservation.checkin
      this.form.checkout = this.reservation.checkout
      this.form.guests = this.reservation.guests || 2
      this.form.amount = this.reservation.amount
      this.form.status = this.reservation.status
      this.form.payment_status = this.reservation.payment_status
      this.form.booking_source = this.reservation.booking_source
      this.form.special_requests = this.reservation.special_requests || ''
      this.form.internal_notes = this.reservation.internal_notes || ''
      this.form.invoice_number = this.reservation.invoice_number || ''

      if (this.reservation.customer) {
        this.selectedCustomer = this.reservation.customer
        this.form.customer_id = this.reservation.customer_id
        this.customerMode = 'existing'
      }

      if (this.reservation.products && Array.isArray(this.reservation.products)) {
        this.reservation.products.forEach(product => {
          const type = product.productable_type
          const id = product.id

          if (type === 'App\\Models\\Room') {
            if (!this.selectedAccommodations.includes(id)) {
              this.selectedAccommodations.push(id)
              this.accommodationQuantities[id] = (this.accommodationQuantities[id] || 0) + 1
            }
          } else if (type === 'App\\Models\\Activity') {
            if (!this.selectedActivities.includes(id)) {
              this.selectedActivities.push(id)
            }
            this.activityQuantities[id] = (this.activityQuantities[id] || 0) + (product.pivot?.quantity || 1)
          } else if (type === 'App\\Models\\Menu') {
            if (!this.selectedMenus.includes(id)) {
              this.selectedMenus.push(id)
            }
            this.menuQuantities[id] = (this.menuQuantities[id] || 0) + (product.pivot?.quantity || 1)
          }
        })
      }
    },

    handleRestoreDraft() {
      try {
        const restored = this._draft.restoreDraft((data) => {
          this.form = { ...this.form, ...data }
        })
        if (restored) {
          this.$toast?.success?.('Brouillon restauré')
        }
      } catch (e) {
        console.error('❌ Restauration du brouillon', e)
        this.$toast?.error?.('Impossible de restaurer le brouillon')
      } finally {
        this.showDraftNotification = false
      }
    },

    handleDiscardDraft() {
      try {
        this._draft.clearDraft()
        this.$toast?.info?.('Brouillon ignoré')
      } catch (e) {
        console.error('❌ Suppression du brouillon', e)
      } finally {
        this.showDraftNotification = false
      }
    },

    // Recherche clients
    searchCustomers() {
      if (this.customerSearch.length >= 2) {
        this.debouncedSearchCustomers()
      } else {
        this.customerResults = []
      }
    },

    async performCustomerSearch() {
      try {
        const response = await AdminApi.get('/customers', {
          params: {
            search: this.customerSearch,
            limit: 10
          }
        })
        this.customerResults = response.data.data || response.data
      } catch (error) {
        console.error('Erreur recherche clients:', error)
        this.customerResults = []
      }
    },

    selectCustomer(customer) {
      this.selectedCustomer = customer
      this.form.customer_id = customer.id
      this.customerSearch = ''
      this.customerResults = []
    },

    clearCustomer() {
      this.selectedCustomer = null
      this.form.customer_id = null
      this.customerSearch = ''
    },

    // Chargement des produits
    async loadAccommodations() {
      if (this.accommodations.length > 0) return

      this.loadingAccommodations = true
      try {
        const response = await ProductsApi.getProducts({
          type: 'App\\Models\\Room',
          status: 'active',
          per_page: 50
        })
        this.accommodations = response.data || []
        console.log('✅ Hébergements chargés:', this.accommodations.length)
      } catch (error) {
        console.error('❌ Erreur chargement hébergements:', error)
        this.$toast?.error?.('Impossible de charger les hébergements')
      } finally {
        this.loadingAccommodations = false
      }
    },

    async loadActivitiesAndMenus() {
      if (this.availableActivities.length > 0 && this.availableMenus.length > 0) return

      this.loadingServices = true
      try {
        const response = await ProductsApi.getProducts({
          status: 'active',
          per_page: 100
        })

        const products = response.data || []

        this.availableActivities = products.filter(p =>
          p.productable_type === 'App\\Models\\Activity' ||
          p.typeConfig?.label === 'Activités'
        )

        this.availableMenus = products.filter(p =>
          p.productable_type === 'App\\Models\\Menu' ||
          p.typeConfig?.label === 'Menus'
        )

        console.log('✅ Chargé:', this.availableActivities.length, 'activités et', this.availableMenus.length, 'menus')
      } catch (error) {
        console.error('❌ Erreur chargement activités/menus:', error)
        this.$toast?.error?.('Impossible de charger les services')
      } finally {
        this.loadingServices = false
      }
    },

    // Gestion des hébergements
    toggleAccommodation(accommodationId) {
      const index = this.selectedAccommodations.indexOf(accommodationId)
      if (index > -1) {
        this.selectedAccommodations.splice(index, 1)
        delete this.accommodationQuantities[accommodationId]
      } else {
        this.selectedAccommodations.push(accommodationId)
        this.accommodationQuantities[accommodationId] = 1
      }
    },

    getAccommodationQuantity(accommodationId) {
      return this.accommodationQuantities[accommodationId] || 1
    },

    setAccommodationQuantity(accommodationId, quantity) {
      this.accommodationQuantities[accommodationId] = Math.max(1, parseInt(quantity) || 1)
    },

    // Gestion des activités
    toggleActivity(activityId) {
      const index = this.selectedActivities.indexOf(activityId)
      if (index > -1) {
        this.selectedActivities.splice(index, 1)
        delete this.activityQuantities[activityId]
      } else {
        this.selectedActivities.push(activityId)
        this.activityQuantities[activityId] = 1
      }
    },

    getActivityQuantity(activityId) {
      return this.activityQuantities[activityId] || 1
    },

    setActivityQuantity(activityId, quantity) {
      this.activityQuantities[activityId] = Math.max(1, parseInt(quantity) || 1)
    },

    // Gestion des menus
    toggleMenu(menuId) {
      const index = this.selectedMenus.indexOf(menuId)
      if (index > -1) {
        this.selectedMenus.splice(index, 1)
        delete this.menuQuantities[menuId]
      } else {
        this.selectedMenus.push(menuId)
        this.menuQuantities[menuId] = 1
      }
    },

    getMenuQuantity(menuId) {
      return this.menuQuantities[menuId] || 1
    },

    setMenuQuantity(menuId, quantity) {
      this.menuQuantities[menuId] = Math.max(1, parseInt(quantity) || 1)
    },

    // Validation
    validateForm() {
      this.errors = {}

      if (this.customerMode === 'existing') {
        if (!this.form.customer_id) {
          this.errors.customer_id = 'Veuillez sélectionner un client'
        }
      } else {
        if (!this.form.newCustomer.name) {
          this.errors['newCustomer.name'] = 'Le prénom est obligatoire'
        }
        if (!this.form.newCustomer.last_name) {
          this.errors['newCustomer.last_name'] = 'Le nom est obligatoire'
        }
        if (!this.form.newCustomer.email) {
          this.errors['newCustomer.email'] = 'L\'email est obligatoire'
        }
        if (!this.form.newCustomer.phone) {
          this.errors['newCustomer.phone'] = 'Le téléphone est obligatoire'
        }
      }

      if (this.selectedAccommodations.length === 0) {
        this.errors.accommodations = 'Au moins un hébergement doit être sélectionné'
      }

      if (!this.form.checkin) {
        this.errors.checkin = 'La date d\'arrivée est obligatoire'
      }
      if (!this.form.checkout) {
        this.errors.checkout = 'La date de départ est obligatoire'
      }
      if (this.form.checkin && this.form.checkout && this.form.checkin >= this.form.checkout) {
        this.errors.checkout = 'La date de départ doit être après l\'arrivée'
      }

      if (!this.form.guests || this.form.guests < 1) {
        this.errors.guests = 'Le nombre d\'invités doit être d\'au moins 1'
      }

      if (this.form.amount < 0) {
        this.errors.amount = 'Le montant doit être positif'
      }

      if (!this.form.status) {
        this.errors.status = 'Le statut est obligatoire'
      }

      return Object.keys(this.errors).length === 0
    },

    // Soumission
    async submitForm() {
      if (!this.validateForm()) {
        this.$toast?.error?.('Veuillez corriger les erreurs du formulaire')
        return
      }

      this.saving = true
      this.error = null

      try {
        const payload = {
          checkin: this.form.checkin,
          checkout: this.form.checkout,
          guests: Number(this.form.guests || 1),
          amount: this.amountStr,               // <-- string "1140.00"
          status: this.form.status,
          payment_status: this.form.payment_status,
          booking_source: this.form.booking_source || 'website',
          special_requests: this.form.special_requests || '',
          internal_notes: this.form.internal_notes || '',
          invoice_number: this.form.invoice_number ?? null,
          products: this.productsPayload,       // <-- [{ product_id, quantity }]
        }

        if (this.customerMode === 'existing' && this.selectedCustomer) {
          payload.customer_id = this.selectedCustomer.id
        } else {
          payload.new_customer = { ...this.form.newCustomer }
        }

        payload.products = []

        this.selectedAccommodations.forEach(id => {
          const qty = this.accommodationQuantities[id] || 1
          for (let i = 0; i < qty; i++) {
            payload.products.push({ product_id: id, quantity: qty })
          }
        })

        this.selectedActivities.forEach(id => {
          const qty = this.activityQuantities[id] || 1
          payload.products.push({ product_id: id, quantity: qty })
        })

        this.selectedMenus.forEach(id => {
          const qty = this.menuQuantities[id] || 1
          payload.products.push({ product_id: id, quantity: qty })
        })

        
        // === Patch: align payload with backend expectations ===
        // map customer -> customer_data (keep amount as-is)
        if (!payload.customer_id) {
          const c = this.form?.newCustomer || payload.new_customer || {};
          const normalize = (x) => ({
            name: (x?.name || '').trim(),
            last_name: (x?.last_name || '').trim(),
            email: (x?.email || '').trim(),
            phone: (x?.phone || '').trim(),
            address: (x?.address || '').trim(),
          });
          payload.customer_data = normalize(c);
        }
        delete payload.new_customer;

        // guests -> number_of_adults / children
        payload.number_of_adults = Number(this.form?.guests ?? 1);
        payload.number_of_children = Number(this.form?.children ?? 0);
        delete payload.guests;

        // comment preferred by backend; keep original fields for UI
        payload.comment = ((this.form?.special_requests || '') + ' ' + (this.form?.internal_notes || '')).trim() || null;

        // ensure root product_id (NOT NULL on DB)
        if (!payload.product_id && Array.isArray(payload.products) && payload.products.length > 0) {
          const primary = payload.products.find(p => Number(p?.quantity ?? 0) > 0) || payload.products[0];
          payload.product_id = Number(primary.product_id);
        }
        // === End patch ===
console.log('📤 Envoi des données:', payload)

        let response
        if (this.isEditing) {
          response = await AdminApi.put(`/admin/reservations/${this.reservationId}`, payload)
          this.$toast?.success?.('Réservation modifiée avec succès')
        } else {
          response = await AdminApi.post('/admin/reservations', payload)
          this.$toast?.success?.('Réservation créée avec succès')
          this._draft?.clearDraft()
        }

        if (response.data?.id) {
          this.$router.push({
            name: 'ReservationDetail',
            params: { id: response.data.id }
          })
        } else {
          this.$router.push({ name: 'AdminReservations' })
        }

      } catch (error) {
        console.error('❌ Erreur soumission:', error)
        this.error = error.response?.data?.message || error.message || 'Erreur lors de l\'enregistrement'
        this.$toast?.error?.(this.error)
      } finally {
        this.saving = false
      }
    },

    resetForm() {
      if (confirm('Êtes-vous sûr de vouloir réinitialiser le formulaire ?')) {
        this.form = {
          customer_id: null,
          newCustomer: {
            name: '',
            last_name: '',
            email: '',
            phone: '',
            address: ''
          },
          checkin: '',
          checkout: '',
          guests: 2,
          amount: 0,
          status: 'pending',
          payment_status: 'pending',
          booking_source: 'website',
          special_requests: '',
          internal_notes: '',
          invoice_number: ''
        }
        this.selectedAccommodations = []
        this.selectedActivities = []
        this.selectedMenus = []
        this.accommodationQuantities = {}
        this.activityQuantities = {}
        this.menuQuantities = {}
        this.selectedCustomer = null
        this.customerMode = 'existing'
        this.errors = {}
        this._draft?.clearDraft()
      }
    }
  }
}
</script>

<style scoped>
/* Container principal */
.reservation-form-container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 2rem;
}

/* Header */
.form-header {
  margin-bottom: 1cqb;
}

.back-link:hover {
  color: #3b82f6;
}


/* Titre */
.page-title-section {
  margin-bottom: 1rem;
}

.reservation-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem 1rem;
  background: #eff6ff;
  color: #3b82f6;
  border-radius: 6px;
  font-size: 14px;
  font-weight: 500;
  margin-bottom: 1rem;
}

.page-title {
  font-weight: 700;
}

/* Formulaire */
.reservation-form {
  background: white;
  border-radius: 12px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}


/* Sections */
.form-section {
  border-bottom: 1px solid #e5e7eb;
}


.section-header h3 i {
  color: #3b82f6;
}

.section-description {
  color: #6b7280;
  font-size: 14px;
}

/* Form groups */

.required {
  color: #dc3545;
  margin-left: 4px;
  font-weight: 600;
}


.form-control:focus {
  border-color: #3b82f6;
}

.form-control.is-invalid {
  border-color: #dc2626;
}

.error-text {
  display: block;
  margin-top: 0.5rem;
  color: #dc2626;
  font-size: 13px;
  font-weight: 500;
}

/* Radio buttons */

.radio-option {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  cursor: pointer;
  user-select: none;
}

.radio-option input[type="radio"] {
  width: 18px;
  height: 18px;
  cursor: pointer;
  margin-right: 0.75rem;
}

/* Client selection */
.search-customer {
  position: relative;
}

.customer-results {
  position: absolute;
  top: 100%;
  left: 0;
  right: 0;
  background: white;
  border: 1px solid #d1d5db;
  border-top: none;
  border-radius: 0 0 6px 6px;
  max-height: 300px;
  overflow-y: auto;
  z-index: 10;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.customer-result-item {
  padding: 0.75rem;
  cursor: pointer;
  transition: background-color 0.2s;
}

.customer-result-item:hover {
  background: #f3f4f6;
}

.customer-info {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.customer-meta {
  font-size: 13px;
  color: #6b7280;
}

.selected-customer,
.selected-product {
  margin-top: 1rem;
}

.customer-card,
.product-card {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1rem;
  background: #f9fafb;
  border: 1px solid #e5e7eb;
  border-radius: 6px;
}

.customer-card i,
.product-card i {
  font-size: 24px;
  color: #3b82f6;
}

.customer-details,
.product-details {
  font-size: 13px;
  color: #6b7280;
  margin-top: 0.25rem;
}

/* Services list */
.services-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
  margin-top: 8px;
}

.service-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 12px 16px;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  background: #ffffff;
  transition: all 0.2s ease;
  min-height: 56px;
}

.service-item:hover {
  border-color: #d1d5db;
  background: #f9fafb;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
}

.checkbox-wrapper {
  display: flex;
  align-items: center;
  gap: 12px;
  flex: 1;
  cursor: pointer;
  user-select: none;
}

.checkbox-wrapper input[type="checkbox"] {
  position: absolute;
  opacity: 0;
  cursor: pointer;
  height: 0;
  width: 0;
}

.checkmark {
  position: relative;
  height: 20px;
  width: 20px;
  min-width: 20px;
  background-color: #fff;
  border: 2px solid #d1d5db;
  border-radius: 4px;
  transition: all 0.2s ease;
  flex-shrink: 0;
}

.checkbox-wrapper:hover .checkmark {
  border-color: #9ca3af;
}

.checkbox-wrapper input:checked~.checkmark {
  background-color: #3b82f6;
  border-color: #3b82f6;
}

.checkmark:after {
  content: "";
  position: absolute;
  display: none;
  left: 6px;
  top: 2px;
  width: 5px;
  height: 10px;
  border: solid white;
  border-width: 0 2px 2px 0;
  transform: rotate(45deg);
}

.checkbox-wrapper input:checked~.checkmark:after {
  display: block;
}

.service-info {
  display: flex;
  flex-direction: row;
  align-items: center;
  gap: 12px;
  flex: 1;
  min-width: 0;
  flex-wrap: wrap;
}

.service-name {
  font-weight: 600;
  color: #111827;
  font-size: 14px;
  white-space: nowrap;
}

.service-price {
  color: #3b82f6;
  font-weight: 500;
  font-size: 14px;
  white-space: nowrap;
}

.service-meta {
  color: #6b7280;
  font-size: 12px;
  width: 100%;
  flex-basis: 100%;
}

.quantity-control {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-left: 12px;
  flex-shrink: 0;
}

.quantity-control label {
  font-size: 13px;
  color: #6b7280;
  font-weight: 500;
  margin: 0;
}

.qty-input {
  width: 60px;
  padding: 6px 8px;
  border: 1px solid #d1d5db;
  border-radius: 4px;
  text-align: center;
  font-size: 14px;
  font-weight: 500;
  color: #111827;
  transition: border-color 0.2s ease;
}

.qty-input:focus {
  outline: none;
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.loading-item,
.empty-item {
  padding: 24px;
  text-align: center;
  color: #6b7280;
  font-size: 14px;
  background: #f9fafb;
  border: 1px dashed #d1d5db;
  border-radius: 8px;
}

.loading-item i {
  margin-right: 8px;
  color: #3b82f6;
}

.empty-item {
  font-style: italic;
}

/* Responsive */
@media (max-width: 768px) {
  .reservation-form-container {
    padding: 1rem;
  }

  .form-content {
    padding: 1rem;
  }

  .form-row {
    grid-template-columns: 1fr;
  }

  .form-actions {
    flex-direction: column;
    gap: 1rem;
  }

  .actions-left,
  .actions-right {
    width: 100%;
    justify-content: stretch;
  }

  .btn {
    width: 100%;
    justify-content: center;
  }

  .service-item {
    flex-direction: column;
    align-items: flex-start;
    gap: 0.5rem;
  }

  .quantity-control {
    width: 100%;
  }
}
</style>