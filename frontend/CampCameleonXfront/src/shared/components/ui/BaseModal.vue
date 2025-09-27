<template>
  <teleport to="body">
    <transition name="modal" appear>
      <div v-if="modelValue" class="modal-overlay" @click="handleOverlayClick">
        <div class="modal-content" :class="sizeClass" @click.stop>
          <!-- Header -->
          <div v-if="title || $slots.header || closable" class="modal-header">
            <div class="modal-title">
              <slot name="header">
                <h3 v-if="title">{{ title }}</h3>
              </slot>
            </div>
            <button 
              v-if="closable" 
              @click="close" 
              class="btn-close"
              aria-label="Fermer"
            >
              &times;
            </button>
          </div>
          
          <!-- Body -->
          <div class="modal-body">
            <slot></slot>
          </div>
          
          <!-- Footer -->
          <div v-if="$slots.footer" class="modal-footer">
            <slot name="footer"></slot>
          </div>
        </div>
      </div>
    </transition>
  </teleport>
</template>

<script>
export default {
  name: 'BaseModal',
  emits: ['update:modelValue', 'close'],
  
  props: {
    modelValue: {
      type: Boolean,
      default: false
    },
    title: String,
    size: {
      type: String,
      default: 'md',
      validator: v => ['sm', 'md', 'lg', 'xl'].includes(v)
    },
    closable: {
      type: Boolean,
      default: true
    },
    closeOnOverlay: {
      type: Boolean,
      default: true
    }
  },
  
  computed: {
    sizeClass() {
      return `modal-${this.size}`
    }
  },
  
  methods: {
    close() {
      this.$emit('update:modelValue', false)
      this.$emit('close')
    },
    
    handleOverlayClick() {
      if (this.closeOnOverlay) {
        this.close()
      }
    }
  },
  
  mounted() {
    // Empêcher le scroll du body quand la modal est ouverte
    if (this.modelValue) {
      document.body.style.overflow = 'hidden'
    }
  },
  
  beforeUnmount() {
    // Restaurer le scroll
    document.body.style.overflow = ''
  },
  
  watch: {
    modelValue(newVal) {
      if (newVal) {
        document.body.style.overflow = 'hidden'
      } else {
        document.body.style.overflow = ''
      }
    }
  }
}
</script>

<style scoped>
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 9999;
  padding: 1rem;
}

.modal-content {
  background: white;
  border-radius: var(--border-radius-lg);
  width: 100%;
  max-height: 90vh;
  overflow-y: auto;
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
}

.modal-sm {
  max-width: 400px;
}

.modal-md {
  max-width: 600px;
}

.modal-lg {
  max-width: 800px;
}

.modal-xl {
  max-width: 1200px;
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1.5rem;
  border-bottom: 1px solid var(--border-color);
}

.modal-title h3 {
  margin: 0;
  font-size: 1.25rem;
  font-weight: 600;
  color: var(--text-primary);
}

.btn-close {
  background: none;
  border: none;
  font-size: 1.5rem;
  cursor: pointer;
  opacity: 0.7;
  transition: opacity 0.2s ease;
  padding: 0;
  width: 2rem;
  height: 2rem;
  display: flex;
  align-items: center;
  justify-content: center;
}

.btn-close:hover {
  opacity: 1;
}

.modal-body {
  padding: 1.5rem;
}

.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 1rem;
  padding: 1.5rem;
  border-top: 1px solid var(--border-color);
  background: var(--bg-light);
}

/* Transitions */
.modal-enter-active, .modal-leave-active {
  transition: all 0.3s ease;
}

.modal-enter-from, .modal-leave-to {
  opacity: 0;
}

.modal-enter-from .modal-content,
.modal-leave-to .modal-content {
  transform: scale(0.9) translateY(-20px);
}

@media (max-width: 768px) {
  .modal-overlay {
    padding: 0;
  }
  
  .modal-content {
    border-radius: 0;
    max-height: 100vh;
  }
}
</style>