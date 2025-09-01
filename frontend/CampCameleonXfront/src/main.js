import { createApp } from 'vue'
import { createPinia } from 'pinia'
import router from './router'
import axios from 'axios'

import '@fortawesome/fontawesome-free/css/all.css'
// Import des styles
import './assets/styles/shared.scss'

// Import de l'App principal
import App from './App.vue'

// ===========================
// CONFIGURATION AXIOS
// ===========================
axios.defaults.baseURL = 'http://localhost:8000'
axios.defaults.headers.common['Accept'] = 'application/json'
axios.defaults.headers.common['Content-Type'] = 'application/json'

// ===========================
// INITIALISATION APP
// ===========================
const app = createApp(App)
const pinia = createPinia()

app.use(pinia)
app.config.globalProperties.$http = axios

// ===========================
// INITIALISATION AUTH + INTERCEPTEURS
// ===========================
async function initializeApp() {
  const { useAuthStore } = await import('./shared/stores/auth')
  const authStore = useAuthStore()

  // 🔧 CONFIGURATION : Token initial si présent dans localStorage
  const storedToken = localStorage.getItem('auth-token')
  if (storedToken) {
    axios.defaults.headers.common['Authorization'] = `Bearer ${storedToken}`
  }

  // 🔧 INTERCEPTEUR : Gérer les erreurs 401 APRÈS initialisation du store
  axios.interceptors.response.use(
    response => response,
    error => {
      if (error.response?.status === 401) {
        // Éviter la boucle infinie si on est déjà en train de se déconnecter
        if (!error.config._isRetry) {
          authStore.logout()
          const currentPath = router.currentRoute.value.path
          if (currentPath !== '/admin/login') {
            router.push('/admin/login')
          }
        }
      }
      return Promise.reject(error)
    }
  )

  // 🔧 VÉRIFICATION AUTH : Attendre l'initialisation AVANT de monter l'app
  try {
    if (storedToken) {
      console.log('🔄 Vérification du token au démarrage...')
      await authStore.checkAuth()
      console.log('✅ Token vérifié:', authStore.isAuthenticated ? 'valide' : 'invalide')
    } else {
      // Pas de token, marquer l'initialisation comme terminée
      authStore.initializing = false
    }
  } catch (error) {
    console.warn('⚠️ Erreur lors de la vérification du token:', error)
    // En cas d'erreur, marquer l'initialisation comme terminée
    authStore.initializing = false
  }

  // ===========================
  // MONTAGE DE L'APP
  // ===========================
  app.use(router)
  app.mount('#app')

  console.log('🚀 Application initialisée')
}

// Lancer l'initialisation
initializeApp().catch(error => {
  console.error('❌ Erreur lors de l\'initialisation de l\'app:', error)
  // En cas d'erreur critique, monter l'app quand même
  app.use(router)
  app.mount('#app')
})