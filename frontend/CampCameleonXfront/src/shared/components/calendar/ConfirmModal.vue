<template>
  <div class="modal-overlay" v-if="show" @click="handleOverlayClick">
    <div class="confirm-modal" @click.stop>
      <div class="modal-icon" :class="iconClass">
        <i :class="icon"></i>
      </div>
      
      <div class="modal-content">
        <h3 class="modal-title">{{ title }}</h3>
        <p class="modal-message">{{ message }}</p>
        
        <div v-if="details" class="modal-details">
          <div class="detail-item" v-for="(value, key) in details" :key="key">
            <span class="detail-label">{{ formatLabel(key) }} :</span>
            <span class="detail-value">{{ value }}</span>
          </div>
        </div>
      </div>
      
      <div class="modal-actions">
        <button @click="handleCancel" class="btn btn-cancel">
          <i class="fas fa-times"></i>
          {{ cancelText }}
        </button>
        <button @click="handleConfirm" class="btn btn-confirm" :class="confirmClass">
          <i :class="confirmIcon"></i>
          {{ confirmText }}
        </button>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'ConfirmModal',
  props: {
    show: {
      type: Boolean,
      default: false
    },
    title: {
      type: String,
      default: 'Confirmer l\'action'
    },
    message: {
      type: String,
      default: 'Êtes-vous sûr de vouloir continuer ?'
    },
    details: {
      type: Object,
      default: null
    },
    type: {
      type: String,
      default: 'danger',
      validator: value => ['info', 'warning', 'danger', 'success'].includes(value)
    },
    confirmText: {
      type: String,
      default: 'Confirmer'
    },
    cancelText: {
      type: String,
      default: 'Annuler'
    },
    loading: {
      type: Boolean,
      default: false
    }
  },
  
  computed: {
    iconClass() {
      const classes = {
        info: 'modal-icon-info',
        warning: 'modal-icon-warning',
        danger: 'modal-icon-danger',
        success: 'modal-icon-success'
      }
      return classes[this.type] || classes.danger
    },
    
    icon() {
      const icons = {
        info: 'fas fa-info-circle',
        warning: 'fas fa-exclamation-triangle',
        danger: 'fas fa-trash-alt',
        success: 'fas fa-check-circle'
      }
      return icons[this.type] || icons.danger
    },
    
    confirmClass() {
      const classes = {
        info: 'btn-info',
        warning: 'btn-warning',
        danger: 'btn-danger',
        success: 'btn-success'
      }
      return classes[this.type] || classes.danger
    },
    
    confirmIcon() {
      if (this.loading) return 'fas fa-spinner fa-spin'
      
      const icons = {
        info: 'fas fa-check',
        warning: 'fas fa-exclamation',
        danger: 'fas fa-trash',
        success: 'fas fa-check'
      }
      return icons[this.type] || icons.danger
    }
  },
  
  watch: {
    show(newVal) {
      if (newVal) {
        // Focus sur le bouton de confirmation après ouverture
        this.$nextTick(() => {
          const confirmBtn = this.$el.querySelector('.btn-confirm')
          if (confirmBtn) confirmBtn.focus()
        })
        
        // Écouter les touches du clavier
        document.addEventListener('keydown', this.handleKeydown)
      } else {
        // Retirer l'écouteur quand la modal se ferme
        document.removeEventListener('keydown', this.handleKeydown)
      }
    }
  },
  
  beforeUnmount() {
    // Nettoyer l'écouteur si le composant est détruit
    document.removeEventListener('keydown', this.handleKeydown)
  },
  
  methods: {
    handleConfirm() {
      if (this.loading) return
      this.$emit('confirm')
    },
    
    handleCancel() {
      if (this.loading) return
      this.$emit('cancel')
    },
    
    handleOverlayClick() {
      if (this.loading) return
      this.handleCancel()
    },
    
    handleKeydown(event) {
      if (event.key === 'Escape') {
        this.handleCancel()
      } else if (event.key === 'Enter') {
        this.handleConfirm()
      }
    },
    
    formatLabel(key) {
      // Convertit les clés en labels lisibles
      const labels = {
        title: 'Titre',
        start: 'Date de début',
        end: 'Date de fin',
        type: 'Type',
        customerName: 'Client',
        phone: 'Téléphone',
        location: 'Lieu',
        priority: 'Priorité'
      }
      return labels[key] || key.charAt(0).toUpperCase() + key.slice(1)
    }
  }
}
</script>
