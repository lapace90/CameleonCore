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
  routes,
  scrollBehavior(to, from, savedPosition) {
    // Si on utilise le bouton retour du navigateur
    if (savedPosition) {
      return savedPosition
    }
    // Si on navigue vers une ancre
    else if (to.hash) {
      return {
        el: to.hash,  // Pour Vue 3, utiliser 'el' au lieu de 'selector'
        behavior: 'smooth'
      }
    }
    // Sinon, toujours en haut
    else {
      return { top: 0, left: 0 }  // Pour Vue 3, utiliser 'top' et 'left'
    }
  }
})

// 🔧 ROUTER GUARD SIMPLIFIÉ : SANS ATTENTE BLOQUANTE
router.beforeEach(async (to, from, next) => {
  console.log(`🧭 Navigation: ${from.path} → ${to.path}`)

  const authStore = useAuthStore()

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

// Guard après navigation pour gérer les erreurs
router.afterEach((to, from, failure) => {
  if (failure) {
    console.error('Erreur de navigation:', failure)
  }
})

// Gestion des erreurs de résolution de route
router.onError((error) => {
  console.error('Erreur du router:', error)
})

export default router