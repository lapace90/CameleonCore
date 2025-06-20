<template>
  <div class="input-group" :class="{ 'has-error': error }">
    <label v-if="label" :for="id" class="input-label">
      {{ label }}
      <span v-if="required" class="required">*</span>
    </label>
    
    <div class="input-wrapper">
      <i v-if="icon" :class="icon" class="input-icon"></i>
      <input
        :id="id"
        :type="type"
        :value="modelValue"
        :placeholder="placeholder"
        :disabled="disabled"
        :required="required"
        :class="inputClasses"
        @input="$emit('update:modelValue', $event.target.value)"
        @focus="$emit('focus', $event)"
        @blur="$emit('blur', $event)"
      >
    </div>
    
    <span v-if="error" class="error-message">{{ error }}</span>
    <span v-else-if="hint" class="hint-message">{{ hint }}</span>
  </div>
</template>

<script>
export default {
  name: 'BaseInput',
  emits: ['update:modelValue', 'focus', 'blur'],
  props: {
    id: String,
    modelValue: [String, Number],
    type: {
      type: String,
      default: 'text'
    },
    label: String,
    placeholder: String,
    icon: String,
    error: String,
    hint: String,
    disabled: Boolean,
    required: Boolean,
    size: {
      type: String,
      default: 'md',
      validator: value => ['sm', 'md', 'lg'].includes(value)
    }
  },
  computed: {
    inputClasses() {
      return [
        'base-input',
        `input-${this.size}`,
        {
          'has-icon': this.icon,
          'is-error': this.error
        }
      ]
    }
  }
}
</script>

<style scoped>
.input-group {
  margin-bottom: 1rem;
}

.input-label {
  display: block;
  margin-bottom: 0.5rem;
  font-weight: 600;
  color: #32325d;
  font-size: 0.875rem;
}

.required {
  color: #f5365c;
}

.input-wrapper {
  position: relative;
}

.input-icon {
  position: absolute;
  left: 1rem;
  top: 50%;
  transform: translateY(-50%);
  color: #8898aa;
  z-index: 1;
}

.base-input {
  width: 100%;
  border: 1px solid #dee2e6;
  border-radius: 0.375rem;
  background-color: #fff;
  transition: all 0.15s ease;
  font-size: 1rem;
}

.base-input:focus {
  outline: none;
  border-color: #5e72e4;
  box-shadow: 0 0 0 0.2rem rgba(94, 114, 228, 0.25);
}

.base-input.has-icon {
  padding-left: 3rem;
}

.base-input.is-error {
  border-color: #f5365c;
}

.input-sm {
  padding: 0.5rem 0.75rem;
  font-size: 0.875rem;
}

.input-md {
  padding: 0.75rem 1rem;
}

.input-lg {
  padding: 1rem 1.25rem;
  font-size: 1.125rem;
}

.error-message {
  display: block;
  margin-top: 0.25rem;
  font-size: 0.875rem;
  color: #f5365c;
}

.hint-message {
  display: block;
  margin-top: 0.25rem;
  font-size: 0.875rem;
  color: #8898aa;
}
</style>