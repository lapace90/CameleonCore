<template>
  <div class="basic-fields">
    <h3>Informations générales</h3>
    <div class="form-grid">
      <div class="form-group">
        <label class="form-label required">Nom</label>
        <input 
          v-model="localValue.name" 
          type="text" 
          class="form-input" 
          placeholder="Nom du produit" 
          required
          :class="{ 'error': errors.name }" 
        />
        <span v-if="errors.name" class="error-message">{{ errors.name }}</span>
      </div>

      <div class="form-group">
        <label class="form-label required">Prix</label>
        <div class="input-group">
          <input 
            v-model.number="localValue.price" 
            type="number" 
            step="0.01" 
            min="0" 
            class="form-input"
            placeholder="0.00" 
            required 
            :class="{ 'error': errors.price }" 
          />
          <span class="input-addon">€</span>
        </div>
        <span v-if="errors.price" class="error-message">{{ errors.price }}</span>
      </div>

      <div class="form-group">
        <label class="form-label">Statut</label>
        <div class="radio-group">
          <label class="radio-item">
            <input 
              type="radio" 
              v-model="localValue.status" 
              :value="true" 
              name="status" 
            />
            <span class="radio-label">Actif</span>
          </label>
          <label class="radio-item">
            <input 
              type="radio" 
              v-model="localValue.status" 
              :value="false" 
              name="status" 
            />
            <span class="radio-label">Inactif</span>
          </label>
        </div>
      </div>

      <div class="form-group full-width">
        <label class="form-label">Description</label>
        <textarea 
          v-model="localValue.description" 
          class="form-textarea" 
          rows="3"
          placeholder="Description du produit..."
        ></textarea>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'ProductBasicFields',
  props: {
    type: { type: String, required: true },
    modelValue: { type: Object, default: () => ({}) },
    errors: { type: Object, default: () => ({}) }
  },

  emits: ['update:modelValue'],

  computed: {
    localValue: {
      get() {
        return this.modelValue
      },
      set(value) {
        this.$emit('update:modelValue', value)
      }
    }
  },

  watch: {
    localValue: {
      handler(newValue) {
        this.$emit('update:modelValue', newValue)
      },
      deep: true
    }
  }
}
</script>