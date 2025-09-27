<template>
  <div class="reservation-form-container">
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
          <div class="breadcrumb">
            <span>Réservations</span>
            <i class="fas fa-chevron-right"></i>
            <span>{{ isEditing ? `Modifier #${reservationId}` : 'Nouvelle réservation' }}</span>
          </div>
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
                  <input
                    id="customer_search"
                    type="text"
                    v-model="customerSearch"
                    @input="searchCustomers"
                    placeholder="Tapez un nom, email ou téléphone..."
                    class="form-control"
                  />
                  <div v-if="customerResults.length > 0" class="customer-results">
                    <div
                      v-for="customer in customerResults"
                      :key="customer.id"
                      @click="selectCustomer(customer)"
                      class="customer-result-item"
                    >
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
              <div v-if="customerMode === 'new'" class="new-customer-fields">
                <div class="form-row">
                  <div class="form-group">
                    <label for="customer_name">Prénom *</label>
                    <input
                      id="customer_name"
                      type="text"
                      v-model="form.newCustomer.name"
                      class="form-control"
                      :class="{ 'is-invalid': errors['newCustomer.name'] }"
                    />
                    <div v-if="errors['newCustomer.name']" class="error-text">{{ errors['newCustomer.name'] }}</div>
                  </div>
                  <div class="form-group">
                    <label for="customer_last_name">Nom *</label>
                    <input
                      id="customer_last_name"
                      type="text"
                      v-model="form.newCustomer.last_name"
                      class="form-control"
                      :class="{ 'is-invalid': errors['newCustomer.last_name'] }"
                    />
                    <div v-if="errors['newCustomer.last_name']" class="error-text">{{ errors['newCustomer.last_name'] }}</div>
                  </div>
                </div>
                <div class="form-row">
                  <div class="form-group">
                    <label for="customer_email">Email *</label>
                    <input
                      id="customer_email"
                      type="email"
                      v-model="form.newCustomer.email"
                      class="form-control"
                      :class="{ 'is-invalid': errors['newCustomer.email'] }"
                    />
                    <div v-if="errors['newCustomer.email']" class="error-text">{{ errors['newCustomer.email'] }}</div>
                  </div>
                  <div class="form-group">
                    <label for="customer_phone">Téléphone *</label>
                    <input
                      id="customer_phone"
                      type="tel"
                      v-model="form.newCustomer.phone"
                      class="form-control"
                      :class="{ 'is-invalid': errors['newCustomer.phone'] }"
                    />
                    <div v-if="errors['newCustomer.phone']" class="error-text">{{ errors['newCustomer.phone'] }}</div>
                  </div>
                </div>
                <div class="form-group">
                  <label for="customer_address">Adresse</label>
                  <textarea
                    id="customer_address"
                    v-model="form.newCustomer.address"
                    class="form-control"
                    rows="2"
                    placeholder="Adresse complète du client..."
                  ></textarea>
                </div>
              </div>
            </div>
          </div>

          <!-- Section Produit -->
          <div class="form-section">
            <div class="section-header">
              <h3>
                <i class="fas fa-box"></i>
                Produit/Service
              </h3>
              <p class="section-description">Sélectionnez le produit ou service à réserver</p>
            </div>

            <div class="form-group">
              <label for="product_search">Rechercher un produit</label>
              <div class="search-product">
                <input
                  id="product_search"
                  type="text"
                  v-model="productSearch"
                  @input="searchProducts"
                  placeholder="Tapez le nom d'un produit..."
                  class="form-control"
                />
                <div v-if="productResults.length > 0" class="product-results">
                  <div
                    v-for="product in productResults"
                    :key="product.id"
                    @click="selectProduct(product)"
                    class="product-result-item"
                  >
                    <div class="product-info">
                      <strong>{{ product.name }}</strong>
                      <span class="product-meta">{{ product.formatted_price }} • {{ product.category?.name || 'Sans catégorie' }}</span>
                    </div>
                  </div>
                </div>
              </div>
              <div v-if="form.product_id && selectedProduct" class="selected-product">
                <div class="product-card">
                  <i class="fas fa-check-circle"></i>
                  <div>
                    <strong>{{ selectedProduct.name }}</strong>
                    <div class="product-details">
                      {{ selectedProduct.formatted_price }} • {{ selectedProduct.category?.name || 'Sans catégorie' }}
                    </div>
                  </div>
                  <button type="button" @click="clearProduct" class="btn-clear btn-sm">
                    <i class="fas fa-times"></i>
                  </button>
                </div>
              </div>
              <div v-if="errors.product_id" class="error-text">{{ errors.product_id }}</div>
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
                <input
                  id="checkin"
                  type="date"
                  v-model="form.checkin"
                  class="form-control"
                  :class="{ 'is-invalid': errors.checkin }"
                  :min="minDate"
                />
                <div v-if="errors.checkin" class="error-text">{{ errors.checkin }}</div>
              </div>
              <div class="form-group">
                <label for="checkout">Date de départ *</label>
                <input
                  id="checkout"
                  type="date"
                  v-model="form.checkout"
                  class="form-control"
                  :class="{ 'is-invalid': errors.checkout }"
                  :min="form.checkin || minDate"
                />
                <div v-if="errors.checkout" class="error-text">{{ errors.checkout }}</div>
              </div>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label for="guests">Nombre d'invités *</label>
                <input
                  id="guests"
                  type="number"
                  v-model.number="form.guests"
                  class="form-control"
                  :class="{ 'is-invalid': errors.guests }"
                  min="1"
                  max="20"
                />
                <div v-if="errors.guests" class="error-text">{{ errors.guests }}</div>
              </div>
              <div class="form-group">
                <label for="amount">Montant (€) *</label>
                <input
                  id="amount"
                  type="number"
                  v-model.number="form.amount"
                  class="form-control"
                  :class="{ 'is-invalid': errors.amount }"
                  min="0"
                  step="0.01"
                />
                <div v-if="errors.amount" class="error-text">{{ errors.amount }}</div>
              </div>
            </div>

            <div class="form-group">
              <label for="special_requests">Demandes spéciales</label>
              <textarea
                id="special_requests"
                v-model="form.special_requests"
                class="form-control"
                rows="3"
                placeholder="Allergies, préférences, demandes particulières..."
              ></textarea>
            </div>
          </div>

          <!-- Section Statut et Paiement -->
          <div class="form-section">
            <div class="section-header">
              <h3>
                <i class="fas fa-cog"></i>
                Statut et Paiement
              </h3>
              <p class="section-description">Définissez le statut de la réservation et du paiement</p>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label for="status">Statut de la réservation *</label>
                <select
                  id="status"
                  v-model="form.status"
                  class="form-control"
                  :class="{ 'is-invalid': errors.status }"
                >
                  <option value="pending">En attente</option>
                  <option value="confirmed">Confirmée</option>
                  <option value="cancelled">Annulée</option>
                  <option value="completed">Terminée</option>
                </select>
                <div v-if="errors.status" class="error-text">{{ errors.status }}</div>
              </div>
              <div class="form-group">
                <label for="payment_status">Statut du paiement</label>
                <select
                  id="payment_status"
                  v-model="form.payment_status"
                  class="form-control"
                >
                  <option value="pending">En attente</option>
                  <option value="paid">Payé</option>
                  <option value="partial">Partiellement payé</option>
                  <option value="refunded">Remboursé</option>
                </select>
              </div>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label for="booking_source">Source de réservation</label>
                <select
                  id="booking_source"
                  v-model="form.booking_source"
                  class="form-control"
                >
                  <option value="website">Site web</option>
                  <option value="phone">Téléphone</option>
                  <option value="email">Email</option>
                  <option value="booking.com">Booking.com</option>
                  <option value="airbnb">Airbnb</option>
                  <option value="other">Autre</option>
                </select>
              </div>
              <div class="form-group">
                <label for="invoice_number">Numéro de facture</label>
                <input
                  id="invoice_number"
                  type="text"
                  v-model="form.invoice_number"
                  class="form-control"
                  placeholder="Ex: INV-2024-001"
                />
              </div>
            </div>
          </div>

          <!-- Notes internes -->
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
              <textarea
                id="internal_notes"
                v-model="form.internal_notes"
                class="form-control"
                rows="3"
                placeholder="Notes pour l'équipe, instructions particulières..."
              ></textarea>
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
import { debounce } from '@/shared/utils/helpers'

