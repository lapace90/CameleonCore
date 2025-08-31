// stores/auth.js - Store Pinia corrigé pour votre architecture

import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import axios from 'axios'

export const useAuthStore = defineStore('auth', () => {
  // ===========================
  // STATE
  // ===========================
  const user = ref(null)
  const token = ref(localStorage.getItem('auth-token'))
  const isAuthenticated = ref(false)
  const permissions = ref([])
  const roles = ref([])
  const loading = ref(false)
  const initializing = ref(true)
  const error = ref(null)

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
    }
  }

  // Configurer le token initial si présent
  if (token.value) {
    axios.defaults.headers.common['Authorization'] = `Bearer ${token.value}`
  }

  const login = async (credentials) => {
    loading.value = true
    error.value = null
    
    try {
      // CORRECTION : utiliser /auth/login (pas /api/auth/login)
      const response = await axios.post('/auth/login', credentials)
      const { user: userData, token: userToken } = response.data
      
      setToken(userToken)
      user.value = userData
      isAuthenticated.value = true
      
      // Charger les permissions/rôles si disponibles
      await loadUserPermissions()
      
      return { success: true, user: userData }
    } catch (err) {
      const message = err.response?.data?.message || 'Erreur de connexion'
      error.value = message
      throw err
    } finally {
      loading.value = false
    }
  }

  const logout = async () => {
    try {
      // CORRECTION : utiliser /auth/logout (pas /api/auth/logout)
      await axios.post('/auth/logout')
    } catch (err) {
      console.warn('Erreur lors de la déconnexion:', err)
    } finally {
      setToken(null)
      user.value = null
      isAuthenticated.value = false
      permissions.value = []
      roles.value = []
      error.value = null
      loading.value = false
    }
  }

  const checkAuth = async () => {
    if (!token.value) {
      initializing.value = false
      return false
    }
    
    loading.value = true
    
    try {
      // CORRECTION : utiliser /auth/verify (pas /api/auth/verify)
      const response = await axios.get('/auth/verify')
      const userData = response.data.user
      
      user.value = userData
      isAuthenticated.value = true
      
      await loadUserPermissions()
      
      return true
    } catch (err) {
      console.warn('Token invalide:', err)
      setToken(null)
      user.value = null
      isAuthenticated.value = false
      return false
    } finally {
      loading.value = false
      initializing.value = false
    }
  }

  const loadUserPermissions = async () => {
    if (!user.value) return
    
    try {
      // Pour l'instant, utilisation simple basée sur le rôle
      // À adapter selon vos besoins futurs
      if (user.value.role) {
        roles.value = [user.value.role]
        
        // Permissions basiques selon le rôle
        const rolePermissions = {
          'admin': ['admin', 'create', 'read', 'update', 'delete'],
          'manager': ['read', 'update', 'create'],
          'user': ['read']
        }
        
        permissions.value = rolePermissions[user.value.role] || ['read']
      }
      
    } catch (err) {
      console.error('Erreur lors du chargement des permissions:', err)
    }
  }

  // ===========================
  // FONCTIONS UTILITAIRES
  // ===========================
  
  const hasPermission = (permission) => {
    if (!user.value) return false
    if (isSuperAdmin.value) return true
    
    return userPermissions.value.includes(permission)
  }

  const hasRole = (roleSlug) => {
    if (!user.value) return false
    return userRoles.value.includes(roleSlug)
  }

  const hasAnyRole = (roleSlugs) => {
    if (!user.value) return false
    return roleSlugs.some(slug => userRoles.value.includes(slug))
  }

  const clearError = () => {
    error.value = null
  }

  // ===========================
  // RETURN (équivalent aux getters/actions exportés)
  // ===========================
  
  return {
    // State
    user,
    token,
    isAuthenticated,
    permissions,
    roles,
    loading,
    initializing,
    error,
    
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
    loadUserPermissions,
    hasPermission,
    hasRole,
    hasAnyRole,
    clearError,
    setToken
  }
})