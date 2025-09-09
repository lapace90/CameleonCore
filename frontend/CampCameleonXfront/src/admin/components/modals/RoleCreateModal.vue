<template>
    <div class="modal-overlay" @click.self="$emit('close')">
        <div class="modal-container">
            <div class="modal-header">
                <h3>
                    <i class="fas fa-plus-circle"></i>
                    Créer un rôle
                </h3>
                <button @click="$emit('close')" class="btn-close">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <form @submit.prevent="createRole" class="modal-body">
                <!-- Informations de base -->
                <div class="form-section">
                    <h4>Informations générales</h4>

                    <div class="form-group">
                        <label class="form-label required">Nom du rôle</label>
                        <input v-model="form.name" type="text" class="form-input"
                            placeholder="Ex: Administrateur, Gestionnaire..." required
                            :class="{ error: formErrors.name }" />
                        <span v-if="formErrors.name" class="error-message">{{ formErrors.name[0] }}</span>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Slug</label>
                        <input v-model="form.slug" type="text" class="form-input" placeholder="admin, manager..."
                            :class="{ error: formErrors.slug }" />
                        <span v-if="formErrors.slug" class="error-message">{{ formErrors.slug[0] }}</span>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Description</label>
                        <textarea v-model="form.description" class="form-textarea" rows="3"
                            placeholder="Description du rôle et de ses responsabilités..."
                            :class="{ error: formErrors.description }"></textarea>
                        <span v-if="formErrors.description" class="error-message">{{ formErrors.description[0] }}</span>
                    </div>
                </div>

                <!-- Permissions -->
                <div class="form-section">
                    <h4>
                        <i class="fas fa-key"></i>
                        Permissions ({{ selectedPermissions.length }} sélectionnées)
                    </h4>

                    <!-- États de chargement / vide / erreur -->
                    <div v-if="loadingPermissions" class="form-note">
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
                            <button type="button" @click="resetPermissions" class="btn btn-outline btn-sm"
                                :disabled="originalPermissions.length === 0">
                                Restaurer
                            </button>

                            <div class="spacer"></div>

                            <button type="button" @click="expandAll" class="btn btn-outline btn-sm">
                                Tout développer
                            </button>
                            <button type="button" @click="collapseAll" class="btn btn-outline btn-sm">
                                Tout réduire
                            </button>
                        </div>

                        <!-- Accordéon par catégorie -->
                        <div class="permissions-grid">
                            <div v-for="category in permissionCategories" :key="category.key || category.name"
                                class="permission-category">
                                <div class="category-header" @click="toggleAccordion(category)"
                                    style="cursor: pointer;">
                                    <div class="category-left">
                                        <i :class="`fas ${category.icon || 'fa-key'}`"
                                            :style="{ color: category.color }"></i>
                                        <span class="category-title">
                                            {{ category.name }}
                                            ({{ getCategorySelectedCount(category) }}/{{ category.permissions.length }})
                                        </span>
                                    </div>

                                    <div class="category-right">
                                        <!-- Bouton visible seulement si l'accordéon est ouvert -->
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
                                            :class="{ changed: isPermissionChanged(permission.id) }">
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
                    <button type="submit" class="btn btn-primary btn-sm" :disabled="submitting || !isFormValid">
                        <i v-if="submitting" class="fas fa-spinner fa-spin"></i>
                        {{ submitting ? 'Création...' : 'Créer le rôle' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>

<script>
import RolesApi from '@/services/RolesApi'

export default {
  name: 'RoleCreateModal',
  emits: ['close', 'created'],

  data() {
    return {
      form: { name: '', slug: '', description: '' },
      selectedPermissions: [],
      // vide au départ ; si tu charges un “template de rôle” plus tard, tu pourras le remplir
      originalPermissions: [],
      permissionCategories: [],
      formErrors: {},
      submitting: false,
      loadingPermissions: false,
      loadError: false,

      // accordéon
      openCategories: new Set(),
    }
  },

  computed: {
    isFormValid() {
      return (this.form.name || '').trim().length > 0
    },
  },

  async mounted() {
    await this.loadPermissions()
    this.collapseAll() // fermé par défaut
  },

  methods: {
    toId(v) { return String(v) },

    async loadPermissions() {
      this.loadingPermissions = true
      this.loadError = false
      try {
        // ✅ lit le cache Pinia (évite getActivePinia error grâce à this.$pinia)
        const { useRolesStore } = await import('@/shared/stores/roles')
        const rolesStore = useRolesStore(this.$pinia)
        await rolesStore.ensurePermissions()
        this.permissionCategories = rolesStore.availablePermissions.categories

        // normalisation au cas où
        this.selectedPermissions = this.selectedPermissions.map(String)
        this.originalPermissions = this.originalPermissions.map(String)
      } catch (e) {
        console.error('Erreur chargement permissions:', e)
        this.loadError = true
      } finally {
        this.loadingPermissions = false
      }
    },

    // --- Accordéon
    isOpen(category) {
      return this.openCategories.has(category.key || category.name)
    },
    toggleAccordion(category) {
      const k = category.key || category.name
      if (this.openCategories.has(k)) this.openCategories.delete(k)
      else this.openCategories.add(k)
      // Set → force la réactivité
      this.openCategories = new Set(this.openCategories)
    },
    expandAll() {
      const s = new Set()
      this.permissionCategories.forEach(c => s.add(c.key || c.name))
      this.openCategories = s
    },
    collapseAll() {
      this.openCategories = new Set()
    },

    // --- Sélection
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
      if (allSelected) ids.forEach(id => selected.delete(id))
      else ids.forEach(id => selected.add(id))
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

    // --- Create
    async createRole() {
      try {
        this.submitting = true
        this.formErrors = {}

        const payload = {
          name: this.form.name,
          slug: this.form.slug || '',
          description: this.form.description || '',
          // si tu veux blindé côté backend, tu peux caster en number ici :
          permissions: this.selectedPermissions.map(id => Number(id)),
        }

        await RolesApi.create(payload)
        this.$emit('created')
        this.$emit('close')
      } catch (error) {
        console.error('Erreur création rôle:', error)
        if (error.response?.data?.violations) {
          error.response.data.violations.forEach(v => {
            this.formErrors[v.propertyPath] = [v.message]
          })
        } else if (error.response?.data?.message) {
          this.formErrors._global = [error.response.data.message]
        }
      } finally {
        this.submitting = false
      }
    },
  },
}
</script>


<style scoped>
/* Optionnel: petite transition pour l’accordéon */
.fade-enter-active,
.fade-leave-active {
    transition: opacity .18s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}

/* Align header accordéon */
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

.category-title {
    font-weight: 600;
}

/* Espace flexible dans actions */
.permission-actions {
    display: flex;
    align-items: center;
    gap: .5rem;
    flex-wrap: wrap;
}

.permission-actions .spacer {
    flex: 1 1 auto;
}
</style>
