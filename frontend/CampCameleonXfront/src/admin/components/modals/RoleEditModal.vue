<template>
    <div class="modal-overlay" @click.self="$emit('close')">
        <div class="modal-container">
            <div class="modal-header">
                <h3>
                    <i class="fas fa-edit"></i>
                    Modifier "{{ role.name }}"
                </h3>
                <button @click="$emit('close')" class="btn-close">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <form @submit.prevent="updateRole" class="modal-body">
                <!-- Informations de base -->
                <div class="form-section">
                    <h4>Informations générales</h4>

                    <div class="form-group">
                        <label class="form-label required">Nom du rôle</label>
                        <input v-model="form.name" type="text" class="form-input"
                            placeholder="Ex: Administrateur, Gestionnaire..." required
                            :class="{ 'error': formErrors.name }" />
                        <span v-if="formErrors.name" class="error-message">{{ formErrors.name[0] }}</span>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Slug</label>
                        <input v-model="form.slug" type="text" class="form-input" placeholder="admin, manager..."
                            :class="{ 'error': formErrors.slug }" :disabled="role.is_critical" />
                        <span v-if="formErrors.slug" class="error-message">{{ formErrors.slug[0] }}</span>
                        <span v-if="role.is_critical" class="form-help">
                            Le slug ne peut pas être modifié pour un rôle critique
                        </span>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Description</label>
                        <textarea v-model="form.description" class="form-textarea" rows="3"
                            placeholder="Description du rôle et de ses responsabilités..."
                            :class="{ 'error': formErrors.description }"></textarea>
                        <span v-if="formErrors.description" class="error-message">{{ formErrors.description[0] }}</span>
                    </div>
                </div>

                <!-- Gestion des permissions -->
                <!-- Gestion des permissions -->
                <div class="form-section">
                    <h4>
                        <i class="fas fa-key"></i>
                        Permissions ({{ selectedPermissions.length }} sélectionnées)
                    </h4>

                    <div v-if="role.is_critical" class="warning-box">
                        <i class="fas fa-shield-alt"></i>
                        Attention : Ce rôle est critique pour le système. Modifiez les permissions avec précaution.
                    </div>

                    <!-- États de chargement / vide / erreur -->
                    <div v-else-if="loadingPermissions" class="form-note">
                        <i class="fas fa-info-circle"></i>
                        Chargement des permissions...
                    </div>

                    <div v-else-if="loadError" class="form-note">
                        <i class="fas fa-exclamation-triangle"></i>
                        Erreur lors du chargement des permissions.
                    </div>

                    <div v-else-if="permissionCategories.length === 0" class="form-note">
                        <i class="fas fa-info-circle"></i>
                        Aucune permission disponible.
                    </div>

                    <div v-else class="permissions-section">

                        <!-- Actions rapides -->
                        <div class="permission-actions">
                            <button type="button" @click="selectAllPermissions" class="btn btn-outline btn-sm">
                                Tout sélectionner
                            </button>
                            <button type="button" @click="clearAllPermissions" class="btn btn-outline btn-sm">
                                Tout désélectionner
                            </button>
                            <button type="button" @click="resetPermissions" class="btn btn-outline btn-sm">
                                Restaurer
                            </button>
                        </div>

                        <!-- Liste des permissions groupées -->
                        <div class="permissions-grid">
                            <div v-for="category in permissionCategories" :key="category.key || category.name"
                                class="permission-category">
                                <div class="category-header" @click="toggleAccordion(category)" style="cursor:pointer;">
                                    <div class="category-left">
                                        <i :class="`fas ${category.icon}`" :style="{ color: category.color }"></i>
                                        <span>
                                            {{ category.name }}
                                            ({{ getCategorySelectedCount(category) }}/{{ category.permissions.length }})
                                        </span>
                                    </div>
                                    <div class="category-right">
                                        <button v-if="isOpen(category)" type="button"
                                            @click.stop="toggleCategoryPermissions(category)"
                                            class="btn btn-outline btn-xs mx-5">
                                            {{ isCategorySelected(category) ? 'Désélectionner' : 'Sélectionner' }}
                                        </button>
                                        <i class="fas"
                                            :class="isOpen(category) ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
                                    </div>
                                </div>

                                <transition name="fade">
                                    <div v-show="isOpen(category)" class="permission-items">
                                        <label v-for="permission in category.permissions" :key="permission.id"
                                            class="permission-checkbox"
                                            :class="{ 'changed': isPermissionChanged(permission.id) }">
                                            <input type="checkbox" :value="permission.id"
                                                v-model="selectedPermissions" />
                                            <span class="checkmark"></span>
                                            <span class="permission-name">{{ permission.name }}</span>
                                            <i v-if="isPermissionChanged(permission.id)"
                                                class="fas fa-dot-circle text-warning" title="Modifié"></i>
                                        </label>
                                    </div>
                                </transition>
                            </div>

                        </div>
                    </div>
                </div>


                <!-- Actions -->
                <div class="modal-actions">
                    <button type="button" @click="$emit('close')" class="btn btn-secondary btn-sm">
                        Annuler
                    </button>
                    <button type="submit" class="btn btn-primary btn-sm" :disabled="submitting || !hasChanges">
                        <i v-if="submitting" class="fas fa-spinner fa-spin"></i>
                        {{ submitting ? 'Mise à jour...' : 'Mettre à jour' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>

<script>
import RolesApi from '@/services/RolesApi'

export default {
    name: 'RoleEditModal',

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
            permissionCategories: [],
            formErrors: {},
            submitting: false,
            loadingPermissions: false,
            loadError: false,

            // on garde une copie locale "pleine" du rôle si on doit refetch
            roleFull: null,
            openCategories: new Set(), // accordéon
        }
    },

    computed: {
        currentRole() {
            // on affiche toujours ce qu'on a de plus “hydraté”
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
            const A = this.originalPermissions
            const B = this.selectedPermissions
            if (A.length !== B.length) return true
            // comparaison sur IDs normalisés (string)
            const setB = new Set(B.map(String))
            return !A.every(id => setB.has(String(id)))
        }
    },

    watch: {
        role: {
            async handler() {
                await this.ensureRolePermissions()   // réhydrate si besoin
                this.initializeForm()                // puis réinit form/permissions
            },
            deep: true
        }
    },

    async mounted() {
        await this.ensureRolePermissions()
        this.initializeForm()

        // ✅ charger via le store (cache, TTL, mutualisation)
        const { useRolesStore } = await import('@/shared/stores/roles')
        const rolesStore = useRolesStore()
        await rolesStore.ensurePermissions()
        this.permissionCategories = rolesStore.availablePermissions.categories

        // accordéon fermé par défaut
        this.collapseAll()
    },

    methods: {
        // --- Normalisation ID -> string (évite includes() qui rate)
        toId(val) { return String(val) },

        normalizeIdArray(arr) { return (arr || []).map(x => this.toId(x)) },

        initializeForm() {
            const r = this.currentRole
            this.form = {
                name: r.name || '',
                slug: r.slug || '',
                description: r.description || '',
            }
            // permissions sélectionnées initiales (normalisées)
            const selected = (r.permissions || []).map(p => this.toId(p.id))
            this.selectedPermissions = [...selected]
            this.originalPermissions = [...selected]
        },

        async ensureRolePermissions() {
            if (!this.role?.permissions || this.role.permissions.length === 0) {
                try {
                    const data = await RolesApi.getById(this.role.id) // ✅ bon nom
                    this.roleFull = data
                } catch (e) {
                    console.warn('Impossible de recharger le rôle complet:', e)
                    this.roleFull = null
                }
            } else {
                this.roleFull = null
            }
        },

        // async loadPermissions() {
        //     this.loadingPermissions = true
        //     this.loadError = false
        //     try {
        //         // ⚠️ getAllPermissions() retourne déjà le body (pas l'objet axios complet)
        //         const data = await RolesApi.getAllPermissions()

        //         if (Array.isArray(data?.categories)) {
        //             // format groupé
        //             this.permissionCategories = data.categories.map(cat => ({
        //                 ...cat,
        //                 permissions: (cat.permissions || []).map(p => ({ ...p, id: String(p.id) }))
        //             }))
        //         } else if (Array.isArray(data?.permissions)) {
        //             // format plat → regrouper
        //             const byCat = {}
        //             data.permissions.forEach(p => {
        //                 const key = p.category || 'general'
        //                 if (!byCat[key]) {
        //                     byCat[key] = {
        //                         key,
        //                         name: key.charAt(0).toUpperCase() + key.slice(1),
        //                         icon: 'fa-key',
        //                         color: undefined,
        //                         permissions: []
        //                     }
        //                 }
        //                 byCat[key].permissions.push({ ...p, id: String(p.id) })
        //             })
        //             this.permissionCategories = Object.values(byCat)
        //         } else {
        //             this.permissionCategories = []
        //         }

        //         // normalisation des ids sélectionnés existants
        //         this.selectedPermissions = this.selectedPermissions.map(String)
        //         this.originalPermissions = this.originalPermissions.map(String)
        //     } catch (e) {
        //         console.error('Erreur chargement permissions:', e)
        //         this.loadError = true
        //     } finally {
        //         this.loadingPermissions = false
        //     }
        // },

        // === Sélection & toggles (idempotents, normalisés)
        selectAllPermissions() {
            const merged = new Set(this.selectedPermissions.map(String))
            this.permissionCategories.forEach(cat => {
                cat.permissions.forEach(p => merged.add(this.toId(p.id)))
            })
            this.selectedPermissions = Array.from(merged)
        },

        clearAllPermissions() {
            this.selectedPermissions = []
        },

        resetPermissions() {
            this.selectedPermissions = [...this.originalPermissions]
        },

        toggleCategoryPermissions(category) {
            const ids = category.permissions.map(p => this.toId(p.id))
            const selected = new Set(this.selectedPermissions.map(String))
            const allSelected = ids.every(id => selected.has(id))

            if (allSelected) {
                ids.forEach(id => selected.delete(id))
            } else {
                ids.forEach(id => selected.add(id))
            }
            this.selectedPermissions = Array.from(selected)
        },

        isCategorySelected(category) {
            const ids = category.permissions.map(p => this.toId(p.id))
            const selected = new Set(this.selectedPermissions.map(String))
            return ids.every(id => selected.has(id))
        },

        getCategorySelectedCount(category) {
            const ids = category.permissions.map(p => this.toId(p.id))
            const selected = new Set(this.selectedPermissions.map(String))
            return ids.filter(id => selected.has(id)).length
        },

        isPermissionChanged(permissionId) {
            const id = this.toId(permissionId)
            const was = new Set(this.originalPermissions.map(String))
            const is = new Set(this.selectedPermissions.map(String))
            return was.has(id) !== is.has(id)
        },

        async updateRole() {
            try {
                this.submitting = true
                this.formErrors = {}

                const payload = {
                    name: this.form.name,
                    slug: this.currentRole.is_critical ? this.currentRole.slug : (this.form.slug || null),
                    description: this.form.description || null,
                    // envoie des IDs normalisés (string) ; si ton backend veut des numbers, parseInt côté API
                    permissions: this.selectedPermissions
                }

                await RolesApi.update(this.role.id, payload)
                this.$emit('updated')
                this.$emit('close')
            } catch (error) {
                console.error('Erreur mise à jour rôle:', error)
                if (error.response?.data?.violations) {
                    error.response.data.violations.forEach(v => {
                        this.formErrors[v.propertyPath] = [v.message]
                    })
                }
            } finally {
                this.submitting = false
            }
        },
        isOpen(category) {
            return this.openCategories.has(category.key || category.name)
        },
        toggleAccordion(category) {
            const k = category.key || category.name
            if (this.openCategories.has(k)) this.openCategories.delete(k)
            else this.openCategories.add(k)
            this.openCategories = new Set(this.openCategories) // force réactivité
        },
        expandAll() {
            const s = new Set()
            this.permissionCategories.forEach(c => s.add(c.key || c.name))
            this.openCategories = s
        },
        collapseAll() {
            this.openCategories = new Set()
        },
    }
}
</script>

<style scoped>
.fade-enter-active,
.fade-leave-active {
    transition: opacity .18s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}

.category-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.category-left {
    display: flex;
    align-items: center;
    gap: .5rem;
}
</style>
