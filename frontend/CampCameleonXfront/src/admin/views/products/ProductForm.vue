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
          <ProductImageUpload v-model="form.image" :product-name="form.name || 'Nouveau produit'" />

          <!-- Colonne droite - Formulaires -->
          <div class="form-right">
            <!-- Informations de base -->
            <ProductBasicFields :type="productType" v-model="form" :errors="errors" />

            <!-- Champs spécifiques -->
            <ProductTypeFields v-if="typeConfig.fields.length > 0 && productType !== 'dish'" :type="productType"
              :config="typeConfig" v-model="form.productable" />

            <!-- Relations -->
            <ProductRelations v-if="isEditing && typeConfig.hasRelation && product" :type="productType"
              :config="typeConfig" :product="product" :edit-mode="true" @relations-changed="onRelationsChanged" />

            <!-- Message pour création -->
            <div v-if="!isEditing && typeConfig.hasRelation" class="form-section">
              <div class="form-note">
                <i class="fas fa-info-circle"></i>
                Les {{ getRelationTitle().toLowerCase() }} pourront être ajoutés après la création.
              </div>
            </div>

          </div>
        </div>

        <!-- Actions -->
        <ProductFormActions :is-editing="isEditing" :saving="saving" :is-form-valid="isFormValid" @reset="resetForm"
          @submit="submitForm" />

      </form>
    </div>
  </div>
</template>

<script>
import ProductsApi from '@/services/ProductsApi'
import ProductRelations from './ProductRelations.vue'
import ProductTypeFields from './components/ProductTypeFields.vue'
import ProductImageUpload from './components/ProductImageUpload.vue'
import ProductBasicFields from './components/ProductBasicFields.vue'
import ProductFormActions from './components/ProductFormActions.vue'
import { PRODUCT_CONFIGS, getProductableType } from '@/shared/configs/productConfigs'
import { formatPrice, getFieldLabel } from '@/shared/utils/ProductUtils.js'

export default {
  name: 'ProductForm',

  components: {
    ProductRelations,
    ProductTypeFields,
    ProductImageUpload,
    ProductBasicFields,
    ProductFormActions
  },

  props: {
    productType: { type: String, required: true },
    action: { type: String, required: true }
  },

  data() {
    return {
      loading: false,
      saving: false,
      product: null,
      error: null,
      localRelations: {
        dishes: [],
        ingredients: []
      },

      form: {
        name: '',
        description: '',
        price: 0,
        status: true,
        image: null,
        productable: {}
      },

      errors: {}
    }
  },

  computed: {
    isEditing() {
      return this.action === 'edit'
    },

    typeConfig() {
      return PRODUCT_CONFIGS[this.productType] || PRODUCT_CONFIGS.activity
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

  methods: {
    async initializeComponent() {
      this.loading = true
      this.error = null

      try {
        this.initializeForm()

        if (this.isEditing) {
          await this.fetchProduct()
        }
      } catch (error) {
        console.error('Erreur lors de l\'initialisation:', error)
        this.error = 'Erreur lors du chargement du formulaire'
      } finally {
        this.loading = false
      }
    },

    initializeForm() {
      this.form.productable = {}
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
        productable: { ...this.product.productableDetail }
      }
      this.form.categoryIds = this.extractCategoryIds(this.product)
    },

    onRelationsChanged(relations) {
      this.localRelations = relations
    },

    extractIdFromIri(iri) {
      if (!iri) return null
      const m = String(iri).match(/\/(\d+)$|(\d+)(?=\D*$)/)
      return m ? Number(m[1] || m[2]) : null
    },

    extractCategoryIds(product) {
      if (!product) return []

      if (Array.isArray(product.categories)) {
        return product.categories
          .map(c => typeof c === 'string' ? this.extractIdFromIri(c) : Number(c?.id))
          .filter(id => Number.isFinite(id))
      }

      const single = typeof product.category === 'string' ? this.extractIdFromIri(product.category)
        : (product.category && Number(product.category.id)) || null
      return single ? [single] : []
    },

    async submitForm() {
      if (!this.validateForm()) return

      this.saving = true
      this.error = null

      try {
        let response

        // Si c'est un File, utiliser FormData
        if (this.form.image instanceof File) {
          console.log('📤 Envoi FormData avec fichier')

          const jsonPayload = this.buildPayload()
          const formData = new FormData()
          formData.append('payload', JSON.stringify(jsonPayload))
          formData.append('image', this.form.image, this.form.image.name)

          if (this.isEditing) {
            response = await ProductsApi.updateProduct(this.productId, formData)
          } else {
            response = await ProductsApi.createProduct(formData)
          }
        }
        // Sinon JSON classique
        else {
          console.log('📤 Envoi JSON classique')
          const jsonPayload = this.buildPayload()

          if (this.isEditing) {
            response = await ProductsApi.updateProduct(this.productId, jsonPayload)
          } else {
            response = await ProductsApi.createProduct(jsonPayload)
          }
        }

        this.$router.push({
          name: 'ProductDetail',
          params: { type: this.productType, id: response.id }
        })

      } catch (error) {
        console.error('Erreur lors de la sauvegarde:', error)
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

      // Inclure l'image seulement si c'est une URL string
      if (this.form.image && typeof this.form.image === 'string') {
        payload.image = this.form.image
      }

      if (Object.keys(this.form.productable).length > 0) {
        payload.productable = { ...this.form.productable }
      }

      if (this.typeConfig.hasRelation) {
        payload.relations = this.localRelations
      }

      const ids = Array.isArray(this.form.categoryIds) ? this.form.categoryIds : []
      const backendIsMulti = Array.isArray(this.product?.categories)

      if (backendIsMulti || ids.length > 1) {
        payload.categories = ids.map(id => `/api/categories/${id}`)
      } else {
        payload.category = ids[0] ? `/api/categories/${ids[0]}` : null
      }

      return payload
    },

    getProductableType() {
      return getProductableType(this.productType)
    },

    validateForm() {
      this.errors = {}

      if (!this.form.name) {
        this.errors.name = 'Le nom est obligatoire'
      }

      if (this.form.price < 0) {
        this.errors.price = 'Le prix doit être positif'
      }

      if (this.productType === 'activity') {
        const requiredFields = ['guide', 'duration', 'meeting_point', 'max_people', 'difficulty_level']
        requiredFields.forEach(field => {
          if (!this.form.productable[field]) {
            this.errors[`productable.${field}`] = `${getFieldLabel(field)} est obligatoire`
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
        this.errors = {}
      }
    },

    getRelationTitle() {
      if (this.typeConfig.hasRelation === 'ingredients') return 'Ingrédients'
      if (this.typeConfig.hasRelation === 'dishes') {
        return this.productType === 'ingredient' ? 'Plats utilisant cet ingrédient' : 'Plats du menu'
      }
      return ''
    },

    formatPrice
  }
}
</script>