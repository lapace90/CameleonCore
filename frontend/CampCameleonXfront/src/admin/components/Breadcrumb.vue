<template>
  <nav v-if="breadcrumbs.length > 0" class="breadcrumb">
    <template v-for="(crumb, index) in breadcrumbs" :key="index">
      <!-- Élément avec lien -->
      <router-link 
        v-if="crumb.path && index < breadcrumbs.length - 1" 
        :to="crumb.path" 
        class="breadcrumb-item"
      >
        {{ crumb.name }}
      </router-link>
      
      <!-- Dernier élément (page actuelle, sans lien) -->
      <span v-else class="breadcrumb-item active">
        {{ crumb.name }}
      </span>
      
    </template>
  </nav>
</template>

<script>
import { PRODUCT_CONFIGS } from '@/shared/configs/productConfigs'

export default {
  name: 'Breadcrumb',

  props: {
    // Props optionnelles pour override manuel
    items: {
      type: Array,
      default: () => []
    }
  },

  computed: {
    breadcrumbs() {
      // Si des items sont fournis manuellement, les utiliser
      if (this.items && this.items.length > 0) {
        return this.items
      }

      // Sinon, génération automatique basée sur la route
      return this.generateBreadcrumbs()
    }
  },

  methods: {
    generateBreadcrumbs() {
      const route = this.$route
      const breadcrumbs = []

      // Pas de breadcrumb sur la page dashboard (évite "Dashboard > Dashboard")
      if (route.name === 'AdminDashboard') {
        return []
      }

      // Toujours commencer par Dashboard
      breadcrumbs.push({
        name: 'Dashboard',
        path: '/admin/dashboard'
      })

      // === ROUTES PRODUITS ===
      if (route.path.includes('/products/') || route.path.includes('/admin/products')) {
        const productType = route.params.type
        const config = PRODUCT_CONFIGS[productType]
        
        if (config) {
          // Ajouter la page liste du type de produit
          breadcrumbs.push({
            name: config.label,
            path: `/admin/products/${productType}`
          })

          // Ajouter la page spécifique si on n'est pas sur la liste
          if (route.name === 'ProductCreate') {
            breadcrumbs.push({ name: 'Nouveau', path: null })
          } else if (route.name === 'ProductEdit') {
            breadcrumbs.push({ name: 'Modification', path: null })
          } else if (route.name === 'ProductDetail') {
            breadcrumbs.push({ name: 'Détails', path: null })
          }
        } else {
          // Fallback pour les routes produits sans type spécifique
          breadcrumbs.push({ name: 'Produits', path: null })
        }
      }
      // === ROUTES USERS ===
      else if (route.path.includes('/users')) {
        breadcrumbs.push({
          name: 'Utilisateurs',
          path: '/admin/users'
        })

        if (route.name === 'UserCreate') {
          breadcrumbs.push({ name: 'Nouveau', path: null })
        } else if (route.name === 'UserEdit') {
          breadcrumbs.push({ name: 'Modification', path: null })
        } else if (route.name === 'UserDetail') {
          breadcrumbs.push({ name: 'Détails', path: null })
        }
      }
      // === ROUTES ROLES ===
      else if (route.path.includes('/roles')) {
        breadcrumbs.push({
          name: 'Rôles & Permissions',
          path: '/admin/roles'
        })

        if (route.name === 'RoleCreate') {
          breadcrumbs.push({ name: 'Nouveau rôle', path: null })
        } else if (route.name === 'RoleEdit') {
          breadcrumbs.push({ name: 'Modification', path: null })
        }
      }
      // === AUTRES ROUTES ADMIN ===
      else {
        const routeNames = {
          'AdminAnalytics': 'Analytics',
          'AdminSettings': 'Paramètres',
          'FullAgenda': 'Agenda',
          'AdminReservations': 'Réservations'
        }

        if (routeNames[route.name]) {
          breadcrumbs.push({
            name: routeNames[route.name],
            path: null  // Page finale, pas de lien
          })
        } else {
          // Fallback : utiliser le nom de la route ou le titre de la page
          const pageName = route.meta?.title || route.name || 'Page'
          breadcrumbs.push({
            name: pageName,
            path: null
          })
        }
      }

      return breadcrumbs
    }
  },

  // Réagir aux changements de route pour mettre à jour les breadcrumbs
  watch: {
    '$route'() {
      // Force la réactivité si nécessaire
      this.$forceUpdate()
    }
  }
}
</script>
