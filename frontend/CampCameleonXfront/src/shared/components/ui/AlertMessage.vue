<template>
  <transition name="alert" appear>
    <div v-if="visible" :class="alertClasses">
      <i :class="iconClass"></i>
      <span class="alert-message">{{ message }}</span>
      <button v-if="dismissible" @click="dismiss" class="btn-close">&times;</button>
    </div>
  </transition>
</template>

<script>
export default {
  name: 'AlertMessage',
  emits: ['dismiss'],
  props: {
    message: {
      type: String,
      required: true
    },
    type: {
      type: String,
      default: 'info',
      validator: v => ['success', 'danger', 'warning', 'info'].includes(v)
    },
    dismissible: {
      type: Boolean,
      default: true
    },
    autoDismiss: {
      type: Number,
      default: 0 // 0 = pas d'auto dismiss
    }
  },
  
  data() {
    return {
      visible: true
    }
  },
  
  computed: {
    alertClasses() {
      return [
        'alert',
        `alert-${this.type}`
      ]
    },
    
    iconClass() {
      const icons = {
        success: 'fas fa-check-circle',
        danger: 'fas fa-exclamation-triangle',
        warning: 'fas fa-exclamation-circle',
        info: 'fas fa-info-circle'
      }
      return icons[this.type]
    }
  },
  
  mounted() {
    if (this.autoDismiss > 0) {
      setTimeout(() => {
        this.dismiss()
      }, this.autoDismiss)
    }
  },
  
  methods: {
    dismiss() {
      this.visible = false
      this.$emit('dismiss')
    }
  }
}
</script>

<style scoped>
.alert {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1rem 1.5rem;
  border-radius: var(--border-radius-lg);
  margin-bottom: 1.5rem;
  position: relative;
}

.alert-success {
  background-color: #f0f9ff;
  border: 1px solid var(--success);
  color: var(--success);
}

.alert-danger {
  background-color: #fef2f2;
  border: 1px solid var(--danger);
  color: var(--danger);
}

.alert-warning {
  background-color: #fffbeb;
  border: 1px solid var(--warning);
  color: var(--warning);
}

.alert-info {
  background-color: #f0f9ff;
  border: 1px solid var(--info);
  color: var(--info);
}

.alert-message {
  flex: 1;
}

.btn-close {
  position: absolute;
  right: 1rem;
  top: 50%;
  transform: translateY(-50%);
  background: none;
  border: none;
  font-size: 1.5rem;
  cursor: pointer;
  opacity: 0.7;
  transition: opacity 0.2s ease;
}

.btn-close:hover {
  opacity: 1;
}

/* Transitions */
.alert-enter-active, .alert-leave-active {
  transition: all 0.3s ease;
}

.alert-enter-from {
  opacity: 0;
  transform: translateY(-20px);
}

.alert-leave-to {
  opacity: 0;
  transform: translateX(100%);
}
</style>