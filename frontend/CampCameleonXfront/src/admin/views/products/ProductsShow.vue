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
          </div>
        </div>
      </div>
      <div class="header-actions">
        <button @click="exportProducts" class="btn btn-outline btn-sm">
          <i class="fas fa-download"></i>
          Exporter
        </button>
        <router-link :to="createRoute" class="btn btn-primary btn-sm">
          <i class="fas fa-plus"></i>
          Nouveau {{ typeConfig.singular }}
        </router-link>
      </div>
    </div>

    <!-- Stats rapides -->
    <ProductStats :stats="quickStats" :products="products" />

    <!-- Filtres et recherche -->
    <AdminFilterBar v-model="filters" :default-filters="defaultFilters" :fields="productFilterFields"
      search-placeholder="Rechercher un produit..." :search-debounce="400" reset-label="Réinitialiser"
      @apply="onFiltersApply">
      <!-- Actions personnalisées : switcher vue -->
      <template #actions>
        <div class="view-switcher">
          <button @click="viewMode = 'grid'" class="view-btn" :class="{ active: viewMode === 'grid' }"
            title="Vue grille">
            <i class="fas fa-th"></i>
          </button>
          <button @click="viewMode = 'list'" class="view-btn" :class="{ active: viewMode === 'list' }"
            title="Vue liste">
            <i class="fas fa-list"></i>
          </button>
        </div>
      </template>

      <!-- Infos de résultats dans le footer -->
      <template #footer="{ activeCount }">
        <div class="filters-footer-content">
          <span class="results-info">
            <i class="fas fa-box"></i>
            {{ visibleProducts.length }} produit(s) affiché(s) sur {{ pagination.total }}
          </span>
          <span v-if="activeCount > 0" class="active-filters-info">
            <i class="fas fa-filter"></i>
            {{ activeCount }} filtre(s) actif(s)
          </span>
        </div>
      </template>
    </AdminFilterBar>

    <!-- Actions en lot -->
    <BulkActions v-if="selectedProducts.length > 0" :selected-count="selectedProducts.length"
      @bulk-action="handleBulkAction" @clear-selection="selectedProducts = []" />

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
      <LoadingState v-if="loading" state="loading" variant="inline"
        :loading-text="`Chargement des ${typeConfig.label}...`" />

      <!-- Empty state -->
      <div v-else-if="visibleProducts.length === 0" class="empty-state">
        <div class="empty-icon">
          <i :class="typeConfig.icon"></i>
        </div>
        <h3>Aucun {{ typeConfig.singular.toLowerCase() }} trouvé</h3>
        <p>{{ emptyStateMessage }}</p>
        <router-link v-if="pagination.total === 0" :to="createRoute" class="btn btn-primary">
          <i class="fas fa-plus"></i>
          Créer le premier {{ typeConfig.singular.toLowerCase() }}
        </router-link>
        <button v-else @click="resetFilters" class="btn btn-outline">
          <i class="fas fa-times"></i>
          Réinitialiser les filtres
        </button>
      </div>

      <!-- Vue grille -->
      <div v-else-if="viewMode === 'grid'" class="products-grid">
        <ProductCard v-for="product in visibleProducts" :key="product.id" :product="product"
          :selected="selectedProducts.includes(product.id)" @select="toggleSelection(product.id)" @view="viewProduct"
          @edit="editProduct" @duplicate="duplicateProduct" @delete="deleteProduct"
          @toggle-status="toggleProductStatus" />
      </div>

      <!-- Vue liste -->
      <div v-else class="products-table">
        <ProductTable :products="visibleProducts" :type-config="typeConfig" :selected="selectedProducts"
          @select="toggleSelection" @select-all="toggleAllSelection" @view="viewProduct" @edit="editProduct"
          @duplicate="duplicateProduct" @delete="deleteProduct" @sort="handleSort" />
      </div>
    </div>

    <!-- Pagination -->
    <Pagination v-if="pagination.total > pagination.perPage" :pagination="pagination" @page-change="changePage" />
  </div>
</template>

<script>
import ProductsApi from '@/services/ProductsApi'
import ProductCard from './components/ProductCard.vue'
import ProductTable from './components/ProductTable.vue'
import ProductStats from './components/ProductStats.vue'
import BulkActions from './components/BulkActions.vue'
import Pagination from './components/Pagination.vue'
import CategoryBadge from './components/CategoryBadge.vue'
import AdminFilterBar from '@/admin/components/ui/AdminFilterBar.vue'
import { PRODUCT_CONFIGS } from '@/shared/configs/productConfigs'
import LoadingState from '@/admin/components/ui/LoadingState.vue'

