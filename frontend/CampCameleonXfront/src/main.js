import { createApp } from 'vue'
import { createPinia } from 'pinia'
import router from './router'

import '@fortawesome/fontawesome-free/css/all.css'
import './assets/styles/shared.scss'
import App from './App.vue'

import 'driver.js/dist/driver.css'

// ---------- APP + PINIA ----------
const app = createApp(App)
const pinia = createPinia()
app.use(pinia)

// ---------- INIT AUTH AVANT MOUNT ----------
async function initializeAndMount() {
  const { useAuthStore } = await import('./shared/stores/auth')
  const authStore = useAuthStore()

  const storedToken = localStorage.getItem('auth-token')

  // Hydrate rapidement depuis le storage (sans requête)
  if (storedToken) {
    authStore.token = storedToken
    authStore.isAuthenticated = true // provisoire, sera confirmé par verify
  }

  // Vérifie le token (bloquant) AVANT de monter l'app
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
  // en dernier recours, on monte quand même l'app pour afficher une page d'erreur/login
  app.use(router)
  app.mount('#app')
})
