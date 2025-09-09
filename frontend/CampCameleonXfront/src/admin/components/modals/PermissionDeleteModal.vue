<!-- frontend/CampCameleonXfront/src/admin/components/modals/PermissionDeleteModal.vue -->
<!-- Modal de suppression utilisant tes classes existantes -->

<template>
  <div class="modal-overlay" @click.self="$emit('close')">
    <div class="modal-container">
      <div class="modal-header">
        <h3>
          <i class="fas fa-trash"></i>
          Supprimer la permission
        </h3>
        <button @click="$emit('close')" class="btn-close">
          <i class="fas fa-times"></i>
        </button>
      </div>

      <div class="modal-body">
        <!-- Informations sur la permission -->
        <div class="permission-info">
          <div class="permission-details">
            <h4>{{ permission.name }}</h4>
            <span class="badge badge-primary">{{ permission.action }}</span>
          </div>
        </div>

        <!-- Vérification d'usage -->
        <div v-if="permission.roles_count > 0" class="alert alert-warning">
          <div class="warning-content">
            <h6><i class="fas fa-exclamation-triangle"></i> Permission utilisée</h6>
            <p>
              Cette permission est actuellement utilisée par 
              <strong>{{ permission.roles_count }} rôle(s)</strong>.
            </p>
            <p>
              <strong>Vous ne pouvez pas la supprimer</strong> tant qu'elle est assignée.
            </p>
            <div class="alert alert-info">
              <i class="fas fa-lightbulb"></i>
              Retirez d'abord cette permission des rôles concernés.
            </div>
          </div>
        </div>

        <!-- Avertissement permission critique -->
        <div v-else-if="isCriticalPermission" class="alert alert-danger">
          <div class="warning-content">
            <h6><i class="fas fa-shield-alt"></i> Permission critique</h6>
            <p>
              Cette permission est considérée comme critique pour la sécurité du système.
            </p>
            <p>
              <strong>Sa suppression est interdite</strong> pour des raisons de sécurité.
            </p>
          </div>
        </div>

        <!-- Confirmation de suppression -->
        <div v-else class="alert alert-info">
          <div class="confirmation-content">
            <h6><i class="fas fa-question-circle"></i> Confirmer la suppression</h6>
            <p>
              Êtes-vous sûr de vouloir supprimer définitivement cette permission ?
            </p>
            <p class="text-danger">
              <i class="fas fa-exclamation-triangle"></i>
              <strong>Cette action est irréversible.</strong>
            </p>
          </div>
        </div>

        <!-- Confirmation par saisie -->
        <div v-if="canDelete" class="form-group">
          <label for="confirmText">
            Tapez <strong>"{{ confirmationText }}"</strong> pour confirmer :
          </label>
          <input
            id="confirmText"
            v-model="userConfirmation"
            type="text"
            class="form-control"
            :placeholder="confirmationText"
          />
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
          v-if="canDelete"
          @click="handleDelete"
          class="btn btn-danger btn-sm"
          :disabled="loading || !isConfirmationValid"
        >
          <i v-if="loading" class="fas fa-spinner fa-spin"></i>
          <i v-else class="fas fa-trash"></i>
          {{ loading ? 'Suppression...' : 'Supprimer définitivement' }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import PermissionsApi from '@/services/PermissionsApi'

const props = defineProps({
  permission: {
    type: Object,
    required: true
  }
})

const emit = defineEmits(['close', 'deleted'])

const loading = ref(false)
const userConfirmation = ref('')

// Peut-on supprimer cette permission ?
const canDelete = computed(() => {
  return props.permission.roles_count === 0 && !isCriticalPermission.value
})

// Est-ce une permission critique ?
const isCriticalPermission = computed(() => {
  const criticalActions = [
    'system-admin', 'delete-users', 'manage-permissions', 
    'manage-roles', 'admin-access', 'super-admin'
  ]
  return criticalActions.includes(props.permission.action)
})

// Texte de confirmation à saisir
const confirmationText = computed(() => {
  return `SUPPRIMER ${props.permission.action.toUpperCase()}`
})

// La confirmation est-elle valide ?
const isConfirmationValid = computed(() => {
  return userConfirmation.value.trim() === confirmationText.value
})

const handleDelete = async () => {
  if (!canDelete.value || !isConfirmationValid.value) return

  loading.value = true
  
  try {
    await PermissionsApi.delete(props.permission.id)
    
    emit('deleted', props.permission)
    emit('close')
  } catch (error) {
    console.error('Erreur suppression permission:', error)
  } finally {
    loading.value = false
  }
}
</script>