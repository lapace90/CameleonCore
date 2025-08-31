<template>
  <div class="users-page">
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
          <i class="fas fa-users"></i>
         
        </h1>
        
      </div>
      
      <div class="header-actions">
        <router-link to="/admin/roles" class="btn btn-outline btn-sm">
          <i class="fas fa-shield-alt"></i>
          Gérer les rôles
        </router-link>
        
        <router-link to="/admin/permissions" class="btn btn-outline btn-sm">
          <i class="fas fa-key"></i>
          Permissions
        </router-link>
        
        <router-link to="/admin/users/create" class="btn btn-primary btn-sm">
          <i class="fas fa-plus"></i>
          Nouvel utilisateur
        </router-link>
      </div>
    </div>

    <!-- Statistiques rapides -->
    <div class="users-stats">
      <div class="stat-card">
        <div class="stat-icon">
          <i class="fas fa-users"></i>
        </div>
        <div class="stat-content">
          <div class="stat-number">{{ users.length }}</div>
          <div class="stat-label">Utilisateurs totaux</div>
        </div>
      </div>
      
      <div class="stat-card">
        <div class="stat-icon">
          <i class="fas fa-user-check"></i>
        </div>
        <div class="stat-content">
          <div class="stat-number">{{ activeUsers }}</div>
          <div class="stat-label">Utilisateurs actifs</div>
        </div>
      </div>
      
      <div class="stat-card">
        <div class="stat-icon">
          <i class="fas fa-crown"></i>
        </div>
        <div class="stat-content">
          <div class="stat-number">{{ adminUsers }}</div>
          <div class="stat-label">Administrateurs</div>
        </div>
      </div>
      
      <div class="stat-card">
        <div class="stat-icon">
          <i class="fas fa-user-plus"></i>
        </div>
        <div class="stat-content">
          <div class="stat-number">{{ recentUsers }}</div>
          <div class="stat-label">Nouveaux (7j)</div>
        </div>
      </div>
    </div>

    <!-- Filtres et recherche -->
    <div class="users-table-card">
      <div class="table-header">
        <div class="search-filter">
          <div class="search-box">
            <i class="fas fa-search search-icon"></i>
            <input 
              v-model="searchQuery"
              type="text" 
              placeholder="Rechercher un utilisateur..." 
              class="search-input"
              @input="handleSearch"
            >
          </div>
          
          <div class="filter-options">
            <select v-model="roleFilter" class="filter-select" @change="applyFilters">
              <option value="">Tous les rôles</option>
              <option 
                v-for="role in availableRoles" 
                :key="role.id" 
                :value="role.id"
              >
                {{ role.name }}
              </option>
            </select>
            
            <select v-model="statusFilter" class="filter-select" @change="applyFilters">
              <option value="">Tous les statuts</option>
              <option value="active">Actifs</option>
              <option value="inactive">Suspendus</option>
              <option value="blocked">Bloqués</option>
            </select>
          </div>

          <div class="bulk-actions" v-if="selectedUsers.length > 0">
            <select v-model="bulkAction" class="filter-select">
              <option value="">Actions en masse ({{ selectedUsers.length }})</option>
              <option value="activate">Activer</option>
              <option value="suspend">Suspendre</option>
              <option value="assign-role">Assigner un rôle</option>
              <option value="export">Exporter</option>
            </select>
            
            <button @click="executeBulkAction" class="btn btn-outline btn-sm" :disabled="!bulkAction">
              <i class="fas fa-play"></i>
              Exécuter
            </button>
          </div>

          <div class="results-info">
            {{ filteredUsers.length }} utilisateur(s) trouvé(s)
          </div>
        </div>
      </div>

      <!-- Tableau -->
      <div class="table-container">
        <!-- Loading -->
        <div v-if="loading" class="table-placeholder">
          <i class="fas fa-spinner fa-spin table-icon"></i>
          <h3>Chargement des utilisateurs...</h3>
        </div>

        <!-- Erreur -->
        <div v-else-if="error" class="table-placeholder">
          <i class="fas fa-exclamation-triangle table-icon"></i>
          <h3>{{ error }}</h3>
          <button @click="fetchUsers" class="btn btn-outline btn-sm">
            <i class="fas fa-redo"></i>
            Réessayer
          </button>
        </div>

        <!-- Aucun résultat -->
        <div v-else-if="filteredUsers.length === 0" class="table-placeholder">
          <i class="fas fa-users table-icon"></i>
          <h3>Aucun utilisateur trouvé</h3>
          <p v-if="searchQuery || roleFilter || statusFilter">
            Essayez de modifier vos critères de recherche
          </p>
          <p v-else>
            Commencez par créer votre premier utilisateur
          </p>
          <router-link v-if="!searchQuery && !roleFilter && !statusFilter" to="/admin/users/create" class="btn btn-primary btn-sm">
            <i class="fas fa-plus"></i>
            Créer un utilisateur
          </router-link>
        </div>

        <!-- Tableau des utilisateurs -->
        <table v-else class="users-table table">
          <thead>
            <tr>
              <th width="40">
                <input 
                  type="checkbox" 
                  @change="toggleSelectAll" 
                  :checked="allSelected"
                  :indeterminate="someSelected"
                />
              </th>
              <th>
                <button @click="sortBy('name')" class="sort-button">
                  Utilisateur
                  <i :class="getSortIcon('name')"></i>
                </button>
              </th>
              <th>
                <button @click="sortBy('email')" class="sort-button">
                  Email
                  <i :class="getSortIcon('email')"></i>
                </button>
              </th>
              <th>Rôles</th>
              <th>Statut</th>
              <th>
                <button @click="sortBy('created_at')" class="sort-button">
                  Inscription
                  <i :class="getSortIcon('created_at')"></i>
                </button>
              </th>
              <th>
                <button @click="sortBy('last_login')" class="sort-button">
                  Dernière connexion
                  <i :class="getSortIcon('last_login')"></i>
                </button>
              </th>
              <th width="120">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr 
              v-for="user in paginatedUsers" 
              :key="user.id" 
              class="user-row"
              :class="{ 'selected': selectedUsers.includes(user.id) }"
            >
              <!-- Sélection -->
              <td>
                <input 
                  type="checkbox" 
                  :value="user.id" 
                  v-model="selectedUsers"
                />
              </td>

              <!-- Utilisateur -->
              <td class="user-info-cell">
                <div class="user-info">
                  <div class="user-avatar">
                    <i class="fas fa-user"></i>
                  </div>
                  <div class="user-details">
                    <div class="user-name">{{ user.name }}</div>
                    <div class="user-id">#{{ user.id }}</div>
                  </div>
                </div>
              </td>

              <!-- Email -->
              <td class="user-email">
                <div class="email-info">
                  <span class="email-address">{{ user.email }}</span>
                  <div class="email-status">
                    <i v-if="user.email_verified_at" class="fas fa-check-circle text-success" title="Email vérifié"></i>
                    <i v-else class="fas fa-exclamation-triangle text-warning" title="Email non vérifié"></i>
                  </div>
                </div>
              </td>

              <!-- Rôles -->
              <td class="user-roles">
                <div class="roles-list">
                  <span 
                    v-if="user.role" 
                    class="role-badge role-primary"
                  >
                    {{ user.role.name }}
                  </span>
                  
                  <span 
                    v-for="role in user.additional_roles || []" 
                    :key="role.id"
                    class="role-badge role-secondary"
                  >
                    {{ role.name }}
                  </span>
                  
                  <span v-if="!user.role && (!user.additional_roles || user.additional_roles.length === 0)" 
                        class="text-muted">
                    Aucun rôle
                  </span>
                </div>
              </td>

              <!-- Statut -->
              <td class="user-status">
                <div class="status-indicator" :class="getStatusClass(user.status)">
                  <i :class="getStatusIcon(user.status)"></i>
                  <span>{{ getStatusLabel(user.status) }}</span>
                </div>
              </td>

              <!-- Date d'inscription -->
              <td class="user-created">
                <div class="date-info">
                  <div class="date-primary">{{ formatDate(user.created_at) }}</div>
                  <div class="date-relative">{{ getRelativeTime(user.created_at) }}</div>
                </div>
              </td>

              <!-- Dernière connexion -->
              <td class="user-last-login">
                <div v-if="user.last_login_at" class="date-info">
                  <div class="date-primary">{{ formatDate(user.last_login_at) }}</div>
                  <div class="date-relative">{{ getRelativeTime(user.last_login_at) }}</div>
                </div>
                <div v-else class="text-muted">Jamais</div>
              </td>

              <!-- Actions -->
              <td class="actions-col">
                <div class="action-buttons">
                  <button 
                    @click="viewUser(user)" 
                    class="btn-icon" 
                    title="Voir les détails"
                  >
                    <i class="fas fa-eye"></i>
                  </button>
                  
                  <router-link 
                    :to="`/admin/users/${user.id}/edit`" 
                    class="btn-icon" 
                    title="Modifier"
                  >
                    <i class="fas fa-edit"></i>
                  </router-link>
                  
                  <button 
                    @click="toggleUserStatus(user)" 
                    class="btn-icon" 
                    :title="user.status === 'active' ? 'Suspendre' : 'Activer'"
                  >
                    <i :class="user.status === 'active' ? 'fas fa-pause' : 'fas fa-play'"></i>
                  </button>
                  
                  <button 
                    @click="deleteUser(user)" 
                    class="btn-icon text-danger" 
                    title="Supprimer"
                  >
                    <i class="fas fa-trash"></i>
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>

        <!-- Pagination -->
        <div v-if="totalPages > 1" class="users-pagination">
          <div class="pagination-info">
            Affichage de {{ startItem }} à {{ endItem }} sur {{ filteredUsers.length }} utilisateurs
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

    <!-- Modal de détail utilisateur -->
    <div v-if="showUserModal" class="modal-overlay" @click="closeUserModal">
      <div class="modal-content" @click.stop>
        <div class="modal-header">
          <h3>
            <i class="fas fa-user"></i>
            {{ selectedUser.name }}
          </h3>
          <button @click="closeUserModal" class="btn-close">&times;</button>
        </div>
        
        <div class="modal-body">
          <div class="user-details-modal">
            <div class="detail-section">
              <h4>Informations générales</h4>
              <div class="info-grid">
                <div class="info-item">
                  <strong>Email:</strong> {{ selectedUser.email }}
                </div>
                <div class="info-item">
                  <strong>Statut:</strong> 
                  <span class="status-indicator" :class="getStatusClass(selectedUser.status)">
                    {{ getStatusLabel(selectedUser.status) }}
                  </span>
                </div>
                <div class="info-item">
                  <strong>Inscription:</strong> {{ formatDate(selectedUser.created_at) }}
                </div>
                <div class="info-item">
                  <strong>Dernière connexion:</strong> 
                  {{ selectedUser.last_login_at ? formatDate(selectedUser.last_login_at) : 'Jamais' }}
                </div>
              </div>
            </div>
            
            <div class="detail-section">
              <h4>Rôles et permissions</h4>
              <div class="roles-permissions-detail">
                <div v-if="selectedUser.role" class="role-detail">
                  <h5>Rôle principal</h5>
                  <div class="role-badge role-primary">{{ selectedUser.role.name }}</div>
                </div>
                
                <div v-if="selectedUser.additional_roles && selectedUser.additional_roles.length > 0" class="role-detail">
                  <h5>Rôles additionnels</h5>
                  <div class="roles-list">
                    <span 
                      v-for="role in selectedUser.additional_roles" 
                      :key="role.id"
                      class="role-badge role-secondary"
                    >
                      {{ role.name }}
                    </span>
                  </div>
                </div>
                
                <div v-if="selectedUser.permissions && selectedUser.permissions.length > 0" class="permissions-detail">
                  <h5>Permissions directes</h5>
                  <div class="permissions-list">
                    <span 
                      v-for="permission in selectedUser.permissions" 
                      :key="permission.id"
                      class="permission-badge"
                      :class="getActionClass(permission.action)"
                    >
                      {{ permission.name }}
                    </span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal pour actions en masse -->
    <div v-if="showBulkModal" class="modal-overlay" @click="closeBulkModal">
      <div class="modal-content" @click.stop>
        <div class="modal-header">
          <h3>
            <i class="fas fa-users-cog"></i>
            Action en masse
          </h3>
          <button @click="closeBulkModal" class="btn-close">&times;</button>
        </div>
        
        <div class="modal-body">
          <div class="bulk-action-content">
            <p>
              Vous allez appliquer l'action <strong>{{ getBulkActionLabel() }}</strong> 
              à {{ selectedUsers.length }} utilisateur(s).
            </p>
            
            <!-- Sélection de rôle pour l'action "assign-role" -->
            <div v-if="bulkAction === 'assign-role'" class="form-group">
              <label class="form-label">Sélectionner le rôle à assigner</label>
              <select v-model="bulkRoleId" class="form-input">
                <option value="">Sélectionner un rôle</option>
                <option 
                  v-for="role in availableRoles" 
                  :key="role.id" 
                  :value="role.id"
                >
                  {{ role.name }}
                </option>
              </select>
            </div>
            
            <div class="form-actions">
              <button @click="closeBulkModal" class="btn btn-outline btn-sm">
                Annuler
              </button>
              
              <button 
                @click="confirmBulkAction" 
                class="btn btn-primary btn-sm"
                :disabled="bulkAction === 'assign-role' && !bulkRoleId"
              >
                Confirmer
              </button>
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
  name: 'AdminUsers',
  data() {
    return {
      users: [],
      availableRoles: [],
      loading: true,
      error: null,
      successMessage: null,
      
      // Filtres et recherche
      searchQuery: '',
      roleFilter: '',
      statusFilter: '',
      
      // Tri
      sortField: 'name',
      sortDirection: 'asc',
      
      // Pagination
      currentPage: 1,
      itemsPerPage: 10,
      
      // Sélection multiple
      selectedUsers: [],
      
      // Actions en masse
      bulkAction: '',
      bulkRoleId: '',
      showBulkModal: false,
      
      // Modal détail
      showUserModal: false,
      selectedUser: null
    }
  },
  computed: {
    activeUsers() {
      return this.users.filter(user => user.status === 'active').length
    },
    
    adminUsers() {
      return this.users.filter(user => 
        user.role?.slug === 'admin' || 
        user.additional_roles?.some(role => role.slug === 'admin')
      ).length
    },
    
    recentUsers() {
      const weekAgo = new Date()
      weekAgo.setDate(weekAgo.getDate() - 7)
      return this.users.filter(user => new Date(user.created_at) > weekAgo).length
    },
    
    filteredUsers() {
      let filtered = [...this.users]
      
      // Recherche textuelle
      if (this.searchQuery) {
        const query = this.searchQuery.toLowerCase()
        filtered = filtered.filter(user => 
          user.name.toLowerCase().includes(query) ||
          user.email.toLowerCase().includes(query)
        )
      }
      
      // Filtre par rôle
      if (this.roleFilter) {
        filtered = filtered.filter(user => 
          user.role_id === parseInt(this.roleFilter) ||
          user.additional_roles?.some(role => role.id === parseInt(this.roleFilter))
        )
      }
      
      // Filtre par statut
      if (this.statusFilter) {
        filtered = filtered.filter(user => user.status === this.statusFilter)
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
      return Math.ceil(this.filteredUsers.length / this.itemsPerPage)
    },
    
    paginatedUsers() {
      const start = (this.currentPage - 1) * this.itemsPerPage
      const end = start + this.itemsPerPage
      return this.filteredUsers.slice(start, end)
    },
    
    startItem() {
      return (this.currentPage - 1) * this.itemsPerPage + 1
    },
    
    endItem() {
      return Math.min(this.currentPage * this.itemsPerPage, this.filteredUsers.length)
    },
    
    visiblePages() {
      const pages = []
      const start = Math.max(1, this.currentPage - 2)
      const end = Math.min(this.totalPages, this.currentPage + 2)
      
      for (let i = start; i <= end; i++) {
        pages.push(i)
      }
      
      return pages
    },
    
    allSelected() {
      return this.paginatedUsers.length > 0 && 
             this.selectedUsers.length === this.paginatedUsers.length
    },
    
    someSelected() {
      return this.selectedUsers.length > 0 && 
             this.selectedUsers.length < this.paginatedUsers.length
    }
  },
  created() {
    this.checkSuccessMessage()
    this.fetchUsers()
    this.fetchRoles()
  },
  methods: {
    checkSuccessMessage() {
      if (this.$route.query.success) {
        this.successMessage = this.$route.query.success
        this.$router.replace({ query: {} })
      }
    },

    async fetchUsers() {
      this.loading = true
      this.error = null

      try {
        const response = await axios.get('/api/admin/users')
        this.users = Array.isArray(response.data)
          ? response.data
          : response.data['hydra:member'] || []
      } catch (error) {
        console.error('Erreur lors du chargement des utilisateurs:', error)
        this.error = 'Impossible de charger les utilisateurs'
      } finally {
        this.loading = false
      }
    },

    async fetchRoles() {
      try {
        const response = await axios.get('/api/admin/roles')
        this.availableRoles = Array.isArray(response.data)
          ? response.data
          : response.data['hydra:member'] || []
      } catch (error) {
        console.error('Erreur lors du chargement des rôles:', error)
      }
    },

    // Recherche et filtres
    handleSearch() {
      this.currentPage = 1
      this.selectedUsers = []
    },

    applyFilters() {
      this.currentPage = 1
      this.selectedUsers = []
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

    getSortValue(user, field) {
      switch (field) {
        case 'name':
          return user.name || ''
        case 'email':
          return user.email || ''
        case 'created_at':
          return new Date(user.created_at)
        case 'last_login':
          return user.last_login_at ? new Date(user.last_login_at) : new Date(0)
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

    // Sélection multiple
    toggleSelectAll() {
      if (this.allSelected) {
        this.selectedUsers = []
      } else {
        this.selectedUsers = [...this.paginatedUsers.map(user => user.id)]
      }
    },

    // Actions
    viewUser(user) {
      this.selectedUser = user
      this.showUserModal = true
    },

    closeUserModal() {
      this.showUserModal = false
      this.selectedUser = null
    },

    async toggleUserStatus(user) {
      const newStatus = user.status === 'active' ? 'inactive' : 'active'
      const action = newStatus === 'active' ? 'activer' : 'suspendre'
      
      if (!confirm(`${action} l'utilisateur "${user.name}" ?`)) {
        return
      }

      try {
        await axios.patch(`/api/admin/users/${user.id}/status`, { status: newStatus })
        user.status = newStatus
        this.successMessage = `Utilisateur ${action === 'activer' ? 'activé' : 'suspendu'} avec succès`
      } catch (error) {
        console.error('Erreur lors du changement de statut:', error)
        this.error = 'Impossible de modifier le statut de l\'utilisateur'
      }
    },

    async deleteUser(user) {
      if (!confirm(`Supprimer définitivement l'utilisateur "${user.name}" ?`)) {
        return
      }

      try {
        await axios.delete(`/api/admin/users/${user.id}`)
        this.successMessage = 'Utilisateur supprimé avec succès'
        this.fetchUsers()
      } catch (error) {
        console.error('Erreur lors de la suppression:', error)
        this.error = 'Impossible de supprimer l\'utilisateur'
      }
    },

    // Actions en masse
    executeBulkAction() {
      if (!this.bulkAction || this.selectedUsers.length === 0) {
        return
      }
      
      this.showBulkModal = true
    },

    closeBulkModal() {
      this.showBulkModal = false
      this.bulkRoleId = ''
    },

    getBulkActionLabel() {
      const labels = {
        'activate': 'Activer',
        'suspend': 'Suspendre',
        'assign-role': 'Assigner un rôle',
        'export': 'Exporter'
      }
      return labels[this.bulkAction] || this.bulkAction
    },

    async confirmBulkAction() {
      try {
        const payload = {
          action: this.bulkAction,
          user_ids: this.selectedUsers,
          ...(this.bulkAction === 'assign-role' && { role_id: this.bulkRoleId })
        }

        await axios.post('/api/admin/users/bulk-action', payload)
        
        this.successMessage = `Action "${this.getBulkActionLabel()}" appliquée à ${this.selectedUsers.length} utilisateur(s)`
        this.selectedUsers = []
        this.bulkAction = ''
        this.closeBulkModal()
        this.fetchUsers()
      } catch (error) {
        console.error('Erreur lors de l\'action en masse:', error)
        this.error = 'Erreur lors de l\'exécution de l\'action en masse'
      }
    },

    // Utilitaires
    formatDate(dateString) {
      if (!dateString) return ''
      const date = new Date(dateString)
      return date.toLocaleDateString('fr-FR', {
        day: '2-digit',
        month: '2-digit', 
        year: 'numeric'
      })
    },

    getRelativeTime(dateString) {
      if (!dateString) return ''
      const date = new Date(dateString)
      const now = new Date()
      const diffMs = now - date
      const diffDays = Math.floor(diffMs / (1000 * 60 * 60 * 24))
      
      if (diffDays === 0) return 'Aujourd\'hui'
      if (diffDays === 1) return 'Hier'
      if (diffDays < 7) return `Il y a ${diffDays} jours`
      if (diffDays < 30) return `Il y a ${Math.floor(diffDays / 7)} semaine(s)`
      if (diffDays < 365) return `Il y a ${Math.floor(diffDays / 30)} mois`
      return `Il y a ${Math.floor(diffDays / 365)} an(s)`
    },

    getStatusClass(status) {
      const classes = {
        'active': 'status-active',
        'inactive': 'status-inactive', 
        'blocked': 'status-blocked'
      }
      return classes[status] || 'status-unknown'
    },

    getStatusIcon(status) {
      const icons = {
        'active': 'fas fa-check-circle',
        'inactive': 'fas fa-pause-circle',
        'blocked': 'fas fa-ban'
      }
      return icons[status] || 'fas fa-question-circle'
    },

    getStatusLabel(status) {
      const labels = {
        'active': 'Actif',
        'inactive': 'Suspendu',
        'blocked': 'Bloqué'
      }
      return labels[status] || status
    },

    getActionClass(action) {
      const classes = {
        'create': 'badge-success',
        'read': 'badge-info',
        'update': 'badge-warning',
        'delete': 'badge-danger',
        'admin': 'badge-primary'
      }
      return classes[action.toLowerCase()] || 'badge-secondary'
    }
  }
}
</script>