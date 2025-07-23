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
            <div 
              v-for="type in eventTypes" 
              :key="type.value"
              @click="formData.type = type.value"
              class="type-option"
              :class="{ active: formData.type === type.value }"
            >
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
          <input 
            v-model="formData.title"
            type="text" 
            class="form-input"
            placeholder="Ex: Réservation - Famille Martin"
            required
          >
        </div>

        <!-- Dates -->
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">
              Date de début <span class="required">*</span>
            </label>
            <input 
              v-model="formData.start"
              type="datetime-local" 
              class="form-input"
              required
            >
          </div>
          <div class="form-group">
            <label class="form-label">Date de fin</label>
            <input 
              v-model="formData.end"
              type="datetime-local" 
              class="form-input"
            >
          </div>
        </div>

        <!-- Champs spécifiques selon le type -->
        <div v-if="formData.type === 'reservation'" class="form-section">
          <h4 class="section-title">Détails de la réservation</h4>
          
          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Nom du client</label>
              <input 
                v-model="formData.customerName"
                type="text" 
                class="form-input"
                placeholder="Nom de famille"
              >
            </div>
            <div class="form-group">
              <label class="form-label">Téléphone</label>
              <input 
                v-model="formData.phone"
                type="tel" 
                class="form-input"
                placeholder="06 12 34 56 78"
              >
            </div>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Nombre de personnes</label>
              <input 
                v-model.number="formData.guests"
                type="number" 
                class="form-input"
                min="1"
                placeholder="4"
              >
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

        <div v-if="formData.type === 'animation'" class="form-section">
          <h4 class="section-title">Détails de l'animation</h4>
          
          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Lieu</label>
              <input 
                v-model="formData.location"
                type="text" 
                class="form-input"
                placeholder="Salle principale, Piscine..."
              >
            </div>
            <div class="form-group">
              <label class="form-label">Capacité max</label>
              <input 
                v-model.number="formData.capacity"
                type="number" 
                class="form-input"
                min="1"
                placeholder="50"
              >
            </div>
          </div>

          <div class="form-group">
            <label class="form-label">Animateur</label>
            <input 
              v-model="formData.animator"
              type="text" 
              class="form-input"
              placeholder="Nom de l'animateur"
            >
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
              <input 
                v-model="formData.technician"
                type="text" 
                class="form-input"
                placeholder="Nom du technicien"
              >
            </div>
          </div>
        </div>

        <!-- Notes -->
        <div class="form-group">
          <label class="form-label">Notes / Description</label>
          <textarea 
            v-model="formData.notes"
            class="form-textarea"
            rows="3"
            placeholder="Informations complémentaires..."
          ></textarea>
        </div>

        <!-- Couleur personnalisée -->
        <div class="form-group">
          <label class="form-label">Couleur</label>
          <div class="color-picker">
            <div 
              v-for="color in availableColors" 
              :key="color.value"
              @click="formData.backgroundColor = color.value"
              class="color-option"
              :class="{ active: formData.backgroundColor === color.value }"
              :style="{ backgroundColor: color.value }"
              :title="color.name"
            ></div>
          </div>
        </div>
      </form>

      <div class="modal-footer">
        <button @click="$emit('close')" type="button" class="btn btn-secondary">
          <i class="fas fa-times"></i>
          Annuler
        </button>
        
        <div class="action-buttons">
          <button 
            v-if="isEditing" 
            @click="handleDelete"
            type="button" 
            class="btn btn-danger"
          >
            <i class="fas fa-trash"></i>
            Supprimer
          </button>
          
          <button @click="handleSubmit" type="button" class="btn btn-primary">
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
        type: 'reservation',
        backgroundColor: '#28a745',
        notes: '',
        // Réservation
        customerName: '',
        phone: '',
        guests: 4,
        pitchType: '',
        // Animation
        location: '',
        capacity: null,
        animator: '',
        // Maintenance
        priority: 'medium',
        technician: ''
      },
      
      eventTypes: [
        {
          value: 'reservation',
          label: 'Réservation',
          icon: 'fas fa-bed',
          color: '#28a745'
        },
        {
          value: 'animation',
          label: 'Animation',
          icon: 'fas fa-music',
          color: '#ffc107'
        },
        {
          value: 'maintenance',
          label: 'Maintenance',
          icon: 'fas fa-tools',
          color: '#dc3545'
        },
        {
          value: 'formation',
          label: 'Formation',
          icon: 'fas fa-graduation-cap',
          color: '#6f42c1'
        },
        {
          value: 'other',
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
        // Focus sur le premier champ
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
      // Changer la couleur par défaut selon le type
      const eventType = this.eventTypes.find(t => t.value === newType)
      if (eventType && !this.isEditing) {
        this.formData.backgroundColor = eventType.color
      }
    }
  },
  
  methods: {
    loadEventData() {
      if (this.isEditing && this.event) {
        this.formData = { ...this.event }
      } else {
        this.resetForm()
      }
    },
    
    resetForm() {
      this.formData = {
        id: null,
        title: '',
        start: this.event.start || new Date().toISOString().slice(0, 16),
        end: this.event.end || '',
        type: 'reservation',
        backgroundColor: '#28a745',
        notes: '',
        customerName: '',
        phone: '',
        guests: 4,
        pitchType: '',
        location: '',
        capacity: null,
        animator: '',
        priority: 'medium',
        technician: ''
      }
    },
    
    handleSubmit() {
      if (!this.validateForm()) return
      
      const eventData = {
        ...this.formData,
        start: this.formData.start,
        end: this.formData.end || null
      }
      
      this.$emit('save', eventData)
    },
    
    handleDelete() {
      this.$emit('delete', this.formData)
    },
    
    handleOverlayClick() {
      this.$emit('close')
    },
    
    validateForm() {
      if (!this.formData.title.trim()) {
        this.showError('Le titre est obligatoire')
        return false
      }
      
      if (!this.formData.start) {
        this.showError('La date de début est obligatoire')
        return false
      }
      
      if (this.formData.end && new Date(this.formData.end) <= new Date(this.formData.start)) {
        this.showError('La date de fin doit être après la date de début')
        return false
      }
      
      return true
    },
    
    showError(message) {
      // Intégrer avec votre système de notifications
      console.error(message)
      // this.$toast.error(message)
    }
  }
}
</script>

