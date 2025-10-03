// src/shared/stores/auth.js - VERSION COMPLÈTE avec méthodes de permissions
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

  // Configuration du token
  const setToken = (newToken) => {
    token.value = newToken
    if (newToken) {
      localStorage.setItem('auth-token', newToken)
      axios.defaults.headers.common['Authorization'] = `Bearer ${newToken}`
    } else {
      localStorage.removeItem('auth-token')
      delete axios.defaults.headers.common['Authorization']
      isAuthenticated.value = false
    }
  }

  // Méthode pour définir l'utilisateur
  const setUser = (userData) => {
    user.value = userData
    
    if (userData) {
      // Extraire et normaliser les permissions
      permissions.value = userData.permissions || []
      
      // Extraire et normaliser les rôles
      if (userData.roles) {
        roles.value = userData.roles
      } else if (userData.role) {
        // Si seulement un rôle principal existe
        roles.value = [userData.role]
      } else {
        roles.value = []
      }
      
      isAuthenticated.value = true
    } else {
      permissions.value = []
      roles.value = []
      isAuthenticated.value = false
    }
  }

  // ===========================
  // MÉTHODES DE VÉRIFICATION DES PERMISSIONS
  // ===========================

  /**
   * Vérifie si l'utilisateur a une permission spécifique
   * @param {string} permission - L'action de permission à vérifier
   * @returns {boolean}
   */
  const hasPermission = (permission) => {
    if (!user.value) return false
    if (isAdmin.value) return true // Les admins ont toutes les permissions
    return userPermissions.value.includes(permission)
  }

  /**
   * Vérifie si l'utilisateur a au moins une des permissions fournies
   * @param {string[]} permissionList - Liste des permissions
   * @returns {boolean}
   */
  const hasAnyPermission = (permissionList) => {
    if (!user.value) return false
    if (isAdmin.value) return true
    return permissionList.some(permission => hasPermission(permission))
  }

  /**
   * Vérifie si l'utilisateur a toutes les permissions fournies
   * @param {string[]} permissionList - Liste des permissions
   * @returns {boolean}
   */
  const hasAllPermissions = (permissionList) => {
    if (!user.value) return false
    if (isAdmin.value) return true
    return permissionList.every(permission => hasPermission(permission))
  }

  /**
   * Vérifie si l'utilisateur a un rôle spécifique
   * @param {string} role - Le slug du rôle à vérifier
   * @returns {boolean}
   */
  const hasRole = (role) => {
    if (!user.value) return false
    return userRoles.value.includes(role)
  }

  /**
   * Vérifie si l'utilisateur a au moins un des rôles fournis
   * @param {string[]} roleList - Liste des rôles
   * @returns {boolean}
   */
  const hasAnyRole = (roleList) => {
    if (!user.value) return false
    return roleList.some(role => hasRole(role))
  }

  /**
   * Vérifie si l'utilisateur a tous les rôles fournis
   * @param {string[]} roleList - Liste des rôles
   * @returns {boolean}
   */
  const hasAllRoles = (roleList) => {
    if (!user.value) return false
    return roleList.every(role => hasRole(role))
  }

  // ===========================
  // VÉRIFICATION DE L'AUTHENTIFICATION
  // ===========================

  const checkAuth = async () => {
    if (!token.value) {
      console.log('❌ Pas de token, pas de vérification')
      isAuthenticated.value = false
      initializing.value = false
      return false
    }

    loading.value = true
    console.log('🔍 Vérification du token...')

    try {
      const response = await axios.get('/api/auth/verify')
      console.log('🔍 Réponse API verify:', response.data)

      if (response.data.user) {
        console.log('✅ Token valide, utilisateur:', response.data.user.name)
        setUser(response.data.user)
        console.log('🔍 User dans store après verify:', user.value)
        return true
      }

      console.warn('⚠️ Pas d\'utilisateur dans la réponse')
      logout()
      return false
    } catch (err) {
      console.error('❌ Erreur vérification token:', err.response?.status, err.message)
      logout()
      return false
    } finally {
      loading.value = false
      initializing.value = false
    }
  }

  // ===========================
  // LOGIN
  // ===========================

  const login = async (credentials) => {
    loading.value = true
    error.value = null

    try {
      const response = await axios.post('/api/auth/login', credentials)
      
      if (response.data.token && response.data.user) {
        setToken(response.data.token)
        setUser(response.data.user)
        return { success: true, user: response.data.user }
      }

      throw new Error('Réponse invalide du serveur')
    } catch (err) {
      error.value = err.response?.data?.message || 'Erreur de connexion'
      return { success: false, error: error.value }
    } finally {
      loading.value = false
    }
  }

  // ===========================
  // LOGOUT
  // ===========================

  const logout = () => {
    console.log('🚪 Déconnexion...')
    setToken(null)
    setUser(null)
    isAuthenticated.value = false
  }

  // ===========================
  // REFRESH TOKEN (optionnel)
  // ===========================

  const refreshToken = async () => {
    try {
      const response = await axios.post('/api/auth/refresh')
      if (response.data.token) {
        setToken(response.data.token)
        return true
      }
      return false
    } catch (err) {
      console.error('Erreur refresh token:', err)
      return false
    }
  }

  // ===========================
  // RETOUR PUBLIC
  // ===========================

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
    setToken,
    setUser,
    checkAuth,
    login,
    logout,
    refreshToken,

    // Méthodes de vérification
    hasPermission,
    hasAnyPermission,
    hasAllPermissions,
    hasRole,
    hasAnyRole,
    hasAllRoles
  }
})