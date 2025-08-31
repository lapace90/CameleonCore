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
