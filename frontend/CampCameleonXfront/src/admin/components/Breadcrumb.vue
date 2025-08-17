<template>
  
  <!-- Mode navigation pour Header.vue -->
 <nav v-if="!productType && breadcrumbs.length" class="breadcrumb">

    <router-link 
      v-for="(crumb, index) in breadcrumbs" 
      :key="index"
      :to="crumb.path"
      class="breadcrumb-item"
      :class="{ 'active': index === breadcrumbs.length - 1 }"
    >
      {{ crumb.name }}
    </router-link>
  </nav>

  <!-- Mode simple pour ProductForm.vue -->
  <div v-else class="breadcrumb">
    <span>{{ typeConfig.label }}</span>
    <i class="fas fa-chevron-right"></i>
    <span>{{ isEditing ? product?.name : `Nouveau ${typeConfig.singular}` }}</span>
  </div>
</template>

<script>
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
      productConfigs: {
        ingredient: {
          label: 'Ingrédients',
          singular: 'Ingrédient',
          icon: 'fas fa-seedling',
          color: '#22c55e'
        },
        activity: {
          label: 'Activités',
          singular: 'Activité',
          icon: 'fas fa-hiking',
          color: '#3b82f6'
        },
        dish: {
          label: 'Plats',
          singular: 'Plat',
          icon: 'fas fa-drumstick-bite',
          color: '#f97316'
        },
        menu: {
          label: 'Menus',
          singular: 'Menu',
          icon: 'fas fa-utensils',
          color: '#10b981'
        },
        room: {
          label: 'Hébergements',
          singular: 'Hébergement',
          icon: 'fas fa-bed',
          color: '#f59e0b'
        }
      }
    }
  },

  computed: {
    typeConfig() {
      return this.productConfigs[this.productType] || this.productConfigs.activity
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
        const config = this.productConfigs[productType]
        
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

