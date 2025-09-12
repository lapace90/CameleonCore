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
      console.log('🔍 Réponse API verify:', response.data)
      const userData = response.data.user

      console.log('✅ Token valide, utilisateur:', userData.name)

      // Token valide, restaurer l'état
      user.value = userData
      console.log('🔍 User dans store après verify:', user.value)
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
    // ⚡ Pas d'appel API ici : on se contente des infos incluses dans le payload user
    permissions.value = Array.isArray(user.value.permissions) ? user.value.permissions : []
    roles.value = Array.isArray(user.value.roles) ? user.value.roles : []
  }

  // 🔧 NOUVELLE MÉTHODE : Force refresh de l'état d'authentification
  const refreshAuth = async () => {
    if (!token.value) return false
    return await checkAuth()
  }

  // ===========================
  // PROFILE MANAGEMENT
  // ===========================

  /**
   * Mettre à jour le profil utilisateur
   */
  const updateProfile = async (profileData) => {
    if (!user.value?.id) {
      throw new Error('Utilisateur non connecté')
    }

    loading.value = true
    error.value = null

    try {
      // Utiliser l'API Platform existante pour l'auto-édition
      const response = await axios.patch(`/api/users/${user.value.id}`, profileData, {
        headers: {
          'Content-Type': 'application/json'
        }
      })

      // Mettre à jour l'utilisateur local avec les nouvelles données complètes
      const updatedUser = response.data
      user.value = {
        ...user.value,
        name: updatedUser.name,
        email: updatedUser.email,
        // ✅ TOUS LES NOUVEAUX CHAMPS
        phone: updatedUser.phone,
        address: updatedUser.address,
        city: updatedUser.city,
        postal_code: updatedUser.postal_code,
        avatar: updatedUser.avatar,
        // Garder les autres champs existants
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

  /**
   * Changer le mot de passe
   */
  const changePassword = async ({ current, new: newPassword }) => {
    if (!user.value?.id) {
      throw new Error('Utilisateur non connecté')
    }

    loading.value = true
    error.value = null

    try {
      // Utiliser l'API Platform pour changer le mot de passe
      await axios.patch(`/api/users/${user.value.id}`, {
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
    loadUserPermissions,
    updateProfile,
    changePassword
  }
})