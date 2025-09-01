import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '../shared/stores/auth'

// Import des routes admin et public
import adminRoutes from '../admin/router/admin.routes.js'
import publicRoutes from '../public/router/public.routes.js'

const routes = [
  {
    path: '/',
    redirect: '/home'
  },
  {
    path: '/admin/login',
    name: 'AdminLogin',
    component: () => import('../admin/views/Login.vue'),
    meta: { requiresGuest: true } // Éviter d'accéder au login si déjà connecté
  },
  {
    path: '/admin/register',
    name: 'AdminRegister',
    component: () => import('../admin/views/Register.vue'),
    meta: { requiresGuest: true }
  },
  // Routes publiques
  ...publicRoutes,

  // Routes admin (préfixées par /admin)
  {
    path: '/admin',
    children: adminRoutes
  }
]

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes
})

// 🔧 ROUTER GUARD AMÉLIORÉ : Meilleure gestion de l'initialisation
router.beforeEach(async (to, from, next) => {
  console.log(`🧭 Navigation: ${from.path} → ${to.path}`)
  
  const authStore = useAuthStore()
  
  // 🔧 ATTENDRE L'INITIALISATION si nécessaire
  if (authStore.isInitializing) {
    console.log('⏳ Attente de l\'initialisation de l\'authentification...')
    
    // Créer une promesse qui se résout quand l'initialisation est terminée
    await new Promise((resolve) => {
      const checkInitialization = () => {
        if (!authStore.isInitializing) {
          resolve()
        } else {
          // Vérifier toutes les 50ms
          setTimeout(checkInitialization, 50)
        }
      }
      checkInitialization()
    })
    
    console.log('✅ Initialisation terminée, auth:', authStore.isAuthenticated)
  }

  // 🔧 ROUTES INVITÉ : Rediriger si déjà connecté
  if (to.meta.requiresGuest && authStore.isAuthenticated) {
    console.log('🔒 Déjà connecté, redirection vers dashboard')
    next('/admin/dashboard')
    return
  }

  // 🔧 ROUTES PROTÉGÉES : Vérifier l'authentification
  if (to.matched.some(record => record.meta.requiresAuth)) {
    if (!authStore.isAuthenticated) {
      console.log('🚫 Non connecté, redirection vers login')
      next({ 
        name: 'AdminLogin', 
        query: { redirect: to.fullPath } 
      })
      return
    }
    
    // 🔧 VÉRIFICATION DES PERMISSIONS (optionnel)
    if (to.meta.requiresAdmin && !authStore.canAccessAdmin) {
      console.log('🚫 Accès admin refusé')
      next({ name: 'AdminLogin' })
      return
    }
  }

  next()
})

// 🔧 AJOUT : Guard après navigation pour gérer les erreurs
router.afterEach((to, from, failure) => {
  if (failure) {
    console.error('Erreur de navigation:', failure)
  }
})

// 🔧 AJOUT : Gestion des erreurs de résolution de route
router.onError((error) => {
  console.error('Erreur du router:', error)
})

export default router