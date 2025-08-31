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

router.beforeEach((to, from, next) => {
  const authStore = useAuthStore()

  if (to.name === 'AdminLogin' && authStore.isAuthenticated) {
    next('/admin/dashboard')
    return
  }

  if (to.matched.some(record => record.meta.requiresAuth) && !authStore.isAuthenticated) {
    next({ name: 'AdminLogin', query: { redirect: to.fullPath } })
  } else {
    next()
  }
})

export default router