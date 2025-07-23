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
  z-index: 1060;
  padding: 1rem;
}

.confirm-modal {
  background: white;
  border-radius: 12px;
  box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
  width: 100%;
  max-width: 480px;
  padding: 2rem;
  text-align: center;
  transform: scale(0.95);
  opacity: 0;
  animation: modalEnter 0.15s ease-out forwards;
}

@keyframes modalEnter {
  to {
    transform: scale(1);
    opacity: 1;
  }
}

.modal-icon {
  width: 64px;
  height: 64px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 1.5rem;
  font-size: 1.75rem;
}

.modal-icon-info {
  background: rgba(59, 130, 246, 0.1);
  color: #3b82f6;
}

.modal-icon-warning {
  background: rgba(245, 158, 11, 0.1);
  color: #f59e0b;
}

.modal-icon-danger {
  background: rgba(239, 68, 68, 0.1);
  color: #ef4444;
}

.modal-icon-success {
  background: rgba(34, 197, 94, 0.1);
  color: #22c55e;
}

.modal-content {
  margin-bottom: 2rem;
}

.modal-title {
  font-size: 1.25rem;
  font-weight: 600;
  color: #111827;
  margin: 0 0 0.75rem 0;
}

.modal-message {
  color: #6b7280;
  margin: 0 0 1rem 0;
  line-height: 1.6;
}

.modal-details {
  background: #f9fafb;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  padding: 1rem;
  margin-top: 1rem;
  text-align: left;
}

.detail-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.5rem 0;
  border-bottom: 1px solid #e5e7eb;
}

.detail-item:last-child {
  border-bottom: none;
}

.detail-label {
  font-weight: 500;
  color: #374151;
  font-size: 0.875rem;
}

.detail-value {
  color: #6b7280;
  font-size: 0.875rem;
  text-align: right;
  max-width: 60%;
  word-break: break-word;
}

.modal-actions {
  display: flex;
  gap: 0.75rem;
  justify-content: center;
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
  min-width: 120px;
  justify-content: center;
}

.btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.btn-cancel {
  background: #f3f4f6;
  color: #374151;
  border: 1px solid #d1d5db;
}

.btn-cancel:hover:not(:disabled) {
  background: #e5e7eb;
  border-color: #9ca3af;
}

.btn-info {
  background: #3b82f6;
  color: white;
}

.btn-info:hover:not(:disabled) {
  background: #2563eb;
  transform: translateY(-1px);
}

.btn-warning {
  background: #f59e0b;
  color: white;
}

.btn-warning:hover:not(:disabled) {
  background: #d97706;
  transform: translateY(-1px);
}

.btn-danger {
  background: #ef4444;
  color: white;
}

.btn-danger:hover:not(:disabled) {
  background: #dc2626;
  transform: translateY(-1px);
}

.btn-success {
  background: #22c55e;
  color: white;
}

.btn-success:hover:not(:disabled) {
  background: #16a34a;
  transform: translateY(-1px);
}

/* Loading state */
.btn .fa-spinner {
  animation: spin 1s linear infinite;
}

@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

/* Responsive */
@media (max-width: 640px) {
  .confirm-modal {
    padding: 1.5rem;
    margin: 1rem;
  }
  
  .modal-actions {
    flex-direction: column;
  }
  
  .btn {
    width: 100%;
  }
  
  .detail-item {
    flex-direction: column;
    align-items: flex-start;
    gap: 0.25rem;
  }
  
  .detail-value {
    text-align: left;
    max-width: 100%;
  }
}
</style> -->