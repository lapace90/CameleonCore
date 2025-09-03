// src/shared/stores/users.js - VERSION OPTIMISÉE
import { defineStore } from 'pinia'
import UsersApi from '@/services/UsersApi'
import { useAuthStore } from './auth'

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
    },

    // 🚀 OPTIMISATION : Cache management
    lastFetch: {
      users: null,
      roles: null,
      permissions: null
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

        let aValue = a[field] || ''
        let bValue = b[field] || ''

        if (typeof aValue === 'string') {
          return aValue.localeCompare(bValue) * direction
        }

        return (aValue - bValue) * direction
      })

      return filtered
    },

    // Statistiques calculées
    activeUsers: (state) => state.users.filter(user => user.status === 'active').length,

    adminUsers: (state) => state.users.filter(user =>
      user.role?.slug === 'admin' ||
      user.additional_roles?.some(role => role.slug === 'admin')
    ).length,

    recentUsers: (state) => {
      const weekAgo = new Date()
      weekAgo.setDate(weekAgo.getDate() - 7)
      return state.users.filter(user => new Date(user.created_at) > weekAgo).length
    },

    // 🚀 Cache status
    isCacheValid: (state) => {
      const now = Date.now()
      const CACHE_TIME = 2 * 60 * 1000 // 2 minutes
      return state.lastFetch.users && (now - state.lastFetch.users < CACHE_TIME)
    }
  },

  actions: {
    // 🚀 OPTIMISATION : Fetch users with intelligent cache
    async fetchUsers(forceRefresh = false) {
      // Vérifier le cache
      if (!forceRefresh && this.isCacheValid && this.users.length > 0) {
        console.log('🚀 Users cache valide, pas de requête backend')
        return
      }

      this.loading = true
      this.error = null

      try {
        console.log('🔄 Chargement des utilisateurs depuis l\'API...')

        // Utiliser l'API service au lieu d'axios direct
        this.users = await UsersApi.getAll()

        // Calculer les stats
        this.updateStats()

        // 🚀 Marquer le cache comme à jour
        this.lastFetch.users = Date.now()

        console.log(`✅ ${this.users.length} utilisateurs chargés et mis en cache`)

      } catch (error) {
        console.error('❌ Erreur lors du chargement des utilisateurs:', error)
        this.error = error.message || 'Erreur lors du chargement des utilisateurs'
        this.users = []
      } finally {
        this.loading = false
      }
    },

    // 🚀 OPTIMISATION : Fetch roles with cache
    async fetchRoles(forceRefresh = false) {
      const now = Date.now()
      const CACHE_TIME = 10 * 60 * 1000 // 10 minutes pour les rôles

      if (!forceRefresh && this.lastFetch.roles &&
        (now - this.lastFetch.roles < CACHE_TIME) &&
        this.availableRoles.length > 0) {
        console.log('🚀 Roles cache valide')
        return
      }

      try {
        console.log('🔄 Chargement des rôles...')
        this.availableRoles = await UsersApi.getRoles()
        this.lastFetch.roles = now
        console.log(`✅ ${this.availableRoles.length} rôles chargés`)
      } catch (error) {
        console.error('❌ Erreur lors du chargement des rôles:', error)
        this.error = error.message || 'Erreur lors du chargement des rôles'
      }
    },

    // 🚀 OPTIMISATION : Fetch permissions with cache
    async fetchPermissions(forceRefresh = false) {
      const now = Date.now()
      const CACHE_TIME = 10 * 60 * 1000 // 10 minutes

      if (!forceRefresh && this.lastFetch.permissions &&
        (now - this.lastFetch.permissions < CACHE_TIME) &&
        this.availablePermissions.length > 0) {
        console.log('🚀 Permissions cache valide')
        return
      }

      try {
        console.log('🔄 Chargement des permissions...')
        this.availablePermissions = await UsersApi.getPermissions()
        this.lastFetch.permissions = now
        console.log(`✅ ${this.availablePermissions.length} permissions chargées`)
      } catch (error) {
        console.error('❌ Erreur lors du chargement des permissions:', error)
        this.error = error.message || 'Erreur lors du chargement des permissions'
      }
    },

    // 🚀 OPTIMISATION : Load all data with one call
    async loadAllData(forceRefresh = false) {
      this.loading = true

      try {
        // Charger en parallèle pour optimiser
        await Promise.all([
          this.fetchUsers(forceRefresh),
          this.fetchRoles(forceRefresh),
          this.fetchPermissions(forceRefresh)
        ])

        console.log('✅ Toutes les données utilisateurs chargées')

      } catch (error) {
        console.error('❌ Erreur lors du chargement des données:', error)
        this.error = 'Erreur lors du chargement des données'
      } finally {
        this.loading = false
      }
    },

    // Créer un utilisateur
    async createUser(userData) {
      this.loading = true
      this.error = null

      try {
        const newUser = await UsersApi.create(userData)

        // Ajouter à la liste locale (optimistic update)
        this.users.unshift(newUser)
        this.updateStats()

        this.successMessage = 'Utilisateur créé avec succès'

        // Invalider le cache pour forcer un refresh au prochain fetch
        this.lastFetch.users = null

        return newUser
      } catch (error) {
        console.error('❌ Erreur lors de la création:', error)
        this.error = error.message || 'Erreur lors de la création'
        throw error
      } finally {
        this.loading = false
      }
    },

    // Mettre à jour un utilisateur
    async updateUser(userId, userData) {
      this.loading = true
      this.error = null

      try {
        const updatedUser = await UsersApi.update(userId, userData)

        // Mise à jour optimistic dans la liste locale
        const index = this.users.findIndex(user => user.id === userId)
        if (index !== -1) {
          this.users[index] = updatedUser
        }

        this.updateStats()
        this.successMessage = 'Utilisateur modifié avec succès'

        // Invalider le cache
        this.lastFetch.users = null

        return updatedUser
      } catch (error) {
        console.error('❌ Erreur lors de la mise à jour:', error)
        this.error = error.message || 'Erreur lors de la mise à jour'
        throw error
      } finally {
        this.loading = false
      }
    },

    // Supprimer un utilisateur
    async deleteUser(userId) {
      this.loading = true
      this.error = null

      try {
        await UsersApi.delete(userId)

        // Suppression optimistic de la liste locale
        this.users = this.users.filter(user => user.id !== userId)
        this.updateStats()

        this.successMessage = 'Utilisateur supprimé avec succès'

        // Invalider le cache
        this.lastFetch.users = null

      } catch (error) {
        console.error('❌ Erreur lors de la suppression:', error)
        this.error = error.message || 'Erreur lors de la suppression'
        throw error
      } finally {
        this.loading = false
      }
    },

    // Récupérer un utilisateur par ID avec cache
    async fetchUserById(userId, forceRefresh = false) {
      const cacheKey = `user_${userId}`
      const now = Date.now()
      const CACHE_TIME = 5 * 60 * 1000 // 5 minutes pour un user spécifique

      // Vérifier cache
      if (!forceRefresh && this.currentUser && this.currentUser.id == userId) {
        console.log('🚀 User déjà en store, pas de requête')
        return this.currentUser
      }

      this.loading = true
      this.error = null

      try {
        console.log(`🔄 Chargement utilisateur ${userId}...`)

        this.currentUser = await UsersApi.getById(userId)

        console.log(`✅ Utilisateur ${userId} chargé`)
        return this.currentUser

      } catch (error) {
        console.error(`❌ Erreur chargement utilisateur ${userId}:`, error)
        this.error = error.message || 'Erreur lors du chargement de l\'utilisateur'
        this.currentUser = null
        throw error
      } finally {
        this.loading = false
      }
    },

    // Reset password utilisateur
    async resetUserPassword(userId) {
      this.loading = true

      try {
        await UsersApi.resetPassword(userId)

        // Mettre à jour l'utilisateur en store si c'est celui affiché
        if (this.currentUser && this.currentUser.id == userId) {
          this.currentUser.password_reset_required = true
        }

        this.successMessage = 'Réinitialisation du mot de passe programmée'

      } catch (error) {
        console.error('❌ Erreur reset password:', error)
        this.error = error.message || 'Erreur lors de la réinitialisation'
        throw error
      } finally {
        this.loading = false
      }
    },

    // Envoyer email de vérification
    async sendVerificationEmail(userId) {
      this.loading = true

      try {
        await UsersApi.sendVerificationEmail(userId)
        this.successMessage = 'Email de vérification envoyé'

      } catch (error) {
        console.error('❌ Erreur send verification:', error)
        this.error = error.message || 'Erreur lors de l\'envoi'
        throw error
      } finally {
        this.loading = false
      }
    },

    // Dupliquer utilisateur
    async duplicateUser(userId) {
      this.loading = true

      try {
        const duplicated = await UsersApi.duplicate(userId)

        // Invalider le cache users pour refresh
        this.lastFetch.users = null

        this.successMessage = 'Utilisateur dupliqué avec succès'
        return duplicated

      } catch (error) {
        console.error('❌ Erreur duplication:', error)
        this.error = error.message || 'Erreur lors de la duplication'
        throw error
      } finally {
        this.loading = false
      }
    },

    // Toggle status utilisateur
    async toggleUserStatus(userId, newStatus) {
      const user = this.users.find(u => u.id === userId)
      if (!user) return

      const oldStatus = user.status

      try {
        // Optimistic update
        user.status = newStatus
        this.updateStats()

        await UsersApi.toggleStatus(userId, newStatus)
        this.successMessage = `Statut utilisateur modifié: ${newStatus}`

      } catch (error) {
        // Revert optimistic update
        user.status = oldStatus
        this.updateStats()

        console.error('❌ Erreur lors du changement de statut:', error)
        this.error = error.message || 'Erreur lors du changement de statut'
        throw error
      }
    },

    // Action en masse
    async bulkAction(action, userIds, additionalData = {}) {
      this.loading = true
      this.error = null

      try {
        await UsersApi.bulkAction({
          action,
          user_ids: userIds,
          ...additionalData
        })

        // Recharger les données après action en masse
        await this.fetchUsers(true)

        this.successMessage = `Action "${action}" appliquée à ${userIds.length} utilisateur(s)`

      } catch (error) {
        console.error('❌ Erreur lors de l\'action en masse:', error)
        this.error = error.message || 'Erreur lors de l\'action en masse'
        throw error
      } finally {
        this.loading = false
      }
    },

    // Mettre à jour les statistiques
    updateStats() {
      this.stats.total = this.users.length
      this.stats.active = this.users.filter(u => u.status === 'active').length
      this.stats.inactive = this.users.filter(u => u.status === 'inactive').length
      this.stats.admins = this.adminUsers
      this.stats.recent = this.recentUsers
    },

    // Utilitaires
    setFilters(filters) {
      this.filters = { ...this.filters, ...filters }
    },

    clearFilters() {
      this.filters = {
        search: '',
        role: '',
        status: '',
        sortField: 'name',
        sortDirection: 'asc'
      }
    },

    setSelectedUsers(users) {
      this.selectedUsers = users
    },

    clearMessages() {
      this.error = null
      this.successMessage = null
    },

    // 🚀 Cache management
    clearCache() {
      this.lastFetch = {
        users: null,
        roles: null,
        permissions: null
      }
      console.log('🧹 Users cache effacé')
    },

    // Forcer le refresh
    async refresh() {
      console.log('🔄 Refresh forcé des données utilisateurs')
      await this.loadAllData(true)
    }
  }
})

// 🚀 AJOUTS pour src/shared/stores/users.js

/*
// Ajouter ces méthodes dans les actions du users store :


*/

