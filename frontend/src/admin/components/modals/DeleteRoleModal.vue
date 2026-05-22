<template>
    <div class="modal-overlay" @click.self="$emit('close')">
        <div class="modal-content modal-sm">
            <div class="modal-header">
                <h3 class="text-danger">
                    <AppIcon name="triangle-alert" />
                    Supprimer le rôle
                </h3>
                <button @click="$emit('close')" class="btn-close">&times;</button>
            </div>

            <div class="modal-body">
                <p>
                    Êtes-vous sûr de vouloir supprimer le rôle
                    <strong>{{ role?.name }}</strong> ?
                </p>

                <div class="warning-box">
                    <AppIcon name="info" />
                    Cette action est irréversible. Toutes les permissions associées seront détachées.
                </div>

                <!-- Champ de confirmation seulement si suppression autorisée côté UI -->
                <div v-if="canDelete" class="form-group" style="margin-top: 1rem;">
                    <label class="form-label">
                        Pour confirmer, tapez <strong>{{ role.name }}</strong>
                    </label>
                    <input v-model.trim="confirmationName" type="text" class="form-input"
                        placeholder="Nom exact du rôle"
                        :class="{ error: showConfirmationError && !isConfirmationValid }" />
                    <span v-if="showConfirmationError && !isConfirmationValid" class="error-message">
                        Le nom saisi ne correspond pas.
                    </span>
                </div>

                <!-- Message backend global -->
                <div v-if="globalError" class="form-note error" style="margin-top: .75rem;">
                    <AppIcon name="circle-alert" />
                    {{ globalError }}
                </div>
            </div>

            <div class="modal-actions">
                <button @click="$emit('close')" class="btn btn-secondary btn-sm">
                    Annuler
                </button>

                <button v-if="canDelete" @click="confirmDeletion" class="btn btn-danger btn-sm"
                    :disabled="!isConfirmationValid || deleting">
                    <AppIcon name="loader-circle" :spin="true" v-if="deleting" />
                    {{ deleting ? 'Suppression...' : 'Supprimer définitivement' }}
                </button>

                <button v-else @click="$emit('close')" class="btn btn-primary btn-sm">
                    Compris
                </button>
            </div>
        </div>
    </div>
</template>

<script>
import RolesApi from '@/services/RolesApi'

export default {
    name: 'RoleDeleteModal',
    props: { role: { type: Object, required: true } },
    emits: ['close', 'deleted'],

    data() {
        return {
            confirmationName: '',
            showConfirmationError: false,
            deleting: false,
            globalError: null,
        }
    },

    computed: {
        // Autorise la suppression si non-critique et pas d’utilisateurs.
        // On tolère l’absence de compteur côté front en le considérant à 0 (le backend tranchera de toute façon).
        canDelete() {
            const r = this.role || {}
            const count =
                r.total_users_count ??
                r.users_count ??
                (Array.isArray(r.users) ? r.users.length : undefined) ??
                0
            return !r.is_critical && Number(count) === 0
        },

        isConfirmationValid() {
            if (!this.role?.name) return false
            return this.confirmationName.trim().toLowerCase() === this.role.name.toLowerCase()
        },
    },

    methods: {
        async confirmDeletion() {
            if (!this.isConfirmationValid) { this.showConfirmationError = true; return }
            this.globalError = null
            try {
                this.deleting = true
                const { useRolesStore } = await import('@/shared/stores/roles')
                const rolesStore = useRolesStore(this.$pinia)

                await rolesStore.deleteRole(this.role.id)
                this.$emit('deleted')
                this.$emit('close')
            } catch (err) {
                this.globalError = err.message || 'Erreur lors de la suppression du rôle.'
            } finally {
                this.deleting = false
            }
        }

    },
}
</script>
