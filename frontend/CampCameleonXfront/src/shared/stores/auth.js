import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import axios from 'axios'

export const useAuthStore = defineStore('auth', () => {
  // ===========================
  // STATE
  // ===========================
  const user = ref(null)
  const token = ref(localStorage.getItem('auth-token'))
  const permissions = ref([])
  const roles = ref([])
  const loading = ref(false)
  const error = ref(null)

  // 🔧 AMÉLIORATION : État d'initialisation plus précis
  const initializing = ref(!!token.value) // True seulement si un token existe
  const isAuthenticated = ref(false) // False par défaut, sera mis à true après vérification

  // ===========================
  // GETTERS
  // ===========================
  const currentUser = computed(() => user.value)
  const isLoading = computed(() => loading.value)
  const isInitializing = computed(() => initializing.value)
  const authError = computed(() => error.value)

  const userPermissions = computed(() => permissions.value.map(p => p.action || p))
  const userRoles = computed(() => roles.value.map(r => r.slug || r))
  const userRoleNames = computed(() => roles.value.map(r => r.name || r))

  const isAdmin = computed(() => {
    if (!user.value) return false
    return userRoles.value.some(r => ['super-admin', 'admin'].includes(r))
  })

  const isSuperAdmin = computed(() => {
    if (!user.value) return false
    return userRoles.value.some(r => r === 'super-admin')
  })

  const canAccessAdmin = computed(() => {
    if (!user.value) return false
    return userRoles.value.some(r => ['super-admin', 'admin', 'manager'].includes(r)) ||
      userPermissions.value.includes('admin')
  })

  // ===========================
  // ACTIONS
  // ===========================

  // 🔧 AMÉLIORATION : Configuration du token plus robuste
  const setToken = (newToken) => {
    token.value = newToken
    if (newToken) {
      localStorage.setItem('auth-token', newToken)
      axios.defaults.headers.common['Authorization'] = `Bearer ${newToken}`
      // Ne pas changer isAuthenticated ici, le faire après vérification
    } else {
      localStorage.removeItem('auth-token')
      delete axios.defaults.headers.common['Authorization']
      isAuthenticated.value = false
    }
  }

  // 🔧 INITIALISATION : Configuration du token au démarrage (appelée par main.js)
  const initializeToken = () => {
    if (token.value) {
      axios.defaults.headers.common['Authorization'] = `Bearer ${token.value}`
    }
  }

  const login = async (credentials) => {
    loading.value = true
    error.value = null

    try {
      const response = await axios.post('/api/auth/login', credentials)
      const { user: userData, token: userToken } = response.data

      setToken(userToken)
      user.value = userData
      isAuthenticated.value = true

      // Charger les permissions/rôles si disponibles
      await loadUserPermissions()

      initializing.value = false
      return { success: true, user: userData }
    } catch (err) {
      const message = err.response?.data?.message || 'Erreur de connexion'
      error.value = message
      isAuthenticated.value = false
      throw err
    } finally {
      loading.value = false
    }
  }

  const logout = async () => {
    try {
      if (token.value) {
        await axios.post('/api/auth/logout')
      }
    } catch (err) {
      console.warn('Erreur lors du logout côté serveur:', err)
    } finally {
      // 🔧 NETTOYAGE COMPLET
      setToken(null)
      user.value = null
      permissions.value = []
      roles.value = []
      error.value = null
      initializing.value = false
      isAuthenticated.value = false
    }
  }

  const lastCheck = ref(null)
  const CACHE_TIME = 5 * 60 * 1000 // 5 minutes

  // REMPLACER ta méthode checkAuth par celle-ci :
  const checkAuth = async () => {
    // Pas de token = pas connecté
    if (!token.value) {
      isAuthenticated.value = false
      initializing.value = false
      return false
    }

    // 🚀 CACHE : Éviter requête si vérification récente
    const now = Date.now()
    if (lastCheck.value && (now - lastCheck.value < CACHE_TIME)) {
      console.log('🚀 Cache auth hit - pas de vérification backend')
      return isAuthenticated.value
    }

    loading.value = true

    try {
      console.log('🔄 Vérification du token avec le backend...')

      // Vérifier le token avec le backend
      const response = await axios.get('/api/auth/verify')
      const userData = response.data.user

      console.log('✅ Token valide, utilisateur:', userData.name)

      // Token valide, restaurer l'état
      user.value = userData
      isAuthenticated.value = true

      // Charger permissions/rôles
      await loadUserPermissions()

      // 🚀 CACHE : Marquer la vérification comme réussie
      lastCheck.value = now

      return true
    } catch (err) {
      console.warn('⚠️ Token invalide ou expiré:', err.response?.status, err.response?.data?.message)

      // 🚀 CACHE : Invalider le cache en cas d'erreur
      lastCheck.value = null

      // NETTOYAGE en cas de token invalide
      setToken(null)
      user.value = null
      permissions.value = []
      roles.value = []
      isAuthenticated.value = false

      return false
    } finally {
      loading.value = false
      initializing.value = false
    }
  }

  const loadUserPermissions = async () => {
    if (!user.value) return
    loading.value = true
    error.value = null
    try {
      if (user.value.permissions && user.value.permissions.length) {
        permissions.value = user.value.permissions
      } else {
        const permRes = await axios.get('/api/permissions')
        permissions.value = Array.isArray(permRes.data)
          ? permRes.data
          : permRes.data['hydra:member'] || []
      }
      if (user.value.roles && user.value.roles.length) {
        roles.value = user.value.roles
      } else {
        const rolesRes = await axios.get('/api/roles')
        roles.value = Array.isArray(rolesRes.data)
          ? rolesRes.data
          : rolesRes.data['hydra:member'] || []
      }

      console.log('📋 Permissions chargées:', permissions.value.length)
      console.log('👥 Rôles chargés:', roles.value.length)
    } catch (err) {
      console.warn('Erreur lors du chargement des permissions ou rôles:', err)
      error.value = err.response?.data?.message || 'Impossible de charger permissions/roles'
    } finally {
      loading.value = false
    }
  }

  // 🔧 NOUVELLE MÉTHODE : Force refresh de l'état d'authentification
  const refreshAuth = async () => {
    if (!token.value) return false
    return await checkAuth()
  }

  // ===========================
  // INITIALISATION
  // ===========================

  // Configurer axios avec le token au démarrage
  initializeToken()

  return {
    // State
    user,
    token,
    permissions,
    roles,
    loading,
    error,
    initializing,
    isAuthenticated,

    // Getters
    currentUser,
    isLoading,
    isInitializing,
    authError,
    userPermissions,
    userRoles,
    userRoleNames,
    isAdmin,
    isSuperAdmin,
    canAccessAdmin,

    // Actions
    login,
    logout,
    checkAuth,
    refreshAuth,
    loadUserPermissions
  }
})