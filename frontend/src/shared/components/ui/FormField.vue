<template>
  <div :class="fieldClasses">
    <label v-if="label" :for="fieldId" class="form-label">
      {{ label }}
      <span v-if="required" class="required">*</span>
    </label>
    
    <!-- Input simple -->
    <BaseInput
      v-if="type !== 'textarea' && type !== 'select'"
      :id="fieldId"
      :model-value="modelValue"
      :type="type"
      :placeholder="placeholder"
      :disabled="disabled"
      :required="required"
      :error="error"
      :icon="icon"
      v-bind="$attrs"
      @update:modelValue="$emit('update:modelValue', $event)"
    />
    
    <!-- Textarea -->
    <textarea
      v-else-if="type === 'textarea'"
      :id="fieldId"
      :value="modelValue"
      :placeholder="placeholder"
      :disabled="disabled"
      :required="required"
      :rows="rows"
      class="form-textarea"
      :class="{ 'has-error': error }"
      @input="$emit('update:modelValue', $event.target.value)"
    />
    
    <!-- Select -->
    <select
      v-else-if="type === 'select'"
      :id="fieldId"
      :value="modelValue"
      :disabled="disabled"
      :required="required"
      class="form-select"
      :class="{ 'has-error': error }"
      @change="$emit('update:modelValue', $event.target.value)"
    >
      <option v-if="placeholder" value="">{{ placeholder }}</option>
      <slot name="options"></slot>
    </select>
    
    <span v-if="error" class="error-text">{{ error }}</span>
    <span v-else-if="hint" class="hint-text">{{ hint }}</span>
  </div>
</template>

<script>
import BaseInput from './BaseInput.vue'

export default {
  name: 'FormField',
  inheritAttrs: false,
  
  components: {
    BaseInput
  },
  
  emits: ['update:modelValue'],
  
  props: {
    modelValue: [String, Number, Boolean],
    label: String,
    type: {
      type: String,
      default: 'text'
    },
    placeholder: String,
    disabled: Boolean,
    required: Boolean,
    error: String,
    hint: String,
    icon: String,
    rows: {
      type: Number,
      default: 4
    },
    fullWidth: Boolean,
    id: String
  },
  
  computed: {
    fieldClasses() {
      return [
        'form-field',
        {
          'form-field-full-width': this.fullWidth,
          'has-error': this.error
        }
      ]
    },
    
    fieldId() {
      return this.id || `field-${Math.random().toString(36).substr(2, 9)}`
    }
  }
}
</script>

<style scoped>
.form-field {
  display: flex;
  flex-direction: column;
}

.form-field-full-width {
  grid-column: 1 / -1;
}

.form-label {
  display: block;
  margin-bottom: 0.5rem;
  font-weight: 600;
  color: var(--text-primary);
  font-size: 0.875rem;
}

.required {
  color: var(--danger);
}

.form-textarea,
.form-select {
  width: 100%;
  padding: 0.75rem 1rem;
  border: 1px solid #dee2e6;
  border-radius: 0.375rem;
  font-family: var(--font-primary);
  font-size: 1rem;
  line-height: 1.5;
  color: var(--text-primary);
  background: white;
  transition: all 0.15s ease;
}

.form-textarea {
  resize: vertical;
  min-height: 100px;
}

.form-textarea:focus,
.form-select:focus {
  outline: none;
  border-color: var(--primary);
  box-shadow: 0 0 0 0.2rem rgba(var(--primary), 0.25);
}

.form-textarea:disabled,
.form-select:disabled {
  background-color: #f8f9fa;
  opacity: 0.6;
  cursor: not-allowed;
}

.form-textarea.has-error,
.form-select.has-error {
  border-color: var(--danger);
}

.error-text {
  display: block;
  margin-top: 0.25rem;
  font-size: 0.875rem;
  color: var(--danger);
}

.hint-text {
  display: block;
  margin-top: 0.25rem;
  font-size: 0.875rem;
  color: var(--text-muted);
}
</style>