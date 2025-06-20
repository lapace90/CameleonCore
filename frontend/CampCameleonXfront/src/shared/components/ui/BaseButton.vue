<template>
  <button 
    :type="type" 
    :disabled="disabled || loading"
    :class="buttonClasses"
    @click="$emit('click', $event)"
  >
    <i v-if="loading" class="fas fa-spinner fa-spin"></i>
    <i v-else-if="icon" :class="icon"></i>
    <span v-if="$slots.default"><slot /></span>
  </button>
</template>

<script>
export default {
  name: 'BaseButton',
  emits: ['click'],
  props: {
    variant: {
      type: String,
      default: 'primary',
      validator: value => ['primary', 'secondary', 'outline', 'danger', 'success'].includes(value)
    },
    size: {
      type: String,
      default: 'md',
      validator: value => ['sm', 'md', 'lg'].includes(value)
    },
    type: {
      type: String,
      default: 'button'
    },
    disabled: {
      type: Boolean,
      default: false
    },
    loading: {
      type: Boolean,
      default: false
    },
    icon: {
      type: String,
      default: ''
    },
    block: {
      type: Boolean,
      default: false
    }
  },
  computed: {
    buttonClasses() {
      return [
        'btn',
        `btn-${this.variant}`,
        `btn-${this.size}`,
        {
          'btn-block': this.block,
          'btn-loading': this.loading
        }
      ]
    }
  }
}
</script>
<!-- 
<style scoped>
.btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  font-weight: 600;
  text-align: center;
  text-decoration: none;
  border: 1px solid transparent;
  border-radius: 0.375rem;
  cursor: pointer;
  transition: all 0.15s ease;
  white-space: nowrap;
  user-select: none;
}

.btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
  transform: none !important;
}

.btn-sm {
  padding: 0.5rem 1rem;
  font-size: 0.875rem;
}

.btn-md {
  padding: 0.625rem 1.25rem;
  font-size: 1rem;
}

.btn-lg {
  padding: 1rem 2rem;
  font-size: 1.125rem;
}

.btn-block {
  width: 100%;
}

.btn-primary {
  background: linear-gradient(87deg, #5e72e4 0, #825ee4 100%);
  border-color: #5e72e4;
  color: white;
}

.btn-primary:hover:not(:disabled) {
  transform: translateY(-1px);
  box-shadow: 0 4px 6px rgba(94, 114, 228, 0.3);
}

.btn-secondary {
  background-color: #8392ab;
  border-color: #8392ab;
  color: white;
}

.btn-outline {
  background-color: transparent;
  border-color: #5e72e4;
  color: #5e72e4;
}

.btn-outline:hover:not(:disabled) {
  background-color: #5e72e4;
  color: white;
  transform: translateY(-1px);
}

.btn-danger {
  background-color: #f5365c;
  border-color: #f5365c;
  color: white;
}

.btn-success {
  background-color: #2dce89;
  border-color: #2dce89;
  color: white;
}
</style> -->