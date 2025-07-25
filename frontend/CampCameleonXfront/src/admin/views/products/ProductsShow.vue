<template>
  <div class="products-container">
    <!-- Header avec titre et actions -->
    <div class="products-header">
      <div class="header-left">
        <div class="product-type-info">
          <div class="type-icon" :style="{ backgroundColor: typeConfig.color }">
            <i :class="typeConfig.icon"></i>
          </div>
          <div class="type-details">
            <h1 class="page-title">{{ typeConfig.label }}</h1>
            <p class="page-subtitle">Gestion de {{ typeConfig.label.toLowerCase() }}</p>
          </div>
        </div>
      </div>
      <div class="header-actions">
        <button @click="exportProducts" class="btn btn-secondary">
          <i class="fas fa-download"></i>
          Exporter
        </button>
        <router-link :to="{ name: 'ProductCreate', params: { type: type } }" class="btn btn-primary">
          <i class="fas fa-plus"></i>
          Nouveau {{ typeConfig.singular }}
        </router-link>
      </div>
    </div>

    <!-- Filtres et recherche -->
    <div class="products-filters">
      <div class="filters-row">
        <!-- Recherche -->
        <div class="col-2">
          <div class="search-box">
            <i class="fas fa-search"></i>
            <input v-model="filters.search" type="text" placeholder="Rechercher..." @input="debouncedSearch"
              style="width: 95%" />
          </div>
        </div>
        <!-- Filtres -->
        <div class="filter-group col-3">
          <select v-model="filters.category" @change="fetchProducts" class="filter-select">
            <option value="">Toutes les catégories</option>
            <option v-for="category in categories" :key="category.id" :value="category.id">
              {{ category.name }}
            </option>
          </select>

          <select v-model="filters.status" @change="fetchProducts" class="filter-select">
            <option value="">Tous les statuts</option>
            <option value="active">Actif</option>
            <option value="inactive">Inactif</option>
            <option value="draft">Brouillon</option>
          </select>

          <button @click="resetFilters" class="btn btn-outline">
            <i class="fas fa-times"></i>
            Reset
          </button>
        </div>

        <!-- Sélection d'affichage -->
        <div class="view-switcher">
          <button @click="viewMode = 'grid'" class="view-btn" :class="{ active: viewMode === 'grid' }">
            <i class="fas fa-th"></i>
          </button>
          <button @click="viewMode = 'list'" class="view-btn" :class="{ active: viewMode === 'list' }">
            <i class="fas fa-list"></i>
          </button>
        </div>
      </div>
    </div>

    <!-- Stats rapides -->
    <div class="products-stats">
      <div class="stat-item">
        <span class="stat-number">{{ stats.total }}</span>
        <span class="stat-label">Total</span>
      </div>
      <div class="stat-item">
        <span class="stat-number">{{ stats.active }}</span>
        <span class="stat-label">Actifs</span>
      </div>
      <div class="stat-item">
        <span class="stat-number">{{ stats.draft }}</span>
        <span class="stat-label">Brouillons</span>
      </div>
      <div class="stat-item">
        <span class="stat-number">{{ stats.revenue }}</span>
        <span class="stat-label">CA moyen</span>
      </div>
    </div>

    <!-- Actions en lot -->
    <div v-if="selectedProducts.length > 0" class="bulk-actions">
      <div class="bulk-info">
        <span>{{ selectedProducts.length }} produit(s) sélectionné(s)</span>
      </div>
      <div class="bulk-buttons">
        <button @click="bulkAction('activate')" class="btn btn-success btn-sm">
          <i class="fas fa-check"></i>
          Activer
        </button>
        <button @click="bulkAction('deactivate')" class="btn btn-warning btn-sm">
          <i class="fas fa-pause"></i>
          Désactiver
        </button>
        <button @click="bulkAction('delete')" class="btn btn-danger btn-sm">
          <i class="fas fa-trash"></i>
          Supprimer
        </button>
      </div>
    </div>

    <!-- Message d'erreur -->
    <div v-if="error" class="error-message">
      <div class="alert alert-danger">
        <i class="fas fa-exclamation-triangle"></i>
        {{ error }}
        <button @click="error = null" class="btn-close">&times;</button>
      </div>
    </div>

    <!-- Contenu principal -->
    <div class="products-content">
      <!-- Loading -->
      <div v-if="loading" class="loading-state">
        <div class="spinner"></div>
        <p>Chargement des {{ typeConfig.label }}...</p>
      </div>

      <!-- Vue grille -->
      <div v-else-if="viewMode === 'grid'" class="products-grid">
        <div v-for="product in filteredProducts" :key="product.id" class="product-card"
          :class="{ selected: selectedProducts.includes(product.id) }">
          <div class="card-header">
            <input type="checkbox" :value="product.id" v-model="selectedProducts" class="product-checkbox" />
            <div class="product-status" :class="getStatusClass(product)">
              {{ getStatusLabel(product) }}
            </div>
          </div>

          <div class="card-image">
            <img :src="getValidImageUrl(product.image)" :alt="product.name" @error="handleImageError" />
            <div class="image-overlay">
              <button @click="viewProduct(product)" class="overlay-btn">
                <i class="fas fa-eye"></i>
              </button>
              <button @click="editProduct(product)" class="overlay-btn">
                <i class="fas fa-edit"></i>
              </button>
            </div>
          </div>

          <div class="card-content">
            <h3 class="product-name">{{ product.name }}</h3>
            <p class="product-category">{{ getProductCategoryName(product) }}</p>
            <p class="product-description">{{ truncateText(product.description, 80) }}</p>

            <!-- Champs spécifiques par type -->
            <!-- <div class="product-specifics" v-if="product.productable">
              <div v-for="(value, key) in product.productable" :key="key" class="specific-field">
                <span class="field-label" v-if="key && typeof key === 'string'">{{ getFieldLabel(key) }}:</span>
                <span class="field-value">{{ formatFieldValue(product.productable, key) }}</span>
              </div>
            </div> -->

            <div class="card-footer">
              <div class="product-price">
                {{ formatPrice(product.price) }}
              </div>
              <div class="card-actions">
                <button @click="duplicateProduct(product)" class="btn-icon" title="Dupliquer">
                  <i class="fas fa-copy"></i>
                </button>
                <button @click="toggleStatus(product)" class="btn-icon"
                  :title="product.status ? 'Désactiver' : 'Activer'">
                  <i :class="product.status ? 'fas fa-pause' : 'fas fa-play'"></i>
                </button>
                <button @click="deleteProduct(product)" class="btn-icon text-danger" title="Supprimer">
                  <i class="fas fa-trash"></i>
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Vue liste -->
      <div v-else class="products-table">
        <table class="table">
          <thead>
            <tr>
              <th>
                <input type="checkbox" :checked="allSelected" @change="toggleAllSelection" />
              </th>
              <th>Image</th>
              <th @click="sortBy('name')" class="sortable">
                Nom
                <i class="fas fa-sort"></i>
              </th>
              <th>Catégorie</th>
              <th v-for="field in safeListColumns" :key="field" @click="sortBy(field)" class="sortable">
                {{ getFieldLabel(field) }}
                <i class="fas fa-sort"></i>
              </th>
              <th @click="sortBy('price')" class="sortable">
                Prix
                <i class="fas fa-sort"></i>
              </th>
              <th>Statut</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="product in filteredProducts" :key="product.id"
              :class="{ selected: selectedProducts.includes(product.id) }">
              <td>
                <input type="checkbox" :value="product.id" v-model="selectedProducts" />
              </td>
              <td>
                <div class="table-image">
                  <img :src="getValidImageUrl(product.image)" :alt="product.name" @error="handleImageError" />
                </div>
              </td>
              <td>
                <div class="product-name-cell">
                  <strong>{{ product.name }}</strong>
                  <p class="product-description-mini">{{ truncateText(product.description, 50) }}</p>
                </div>
              </td>
              <td>
                <span class="category-badge">{{ getProductCategoryName(product) }}</span>
              </td>
              <td v-for="field in safeListColumns" :key="field">
                {{ formatFieldValue(product.productableData, field) }}
              </td>
              <td>
                <span class="price-value">{{ formatPrice(product.price) }}</span>
              </td>
              <td>
                <span class="status-badge" :class="getStatusClass(product)">
                  {{ getStatusLabel(product) }}
                </span>
              </td>
              <td>
                <div class="table-actions">
                  <button @click="viewProduct(product)" class="btn-icon" title="Voir">
                    <i class="fas fa-eye"></i>
                  </button>
                  <button @click="editProduct(product)" class="btn-icon" title="Modifier">
                    <i class="fas fa-edit"></i>
                  </button>
                  <button @click="duplicateProduct(product)" class="btn-icon" title="Dupliquer">
                    <i class="fas fa-copy"></i>
                  </button>
                  <button @click="deleteProduct(product)" class="btn-icon text-danger" title="Supprimer">
                    <i class="fas fa-trash"></i>
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Empty state -->
      <div v-if="!loading && filteredProducts.length === 0" class="empty-state">
        <div class="empty-icon">
          <i :class="typeConfig.icon"></i>
        </div>
        <h3>Aucun {{ typeConfig.singular.toLowerCase() }} trouvé</h3>
        <p>Commencez par créer votre premier {{ typeConfig.singular.toLowerCase() }}</p>
        <router-link :to="{ name: 'ProductCreate', params: { type: type } }" class="btn btn-primary">
          <i class="fas fa-plus"></i>
          Créer {{ typeConfig.singular }}
        </router-link>
      </div>
    </div>

    <!-- Pagination -->
    <div v-if="pagination.total > pagination.perPage" class="products-pagination">
      <div class="pagination-info">
        Affichage de {{ pagination.from }} à {{ pagination.to }} sur {{ pagination.total }} résultats
      </div>
      <div class="pagination-controls">
        <button @click="changePage(pagination.currentPage - 1)" :disabled="pagination.currentPage === 1"
          class="btn btn-outline btn-sm">
          <i class="fas fa-chevron-left"></i>
          Précédent
        </button>

        <span class="pagination-pages">
          <button v-for="page in visiblePages" :key="page" @click="changePage(page)" class="btn btn-sm"
            :class="page === pagination.currentPage ? 'btn-primary' : 'btn-outline'">
            {{ page }}
          </button>
        </span>

        <button @click="changePage(pagination.currentPage + 1)"
          :disabled="pagination.currentPage === pagination.lastPage" class="btn btn-outline btn-sm">
          Suivant
          <i class="fas fa-chevron-right"></i>
        </button>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios'

