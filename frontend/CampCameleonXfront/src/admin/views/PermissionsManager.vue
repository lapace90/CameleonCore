<template>
  <div class="permissions-page">
    <!-- Message de succès -->
    <div v-if="successMessage" class="alert alert-success">
      <i class="fas fa-check-circle"></i>
      {{ successMessage }}
      <button @click="successMessage = null" class="btn-close">&times;</button>
    </div>

    <!-- En-tête -->
    <div class="page-header">
      <div class="header-content">
        <h1 class="page-title">
          <i class="fas fa-key"></i>
          Gestion des permissions
        </h1>
        <p class="page-subtitle">
          Définissez les permissions disponibles dans le système
        </p>
      </div>
      
      <div class="header-actions">
        <button @click="showCreateForm = true" class="btn btn-primary btn-sm">
          <i class="fas fa-plus"></i>
          Nouvelle permission
        </button>
        
        <button @click="generateStandardPermissions" class="btn btn-outline btn-sm">
          <i class="fas fa-magic"></i>
          Générer permissions standards
        </button>
      </div>
    </div>

    <!-- Statistiques -->
    <div class="permissions-stats">
      <div class="stat-card">
        <div class="stat-icon">
          <i class="fas fa-key"></i>
        </div>
        <div class="stat-content">
          <div class="stat-number">{{ permissions.length }}</div>
          <div class="stat-label">Permissions totales</div>
        </div>
      </div>
      
      <div class="stat-card">
        <div class="stat-icon">
          <i class="fas fa-plus"></i>
        </div>
        <div class="stat-content">
          <div class="stat-number">{{ getPermissionsByAction('create').length }}</div>
          <div class="stat-label">Permissions Création</div>
        </div>
      </div>
      
      <div class="stat-card">
        <div class="stat-icon">
          <i class="fas fa-eye"></i>
        </div>
        <div class="stat-content">
          <div class="stat-number">{{ getPermissionsByAction('read').length }}</div>
          <div class="stat-label">Permissions Lecture</div>
        </div>
      </div>
      
      <div class="stat-card">
        <div class="stat-icon">
          <i class="fas fa-edit"></i>
        </div>
        <div class="stat-content">
          <div class="stat-number">{{ getPermissionsByAction('update').length }}</div>
          <div class="stat-label">Permissions Modification</div>
        </div>
      </div>
      
      <div class="stat-card">
        <div class="stat-icon">
          <i class="fas fa-trash"></i>
        </div>
        <div class="stat-content">
          <div class="stat-number">{{ getPermissionsByAction('delete').length }}</div>
          <div class="stat-label">Permissions Suppression</div>
        </div>
      </div>
    </div>

    <!-- Formulaire de création/édition -->
    <div v-if="showCreateForm || editingPermission" class="form-modal-overlay" @click="closeForm">
      <div class="form-modal" @click.stop>
        <div class="form-header">
          <h3>
            <i class="fas fa-key"></i>
            {{ editingPermission ? 'Modifier la permission' : 'Nouvelle permission' }}
          </h3>
          <button @click="closeForm" class="btn-close">&times;</button>
        </div>
        
        <form @submit.prevent="savePermission" class="permission-form">
          <div class="form-group">
            <label class="form-label required">Nom de la permission</label>
            <input 
              v-model="form.name" 
              type="text" 
              class="form-input" 
              placeholder="Ex: Créer des utilisateurs"
              required
              :class="{ 'error': errors.name }"
            />
            <span v-if="errors.name" class="error-message">{{ errors.name }}</span>
          </div>

          <div class="form-group">
            <label class="form-label required">Action</label>
            <select 
              v-model="form.action" 
              class="form-input" 
              required
              :class="{ 'error': errors.action }"
            >
              <option value="">Sélectionner une action</option>
              <optgroup label="Actions CRUD">
                <option value="create">create</option>
                <option value="read">read</option>
                <option value="update">update</option>
                <option value="delete">delete</option>
              </optgroup>
              <optgroup label="Actions spécifiques">
                <option value="list">list</option>
                <option value="view">view</option>
                <option value="edit">edit</option>
                <option value="destroy">destroy</option>
                <option value="manage">manage</option>
                <option value="admin">admin</option>
              </optgroup>
              <optgroup label="Actions métier">
                <option value="publish">publish</option>
                <option value="approve">approve</option>
                <option value="export">export</option>
                <option value="import">import</option>
              </optgroup>
            </select>
            <span v-if="errors.action" class="error-message">{{ errors.action }}</span>
          </div>

          <div class="form-group">
            <label class="form-label">Ressource concernée</label>
            <input 
              v-model="form.resource" 
              type="text" 
              class="form-input" 
              placeholder="Ex: users, products, orders..."
            />
            <div class="form-text">
              Optionnel : spécifiez la ressource concernée par cette permission
            </div>
          </div>

          <div class="form-actions">
            <button type="button" @click="closeForm" class="btn btn-outline btn-sm">
              <i class="fas fa-times"></i>
              Annuler
            </button>
            
            <button type="submit" class="btn btn-primary btn-sm" :disabled="saving">
              <i v-if="saving" class="fas fa-spinner fa-spin"></i>
              <i v-else class="fas fa-check"></i>
              {{ editingPermission ? 'Mettre à jour' : 'Créer' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Liste des permissions -->
    <div class="permissions-table-card">
      <div class="table-header">
        <div class="search-filter">
          <div class="search-box">
            <i class="fas fa-search search-icon"></i>
            <input 
              v-model="searchQuery"
              type="text" 
              placeholder="Rechercher une permission..." 
              class="search-input"
            >
          </div>
          
          <select v-model="actionFilter" class="filter-select">
            <option value="">Toutes les actions</option>
            <option value="create">Création</option>
            <option value="read">Lecture</option>
            <option value="update">Modification</option>
            <option value="delete">Suppression</option>
            <option value="admin">Administration</option>
          </select>

          <div class="results-info">
            {{ filteredPermissions.length }} permission(s)
          </div>
        </div>
      </div>

      <div class="table-container">
        <!-- Loading -->
        <div v-if="loading" class="table-placeholder">
          <i class="fas fa-spinner fa-spin table-icon"></i>
          <h3>Chargement des permissions...</h3>
        </div>

        <!-- Erreur -->
        <div v-else-if="error" class="table-placeholder">
          <i class="fas fa-exclamation-triangle table-icon"></i>
          <h3>{{ error }}</h3>
          <button @click="fetchPermissions" class="btn btn-outline btn-sm">
            <i class="fas fa-redo"></i>
            Réessayer
          </button>
        </div>

        <!-- Aucun résultat -->
        <div v-else-if="filteredPermissions.length === 0" class="table-placeholder">
          <i class="fas fa-key table-icon"></i>
          <h3>Aucune permission trouvée</h3>
          <p v-if="searchQuery || actionFilter">
            Essayez de modifier vos critères de recherche
          </p>
          <p v-else>
            Commencez par créer vos premières permissions
          </p>
        </div>

        <!-- Permissions groupées par action -->
        <div v-else class="permissions-categories">
          <div 
            v-for="(categoryPerms, category) in permissionsByCategory" 
            :key="category"
            class="permission-category"
          >
            <div class="category-header">
              <h3 class="category-title">
                <i :class="getCategoryIcon(category)"></i>
                {{ getCategoryLabel(category) }}
                <span class="category-count">({{ categoryPerms.length }})</span>
              </h3>
              
              <div class="category-actions">
                <button 
                  @click="expandedCategories[category] = !expandedCategories[category]"
                  class="btn btn-ghost btn-sm"
                >
                  <i :class="expandedCategories[category] ? 'fas fa-chevron-up' : 'fas fa-chevron-down'"></i>
                </button>
              </div>
            </div>

            <!-- Liste des permissions de cette catégorie -->
            <div v-if="expandedCategories[category] !== false" class="permissions-list">
              <div 
                v-for="permission in categoryPerms" 
                :key="permission.id"
                class="permission-item"
              >
                <div class="permission-info">
                  <div class="permission-name">{{ permission.name }}</div>
                  <div class="permission-details">
                    <span class="permission-action" :class="getActionClass(permission.action)">
                      {{ permission.action }}
                    </span>
                    <span v-if="permission.resource" class="permission-resource">
                      → {{ permission.resource }}
                    </span>
                  </div>
                </div>
                
                <div class="permission-usage" v-if="permission.roles_count !== undefined">
                  <i class="fas fa-shield-alt"></i>
                  {{ permission.roles_count || 0 }} rôle(s)
                </div>
                
                <div class="permission-actions">
                  <button 
                    @click="editPermission(permission)" 
                    class="btn-icon" 
                    title="Modifier"
                  >
                    <i class="fas fa-edit"></i>
                  </button>
                  
                  <button 
                    @click="deletePermission(permission)" 
                    class="btn-icon text-danger" 
                    title="Supprimer"
                    :disabled="permission.roles_count > 0"
                  >
                    <i class="fas fa-trash"></i>
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios'

export default {
  name: 'PermissionsManager',
  data() {
    return {
      permissions: [],
      loading: true,
      saving: false,
      error: null,
      successMessage: null,
      
      // Formulaire
      showCreateForm: false,
      editingPermission: null,
      form: {
        name: '',
        action: '',
        resource: ''
      },
      errors: {},
      
      // Filtres
      searchQuery: '',
      actionFilter: '',
      
      // UI
      expandedCategories: {}
    }
  },
  computed: {
    filteredPermissions() {
      let filtered = [...this.permissions]
      
      if (this.searchQuery) {
        const query = this.searchQuery.toLowerCase()
        filtered = filtered.filter(perm => 
          perm.name.toLowerCase().includes(query) ||
          perm.action.toLowerCase().includes(query) ||
          (perm.resource && perm.resource.toLowerCase().includes(query))
        )
      }
      
      if (this.actionFilter) {
        filtered = filtered.filter(perm => 
          perm.action.toLowerCase().includes(this.actionFilter.toLowerCase())
        )
      }
      
      return filtered.sort((a, b) => a.name.localeCompare(b.name))
    },
    
    permissionsByCategory() {
      const categories = {}
      
      this.filteredPermissions.forEach(permission => {
        const category = this.getPermissionCategory(permission.action)
        if (!categories[category]) {
          categories[category] = []
        }
        categories[category].push(permission)
      })
      
      return categories
    }
  },
  created() {
    this.checkSuccessMessage()
    this.fetchPermissions()
    
    // Toutes les catégories sont ouvertes par défaut
    this.expandedCategories = {
      create: true,
      read: true,
      update: true,
      delete: true,
      admin: true,
      other: true
    }
  },
  methods: {
    checkSuccessMessage() {
      if (this.$route.query.success) {
        this.successMessage = this.$route.query.success
        this.$router.replace({ query: {} })
      }
    },

    async fetchPermissions() {
      this.loading = true
      this.error = null

      try {
        const response = await axios.get('/api/admin/permissions')
        this.permissions = Array.isArray(response.data)
          ? response.data
          : response.data['hydra:member'] || []
      } catch (error) {
        console.error('Erreur lors du chargement des permissions:', error)
        this.error = 'Impossible de charger les permissions'
      } finally {
        this.loading = false
      }
    },

    async generateStandardPermissions() {
      if (!confirm('Générer les permissions CRUD standards ? Cela ajoutera des permissions pour les ressources communes.')) {
        return
      }

      const standardPermissions = [
        // Utilisateurs
        { name: 'Créer des utilisateurs', action: 'create', resource: 'users' },
        { name: 'Voir les utilisateurs', action: 'read', resource: 'users' },
        { name: 'Modifier les utilisateurs', action: 'update', resource: 'users' },
        { name: 'Supprimer les utilisateurs', action: 'delete', resource: 'users' },
        
        // Rôles
        { name: 'Gérer les rôles', action: 'manage', resource: 'roles' },
        { name: 'Créer des rôles', action: 'create', resource: 'roles' },
        { name: 'Modifier les rôles', action: 'update', resource: 'roles' },
        
        // Produits
        { name: 'Créer des produits', action: 'create', resource: 'products' },
        { name: 'Voir les produits', action: 'read', resource: 'products' },
        { name: 'Modifier les produits', action: 'update', resource: 'products' },
        { name: 'Supprimer les produits', action: 'delete', resource: 'products' },
        
        // Administration
        { name: 'Administration générale', action: 'admin', resource: 'system' },
        { name: 'Voir les statistiques', action: 'read', resource: 'analytics' },
        { name: 'Exporter les données', action: 'export', resource: 'data' }
      ]

      try {
        const promises = standardPermissions.map(perm => 
          axios.post('/api/admin/permissions', perm).catch(err => {
            // Ignorer les erreurs de doublons
            if (err.response?.status !== 422) {
              throw err
            }
          })
        )
        
        await Promise.all(promises)
        
        this.successMessage = 'Permissions standards générées avec succès'
        this.fetchPermissions()
      } catch (error) {
        console.error('Erreur lors de la génération:', error)
        this.error = 'Erreur lors de la génération des permissions standards'
      }
    },

    // Formulaire
    editPermission(permission) {
      this.editingPermission = permission
      this.form = {
        name: permission.name,
        action: permission.action,
        resource: permission.resource || ''
      }
      this.errors = {}
    },

    closeForm() {
      this.showCreateForm = false
      this.editingPermission = null
      this.form = {
        name: '',
        action: '',
        resource: ''
      }
      this.errors = {}
    },

    async savePermission() {
      this.saving = true
      this.errors = {}
      
      try {
        if (this.editingPermission) {
          await axios.put(`/api/admin/permissions/${this.editingPermission.id}`, this.form)
          this.successMessage = 'Permission modifiée avec succès'
        } else {
          await axios.post('/api/admin/permissions', this.form)
          this.successMessage = 'Permission créée avec succès'
        }
        
        this.closeForm()
        this.fetchPermissions()
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

    async deletePermission(permission) {
      if (permission.roles_count > 0) {
        alert('Impossible de supprimer une permission utilisée par des rôles')
        return
      }

      if (!confirm(`Supprimer la permission "${permission.name}" ?`)) {
        return
      }

      try {
        await axios.delete(`/api/admin/permissions/${permission.id}`)
        this.successMessage = 'Permission supprimée avec succès'
        this.fetchPermissions()
      } catch (error) {
        console.error('Erreur lors de la suppression:', error)
        this.error = 'Impossible de supprimer la permission'
      }
    },

    // Utilitaires
    getPermissionsByAction(action) {
      return this.permissions.filter(perm => 
        perm.action.toLowerCase().includes(action.toLowerCase())
      )
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
        'admin': 'admin',
        'export': 'other',
        'import': 'other',
        'publish': 'other',
        'approve': 'other'
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
        'other': 'Autres actions'
      }
      
      return labels[category] || category
    },

    getCategoryIcon(category) {
      const icons = {
        'create': 'fas fa-plus',
        'read': 'fas fa-eye',
        'update': 'fas fa-edit',
        'delete': 'fas fa-trash',
        'admin': 'fas fa-cog',
        'other': 'fas fa-star'
      }
      
      return icons[category] || 'fas fa-key'
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
        'admin': 'badge-primary',
        'export': 'badge-secondary',
        'import': 'badge-secondary',
        'publish': 'badge-success',
        'approve': 'badge-success'
      }
      
      return classes[action.toLowerCase()] || 'badge-secondary'
    }
  }
}
</script>