export default {
  name: 'ProductsShow',
  components: {
    ProductCard,
    ProductTable,
    CategoryBadge,
    ProductStats,
    BulkActions,
    Pagination,
    AdminFilterBar,
    LoadingState
  },
  props: {
    type: { type: String, required: true }
  },

  data() {
    return {
      loading: false,
      viewMode: 'grid',
      products: [],
      selectedProducts: [],
      error: null,

      // Filtres avec valeurs par défaut
      defaultFilters: {
        search: '',
        category: '',
        status: ''
      },

      filters: {
        search: '',
        category: '',
        status: ''
      },

      sort: {
        by: 'createdAt',
        dir: 'desc'
      },

      pagination: {
        currentPage: 1,
        perPage: 20,
        total: 0,
        lastPage: 1
      },

      quickStats: {
        total: 0,
        active: 0,
        draft: 0,
        revenue: 0
      }
    }
  },

  computed: {
    typeConfig() {
      return PRODUCT_CONFIGS[this.type] || PRODUCT_CONFIGS.activity
    },

    // Catégories disponibles (calculé depuis les produits chargés)
    availableCategories() {
      const toCategory = (p) => {
        const c = p?.category
        if (!c || (!c.id && !c.name)) {
          return { id: 'null', name: 'Sans catégorie' }
        }
        return {
          id: String(c.id ?? c.slug ?? c.name),
          name: c.name ?? String(c.id)
        }
      }

      const map = new Map()
      for (const p of this.products) {
        const c = toCategory(p)
        if (!map.has(c.id)) {
          map.set(c.id, { ...c, count: 0 })
        }
        map.get(c.id).count++
      }

      return Array.from(map.values()).sort((a, b) =>
        a.name.localeCompare(b.name)
      )
    },

    // Options pour le select de catégories
    categoryOptions() {
      // Si pas encore de produits chargés, retourner un array vide
      if (!this.products || this.products.length === 0) {
        return []
      }

      return this.availableCategories.map(category => ({
        label: `${category.name} (${category.count})`,
        value: category.id
      }))
    },

    // Configuration des champs de filtre
    productFilterFields() {
      return [
        {
          key: 'category',
          label: 'Catégorie',
          type: 'select',
          placeholder: 'Toutes les catégories',
          options: this.categoryOptions // Réactif, se met à jour automatiquement
        },
        {
          key: 'status',
          label: 'Statut',
          type: 'select',
          placeholder: 'Tous les statuts',
          options: [
            { label: 'Actif', value: 'active' },
            { label: 'Inactif', value: 'inactive' },
            { label: 'Brouillon', value: 'draft' }
          ]
        }
      ]
    },

    // Produits filtrés (côté client)
    visibleProducts() {
      const search = (this.filters.search || '').toLowerCase().trim()
      const wantedCat = this.filters.category ? String(this.filters.category) : ''
      const wantedStatus = this.filters.status

      const catOf = (p) => {
        const c = p?.category
        if (!c || (!c.id && !c.name)) return 'null'
        return String(c.id ?? c.slug ?? c.name)
      }

      // Filtrage
      let list = this.products.filter(p => {
        // Statut
        if (wantedStatus === 'active' && !(p.status && !p.is_draft && !p.isDraft)) {
          return false
        }
        if (wantedStatus === 'inactive' && p.status) {
          return false
        }
        if (wantedStatus === 'draft' && !(p.is_draft || p.isDraft)) {
          return false
        }

        // Catégorie
        if (wantedCat && catOf(p) !== wantedCat) {
          return false
        }

        // Recherche
        if (search) {
          const haystack = `${p.name || ''} ${p.description || ''}`.toLowerCase()
          if (!haystack.includes(search)) {
            return false
          }
        }

        return true
      })

      // Tri
      const { by, dir } = this.sort
      if (by) {
        const sign = dir === 'desc' ? -1 : 1
        list = [...list].sort((a, b) => {
          const av = a?.[by] ?? ''
          const bv = b?.[by] ?? ''

          // Nombres
          const aNum = typeof av === 'number' ? av : (Number.isFinite(+av) ? +av : null)
          const bNum = typeof bv === 'number' ? bv : (Number.isFinite(+bv) ? +bv : null)
          if (aNum !== null && bNum !== null) {
            return (aNum - bNum) * sign
          }

          // Dates
          const aTime = Date.parse(av)
          const bTime = Date.parse(bv)
          if (!Number.isNaN(aTime) && !Number.isNaN(bTime)) {
            return (aTime - bTime) * sign
          }

          // Texte
          return String(av).localeCompare(String(bv)) * sign
        })
      }

      return list
    },

    createRoute() {
      return {
        name: 'ProductCreate',
        params: { type: this.type }
      }
    },

    emptyStateMessage() {
      const hasFilters = this.filters.search || this.filters.category || this.filters.status

      if (hasFilters) {
        return 'Aucun résultat ne correspond à vos critères de recherche.'
      }

      return `Commencez par créer votre premier ${this.typeConfig.singular.toLowerCase()}.`
    },

    allSelected() {
      return this.products.length > 0 &&
        this.selectedProducts.length === this.products.length
    }
  },

  created() {
    this.initialize()
  },

  methods: {
    async initialize() {
      await Promise.all([
        this.fetchProducts(),
      ])
    },

    async fetchProducts() {
      this.loading = true
      this.error = null

      try {
        const params = {
          type: this.getApiType(),
          page: this.pagination.currentPage,
          per_page: this.pagination.perPage,
        }

        const response = await ProductsApi.getProducts(params)

        this.products = response.data || []
        this.updatePagination(response)
        this.updateStats()

      } catch (error) {
        console.error('Erreur lors du chargement des produits:', error)
        this.error = 'Erreur lors du chargement des produits'
        this.products = []
      } finally {
        this.loading = false
      }
    },

    // Méthode pour réinitialiser les filtres
    resetFilters() {
      this.filters = { ...this.defaultFilters }
    },

    updatePagination(response) {
      if (response.totalItems !== undefined) {
        this.pagination.total = response.totalItems
        this.pagination.lastPage = Math.ceil(
          response.totalItems / this.pagination.perPage
        )
      }
    },

    updateStats() {
      const active = this.products.filter(p => p.status && !p.is_draft).length
      const draft = this.products.filter(p => p.is_draft).length
      const revenue = this.products.reduce((sum, p) => sum + (p.price || 0), 0)

      this.quickStats = {
        total: this.pagination.total,
        active,
        draft,
        revenue
      }
    },

    getApiType() {
      const typeMap = {
        'activity': 'App\\Models\\Activity',
        'room': 'App\\Models\\Room',
        'menu': 'App\\Models\\Menu',
        'dish': 'App\\Models\\Dish',
        'ingredient': 'App\\Models\\Ingredient'
      }
      return typeMap[this.type]
    },

    onFiltersApply() {
      this.pagination.currentPage = 1
      this.fetchProducts()
    },

    changePage(page) {
      this.pagination.currentPage = page
      this.fetchProducts()
    },

    handleSort(sortBy, direction) {
      // Implémentation du tri
      console.log('Tri par:', sortBy, direction)
      this.sort = { by: sortBy, dir: direction || 'asc' }
    },

    // Actions sur les produits
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
        await ProductsApi.duplicateProduct(product.id)
        this.fetchProducts()
      } catch (error) {
        this.error = 'Erreur lors de la duplication'
      }
    },

    async deleteProduct(product) {
      if (!confirm(`Supprimer "${product.name}" ?`)) return

      try {
        await ProductsApi.deleteProduct(product.id)
        this.fetchProducts()
      } catch (error) {
        this.error = 'Erreur lors de la suppression'
      }
    },

    async toggleProductStatus(product) {
      try {
        await ProductsApi.updateProduct(product.id, { status: !product.status })
        this.fetchProducts()
      } catch (error) {
        this.error = 'Erreur lors de la modification du statut'
      }
    },

    // Sélection
    toggleSelection(productId) {
      const index = this.selectedProducts.indexOf(productId)
      if (index > -1) {
        this.selectedProducts.splice(index, 1)
      } else {
        this.selectedProducts.push(productId)
      }
    },

    toggleAllSelection() {
      if (this.allSelected) {
        this.selectedProducts = []
      } else {
        this.selectedProducts = this.products.map(p => p.id)
      }
    },

    // Actions en lot
    async handleBulkAction(action) {
      if (!confirm(`Confirmer l'action "${action}" sur ${this.selectedProducts.length} produit(s) ?`)) {
        return
      }

      try {
        await ProductsApi.bulkAction(this.selectedProducts, action)
        this.selectedProducts = []
        this.fetchProducts()
      } catch (error) {
        this.error = `Erreur lors de l'action ${action}`
      }
    },

    exportProducts() {
      console.log('Export des produits')
      // Implémentation de l'export
    }
  }
}
</script>
