import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import axios from 'axios'

export const useAuthStore = defineStore('auth', () => {
  // ===========================
  // STATE
  // ===========================
  const user = ref(null)
  const token = ref(localStorage.getItem('auth-token'))
  // 🔧 CORRECTION : Commencer authentifié si token existe
  const isAuthenticated = ref(!!token.value)
  const permissions = ref([])
  const roles = ref([])
  const loading = ref(false)
  const initializing = ref(!!token.value) // True si token à vérifier
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
      isAuthenticated.value = true
    } else {
      localStorage.removeItem('auth-token')
      delete axios.defaults.headers.common['Authorization']
      isAuthenticated.value = false
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
      const response = await axios.post('/api/auth/login', credentials)
      const { user: userData, token: userToken } = response.data
      
      setToken(userToken)
      user.value = userData
      
      // Charger les permissions/rôles si disponibles
      await loadUserPermissions()
      
      initializing.value = false
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
      await axios.post('/api/auth/logout')
    } catch (err) {
      // Ignorer les erreurs de logout côté serveur
    } finally {
      // Nettoyer côté client
      setToken(null)
      user.value = null
      permissions.value = []
      roles.value = []
      error.value = null
      initializing.value = false
    }
  }

  // 🔧 CORRECTION : checkAuth complète et fonctionnelle
  const checkAuth = async () => {
    // Pas de token = pas connecté
    if (!token.value) {
      isAuthenticated.value = false
      initializing.value = false
      return false
    }

    loading.value = true
    
    try {
      // Vérifier le token avec le backend
      const response = await axios.get('/api/auth/verify')
      const userData = response.data.user
      
      // Token valide, restaurer l'état
      user.value = userData
      isAuthenticated.value = true
      
      // Charger permissions/rôles
      await loadUserPermissions()
      
      return true
    } catch (err) {
      // Token invalide, nettoyer
      console.warn('Token invalide, déconnexion:', err)
      setToken(null)
      user.value = null
      permissions.value = []
      roles.value = []
      return false
    } finally {
      loading.value = false
      initializing.value = false
    }
  }

  const loadUserPermissions = async () => {
    if (!user.value) return
    
    try {
      // Charger rôles depuis les données utilisateur
      if (user.value.role) {
        roles.value = [user.value.role]
      }
      if (user.value.additional_roles) {
        roles.value = [...roles.value, ...user.value.additional_roles]
      }
      if (user.value.permissions) {
        permissions.value = user.value.permissions
      }
      
      // Permissions basiques selon le rôle
      const rolePermissions = {
        'super-admin': ['admin', 'create', 'read', 'update', 'delete', 'manage'],
        'admin': ['admin', 'create', 'read', 'update', 'delete'],
        'manager': ['read', 'update', 'create'],
        'user': ['read']
      }
      
      const userRole = user.value.role?.slug || user.value.role || 'user'
      permissions.value = rolePermissions[userRole] || ['read']
      
    } catch (err) {
      console.error('Erreur lors du chargement des permissions:', err)
    }
  }

  // Fonctions utilitaires
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
  // RETURN
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