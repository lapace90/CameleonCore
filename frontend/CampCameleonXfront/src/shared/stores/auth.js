// src/shared/stores/auth.js - VERSION COMPLÈTE avec register
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

  const initializing = ref(!!token.value)
  const isAuthenticated = ref(false)

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
      isAuthenticated.value = true
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
      roles.value = userData.roles || []
      isAuthenticated.value = true
    } else {
      permissions.value = []
      roles.value = []
      isAuthenticated.value = false
    }
  }

  // ===========================
  // VÉRIFICATION PERMISSIONS
  // ===========================

  const hasPermission = (permission) => {
    if (!user.value) return false
    return userPermissions.value.includes(permission)
  }

  const hasAnyPermission = (permissionList) => {
    if (!user.value) return false
    return permissionList.some(perm => hasPermission(perm))
  }

  const hasAllPermissions = (permissionList) => {
    if (!user.value) return false
    return permissionList.every(perm => hasPermission(perm))
  }

  const hasRole = (role) => {
    if (!user.value) return false
    return userRoles.value.includes(role)
  }

  const hasAnyRole = (roleList) => {
    if (!user.value) return false
    return roleList.some(role => hasRole(role))
  }

  const hasAllRoles = (roleList) => {
    if (!user.value) return false
    return roleList.every(role => hasRole(role))
  }

  // ===========================
  // VÉRIFICATION AUTH
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
  // 🆕 INSCRIPTION (REGISTER)
  // ===========================

  const register = async (userData) => {
    loading.value = true
    error.value = null

    try {
      console.log('📝 Tentative d\'inscription...', userData.email)
      
      const response = await axios.post('/api/auth/register', userData)
      
      if (response.data.token && response.data.user) {
        console.log('✅ Inscription réussie:', response.data.user.name)
        
        setToken(response.data.token)
        setUser(response.data.user)
        
        return { success: true, user: response.data.user }
      }

      throw new Error('Réponse invalide du serveur')
    } catch (err) {
      console.error('❌ Erreur inscription:', err)
      
      // Gestion des erreurs de validation
      if (err.response?.status === 422) {
        const validationErrors = err.response.data?.errors || {}
        const firstError = Object.values(validationErrors)[0]?.[0]
        error.value = firstError || 'Données invalides'
      } else {
        error.value = err.response?.data?.message || 'Erreur lors de l\'inscription'
      }
      
      return { success: false, error: error.value }
    } finally {
      loading.value = false
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
    register,  // 🆕 AJOUTÉ
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