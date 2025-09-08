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

    <!-- Filtres -->
    <div class="categories-filters">
      <div class="filter-group">
        <select v-model="selectedType" @change="filterByType" class="filter-select">
          <option value="">Tous les types</option>
          <option value="Activity">🏕️ Activités</option>
          <option value="Menu">🍽️ Menus</option>
          <option value="Dish">🍲 Plats</option>
          <option value="Room">🏠 Hébergements</option>
          <option value="Ingredient">🌿 Ingrédients</option>
        </select>
        
        <div class="search-box">
          <i class="fas fa-search"></i>
          <input 
            v-model="searchTerm" 
            type="text" 
            placeholder="Rechercher une catégorie..." 
          />
        </div>
      </div>
    </div>
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
import ProductsApi from '@/services/ProductsApi'  // Utiliser l'API existante

export default {
  name: 'Categories',
  components: {
    CategoryBadge
    // CategoryModal,  // À ajouter plus tard
    // ConfirmModal   // À ajouter plus tard
  },
  
  data() {
    return {
      categories: [],
      selectedType: '',
      searchTerm: '',
      loading: false,
      
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
      if (this.selectedType) {
        filtered = { [this.selectedType]: filtered[this.selectedType] || [] }
      }
      
      // Filtrer par recherche
      if (this.searchTerm) {
        Object.keys(filtered).forEach(type => {
          filtered[type] = filtered[type].filter(cat =>
            cat.name.toLowerCase().includes(this.searchTerm.toLowerCase()) ||
            cat.description?.toLowerCase().includes(this.searchTerm.toLowerCase())
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
        this.categories = await ProductsApi.getCategories()  // Utiliser l'API existante
      } catch (error) {
        console.error('Erreur lors du chargement des catégories:', error)
      } finally {
        this.loading = false
      }
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
      // TODO: Implémenter le comptage des produits par catégorie
      return Math.floor(Math.random() * 20) // Placeholder
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
      // TODO: Implémenter avec ProductsApi ou créer CategoriesApi
      console.log('Sauvegarder catégorie:', categoryData)
      this.closeModal()
    },

    async confirmDelete() {
      // TODO: Implémenter avec ProductsApi ou créer CategoriesApi  
      console.log('Supprimer catégorie:', this.categoryToDelete)
      this.showDeleteModal = false
      this.categoryToDelete = null
    },

    closeModal() {
      this.showCreateModal = false
      this.showEditModal = false
      this.selectedCategory = null
    },

    filterByType() {
      // La réactivité se charge du filtrage
    }
  }
}
</script>

