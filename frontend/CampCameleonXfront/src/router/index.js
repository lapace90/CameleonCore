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
    component: () => import('../admin/views/Login.vue')
  },
   {
    path: '/admin/register',
    name: 'AdminRegister',
    component: () => import('../admin/views/Register.vue')
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

// 🔧 CORRECTION : Router guard qui attend l'initialisation
router.beforeEach(async (to, from, next) => {
  const authStore = useAuthStore()

  // Si on va vers login et qu'on est déjà connecté, rediriger
  if (to.name === 'AdminLogin' && authStore.isAuthenticated) {
    next('/admin/dashboard')
    return
  }

  // Si la route nécessite une auth
  if (to.matched.some(record => record.meta.requiresAuth)) {
    
    // ⏳ ATTENDRE que l'initialisation soit terminée
    if (authStore.initializing) {
      try {
        // Attendre la vérification de l'auth
        await authStore.checkAuth()
      } catch (err) {
        console.warn('Erreur lors de la vérification auth:', err)
      }
    }
    
    // Maintenant vérifier l'authentification
    if (!authStore.isAuthenticated) {
      next({ name: 'AdminLogin', query: { redirect: to.fullPath } })
      return
    }
  }

  next()
})

export default router