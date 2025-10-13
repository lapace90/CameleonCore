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
    <UserStats 
      :total-users="users.length" 
      :active-users="activeUsers" 
      :admin-users="adminUsers"
      :recent-users="recentUsers" 
    />

    <!--  AdminFilterBar au lieu de UserFilters -->
    <AdminFilterBar
      v-model="filters"
      :default-filters="defaultFilters"
      :fields="userFilterFields"
      search-placeholder="Rechercher un utilisateur (nom, email)..."
      reset-label="Réinitialiser"
      @apply="applyFilters"
    >
      <!-- Slot actions : Bulk actions -->
      <template #actions>
        <div v-if="selectedUsers.length > 0" class="bulk-actions-group">
          <select v-model="bulkAction" class="filter-select">
            <option value="">Actions ({{ selectedUsers.length }})</option>
            <option value="activate">Activer</option>
            <option value="suspend">Suspendre</option>
            <option value="assign-role">Assigner un rôle</option>
            <option value="export">Exporter</option>
          </select>
          
          <button 
            @click="executeBulkAction" 
            class="btn btn-outline btn-sm"
            :disabled="!bulkAction"
          >
            <i class="fas fa-play"></i>
            Exécuter
          </button>
        </div>
      </template>

      <!-- Slot results personnalisé -->
      <template #results="{ activeCount }">
        <span class="results-info">
          <i class="fas fa-users"></i>
          {{ filteredUsers.length }} utilisateur(s)
          <span v-if="activeCount > 0" class="text-muted">
            · {{ activeCount }} filtre(s)
          </span>
        </span>
      </template>
    </AdminFilterBar>

    <!-- Tableau -->
    <UserTable 
      :users="filteredUsers" 
      v-model:selected-users="selectedUsers" 
      :loading="loading" 
      :error="error"
      :has-filters="hasFilters" 
      :sort-field="sortField" 
      :sort-direction="sortDirection" 
      :current-page="currentPage"
      :items-per-page="itemsPerPage" 
      @sort="sortBy" 
      @view-user="viewUser" 
      @toggle-status="toggleUserStatus"
      @delete-user="deleteUser" 
      @retry="fetchUsers" 
      @page-change="currentPage = $event" 
    />

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

            <div v-if="bulkAction === 'assign-role'" class="form-group">
              <label>Sélectionner un rôle</label>
              <select v-model="selectedRoleForBulk" class="form-control">
                <option value="">-- Choisir un rôle --</option>
                <option v-for="role in availableRoles" :key="role.id" :value="role.id">
                  {{ role.name }}
                </option>
              </select>
            </div>
          </div>
        </div>

        <div class="modal-footer">
          <button @click="closeBulkModal" class="btn btn-outline">Annuler</button>
          <button @click="confirmBulkAction" class="btn btn-primary">Confirmer</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import UserStats from './components/UserStats.vue'
import UserTable from './components/UserTable.vue'
import UserModal from './components/UserModal.vue'
import AdminFilterBar from '@/admin/components/ui/AdminFilterBar.vue'
import UsersApi from '@/services/UsersApi'
import RolesApi from '@/services/RolesApi'

