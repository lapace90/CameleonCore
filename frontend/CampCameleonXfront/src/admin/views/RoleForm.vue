<template>
  <div class="product-form-container">
    <!-- Message d'erreur -->
    <div v-if="error" class="error-message">
      <div class="alert alert-danger">
        <AppIcon name="triangle-alert" />
        {{ error }}
        <button @click="error = null" class="btn-close">&times;</button>
      </div>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="loading-state">
      <LoadingState 
        state="loading" 
        loading-text="Chargement des données..." 
        :container-class="'py-5'"
      />
    </div>

    <!-- Contenu principal -->
    <div v-else>
      <!-- Header -->
      <div class="form-header">
        <div class="header-navigation">
          <router-link to="/admin/roles" class="back-link">
            <AppIcon name="arrow-left" />
            Retour aux rôles
          </router-link>
          <div class="breadcrumb">
            <span>Rôles</span>
            <AppIcon name="chevron-right" />
            <span>{{ isEditing ? role?.name : 'Nouveau rôle' }}</span>
          </div>
        </div>
      </div>

      <!-- Titre -->
      <div class="page-title-section">
        <div class="product-type-badge" style="background-color: #7c3aed;">
          <AppIcon name="shield" />
          Rôle
        </div>
        <h1 class="page-title">
          {{ isEditing ? `Modifier "${role?.name}"` : 'Nouveau rôle' }}
        </h1>
      </div>

      <!-- Formulaire principal -->
      <form @submit.prevent="submitForm" class="product-form">
        <div class="form-content">
          <!-- Informations du rôle -->
          <div class="form-right">
            <div class="form-section">
              <h3>Informations générales</h3>
              <div class="form-grid">
                <div class="form-group">
                  <label class="form-label required">Nom du rôle</label>
                  <input 
                    v-model="form.name" 
                    type="text" 
                    class="form-input" 
                    placeholder="Ex: Administrateur, Gestionnaire..."
                    required
                    :class="{ 'error': errors.name }"
                  />
                  <span v-if="errors.name" class="error-message">{{ errors.name }}</span>
                </div>

                <div class="form-group">
                  <label class="form-label">Slug (généré automatiquement)</label>
                  <input 
                    v-model="form.slug" 
                    type="text" 
                    class="form-input" 
                    placeholder="admin, manager..."
                    :class="{ 'error': errors.slug }"
                  />
                  <span v-if="errors.slug" class="error-message">{{ errors.slug }}</span>
                </div>

                <div class="form-group full-width">
                  <label class="form-label">Description</label>
                  <textarea 
                    v-model="form.description" 
                    class="form-textarea" 
                    rows="3"
                    placeholder="Description du rôle et de ses responsabilités..."
                    :class="{ 'error': errors.description }"
                  ></textarea>
                  <span v-if="errors.description" class="error-message">{{ errors.description }}</span>
                </div>
              </div>
            </div>

            <!-- Gestion des permissions -->
            <div class="form-section">
              <h3>
                <AppIcon name="key" />
                Permissions
              </h3>
              
              <div v-if="permissions.length === 0" class="form-note">
                <AppIcon name="info" />
                Aucune permission disponible. Créez d'abord des permissions dans la section dédiée.
              </div>

              <div v-else class="permissions-manager">
                <!-- Actions en masse -->
                <div class="permissions-actions">
                  <button 
                    type="button" 
                    @click="selectAllPermissions" 
                    class="btn btn-outline btn-sm"
                  >
                    <AppIcon name="square-check" />
                    Tout sélectionner
                  </button>
                  
                  <button 
                    type="button" 
                    @click="unselectAllPermissions" 
                    class="btn btn-outline btn-sm"
                  >
                    <AppIcon name="square" />
                    Tout désélectionner
                  </button>

                  <div class="selected-count">
                    {{ form.permissions.length }} permission(s) sélectionnée(s)
                  </div>
                </div>

                <!-- Liste des permissions par catégorie -->
                <div class="permissions-categories">
                  <div 
                    v-for="(categoryPerms, category) in permissionsByCategory" 
                    :key="category" 
                    class="permission-category"
                  >
                    <div class="category-header">
                      <h4 class="category-title">
                        <AppIcon :name="getCategoryIcon(category)" />
                        {{ getCategoryLabel(category) }}
                      </h4>
                      
                      <div class="category-actions">
                        <button 
                          type="button" 
                          @click="selectCategoryPermissions(category)" 
                          class="btn btn-ghost btn-sm"
                        >
                          <AppIcon name="check" />
                          Tout
                        </button>
                        
                        <button 
                          type="button" 
                          @click="unselectCategoryPermissions(category)" 
                          class="btn btn-ghost btn-sm"
                        >
                          <AppIcon name="x" />
                          Rien
                        </button>
                      </div>
                    </div>

                    <div class="permissions-grid">
                      <label 
                        v-for="permission in categoryPerms" 
                        :key="permission.id" 
                        class="permission-item"
                        :class="{ 'selected': form.permissions.includes(permission.id) }"
                      >
                        <input 
                          type="checkbox" 
                          :value="permission.id" 
                          v-model="form.permissions"
                          class="permission-checkbox"
                        />
                        
                        <div class="permission-card">
                          <div class="permission-header">
                            <span class="permission-name">{{ permission.name }}</span>
                            <div class="permission-badge" :class="getActionClass(permission.action)">
                              {{ permission.action }}
                            </div>
                          </div>
                          
                          <div class="permission-description" v-if="permission.description">
                            {{ permission.description }}
                          </div>
                        </div>
                      </label>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Aperçu des utilisateurs avec ce rôle -->
            <div v-if="isEditing && role?.users_count > 0" class="form-section">
              <h3>
                <AppIcon name="users" />
                Utilisateurs concernés
              </h3>
              
              <div class="users-preview">
                <div class="users-count">
                  <AppIcon name="user" />
                  {{ role.users_count }} utilisateur(s) ont ce rôle
                </div>
                
                <div v-if="role?.users && role.users.length > 0" class="users-list">
                  <div 
                    v-for="user in role.users.slice(0, 5)" 
                    :key="user.id" 
                    class="user-item"
                  >
                    <div class="user-avatar">
                      <AppIcon name="user" />
                    </div>
                    <div class="user-info">
                      <span class="user-name">{{ user.name }}</span>
                      <span class="user-email">{{ user.email }}</span>
                    </div>
                  </div>
                  
                  <div v-if="role.users.length > 5" class="users-more">
                    et {{ role.users.length - 5 }} autre(s)...
                  </div>
                </div>
              </div>
            </div>

            <!-- Actions -->
            <div class="form-actions">
              <div class="actions-left">
                <button 
                  type="button" 
                  @click="resetForm" 
                  class="btn btn-outline btn-sm"
                >
                  <AppIcon name="undo-2" />
                  Réinitialiser
                </button>
              </div>
              
              <div class="actions-right">
                <button 
                  v-if="isEditing && role?.users_count === 0" 
                  type="button" 
                  @click="deleteRole" 
                  class="btn btn-danger btn-sm"
                  :disabled="saving"
                >
                  <AppIcon name="trash-2" />
                  Supprimer
                </button>
                
                <button 
                  type="submit" 
                  class="btn btn-primary btn-sm" 
                  :disabled="saving"
                >
                  <AppIcon name="loader-circle" :spin="true" v-if="saving" />
                  <AppIcon name="check" v-else />
                  {{ isEditing ? 'Mettre à jour' : 'Créer le rôle' }}
                </button>
              </div>
            </div>
          </div>
        </div>
      </form>
    </div>
  </div>
