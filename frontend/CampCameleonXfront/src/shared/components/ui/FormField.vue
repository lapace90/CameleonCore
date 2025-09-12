<!-- src/shared/components/ui/BaseInput.vue -->
<template>
  <div class="input-wrapper">
    <input
      :id="fieldId"
      :type="type"
      :value="modelValue"
      :placeholder="placeholder"
      :disabled="disabled"
      :required="required"
      :readonly="readonly"
      :class="inputClasses"
      v-bind="$attrs"
      @input="$emit('update:modelValue', $event.target.value)"
      @blur="$emit('blur', $event)"
      @focus="$emit('focus', $event)"
      @keydown="$emit('keydown', $event)"
    />
    
    <!-- Icône à droite (optionnelle) -->
    <div v-if="icon || $slots.icon" class="input-icon">
      <slot name="icon">
        <i v-if="icon" :class="icon"></i>
      </slot>
    </div>
    
    <!-- Addon à droite (ex: €, %, etc.) -->
    <div v-if="addon || $slots.addon" class="input-addon">
      <slot name="addon">
        {{ addon }}
      </slot>
    </div>
  </div>
</template>

<script>
export default {
  name: 'BaseInput',
  inheritAttrs: false,
  emits: ['update:modelValue', 'blur', 'focus', 'keydown'],
  
  props: {
    modelValue: [String, Number],
    type: {
      type: String,
      default: 'text'
    },
    placeholder: String,
    disabled: Boolean,
    required: Boolean,
    readonly: Boolean,
    error: Boolean,
    size: {
      type: String,
      default: 'md',
      validator: v => ['sm', 'md', 'lg'].includes(v)
    },
    icon: String, // Icône fontawesome
    addon: String, // Texte addon (€, %, etc.)
    fieldId: String
  },
  
  computed: {
    inputClasses() {
      return [
        'form-input',
        `form-input-${this.size}`,
        {
          'form-input-error': this.error,
          'form-input-disabled': this.disabled,
          'form-input-readonly': this.readonly,
          'has-icon': this.icon || this.$slots.icon,
          'has-addon': this.addon || this.$slots.addon
        }
      ]
    }
  }
}
</script>

<style scoped>
.input-wrapper {
  position: relative;
  display: flex;
  align-items: center;
}

.form-input {
  width: 100%;
  padding: 0.5rem 0.75rem;
  font-size: 0.875rem;
  font-weight: 400;
  line-height: 1.5;
  color: var(--text-primary);
  background-color: #fff;
  background-clip: padding-box;
  border: 1px solid #dee2e6;
  border-radius: 0.375rem;
  transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
}

.form-input:focus {
  color: var(--text-primary);
  background-color: #fff;
  border-color: var(--primary);
  outline: 0;
  box-shadow: 0 0 0 0.2rem rgba(var(--primary-rgb), 0.25);
}

.form-input::placeholder {
  color: #6c757d;
  opacity: 1;
}

.form-input:disabled,
.form-input-disabled {
  background-color: #e9ecef;
  opacity: 1;
  cursor: not-allowed;
}

.form-input:readonly,
.form-input-readonly {
  background-color: #f8f9fa;
}

.form-input.form-input-error {
  border-color: var(--danger);
  box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
}

/* Tailles */
.form-input-sm {
  padding: 0.375rem 0.5rem;
  font-size: 0.75rem;
}

.form-input-lg {
  padding: 0.75rem 1rem;
  font-size: 1rem;
}

/* Avec icône */
.form-input.has-icon {
  padding-right: 2.5rem;
}

.input-icon {
  position: absolute;
  right: 0.75rem;
  color: #6c757d;
  pointer-events: none;
}

/* Avec addon */
.form-input.has-addon {
  border-top-right-radius: 0;
  border-bottom-right-radius: 0;
  border-right: 0;
}

.input-addon {
  display: flex;
  align-items: center;
  padding: 0.5rem 0.75rem;
  font-size: 0.875rem;
  font-weight: 400;
  line-height: 1.5;
  color: #495057;
  text-align: center;
  white-space: nowrap;
  background-color: #e9ecef;
  border: 1px solid #dee2e6;
  border-left: 0;
  border-radius: 0 0.375rem 0.375rem 0;
}

/* Focus sur input avec addon */
.input-wrapper:focus-within .input-addon {
  border-color: var(--primary);
}
</style>