export default {
  name: 'ReservationForm',
  
  props: {
    action: { type: String, required: true } // 'create' | 'edit'
  },

  data() {
    return {
      loading: false,
      saving: false,
      error: null,
      reservation: null,
      
      // Mode de sélection client
      customerMode: 'existing', // 'existing' | 'new'
      
      // Recherche clients
      customerSearch: '',
      customerResults: [],
      selectedCustomer: null,
      
      // Recherche produits
      productSearch: '',
      productResults: [],
      selectedProduct: null,

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
        product_id: null,
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
      },

      errors: {}
    }
  },

  computed: {
    isEditing() {
      return this.action === 'edit'
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
      // Validation du client
      const hasValidCustomer = this.customerMode === 'existing' 
        ? this.form.customer_id 
        : this.form.newCustomer.name && this.form.newCustomer.last_name && this.form.newCustomer.email && this.form.newCustomer.phone

      return hasValidCustomer &&
        this.form.product_id &&
        this.form.checkin &&
        this.form.checkout &&
        this.form.guests > 0 &&
        this.form.amount >= 0 &&
        this.form.status &&
        Object.keys(this.errors).length === 0
    },

    minDate() {
      return new Date().toISOString().split('T')[0]
    }
  },

  async created() {
    await this.initializeComponent()
    
    // Debounce pour les recherches
    this.debouncedSearchCustomers = debounce(this.performCustomerSearch, 300)
    this.debouncedSearchProducts = debounce(this.performProductSearch, 300)
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
        const response = await AdminApi.get(`/api/reservations/${this.reservationId}`)
        this.reservation = response.data
        this.populateForm()
      } catch (error) {
        throw new Error('Impossible de charger la réservation')
      }
    },

    populateForm() {
      if (!this.reservation) return

      // Données de base
      this.form.customer_id = this.reservation.customer_id
      this.form.product_id = this.reservation.product_id
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

      // Client et produit sélectionnés
      if (this.reservation.customer) {
        this.selectedCustomer = this.reservation.customer
        this.customerMode = 'existing'
      }
      if (this.reservation.product) {
        this.selectedProduct = this.reservation.product
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
        const response = await AdminApi.get('/api/customers', {
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

    // Recherche produits
    searchProducts() {
      if (this.productSearch.length >= 2) {
        this.debouncedSearchProducts()
      } else {
        this.productResults = []
      }
    },

    async performProductSearch() {
      try {
        const response = await AdminApi.get('/api/products', {
          params: {
            search: this.productSearch,
            limit: 10
          }
        })
        this.productResults = response.data.data || response.data
      } catch (error) {
        console.error('Erreur recherche produits:', error)
        this.productResults = []
      }
    },

    selectProduct(product) {
      this.selectedProduct = product
      this.form.product_id = product.id
      this.form.amount = parseFloat(product.price) || 0
      this.productSearch = ''
      this.productResults = []
    },

    clearProduct() {
      this.selectedProduct = null
      this.form.product_id = null
      this.form.amount = 0
      this.productSearch = ''
    },

    // Validation
    validateForm() {
      this.errors = {}

      // Validation du client
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

      // Validation du produit
      if (!this.form.product_id) {
        this.errors.product_id = 'Veuillez sélectionner un produit'
      }

      // Validation des dates
      if (!this.form.checkin) {
        this.errors.checkin = 'La date d\'arrivée est obligatoire'
      }
      if (!this.form.checkout) {
        this.errors.checkout = 'La date de départ est obligatoire'
      }
      if (this.form.checkin && this.form.checkout && this.form.checkin >= this.form.checkout) {
        this.errors.checkout = 'La date de départ doit être après l\'arrivée'
      }

      // Validation des invités
      if (!this.form.guests || this.form.guests < 1) {
        this.errors.guests = 'Le nombre d\'invités doit être d\'au moins 1'
      }

      // Validation du montant
      if (this.form.amount < 0) {
        this.errors.amount = 'Le montant doit être positif'
      }

      // Validation du statut
      if (!this.form.status) {
        this.errors.status = 'Le statut est obligatoire'
      }

      return Object.keys(this.errors).length === 0
    },

    // Soumission
    async submitForm() {
      if (!this.validateForm()) {
        this.error = 'Veuillez corriger les erreurs dans le formulaire'
        return
      }

      this.saving = true
      this.error = null

      try {
        const payload = this.buildPayload()
        
        let response
        if (this.isEditing) {
          response = await AdminApi.put(`/api/reservations/${this.reservationId}`, payload)
        } else {
          response = await AdminApi.post('/api/reservations', payload)
        }

        // Redirection
        const reservationId = response.data.id || this.reservationId
        this.$router.push({ 
          name: 'ReservationDetail', 
          params: { id: reservationId } 
        })

      } catch (error) {
        console.error('Erreur lors de la sauvegarde:', error)
        this.error = error.response?.data?.message || 'Erreur lors de la sauvegarde'
      } finally {
        this.saving = false
      }
    },

    buildPayload() {
      const payload = {
        product_id: this.form.product_id,
        checkin: this.form.checkin,
        checkout: this.form.checkout,
        guests: this.form.guests,
        amount: this.form.amount,
        status: this.form.status,
        payment_status: this.form.payment_status,
        booking_source: this.form.booking_source,
        special_requests: this.form.special_requests,
        internal_notes: this.form.internal_notes,
        invoice_number: this.form.invoice_number
      }

      // Client
      if (this.customerMode === 'existing') {
        payload.customer_id = this.form.customer_id
      } else {
        payload.customer = this.form.newCustomer
      }

      return payload
    },

    resetForm() {
      if (confirm('Réinitialiser le formulaire ?')) {
        this.form = {
          customer_id: null,
          newCustomer: {
            name: '',
            last_name: '',
            email: '',
            phone: '',
            address: ''
          },
          product_id: null,
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
        this.selectedCustomer = null
        this.selectedProduct = null
        this.customerMode = 'existing'
        this.errors = {}
      }
    }
  }
}
</script>

<style scoped>
.reservation-form-container {
  display: flex;
  flex-direction: column;
  gap: 24px;
  padding: 24px;
  margin: 0 auto;
  width: 80%;
}

.reservation-badge {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  background: #2563eb;
  color: white;
  padding: 6px 12px;
  border-radius: 20px;
  font-size: 12px;
  font-weight: 600;
  margin-bottom: 12px;
}

.reservation-form {
  background: white;
  border-radius: 16px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  overflow: hidden;
}

.form-content {
  padding: 32px;
  display: flex;
  flex-direction: column;
  gap: 32px;
}

.form-section {
  border-bottom: 1px solid #e5e7eb;
  padding-bottom: 32px;
}

.form-section:last-child {
  border-bottom: none;
  padding-bottom: 0;
}

.section-header {
  margin-bottom: 24px;
}

.section-header h3 {
  display: flex;
  align-items: center;
  gap: 12px;
  font-size: 18px;
  font-weight: 600;
  color: #111827;
  margin: 0 0 8px 0;
}

.section-header h3 i {
  color: #2563eb;
}

.section-description {
  color: #6b7280;
  font-size: 14px;
  margin: 0;
}

.form-group label {
  font-weight: 500;
  color: #374151;
  font-size: 14px;
}

.form-control:focus {
  outline: none;
  border-color: #2563eb;
  box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

/* Radio buttons */
.radio-group {
  display: flex;
  gap: 20px;
}

.radio-option {
  display: flex;
  align-items: center;
  gap: 8px;
  cursor: pointer;
  font-size: 14px;
}

.radio-option input[type="radio"] {
  margin: 0;
}

/* Recherche clients/produits */
.search-customer,
.search-product {
  position: relative;
}

.customer-results,
.product-results {
  position: absolute;
  top: 100%;
  left: 0;
  right: 0;
  background: white;
  border: 1px solid #d1d5db;
  border-top: none;
  border-radius: 0 0 8px 8px;
  max-height: 200px;
  overflow-y: auto;
  z-index: 10;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.customer-result-item,
.product-result-item {
  padding: 12px 16px;
  cursor: pointer;
  border-bottom: 1px solid #f3f4f6;
  transition: background-color 0.2s;
}

.customer-result-item:hover,
.product-result-item:hover {
  background: #f9fafb;
}

.customer-result-item:last-child,
.product-result-item:last-child {
  border-bottom: none;
}

.customer-info,
.product-info {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.customer-meta,
.product-meta {
  font-size: 12px;
  color: #6b7280;
}

/* Sélections */
.selected-customer,
.selected-product {
  margin-top: 12px;
}

.customer-card,
.product-card {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px 16px;
  background: #f0f9ff;
  border: 1px solid #0ea5e9;
  border-radius: 8px;
}

.customer-card i,
.product-card i {
  color: #0ea5e9;
}

.customer-details,
.product-details {
  font-size: 12px;
  color: #6b7280;
}
/* Actions */
.loading-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 16px;
  padding: 48px;
  text-align: center;
}

.spinner {
  width: 32px;
  height: 32px;
  border: 3px solid #f3f4f6;
  border-top: 3px solid #2563eb;
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

</style>