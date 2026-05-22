<!-- frontend/CampCameleonXfront/src/admin/components/modals/PermissionEditModal.vue -->
<!-- Modal d'édition utilisant tes classes existantes -->

<template>
    <div class="modal-overlay" @click.self="$emit('close')">
        <div class="modal-container">
            <div class="modal-header">
                <h3>
                    <AppIcon name="pencil" />
                    Modifier la permission
                </h3>
                <button @click="$emit('close')" class="btn-close">
                    <AppIcon name="x" />
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

                <!-- Sélection de catégorie -->
                <PermissionCategorySelect v-model="form.category" :action-preview="form.action" />

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

                    <div v-if="form.category !== (permission.category || '')" class="change-item">
                        <span class="change-field">Catégorie :</span>
                        <span class="text-muted">{{ permission.category || 'Auto-détectée' }}</span>
                        <span class="change-arrow">→</span>
                        <span class="text-success font-weight-bold">
                            {{ form.category || 'Auto-détectée' }}
                        </span>
                    </div>
                </div>

                <!-- Actions -->
                <div class="modal-actions">
                    <button type="button" @click="$emit('close')" class="btn btn-outline-secondary btn-sm"
                        :disabled="loading">
                        Annuler
                    </button>
                    <button type="submit" class="btn btn-warning btn-sm"
                        :disabled="loading || !isFormValid || !hasChanges">
                        <AppIcon name="loader-circle" :spin="true" v-if="loading" />
                        <AppIcon name="save" v-else />
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
import PermissionCategorySelect from '../forms/PermissionCategorySelect.vue'

const props = defineProps({
    permission: {
        type: Object,
        required: true
    }
})

const emit = defineEmits(['close', 'updated'])

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

const hasChanges = computed(() => {
    return form.value.name !== props.permission.name ||
        form.value.action !== props.permission.action ||
        form.value.category !== (props.permission.category || '')
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

const initializeForm = () => {
    form.value = {
        name: props.permission.name || '',
        action: props.permission.action || '',
        category: props.permission.category || '' // Récupérer la catégorie existante
    }
}

const handleSubmit = async () => {
    if (!isFormValid.value || !hasChanges.value) return

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

        const updatedPermission = await PermissionsApi.update(props.permission.id, permissionData)

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