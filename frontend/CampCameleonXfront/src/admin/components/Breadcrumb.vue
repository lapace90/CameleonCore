<template>
  <!-- Mode navigation pour Header.vue -->
  <nav v-if="!productType && breadcrumbs.length" class="breadcrumb">
    <template v-for="(crumb, index) in breadcrumbs" :key="index">
      <!-- Lien actif -->
      <router-link v-if="crumb.path" :to="crumb.path" class="breadcrumb-item">
        {{ crumb.name }}
      </router-link>
      <!-- Élément final (pas de lien) -->
      <span v-else class="breadcrumb-item active">
        {{ crumb.name }}
      </span>
    </template>
  </nav>
  
  <!-- Mode simple pour ProductForm.vue -->
  <div v-else class="breadcrumb">
    <span>{{ typeConfig.label }}</span>
    <i class="fas fa-chevron-right"></i>
    <span>{{ isEditing ? product?.name : `Nouveau ${typeConfig.singular}` }}</span>
  </div>
</template>

<script>
import { PRODUCT_CONFIGS } from '@/shared/configs/productConfigs'

export default {
  name: 'Breadcrumb',

  props: {
    // Pour le mode Header
    items: {
      type: Array,
      default: () => []
    },

    // Pour le mode ProductForm  
    productType: {
      type: String,
      default: null
    },

    isEditing: {
      type: Boolean,
      default: false
    },

    product: {
      type: Object,
      default: null
    }
  },

  data() {
    return {

    }
  },

  computed: {
    typeConfig() {
      return PRODUCT_CONFIGS[this.productType] || PRODUCT_CONFIGS.activity
    },

    breadcrumbs() {
      // Si items fournis, les utiliser
      if (this.items && this.items.length > 0) {
        return this.items
      }

      // Sinon génération auto
      return this.generateBreadcrumbs()
    }
  },

  methods: {
    generateBreadcrumbs() {
      const route = this.$route
      const breadcrumbs = []

      // Pas de breadcrumb sur dashboard
      if (route.name === 'AdminDashboard') {
        return
      }

      // Dashboard en premier
      breadcrumbs.push({
        name: 'Dashboard',
        path: '/admin/dashboard'
      })

      // Routes produits
      if (route.path.includes('/products/')) {
        const productType = route.params.type
        const config = PRODUCT_CONFIGS[productType]
        if (config) {
          breadcrumbs.push({
            name: config.label,
            path: `/admin/products/${productType}`
          })

          if (route.name === 'ProductCreate') {
            breadcrumbs.push({ name: 'Création', path: null })
          } else if (route.name === 'ProductEdit') {
            breadcrumbs.push({ name: 'Modification', path: null })
          } else if (route.name === 'ProductDetail') {
            breadcrumbs.push({ name: 'Détails', path: null })
          }
        }
      }
      // Autres routes
      else {
        const routeNames = {
          'AdminUsers': 'Utilisateurs',
          'AdminAnalytics': 'Analytics',
          'AdminSettings': 'Paramètres',
          'FullAgenda': 'Agenda'
        }

        if (routeNames[route.name]) {
          breadcrumbs.push({
            name: routeNames[route.name],
            path: null
          })
        }
      }

      return breadcrumbs
    }
  }
}
</script>
