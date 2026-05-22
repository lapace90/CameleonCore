<!-- frontend/CampCameleonXfront/src/shared/components/ui/DraftNotification.vue -->
<template>
  <transition name="slide-down">
    <div v-if="show" class="draft-notification" :class="variant">
      <div class="draft-notification-content">
        <div class="draft-icon">
          <AppIcon :name="iconClass" />
        </div>
        
        <div class="draft-message">
          <div class="draft-title">{{ title }}</div>
          <div v-if="draftInfo" class="draft-info">
            Sauvegardé {{ formatAge(draftInfo.age) }}
            <span v-if="draftInfo.fieldsCount"> • {{ draftInfo.fieldsCount }} champs</span>
          </div>
        </div>
      </div>

      <div class="draft-actions">
        <button
          v-if="showRestoreButton"
          @click="$emit('restore')"
          class="btn-draft btn-restore"
          type="button"
        >
          <AppIcon name="undo-2" />
          Restaurer
        </button>
        
        <button
          v-if="showDiscardButton"
          @click="$emit('discard')"
          class="btn-draft btn-discard"
          type="button"
        >
          <AppIcon name="trash-2" />
          Ignorer
        </button>

        <button
          @click="$emit('close')"
          class="btn-draft btn-close"
          type="button"
          aria-label="Fermer"
        >
          <AppIcon name="x" />
        </button>
      </div>
    </div>
  </transition>
</template>

<script>
export default {
  name: 'DraftNotification',
  
  emits: ['restore', 'discard', 'close'],
  
  props: {
    show: {
      type: Boolean,
      default: false
    },
    variant: {
      type: String,
      default: 'info', // 'info', 'success', 'warning'
      validator: (value) => ['info', 'success', 'warning'].includes(value)
    },
    title: {
      type: String,
      default: 'Brouillon disponible'
    },
    draftInfo: {
      type: Object,
      default: null
    },
    showRestoreButton: {
      type: Boolean,
      default: true
    },
    showDiscardButton: {
      type: Boolean,
      default: true
    },
    autoClose: {
      type: Boolean,
      default: false
    },
    autoCloseDelay: {
      type: Number,
      default: 10000 // 10 secondes
    }
  },

  computed: {
    iconClass() {
      const icons = {
        info: 'info',
        success: 'circle-check',
        warning: 'triangle-alert'
      }
      return icons[this.variant]
    }
  },

  watch: {
    show(newValue) {
      if (newValue && this.autoClose) {
        this.startAutoClose()
      }
    }
  },

  methods: {
    formatAge(milliseconds) {
      const seconds = Math.floor(milliseconds / 1000)
      const minutes = Math.floor(seconds / 60)
      const hours = Math.floor(minutes / 60)
      const days = Math.floor(hours / 24)

      if (days > 0) return `il y a ${days} jour${days > 1 ? 's' : ''}`
      if (hours > 0) return `il y a ${hours} heure${hours > 1 ? 's' : ''}`
      if (minutes > 0) return `il y a ${minutes} minute${minutes > 1 ? 's' : ''}`
      return 'à l\'instant'
    },

    startAutoClose() {
      if (this.autoCloseTimeout) {
        clearTimeout(this.autoCloseTimeout)
      }
      
      this.autoCloseTimeout = setTimeout(() => {
        this.$emit('close')
      }, this.autoCloseDelay)
    }
  },

  beforeUnmount() {
    if (this.autoCloseTimeout) {
      clearTimeout(this.autoCloseTimeout)
    }
  }
}
</script>

<style scoped>
.draft-notification {
  position: fixed;
  top: 80px;
  right: 20px;
  max-width: 500px;
  background: white;
  border-radius: 12px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 16px 20px;
  z-index: 1000;
  border-left: 4px solid;
  animation: slideIn 0.3s ease-out;
}

@keyframes slideIn {
  from {
    transform: translateX(400px);
    opacity: 0;
  }
  to {
    transform: translateX(0);
    opacity: 1;
  }
}

.draft-notification.info {
  border-left-color: #3b82f6;
}

.draft-notification.success {
  border-left-color: #10b981;
}

.draft-notification.warning {
  border-left-color: #f59e0b;
}

.draft-notification-content {
  display: flex;
  align-items: center;
  gap: 12px;
  flex: 1;
}

.draft-icon {
  flex-shrink: 0;
  width: 40px;
  height: 40px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 20px;
}

.info .draft-icon {
  background: #dbeafe;
  color: #3b82f6;
}

.success .draft-icon {
  background: #d1fae5;
  color: #10b981;
}

.warning .draft-icon {
  background: #fef3c7;
  color: #f59e0b;
}

.draft-message {
  flex: 1;
}

.draft-title {
  font-weight: 600;
  font-size: 14px;
  color: #111827;
  margin-bottom: 2px;
}

.draft-info {
  font-size: 12px;
  color: #6b7280;
}

.draft-actions {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-left: 12px;
}

.btn-draft {
  padding: 6px 12px;
  border-radius: 6px;
  font-size: 13px;
  font-weight: 500;
  border: none;
  cursor: pointer;
  transition: all 0.2s;
  display: flex;
  align-items: center;
  gap: 6px;
}

.btn-restore {
  background: #3b82f6;
  color: white;
}

.btn-restore:hover {
  background: #2563eb;
}

.btn-discard {
  background: #f3f4f6;
  color: #6b7280;
}

.btn-discard:hover {
  background: #e5e7eb;
  color: #374151;
}

.btn-close {
  background: transparent;
  color: #9ca3af;
  padding: 4px 8px;
}

.btn-close:hover {
  background: #f3f4f6;
  color: #374151;
}

/* Transitions */
.slide-down-enter-active,
.slide-down-leave-active {
  transition: all 0.3s ease;
}

.slide-down-enter-from {
  transform: translateY(-100%);
  opacity: 0;
}

.slide-down-leave-to {
  transform: translateX(400px);
  opacity: 0;
}

/* Responsive */
@media (max-width: 768px) {
  .draft-notification {
    top: 60px;
    right: 10px;
    left: 10px;
    max-width: none;
  }

  .draft-actions {
    flex-direction: column;
    gap: 4px;
  }

  .btn-draft {
    font-size: 12px;
    padding: 4px 8px;
  }
}
</style>