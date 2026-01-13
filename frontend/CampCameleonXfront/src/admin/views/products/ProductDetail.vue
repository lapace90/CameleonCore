<template>
  <div class="product-detail-container">
    <!-- Loading -->
    <div v-if="loading" class="loading-state">
      <div class="spinner"></div>
      <p>Chargement...</p>
    </div>

    <!-- Erreur -->
    <div v-else-if="error" class="error-state">
      <div class="error-icon">
        <i class="fas fa-exclamation-triangle"></i>
      </div>
      <h3>{{ error }}</h3>
      <router-link :to="backRoute" class="btn btn-primary btn-sm mt-6">
        <i class="fas fa-arrow-left"></i>
        Retour à la liste
      </router-link>
    </div>

    <!-- Contenu principal -->
    <div v-else-if="product" class="product-detail">
      <!-- Header avec navigation -->
      <div class="detail-header">
        <div class="header-navigation">
          <router-link :to="backRoute" class="back-link">
            <i class="fas fa-arrow-left"></i>
            Retour à {{ product.typeConfig.label }}
          </router-link>
        </div>

        <div class="header-actions">
          <button @click="duplicateProduct" class="btn btn-secondary btn-sm">
            <i class="fas fa-copy"></i>
            Dupliquer
          </button>
          <router-link :to="editRoute" class="btn btn-primary btn-sm">
            <i class="fas fa-edit"></i>
            Modifier
          </router-link>
          <button @click="deleteProduct" class="btn btn-danger btn-sm">
            <i class="fas fa-trash"></i>
            Supprimer
          </button>
        </div>
      </div>

      <!-- Titre et statut -->
      <div class="product-title-section">
        <div class="title-left">
          <div class="product-type-badge" :style="{ backgroundColor: product.typeConfig.color }">
            <i :class="product.typeConfig.icon"></i>
            {{ product.typeConfig.singular }}
          </div>
          <h1 class="product-title">{{ product.name }}</h1>
          <p v-if="product.category" class="product-category">{{ product.category.name }}</p>
        </div>

        <div class="title-right">
          <div class="status-controls">
            <div class="status-indicator" :class="product.status_class">
              <!-- <i class="fas fa-circle"></i> -->
              {{ product.status_label }}
            </div>
            <button @click="toggleStatus" class="btn btn-sm" :class="statusButtonClass">
              <i :class="statusButtonIcon"></i>
              {{ statusButtonText }}
            </button>
          </div>
          <div class="product-price-display">
            {{ product.formatted_price }}
          </div>
        </div>
      </div>

      <!-- Contenu principal -->
      <div class="product-content">
        <!-- Colonne gauche - Image et stats -->
        <div class="content-left">
          <div class="product-gallery">
            <div class="main-image">
              <img :src="product.image" :alt="product.name" />
            </div>
          </div>

          <!-- Statistiques -->
          <div class="product-metrics">
            <h3>Statistiques</h3>
            <div class="metrics-grid">
              <div v-for="(stat, key) in product.statistics" :key="key" class="metric-item">
                <div class="metric-icon">
                  <i :class="getStatIcon(key)"></i>
                </div>
                <div class="metric-content">
                  <span class="metric-number">{{ formatStatValue(stat, key) }}</span>
                  <span class="metric-label">{{ getStatLabel(key) }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Colonne droite - Informations -->
        <div class="content-right">
          <!-- Informations générales -->
          <div class="info-section">
            <h3>Informations générales</h3>
            <div class="info-grid">
              <div class="info-item">
                <label>Nom</label>
                <span>{{ product.name }}</span>
              </div>
              <div class="info-item">
                <label>Prix</label>
                <span>{{ product.formatted_price }}</span>
              </div>
              <div v-if="product.category" class="info-item">
                <label>Catégorie</label>
                <div v-foreach="product.category" :key="product.category.id" class="info-item">
                  <CategoryBadge v-if="product.category" :category="product.category" />
                </div>
              </div>
              <div class="info-item">
                <label>Statut</label>
                <span class="status-badge" :class="product.status_class">
                  {{ product.status_label }}
                </span>
              </div>
              <div v-if="product.description" class="info-item full-width">
                <label>Description</label>
                <div class="description-content">{{ product.description }}</div>
              </div>
            </div>
          </div>

          <!-- Détails spécifiques -->
          <div v-if="hasDetailFields" class="info-section">
            <h3>Détails {{ lowercaseTypeSingular }}</h3>
            <div class="info-grid">
              <div v-for="(field, key) in product.detail_fields" :key="key" class="info-item">
                <label>{{ field.label }}</label>
                <span>{{ field.value }}</span>
              </div>
            </div>
          </div>

          <!-- Relations -->
          <div v-if="hasRelations" class="info-section">
            <h3>{{ relationTitle }}</h3>
            <div class="relations-content">

              <!-- Plats du menu -->
              <div v-if="menuDishes && menuDishes.length > 0" class="dishes-list">
                <div v-for="dish in menuDishes" :key="dish.id" class="relation-item clickable-dish"
                  @click="goToProduct('dish', dish.product.id)" title="Cliquer pour voir ce plat">
                  <div class="relation-header">
                    <h4 class="card relation-name p-3">{{ dish.product.name }}</h4>
                    <span class="relation-price">{{ dish.formatted_price }}</span>
                  </div>
                  <p v-if="dish.description" class="relation-description">{{ dish.description }}</p>
                </div>
              </div>

              <!-- Ingrédients du plat -->
              <div v-if="dishIngredients && dishIngredients.length > 0" class="ingredients-list">
                <div v-for="ingredient in dishIngredients" :key="ingredient.id"
                  class="relation-item clickable-ingredient" @click="goToProduct('ingredient', ingredient.id)"
                  title="Cliquer pour voir cet ingrédient">
                  <div class="card relation-header py-1 px-3">
                    <h4 class="relation-name">{{ ingredient.product.name }}</h4>
                    <span class="relation-stock">Stock: {{ ingredient.stock }}</span>
                  </div>
                  <div v-if="ingredient.dietary_properties" class="dietary-badges">
                    <span v-if="ingredient.dietary_properties.is_vegetarian"
                      class="badge badge-vegetarian">Végétarien</span>
                    <span v-if="ingredient.dietary_properties.is_vegan" class="badge badge-vegan">Végan</span>
                    <span v-if="ingredient.dietary_properties.is_spicy" class="badge badge-spicy">Épicé</span>
                    <span v-if="ingredient.dietary_properties.is_gluten_free" class="badge badge-gluten-free">Sans
                      gluten</span>
                  </div>
                </div>
              </div>

              <!-- Message si pas de relations -->
              <div v-if="!hasRelations" class="no-relations">
                <p>Aucune relation configurée pour ce produit.</p>
              </div>
            </div>
          </div>

          <!-- Tags -->
          <div v-if="product.tags && product.tags.length > 0" class="info-section">
            <h3>Tags</h3>
            <div class="tags-container">
              <span v-for="tag in product.tags" :key="tag.id" class="tag" :style="{ backgroundColor: tag.color }">
                {{ tag.name }}
              </span>
            </div>
          </div>

          <!-- Options -->
          <div v-if="product.options && product.options.length > 0" class="info-section">
            <h3>Options disponibles</h3>
            <div class="options-list">
              <div v-for="option in product.options" :key="option.id" class="option-item">
                <span class="option-name">{{ option.name }}</span>
                <span class="option-price">{{ option.formatted_price }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Actions en bas -->
      <div class="quick-actions-bar">
        <div class="actions-left">
          <span class="last-updated">
            Dernière modification : {{ formattedUpdatedAt }}
          </span>
        </div>
        <div class="actions-right">
          <router-link :to="editRoute" class="btn btn-primary btn-sm">
            <i class="fas fa-edit"></i>
            Modifier
          </router-link>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import ProductsApi from '@/services/ProductsApi'
import CategoryBadge from './components/CategoryBadge.vue'
import { getStatIcon, getStatLabel, formatStatValue } from '@/shared/utils/ProductUtils'
import { formatDate } from '@/shared/utils/helpers'

export default {
  name: 'ProductDetail',
  props: {
    type: { type: String, required: true }
  },

  data() {
    return {
      loading: false,
      product: null,
      error: null
    }
  },
  components: {
    CategoryBadge
  },

  computed: {
    productId() {
      return this.$route.params.id
    },

    backRoute() {
      return { name: 'ProductsShow', params: { type: this.type } }
    },

    editRoute() {
      return { name: 'ProductEdit', params: { type: this.type, id: this.productId } }
    },
    menuDishes() {
      return this.product?.productableDetail?.dishes || []
    },

    dishIngredients() {
      return this.product?.productableDetail?.ingredients || []
    },

    hasDetailFields() {
      return this.product?.detail_fields && Object.keys(this.product.detail_fields).length > 0
    },

    hasRelations() {
      return (this.product?.relations?.dishes && this.product.relations.dishes.length > 0) ||
        (this.product?.relations?.ingredients && this.product.relations.ingredients.length > 0)
    },

    statusButtonClass() {
      return this.product?.status ? 'btn-warning' : 'btn-success'
    },

    statusButtonIcon() {
      return this.product?.status ? 'fas fa-pause' : 'fas fa-play'
    },

    statusButtonText() {
      return this.product?.status ? 'Désactiver' : 'Activer'
    },

    formattedUpdatedAt() {
      return formatDate(this.product?.updated_at)
    },

    lowercaseTypeSingular() {
      return this.product?.typeConfig?.singular?.toLowerCase() || ''
    },

    relationTitle() {
      if (this.product?.productableDetail?.dishes) return 'Plats du menu'
      if (this.product?.productableDetail?.ingredients) return 'Ingrédients'
      return 'Relations'
    }
  },

  created() {
    this.fetchProduct()
  },

  methods: {
    getStatIcon,
    getStatLabel,
    formatStatValue,
    async fetchProduct() {
      this.loading = true
      this.error = null

      try {
        const response = await ProductsApi.getProduct(this.productId)
        this.product = response
      } catch (error) {
        console.error('Erreur lors du chargement du produit:', error)
        this.error = error.response?.status === 404
          ? 'Produit introuvable'
          : 'Erreur lors du chargement'
      } finally {
        this.loading = false
      }
    },

    async toggleStatus() {
      try {
        const newStatus = !this.product.status

        await ProductsApi.updateProduct(this.productId, { status: newStatus })
        this.product.status = newStatus
        this.product.status_label = newStatus ? 'Actif' : 'Inactif'
        this.product.status_class = newStatus ? 'status-active' : 'status-inactive'
      } catch (error) {
        console.error('Erreur lors de la modification du statut:', error)
      }
    },

    async duplicateProduct() {
      if (!confirm(`Dupliquer "${this.product.name}" ?`)) return

      try {
        const duplicateData = {
          name: `${this.product.name} (copie)`,
          description: this.product.description,
          price: this.product.price,
          category_id: this.product.category?.id,
          productableType: this.product.typeConfig.class,
          productable: this.product.productableDetail
        }

        await ProductsApi.createProduct(duplicateData)
        this.$router.push(this.backRoute)
      } catch (error) {
        console.error('Erreur lors de la duplication:', error)
      }
    },

    async deleteProduct() {
      if (!confirm(`Supprimer "${this.product.name}" ?`)) return

      try {
        await ProductsApi.deleteProduct(this.productId)
        this.$router.push(this.backRoute)

      } catch (error) {
        console.error('Erreur lors de la suppression:', error)
      }
    },

    goToProduct(type, productId) {
      this.$router.push({
        name: 'ProductDetail',
        params: { type, id: productId }
      })
    }
  }
}
</script>