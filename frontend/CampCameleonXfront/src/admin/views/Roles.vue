<template>
  <div class="roles-page">
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
          <i class="fas fa-shield-alt"></i>
          Gestion des rôles
        </h1>
        <p class="page-subtitle">
          Créez et gérez les rôles utilisateurs avec leurs permissions
        </p>
      </div>
      
      <div class="header-actions">
        <router-link to="/admin/roles/create" class="btn btn-primary btn-sm">
          <i class="fas fa-plus"></i>
          Nouveau rôle
        </router-link>
      </div>
    </div>

    <!-- Filtres et recherche -->
    <div class="roles-table-card">
      <div class="table-header">
        <div class="search-filter">
          <div class="search-box">
            <i class="fas fa-search search-icon"></i>
            <input 
              v-model="searchQuery"
              type="text" 
              placeholder="Rechercher un rôle..." 
              class="search-input"
              @input="handleSearch"
            >
          </div>
          
          <div class="filter-options">
            <select v-model="permissionFilter" class="filter-select" @change="applyFilters">
              <option value="">Toutes les permissions</option>
              <option value="create">Création</option>
              <option value="read">Lecture</option>
              <option value="update">Modification</option>
              <option value="delete">Suppression</option>
              <option value="admin">Administration</option>
            </select>
          </div>

          <div class="results-info">
            {{ filteredRoles.length }} rôle(s) trouvé(s)
          </div>
        </div>
      </div>

      <!-- Tableau -->
      <div class="table-container">
        <!-- Loading -->
        <div v-if="loading" class="table-placeholder">
          <i class="fas fa-spinner fa-spin table-icon"></i>
          <h3>Chargement des rôles...</h3>
        </div>

        <!-- Erreur -->
        <div v-else-if="error" class="table-placeholder">
          <i class="fas fa-exclamation-triangle table-icon"></i>
          <h3>{{ error }}</h3>
          <button @click="fetchRoles" class="btn btn-outline btn-sm">
            <i class="fas fa-redo"></i>
            Réessayer
          </button>
        </div>

        <!-- Aucun résultat -->
        <div v-else-if="filteredRoles.length === 0" class="table-placeholder">
          <i class="fas fa-shield-alt table-icon"></i>
          <h3>Aucun rôle trouvé</h3>
          <p v-if="searchQuery || permissionFilter">
            Essayez de modifier vos critères de recherche
          </p>
          <p v-else>
            Commencez par créer votre premier rôle
          </p>
          <router-link v-if="!searchQuery && !permissionFilter" to="/admin/roles/create" class="btn btn-primary btn-sm">
            <i class="fas fa-plus"></i>
            Créer un rôle
          </router-link>
        </div>

        <!-- Tableau des rôles -->
        <table v-else class="roles-table table">
          <thead>
            <tr>
              <th>
                <button @click="sortBy('name')" class="sort-button">
                  Rôle
                  <i :class="getSortIcon('name')"></i>
                </button>
              </th>
              <th>Description</th>
              <th>
                <button @click="sortBy('permissions_count')" class="sort-button">
                  Permissions
                  <i :class="getSortIcon('permissions_count')"></i>
                </button>
              </th>
              <th>
                <button @click="sortBy('users_count')" class="sort-button">
                  Utilisateurs
                  <i :class="getSortIcon('users_count')"></i>
                </button>
              </th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="role in paginatedRoles" :key="role.id" class="role-row">
              <!-- Nom et slug -->
              <td class="role-name-cell">
                <div class="role-info">
                  <div class="role-name">{{ role.name }}</div>
                  <div class="role-slug">{{ role.slug }}</div>
                </div>
              </td>

              <!-- Description -->
              <td class="role-description">
                <div v-if="role.description" class="description-text">
                  {{ truncateText(role.description, 100) }}
                </div>
                <div v-else class="text-muted">—</div>
              </td>

              <!-- Permissions -->
              <td class="role-permissions">
                <div class="permissions-summary">
                  <div class="permissions-count">
                    <i class="fas fa-key"></i>
                    {{ role.permissions_count || 0 }} permission(s)
                  </div>
                  
                  <div v-if="role.permissions && role.permissions.length > 0" class="permissions-preview">
                    <span 
                      v-for="permission in role.permissions.slice(0, 3)" 
                      :key="permission.id"
                      class="permission-badge"
                      :class="getActionClass(permission.action)"
                    >
                      {{ permission.action }}
                    </span>
                    
                    <span v-if="role.permissions.length > 3" class="permissions-more">
                      +{{ role.permissions.length - 3 }}
                    </span>
                  </div>
                </div>
              </td>

              <!-- Utilisateurs -->
              <td class="role-users">
                <div class="users-count">
                  <i class="fas fa-users"></i>
                  {{ role.users_count || 0 }} utilisateur(s)
                </div>
                
                <div v-if="role.users && role.users.length > 0" class="users-preview">
                  <div 
                    v-for="user in role.users.slice(0, 2)" 
                    :key="user.id"
                    class="user-avatar-small" 
                    :title="user.name"
                  >
                    <i class="fas fa-user"></i>
                  </div>
                  
                  <span v-if="role.users.length > 2" class="users-more">
                    +{{ role.users.length - 2 }}
                  </span>
                </div>
              </td>

              <!-- Actions -->
              <td class="actions-col">
                <div class="action-buttons">
                  <button 
                    @click="viewRole(role)" 
                    class="btn-icon" 
                    title="Voir les détails"
                  >
                    <i class="fas fa-eye"></i>
                  </button>
                  
                  <router-link 
                    :to="`/admin/roles/${role.id}/edit`" 
                    class="btn-icon" 
                    title="Modifier"
                  >
                    <i class="fas fa-edit"></i>
                  </router-link>
                  
                  <button 
                    @click="duplicateRole(role)" 
                    class="btn-icon" 
                    title="Dupliquer"
                  >
                    <i class="fas fa-copy"></i>
                  </button>
                  
                  <button 
                    @click="deleteRole(role)" 
                    class="btn-icon text-danger" 
                    title="Supprimer"
                    :disabled="role.users_count > 0"
                  >
                    <i class="fas fa-trash"></i>
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>

        <!-- Pagination -->
        <div v-if="totalPages > 1" class="roles-pagination">
          <div class="pagination-info">
            Affichage de {{ startItem }} à {{ endItem }} sur {{ filteredRoles.length }} rôles
          </div>
          
          <div class="pagination-controls">
            <button 
              @click="currentPage--" 
              :disabled="currentPage === 1" 
              class="btn btn-outline btn-sm"
            >
              <i class="fas fa-chevron-left"></i>
            </button>
            
            <div class="pagination-pages">
              <button 
                v-for="page in visiblePages" 
                :key="page"
                @click="currentPage = page"
                class="btn btn-sm"
                :class="page === currentPage ? 'btn-primary' : 'btn-outline'"
              >
                {{ page }}
              </button>
            </div>
            
            <button 
              @click="currentPage++" 
              :disabled="currentPage === totalPages" 
              class="btn btn-outline btn-sm"
            >
              <i class="fas fa-chevron-right"></i>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal de détail (optionnel) -->
    <div v-if="showRoleModal" class="modal-overlay" @click="closeRoleModal">
      <div class="modal-content" @click.stop>
        <div class="modal-header">
          <h3>
            <i class="fas fa-shield-alt"></i>
            {{ selectedRole.name }}
          </h3>
          <button @click="closeRoleModal" class="btn-close">&times;</button>
        </div>
        
        <div class="modal-body">
          <div class="role-details">
            <div class="detail-section">
              <h4>Description</h4>
              <p>{{ selectedRole.description || 'Aucune description' }}</p>
            </div>
            
            <div class="detail-section">
              <h4>Permissions ({{ selectedRole.permissions?.length || 0 }})</h4>
              <div class="permissions-list">
                <span 
                  v-for="permission in selectedRole.permissions" 
                  :key="permission.id"
                  class="permission-badge"
                  :class="getActionClass(permission.action)"
                >
                  {{ permission.name }}
                </span>
              </div>
            </div>
            
            <div class="detail-section">
              <h4>Utilisateurs ({{ selectedRole.users?.length || 0 }})</h4>
              <div class="users-list">
                <div 
                  v-for="user in selectedRole.users" 
                  :key="user.id"
                  class="user-item"
                >
                  <i class="fas fa-user"></i>
                  <span>{{ user.name }} ({{ user.email }})</span>
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
  name: 'AdminRoles',
  data() {
    return {
      roles: [],
      loading: true,
      error: null,
      successMessage: null,
      
      // Filtres et recherche
      searchQuery: '',
      permissionFilter: '',
      
      // Tri
      sortField: 'name',
      sortDirection: 'asc',
      
      // Pagination
      currentPage: 1,
      itemsPerPage: 10,
      
      // Modal
      showRoleModal: false,
      selectedRole: null
    }
  },
  computed: {
    filteredRoles() {
      let filtered = [...this.roles]
      
      // Recherche textuelle
      if (this.searchQuery) {
        const query = this.searchQuery.toLowerCase()
        filtered = filtered.filter(role => 
          role.name.toLowerCase().includes(query) ||
          role.slug.toLowerCase().includes(query) ||
          (role.description && role.description.toLowerCase().includes(query))
        )
      }
      
      // Filtre par permission
      if (this.permissionFilter) {
        filtered = filtered.filter(role => 
          role.permissions && role.permissions.some(perm => 
            perm.action.toLowerCase().includes(this.permissionFilter.toLowerCase())
          )
        )
      }
      
      // Tri
      filtered.sort((a, b) => {
        let aValue = this.getSortValue(a, this.sortField)
        let bValue = this.getSortValue(b, this.sortField)
        
        if (typeof aValue === 'string') {
          aValue = aValue.toLowerCase()
          bValue = bValue.toLowerCase()
        }
        
        if (this.sortDirection === 'asc') {
          return aValue > bValue ? 1 : -1
        } else {
          return aValue < bValue ? 1 : -1
        }
      })
      
      return filtered
    },
    
    totalPages() {
      return Math.ceil(this.filteredRoles.length / this.itemsPerPage)
    },
    
    paginatedRoles() {
      const start = (this.currentPage - 1) * this.itemsPerPage
      const end = start + this.itemsPerPage
      return this.filteredRoles.slice(start, end)
    },
    
    startItem() {
      return (this.currentPage - 1) * this.itemsPerPage + 1
    },
    
    endItem() {
      return Math.min(this.currentPage * this.itemsPerPage, this.filteredRoles.length)
    },
    
    visiblePages() {
      const pages = []
      const start = Math.max(1, this.currentPage - 2)
      const end = Math.min(this.totalPages, this.currentPage + 2)
      
      for (let i = start; i <= end; i++) {
        pages.push(i)
      }
      
      return pages
    }
  },
  created() {
    this.checkSuccessMessage()
    this.fetchRoles()
  },
  methods: {
    checkSuccessMessage() {
      if (this.$route.query.success) {
        this.successMessage = this.$route.query.success
        
        // Supprimer le paramètre de l'URL
        this.$router.replace({ query: {} })
      }
    },

    async fetchRoles() {
      this.loading = true
      this.error = null

      try {
        const response = await axios.get('/api/admin/roles')
        this.roles = Array.isArray(response.data)
          ? response.data
          : response.data['hydra:member'] || []
      } catch (error) {
        console.error('Erreur lors du chargement des rôles:', error)
        this.error = 'Impossible de charger les rôles'
      } finally {
        this.loading = false
      }
    },

    // Recherche avec debounce
    handleSearch() {
      this.currentPage = 1
    },

    applyFilters() {
      this.currentPage = 1
    },

    // Tri
    sortBy(field) {
      if (this.sortField === field) {
        this.sortDirection = this.sortDirection === 'asc' ? 'desc' : 'asc'
      } else {
        this.sortField = field
        this.sortDirection = 'asc'
      }
    },

    getSortValue(role, field) {
      switch (field) {
        case 'name':
          return role.name || ''
        case 'permissions_count':
          return role.permissions_count || 0
        case 'users_count':
          return role.users_count || 0
        default:
          return ''
      }
    },

    getSortIcon(field) {
      if (this.sortField !== field) {
        return 'fas fa-sort text-muted'
      }
      return this.sortDirection === 'asc' 
        ? 'fas fa-sort-up' 
        : 'fas fa-sort-down'
    },

    // Actions
    viewRole(role) {
      this.selectedRole = role
      this.showRoleModal = true
    },

    closeRoleModal() {
      this.showRoleModal = false
      this.selectedRole = null
    },

    async duplicateRole(role) {
      if (!confirm(`Dupliquer le rôle "${role.name}" ?`)) {
        return
      }

      try {
        const duplicatedRole = {
          name: `${role.name} (copie)`,
          slug: `${role.slug}-copy`,
          description: role.description,
          permissions: role.permissions?.map(p => p.id) || []
        }

        await axios.post('/api/admin/roles', duplicatedRole)
        this.successMessage = 'Rôle dupliqué avec succès'
        this.fetchRoles()
      } catch (error) {
        console.error('Erreur lors de la duplication:', error)
        this.error = 'Impossible de dupliquer le rôle'
      }
    },

    async deleteRole(role) {
      if (role.users_count > 0) {
        alert('Impossible de supprimer un rôle assigné à des utilisateurs')
        return
      }

      if (!confirm(`Supprimer définitivement le rôle "${role.name}" ?`)) {
        return
      }

      try {
        await axios.delete(`/api/admin/roles/${role.id}`)
        this.successMessage = 'Rôle supprimé avec succès'
        this.fetchRoles()
      } catch (error) {
        console.error('Erreur lors de la suppression:', error)
        this.error = 'Impossible de supprimer le rôle'
      }
    },

    // Utilitaires
    truncateText(text, length) {
      if (!text) return ''
      return text.length > length ? text.substring(0, length) + '...' : text
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