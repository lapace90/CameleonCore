import { createApp } from 'vue'
import { createPinia } from 'pinia'
import router from './router'
import axios from 'axios'
import { showToast } from './shared/utils/toast'

import '@fortawesome/fontawesome-free/css/all.css'
import './assets/styles/shared.scss'
import App from './App.vue'

// Configuration axios
axios.defaults.baseURL = 'http://localhost:8000'
axios.defaults.headers.common['Accept'] = 'application/json'
axios.defaults.headers.common['Content-Type'] = 'application/json'

// Création app
const app = createApp(App)
const pinia = createPinia()

app.use(pinia)
app.config.globalProperties.$http = axios

async function initializeApp() {
  const { useAuthStore } = await import('./shared/stores/auth')
  const authStore = useAuthStore()

  // Configuration token
  const storedToken = localStorage.getItem('auth-token')
  if (storedToken) {
    axios.defaults.headers.common['Authorization'] = `Bearer ${storedToken}`
  }

  // Intercepteurs
  axios.interceptors.response.use(
    response => {
      const method = response.config?.method?.toLowerCase()
      if (['post', 'put', 'patch', 'delete'].includes(method)) {
        showToast('Action réalisée avec succès')
      }
      return response
    },
    error => {
      if (error.response?.status === 401) {
        if (!error.config._isRetry) {
          authStore.logout()
          const currentPath = router.currentRoute.value.path
          if (currentPath !== '/admin/login') {
            router.push('/admin/login')
          }
        }
      }
      const method = error.config?.method?.toLowerCase()
      if (['post', 'put', 'patch', 'delete'].includes(method)) {
        const message = error.response?.data?.message || 'Une erreur est survenue'
        showToast(message, 'error')
      }
      return Promise.reject(error)
    }
  )

  // 🚀 MONTER L'APP D'ABORD
  app.use(router)
  app.mount('#app')
  console.log('🚀 App montée')

  // 🔄 PUIS checkAuth EN ARRIÈRE-PLAN
  try {
    if (storedToken) {
      console.log('🔄 Vérification token...')
      await authStore.checkAuth()
      console.log('✅ Token vérifié:', authStore.isAuthenticated ? 'valide' : 'invalide')
      
      if (authStore.isAuthenticated) {
        const { useRolesStore } = await import('./shared/stores/roles')
        const rolesStore = useRolesStore(pinia)
        rolesStore.ensurePermissions()
        if (router.currentRoute.value.path.startsWith('/admin/roles')) {
          rolesStore.ensureRoles()
        }
      }
    } else {
      authStore.initializing = false
    }
  } catch (error) {
    console.warn('⚠️ Erreur token:', error)
    authStore.initializing = false
  }
}

initializeApp().catch(error => {
  console.error('❌ Erreur init:', error)
  app.use(router)
  app.mount('#app')
})