<template>
  <div class="product-form-container">
    <!-- Message d'erreur -->
    <div v-if="error" class="error-message">
      <div class="alert alert-danger">
        <i class="fas fa-exclamation-triangle"></i>
        {{ error }}
        <button @click="error = null" class="btn-close">&times;</button>
      </div>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="loading-state">
      <div class="spinner"></div>
      <p>Chargement...</p>
    </div>

    <!-- Contenu principal -->
    <div v-else>
      <!-- Header -->
      <div class="form-header">
        <div class="header-navigation">
          <router-link :to="backRoute" class="back-link">
            <i class="fas fa-arrow-left"></i>
            {{ isEditing ? 'Retour aux détails' : `Retour à ${typeConfig.label}` }}
          </router-link>
          <div class="breadcrumb">
            <span>{{ typeConfig.label }}</span>
            <i class="fas fa-chevron-right"></i>
            <span>{{ isEditing ? product?.name : `Nouveau ${typeConfig.singular}` }}</span>
          </div>
        </div>
      </div>

      <!-- Titre -->
      <div class="page-title-section">
        <div class="product-type-badge" :style="{ backgroundColor: typeConfig.color }">
          <i :class="typeConfig.icon"></i>
          {{ typeConfig.singular }}
        </div>
        <h1 class="page-title">
          {{ isEditing ? `Modifier "${product?.name}"` : `Nouveau ${typeConfig.singular}` }}
        </h1>
      </div>

      <!-- Formulaire principal -->
      <form @submit.prevent="submitForm" class="product-form">
        <div class="form-content">
          <!-- Colonne gauche - Image -->
          <ProductImageUpload v-model="form.image" />

          <!-- Colonne droite - Formulaires -->
          <div class="form-right">
            <!-- Informations de base -->
            <div class="form-section">
              <ProductBasicFields :type="productType" v-model="form" :errors="errors" />
            </div>

            <!-- Champs spécifiques - SELON VOTRE CONFIG EXACTE -->
            <ProductTypeFields v-if="typeConfig.fields.length > 0" :type="productType" :config="typeConfig"
              v-model="form.productable" />

            <!-- Relations - SELON VOTRE CONFIG hasRelation -->
            <ProductRelations v-if="isEditing && typeConfig.hasRelation" :type="productType" :config="typeConfig"
              :product-id="productId" v-model="relationData" />

            <!-- Message pour les types sans relations en création -->
            <div v-if="!isEditing && typeConfig.hasRelation" class="form-section">
              <div class="form-note">
                <i class="fas fa-info-circle"></i>
                Les {{ getRelationTitle().toLowerCase() }} pourront être ajoutés après la création.
              </div>
            </div>

          </div>
        </div>

        <!-- Actions -->
        <ProductFormActions :is-editing="isEditing" :saving="saving" :is-form-valid="isFormValid"
          @reset="resetForm" @submit="submitForm" />
      </form>
    </div>
  </div>
</template>

<script>
import ProductsApi from '@/services/ProductsApi'
import ProductRelations from './ProductRelations.vue'
import ProductTypeFields from './ProductTypeFields.vue'
import ProductImageUpload from './components/ProductImageUpload.vue'
import ProductBasicFields from './components/ProductBasicFields.vue'
import ProductFormActions from './components/ProductFormActions.vue'

import {
  formatPrice,
  getValidImageUrl,
  getPlaceholderImage,
  handleImageUpload,
  handleImageError,
  getFieldLabel,
  selectImage,
  changeImage,
  removeImage
} from '@/utils/ProductUtils'