export default {
  name: 'Users',
  components: {
    UserStats,
    UserTable,
    UserModal,
    AdminFilterBar
  },

  data() {
    return {
      users: [],
      selectedUsers: [],
      loading: false,
      error: null,
      successMessage: null,

      // ✅ Filtres avec valeurs par défaut
      defaultFilters: {
        search: '',
        role: '',
        status: ''
      },

      filters: {
        search: '',
        role: '',
        status: ''
      },

      // Bulk actions
      bulkAction: '',
      selectedRoleForBulk: '',
      showBulkModal: false,

      // Tri
      sortField: 'created_at',
      sortDirection: 'desc',

      // Pagination
      currentPage: 1,
      itemsPerPage: 25,

      // Modal
      showUserModal: false,
      selectedUser: null,

      // Roles
      availableRoles: []
    }
  },

  computed: {
    // ✅ Configuration des champs de filtres
    userFilterFields() {
      return [
        {
          key: 'role',
          type: 'select',
          placeholder: 'Tous les rôles',
          options: this.availableRoles.map(role => ({
            value: role.id,
            label: role.name
          }))
        },
        {
          key: 'status',
          type: 'select',
          placeholder: 'Tous les statuts',
          options: [
            { value: 'active', label: 'Actifs' },
            { value: 'inactive', label: 'Suspendus' },
            { value: 'blocked', label: 'Bloqués' }
          ]
        }
      ]
    },

    hasFilters() {
      return !!(this.filters.search || this.filters.role || this.filters.status)
    },

    filteredUsers() {
      let filtered = [...this.users]

      // Recherche textuelle
      if (this.filters.search) {
        const query = this.filters.search.toLowerCase()
        filtered = filtered.filter(user =>
          user.name.toLowerCase().includes(query) ||
          user.email.toLowerCase().includes(query)
        )
      }

      // Filtre par rôle
      if (this.filters.role) {
        filtered = filtered.filter(user =>
          user.role_id === parseInt(this.filters.role) ||
          user.additional_roles?.some(role => role.id === parseInt(this.filters.role))
        )
      }

      // Filtre par statut
      if (this.filters.status) {
        filtered = filtered.filter(user => user.status === this.filters.status)
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

    activeUsers() {
      return this.users.filter(u => u.status === 'active').length
    },

    adminUsers() {
      return this.users.filter(u => u.role?.name === 'Admin' || u.role?.slug === 'admin').length
    },

    recentUsers() {
      const weekAgo = new Date()
      weekAgo.setDate(weekAgo.getDate() - 7)
      return this.users.filter(u => new Date(u.created_at) > weekAgo).length
    }
  },

  async mounted() {
    await this.fetchUsers()
    await this.fetchRoles()
  },

  methods: {
    //  Utiliser UsersApi au lieu de AdminApi
    async fetchUsers() {
      this.loading = true
      this.error = null

      try {
        const users = await UsersApi.getAll()
        this.users = Array.isArray(users) ? users : []
      } catch (error) {
        console.error('Erreur chargement utilisateurs:', error)
        this.error = 'Erreur lors du chargement des utilisateurs'
      } finally {
        this.loading = false
      }
    },

    //  Utiliser RolesApi au lieu de AdminApi
    async fetchRoles() {
      try {
        const roles = await RolesApi.getAll()
        // Normaliser la réponse
        if (roles && roles['hydra:member']) {
          this.availableRoles = roles['hydra:member']
        } else if (roles && roles.data) {
          this.availableRoles = roles.data
        } else if (Array.isArray(roles)) {
          this.availableRoles = roles
        } else {
          this.availableRoles = []
        }
      } catch (error) {
        console.error('Erreur chargement rôles:', error)
      }
    },

    applyFilters() {
      // Les filtres sont déjà appliqués via computed filteredUsers
      // Cette méthode peut être utilisée pour des actions supplémentaires
      this.currentPage = 1
    },

    sortBy(field) {
      if (this.sortField === field) {
        this.sortDirection = this.sortDirection === 'asc' ? 'desc' : 'asc'
      } else {
        this.sortField = field
        this.sortDirection = 'asc'
      }
    },

    getSortValue(user, field) {
      if (field === 'role') {
        return user.role?.name || ''
      }
      return user[field] || ''
    },

    viewUser(user) {
      this.selectedUser = user
      this.showUserModal = true
    },

    closeUserModal() {
      this.showUserModal = false
      this.selectedUser = null
    },

    //  Utiliser UsersApi
    async toggleUserStatus(user) {
      try {
        const newStatus = user.status === 'active' ? 'inactive' : 'active'
        await UsersApi.update(user.id, { status: newStatus })
        
        user.status = newStatus
        this.successMessage = `Statut de ${user.name} modifié avec succès`
        
        setTimeout(() => {
          this.successMessage = null
        }, 3000)
      } catch (error) {
        console.error('Erreur modification statut:', error)
        this.error = 'Erreur lors de la modification du statut'
      }
    },

    //  Utiliser UsersApi
    async deleteUser(user) {
      if (!confirm(`Êtes-vous sûr de vouloir supprimer ${user.name} ?`)) {
        return
      }

      try {
        await UsersApi.delete(user.id)
        this.users = this.users.filter(u => u.id !== user.id)
        this.successMessage = `${user.name} supprimé avec succès`
        
        setTimeout(() => {
          this.successMessage = null
        }, 3000)
      } catch (error) {
        console.error('Erreur suppression utilisateur:', error)
        this.error = 'Erreur lors de la suppression'
      }
    },

    // ✅ Bulk actions (à adapter selon ton API backend)
    executeBulkAction() {
      if (!this.bulkAction) return

      if (this.bulkAction === 'assign-role') {
        this.showBulkModal = true
      } else {
        this.confirmBulkAction()
      }
    },

    async confirmBulkAction() {
      try {
        const userIds = this.selectedUsers.map(u => u.id)

        switch (this.bulkAction) {
          case 'activate':
            // Tu devras implémenter ces méthodes dans UsersApi si nécessaire
            for (const id of userIds) {
              await UsersApi.update(id, { status: 'active' })
            }
            this.successMessage = `${userIds.length} utilisateurs activés`
            break

          case 'suspend':
            for (const id of userIds) {
              await UsersApi.update(id, { status: 'inactive' })
            }
            this.successMessage = `${userIds.length} utilisateurs suspendus`
            break

          case 'assign-role':
            if (!this.selectedRoleForBulk) {
              alert('Veuillez sélectionner un rôle')
              return
            }
            // Implémenter selon ton API
            this.successMessage = `Rôle assigné à ${userIds.length} utilisateurs`
            break

          case 'export':
            this.exportUsers(this.selectedUsers)
            this.successMessage = `${userIds.length} utilisateurs exportés`
            break
        }

        await this.fetchUsers()
        this.closeBulkModal()
        this.selectedUsers = []
        this.bulkAction = ''
        this.selectedRoleForBulk = ''

        setTimeout(() => {
          this.successMessage = null
        }, 3000)
      } catch (error) {
        console.error('Erreur bulk action:', error)
        this.error = 'Erreur lors de l\'action en masse'
      }
    },

    closeBulkModal() {
      this.showBulkModal = false
      this.selectedRoleForBulk = ''
    },

    getBulkActionLabel() {
      const labels = {
        activate: 'Activer',
        suspend: 'Suspendre',
        'assign-role': 'Assigner un rôle',
        export: 'Exporter'
      }
      return labels[this.bulkAction] || ''
    },

    exportUsers(users) {
      const csv = this.convertToCSV(users)
      const blob = new Blob([csv], { type: 'text/csv' })
      const url = window.URL.createObjectURL(blob)
      const a = document.createElement('a')
      a.href = url
      a.download = `users-export-${new Date().toISOString()}.csv`
      a.click()
      window.URL.revokeObjectURL(url)
    },

    convertToCSV(users) {
      const headers = ['ID', 'Nom', 'Email', 'Rôle', 'Statut', 'Créé le']
      const rows = users.map(u => [
        u.id,
        u.name,
        u.email,
        u.role?.name || '',
        u.status,
        u.created_at
      ])

      return [
        headers.join(','),
        ...rows.map(r => r.join(','))
      ].join('\n')
    }
  }
}
</script>

<style scoped>
/* Styles spécifiques pour Users.vue */
.bulk-actions-group {
  display: flex;
  gap: 0.5rem;
  align-items: center;
}

.bulk-actions-group .filter-select {
  min-width: 200px;
}
</style>