<!-- <style scoped>
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  backdrop-filter: blur(4px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1050;
  padding: 1rem;
}

.modal-container {
  background: white;
  border-radius: 12px;
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
  width: 100%;
  max-width: 600px;
  max-height: 90vh;
  overflow: hidden;
  display: flex;
  flex-direction: column;
}

.modal-header {
  padding: 1.5rem 2rem;
  border-bottom: 1px solid #e5e7eb;
  display: flex;
  justify-content: space-between;
  align-items: center;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
}

.modal-title {
  margin: 0;
  font-size: 1.25rem;
  font-weight: 600;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.modal-close {
  background: none;
  border: none;
  color: white;
  font-size: 1.2rem;
  cursor: pointer;
  padding: 0.5rem;
  border-radius: 50%;
  transition: all 0.2s ease;
}

.modal-close:hover {
  background: rgba(255, 255, 255, 0.1);
}

.modal-body {
  padding: 2rem;
  overflow-y: auto;
  flex: 1;
}

.form-group {
  margin-bottom: 1.5rem;
}

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
}

.form-label {
  display: block;
  margin-bottom: 0.5rem;
  font-weight: 500;
  color: #374151;
}

.required {
  color: #dc2626;
}

.form-input,
.form-select,
.form-textarea {
  width: 100%;
  padding: 0.75rem;
  border: 1px solid #d1d5db;
  border-radius: 8px;
  font-size: 0.875rem;
  transition: all 0.2s ease;
  background: white;
}

.form-input:focus,
.form-select:focus,
.form-textarea:focus {
  outline: none;
  border-color: #667eea;
  box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.form-textarea {
  resize: vertical;
  min-height: 80px;
}

/* Event Type Selector */
.event-type-selector {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(100px, 1fr));
  gap: 0.75rem;
  margin-top: 0.5rem;
}

.type-option {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.5rem;
  padding: 1rem;
  border: 2px solid #e5e7eb;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.2s ease;
  text-align: center;
}

.type-option:hover {
  border-color: #667eea;
  background: #f8faff;
}

.type-option.active {
  border-color: #667eea;
  background: #667eea;
  color: white;
}

.type-icon {
  width: 40px;
  height: 40px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 1.1rem;
}

.type-option.active .type-icon {
  background: rgba(255, 255, 255, 0.2) !important;
}

.type-label {
  font-size: 0.875rem;
  font-weight: 500;
}

/* Color Picker */
.color-picker {
  display: flex;
  gap: 0.5rem;
  flex-wrap: wrap;
  margin-top: 0.5rem;
}

.color-option {
  width: 32px;
  height: 32px;
  border-radius: 6px;
  cursor: pointer;
  border: 2px solid transparent;
  transition: all 0.2s ease;
}

.color-option:hover {
  transform: scale(1.1);
}

.color-option.active {
  border-color: #374151;
  transform: scale(1.15);
}

/* Form Sections */
.form-section {
  margin: 2rem 0;
  padding: 1.5rem;
  background: #f9fafb;
  border-radius: 8px;
  border: 1px solid #e5e7eb;
}

.section-title {
  margin: 0 0 1rem 0;
  font-size: 1.1rem;
  font-weight: 600;
  color: #374151;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.section-title::before {
  content: '';
  width: 4px;
  height: 20px;
  background: #667eea;
  border-radius: 2px;
}

/* Modal Footer */
.modal-footer {
  padding: 1.5rem 2rem;
  border-top: 1px solid #e5e7eb;
  display: flex;
  justify-content: space-between;
  align-items: center;
  background: #f9fafb;
}

.action-buttons {
  display: flex;
  gap: 0.75rem;
}

.btn {
  padding: 0.75rem 1.5rem;
  border: none;
  border-radius: 8px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s ease;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.875rem;
}

.btn-primary {
  background: #667eea;
  color: white;
}

.btn-primary:hover {
  background: #5a67d8;
  transform: translateY(-1px);
}

.btn-secondary {
  background: #6b7280;
  color: white;
}

.btn-secondary:hover {
  background: #4b5563;
}

.btn-danger {
  background: #dc2626;
  color: white;
}

.btn-danger:hover {
  background: #b91c1c;
}

/* Responsive */
@media (max-width: 768px) {
  .modal-container {
    margin: 1rem;
    max-height: calc(100vh - 2rem);
  }

  .modal-header,
  .modal-body,
  .modal-footer {
    padding: 1rem;
  }

  .form-row {
    grid-template-columns: 1fr;
  }

  .event-type-selector {
    grid-template-columns: repeat(2, 1fr);
  }

  .modal-footer {
    flex-direction: column;
    gap: 1rem;
  }

  .action-buttons {
    width: 100%;
    justify-content: center;
  }
}
</style> -->