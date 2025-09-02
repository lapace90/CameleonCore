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
.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
}

.modal-content {
    background: white;
    border-radius: 12px;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
    max-width: 500px;
    width: 90%;
    max-height: 90vh;
    overflow-y: auto;
}

.modal-header {
    padding: 1.5rem;
    border-bottom: 1px solid #e9ecef;
    display: flex;
    justify-content: space-between;
    align-items: center;

    h3 {
        margin: 0;
        font-size: 1.25rem;
        font-weight: 600;
        color: #2c3e50;
        display: flex;
        align-items: center;
        gap: 0.5rem;

        i {
            color: #3498db;
        }
    }

    .btn-close {
        background: none;
        border: none;
        font-size: 1.5rem;
        cursor: pointer;
        color: #6c757d;
        width: 32px;
        height: 32px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 4px;
        transition: all 0.2s ease;

        &:hover {
            background: #f8f9fa;
            color: #495057;
        }
    }
}

.modal-body {
    padding: 1.5rem;
}

.bulk-action-content {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.action-description {
    margin: 0;
    color: #495057;
    line-height: 1.5;
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.form-label {
    font-weight: 500;
    color: #495057;
    margin: 0;
}

.form-input {
    padding: 0.75rem;
    border: 1px solid #ced4da;
    border-radius: 6px;
    font-size: 1rem;
    transition: border-color 0.2s ease, box-shadow 0.2s ease;

    &:focus {
        outline: none;
        border-color: #3498db;
        box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
    }
}

.warning-message {
    background: #fff3cd;
    border: 1px solid #ffeaa7;
    border-radius: 8px;
    padding: 1rem;
    color: #856404;

    i {
        color: #f39c12;
        margin-right: 0.5rem;
    }

    p {
        margin: 0 0 1rem 0;
    }
}

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

.users-preview {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.user-count {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: #6c757d;
    font-size: 0.9rem;

    i {
        color: #3498db;
    }
}

.form-actions {
    display: flex;
    gap: 0.75rem;
    justify-content: flex-end;
    margin-top: 1rem;
}

// Responsive
@media (max-width: 576px) {
    .modal-content {
        width: 95%;
        margin: 1rem;
    }

    .modal-header,
    .modal-body {
        padding: 1rem;
    }

    .form-actions {
        flex-direction: column;

        .btn {
            width: 100%;
            justify-content: center;
        }
    }
}
</style>