<template>
    <div v-if="show" class="modal-overlay" @click="$emit('cancel')">
        <div class="modal-content" @click.stop>
            <div class="modal-header">
                <h3>
                    <i class="fas fa-users-cog"></i>
                    Action en masse
                </h3>
                <button @click="$emit('cancel')" class="btn-close">&times;</button>
            </div>

            <div class="modal-body">
                <div class="bulk-action-content">
                    <p class="action-description">
                        Vous allez appliquer l'action <strong>{{ getActionLabel() }}</strong>
                        à <strong>{{ selectedCount }}</strong> utilisateur(s).
                    </p>

                    <!-- Sélection de rôle pour l'action "assign-role" -->
                    <div v-if="action === 'assign-role'" class="form-group">
                        <label class="form-label">Sélectionner le rôle à assigner</label>
                        <select v-model="selectedRoleId" class="form-input" required>
                            <option value="">Sélectionner un rôle</option>
                            <option v-for="role in availableRoles" :key="role.id" :value="role.id">
                                {{ role.name }}
                            </option>
                        </select>
                    </div>

                    <!-- Confirmation pour les actions destructives -->
                    <div v-if="isDestructiveAction" class="warning-message">
                        <i class="fas fa-exclamation-triangle"></i>
                        <p>
                            <strong>Attention :</strong>
                            Cette action {{ isDestructiveAction === 'delete' ? 'supprimera définitivement' : 'modifiera'
                            }}
                            les utilisateurs sélectionnés. Cette opération ne peut pas être annulée.
                        </p>

                        <div class="confirmation-checkbox">
                            <label>
                                <input type="checkbox" v-model="confirmAction">
                                Je comprends les conséquences de cette action
                            </label>
                        </div>
                    </div>

                    <!-- Résumé des utilisateurs sélectionnés -->
                    <div class="selected-users-summary">
                        <h4>Utilisateurs sélectionnés ({{ selectedCount }})</h4>
                        <div class="users-preview">
                            <div class="user-count">
                                <i class="fas fa-users"></i>
                                {{ selectedCount }} utilisateur(s) {{ getPluralText() }}
                            </div>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button @click="$emit('cancel')" class="btn btn-outline btn-sm">
                            <i class="fas fa-times"></i>
                            Annuler
                        </button>

                        <button @click="handleConfirm" class="btn btn-sm" :class="getConfirmButtonClass()"
                            :disabled="!canConfirm">
                            <i :class="getActionIcon()"></i>
                            {{ getActionLabel() }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: 'BulkActionModal',
    props: {
        show: {
            type: Boolean,
            default: false
        },
        action: {
            type: String,
            required: true
        },
        selectedCount: {
            type: Number,
            required: true
        },
        availableRoles: {
            type: Array,
            default: () => []
        }
    },

    emits: ['confirm', 'cancel'],

    data() {
        return {
            selectedRoleId: '',
            confirmAction: false
        }
    },

    computed: {
        isDestructiveAction() {
            const destructiveActions = {
                'delete': 'delete',
                'deactivate': 'modify'
            }
            return destructiveActions[this.action] || false
        },

        canConfirm() {
            // Vérifier si l'action peut être confirmée
            if (this.action === 'assign-role' && !this.selectedRoleId) {
                return false
            }

            if (this.isDestructiveAction && !this.confirmAction) {
                return false
            }

            return true
        }
    },

    watch: {
        show(newVal) {
            if (newVal) {
                // Réinitialiser le formulaire quand la modal s'ouvre
                this.selectedRoleId = ''
                this.confirmAction = false
            }
        }
    },

    methods: {
        getActionLabel() {
            const labels = {
                'activate': 'Activer',
                'deactivate': 'Désactiver',
                'delete': 'Supprimer',
                'assign-role': 'Assigner un rôle'
            }
            return labels[this.action] || 'Action'
        },

        getActionIcon() {
            const icons = {
                'activate': 'fas fa-check',
                'deactivate': 'fas fa-ban',
                'delete': 'fas fa-trash',
                'assign-role': 'fas fa-user-tag'
            }
            return icons[this.action] || 'fas fa-cog'
        },

        getConfirmButtonClass() {
            const classes = {
                'activate': 'btn-success',
                'deactivate': 'btn-warning',
                'delete': 'btn-danger',
                'assign-role': 'btn-primary'
            }
            return classes[this.action] || 'btn-primary'
        },

        getPluralText() {
            const texts = {
                'activate': 'seront activés',
                'deactivate': 'seront désactivés',
                'delete': 'seront supprimés',
                'assign-role': 'recevront le rôle sélectionné'
            }
            return texts[this.action] || 'seront traités'
        },

        handleConfirm() {
            if (!this.canConfirm) return

            const additionalData = {}

            // Ajouter les données supplémentaires selon l'action
            if (this.action === 'assign-role') {
                additionalData.role_id = this.selectedRoleId
            }

            this.$emit('confirm', additionalData)
        }
    }
}
</script>

<style scoped lang="scss">

.confirmation-checkbox {
    label {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        cursor: pointer;
        font-weight: 500;

        input[type="checkbox"] {
            margin: 0;
        }
    }
}

.selected-users-summary {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 1rem;

    h4 {
        margin: 0 0 0.75rem 0;
        font-size: 1rem;
        color: #495057;
    }
}

</style>