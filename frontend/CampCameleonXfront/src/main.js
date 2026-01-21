import { createApp } from 'vue'
import { createPinia } from 'pinia'
import router from './router'
import axios from 'axios'
import { showToast } from './shared/utils/toast'

import '@fortawesome/fontawesome-free/css/all.css'
import './assets/styles/shared.scss'
import App from './App.vue'

import 'driver.js/dist/driver.css'

// ---------- AXIOS DE BASE ----------
axios.defaults.baseURL = import.meta.env.VITE_API_URL || '/api'
axios.defaults.headers.common['Accept'] = 'application/json'
axios.defaults.headers.common['Content-Type'] = 'application/json'

// RÉTABLIR LE TOKEN AVANT TOUT (critique pour la persistance)
const storedToken = localStorage.getItem('auth-token')
if (storedToken) {
  axios.defaults.headers.common['Authorization'] = `Bearer ${storedToken}`
}

// ---------- APP + PINIA ----------
const app = createApp(App)
const pinia = createPinia()
app.use(pinia)

// ---------- INTERCEPTORS (robustes) ----------
axios.interceptors.response.use(
  (response) => {
    const method = response.config?.method?.toLowerCase()
    if (['post', 'put', 'patch', 'delete'].includes(method)) {
      showToast('Action réalisée avec succès')
    }
    return response
  },
  async (error) => {
    const status = error.response?.status
    const cfg = error.config || {}

    // Ne pas casser la session si 401 vient de la vérif elle-même
    const url = (cfg.url || '').toString()
    const isAuthVerify = url.includes('/auth/verify') || url.includes('/auth/refresh')

    if (status === 401 && !isAuthVerify) {
      // évite les boucles
      if (!cfg._isRetry) {
        cfg._isRetry = true
        try {
          // si tu as un endpoint /auth/refresh, tente-le ici (optionnel)
          // await axios.post('/auth/refresh')
          // return axios(cfg)
        } catch (_) {
          const { useAuthStore } = await import('./shared/stores/auth')
          const authStore = useAuthStore()
          authStore.logout()
          if (router.currentRoute.value.path !== '/admin/login') {
            router.push({ path: '/admin/login', query: { redirect: router.currentRoute.value.fullPath } })
          }
        }
      }
    }

    const method = cfg.method?.toLowerCase()
    if (['post', 'put', 'patch', 'delete'].includes(method)) {
      const message = error.response?.data?.message || 'Une erreur est survenue'
      showToast(message, 'error')
    }

    return Promise.reject(error)
  }
)

// ---------- INIT AUTH AVANT MOUNT ----------
async function initializeAndMount() {
  const { useAuthStore } = await import('./shared/stores/auth')
  const authStore = useAuthStore()

  // Hydrate rapidement depuis le storage (sans requête)
  if (storedToken) {
    // Optionnel si ton store possède une méthode dédiée :
    // authStore.restoreFromStorage()
    authStore.token = storedToken
    authStore.isAuthenticated = true // provisoire, sera confirmé par verify
  }

  // Vérifie le token (bloquant) AVANT de monter l’app
  try {
    if (storedToken) {
      await authStore.checkAuth() // GET /api/auth/verify → remplit user
    } else {
      authStore.isAuthenticated = false
    }
  } catch (e) {
    // token invalide → purge propre
    authStore.logout()
  }

  // Brancher le router seulement maintenant (pour éviter les guards qui courent trop tôt)
  app.use(router)

  // Optionnel : attendre la résolution initiale des guards
  await router.isReady()

  app.mount('#app')
  console.log('🚀 App montée (auth prête)')
  
  // Post-init (chargements non critiques)
  if (authStore.isAuthenticated) {
    try {
      const { useRolesStore } = await import('./shared/stores/roles')
      const rolesStore = useRolesStore(pinia)
      rolesStore.ensurePermissions()
      if (router.currentRoute.value.path.startsWith('/admin/roles')) {
        rolesStore.ensureRoles()
      }
    } catch (e) {
      console.warn('⚠️ Post-init roles/permissions:', e)
    }
  }
}

initializeAndMount().catch(err => {
  console.error('❌ Erreur init:', err)
  // en dernier recours, on monte quand même l’app pour afficher une page d’erreur/login
  app.use(router)
  app.mount('#app')
})
