<!-- frontend/CampCameleonXfront/src/admin/components/modals/RoleEditModal.vue -->
<template>
    <div class="modal-overlay" @click.self="$emit('close')">
        <div class="modal-container modal-lg">
            <div class="modal-header">
                <h3>
                    <i class="fas fa-edit"></i>
                    Modifier le rôle : {{ currentRole.name }}
                </h3>
                <button @click="$emit('close')" class="btn-close">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <form @submit.prevent="handleSubmit" class="modal-body">
                <!-- Informations du rôle -->
                <div class="form-section">
                    <h4>
                        <i class="fas fa-info-circle"></i>
                        Informations générales
                    </h4>

                    <div class="form-group">
                        <label for="name">Nom du rôle *</label>
                        <input id="name" v-model="form.name" type="text" class="form-control"
                            placeholder="Ex: Gestionnaire" :class="{ 'is-invalid': formErrors.name }" required />
                        <div v-if="formErrors.name" class="invalid-feedback">{{ formErrors.name[0] }}</div>
                    </div>

                    <div class="form-group">
                        <label for="slug">Identifiant (slug)</label>
                        <input id="slug" v-model="form.slug" type="text" class="form-control" placeholder="Ex: manager"
                            :class="{ 'is-invalid': formErrors.slug }" />
                        <div v-if="formErrors.slug" class="invalid-feedback">{{ formErrors.slug[0] }}</div>
                    </div>

                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea id="description" v-model="form.description" class="form-control" rows="3"
                            placeholder="Description du rôle et de ses responsabilités"
                            :class="{ 'is-invalid': formErrors.description }"></textarea>
                        <div v-if="formErrors.description" class="invalid-feedback">{{ formErrors.description[0] }}
                        </div>
                    </div>
                </div>

                <!-- Permissions -->
                <div class="form-section">
                    <h4>
                        <i class="fas fa-key"></i>
                        Permissions
                        <span class="text-muted">({{ selectedPermissions.length }})</span>
                        <span v-if="permissionsChanged" class="badge badge-warning ml-2">
                            <i class="fas fa-exclamation-triangle"></i>
                            Modifiées
                        </span>
                    </h4>

                    <!-- Loading des permissions -->
                    <LoadingState v-if="loadingPermissions" state="loading" variant="inline"
                        loading-text="Chargement des permissions..." />

                    <!-- Erreur de chargement -->
                    <div v-else-if="loadError" class="error-state">
                        <i class="fas fa-exclamation-triangle"></i>
                        <p>Impossible de charger les permissions</p>
                        <button type="button" @click="loadPermissions" class="btn btn-outline btn-sm">
                            <i class="fas fa-redo"></i>
                            Réessayer
                        </button>
                    </div>

                    <!-- Accordéon des permissions -->
                    <div v-else-if="allPermissions.length > 0">
                        <PermissionsAccordion :permissions="allPermissions" :selected-permissions="selectedPermissions"
                            :original-permissions="originalPermissions" mode="editable" :show-actions="true"
                            :default-open-categories="getDefaultOpenCategories()"
                            @update:selected-permissions="selectedPermissions = $event"
                            @permission-changed="handlePermissionChanged" />

                        <!-- Résumé des changements -->
                        <div v-if="hasChanges" class="alert alert-info mt-3">
                            <h6><i class="fas fa-info-circle"></i> Résumé des modifications</h6>
                            <div class="changes-summary">
                                <div v-if="permissionsChanged">
                                    <strong>Permissions :</strong> {{ selectedPermissions.length }} sélectionnées
                                    ({{ getPermissionsDiff() }})
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Aucune permission -->
                    <div v-else class="form-note">
                        <i class="fas fa-info-circle"></i>
                        Aucune permission disponible.
                    </div>
                </div>

                <!-- Actions -->
                <div class="modal-footer">
                    <button type="button" @click="$emit('close')" class="btn btn-secondary btn-sm">
                        Annuler
                    </button>
                    <button type="button" @click="resetForm" class="btn btn-outline btn-sm" :disabled="!hasChanges">
                        <i class="fas fa-undo"></i>
                        Réinitialiser
                    </button>
                    <button type="submit" class="btn btn-primary btn-sm" :disabled="!hasChanges || submitting">
                        <i v-if="submitting" class="fas fa-spinner fa-spin"></i>
                        <i v-else class="fas fa-save"></i>
                        {{ submitting ? 'Mise à jour...' : 'Mettre à jour' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>

<script>
import RolesApi from '@/services/RolesApi'
import PermissionsAccordion from '@/admin/components/ui/PermissionsAccordion.vue'
import LoadingState from '@/admin/components/ui/LoadingState.vue'

export default {
    name: 'RoleEditModal',

    components: {
        PermissionsAccordion,
        LoadingState
    },

    props: {
        role: {
            type: Object,
            required: true
        }
    },

    emits: ['close', 'updated'],

    data() {
        return {
            form: { name: '', slug: '', description: '' },
            selectedPermissions: [],
            originalPermissions: [],
            allPermissions: [],
            formErrors: {},
            submitting: false,
            loadingPermissions: false,
            loadError: false,
            // Copie locale complète du rôle si besoin de refetch
            roleFull: null,
        }
    },

    computed: {
        currentRole() {
            return this.roleFull || this.role
        },

        hasChanges() {
            const r = this.currentRole
            return this.form.name !== (r.name || '') ||
                this.form.slug !== (r.slug || '') ||
                this.form.description !== (r.description || '') ||
                this.permissionsChanged
        },

        permissionsChanged() {
            if (this.originalPermissions.length !== this.selectedPermissions.length) return true

            const setSelected = new Set(this.selectedPermissions.map(String))
            return !this.originalPermissions.every(id => setSelected.has(String(id)))
        }
    },

    watch: {
        role: {
            async handler() {
                await this.ensureRolePermissions()
                this.initializeForm()
            },
            deep: true
        }
    },

    async mounted() {
        await this.ensureRolePermissions()
        this.initializeForm()
        await this.loadPermissions()
    },

    methods: {
        initializeForm() {
            const r = this.currentRole
            this.form = {
                name: r.name || '',
                slug: r.slug || '',
                description: r.description || '',
            }

            // Permissions sélectionnées initiales
            const selected = (r.permissions || []).map(p => String(p.id))
            this.selectedPermissions = [...selected]
            this.originalPermissions = [...selected]
        },

        async ensureRolePermissions() {
            if (!this.role?.permissions || this.role.permissions.length === 0) {
                try {
                    const data = await RolesApi.getById(this.role.id)
                    this.roleFull = data
                } catch (e) {
                    console.warn('Impossible de recharger le rôle complet:', e)
                    this.roleFull = null
                }
            } else {
                this.roleFull = null
            }
        },

        async loadPermissions() {
            this.loadingPermissions = true
            this.loadError = false
            try {
                const { useRolesStore } = await import('@/shared/stores/roles')
                const rolesStore = useRolesStore()
                await rolesStore.ensurePermissions()

                // Conversion du format store vers format PermissionsAccordion
                this.allPermissions = this.flattenPermissions(rolesStore.availablePermissions.categories)

            } catch (e) {
                console.error('Erreur chargement permissions:', e)
                this.loadError = true
            } finally {
                this.loadingPermissions = false
            }
        },

        // Convertit le format { categories: [{ permissions: [] }] } vers un array plat
        flattenPermissions(categories) {
            const permissions = []
            categories.forEach(category => {
                category.permissions.forEach(permission => {
                    permissions.push({
                        ...permission,
                        category: category.key || category.name
                    })
                })
            })
            return permissions
        },

        getDefaultOpenCategories() {
            // Ouvre les catégories qui ont des permissions sélectionnées
            const selectedSet = new Set(this.selectedPermissions.map(String))
            const openCategories = new Set()

            this.allPermissions.forEach(permission => {
                if (selectedSet.has(String(permission.id))) {
                    openCategories.add(permission.category)
                }
            })

            // Toujours ouvrir 'users' par défaut
            openCategories.add('users')

            return Array.from(openCategories)
        },

        getPermissionsDiff() {
            const added = this.selectedPermissions.length - this.originalPermissions.length
            if (added > 0) return `+${added}`
            if (added < 0) return `${added}`
            return 'modifiées'
        },

        handlePermissionChanged(event) {
            // Optionnel : logger les changements
            console.log('Permission changed:', event)
        },

        resetForm() {
            this.initializeForm()
        },

        async handleSubmit() {
            if (!this.hasChanges || this.submitting) return

            this.submitting = true
            this.formErrors = {}

            try {
                const payload = {
                    ...this.form,
                    permissions: this.selectedPermissions
                }

                const response = await RolesApi.update(this.currentRole.id, payload)

                this.$emit('updated', response)
                this.$emit('close')
            } catch (error) {
                console.error('Erreur lors de la mise à jour:', error)

                if (error.response?.status === 422) {
                    this.formErrors = error.response.data.errors || {}
                } else {
                    alert('Erreur lors de la mise à jour du rôle')
                }
            } finally {
                this.submitting = false
            }
        },
    }
}
</script>