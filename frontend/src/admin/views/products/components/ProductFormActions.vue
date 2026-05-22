<template>
  <div class="form-actions">
    <div class="actions-left">
      <button 
        type="button" 
        @click="resetForm" 
        class="btn btn-outline btn-sm" 
        :disabled="saving"
      >
        <AppIcon name="undo-2" />
        Réinitialiser
      </button>
    </div>
    <div class="actions-right ">
      <button 
        type="submit" 
        class="btn btn-primary btn-sm" 
        :disabled="saving || !isFormValid"
        @click="submitForm"
      >
        <AppIcon name="loader-circle" :spin="true" v-if="saving" />
        <AppIcon name="check" v-else />
        {{ isEditing ? 'Mettre à jour' : 'Créer' }}
      </button>
    </div>
  </div>
</template>

<script>
export default {
  name: 'ProductFormActions',
  props: {
    isEditing: { type: Boolean, default: false },
    saving: { type: Boolean, default: false },
    isFormValid: { type: Boolean, default: true }
  },

  emits: ['reset', 'submit'],

  methods: {
    resetForm() {
      this.$emit('reset')
    },
    submitForm() {
      this.$emit('submit')
    }
  }
}
</script>