export default {
  name: 'ProductForm',
  props: {
    productType: {
      type: String,
      required: true
    },
    action: {
      type: String,
      required: true
    }
  },
  data() {
    return {
      loading: false,
      saving: false,
      // imagePreview: null,
      product: null,
      relationData: {},
      // Données pour les relations
      availableIngredients: [],
      availableDishes: [],
      error: null,


      // Formulaire
      form: {
        name: '',
        description: '',
        price: 0,
        status: true,
        image: null,
        // Relations
        selectedIngredients: [],
        selectedDishes: [],
        productable: {}
      },

      errors: {},

      // VOTRE CONFIGURATION EXACTE
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
      }
    }
  },

  computed: {
    isEditing() {
      return this.action === 'edit'
    },

    typeConfig() {
      // ✅ CORRECTION : Ajouter une vérification et une valeur par défaut
      if (!this.productType) {
        console.warn('productType non défini, utilisation de la config par défaut')
        return this.productConfigs.activity // Valeur par défaut
      }

      const config = this.productConfigs[this.productType]
      if (!config) {
        console.warn(`Configuration non trouvée pour le type: ${this.productType}`)
        return this.productConfigs.activity // Valeur par défaut
      }

      console.log(`✅ TypeConfig trouvé pour ${this.productType}:`, config)
      return config
    },

    productId() {
      return this.$route.params.id
    },

    backRoute() {
      if (this.isEditing && this.productId) {
        return { name: 'ProductDetail', params: { type: this.productType, id: this.productId } }
      }
      return { name: 'ProductsShow', params: { type: this.productType } }
    },

    isFormValid() {
      return this.form.name && this.form.price >= 0 && Object.keys(this.errors).length === 0
    }
  },

  async created() {
    await this.initializeComponent()
  },

  components: {
    ProductRelations,
    ProductTypeFields,
    ProductImageUpload,
    ProductBasicFields,
    ProductFormActions
  },

  methods: {
    async initializeComponent() {
      this.loading = true
      this.error = null

      try {
        this.initializeForm()

        if (this.isEditing) {
          await this.fetchProduct()
          await this.fetchRelationalData()
        }
      } catch (error) {
        console.error('Erreur lors de l\'initialisation:', error)
        this.error = 'Erreur lors du chargement du formulaire'
      } finally {
        this.loading = false
      }
    },

    initializeForm() {
      // Initialiser selon VOTRE config
      this.form.productable = {}

      // Initialiser seulement les champs définis dans votre config
      this.typeConfig.fields.forEach(field => {
        if (['is_vegetarian', 'is_vegan', 'is_spicy', 'is_gluten_free', 'is_lactose_free', 'is_nut_free', 'availability'].includes(field)) {
          this.form.productable[field] = false
        } else {
          this.form.productable[field] = null
        }
      })
    },

    async fetchProduct() {
      try {
        console.log('Fetching product for edit:', this.productId)
        this.product = await ProductsApi.getProduct(this.productId)
        this.populateForm()
      } catch (error) {
        console.error('Error fetching product:', error)
        throw error
      }
    },

    populateForm() {
      if (!this.product) return

      this.form = {
        name: this.product.name || '',
        description: this.product.description || '',
        price: this.product.price || 0,
        status: this.product.status !== false,
        image: this.product.image || null,
        selectedIngredients: [],
        selectedDishes: [],
        productable: { ...this.product.productableDetail }
      }
    },

    // Récupération des données relationnelles selon VOTRE structure
    async fetchRelationalData() {
      try {
        // Pour un plat, récupérer les ingrédients disponibles
        if (this.typeConfig.hasRelation === 'ingredients') {
          const { data } = await ProductsApi.getRelationProducts('ingredients')
          this.availableIngredients = data.map(i => ({ ...i, id: Number(i.id) }))
        }

        // Pour un menu ou ingrédient, récupérer les plats disponibles
        if (this.typeConfig.hasRelation === 'dishes') {
          const { data } = await ProductsApi.getRelationProducts('dishes')
          this.availableDishes = data.map(d => ({ ...d, id: Number(d.id) }))
        }
      } catch (error) {
        console.error('Erreur lors du chargement des données relationnelles:', error)
      }
    },

    // Charger les relations existantes selon VOTRE structure
    async loadRelations() {
      if (!this.product.productableDetail || !this.product.productableDetail.id) return
      if (!this.typeConfig.hasRelation) return

      try {
        const data = await ProductsApi.getProductRelations(
          this.product.productableDetail.id,
          this.getProductableType()
        )

        if (data.ingredients && Array.isArray(data.ingredients)) {
          this.form.selectedIngredients = data.ingredients.map(ingredientIRI => {
            const match = ingredientIRI.match(/\/(\d+)$/)
            return match ? parseInt(match[1]) : null
          }).filter(Boolean)
        }

        if (data.dishes && Array.isArray(data.dishes)) {
          this.form.selectedDishes = data.dishes.map(dishIRI => {
            const match = dishIRI.match(/\/(\d+)$/)
            return match ? parseInt(match[1]) : null
          }).filter(Boolean)
        }
      } catch (error) {
        console.error('Erreur lors du chargement des relations:', error)
      }
    },

    // Actions du formulaire
    async submitForm() {
      if (!this.validateForm()) return

      this.saving = true
      this.error = null

      try {
        const payload = this.buildPayload()
        console.log('Payload:', payload)

        let response
        if (this.isEditing) {
          response = await ProductsApi.updateProduct(this.productId, payload)
        } else {
          response = await ProductsApi.createProduct(payload)
        }

        if (this.form.imageFile) {
          try {
            await ProductsApi.uploadImage(this.form.imageFile, response.id)
          } catch (error) {
            console.warn('Could not upload image:', error)
          }
        }
        // Sauvegarder les relations si besoin
        if (this.isEditing && this.typeConfig.hasRelation) {
          await this.saveRelations(response)
        }

        // Redirection
        this.$router.push({
          name: 'ProductDetail',
          params: { type: this.productType, id: response.id }
        })

      } catch (error) {
        console.error('Erreur lors de la sauvegarde:', error)
        if (error.response?.data?.errors) {
          this.errors = error.response.data.errors
        }
        this.error = 'Erreur lors de la sauvegarde du produit'
      } finally {
        this.saving = false
      }
    },

    buildPayload() {
      const payload = {
        name: this.form.name,
        description: this.form.description,
        price: this.form.price,
        status: this.form.status,
        productableType: this.getProductableType()
      }

      if (this.form.image) {
        payload.image = this.form.image
      }

      if (Object.keys(this.form.productable).length > 0) {
        payload.productable = { ...this.form.productable }
      }

      return payload
    },

    getProductableType() {
      const typeMap = {
        'activity': 'App\\Models\\Activity',
        'ingredient': 'App\\Models\\Ingredient',
        'dish': 'App\\Models\\Dish',
        'menu': 'App\\Models\\Menu',
        'room': 'App\\Models\\Room'
      }
      return typeMap[this.productType] || 'App\\Models\\Activity'
    },

    // Sauvegarde des relations selon VOTRE structure
    async saveRelations(product) {
      if (!product.productable || !this.typeConfig.hasRelation) return

      try {
        const productableId = typeof product.productable === 'string'
          ? product.productable.match(/\/(\d+)$/)?.[1]
          : product.productable.id

        if (!productableId) return

        // Sauvegarder les ingrédients d'un plat
        if (this.typeConfig.hasRelation === 'ingredients' && this.form.selectedIngredients.length > 0) {
          const ingredientIRIs = this.form.selectedIngredients.map(id => `/api/ingredients/${id}`)

          try {
            await ProductsApi.updateProductRelations(
              this.productId,
              productableId,
              'App\\Models\\Dish',
              { ingredients: ingredientIRIs }
            )
          } catch (error) {
            console.warn('Could not save dish ingredients:', error)
          }
        }

        // Sauvegarder les plats d'un menu
        if (this.typeConfig.hasRelation === 'dishes' && this.productType === 'menu' && this.form.selectedDishes.length > 0) {
          const dishIRIs = this.form.selectedDishes.map(id => `/api/dishes/${id}`)

          try {
            await ProductsApi.updateProductRelations(
              this.productId,
              productableId,
              this.getProductableType(),
              { dishes: dishIRIs }
            )
          } catch (error) {
            console.warn('Could not save menu dishes:', error)
          }
        }

        // Note: Les relations ingrédient → plats sont gérées côté plat
      } catch (error) {
        console.error('Erreur lors de la sauvegarde des relations:', error)
      }
    },

    validateForm() {
      this.errors = {}

      if (!this.form.name) {
        this.errors.name = 'Le nom est obligatoire'
      }

      if (this.form.price < 0) {
        this.errors.price = 'Le prix doit être positif'
      }

      // Validation spécifique selon votre config
      if (this.productType === 'activity') {
        const requiredFields = ['guide', 'duration', 'meeting_point', 'max_people', 'difficulty_level']
        requiredFields.forEach(field => {
          if (!this.form.productable[field]) {
            this.errors[`productable.${field}`] = `${this.getFieldLabel(field)} est obligatoire`
          }
        })
      }

      if (this.productType === 'room') {
        if (!this.form.productable.capacity) {
          this.errors['productable.capacity'] = 'La capacité est obligatoire'
        }
      }

      return Object.keys(this.errors).length === 0
    },

    resetForm() {
      if (confirm('Réinitialiser le formulaire ?')) {
        this.initializeForm()
        this.form.name = ''
        this.form.description = ''
        this.form.price = 0
        this.form.status = true
        this.form.image = null
        this.form.selectedIngredients = []
        this.form.selectedDishes = []
        this.errors = {}
        this.imagePreview = null
      }
    },

    // Utilitaires
    getRelationTitle() {
      if (this.typeConfig.hasRelation === 'ingredients') return 'Ingrédients'
      if (this.typeConfig.hasRelation === 'dishes') {
        return this.productType === 'ingredient' ? 'Plats utilisant cet ingrédient' : 'Plats du menu'
      }
      return ''
    },

    getFieldLabel,

    // Gestion des images
    getValidImageUrl,

    getPlaceholderImage,

    selectImage,

    changeImage,

    removeImage,

    handleImageUpload,

    handleImageError,

    formatPrice
  }
}
</script>