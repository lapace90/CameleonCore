// frontend/CampCameleonXfront/src/admin/views/Categories.vue
<template>
    
  <div class="categories-page">
    <!-- Header -->
    <div class="page-header">
      <div class="header-left">
        <h1 class="page-title">
          <i class="fas fa-tags"></i>
          Gestion des Catégories
        </h1>
        <p class="page-subtitle">{{ totalCategories }} catégories organisées par type</p>
      </div>
      <div class="header-actions">
        <!-- TODO: Réactiver quand CategoryModal sera créé -->
        <!-- <button @click="showCreateModal = true" class="btn btn-primary">
          <i class="fas fa-plus"></i>
          Nouvelle catégorie
        </button> -->
        <span class="text-muted">Gestion en lecture seule pour l'instant</span>
      </div>
    </div>

    <!-- ✅ AdminFilterBar remplace uniquement categories-filters -->
    <AdminFilterBar
      v-model="filters"
      :default-filters="defaultFilters"
      :fields="filterFields"
      search-placeholder="Rechercher une catégorie..."
      @apply="applyFilters"
    />

    <!-- Loading -->
    <div v-if="loading" class="loading-state">
      <div class="spinner"></div>
      <p>Chargement...</p>
    </div>

    <!-- Vue par type -->
    <div class="categories-content">
      <div 
        v-for="(typeCategories, typeName) in filteredGroupedCategories" 
        :key="typeName"
        class="category-type-section"
      >
        <div class="type-header">
          <div class="type-info">
            <h3>{{ getTypeLabel(typeName) }}</h3>
            <span class="count">{{ typeCategories.length }} catégories</span>
          </div>
          <!-- TODO: Réactiver quand CategoryModal sera créé -->
          <!-- <button @click="createCategory(typeName)" class="btn btn-outline btn-sm">
            <i class="fas fa-plus"></i>
            Ajouter
          </button> -->
        </div>

        <div class="categories-grid">
          <div 
            v-for="category in typeCategories" 
            :key="category.id"
            class="category-card"
          >
            <div class="category-content">
              <div class="category-header">
                <CategoryBadge :category="category" size="normal" />
                <div class="category-actions">
                  <!-- TODO: Réactiver quand les modales seront créées -->
                  <!-- <button @click="editCategory(category)" class="btn-icon" title="Modifier">
                    <i class="fas fa-edit"></i>
                  </button>
                  <button @click="deleteCategory(category)" class="btn-icon btn-danger" title="Supprimer">
                    <i class="fas fa-trash"></i>
                  </button> -->
                  <small class="text-muted">Actions à venir</small>
                </div>
              </div>
              
              <p class="category-description">{{ category.description }}</p>
              
              <div class="category-stats">
                <span class="stat-item">
                  <i class="fas fa-box"></i>
                  {{ getCategoryProductCount(category.id) }} produits
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- TODO: Ajouter les modales plus tard -->
    <!-- Modal de création/édition -->
    <!-- Modal de confirmation suppression -->
  </div>
</template>

<script>
import CategoryBadge from '@/admin/views/products/components/CategoryBadge.vue'
import AdminFilterBar from '@/admin/components/ui/AdminFilterBar.vue'
import ProductsApi from '@/services/ProductsApi'

export default {
  name: 'Categories',
  components: {
    CategoryBadge,
    AdminFilterBar
  },
  
  data() {
    return {
      categories: [],
      loading: false,
      
      // Filtres pour AdminFilterBar
      defaultFilters: {
        search: '',
        type: ''
      },
      filters: {
        search: '',
        type: ''
      },
      
      // Modals
      showCreateModal: false,
      showEditModal: false,
      showDeleteModal: false,
      selectedCategory: null,
      categoryToDelete: null,
      modalMode: 'create'
    }
  },

  computed: {
    // Config pour AdminFilterBar
    filterFields() {
      return [
        {
          key: 'type',
          type: 'select',
          placeholder: 'Tous les types',
          options: [
            { value: 'Activity', label: '🏕️ Activités' },
            { value: 'Menu', label: '🍽️ Menus' },
            { value: 'Dish', label: '🍲 Plats' },
            { value: 'Room', label: '🏠 Hébergements' },
            { value: 'Ingredient', label: '🌿 Ingrédients' }
          ]
        }
      ]
    },

    totalCategories() {
      return this.categories.length
    },

    groupedCategories() {
      const grouped = {}
      this.categories.forEach(category => {
        if (!grouped[category.type]) {
          grouped[category.type] = []
        }
        grouped[category.type].push(category)
      })
      return grouped
    },

    filteredGroupedCategories() {
      let filtered = { ...this.groupedCategories }
      
      // Filtrer par type
      if (this.filters.type) {
        filtered = { [this.filters.type]: filtered[this.filters.type] || [] }
      }
      
      // Filtrer par recherche
      if (this.filters.search) {
        Object.keys(filtered).forEach(type => {
          filtered[type] = filtered[type].filter(cat =>
            cat.name.toLowerCase().includes(this.filters.search.toLowerCase()) ||
            cat.description?.toLowerCase().includes(this.filters.search.toLowerCase())
          )
        })
      }
      
      return filtered
    }
  },

  mounted() {
    this.fetchCategories()
  },

  methods: {
    async fetchCategories() {
      this.loading = true
      try {
        this.categories = await ProductsApi.getCategories()
      } catch (error) {
        console.error('Erreur lors du chargement des catégories:', error)
      } finally {
        this.loading = false
      }
    },

    applyFilters() {
      // Filtrage via computed
    },

    getTypeLabel(type) {
      const labels = {
        'Activity': '🏕️ Activités',
        'Menu': '🍽️ Menus',
        'Dish': '🍲 Plats', 
        'Room': '🏠 Hébergements',
        'Ingredient': '🌿 Ingrédients'
      }
      return labels[type] || type
    },

    getCategoryProductCount(categoryId) {
      return Math.floor(Math.random() * 20)
    },

    createCategory(type = null) {
      this.selectedCategory = type ? { type } : null
      this.modalMode = 'create'
      this.showCreateModal = true
    },

    editCategory(category) {
      this.selectedCategory = { ...category }
      this.modalMode = 'edit'
      this.showEditModal = true
    },

    deleteCategory(category) {
      this.categoryToDelete = category
      this.showDeleteModal = true
    },

    async handleSaveCategory(categoryData) {
      console.log('Sauvegarder catégorie:', categoryData)
      this.closeModal()
    },

    async confirmDelete() {
      console.log('Supprimer catégorie:', this.categoryToDelete)
      this.showDeleteModal = false
      this.categoryToDelete = null
    },

    closeModal() {
      this.showCreateModal = false
      this.showEditModal = false
      this.selectedCategory = null
    }
  }
}
</script>