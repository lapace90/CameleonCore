import { defineStore } from 'pinia'
import UsersApi from '@/services/UsersApi'

const TTL = 1000 * 60 * 10 // 10 minutes de cache

export const useUsersStore = defineStore('users', {
  state: () => ({
    // 🗄️ Données
    users: [],
    availableRoles: [],  
    availablePermissions: [], // Pour plus tard si besoin
    currentUser: null,

    // 🎭 États UI
    loading: false,
    error: null,
    successMessage: null,

    // ⏰ Cache et promesses
    lastFetch: {
      users: null,
      roles: null,        
      permissions: null
    },
    _inflightRequests: new Map() // Éviter les requêtes en doublon
  }),

  getters: {
    // 📊 Stats et données calculées
    usersCount: (state) => state.users.length,
    rolesCount: (state) => state.availableRoles.length,

    // 🔍 Getters pratiques 
    getUserById: (state) => (id) => {
      return state.users.find(user => user.id === id)
    },

    getRoleById: (state) => (id) => {
      return state.availableRoles.find(role => role.id === id)
    }
  },

  actions: {
    // ===============================
    // 🔧 GESTION DES MESSAGES
    // ===============================
    setSuccess(message) {
      this.successMessage = message
      this.error = null
      setTimeout(() => { this.successMessage = null }, 5000)
    },

    setError(message) {
      this.error = message
      this.successMessage = null
    },

    clearMessages() {
      this.error = null
      this.successMessage = null
    },

    // ===============================
    // Chargement des rôles avec gestion d'erreurs
    // ===============================
    async fetchRoles(forceRefresh = false) {
      const now = Date.now()
      const cacheKey = 'fetchRoles'

      // 🚀 Éviter les requêtes en doublon
      if (this._inflightRequests.has(cacheKey)) {
        console.log('🔄 Requête rôles déjà en cours, attente...')
        return await this._inflightRequests.get(cacheKey)
      }

      // 🚀 Vérifier le cache
      if (!forceRefresh &&
        this.lastFetch.roles &&
        (now - this.lastFetch.roles < TTL) &&
        this.availableRoles.length > 0) {
        console.log('🚀 Rôles - cache valide')
        return this.availableRoles
      }

      // 🔄 Nouvelle requête
      console.log('🔄 Chargement des rôles depuis l\'API...')

      const promise = this._fetchRolesFromApi()
      this._inflightRequests.set(cacheKey, promise)

      try {
        const result = await promise
        this.lastFetch.roles = now
        console.log(`✅ ${this.availableRoles.length} rôles chargés`)
        return result
      } finally {
        this._inflightRequests.delete(cacheKey)
      }
    },

    async _fetchRolesFromApi() {
      try {
        // Appeler l'API corrigée
        const roles = await UsersApi.getRoles()

        // 🔧 Validation et normalisation
        if (!Array.isArray(roles)) {
          console.error('❌ Format de rôles invalide:', roles)
          this.availableRoles = []
          throw new Error('Format de données invalide pour les rôles')
        }

        // 🔧 Normalisation des IDs (string/number)
        this.availableRoles = roles.map(role => ({
          ...role,
          id: String(role.id) // Assurer la cohérence des IDs
        }))

        console.log('✅ Rôles normalisés:', this.availableRoles.map(r => ({ id: r.id, name: r.name })))

        return this.availableRoles
      } catch (error) {
        console.error('❌ Erreur lors du chargement des rôles:', error)
        this.availableRoles = []
        this.error = error.message || 'Erreur lors du chargement des rôles'
        throw error
      }
    },

    // ===============================
    // 🚀 CHARGEMENT DES USERS (existant, amélioré)
    // ===============================
    async fetchUsers(forceRefresh = false) {
      const now = Date.now()
      const cacheKey = 'fetchUsers'

      if (this._inflightRequests.has(cacheKey)) {
        return await this._inflightRequests.get(cacheKey)
      }

      if (!forceRefresh &&
        this.lastFetch.users &&
        (now - this.lastFetch.users < TTL) &&
        this.users.length > 0) {
        console.log('🚀 Users - cache valide')
        return this.users
      }

      console.log('🔄 Chargement des utilisateurs...')

      const promise = this._fetchUsersFromApi()
      this._inflightRequests.set(cacheKey, promise)

      try {
        const result = await promise
        this.lastFetch.users = now
        console.log(`✅ ${this.users.length} utilisateurs chargés`)
        return result
      } finally {
        this._inflightRequests.delete(cacheKey)
      }
    },

    async _fetchUsersFromApi() {
      try {
        const users = await UsersApi.getAll()
        this.users = Array.isArray(users) ? users : []
        return this.users
      } catch (error) {
        console.error('❌ Erreur lors du chargement des utilisateurs:', error)
        this.users = []
        this.error = error.message || 'Erreur lors du chargement des utilisateurs'
        throw error
      }
    },

    // ===============================
    // 🔧 MÉTHODE PRINCIPALE : LoadAllData avec chargement conditionnel
    // ===============================
    async loadAllData(options = {}) {
      const { forceRefresh = false, rolesOnly = false } = options

      this.loading = true
      this.clearMessages()

      try {
        console.log('🔄 Chargement des données utilisateurs...', { rolesOnly })

        // 🚀 Si rolesOnly=true (pour UserForm), charger que les rôles
        if (rolesOnly) {
          const roles = await this.fetchRoles(forceRefresh)
          console.log('✅ Rôles seuls chargés (mode formulaire):', { roles: roles.length })
          return { roles }
        }

        // 🚀 Sinon, charger tout en parallèle (optimisation)
        const [users, roles] = await Promise.all([
          this.fetchUsers(forceRefresh),
          this.fetchRoles(forceRefresh)
        ])

        console.log('✅ Toutes les données chargées:', {
          users: users.length,
          roles: roles.length
        })

        return { users, roles }

      } catch (error) {
        console.error('❌ Erreur lors du chargement complet:', error)
        this.error = 'Impossible de charger les données'
        throw error
      } finally {
        this.loading = false
      }
    },

    // ===============================
    // 🔧 MÉTHODES CRUD (existantes, gardées identiques)
    // ===============================
    async fetchUserById(userId) {
      try {
        // Vérifier le cache local d'abord
        let user = this.getUserById(userId)

        if (!user) {
          console.log(`🔄 Chargement utilisateur ${userId}...`)

          // 🔍 DEBUG 1: Appel API
          console.log('🔍 Store - Avant appel UsersApi.getById')
          const userData = await UsersApi.getById(userId)
          console.log('🔍 Store - Après appel UsersApi.getById:', JSON.stringify(userData, null, 2))

          // 🔍 DEBUG 2: Vérifier les rôles spécifiquement
          console.log('🔍 Store - userData.role:', userData.role)
          console.log('🔍 Store - userData.roles:', userData.roles)
          console.log('🔍 Store - userData.additional_roles:', userData.additional_roles)
          console.log('🔍 Store - userData.additionalRoles:', userData.additionalRoles)

          this.currentUser = userData

          // 🔍 DEBUG 3: Après assignation à currentUser
          console.log('🔍 Store - this.currentUser après assignation:', JSON.stringify(this.currentUser, null, 2))

          // Ajouter/mettre à jour dans la liste
          const existingIndex = this.users.findIndex(u => u.id === userId)
          if (existingIndex >= 0) {
            this.users[existingIndex] = userData
          } else {
            this.users.push(userData)
          }

          user = userData
        } else {
          this.currentUser = user
          console.log('🔍 Store - Utilisateur trouvé dans cache:', JSON.stringify(user, null, 2))
        }

        return user
      } catch (error) {
        console.error('❌ Erreur fetchUserById:', error)
        this.error = 'Impossible de charger l\'utilisateur'
        throw error
      }
    },

    async createUser(userData) {
      try {
        console.log('🔄 Création utilisateur...')
        const newUser = await UsersApi.create(userData)

        this.users.push(newUser)
        this.setSuccess('Utilisateur créé avec succès')

        console.log('✅ Utilisateur créé:', newUser.name)
        return newUser
      } catch (error) {
        console.error('❌ Erreur création:', error)
        this.error = error.response?.data?.message || 'Erreur lors de la création'
        throw error
      }
    },

    async updateUser(userId, userData) {
      try {
        console.log(`🔄 Mise à jour utilisateur ${userId}...`)
        const updatedUser = await UsersApi.update(userId, userData)

        // Mettre à jour dans la liste
        const index = this.users.findIndex(u => u.id === userId)
        if (index >= 0) {
          this.users[index] = updatedUser
        }

        // Mettre à jour currentUser si c'est le même
        if (this.currentUser?.id === userId) {
          this.currentUser = updatedUser
        }

        this.setSuccess('Utilisateur mis à jour avec succès')

        console.log('✅ Utilisateur mis à jour:', updatedUser.name)
        return updatedUser
      } catch (error) {
        console.error('❌ Erreur mise à jour:', error)
        this.error = error.response?.data?.message || 'Erreur lors de la mise à jour'
        throw error
      }
    },

    async deleteUser(userId) {
      try {
        console.log(`🔄 Suppression utilisateur ${userId}...`)
        await UsersApi.delete(userId)

        // Retirer de la liste
        this.users = this.users.filter(u => u.id !== userId)

        // Nettoyer currentUser si c'est le même
        if (this.currentUser?.id === userId) {
          this.currentUser = null
        }

        this.setSuccess('Utilisateur supprimé avec succès')

        console.log('✅ Utilisateur supprimé')
        return true
      } catch (error) {
        console.error('❌ Erreur suppression:', error)
        this.error = error.response?.data?.message || 'Erreur lors de la suppression'
        throw error
      }
    }
  }
})