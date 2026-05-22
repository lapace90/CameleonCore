import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import httpClient from '@/services/httpClient'

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
  const lastCheck = ref(null)
  const CACHE_TIME = 5 * 60 * 1000 // 5 minutes

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
  // 2. AJOUTER LA MÉTHODE initializeToken :
  const initializeToken = () => {
    // httpClient reads auth-token from localStorage on every request — no-op here
  }

  // Configuration du token
  const setToken = (newToken) => {
    token.value = newToken
    if (newToken) {
      localStorage.setItem('auth-token', newToken)
      isAuthenticated.value = true
    } else {
      localStorage.removeItem('auth-token')
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
      const response = await httpClient.get('/auth/verify')
      console.log('🔍 Réponse API verify:', response.data)
      const userData = response.data.user

      console.log('✅ Token valide, utilisateur:', userData.name)

      user.value = userData
      isAuthenticated.value = true

      await loadUserPermissions()

      lastCheck.value = now
      return true
    } catch (err) {
      console.warn('⚠️ Token invalide ou expiré:', err.response?.status)

      lastCheck.value = null
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

  // ===========================
  //  INSCRIPTION (REGISTER)
  // ===========================

  const register = async (userData) => {
    loading.value = true
    error.value = null

    try {
      console.log('📝 Tentative d\'inscription...', userData.email)

      const response = await httpClient.post('/auth/register', userData)

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
      const response = await httpClient.post('/auth/login', credentials)

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
      const response = await httpClient.post('/auth/refresh')
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
  const loadUserPermissions = async () => {
    if (!user.value) return
    permissions.value = Array.isArray(user.value.permissions) ? user.value.permissions : []
    roles.value = Array.isArray(user.value.roles) ? user.value.roles : []
  }

  // 5. AJOUTER refreshAuth :
  const refreshAuth = async () => {
    if (!token.value) return false
    return await checkAuth()
  }

  // 6. AJOUTER updateProfile :
  const updateProfile = async (profileData) => {
    if (!user.value?.id) {
      throw new Error('Utilisateur non connecté')
    }

    loading.value = true
    error.value = null

    try {
      const headers = {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      }

      if (token.value) {
        headers['Authorization'] = `Bearer ${token.value}`
      } else {
        throw new Error('Token d\'authentification manquant')
      }
      console.log('🔍 Headers envoyés:', headers)

      const response = await httpClient.patch(`/admin/users/${user.value.id}`, profileData, {
        headers
      })

      const updatedUser = response.data

      // Mettre à jour l'utilisateur dans le store
      user.value = {
        ...user.value,
        name: updatedUser.name,
        email: updatedUser.email,
        phone: updatedUser.phone,
        address: updatedUser.address,
        city: updatedUser.city,
        postal_code: updatedUser.postal_code || updatedUser.postalCode, // Gérer les deux formats
        avatar: updatedUser.avatar,
        last_login_at: updatedUser.last_login_at,
        last_login_ip: updatedUser.last_login_ip,
      }

      return user.value
    } catch (err) {
      const errorMessage = err.response?.data?.message ||
        err.response?.data?.violations?.[0]?.message ||
        'Erreur lors de la mise à jour du profil'
      error.value = errorMessage
      throw new Error(errorMessage)
    } finally {
      loading.value = false
    }
  }

  // 7. AJOUTER changePassword :
  const changePassword = async ({ current, new: newPassword }) => {
    if (!user.value?.id) {
      throw new Error('Utilisateur non connecté')
    }

    loading.value = true
    error.value = null

    try {
      await httpClient.patch(`/admin/users/${user.value.id}`, {
        current_password: current,
        password: newPassword,
        password_confirmation: newPassword
      })

      return true
    } catch (err) {
      const errorMessage = err.response?.data?.message ||
        err.response?.data?.violations?.[0]?.message ||
        'Erreur lors du changement de mot de passe'
      error.value = errorMessage
      throw new Error(errorMessage)
    } finally {
      loading.value = false
    }
  }

  // 8. APPELER initializeToken() à la fin du store (avant le return) :
  initializeToken()


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
    register,
    login,
    logout,
    refreshToken,

    // Méthodes de vérification
    hasPermission,
    hasAnyPermission,
    hasAllPermissions,
    hasRole,
    hasAnyRole,
    hasAllRoles,
    initializeToken,
    loadUserPermissions,
    refreshAuth,
    updateProfile,
    changePassword,
  }
})