export default {
  name: 'ProductsShow',
  props: {
    type: {
      type: String,
      required: true
    }
  },

  data() {
    return {
      loading: false,
      viewMode: 'grid',
      products: [],
      filteredProducts: [],
      categories: [],
      selectedProducts: [],
      searchTimeout: null,
      error: null,

      filters: {
        search: '',
        category: '',
        status: ''
      },

      pagination: {
        currentPage: 1,
        perPage: 20,
        total: 0,
        lastPage: 1,
        from: 0,
        to: 0
      },

      stats: {
        total: 0,
        active: 0,
        draft: 0,
        revenue: '0€'
      },

      productConfigs: {
        activity: {
          label: 'Activités',
          singular: 'Activité',
          icon: 'fas fa-hiking',
          color: '#3b82f6',
          gridFields: ['duration', 'capacity'],
          listColumns: ['duration', 'capacity']
        },
        menu: {
          label: 'Menus',
          singular: 'Menu',
          icon: 'fas fa-utensils',
          color: '#10b981',
          gridFields: ['dishes', 'dietary_tags'],
          listColumns: ['dishes', 'dietary_info']
        },
        dish: {
          label: 'Plats',
          singular: 'Plat',
          icon: 'fas fa-drumstick-bite',
          color: '#f97316',
          gridFields: ['ingredients_count', 'dietary_tags'],
          listColumns: ['ingredients_count', 'dietary_info']
        },
        ingredient: {
          label: 'Ingrédients',
          singular: 'Ingrédient',
          icon: 'fas fa-seedling',
          color: '#22c55e',
          gridFields: ['stock', 'dietary_info'],
          listColumns: ['stock', 'is_vegetarian', 'is_vegan']
        },
        room: {
          label: 'Hébergements',
          singular: 'Hébergement',
          icon: 'fas fa-bed',
          color: '#f59e0b',
          gridFields: ['capacity'],
          listColumns: ['capacity']
        },
        option: {
          label: 'Options',
          singular: 'Option',
          icon: 'fas fa-puzzle-piece',
          color: '#8b5cf6',
          gridFields: ['type'],
          listColumns: ['type']
        }
      }
    }
  },

  computed: {
    typeConfig() {
      return this.productConfigs[this.type] || this.productConfigs.activity
    },

    safeListColumns() {
      const columns = this.typeConfig.listColumns || []
      return columns.filter(col => col && typeof col === 'string')
    },

    allSelected() {
      return this.filteredProducts.length > 0 && this.selectedProducts.length === this.filteredProducts.length
    },

    visiblePages() {
      const pages = []
      const current = this.pagination.currentPage
      const last = this.pagination.lastPage

      for (let i = Math.max(1, current - 2); i <= Math.min(last, current + 2); i++) {
        pages.push(i)
      }

      return pages
    }
  },

  created() {
    this.debouncedSearch = this.debounce(this.applyFilters, 500)
    this.fetchProducts()
    this.fetchCategories()
  },

  methods: {
    debounce(func, wait) {
      let timeout
      return function executedFunction(...args) {
        const later = () => {
          clearTimeout(timeout)
          func.apply(this, args)
        }
        clearTimeout(timeout)
        timeout = setTimeout(later, wait)
      }.bind(this)
    },

    async fetchProducts() {
      this.loading = true
      this.error = null

      try {
        const params = new URLSearchParams()
        if (this.type) {
          // Convertir le type en nom de classe complet pour API Platform
          const typeMap = {
            'activity': 'App\\Models\\Activity',
            'room': 'App\\Models\\Room',
            'menu': 'App\\Models\\Menu',
            'dish': 'App\\Models\\Dish',
            'ingredient': 'App\\Models\\Ingredient',
            'option': 'App\\Models\\Option'
          }
          params.append('type', typeMap[this.type])
        }
        if (this.filters.search) {
          params.append('search', this.filters.search)
        }
        if (this.filters.status) {
          params.append('status', this.filters.status)
        }
        if (this.filters.category) {
          params.append('category_id', this.filters.category)
        }
        params.append('page', this.pagination.currentPage)
        params.append('per_page', this.pagination.perPage)

        const url = `/api/products${params.toString() ? '?' + params.toString() : ''}`
        console.log('Fetching products from:', url)

        const response = await axios.get(url, {
          headers: {
            'Accept': 'application/ld+json',
            'Content-Type': 'application/json'
          }
        })

        console.log('API Response:', response.data)
        console.log('Type envoyé au backend:', this.type)
        console.log('Produits reçus:', response.data.member.map(p => p.productableType))

        if (response.data && response.data.member) {
          this.products = response.data.member
          this.filteredProducts = response.data.member

          if (response.data.totalItems) {
            this.pagination.total = response.data.totalItems
            this.pagination.lastPage = Math.ceil(this.pagination.total / this.pagination.perPage)
          }

          this.updateStats()
        } else {
          this.products = []
          this.filteredProducts = []
        }

      } catch (error) {
        console.error('Erreur lors du chargement des produits:', error)
        this.error = 'Erreur lors du chargement des produits.'
        this.products = []
        this.filteredProducts = []
      } finally {
        this.loading = false
      }
    },

    applyFilters() {
      this.pagination.currentPage = 1
      this.fetchProducts()
    },

    async fetchCategories() {
      try {
        const response = await axios.get('/api/categories', {
          headers: {
            'Accept': 'application/ld+json',
            'Content-Type': 'application/json'
          }
        })

        if (response.data && response.data.member) {
          this.categories = response.data.member
        } else if (Array.isArray(response.data)) {
          this.categories = response.data
        } else {
          this.categories = []
        }
      } catch (error) {
        console.error('Erreur lors du chargement des catégories:', error)
        this.categories = []
      }
    },

    updateStats() {
      const products = this.filteredProducts
      this.stats = {
        total: this.pagination.total || products.length,
        active: products.filter(p => p.status && !p.isDraft).length,
        draft: products.filter(p => p.isDraft).length,
        revenue: this.formatPrice(products.reduce((sum, p) => sum + parseFloat(p.price || 0), 0) / (products.length || 1))
      }
    },

    getProductCategoryName(product) {
      if (typeof product.category === 'string') {
        const categoryId = product.category.split('/').pop()
        const category = this.categories.find(c => c.id == categoryId)
        return category?.name || 'Sans catégorie'
      }
      return product.category?.name || 'Sans catégorie'
    },

    viewProduct(product) {
      this.$router.push({
        name: 'ProductDetail',
        params: { type: this.type, id: product.id }
      })
    },

    editProduct(product) {
      this.$router.push({
        name: 'ProductEdit',
        params: { type: this.type, id: product.id }
      })
    },

    async duplicateProduct(product) {
      if (!confirm(`Dupliquer "${product.name}" ?`)) return

      try {
        const duplicatedData = { ...product }
        delete duplicatedData.id
        delete duplicatedData['@id']
        delete duplicatedData['@type']
        duplicatedData.name = `${product.name} (copie)`

        await axios.post('/api/products', duplicatedData)
        this.fetchProducts()
      } catch (error) {
        console.error('Erreur duplication:', error)
        this.error = 'Erreur lors de la duplication du produit'
      }
    },

    toggleAllSelection() {
      if (this.allSelected) {
        this.selectedProducts = []
      } else {
        this.selectedProducts = this.filteredProducts.map(p => p.id)
      }
    },

    async bulkAction(action) {
      if (!confirm(`Confirmer l'action "${action}" sur ${this.selectedProducts.length} produit(s) ?`)) return

      try {
        const promises = this.selectedProducts.map(async (productId) => {
          const product = this.products.find(p => p.id === productId)
          if (!product) return

          switch (action) {
            case 'activate':
              return axios.patch(`/api/products/${productId}`, { status: true, isDraft: false })
            case 'deactivate':
              return axios.patch(`/api/products/${productId}`, { status: false })
            case 'delete':
              return axios.delete(`/api/products/${productId}`)
          }
        })

        await Promise.all(promises)
        this.selectedProducts = []
        this.fetchProducts()
      } catch (error) {
        console.error(`Erreur action ${action}:`, error)
        this.error = `Erreur lors de l'action ${action}`
      }
    },

    resetFilters() {
      this.filters = { search: '', category: '', status: '' }
      this.pagination.currentPage = 1
      this.fetchProducts()
    },

    sortBy(field) {
      console.log('Tri par:', field)
    },

    changePage(page) {
      if (page >= 1 && page <= this.pagination.lastPage) {
        this.pagination.currentPage = page
        this.fetchProducts()
      }
    },

    async deleteProduct(product) {
      if (!confirm(`Supprimer "${product.name}" ?`)) return

      try {
        await axios.delete(`/api/products/${product.id}`)
        this.fetchProducts()
      } catch (error) {
        console.error('Erreur suppression:', error)
        this.error = 'Erreur lors de la suppression du produit'
      }
    },

    async toggleStatus(product) {
      try {
        await axios.patch(`/api/products/${product.id}`, {
          status: !product.status
        })

        const index = this.products.findIndex(p => p.id === product.id)
        if (index !== -1) {
          this.products[index].status = !product.status
        }

        this.fetchProducts()
      } catch (error) {
        console.error('Erreur toggle status:', error)
        this.error = 'Erreur lors de la modification du statut'
      }
    },

    getFieldLabel(field) {
      if (!field || typeof field !== 'string') {
        return 'N/A'
      }

      const labels = {
        duration: 'Durée',
        capacity: 'Capacité',
        ingredients: 'Ingrédients',
        type: 'Type',
        ingredients_count: 'Nb ingrédients',
        dietary_tags: 'Tags diététiques',
        dietary_info: 'Info diététique',
        stock: 'Stock',
        is_vegetarian: 'Végétarien',
        is_vegan: 'Végan',
        is_spicy: 'Épicé',
        is_gluten_free: 'Sans gluten',
        is_lactose_free: 'Sans lactose',
        is_nut_free: 'Sans noix',
        createdAt: 'Créé le',
        updatedAt: 'Modifié le'
      }
      return labels[field] || field.charAt(0).toUpperCase() + field.slice(1)
    },

    getValidImageUrl(imageUrl) {
      if (!imageUrl) {
        return this.getPlaceholderImage()
      }

      if (imageUrl.includes('storage/https://') || imageUrl.includes('storage/http://')) {
        const match = imageUrl.match(/storage\/(https?:\/\/.+)/)
        if (match && match[1]) {
          return match[1]
        }
      }

      try {
        new URL(imageUrl)
        return imageUrl
      } catch (error) {
        return this.getPlaceholderImage()
      }
    },

    getPlaceholderImage() {
      const svg = `
        <svg width="200" height="200" xmlns="http://www.w3.org/2000/svg">
          <rect width="200" height="200" fill="#f3f4f6"/>
          <text x="100" y="100" text-anchor="middle" dy=".3em" font-family="Arial, sans-serif" font-size="14" fill="#9ca3af">
            Image non disponible
          </text>
        </svg>
      `
      return 'data:image/svg+xml;base64,' + btoa(svg)
    },

    formatDietaryInfo(productable) {
      const tags = []
      if (productable.is_vegan) tags.push('Végan')
      else if (productable.is_vegetarian) tags.push('Végétarien')
      if (productable.is_spicy) tags.push('Épicé')
      if (productable.is_gluten_free) tags.push('Sans gluten')
      if (productable.is_lactose_free) tags.push('Sans lactose')
      if (productable.is_nut_free) tags.push('Sans noix')

      return tags.length > 0 ? tags.join(', ') : 'Aucune restriction'
    },

    formatFieldValue(productable, field) {
      const data = productable || {}

      if (!field || typeof field !== 'string' || !data) {
        return '-'
      }

      if (data[field] === undefined || data[field] === null) {
        return '-'
      }

      const value = data[field]

      switch (field) {
        case 'duration':
          return `${value} min`
        case 'capacity':
          return `${value} pers.`
        case 'stock':
          return value > 0 ? `${value} unités` : 'Rupture'
        case 'is_vegetarian':
        case 'is_vegan':
        case 'is_spicy':
        case 'is_gluten_free':
        case 'is_lactose_free':
        case 'is_nut_free':
          return value ? '✓' : '✗'
        case 'dietary_info':
          return this.formatDietaryInfo(productable)
        case 'createdAt':
        case 'updatedAt':
          return new Date(value).toLocaleDateString('fr-FR')
        default:
          return value.toString()
      }
    },

    truncateText(text, length) {
      if (!text) return ''
      return text.length > length ? text.substring(0, length) + '...' : text
    },

    handleImageError(event) {
      event.target.src = this.getPlaceholderImage()
      event.target.onerror = null
    },

    exportProducts() {
      console.log('Export des produits')
    },

    getStatusClass(product) {
      if (product.is_draft || product.isDraft) return 'status-draft'
      return product.status ? 'status-active' : 'status-inactive'
    },

    getStatusLabel(product) {
      if (product.is_draft || product.isDraft) return 'Brouillon'
      return product.status ? 'Actif' : 'Inactif'
    },

    formatPrice(price) {
      return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'EUR'
      }).format(parseFloat(price) || 0)
    }
  }
}
</script>
