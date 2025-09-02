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
          Utilisateurs
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

    <!-- Statistiques -->
    <UserStats :total-users="users.length" :active-users="activeUsers" :admin-users="adminUsers"
      :recent-users="recentUsers" />

    <!-- Filtres -->
    <UserFilters v-model:search-query="searchQuery" v-model:role-filter="roleFilter"
      v-model:status-filter="statusFilter" v-model:bulk-action="bulkAction" :available-roles="availableRoles"
      :selected-users="selectedUsers" :filtered-count="filteredUsers.length" @execute-bulk-action="executeBulkAction" />

    <!-- Tableau -->
    <UserTable :users="filteredUsers" v-model:selected-users="selectedUsers" :loading="loading" :error="error"
      :has-filters="hasFilters" :sort-field="sortField" :sort-direction="sortDirection" :current-page="currentPage"
      :items-per-page="itemsPerPage" @sort="sortBy" @view-user="viewUser" @toggle-status="toggleUserStatus"
      @delete-user="deleteUser" @retry="fetchUsers" @page-change="currentPage = $event" />

    <!-- Modal détail utilisateur -->
    <UserModal :show="showUserModal" :user="selectedUser" @close="closeUserModal" />

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
                <option v-for="role in availableRoles" :key="role.id" :value="role.id">
                  {{ role.name }}
                </option>
              </select>
            </div>

            <div class="form-actions">
              <button @click="closeBulkModal" class="btn btn-outline btn-sm">
                Annuler
              </button>

              <button @click="confirmBulkAction" class="btn btn-primary btn-sm"
                :disabled="bulkAction === 'assign-role' && !bulkRoleId">
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
import UserStats from './components/UserStats.vue'
import UserFilters from './components/UserFilters.vue'
import UserTable from './components/UserTable.vue'
import UserModal from './components/UserModal.vue'
import UsersApi from '@/services/UsersApi'

export default {
  name: 'AdminUsers',
  components: {
    UserStats,
    UserFilters,
    UserTable,
    UserModal
  },
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

    hasFilters() {
      return !!(this.searchQuery || this.roleFilter || this.statusFilter)
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
    }
  },
  watch: {
    searchQuery() {
      this.currentPage = 1
      this.selectedUsers = []
    },
    roleFilter() {
      this.currentPage = 1
      this.selectedUsers = []
    },
    statusFilter() {
      this.currentPage = 1
      this.selectedUsers = []
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
        this.users = await UsersApi.getAll()
      } catch (error) {
        console.error('Erreur lors du chargement des utilisateurs:', error)
        this.error = 'Impossible de charger les utilisateurs'
      } finally {
        this.loading = false
      }
    },

    async fetchRoles() {
      try {
        this.availableRoles = await UsersApi.getRoles()
      } catch (error) {
        console.error('Erreur lors du chargement des rôles:', error)
      }
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
    await UsersApi.toggleStatus(user.id, newStatus)
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
        await UsersApi.delete(user.id)
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

        await UsersApi.bulkAction(payload)

        this.successMessage = `Action "${this.getBulkActionLabel()}" appliquée à ${this.selectedUsers.length} utilisateur(s)`
        this.selectedUsers = []
        this.bulkAction = ''
        this.closeBulkModal()
        this.fetchUsers()
      } catch (error) {
        console.error('Erreur lors de l\'action en masse:', error)
        this.error = 'Erreur lors de l\'exécution de l\'action en masse'
      }
    }
  }
}
</script>