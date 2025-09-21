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
              <label class="form-label">Type d'emplacement</label>
              <select v-model="formData.pitchType" class="form-select">
                <option value="">Sélectionner</option>
                <option value="standard">Standard</option>
                <option value="premium">Premium</option>
                <option value="mobil-home">Mobil-home</option>
                <option value="chalet">Chalet</option>
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
              <input v-model="formData.location" type="text" class="form-input"
                placeholder="Lieu de l'événement">
            </div>
            <div class="form-group">
              <label class="form-label">Responsable</label>
              <input v-model="formData.responsible" type="text" class="form-input" placeholder="Nom du responsable">
            </div>
          </div>
        </div>

        <!-- Notes -->
        <div class="form-group">
          <label class="form-label">Notes / Description</label>
          <textarea v-model="formData.notes" class="form-textarea" rows="3"
            placeholder="Informations complémentaires..."></textarea>
        </div>

        <!-- Couleur personnalisée -->
        <div class="form-group">
          <label class="form-label">Couleur</label>
          <div class="color-picker">
            <div v-for="color in availableColors" :key="color.value" @click="formData.backgroundColor = color.value"
              class="color-option" :class="{ active: formData.backgroundColor === color.value }"
              :style="{ backgroundColor: color.value }" :title="color.name"></div>
          </div>
        </div>
      </form>

      <div class="modal-footer">
        <button @click="$emit('close')" type="button" class="btn btn-secondary btn-sm">
          <i class="fas fa-times"></i>
          Annuler
        </button>

        <div class="action-buttons">
          <button v-if="isEditing" @click="handleDelete" type="button" class="btn btn-danger">
            <i class="fas fa-trash"></i>
            Supprimer
          </button>

          <button @click="handleSubmit" type="button" class="btn btn-primary btn-sm">
            <i class="fas fa-save"></i>
            {{ isEditing ? 'Modifier' : 'Créer' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
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
        pitchType: '',
        // Événements génériques
        location: '',
        capacity: null,
        responsible: '',
        priority: 'medium'
      },

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
        this.loadEventData()
        this.$nextTick(() => {
          const firstInput = this.$el.querySelector('.form-input')
          if (firstInput) firstInput.focus()
        })
      }
    },

    event: {
      handler() {
        if (this.show) {
          this.loadEventData()
        }
      },
      deep: true
    },

    'formData.type'(newType) {
      const eventType = this.eventTypes.find(t => t.value === newType)
      if (eventType && !this.isEditing) {
        this.formData.backgroundColor = eventType.color
      }
    }
  },

  methods: {
    loadEventData() {
      console.log('🔄 Chargement données événement:', {
        isEditing: this.isEditing,
        event: this.event
      })

      if (this.isEditing && this.event && Object.keys(this.event).length > 0) {
        this.formData = {
          id: this.event.id || null,
          title: this.event.title || '',
          start: this.formatDateTimeLocal(this.event.start),
          end: this.formatDateTimeLocal(this.event.end),
          type: this.event.type || 'autre',
          backgroundColor: this.event.backgroundColor || '#17a2b8',

          // Données de réservation
          customerName: this.event.customerName || '',
          phone: this.event.phone || '',
          guests: this.event.guests || 2,
          pitchType: this.event.pitchType || '',

          // Données événements génériques
          location: this.event.location || '',
          capacity: this.event.capacity || null,
          responsible: this.event.responsible || '',
          priority: this.event.priority || 'medium',
          notes: this.event.notes || this.event.comment || ''
        }

        console.log('✅ Données chargées pour édition:', this.formData)
      } else {
        this.resetForm()

        if (this.event?.start) {
          this.formData.start = this.formatDateTimeLocal(this.event.start)
        }
        if (this.event?.end) {
          this.formData.end = this.formatDateTimeLocal(this.event.end)
        }

        console.log('✅ Formulaire initialisé pour création:', this.formData)
      }
    },

    formatDateTimeLocal(date) {
      if (!date) return ''

      try {
        let dateObj
        if (date instanceof Date) {
          dateObj = date
        } else if (typeof date === 'string') {
          dateObj = new Date(date)
        } else {
          return ''
        }

        if (isNaN(dateObj.getTime())) {
          return ''
        }

        return dateObj.toISOString().slice(0, 16)
      } catch (error) {
        console.warn('⚠️ Erreur formatage date:', error)
        return ''
      }
    },

    resetForm() {
      const now = new Date()
      const oneHourLater = new Date(now.getTime() + 60 * 60 * 1000)

      this.formData = {
        id: null,
        title: '',
        start: this.formatDateTimeLocal(now),
        end: this.formatDateTimeLocal(oneHourLater),
        type: 'autre',
        backgroundColor: '#17a2b8',
        notes: '',

        // Réservation
        customerName: '',
        phone: '',
        guests: 2,
        pitchType: '',

        // Événements génériques
        location: '',
        capacity: null,
        responsible: '',
        priority: 'medium'
      }
    },

    handleSubmit() {
      console.log('📋 Soumission formulaire:', this.formData)

      if (!this.validateForm()) {
        return
      }

      const eventData = {
        ...this.formData,
        start: this.formData.start ? new Date(this.formData.start).toISOString() : null,
        end: this.formData.end ? new Date(this.formData.end).toISOString() : null,
        amount: parseFloat(this.formData.amount || 0).toFixed(2),
        guests: parseInt(this.formData.guests || 1),
        capacity: this.formData.capacity ? parseInt(this.formData.capacity) : null
      }

      console.log('✅ Données formatées pour API:', eventData)
      this.$emit('save', eventData)
    },

    handleDelete() {
      this.$emit('delete', this.formData)
    },

    handleOverlayClick() {
      this.$emit('close')
    },

    validateForm() {
      if (!this.formData.title?.trim()) {
        this.showError('Le titre est obligatoire')
        return false
      }

      if (!this.formData.start) {
        this.showError('La date de début est obligatoire')
        return false
      }

      if (this.formData.end && this.formData.start) {
        const startDate = new Date(this.formData.start)
        const endDate = new Date(this.formData.end)

        if (endDate <= startDate) {
          this.showError('La date de fin doit être après la date de début')
          return false
        }
      }

      // Validations spécifiques selon le type
      if (this.formData.type === 'reservation') {
        if (this.formData.guests < 1) {
          this.showError('Le nombre d\'invités doit être au moins 1')
          return false
        }
      }

      if (this.formData.type === 'maintenance' && !this.formData.responsible?.trim()) {
        this.showError('Le technicien est obligatoire pour une maintenance')
        return false
      }

      if (this.formData.type === 'activite' && !this.formData.responsible?.trim()) {
        this.showError('Le guide est obligatoire pour une activité')
        return false
      }

      console.log('✅ Validation réussie')
      return true
    },

    showError(message) {
      console.error('❌ Erreur validation:', message)
      alert(message)

      this.$nextTick(() => {
        const firstError = this.$el.querySelector('.form-input:invalid, .form-input[required]:not([value])')
        if (firstError) {
          firstError.focus()
        }
      })
    }
  }
}
</script>