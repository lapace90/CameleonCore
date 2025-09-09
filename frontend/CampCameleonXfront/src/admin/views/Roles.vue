<template>
  <div class="roles-management">
    <!-- En-tête avec actions -->
    <div class="page-header">
      <div class="header-content">
        <h1 class="page-title">
          <i class="fas fa-shield-alt"></i>
          Gestion des rôles
        </h1>
        <p class="page-subtitle">
          {{ rolesData?.meta?.total || 0 }} rôles • {{ rolesData?.meta?.stats?.total_users || 0 }} utilisateurs assignés
        </p>
        
        <button @click="openCreateModal" class="btn btn-primary btn-sm">
          <i class="fas fa-plus"></i>
          Nouveau rôle
        </button>
      </div>
    </div>

    <!-- Messages de succès/erreur -->
    <div v-if="successMessage" class="alert alert-success">
      <i class="fas fa-check-circle"></i>
      {{ successMessage }}
      <button @click="successMessage = null" class="btn-close">&times;</button>
    </div>

    <div v-if="error" class="alert alert-error">
      <i class="fas fa-exclamation-triangle"></i>
      {{ error }}
      <button @click="error = null" class="btn-close">&times;</button>
    </div>

    <!-- Filtres et recherche -->
    <div class="filters-section">
      <div class="search-box">
        <i class="fas fa-search search-icon"></i>
        <input v-model="searchQuery" type="text" placeholder="Rechercher un rôle..." class="search-input">
      </div>

      <div class="filter-options">
        <select v-model="filterType" class="filter-select">
          <option value="">Tous les rôles</option>
          <option value="critical">Rôles critiques</option>
          <option value="deletable">Modifiables</option>
          <option value="with_users">Avec utilisateurs</option>
        </select>
      </div>

      <div class="results-info">
        {{ filteredRoles.length }} rôle(s) trouvé(s)
      </div>
    </div>
    <!-- Loading -->
    <div v-if="loading" class="loading-container">
      <i class="fas fa-spinner fa-spin"></i>
      <span>Chargement des rôles...</span>
    </div>

    <!-- Grille des rôles -->
    <div v-else class="roles-grid">
      <div v-for="role in filteredRoles" :key="role.id" class="role-card" :class="{ 'critical': role.is_critical }">
        <!-- En-tête de la carte -->
        <div class="card-header" :style="{ borderTopColor: role.color }">
          <div class="role-info">
            <div class="role-icon" :style="{ backgroundColor: role.color }">
              <i :class="`fas ${role.icon}`"></i>
            </div>
            <div class="role-details">
              <h3 class="role-name">{{ role.name }}</h3>
              <p class="role-slug">{{ role.slug }}</p>
            </div>
          </div>

          <div class="role-badges">
            <span v-if="role.is_critical" class="badge badge-critical">
              <i class="fas fa-shield-alt"></i>
              Critique
            </span>
          </div>
        </div>

        <!-- Description -->
        <div class="card-content">
          <p class="role-description">
            {{ role.description || 'Aucune description' }}
          </p>

          <!-- Statistiques -->
          <div class="role-stats">
            <div class="stat-item">
              <i class="fas fa-key"></i>
              <span>{{ role.permissions_count }} permission(s)</span>
            </div>
            <div class="stat-item">
              <i class="fas fa-users"></i>
              <span>{{ role.total_users_count }} utilisateur(s)</span>
            </div>
          </div>

          <!-- Aperçu permissions (3 premières) -->
          <div v-if="role.permissions.length > 0" class="permissions-preview">
            <div class="preview-title">Permissions :</div>
            <div class="permission-tags">
              <span v-for="permission in role.permissions.slice(0, 3)" :key="permission.id" class="permission-tag">
                {{ permission.name }}
              </span>
              <span v-if="role.permissions.length > 3" class="more-tag">
                +{{ role.permissions.length - 3 }} autres
              </span>
            </div>
          </div>

          <div v-else class="no-permissions">
            <i class="fas fa-info-circle"></i>
            Aucune permission assignée
          </div>
        </div>

        <!-- Actions -->
        <div class="card-actions">
          <button @click="openDetailsModal(role)" class="btn btn-outline btn-sm" title="Voir les détails">
            <i class="fas fa-eye"></i>
            Détails
          </button>

          <button @click="openEditModal(role)" class="btn btn-outline btn-sm" title="Modifier">
            <i class="fas fa-edit"></i>
            Modifier
          </button>

          <button @click="openDeleteModal(role)" class="btn btn-danger btn-sm" :disabled="!role.can_be_deleted"
            :title="!role.can_be_deleted ? 'Ce rôle ne peut pas être supprimé' : 'Supprimer'">
            <i class="fas fa-trash"></i>
            Supprimer
          </button>
        </div>
      </div>
    </div>

    <!-- Modal Création/Édition -->
    <div v-if="showRoleModal" class="modal-overlay" @click="closeRoleModal">
      <div class="modal-content modal-large" @click.stop>
        <div class="modal-header">
          <h3>
            <i class="fas fa-shield-alt"></i>
            {{ isEditing ? `Modifier "${currentRole.name}"` : 'Nouveau rôle' }}
          </h3>
          <button @click="closeRoleModal" class="btn-close">&times;</button>
        </div>

        <form @submit.prevent="submitRole" class="modal-body">
          <!-- Informations de base -->
          <div class="form-section">
            <h4>Informations générales</h4>

            <div class="form-grid">
              <div class="form-group">
                <label class="form-label required">Nom du rôle</label>
                <input v-model="roleForm.name" type="text" class="form-input"
                  placeholder="Ex: Administrateur, Gestionnaire..." required :class="{ 'error': formErrors.name }" />
                <span v-if="formErrors.name" class="error-message">{{ formErrors.name[0] }}</span>
              </div>

              <div class="form-group">
                <label class="form-label">Slug (généré automatiquement)</label>
                <input v-model="roleForm.slug" type="text" class="form-input" placeholder="admin, manager..."
                  :class="{ 'error': formErrors.slug }" />
                <span v-if="formErrors.slug" class="error-message">{{ formErrors.slug[0] }}</span>
              </div>
            </div>

            <div class="form-group">
              <label class="form-label">Description</label>
              <textarea v-model="roleForm.description" class="form-textarea" rows="3"
                placeholder="Description du rôle et de ses responsabilités..."
                :class="{ 'error': formErrors.description }"></textarea>
              <span v-if="formErrors.description" class="error-message">{{ formErrors.description[0] }}</span>
            </div>
          </div>

          <!-- Gestion des permissions -->
          <div class="form-section">
            <h4>
              <i class="fas fa-key"></i>
              Permissions ({{ selectedPermissions.length }} sélectionnées)
            </h4>

            <div v-if="allPermissions.length === 0" class="form-note">
              <i class="fas fa-info-circle"></i>
              Aucune permission disponible. Créez d'abord des permissions.
            </div>

            <div v-else class="permissions-section">
              <!-- Recherche permissions -->
              <div class="permission-search">
                <input v-model="permissionSearch" type="text" placeholder="Rechercher une permission..."
                  class="search-input" />
              </div>

              <!-- Actions rapides -->
              <div class="permission-actions">
                <button type="button" @click="selectAllPermissions" class="btn btn-outline btn-sm">
                  Tout sélectionner
                </button>
                <button type="button" @click="clearAllPermissions" class="btn btn-outline btn-sm">
                  Tout désélectionner
                </button>
              </div>

              <!-- Liste des permissions groupées par catégorie -->
              <div class="permissions-grid">
                <div v-for="category in filteredPermissionsByCategory" :key="category.key" class="permission-category">
                  <div class="category-header">
                    <i :class="`fas ${category.icon}`" :style="{ color: category.color }"></i>
                    <span>{{ category.name }} ({{ category.permissions.length }})</span>
                    <button type="button" @click="toggleCategoryPermissions(category)" class="btn btn-outline btn-xs">
                      {{ isCategorySelected(category) ? 'Désélectionner' : 'Sélectionner' }}
                    </button>
                  </div>

                  <div class="permission-items">
                    <label v-for="permission in category.permissions" :key="permission.id" class="permission-checkbox">
                      <input type="checkbox" :value="permission.id" v-model="selectedPermissions" />
                      <span class="checkmark"></span>
                      <span class="permission-name">{{ permission.name }}</span>
                      <span class="permission-action">{{ permission.action }}</span>
                    </label>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Actions du modal -->
          <div class="modal-actions">
            <button type="button" @click="closeRoleModal" class="btn btn-secondary btn-sm">
              Annuler
            </button>
            <button type="submit" class="btn btn-primary btn-sm" :disabled="submitting">
              <i v-if="submitting" class="fas fa-spinner fa-spin"></i>
              {{ submitting ? 'Enregistrement...' : (isEditing ? 'Mettre à jour' : 'Créer') }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Modal Détails -->
    <div v-if="showDetailsModal" class="modal-overlay" @click="closeDetailsModal">
      <div class="modal-content" @click.stop>
        <div class="modal-header">
          <h3>
            <i :class="`fas ${selectedRole.icon}`" :style="{ color: selectedRole.color }"></i>
            {{ selectedRole.name }}
          </h3>
          <button @click="closeDetailsModal" class="btn-close">&times;</button>
        </div>

        <div class="modal-body">
          <div class="role-details-content">
            <!-- Informations générales -->
            <div class="detail-section">
              <h4>Informations générales</h4>
              <div class="detail-grid">
                <div class="detail-item">
                  <span class="label">Nom :</span>
                  <span class="value">{{ selectedRole.name }}</span>
                </div>
                <div class="detail-item">
                  <span class="label">Slug :</span>
                  <span class="value">{{ selectedRole.slug }}</span>
                </div>
                <div class="detail-item">
                  <span class="label">Description :</span>
                  <span class="value">{{ selectedRole.description || 'Aucune description' }}</span>
                </div>
                <div class="detail-item">
                  <span class="label">Statut :</span>
                  <span class="value">
                    <span v-if="selectedRole.is_critical" class="badge badge-critical">
                      <i class="fas fa-shield-alt"></i> Critique
                    </span>
                    <span v-else class="badge badge-normal">Normal</span>
                  </span>
                </div>
              </div>
            </div>

            <!-- Statistiques -->
            <div class="detail-section">
              <h4>Statistiques</h4>
              <div class="stats-grid">
                <div class="stat-card">
                  <i class="fas fa-key"></i>
                  <div>
                    <span class="stat-number">{{ selectedRole.permissions_count }}</span>
                    <span class="stat-label">Permissions</span>
                  </div>
                </div>
                <div class="stat-card">
                  <i class="fas fa-users"></i>
                  <div>
                    <span class="stat-number">{{ selectedRole.total_users_count }}</span>
                    <span class="stat-label">Utilisateurs</span>
                  </div>
                </div>
                <div class="stat-card">
                  <i class="fas fa-user-shield"></i>
                  <div>
                    <span class="stat-number">{{ selectedRole.primary_users_count }}</span>
                    <span class="stat-label">Rôle principal</span>
                  </div>
                </div>
                <div class="stat-card">
                  <i class="fas fa-user-plus"></i>
                  <div>
                    <span class="stat-number">{{ selectedRole.additional_users_count }}</span>
                    <span class="stat-label">Rôle additionnel</span>
                  </div>
                </div>
              </div>
            </div>

            <!-- Permissions -->
            <div v-if="selectedRole.permissions?.length > 0" class="detail-section">
              <h4>Permissions ({{ selectedRole.permissions.length }})</h4>
              <div class="permissions-list">
                <span v-for="permission in selectedRole.permissions" :key="permission.id" class="permission-badge"
                  :class="`permission-${permission.category}`">
                  {{ permission.name }}
                </span>
              </div>
            </div>

            <!-- Utilisateurs -->
            <div v-if="selectedRole.total_users_count > 0" class="detail-section">
              <h4>Utilisateurs assignés</h4>

              <div v-if="selectedRole.primary_users?.length > 0" class="users-subsection">
                <h5>Rôle principal ({{ selectedRole.primary_users.length }})</h5>
                <div class="users-list">
                  <div v-for="user in selectedRole.primary_users" :key="`primary-${user.id}`" class="user-item">
                    <i class="fas fa-user-shield"></i>
                    <div class="user-info">
                      <span class="user-name">{{ user.name }}</span>
                      <span class="user-email">{{ user.email }}</span>
                    </div>
                  </div>
                </div>
              </div>

              <div v-if="selectedRole.users?.length > 0" class="users-subsection">
                <h5>Rôle additionnel ({{ selectedRole.users.length }})</h5>
                <div class="users-list">
                  <div v-for="user in selectedRole.users" :key="`additional-${user.id}`" class="user-item">
                    <i class="fas fa-user-plus"></i>
                    <div class="user-info">
                      <span class="user-name">{{ user.name }}</span>
                      <span class="user-email">{{ user.email }}</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal Suppression -->
    <div v-if="showDeleteModal" class="modal-overlay" @click="closeDeleteModal">
      <div class="modal-content modal-sm" @click.stop>
        <div class="modal-header">
          <h3 class="text-danger">
            <i class="fas fa-exclamation-triangle"></i>
            Supprimer le rôle
          </h3>
          <button @click="closeDeleteModal" class="btn-close">&times;</button>
        </div>

        <div class="modal-body">
          <p>
            Êtes-vous sûr de vouloir supprimer le rôle
            <strong>{{ roleToDelete?.name }}</strong> ?
          </p>

          <div class="warning-box">
            <i class="fas fa-info-circle"></i>
            Cette action est irréversible. Toutes les permissions associées seront détachées.
          </div>
        </div>

        <div class="modal-actions">
          <button @click="closeDeleteModal" class="btn btn-secondary btn-sm">
            Annuler
          </button>
          <button @click="confirmDelete" class="btn btn-danger btn-sm" :disabled="deleting">
            <i v-if="deleting" class="fas fa-spinner fa-spin"></i>
            {{ deleting ? 'Suppression...' : 'Supprimer' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios'

export default {
  name: 'RolesManagement',

  data() {
    return {
      // Data
      rolesData: null,
      allPermissions: [],
      loading: true,
      error: null,
      successMessage: null,

      // Filtres
      searchQuery: '',
      filterType: '',

      // Modals
      showRoleModal: false,
      showDetailsModal: false,
      showDeleteModal: false,
      isEditing: false,
      currentRole: null,
      selectedRole: null,
      roleToDelete: null,

      // Formulaire
      roleForm: {
        name: '',
        slug: '',
        description: '',
        permissions: []
      },
      selectedPermissions: [],
      permissionSearch: '',
      formErrors: {},
      submitting: false,
      deleting: false,
    }
  },

  computed: {
    roles() {
      return this.rolesData?.data || []
    },

    filteredRoles() {
      let filtered = this.roles

      // Filtre par recherche
      if (this.searchQuery) {
        const query = this.searchQuery.toLowerCase()
        filtered = filtered.filter(role =>
          role.name.toLowerCase().includes(query) ||
          role.slug.toLowerCase().includes(query) ||
          role.description?.toLowerCase().includes(query)
        )
      }

      // Filtre par type
      if (this.filterType) {
        switch (this.filterType) {
          case 'critical':
            filtered = filtered.filter(role => role.is_critical)
            break
          case 'deletable':
            filtered = filtered.filter(role => role.can_be_deleted)
            break
          case 'with_users':
            filtered = filtered.filter(role => role.total_users_count > 0)
            break
        }
      }

      return filtered
    },

    // Permissions groupées par catégorie pour le formulaire
    filteredPermissionsByCategory() {
      const grouped = {}

      this.allPermissions.forEach(permission => {
        if (!this.permissionSearch ||
          permission.name.toLowerCase().includes(this.permissionSearch.toLowerCase()) ||
          permission.action.toLowerCase().includes(this.permissionSearch.toLowerCase())) {

          const category = permission.category || 'other'
          if (!grouped[category]) {
            grouped[category] = {
              key: category,
              name: this.getCategoryName(category),
              icon: this.getCategoryIcon(category),
              color: this.getCategoryColor(category),
              permissions: []
            }
          }
          grouped[category].permissions.push(permission)
        }
      })

      return Object.values(grouped).sort((a, b) => a.name.localeCompare(b.name))
    }
  },

  async mounted() {
    await this.loadRoles()
    await this.loadPermissions()
  },

  methods: {
    // ===========================
    // CHARGEMENT DONNÉES
    // ===========================

    async loadRoles() {
      try {
        this.loading = true
        this.error = null

        const response = await axios.get('/api/roles', {
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
            'Accept': 'application/json'
          }
        })

        this.rolesData = response.data

      } catch (error) {
        console.error('Erreur chargement rôles:', error)
        this.error = 'Impossible de charger les rôles'
      } finally {
        this.loading = false
      }
    },

    async loadPermissions() {
      try {
        const response = await axios.get('/api/permissions', {
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
            'Accept': 'application/json'
          }
        })

        // Aplatir les catégories pour avoir une liste simple
        this.allPermissions = []
        if (response.data?.data?.categories) {
          response.data.data.categories.forEach(category => {
            this.allPermissions.push(...category.permissions)
          })
        }

      } catch (error) {
        console.error('Erreur chargement permissions:', error)
      }
    },

    // ===========================
    // GESTION MODALS
    // ===========================

    openCreateModal() {
      this.isEditing = false
      this.currentRole = null
      this.resetForm()
      this.showRoleModal = true
    },

    openEditModal(role) {
      this.isEditing = true
      this.currentRole = role
      this.fillForm(role)
      this.showRoleModal = true
    },

    openDetailsModal(role) {
      this.selectedRole = role
      this.showDetailsModal = true
    },

    openDeleteModal(role) {
      this.roleToDelete = role
      this.showDeleteModal = true
    },

    closeRoleModal() {
      this.showRoleModal = false
      this.resetForm()
    },

    closeDetailsModal() {
      this.showDetailsModal = false
      this.selectedRole = null
    },

    closeDeleteModal() {
      this.showDeleteModal = false
      this.roleToDelete = null
    },

    // ===========================
    // GESTION FORMULAIRE
    // ===========================

    resetForm() {
      this.roleForm = {
        name: '',
        slug: '',
        description: '',
        permissions: []
      }
      this.selectedPermissions = []
      this.formErrors = {}
    },

    fillForm(role) {
      this.roleForm = {
        name: role.name || '',
        slug: role.slug || '',
        description: role.description || '',
        permissions: role.permissions?.map(p => p.id) || []
      }
      this.selectedPermissions = role.permissions?.map(p => p.id) || []
      this.formErrors = {}
    },

    // ===========================
    // GESTION PERMISSIONS
    // ===========================

    selectAllPermissions() {
      this.selectedPermissions = this.allPermissions.map(p => p.id)
    },

    clearAllPermissions() {
      this.selectedPermissions = []
    },

    toggleCategoryPermissions(category) {
      const categoryPermissionIds = category.permissions.map(p => p.id)
      const isSelected = this.isCategorySelected(category)

      if (isSelected) {
        // Désélectionner toutes les permissions de cette catégorie
        this.selectedPermissions = this.selectedPermissions.filter(id =>
          !categoryPermissionIds.includes(id)
        )
      } else {
        // Sélectionner toutes les permissions de cette catégorie
        const newPermissions = categoryPermissionIds.filter(id =>
          !this.selectedPermissions.includes(id)
        )
        this.selectedPermissions.push(...newPermissions)
      }
    },

    isCategorySelected(category) {
      const categoryPermissionIds = category.permissions.map(p => p.id)
      return categoryPermissionIds.every(id => this.selectedPermissions.includes(id))
    },

    // ===========================
    // CRUD OPERATIONS
    // ===========================

    async submitRole() {
      try {
        this.submitting = true
        this.formErrors = {}

        const payload = {
          name: this.roleForm.name,
          slug: this.roleForm.slug || null,
          description: this.roleForm.description || null,
          permissions: this.selectedPermissions
        }

        let response
        if (this.isEditing) {
          response = await axios.put(`/api/roles/${this.currentRole.id}`, payload, {
            headers: {
              'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
              'Accept': 'application/json',
              'Content-Type': 'application/json'
            }
          })
          this.successMessage = `Rôle "${payload.name}" mis à jour avec succès`
        } else {
          response = await axios.post('/api/roles', payload, {
            headers: {
              'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
              'Accept': 'application/json',
              'Content-Type': 'application/json'
            }
          })
          this.successMessage = `Rôle "${payload.name}" créé avec succès`
        }

        this.closeRoleModal()
        await this.loadRoles()

        // Auto-hide success message
        setTimeout(() => {
          this.successMessage = null
        }, 5000)

      } catch (error) {
        console.error('Erreur sauvegarde rôle:', error)

        if (error.response?.data?.violations) {
          // Erreurs de validation API Platform
          error.response.data.violations.forEach(violation => {
            this.formErrors[violation.propertyPath] = [violation.message]
          })
        } else {
          this.error = error.response?.data?.message || 'Erreur lors de la sauvegarde'
        }
      } finally {
        this.submitting = false
      }
    },

    async confirmDelete() {
      try {
        this.deleting = true

        await axios.delete(`/api/roles/${this.roleToDelete.id}`, {
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
            'Accept': 'application/json'
          }
        })

        this.successMessage = `Rôle "${this.roleToDelete.name}" supprimé avec succès`
        this.closeDeleteModal()
        await this.loadRoles()

        // Auto-hide success message
        setTimeout(() => {
          this.successMessage = null
        }, 5000)

      } catch (error) {
        console.error('Erreur suppression rôle:', error)
        this.error = error.response?.data?.message || 'Erreur lors de la suppression'
      } finally {
        this.deleting = false
      }
    },

    // ===========================
    // UTILITAIRES UI
    // ===========================

    getCategoryName(category) {
      const names = {
        'system': 'Système',
        'users': 'Utilisateurs',
        'accommodations': 'Hébergements',
        'activities': 'Activités',
        'bookings': 'Réservations',
        'reception': 'Réception',
        'customers': 'Clients',
        'restaurant': 'Restaurant',
        'finance': 'Finance',
        'analytics': 'Analyses',
        'communication': 'Communication',
        'other': 'Autres'
      }
      return names[category] || category
    },

    getCategoryIcon(category) {
      const icons = {
        'system': 'fa-cogs',
        'users': 'fa-users',
        'accommodations': 'fa-home',
        'activities': 'fa-hiking',
        'bookings': 'fa-calendar-alt',
        'reception': 'fa-concierge-bell',
        'customers': 'fa-address-book',
        'restaurant': 'fa-utensils',
        'finance': 'fa-coins',
        'analytics': 'fa-chart-bar',
        'communication': 'fa-comments',
        'other': 'fa-ellipsis-h'
      }
      return icons[category] || 'fa-key'
    },

    getCategoryColor(category) {
      const colors = {
        'system': '#dc2626',
        'users': '#7c3aed',
        'accommodations': '#059669',
        'activities': '#ea580c',
        'bookings': '#0891b2',
        'reception': '#be123c',
        'customers': '#4338ca',
        'restaurant': '#ca8a04',
        'finance': '#16a34a',
        'analytics': '#0f766e',
        'communication': '#9333ea',
        'other': '#6b7280'
      }
      return colors[category] || '#6b7280'
    }
  }
}
</script>