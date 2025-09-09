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
                    <input id="name" v-model="form.name" type="text" class="form-control"
                        placeholder="Ex: Créer des utilisateurs" required />
                    <small class="form-text">Nom explicite pour l'interface</small>
                </div>

                <!-- Action -->
                <div class="form-group">
                    <label for="action">Action *</label>
                    <input id="action" v-model="form.action" type="text" class="form-control"
                        placeholder="Ex: create-users" required />
                    <small class="form-text">
                        Code technique (lettres minuscules et tirets uniquement)
                    </small>
                </div>

                <!-- Sélection de catégorie -->
                <PermissionCategorySelect v-model="form.category" :action-preview="form.action" />

                <!-- Aperçu automatique -->
                <div v-if="form.action && form.name" class="form-group">
                    <label>Aperçu de la permission</label>
                    <div class="permission-preview">
                        <div class="preview-item">
                            <span class="preview-label">Nom :</span>
                            <span class="preview-value">{{ form.name }}</span>
                        </div>
                        <div class="preview-item">
                            <span class="preview-label">Action :</span>
                            <span class="badge badge-primary">{{ form.action }}</span>
                        </div>
                        <div class="preview-item">
                            <span class="preview-label">Catégorie :</span>
                            <span class="badge badge-secondary">
                                {{ form.category || 'Auto-détectée' }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="modal-actions">
                    <button type="button" @click="$emit('close')" class="btn btn-outline-secondary btn-sm"
                        :disabled="loading">
                        Annuler
                    </button>
                    <button type="submit" class="btn btn-primary btn-sm" :disabled="loading || !isFormValid">
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
import PermissionCategorySelect from '../forms/PermissionCategorySelect.vue'

const emit = defineEmits(['close', 'created'])

const loading = ref(false)
const actionError = ref('')
const form = ref({
  name: '',
  action: '',
  category: '' // Nouveau champ
})

const isFormValid = computed(() => {
  return form.value.name.trim() && 
         form.value.action.trim() && 
         !actionError.value
})

const validateAction = () => {
  const action = form.value.action.trim()
  
  if (!action) {
    actionError.value = ''
    return
  }
  
  if (!/^[a-z0-9-]+$/.test(action)) {
    actionError.value = 'Seules les lettres minuscules, chiffres et tirets sont autorisés'
  } else if (action.startsWith('-') || action.endsWith('-')) {
    actionError.value = 'L\'action ne peut pas commencer ou finir par un tiret'
  } else if (action.includes('--')) {
    actionError.value = 'Les tirets doubles ne sont pas autorisés'
  } else {
    actionError.value = ''
  }
}

const handleSubmit = async () => {
  if (!isFormValid.value) return

  loading.value = true
  
  try {
    const permissionData = {
      name: form.value.name.trim(),
      action: form.value.action.trim().toLowerCase()
    }
    
    // Ajouter la catégorie seulement si elle est explicitement choisie
    if (form.value.category) {
      permissionData.category = form.value.category
    }
    
    const newPermission = await PermissionsApi.create(permissionData)
    
    emit('created', newPermission)
    emit('close')
  } catch (error) {
    console.error('Erreur création permission:', error)
  } finally {
    loading.value = false
  }
}
</script>