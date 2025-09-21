<template>
  <div class="modal-overlay" v-if="show" @click="handleOverlayClick">
    <div class="modal-container" @click.stop>
      <div class="modal-header">
        <h3 class="modal-title">
          <i :class="modalIcon"></i>
          {{ isEditing ? 'Modifier l\'événement' : 'Nouvel événement' }}
        </h3>
        <button @click="$emit('close')" class="modal-close">
          <i class="fas fa-times"></i>
        </button>
      </div>

      <form @submit.prevent="handleSubmit" class="modal-body">
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

        <!-- Champs spécifiques selon le type -->
        <div v-if="formData.type === 'reservation'" class="form-section">
          <h4 class="section-title">Détails de la réservation</h4>

          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Nom du client</label>
              <input v-model="formData.customerName" type="text" class="form-input" placeholder="Nom de famille">
            </div>
            <div class="form-group">
              <label class="form-label">Téléphone</label>
              <input v-model="formData.phone" type="tel" class="form-input" placeholder="06 12 34 56 78">
            </div>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Nombre de personnes</label>
              <input v-model.number="formData.guests" type="number" class="form-input" min="1" placeholder="4">
            </div>
            <div class="form-group">
              <label class="form-label">Hébergement</label>
              <select v-model="formData.accommodationId" class="form-select" :disabled="loadingAccommodations">
                <option value="">{{ loadingAccommodations ? 'Chargement...' : 'Choisir un hébergement' }}</option>
                <option v-for="room in accommodations" :key="room.id" :value="room.id">
                  {{ room.name }} - {{ room.formatted_price }}
                </option>
              </select>
            </div>
          </div>
        </div>

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

        <!-- Couleur -->
        <div class="form-group">
          <label class="form-label">Couleur</label>
          <div class="color-picker">
            <div v-for="color in availableColors" :key="color.value" @click="formData.backgroundColor = color.value"
              class="color-option" :class="{ active: formData.backgroundColor === color.value }"
              :style="{ backgroundColor: color.value }" :title="color.name">
            </div>
          </div>
        </div>

        <!-- Notes -->
        <div class="form-group">
          <label class="form-label">Notes</label>
          <textarea v-model="formData.notes" class="form-textarea" rows="3"
            placeholder="Informations complémentaires..."></textarea>
        </div>

        <!-- Actions -->
        <div class="form-actions">
          <button type="button" @click="$emit('close')" class="btn btn-secondary">
            Annuler
          </button>
          <button v-if="isEditing" type="button" @click="$emit('delete', event)" class="btn btn-danger">
            <i class="fas fa-trash"></i>
            Supprimer
          </button>
          <button type="submit" class="btn btn-primary" :disabled="isSubmitting">
            <i v-if="isSubmitting" class="fas fa-spinner fa-spin"></i>
            {{ isEditing ? 'Modifier' : 'Créer' }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script>
import ProductsApi from '@/services/ProductsApi'

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
        // Réservation
        customerName: '',
        phone: '',
        guests: 2,
        accommodationId: null,
        // Événements génériques
        location: '',
        capacity: null,
        responsible: '',
        priority: 'medium'
      },

      accommodations: [],
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
    }
  },

  watch: {
    show(newVal) {
      if (newVal) {
        this.loadAccommodations()
        this.resetForm()
        if (this.event && Object.keys(this.event).length > 0) {
          this.populateForm()
        }
      }
    },

    'formData.type'(newType) {
      if (newType === 'reservation') {
        this.loadAccommodations()
      }
    }
  },

  methods: {
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

    populateForm() {
      this.formData = {
        ...this.formData,
        ...this.event,
        customerName: this.event.extendedProps?.customer_name || '',
        phone: this.event.extendedProps?.customer_phone || '',
        guests: this.event.extendedProps?.guests || 2,
        accommodationId: this.event.extendedProps?.product_id || null
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
        phone: '',
        guests: 2,
        accommodationId: null,
        location: '',
        capacity: null,
        responsible: '',
        priority: 'medium'
      }
    },

    handleOverlayClick(e) {
      if (e.target === e.currentTarget) {
        this.$emit('close')
      }
    },

    async handleSubmit() {
      this.isSubmitting = true
      try {
        // Préparer les données selon le type
        const eventData = this.prepareEventData(this.formData)
        this.$emit('save', eventData)
      } catch (error) {
        console.error('Erreur:', error)
      } finally {
        this.isSubmitting = false
      }
    },

    prepareEventData(formData) {
      // Pour les réservations
      if (formData.type === 'reservation') {
        return {
          ...formData,
          // Mapper vers les champs attendus par l'API reservations
          checkin: formData.start ? new Date(formData.start).toISOString() : null,
          checkout: formData.end ? new Date(formData.end).toISOString() : null,
          // On va créer le customer en amont, pas ici
          customer_name: formData.customerName,
          customer_phone: formData.phone,
          customer_email: formData.customerEmail || '',
          product_id: formData.accommodationId,
          number_of_adults: formData.guests || 1,
          amount: "0.00",
          booking_source: 'admin',
          payment_status: 'pending'
        }
      }

      // Pour les événements génériques
      return {
        ...formData,
        // Mapper vers les champs attendus par l'API events
        start_date: formData.start ? new Date(formData.start).toISOString() : null,
        end_date: formData.end ? new Date(formData.end).toISOString() : null,
        background_color: formData.backgroundColor,
        type: formData.type || 'autre'
      }
    }
  }
}
</script>