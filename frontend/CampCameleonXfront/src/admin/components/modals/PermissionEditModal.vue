<!-- frontend/CampCameleonXfront/src/admin/components/modals/PermissionEditModal.vue -->
<!-- Modal d'édition utilisant tes classes existantes -->

<template>
    <div class="modal-overlay" @click.self="$emit('close')">
        <div class="modal-container">
            <div class="modal-header">
                <h3>
                    <i class="fas fa-edit"></i>
                    Modifier la permission
                </h3>
                <button @click="$emit('close')" class="btn-close">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <form @submit.prevent="handleSubmit" class="modal-body">
                <!-- Info actuelle -->
                <div class="alert alert-info">
                    <div class="info-item">
                        <span class="info-label">Permission actuelle :</span>
                        <span class="badge badge-primary">{{ permission.action }}</span>
                    </div>
                    <div v-if="permission.roles_count > 0" class="info-item">
                        <span class="info-label">Utilisée par :</span>
                        <span class="text-warning">{{ permission.roles_count }} rôle(s)</span>
                    </div>
                </div>

                <!-- Nom de la permission -->
                <div class="form-group">
                    <label for="name">Nom de la permission *</label>
                    <input id="name" v-model="form.name" type="text" class="form-control"
                        placeholder="Ex: Créer des utilisateurs" required />
                </div>

                <!-- Action -->
                <div class="form-group">
                    <label for="action">Action *</label>
                    <input id="action" v-model="form.action" type="text" class="form-control"
                        placeholder="Ex: create-users" required />
                    <small class="form-text text-warning">
                        ⚠️ Attention: Modifier l'action peut affecter les autorisations existantes
                    </small>
                </div>

                <!-- Aperçu des changements -->
                <div v-if="hasChanges" class="alert alert-warning">
                    <h6>Aperçu des modifications :</h6>
                    <div v-if="form.name !== permission.name" class="change-item">
                        <span class="change-field">Nom :</span>
                        <span class="text-muted">{{ permission.name }}</span>
                        <span class="change-arrow">→</span>
                        <span class="text-success font-weight-bold">{{ form.name }}</span>
                    </div>
                    <div v-if="form.action !== permission.action" class="change-item">
                        <span class="change-field">Action :</span>
                        <span class="text-muted">{{ permission.action }}</span>
                        <span class="change-arrow">→</span>
                        <span class="text-success font-weight-bold">{{ form.action }}</span>
                    </div>
                </div>

                <!-- Actions -->
                <div class="modal-actions">
                    <button type="button" @click="$emit('close')" class="btn btn-outline-secondary btn-sm" :disabled="loading">
                        Annuler
                    </button>
                    <button type="submit" class="btn btn-warning btn-sm" :disabled="loading || !isFormValid || !hasChanges">
                        <i v-if="loading" class="fas fa-spinner fa-spin"></i>
                        <i v-else class="fas fa-save"></i>
                        {{ loading ? 'Sauvegarde...' : 'Sauvegarder' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import PermissionsApi from '@/services/PermissionsApi'

const props = defineProps({
    permission: {
        type: Object,
        required: true
    }
})

const emit = defineEmits(['close', 'updated'])

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

const hasChanges = computed(() => {
    return form.value.name !== props.permission.name ||
        form.value.action !== props.permission.action
})

const initializeForm = () => {
    form.value = {
        name: props.permission.name || '',
        action: props.permission.action || ''
    }
}

const handleSubmit = async () => {
    if (!isFormValid.value || !hasChanges.value) return

    loading.value = true

    try {
        const updatedPermission = await PermissionsApi.update(props.permission.id, {
            name: form.value.name.trim(),
            action: form.value.action.trim().toLowerCase()
        })

        emit('updated', updatedPermission)
        emit('close')
    } catch (error) {
        console.error('Erreur modification permission:', error)
    } finally {
        loading.value = false
    }
}

onMounted(() => {
    initializeForm()
})
</script>