</template>

<script>
import httpClient from '@/services/httpClient'
import LoadingState from '@/admin/components/ui/LoadingState.vue'

export default {
  name: 'RoleForm',
  components: { LoadingState },
  data() {
    return {
      loading: false,
      saving: false,
      error: null,
      role: null,
      permissions: [],
      form: {
        name: '',
        slug: '',
        description: '',
        permissions: []
      },
      errors: {}
    }
  },
  computed: {
    isEditing() {
      return !!this.$route.params.id
    },
    roleId() {
      return this.$route.params.id
    },
    permissionsByCategory() {
      // Grouper les permissions par catégorie basée sur leur action
      const categories = {}
      
      this.permissions.forEach(permission => {
        const category = this.getPermissionCategory(permission.action)
        if (!categories[category]) {
          categories[category] = []
        }
        categories[category].push(permission)
      })
      
      return categories
    }
  },
  watch: {
    'form.name'(newName) {
      // Auto-générer le slug à partir du nom
      if (newName && !this.isEditing) {
        this.form.slug = this.generateSlug(newName)
      }
    }
  },
  async created() {
    await this.loadPermissions()
    if (this.isEditing) {
      await this.loadRole()
    }
  },
  methods: {
    async loadPermissions() {
      try {
        const response = await httpClient.get('/permissions')
        this.permissions = Array.isArray(response.data) 
          ? response.data 
          : response.data['hydra:member'] || []
      } catch (error) {
        console.error('Erreur lors du chargement des permissions:', error)
        this.error = 'Impossible de charger les permissions'
      }
    },

    async loadRole() {
      this.loading = true
      try {
        const response = await httpClient.get(`/roles/${this.roleId}`)
        this.role = response.data
        
        // Remplir le formulaire
        this.form = {
          name: this.role.name || '',
          slug: this.role.slug || '',
          description: this.role.description || '',
          permissions: this.role.permissions?.map(perm => perm.id) || []
        }
      } catch (error) {
        console.error('Erreur lors du chargement du rôle:', error)
        this.error = 'Impossible de charger les données du rôle'
      } finally {
        this.loading = false
      }
    },

    async submitForm() {
      this.saving = true
      this.errors = {}
      
      try {
        const url = this.isEditing
          ? `/roles/${this.roleId}`
          : '/roles'

        const method = this.isEditing ? 'put' : 'post'

        const response = await httpClient[method](url, this.form)
        
        // Redirection avec message de succès
        this.$router.push({
          path: '/admin/roles',
          query: { 
            success: this.isEditing ? 'Rôle modifié avec succès' : 'Rôle créé avec succès' 
          }
        })
      } catch (error) {
        console.error('Erreur lors de la sauvegarde:', error)
        
        if (error.response?.status === 422 && error.response.data.errors) {
          this.errors = error.response.data.errors
        } else {
          this.error = error.response?.data?.message || 'Erreur lors de la sauvegarde'
        }
      } finally {
        this.saving = false
      }
    },

    async deleteRole() {
      if (!confirm('Êtes-vous sûr de vouloir supprimer ce rôle ?')) {
        return
      }
      
      this.saving = true
      try {
        await httpClient.delete(`/roles/${this.roleId}`)
        this.$router.push({
          path: '/admin/roles',
          query: { success: 'Rôle supprimé avec succès' }
        })
      } catch (error) {
        console.error('Erreur lors de la suppression:', error)
        this.error = 'Impossible de supprimer le rôle'
      } finally {
        this.saving = false
      }
    },

    resetForm() {
      if (this.isEditing) {
        this.loadRole()
      } else {
        this.form = {
          name: '',
          slug: '',
          description: '',
          permissions: []
        }
      }
      this.errors = {}
    },

    // Gestion des permissions
    selectAllPermissions() {
      this.form.permissions = this.permissions.map(p => p.id)
    },

    unselectAllPermissions() {
      this.form.permissions = []
    },

    selectCategoryPermissions(category) {
      const categoryPermIds = this.permissionsByCategory[category].map(p => p.id)
      
      // Ajouter les permissions de cette catégorie si elles ne sont pas déjà sélectionnées
      categoryPermIds.forEach(id => {
        if (!this.form.permissions.includes(id)) {
          this.form.permissions.push(id)
        }
      })
    },

    unselectCategoryPermissions(category) {
      const categoryPermIds = this.permissionsByCategory[category].map(p => p.id)
      
      // Retirer les permissions de cette catégorie
      this.form.permissions = this.form.permissions.filter(id => 
        !categoryPermIds.includes(id)
      )
    },

    // Utilitaires
    generateSlug(name) {
      return name
        .toLowerCase()
        .replace(/[^\w\s-]/g, '') // Supprimer les caractères spéciaux
        .replace(/\s+/g, '-') // Remplacer les espaces par des tirets
        .replace(/--+/g, '-') // Supprimer les doubles tirets
        .trim()
    },

    getPermissionCategory(action) {
      const categories = {
        'create': 'create',
        'read': 'read', 
        'view': 'read',
        'list': 'read',
        'update': 'update',
        'edit': 'update',
        'delete': 'delete',
        'destroy': 'delete',
        'manage': 'admin',
        'admin': 'admin'
      }
      
      return categories[action.toLowerCase()] || 'other'
    },

    getCategoryLabel(category) {
      const labels = {
        'create': 'Création',
        'read': 'Lecture / Consultation',
        'update': 'Modification',
        'delete': 'Suppression',
        'admin': 'Administration',
        'other': 'Autres'
      }
      
      return labels[category] || category
    },

    getCategoryIcon(category) {
      const icons = {
        'create': 'plus',
        'read': 'eye',
        'update': 'pencil',
        'delete': 'trash-2',
        'admin': 'settings',
        'other': 'key'
      }
      
      return icons[category] || 'key'
    },

    getActionClass(action) {
      const classes = {
        'create': 'badge-success',
        'read': 'badge-info',
        'view': 'badge-info',
        'list': 'badge-info',
        'update': 'badge-warning',
        'edit': 'badge-warning',
        'delete': 'badge-danger',
        'destroy': 'badge-danger',
        'manage': 'badge-primary',
        'admin': 'badge-primary'
      }
      
      return classes[action.toLowerCase()] || 'badge-secondary'
    }
  }
}
</script>