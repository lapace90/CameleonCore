<template>
  <div class="modal-overlay" v-if="show" @click="handleOverlayClick">
    <div class="modal-container large-modal" @click.stop>
      <div class="modal-header">
        <h3 class="modal-title">
          <i :class="modalIcon"></i>
          {{ isEditing ? 'Modifier' : 'Créer' }}
        </h3>
        <button @click="confirmClose" class="modal-close">
          <i class="fas fa-times"></i>
        </button>
      </div>

      <form @submit.prevent="handleSubmit" class="modal-body expanded-content">
        <!-- Type d'événement -->
        <div class="form-group">
          <label class="form-label">Type d'événement</label>
          <div class="event-type-selector">
            <div v-for="type in eventTypes" :key="type.value" @click="formData.type = type.value" class="type-option"
              :class="{ active: formData.type === type.value }">
              <div class="type-icon" :style="{ backgroundColor: type.color }">
                <i :class="type.icon"></i>
              </div>
              <span class="type-label">{{ type.label }}</span>
            </div>
          </div>
        </div>

        <!-- Titre -->
        <div class="form-group">
          <label class="form-label">
            Titre <span class="required">*</span>
          </label>
          <input v-model="formData.title" type="text" class="form-input" placeholder="Ex: Réservation - Famille Martin"
            required>
        </div>

        <!-- Dates -->
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">
              Date de début <span class="required">*</span>
            </label>
            <input v-model="formData.start" type="datetime-local" class="form-input" required>
          </div>
          <div class="form-group">
            <label class="form-label">Date de fin</label>
            <input v-model="formData.end" type="datetime-local" class="form-input">
          </div>
        </div>

        <!-- SECTION RÉSERVATION ÉTENDUE -->
        <div v-if="formData.type === 'reservation'" class="form-section">
          <h4 class="section-title">Informations client</h4>

          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Prénom <span class="required">*</span></label>
              <input v-model="formData.customerName" type="text" class="form-input" placeholder="Prénom" required>
            </div>
            <div class="form-group">
              <label class="form-label">Nom de famille <span class="required">*</span></label>
              <input v-model="formData.customerLastName" type="text" class="form-input" placeholder="Nom de famille"
                required>
            </div>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Email <span class="required">*</span></label>
              <input v-model="formData.customerEmail" type="email" class="form-input" placeholder="client@example.com"
                required>
            </div>
            <div class="form-group">
              <label class="form-label">Téléphone <span class="required">*</span></label>
              <input v-model="formData.phone" type="tel" class="form-input" placeholder="+212 6 12 34 56 78" required>
            </div>
          </div>

          <h4 class="section-title">Adresse (optionnel)</h4>

          <div class="form-group">
            <label class="form-label">Adresse</label>
            <input v-model="formData.customerAddress" type="text" class="form-input" placeholder="Rue, avenue...">
          </div>

          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Ville</label>
              <input v-model="formData.customerCity" type="text" class="form-input"
                placeholder="Marrakech, Casablanca...">
            </div>
            <div class="form-group">
              <label class="form-label">Code postal</label>
              <input v-model="formData.customerPostalCode" type="text" class="form-input" placeholder="40000">
            </div>
          </div>

          <div class="form-group">
            <label class="form-label">Pays</label>
            <select v-model="formData.customerCountry" class="form-select">
              <option value="">Sélectionner un pays</option>
              <option value="MA">Maroc</option>
              <option value="FR">France</option>
              <option value="ES">Espagne</option>
              <option value="DE">Allemagne</option>
              <option value="IT">Italie</option>
              <option value="US">États-Unis</option>
              <option value="GB">Royaume-Uni</option>
              <option value="other">Autre</option>
            </select>
          </div>

          <h4 class="section-title">Détails du séjour</h4>

          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Adultes <span class="required">*</span></label>
              <input v-model.number="formData.numberOfAdults" type="number" class="form-input" min="1" max="12"
                required>
            </div>
            <div class="form-group">
              <label class="form-label">Enfants</label>
              <input v-model.number="formData.numberOfChildren" type="number" class="form-input" min="0" max="8">
            </div>
          </div>

          <div class="form-section">
            <h4 class="section-title">Hébergements et services</h4>

            <div class="form-group">
              <label class="form-label">Hébergements <span class="required">*</span></label>
              <div class="services-list">
                <div v-for="room in accommodations" :key="room.id" class="service-item">
                  <label class="checkbox-wrapper">
                    <input type="checkbox" :value="room.id" :checked="selectedAccommodations.includes(room.id)"
                      @change="toggleAccommodation(room.id)">
                    <span class="checkmark"></span>
                    <div class="service-info">
                      <span class="service-name">{{ room.name }}</span>
                      <span class="service-price">{{ room.formatted_price || (room.price + '€') }}</span>
                      <span class="service-meta" v-if="room.capacity">Capacité: {{ room.capacity }} pers.</span>
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
            </div>

            <div class="form-group">
              <label class="form-label">Activités</label>
              <div class="services-list">
                <div v-for="activity in availableActivities" :key="activity.id" class="service-item">
                  <label class="checkbox-wrapper">
                    <input type="checkbox" :value="activity.id" :checked="selectedActivities.includes(activity.id)"
                      @change="toggleActivity(activity.id)">
                    <span class="checkmark"></span>
                    <div class="service-info">
                      <span class="service-name">{{ activity.name }}</span>
                      <span class="service-price">{{ activity.formatted_price || (activity.price + '€') }}</span>
                    </div>
                  </label>
                  <div v-if="selectedActivities.includes(activity.id)" class="quantity-control">
                    <label>Qté:</label>
                    <input type="number" min="1" :max="formData.numberOfAdults + formData.numberOfChildren"
                      :value="getActivityQuantity(activity.id)"
                      @input="setActivityQuantity(activity.id, $event.target.value)" class="qty-input">
                  </div>
                </div>
              </div>
            </div>

            <div class="form-group">
              <label class="form-label">Menus</label>
              <div class="services-list">
                <div v-for="menu in availableMenus" :key="menu.id" class="service-item">
                  <label class="checkbox-wrapper">
                    <input type="checkbox" :value="menu.id" :checked="selectedMenus.includes(menu.id)"
                      @change="toggleMenu(menu.id)">
                    <span class="checkmark"></span>
                    <div class="service-info">
                      <span class="service-name">{{ menu.name }}</span>
                      <span class="service-price">{{ menu.formatted_price || (menu.price + '€') }}</span>
                    </div>
                  </label>
                  <div v-if="selectedMenus.includes(menu.id)" class="quantity-control">
                    <label>Qté:</label>
                    <input type="number" min="1" :max="formData.numberOfAdults + formData.numberOfChildren"
                      :value="getMenuQuantity(menu.id)" @input="setMenuQuantity(menu.id, $event.target.value)"
                      class="qty-input">
                  </div>
                </div>
              </div>
            </div>

            <div class="form-group">
              <label class="form-label">Commentaires / Demandes spéciales</label>
              <textarea v-model="formData.comment" class="form-textarea" rows="3"
                placeholder="Allergies, préférences, demandes particulières..."></textarea>
            </div>
          </div>

          <div class="form-section">
            <h4 class="section-title">Réservation et paiement</h4>
            <div class="form-row">
              <div class="form-group">
                <label class="form-label">Source de réservation</label>
                <select v-model="formData.bookingSource" class="form-select">
                  <option value="direct">Réservation directe</option>
                  <option value="website">Site web</option>
                  <option value="phone">Téléphone</option>
                  <option value="email">Email</option>
                  <option value="booking.com">Booking.com</option>
                  <option value="airbnb">Airbnb</option>
                  <option value="agent">Agent local</option>
                  <option value="walk-in">Sur place</option>
                </select>
              </div>
              <div class="form-group">
                <label class="form-label">Statut de la réservation</label>
                <select v-model="formData.status" class="form-select">
                  <option value="pending">En attente</option>
                  <option value="confirmed">Confirmée</option>
                  <option value="cancelled">Annulée</option>
                  <option value="completed">Terminée</option>
                </select>
              </div>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label class="form-label">Statut du paiement</label>
                <select v-model="formData.paymentStatus" class="form-select">
                  <option value="pending">En attente</option>
                  <option value="partial">Acompte versé</option>
                  <option value="paid">Payé intégralement</option>
                  <option value="failed">Échec de paiement</option>
                  <option value="refunded">Remboursé</option>
                </select>
              </div>
              <div class="form-group">
                <label class="form-label">Mode de paiement</label>
                <select v-model="formData.paymentMethod" class="form-select">
                  <option value="">Sélectionner</option>
                  <option value="cash">Espèces</option>
                  <option value="card">Carte bancaire</option>
                  <option value="transfer">Virement</option>
                  <option value="paypal">PayPal</option>
                  <option value="stripe">Stripe</option>
                </select>
              </div>
            </div>

            <div class="form-group">
              <label class="form-label">Montant total (€)</label>
              <input v-model.number="formData.amount" type="number" class="form-input" min="0" step="0.01"
                placeholder="0.00">
            </div>
          </div>
        </div>

        <!-- SECTION AUTRES ÉVÉNEMENTS -->
        <div v-if="formData.type === 'activite'" class="form-section">
          <h4 class="section-title">Détails de l'activité</h4>

          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Lieu</label>
              <input v-model="formData.location" type="text" class="form-input"
                placeholder="Dunes de Merzouga, Oasis...">
            </div>
            <div class="form-group">
              <label class="form-label">Capacité max</label>
              <input v-model.number="formData.capacity" type="number" class="form-input" min="1" placeholder="12">
            </div>
          </div>

          <div class="form-group">
            <label class="form-label">Guide/Responsable</label>
            <input v-model="formData.responsible" type="text" class="form-input" placeholder="Nom du guide">
          </div>
        </div>

        <div v-if="formData.type === 'maintenance'" class="form-section">
          <h4 class="section-title">Détails de la maintenance</h4>

          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Priorité</label>
              <select v-model="formData.priority" class="form-select">
                <option value="low">Faible</option>
                <option value="medium">Moyenne</option>
                <option value="high">Élevée</option>
                <option value="urgent">Urgente</option>
              </select>
            </div>
            <div class="form-group">
              <label class="form-label">Technicien</label>
              <input v-model="formData.responsible" type="text" class="form-input" placeholder="Nom du technicien">
            </div>
          </div>
        </div>

        <div v-if="formData.type === 'autre'" class="form-section">
          <h4 class="section-title">Détails de l'événement</h4>

          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Lieu</label>
              <input v-model="formData.location" type="text" class="form-input" placeholder="Lieu de l'événement">
            </div>
            <div class="form-group">
              <label class="form-label">Responsable</label>
              <input v-model="formData.responsible" type="text" class="form-input" placeholder="Nom du responsable">
            </div>
          </div>
        </div>

        <!-- Couleur et notes générales -->
        <div class="form-section">
          <h4 class="section-title">Apparence et notes</h4>

          <div class="form-group">
            <label class="form-label">Couleur</label>
            <div class="color-picker">
              <div v-for="color in availableColors" :key="color.value" @click="formData.backgroundColor = color.value"
                class="color-option" :class="{ active: formData.backgroundColor === color.value }"
                :style="{ backgroundColor: color.value }" :title="color.name">
              </div>
            </div>
          </div>

          <div class="form-group">
            <label class="form-label">Notes internes</label>
            <textarea v-model="formData.notes" class="form-textarea" rows="3"
              placeholder="Notes visibles uniquement par l'équipe..."></textarea>
          </div>
        </div>

        <div class="form-actions">
          <button type="button" @click="confirmClose" class="btn btn-secondary btn-sm">
            Annuler
          </button>
          <button type="submit" class="btn btn-primary btn-sm" :disabled="isSubmitting">
            <i class="fas fa-save"></i>
            {{ isSubmitting ? 'Sauvegarde...' : isEditing ? 'Modifier' : 'Créer' }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script>
import ProductsApi from '@/services/ProductsApi'
import { watchEffect } from 'vue'
import { computeQuoteTotal } from '@/shared/composables/useQuotePricing'

export default {
  name: 'EventModal',
  props: {
    show: {
      type: Boolean,
      default: false
    },
    event: {
      type: Object,
      default: () => ({})
    },
    isEditing: {
      type: Boolean,
      default: false
    }
  },
  data() {
    return {
      formData: {
        id: null,
        title: '',
        start: '',
        end: '',
        type: 'autre',
        backgroundColor: '#17a2b8',
        notes: '',
        // Client
        customerName: '',
        customerLastName: '',
        customerEmail: '',
        phone: '',
        customerAddress: '',
        customerCity: '',
        customerPostalCode: '',
        customerCountry: '',
        // Séjour
        numberOfAdults: 2,
        numberOfChildren: 0,
        accommodationId: null, // Gardé pour compatibilité
        // Réservation
        bookingSource: 'direct',
        status: 'pending',
        paymentStatus: 'pending',
        paymentMethod: null,
        amount: 0,
        comment: '',
        // Événements génériques
        location: '',
        capacity: null,
        responsible: '',
        priority: 'medium'
      },

      accommodations: [],
      availableActivities: [],
      availableMenus: [],
      selectedAccommodations: [],
      selectedActivities: [],
      selectedMenus: [],
      accommodationQuantities: {},
      activityQuantities: {},
      menuQuantities: {},
      loadingAccommodations: false,
      isSubmitting: false,

      eventTypes: [
        {
          value: 'reservation',
          label: 'Réservation Client',
          icon: 'fas fa-bed',
          color: '#28a745'
        },
        {
          value: 'activite',
          label: 'Activité Programmée',
          icon: 'fas fa-hiking',
          color: '#ffc107'
        },
        {
          value: 'maintenance',
          label: 'Maintenance',
          icon: 'fas fa-tools',
          color: '#dc3545'
        },
        {
          value: 'autre',
          label: 'Autre',
          icon: 'fas fa-calendar',
          color: '#17a2b8'
        }
      ],

      availableColors: [
        { name: 'Vert', value: '#28a745' },
        { name: 'Bleu', value: '#007bff' },
        { name: 'Orange', value: '#fd7e14' },
        { name: 'Rouge', value: '#dc3545' },
        { name: 'Violet', value: '#6f42c1' },
        { name: 'Cyan', value: '#17a2b8' },
        { name: 'Jaune', value: '#ffc107' },
        { name: 'Rose', value: '#e83e8c' }
      ]
    }
  },

  computed: {
    modalIcon() {
      const type = this.eventTypes.find(t => t.value === this.formData.type)
      return type ? type.icon : 'fas fa-calendar'
    },

    totalGuests() {
      return (this.formData.numberOfAdults || 0) + (this.formData.numberOfChildren || 0)
    },

    // Détecte si le formulaire a été modifié
    hasFormChanges() {
      // Pour les réservations
      if (this.formData.type === 'reservation') {
        return !!(
          this.formData.title ||
          this.formData.customerName ||
          this.formData.customerLastName ||
          this.formData.customerEmail ||
          this.formData.phone ||
          this.selectedAccommodations.length > 0 ||
          this.selectedActivities.length > 0 ||
          this.selectedMenus.length > 0 ||
          this.formData.comment ||
          this.formData.amount > 0
        )
      }

      // Pour les autres événements
      return !!(
        this.formData.title ||
        this.formData.location ||
        this.formData.responsible ||
        this.formData.notes
      )
    }
  },

  watch: {
    show(newVal) {
      if (newVal) {
        this.loadAccommodations()
        this.loadActivitiesAndMenus()
        this.resetForm()
        if (this.event && Object.keys(this.event).length > 0) {
          this.populateForm()
        }
        // Ajouter listener pour Escape
        document.addEventListener('keydown', this.handleEscape)
      } else {
        // Retirer listener quand modal fermée
        document.removeEventListener('keydown', this.handleEscape)
      }
    },

    'formData.type'(newType) {
      if (newType === 'reservation') {
        this.loadAccommodations()
        this.loadActivitiesAndMenus()
      }
    }
  },

  beforeUnmount() {
    document.removeEventListener('keydown', this.handleEscape)
  },
  mounted() {
    watchEffect(() => {
      if (this.formData.type !== 'reservation') return

      // 🛡️ Évite d'écraser le montant existant en édition
      const hasAnySelection =
        this.selectedAccommodations.length > 0 ||
        this.selectedActivities.length > 0 ||
        this.selectedMenus.length > 0

      if (!hasAnySelection) return

      const selected = {
        room: this._expandSelectedRooms(),
        activity: [...this.selectedActivities],
        menu: [...this.selectedMenus]
      }

      const catalog = {
        rooms: this.accommodations.map(p => ({ id: p.id, name: p.name, price: Number(p.price || 0) })),
        activities: this.availableActivities.map(p => ({ id: p.id, name: p.name, price: Number(p.price || 0) })),
        menus: this.availableMenus.map(p => ({ id: p.id, name: p.name, price: Number(p.price || 0) }))
      }

      const dates = {
        checkin: this.dateOnly(this.formData.start),
        checkout: this.dateOnly(this.formData.end),
        guests: (this.formData.numberOfAdults || 0) + (this.formData.numberOfChildren || 0) || 1
      }

      const overrides = {
        activity: { ...this.activityQuantities },
        menu: { ...this.menuQuantities }
      }

      const { total } = computeQuoteTotal({ selected, catalog, dates, overrides })
      // 👉 garde le type "string" attendu par l'API
      this.formData.amount = Number(total || 0).toFixed(2)
    })
  },

  methods: {
    // Gestion de la touche Escape
    handleEscape(e) {
      if (e.key === 'Escape') {
        this.confirmClose()
      }
    },
    // Attendu par computeNights: 'YYYY-MM-DD'
    dateOnly(isoOrDateStr) {
      if (!isoOrDateStr) return ''
      const d = new Date(isoOrDateStr)
      if (Number.isNaN(d.getTime())) return String(isoOrDateStr).slice(0, 10)
      const y = d.getFullYear()
      const m = String(d.getMonth() + 1).padStart(2, '0')
      const day = String(d.getDate()).padStart(2, '0')
      return `${y}-${m}-${day}`
    },

    // Le composable n'a PAS d'override pour 'room' → on duplique l'ID selon la quantité choisie
    _expandSelectedRooms() {
      const rooms = []
      for (const id of this.selectedAccommodations) {
        const q = Math.max(1, parseInt(this.accommodationQuantities[id] || 1))
        for (let i = 0; i < q; i++) rooms.push(id)
      }
      return rooms
    },

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
      } catch (error) {
        console.error('Erreur chargement hébergements:', error)
      } finally {
        this.loadingAccommodations = false
      }
    },

    async loadActivitiesAndMenus() {
      if (this.availableActivities.length > 0 && this.availableMenus.length > 0) return

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
        console.error('Erreur chargement activités/menus:', error)
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

    populateForm() {
      this.formData = {
        ...this.formData,
        ...this.event,
        // Mapping des données existantes
        customerName: this.event.extendedProps?.customer_name || '',
        customerEmail: this.event.extendedProps?.customer_email || '',
        phone: this.event.extendedProps?.customer_phone || '',
        numberOfAdults: this.event.extendedProps?.number_of_adults || 2,
        numberOfChildren: this.event.extendedProps?.number_of_children || 0,
        accommodationId: this.event.extendedProps?.product_id || null,
        amount: this.event.extendedProps?.amount || 0,
        comment: this.event.extendedProps?.comment || ''
      }
    },

    resetForm() {
      this.formData = {
        id: null,
        title: '',
        start: '',
        end: '',
        type: 'autre',
        backgroundColor: '#17a2b8',
        notes: '',
        customerName: '',
        customerLastName: '',
        customerEmail: '',
        phone: '',
        customerAddress: '',
        customerCity: '',
        customerPostalCode: '',
        customerCountry: '',
        numberOfAdults: 2,
        numberOfChildren: 0,
        accommodationId: null, // Gardé pour compatibilité
        bookingSource: 'direct',
        status: 'pending',
        paymentStatus: 'pending',
        paymentMethod: null,
        amount: 0,
        comment: '',
        location: '',
        capacity: null,
        responsible: '',
        priority: 'medium'
      }

      // Réinitialiser les services
      this.selectedAccommodations = []
      this.selectedActivities = []
      this.selectedMenus = []
      this.accommodationQuantities = {}
      this.activityQuantities = {}
      this.menuQuantities = {}
    },

    handleOverlayClick(e) {
      if (e.target === e.currentTarget) {
        this.confirmClose()
      }
    },

    // Demande confirmation avant fermeture si formulaire modifié
    confirmClose() {
      if (this.hasFormChanges) {
        if (confirm('Vous avez des modifications non sauvegardées. Voulez-vous vraiment fermer sans sauvegarder ?')) {
          this.forceClose()
        }
      } else {
        this.forceClose()
      }
    },

    // Ferme sans demander (pour les sauvegardes et annulations confirmées)
    forceClose() {
      this.$emit('close')
    },

    async handleSubmit() {
      this.isSubmitting = true;
      try {
        // Le backend gère automatiquement la création du customer

        this.formData.amount = (Number(this.formData.amount) || 0).toFixed(2);
        const eventData = this.prepareEventData(this.formData);

        // Pas besoin de customer_id, le backend le gère dans prepareEventData
        this.$emit('save', eventData);
        this.forceClose();
      } catch (error) {
        console.error('Erreur:', error);
      } finally {
        this.isSubmitting = false;
      }
    },

    prepareEventData(formData) {
      if (formData.type === 'reservation') {
        // Préparer tous les produits sélectionnés
        const selectedProducts = []

        // Ajouter les hébergements avec quantités
        this.selectedAccommodations.forEach(accommodationId => {
          const quantity = this.getAccommodationQuantity(accommodationId)
          for (let i = 0; i < quantity; i++) {
            selectedProducts.push(accommodationId)
          }
        })

        // Ajouter les activités avec quantités
        this.selectedActivities.forEach(activityId => {
          const quantity = this.getActivityQuantity(activityId)
          for (let i = 0; i < quantity; i++) {
            selectedProducts.push(activityId)
          }
        })

        // Ajouter les menus avec quantités
        this.selectedMenus.forEach(menuId => {
          const quantity = this.getMenuQuantity(menuId)
          for (let i = 0; i < quantity; i++) {
            selectedProducts.push(menuId)
          }
        })

        return {
          type: 'reservation', // Important pour FullCalendar
          title: formData.title,
          start: formData.start,
          end: formData.end,

          // ✅ DONNÉES CUSTOMER (format QuoteRequestProcessor)
          customerEmail: formData.customerEmail,
          customerName: formData.customerName,
          customerLastName: formData.customerLastName,
          phone: formData.phone,
          customerAddress: formData.customerAddress,
          customerCity: formData.customerCity,
          customerPostalCode: formData.customerPostalCode,
          customerCountry: formData.customerCountry,

          // Données du séjour
          numberOfAdults: formData.numberOfAdults,
          numberOfChildren: formData.numberOfChildren,
          amount: (Number(formData.amount) || 0).toFixed(2),
          comment: formData.comment,

          // Produits sélectionnés - format liste simple pour QuoteRequestProcessor
          selected_product_ids: selectedProducts,

          // Métadonnées réservation
          bookingSource: formData.bookingSource,
          paymentStatus: formData.paymentStatus,
          paymentMethod: formData.paymentMethod,
          status: formData.status
        }
      }

      // Événements génériques
      return {
        ...formData,
        start_date: formData.start ? new Date(formData.start).toISOString() : null,
        end_date: formData.end ? new Date(formData.end).toISOString() : null,
        background_color: formData.backgroundColor,
        type: formData.type || 'autre'
      }
    }
  }
}
</script>

<style scoped>
.large-modal {
  max-width: 1200px;
  width: 95vw;
  max-height: 90vh;
}

.expanded-content {
  max-height: 75vh;
  overflow-y: auto;
}

.services-list {
  border: 1px solid #ddd;
  border-radius: 6px;
  max-height: 200px;
  overflow-y: auto;
  background: white;
}

.service-item {
  padding: 0.75rem;
  border-bottom: 1px solid #eee;
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.service-item:last-child {
  border-bottom: none;
}

.checkbox-wrapper {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  flex: 1;
  cursor: pointer;
}

.checkbox-wrapper input[type="checkbox"] {
  margin: 0;
}

.service-info {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.service-name {
  font-weight: 600;
  color: #333;
}

.service-price {
  font-size: 0.875rem;
  color: #666;
}

.service-meta {
  font-size: 0.8rem;
  color: #888;
  font-style: italic;
}

.loading-item,
.empty-item {
  padding: 1rem;
  text-align: center;
  color: #666;
  font-style: italic;
}

.quantity-control {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.875rem;
}

.qty-input {
  width: 60px;
  padding: 0.25rem;
  border: 1px solid #ddd;
  border-radius: 4px;
  text-align: center;
}
</style>