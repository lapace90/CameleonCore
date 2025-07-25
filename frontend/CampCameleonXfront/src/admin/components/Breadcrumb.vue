<!--  Créer ce composant pour gerer dynamiquement le breadcrumb dans AdminHeader sans trop charger -->


<template>
    <!-- From Header.vue -->
     <nav class="breadcrumb" v-if="breadcrumbs.length">
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

        <!-- From ProductFrom.vue -->

         <div class="breadcrumb">
            <span>{{ typeConfig.label }}</span>
            <i class="fas fa-chevron-right"></i>
            <span>{{ isEditing ? product?.name : `Nouveau ${typeConfig.singular}` }}</span>
          </div>
</template>
<script>
// From ProductFrom.vue
data() {
    productConfigs: {
        ingredient: {
          label: 'Ingrédients',
          singular: 'Ingrédient', 
          icon: 'fas fa-seedling',
          color: '#22c55e',
          fields: ['stock', 'is_vegetarian', 'is_vegan', 'is_spicy', 'is_gluten_free', 'is_lactose_free', 'is_nut_free'],
          hasRelation: 'dishes'
        },
        activity: {
          label: 'Activités',
          singular: 'Activité',
          icon: 'fas fa-hiking', 
          color: '#3b82f6',
          fields: ['guide', 'duration', 'meeting_point', 'max_people', 'difficulty_level'],
          hasRelation: false
        },
        dish: {
          label: 'Plats',
          singular: 'Plat',
          icon: 'fas fa-drumstick-bite',
          color: '#f97316', 
          fields: [],
          hasRelation: 'ingredients'
        },
        menu: {
          label: 'Menus',
          singular: 'Menu',
          icon: 'fas fa-utensils',
          color: '#10b981',
          fields: [],
          hasRelation: 'dishes'  
        },
        room: {
          label: 'Hébergements',
          singular: 'Hébergement',
          icon: 'fas fa-bed',
          color: '#f59e0b',
          fields: ['capacity', 'availability'],
          hasRelation: false
        }
      },

}
  computed: {
    isEditing() {
      return this.action === 'edit'
    },

    typeConfig() {
      return this.productConfigs[this.productType] || this.productConfigs.activity
    },

</script>