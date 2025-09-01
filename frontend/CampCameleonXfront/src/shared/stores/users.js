// src/shared/stores/users.js
import { defineStore } from 'pinia'
import UsersApi from '@/services/UsersApi'

export const useUsersStore = defineStore('users', {
  state: () => ({
    // Données
    users: [],
    currentUser: null,
    availableRoles: [],
    availablePermissions: [],
    
    // États de chargement
    loading: false,
    error: null,
    successMessage: null,
    
    // Pagination
    currentPage: 1,
    itemsPerPage: 10,
    totalItems: 0,
    lastPage: 1,
    
    // Filtres et recherche
    filters: {
      search: '',
      role: '',
      status: '',
      sortField: 'name',
      sortDirection: 'asc'
    },
    
    // Sélection multiple
    selectedUsers: [],
    
    // Statistiques
    stats: {
      total: 0,
      active: 0,
      inactive: 0,
      admins: 0,
      recent: 0
    }
  }),

  getters: {
    // Utilisateurs filtrés
    filteredUsers: (state) => {
      let filtered = [...state.users]
      
      // Recherche textuelle
      if (state.filters.search) {
        const search = state.filters.search.toLowerCase()
        filtered = filtered.filter(user => 
          user.name?.toLowerCase().includes(search) ||
          user.email?.toLowerCase().includes(search) ||
          user.role?.name?.toLowerCase().includes(search)
        )
      }
      
      // Filtre par rôle
      if (state.filters.role) {
        filtered = filtered.filter(user => 
          user.role?.id == state.filters.role ||
          user.additional_roles?.some(role => role.id == state.filters.role)
        )
      }
      
      // Filtre par statut
      if (state.filters.status) {
        filtered = filtered.filter(user => user.status === state.filters.status)
      }
      
      // Tri
      filtered.sort((a, b) => {
        const field = state.filters.sortField
        const direction = state.filters.sortDirection === 'asc' ? 1 : -1
        
        let valueA = a[field] || ''
        let valueB = b[field] || ''
        
        // Gestion spéciale pour les dates
        if (field.includes('_at')) {
          valueA = new Date(valueA)
          valueB = new Date(valueB)
        }
        
        // Gestion spéciale pour les rôles
        if (field === 'role') {
          valueA = a.role?.name || ''
          valueB = b.role?.name || ''
        }
        
        if (typeof valueA === 'string') {
          return valueA.localeCompare(valueB) * direction
        }
        
        return (valueA - valueB) * direction
      })
      
      return filtered
    },
    
    // Utilisateurs paginés
    paginatedUsers: (state) => {
      const filtered = state.filteredUsers
      const start = (state.currentPage - 1) * state.itemsPerPage
      const end = start + state.itemsPerPage
      return filtered.slice(start, end)
    },
    
    // Statistiques calculées
    activeUsers: (state) => {
      return state.users.filter(user => user.status === 'active').length
    },
    
    inactiveUsers: (state) => {
      return state.users.filter(user => user.status === 'inactive').length
    },
    
    adminUsers: (state) => {
      return state.users.filter(user =>
        user.role?.slug === 'admin' ||
        user.role?.slug === 'super-admin' ||
        user.additional_roles?.some(role => 
          role.slug === 'admin' || role.slug === 'super-admin'
        )
      ).length
    },
    
    recentUsers: (state) => {
      const weekAgo = new Date()
      weekAgo.setDate(weekAgo.getDate() - 7)
      return state.users.filter(user => 
        new Date(user.created_at) > weekAgo
      ).length
    },
    
    // Total pages pour pagination
    totalPages: (state) => {
      return Math.ceil(state.filteredUsers.length / state.itemsPerPage)
    },
    
    // Utilisateur sélectionné par ID
    getUserById: (state) => (id) => {
      return state.users.find(user => user.id == id)
    },
    
    // Vérifier si des filtres sont appliqués
    hasActiveFilters: (state) => {
      return !!(state.filters.search || state.filters.role || state.filters.status)
    }
  },

  actions: {
    // ==========================================
    // ACTIONS DE CHARGEMENT
    // ==========================================
    
    async fetchUsers(forceRefresh = false) {
      if (this.loading && !forceRefresh) return
      
      this.loading = true
      this.error = null
      
      try {
        const params = {
          page: this.currentPage,
          per_page: this.itemsPerPage,
          ...this.filters
        }
        
        const response = await UsersApi.getAll(params)
        
        this.users = response.data || []
        this.totalItems = response.totalItems || 0
        this.lastPage = response.lastPage || 1
        
        // Mettre à jour les statistiques
        this.updateStats()
        
      } catch (error) {
        this.error = error.message
        console.error('Erreur lors du chargement des utilisateurs:', error)
      } finally {
        this.loading = false
      }
    },
    
    async fetchUser(id) {
      this.loading = true
      this.error = null
      
      try {
        this.currentUser = await UsersApi.getById(id)
        return this.currentUser
      } catch (error) {
        this.error = error.message
        throw error
      } finally {
        this.loading = false
      }
    },
    
    async fetchRoles() {
      try {
        this.availableRoles = await UsersApi.getRoles()
      } catch (error) {
        console.warn('Impossible de charger les rôles:', error)
      }
    },
    
    async fetchPermissions() {
      try {
        this.availablePermissions = await UsersApi.getPermissions()
      } catch (error) {
        console.warn('Impossible de charger les permissions:', error)
      }
    },
    
    async fetchStats() {
      try {
        const stats = await UsersApi.getStats()
        this.stats = { ...this.stats, ...stats }
      } catch (error) {
        console.warn('Impossible de charger les statistiques:', error)
        this.updateStats() // Fallback sur le calcul local
      }
    },
    
    // ==========================================
    // ACTIONS CRUD
    // ==========================================
    
    async createUser(userData) {
      this.loading = true
      this.error = null
      
      try {
        const newUser = await UsersApi.create(userData)
        this.users.unshift(newUser)
        this.updateStats()
        this.setSuccessMessage('Utilisateur créé avec succès')
        return newUser
      } catch (error) {
        this.error = error.message
        throw error
      } finally {
        this.loading = false
      }
    },
    
    async updateUser(id, userData) {
      this.loading = true
      this.error = null
      
      try {
        const updatedUser = await UsersApi.update(id, userData)
        
        // Mettre à jour dans la liste
        const index = this.users.findIndex(user => user.id == id)
        if (index !== -1) {
          this.users[index] = updatedUser
        }
        
        // Mettre à jour l'utilisateur courant si c'est le même
        if (this.currentUser?.id == id) {
          this.currentUser = updatedUser
        }
        
        this.updateStats()
        this.setSuccessMessage('Utilisateur modifié avec succès')
        return updatedUser
      } catch (error) {
        this.error = error.message
        throw error
      } finally {
        this.loading = false
      }
    },
    
    async deleteUser(id) {
      this.loading = true
      this.error = null
      
      try {
        await UsersApi.delete(id)
        this.users = this.users.filter(user => user.id != id)
        
        // Retirer de la sélection si nécessaire
        this.selectedUsers = this.selectedUsers.filter(userId => userId != id)
        
        this.updateStats()
        this.setSuccessMessage('Utilisateur supprimé avec succès')
      } catch (error) {
        this.error = error.message
        throw error
      } finally {
        this.loading = false
      }
    },
    
    async toggleUserStatus(id) {
      const user = this.users.find(u => u.id == id)
      if (!user) return
      
      try {
        const updatedUser = await UsersApi.toggleStatus(id, user.status)
        
        const index = this.users.findIndex(u => u.id == id)
        if (index !== -1) {
          this.users[index] = updatedUser
        }
        
        this.updateStats()
        this.setSuccessMessage(`Utilisateur ${updatedUser.status === 'active' ? 'activé' : 'désactivé'}`)
      } catch (error) {
        this.error = error.message
        throw error
      }
    },
    
    // ==========================================
    // ACTIONS EN MASSE
    // ==========================================
    
    async bulkAction(action, additionalData = {}) {
      if (this.selectedUsers.length === 0) {
        this.error = 'Aucun utilisateur sélectionné'
        return
      }
      
      this.loading = true
      this.error = null
      
      try {
        await UsersApi.bulkAction(action, this.selectedUsers, additionalData)
        
        // Recharger les données
        await this.fetchUsers(true)
        
        // Réinitialiser la sélection
        this.selectedUsers = []
        
        const actionLabels = {
          'activate': 'activés',
          'deactivate': 'désactivés',
          'delete': 'supprimés',
          'assign-role': 'rôle assigné'
        }
        
        this.setSuccessMessage(`Utilisateurs ${actionLabels[action] || 'traités'} avec succès`)
      } catch (error) {
        this.error = error.message
        throw error
      } finally {
        this.loading = false
      }
    },
    
    // ==========================================
    // GESTION DES FILTRES ET PAGINATION
    // ==========================================
    
    setFilter(key, value) {
      this.filters[key] = value
      this.currentPage = 1 // Reset pagination
    },
    
    setSearch(query) {
      this.setFilter('search', query)
    },
    
    setRoleFilter(roleId) {
      this.setFilter('role', roleId)
    },
    
    setStatusFilter(status) {
      this.setFilter('status', status)
    },
    
    setSorting(field, direction = null) {
      this.filters.sortField = field
      this.filters.sortDirection = direction || 
        (this.filters.sortField === field && this.filters.sortDirection === 'asc' ? 'desc' : 'asc')
      this.currentPage = 1
    },
    
    setPage(page) {
      this.currentPage = Math.max(1, Math.min(page, this.totalPages))
    },
    
    setItemsPerPage(count) {
      this.itemsPerPage = count
      this.currentPage = 1
    },
    
    clearFilters() {
      this.filters = {
        search: '',
        role: '',
        status: '',
        sortField: 'name',
        sortDirection: 'asc'
      }
      this.currentPage = 1
    },
    
    // ==========================================
    // GESTION DE LA SÉLECTION
    // ==========================================
    
    selectUser(userId) {
      if (!this.selectedUsers.includes(userId)) {
        this.selectedUsers.push(userId)
      }
    },
    
    deselectUser(userId) {
      this.selectedUsers = this.selectedUsers.filter(id => id !== userId)
    },
    
    toggleUserSelection(userId) {
      if (this.selectedUsers.includes(userId)) {
        this.deselectUser(userId)
      } else {
        this.selectUser(userId)
      }
    },
    
    selectAllVisible() {
      const visibleUserIds = this.paginatedUsers.map(user => user.id)
      this.selectedUsers = [...new Set([...this.selectedUsers, ...visibleUserIds])]
    },
    
    deselectAllVisible() {
      const visibleUserIds = this.paginatedUsers.map(user => user.id)
      this.selectedUsers = this.selectedUsers.filter(id => !visibleUserIds.includes(id))
    },
    
    clearSelection() {
      this.selectedUsers = []
    },
    
    // ==========================================
    // UTILITAIRES
    // ==========================================
    
    setSuccessMessage(message) {
      this.successMessage = message
      // Auto-clear après 5 secondes
      setTimeout(() => {
        if (this.successMessage === message) {
          this.successMessage = null
        }
      }, 5000)
    },
    
    clearMessages() {
      this.error = null
      this.successMessage = null
    },
    
    updateStats() {
      this.stats = {
        total: this.users.length,
        active: this.activeUsers,
        inactive: this.inactiveUsers,
        admins: this.adminUsers,
        recent: this.recentUsers
      }
    },
    
    // Initialisation complète
    async init() {
      await Promise.all([
        this.fetchUsers(),
        this.fetchRoles(),
        this.fetchPermissions()
      ])
    },
    
    // Reset complet du store
    $reset() {
      this.users = []
      this.currentUser = null
      this.availableRoles = []
      this.availablePermissions = []
      this.loading = false
      this.error = null
      this.successMessage = null
      this.currentPage = 1
      this.itemsPerPage = 10
      this.totalItems = 0
      this.lastPage = 1
      this.filters = {
        search: '',
        role: '',
        status: '',
        sortField: 'name',
        sortDirection: 'asc'
      }
      this.selectedUsers = []
      this.stats = {
        total: 0,
        active: 0,
        inactive: 0,
        admins: 0,
        recent: 0
      }
    }
  }
})