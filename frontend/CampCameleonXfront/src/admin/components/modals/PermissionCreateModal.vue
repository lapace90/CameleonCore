<!-- frontend/CampCameleonXfront/src/admin/components/modals/PermissionCreateModal.vue -->
<!-- Modal de création simple utilisant tes classes existantes -->

<template>
  <div class="modal-overlay" @click.self="$emit('close')">
    <div class="modal-container">
      <div class="modal-header">
        <h3>
          <i class="fas fa-plus"></i>
          Créer une permission
        </h3>
        <button @click="$emit('close')" class="btn-close">
          <i class="fas fa-times"></i>
        </button>
      </div>

      <form @submit.prevent="handleSubmit" class="modal-body">
        <!-- Nom de la permission -->
        <div class="form-group">
          <label for="name">Nom de la permission *</label>
          <input
            id="name"
            v-model="form.name"
            type="text"
            class="form-control"
            placeholder="Ex: Créer des utilisateurs"
            required
          />
          <small class="form-text">Nom explicite pour l'interface</small>
        </div>

        <!-- Action -->
        <div class="form-group">
          <label for="action">Action *</label>
          <input
            id="action"
            v-model="form.action"
            type="text"
            class="form-control"
            placeholder="Ex: create-users"
            required
          />
          <small class="form-text">
            Code technique (lettres minuscules et tirets uniquement)
          </small>
        </div>

        <!-- Aperçu automatique -->
        <div v-if="form.action" class="form-group">
          <label>Aperçu</label>
          <div class="permission-preview">
            <span class="badge badge-primary">{{ form.action }}</span>
            <span class="preview-category">→ Catégorie: {{ getCategory(form.action) }}</span>
          </div>
        </div>

        <!-- Actions -->
        <div class="modal-actions">
          <button
            type="button"
            @click="$emit('close')"
            class="btn btn-outline-secondary btn-sm"
            :disabled="loading"
          >
            Annuler
          </button>
          <button
            type="submit"
            class="btn btn-primary btn-sm"
            :disabled="loading || !isFormValid"
          >
            <i v-if="loading" class="fas fa-spinner fa-spin"></i>
            <i v-else class="fas fa-plus"></i>
            {{ loading ? 'Création...' : 'Créer' }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import PermissionsApi from '@/services/PermissionsApi'

const emit = defineEmits(['close', 'created'])

const loading = ref(false)
const form = ref({
  name: '',
  action: ''
})

const isFormValid = computed(() => {
  return form.value.name.trim() && 
         form.value.action.trim() && 
         /^[a-z-]+$/.test(form.value.action)
})

// Aperçu catégorie - juste pour l'UI
const getCategory = (action) => {
  const act = action.toLowerCase()
  if (act.includes('create') || act.includes('add')) return 'Création'
  if (act.includes('read') || act.includes('view') || act.includes('show')) return 'Lecture'
  if (act.includes('update') || act.includes('edit')) return 'Modification'
  if (act.includes('delete') || act.includes('remove')) return 'Suppression'
  if (act.includes('manage') || act.includes('admin')) return 'Administration'
  return 'Autre'
}

const handleSubmit = async () => {
  if (!isFormValid.value) return

  loading.value = true
  
  try {
    const newPermission = await PermissionsApi.create({
      name: form.value.name.trim(),
      action: form.value.action.trim().toLowerCase()
    })
    
    emit('created', newPermission)
    emit('close')
  } catch (error) {
    console.error('Erreur création permission:', error)
  } finally {
    loading.value = false
  }
}
</script>