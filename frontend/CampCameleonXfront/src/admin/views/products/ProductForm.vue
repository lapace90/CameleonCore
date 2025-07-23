<template>
  <div class="product-form-container">
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

      <div class="header-actions">
        <button v-if="isEditing" @click="previewProduct" class="btn btn-secondary">
          <i class="fas fa-eye"></i>
          Aperçu
        </button>
        <button @click="saveDraft" class="btn btn-outline" :disabled="saving">
          <i class="fas fa-save"></i>
          Sauvegarder brouillon
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

    <!-- Loading -->
    <div v-if="loading" class="loading-state">
      <div class="spinner"></div>
      <p>Chargement...</p>
    </div>

    <div v-else>
      <!-- Titre de la page -->
      <div class="page-title-section">
        <div class="title-left">
          <div class="product-type-badge" :style="{ backgroundColor: typeConfig.color }">
            <i :class="typeConfig.icon"></i>
            {{ typeConfig.singular }}
          </div>
          <h1 class="page-title">
            {{ isEditing ? `Modifier "${product?.name}"` : `Nouveau ${typeConfig.singular}` }}
          </h1>
        </div>

        <div class="title-right" v-if="isEditing && product">
          <div class="status-info">
            <span class="status-badge" :class="getStatusClass(product)">
              {{ getStatusLabel(product) }}
            </span>
          </div>
        </div>
      </div>

      <!-- Formulaire principal -->
      <form @submit.prevent="submitForm" class="product-form">
        <div class="form-content">
          <!-- Colonne gauche - Image et aperçu -->
          <div class="form-left">
            <!-- Upload d'image -->
            <div class="image-upload-section">
              <h3>Image principale</h3>
              <div class="image-upload-area">
                <div v-if="imagePreview || form.image" class="current-image">
                  <img :src="getValidImageUrl(imagePreview || form.image)" :alt="form.name || 'Image du produit'"
                    @error="handleImageError" />
                  <div class="image-overlay">
                    <button type="button" @click="changeImage" class="overlay-btn">
                      <i class="fas fa-edit"></i>
                      Changer
                    </button>
                    <button type="button" @click="removeImage" class="overlay-btn">
                      <i class="fas fa-trash"></i>
                      Supprimer
                    </button>
                  </div>
                </div>

                <div v-else class="upload-placeholder" @click="selectImage">
                  <i class="fas fa-cloud-upload-alt"></i>
                  <p>Cliquez pour ajouter une image</p>
                  <span>JPG, PNG - Max 5MB</span>
                </div>

                <input ref="imageInput" type="file" accept="image/*" @change="handleImageUpload"
                  style="display: none" />
              </div>
            </div>

            <!-- Aperçu du produit -->
            <div class="product-preview">
              <h3>Aperçu</h3>
              <div class="preview-card">
                <div class="preview-image">
                  <img :src="getValidImageUrl(imagePreview || form.image) || getPlaceholderImage()"
                    :alt="form.name || 'Aperçu'" />
                </div>
                <div class="preview-content">
                  <h4>{{ form.name || 'Nom du produit' }}</h4>
                  <p class="preview-category">{{ selectedCategory?.name || 'Catégorie' }}</p>
                  <p class="preview-description">
                    {{ truncateText(form.description, 100) || 'Description du produit...' }}
                  </p>
                  <div class="preview-price">
                    {{ formatPrice(form.price) }}
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Colonne droite - Formulaires -->
          <div class="form-right">
            <!-- Informations générales -->
            <div class="form-section">
              <h3>Informations générales</h3>
              <div class="form-grid">
                <div class="form-group">
                  <label class="form-label required">Nom</label>
                  <input v-model="form.name" type="text" class="form-input" placeholder="Nom du produit" required
                    :class="{ 'error': errors.name }" />
                  <span v-if="errors.name" class="error-message">{{ errors.name }}</span>
                </div>

                <div class="form-group">
                  <label class="form-label required">Prix</label>
                  <div class="input-group">
                    <input v-model.number="form.price" type="number" step="0.01" min="0" class="form-input"
                      placeholder="0.00" required :class="{ 'error': errors.price }" />
                    <span class="input-addon">€</span>
                  </div>
                  <span v-if="errors.price" class="error-message">{{ errors.price }}</span>
                </div>

                <div class="form-group">
                  <label class="form-label">Catégorie</label>
                  <select v-model="form.category_id" class="form-select" :class="{ 'error': errors.category_id }">
                    <option value="">Sélectionner une catégorie</option>
                    <option v-for="category in categories" :key="category.id" :value="category.id">
                      {{ category.name }}
                    </option>
                  </select>
                  <span v-if="errors.category_id" class="error-message">{{ errors.category_id }}</span>
                </div>

                <div class="form-group">
                  <label class="form-label">Statut</label>
                  <div class="radio-group">
                    <label class="radio-item">
                      <input type="radio" v-model="form.status" :value="true" name="status" />
                      <span class="radio-label">Actif</span>
                    </label>
                    <label class="radio-item">
                      <input type="radio" v-model="form.status" :value="false" name="status" />
                      <span class="radio-label">Inactif</span>
                    </label>
                  </div>
                </div>

                <div class="form-group full-width">
                  <label class="form-label">Description</label>
                  <textarea v-model="form.description" class="form-textarea" rows="4"
                    placeholder="Description détaillée du produit..."
                    :class="{ 'error': errors.description }"></textarea>
                  <span v-if="errors.description" class="error-message">{{ errors.description }}</span>
                </div>
              </div>
            </div>

            <!-- Champs spécifiques au type -->
            <div class="form-section">
              <h3>Détails {{ typeConfig.singular.toLowerCase() }}</h3>
              <div class="form-grid">
                <div v-for="field in typeConfig.formFields" :key="field.name" class="form-group"
                  :class="{ 'full-width': field.fullWidth }">
                  <label class="form-label" :class="{ 'required': field.required }">
                    {{ field.label }}
                  </label>

                  <!-- Input text -->
                  <input v-if="field.type === 'text'" v-model="form.productable[field.name]" type="text"
                    class="form-input" :placeholder="field.placeholder" :required="field.required"
                    :class="{ 'error': errors[`productable.${field.name}`] }" />

                  <!-- Input number -->
                  <div v-else-if="field.type === 'number'" class="input-group">
                    <input v-model.number="form.productable[field.name]" type="number" class="form-input"
                      :placeholder="field.placeholder" :min="field.min" :max="field.max" :step="field.step"
                      :required="field.required" :class="{ 'error': errors[`productable.${field.name}`] }" />
                    <span v-if="field.unit" class="input-addon">{{ field.unit }}</span>
                  </div>

                  <!-- Select -->
                  <select v-else-if="field.type === 'select'" v-model="form.productable[field.name]" class="form-select"
                    :required="field.required" :class="{ 'error': errors[`productable.${field.name}`] }">
                    <option value="">{{ field.placeholder }}</option>
                    <option v-for="option in field.options" :key="option.value" :value="option.value">
                      {{ option.label }}
                    </option>
                  </select>

                  <!-- Textarea -->
                  <textarea v-else-if="field.type === 'textarea'" v-model="form.productable[field.name]"
                    class="form-textarea" :rows="field.rows || 3" :placeholder="field.placeholder"
                    :required="field.required" :class="{ 'error': errors[`productable.${field.name}`] }"></textarea>

                  <!-- Checkbox -->
                  <div v-else-if="field.type === 'checkbox'" class="checkbox-group">
                    <label class="checkbox-item">
                      <input type="checkbox" v-model="form.productable[field.name]" />
                      <span class="checkbox-label">{{ field.checkboxLabel || field.label }}</span>
                    </label>
                  </div>

                  <!-- Multi-select pour tags/options -->
                  <div v-else-if="field.type === 'tags'" class="tags-input">
                    <div class="selected-tags">
                      <span v-for="tag in form.productable[field.name] || []" :key="tag" class="tag-item">
                        {{ tag }}
                        <button type="button" @click="removeTag(field.name, tag)" class="tag-remove">
                          <i class="fas fa-times"></i>
                        </button>
                      </span>
                    </div>
                    <input type="text" class="form-input" :placeholder="field.placeholder"
                      @keydown.enter.prevent="addTag(field.name, $event)"
                      @keydown.comma.prevent="addTag(field.name, $event)" />
                  </div>

                  <span v-if="errors[`productable.${field.name}`]" class="error-message">
                    {{ errors[`productable.${field.name}`] }}
                  </span>
                  <span v-if="field.help" class="help-text">{{ field.help }}</span>
                </div>
              </div>
            </div>

            <!-- Tags globaux -->
            <div class="form-section">
              <h3>Tags</h3>
              <div class="tags-section">
                <div class="available-tags">
                  <label class="form-label">Tags disponibles</label>
                  <div class="tags-list">
                    <label v-for="tag in availableTags" :key="tag.id" class="tag-checkbox"
                      :class="{ 'selected': form.selectedTags.includes(tag.id) }">
                      <input type="checkbox" :value="tag.id" v-model="form.selectedTags" />
                      <span>{{ tag.name }}</span>
                    </label>
                  </div>
                </div>

                <div class="custom-tags">
                  <label class="form-label">Tags personnalisés</label>
                  <div class="tags-input">
                    <div class="selected-tags">
                      <span v-for="tag in form.customTags" :key="tag" class="tag-item custom">
                        {{ tag }}
                        <button type="button" @click="removeCustomTag(tag)" class="tag-remove">
                          <i class="fas fa-times"></i>
                        </button>
                      </span>
                    </div>
                    <input type="text" class="form-input" placeholder="Ajouter un tag personnalisé..."
                      @keydown.enter.prevent="addCustomTag" @keydown.comma.prevent="addCustomTag" />
                  </div>
                </div>
              </div>
            </div>

            <!-- Options associées (si applicable) -->
            <div v-if="typeConfig.hasOptions" class="form-section">
              <h3>Options disponibles</h3>
              <div class="options-section">
                <div class="available-options">
                  <div v-for="option in availableOptions" :key="option.id" class="option-item"
                    :class="{ 'selected': isOptionSelected(option.id) }">
                    <label class="option-checkbox">
                      <input type="checkbox" :value="option.id" @change="toggleOption(option.id)"
                        :checked="isOptionSelected(option.id)" />
                      <div class="option-info">
                        <span class="option-name">{{ option.name }}</span>
                        <span class="option-price">{{ formatPrice(option.price) }}</span>
                      </div>
                    </label>

                    <div v-if="isOptionSelected(option.id)" class="option-config">
                      <label class="config-item">
                        <input type="checkbox" v-model="getOptionConfig(option.id).required" />
                        <span>Obligatoire</span>
                      </label>
                      <div class="config-item">
                        <label>Quantité max:</label>
                        <input type="number" v-model.number="getOptionConfig(option.id).max_quantity" min="1"
                          class="form-input small" />
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Actions du formulaire -->
        <div class="form-actions">
          <div class="actions-left">
            <button type="button" @click="resetForm" class="btn btn-outline" :disabled="saving">
              <i class="fas fa-undo"></i>
              Réinitialiser
            </button>
          </div>

          <div class="actions-right">
            <button type="button" @click="saveDraft" class="btn btn-secondary" :disabled="saving">
              <i class="fas fa-save"></i>
              Sauvegarder brouillon
            </button>
            <button type="submit" class="btn btn-primary" :disabled="saving || !isFormValid">
              <i v-if="saving" class="fas fa-spinner fa-spin"></i>
              <i v-else class="fas fa-check"></i>
              {{ isEditing ? 'Mettre à jour' : 'Créer' }} {{ typeConfig.singular.toLowerCase() }}
            </button>
          </div>
        </div>
      </form>
    </div>
  </div>
</template>

<script>
import axios from 'axios'

export default {
  name: 'ProductForm',
  props: {
    productType: { // Reçu de la route
      type: String,
      required: true
    },
    action: { // 'create' ou 'edit'
      type: String,
      required: true
    },
    productIdProp: { // Seulement pour l'édition (optionnel)
      type: [String, Number],
      default: null
    }
  },
  data() {
    return {
      loading: false,
      saving: false,
      imagePreview: null,
      product: null,
      categories: [],
      availableTags: [],
      availableOptions: [],
      error: null,

      // Formulaire
      form: {
        name: '',
        description: '',
        price: 0,
        category_id: '',
        status: true,
        is_draft: false,
        image: null,
        selectedTags: [],
        customTags: [],
        selectedOptions: [],
        productable: {}
      },

      // Erreurs de validation
      errors: {},

      // Configuration des types de produits
      productConfigs: {
        activity: {
          label: 'Activités',
          singular: 'Activité',
          icon: 'fas fa-hiking',
          color: '#3b82f6',
          hasOptions: true,
          formFields: [
            { name: 'duration', label: 'Durée', type: 'number', unit: 'min', required: true, placeholder: '60' },
            { name: 'capacity', label: 'Capacité', type: 'number', unit: 'pers.', required: true, placeholder: '10' },
            {
              name: 'difficulty', label: 'Difficulté', type: 'select', required: true, placeholder: 'Choisir...',
              options: [
                { value: 'facile', label: 'Facile' },
                { value: 'moyen', label: 'Moyen' },
                { value: 'difficile', label: 'Difficile' }
              ]
            },
            { name: 'equipment', label: 'Équipement fourni', type: 'tags', placeholder: 'Ajouter équipement...', fullWidth: true },
            { name: 'location', label: 'Lieu', type: 'text', placeholder: 'Lieu de l\'activité' },
            { name: 'age_min', label: 'Âge minimum', type: 'number', unit: 'ans', min: 0, max: 99 },
            { name: 'age_max', label: 'Âge maximum', type: 'number', unit: 'ans', min: 0, max: 99 },
            {
              name: 'requirements', label: 'Prérequis', type: 'textarea', rows: 3, fullWidth: true,
              placeholder: 'Conditions physiques, expérience requise...'
            }
          ]
        },
        menu: {
          label: 'Menus',
          singular: 'Menu',
          icon: 'fas fa-utensils',
          color: '#10b981',
          hasOptions: true,
          formFields: [
            { name: 'ingredients', label: 'Ingrédients', type: 'tags', required: true, placeholder: 'Ajouter ingrédient...', fullWidth: true },
            { name: 'allergens', label: 'Allergènes', type: 'tags', placeholder: 'Ajouter allergène...', fullWidth: true },
            { name: 'preparation_time', label: 'Temps de préparation', type: 'number', unit: 'min', placeholder: '30' },
            {
              name: 'cooking_method', label: 'Méthode de cuisson', type: 'select', placeholder: 'Choisir...',
              options: [
                { value: 'grille', label: 'Grillé' },
                { value: 'four', label: 'Au four' },
                { value: 'poele', label: 'À la poêle' },
                { value: 'vapeur', label: 'À la vapeur' }
              ]
            },
            {
              name: 'nutritional_info', label: 'Informations nutritionnelles', type: 'textarea', rows: 3, fullWidth: true,
              placeholder: 'Calories, protéines, glucides...'
            },
            { name: 'vegetarian', label: 'Végétarien', type: 'checkbox' },
            { name: 'vegan', label: 'Végan', type: 'checkbox' },
            { name: 'gluten_free', label: 'Sans gluten', type: 'checkbox' }
          ]
        },
        room: {
          label: 'Hébergements',
          singular: 'Hébergement',
          icon: 'fas fa-bed',
          color: '#f59e0b',
          hasOptions: true,
          formFields: [
            { name: 'capacity', label: 'Capacité', type: 'number', unit: 'pers.', required: true, placeholder: '4' },
            { name: 'surface', label: 'Surface', type: 'number', unit: 'm²', placeholder: '25' },
            {
              name: 'bed_type', label: 'Type de lit', type: 'select', placeholder: 'Choisir...',
              options: [
                { value: 'simple', label: 'Lit simple' },
                { value: 'double', label: 'Lit double' },
                { value: 'superpose', label: 'Lits superposés' },
                { value: 'queen', label: 'Lit queen' }
              ]
            },
            {
              name: 'bathroom_type', label: 'Salle de bain', type: 'select', placeholder: 'Choisir...',
              options: [
                { value: 'privee', label: 'Privée' },
                { value: 'partagee', label: 'Partagée' },
                { value: 'commune', label: 'Commune' }
              ]
            },
            { name: 'amenities', label: 'Équipements', type: 'tags', placeholder: 'Ajouter équipement...', fullWidth: true },
            { name: 'view_type', label: 'Vue', type: 'text', placeholder: 'Vue mer, montagne...' },
            { name: 'wifi', label: 'WiFi', type: 'checkbox' },
            { name: 'air_conditioning', label: 'Climatisation', type: 'checkbox' },
            { name: 'balcony', label: 'Balcon/Terrasse', type: 'checkbox' }
          ]
        },
        option: {
          label: 'Options',
          singular: 'Option',
          icon: 'fas fa-puzzle-piece',
          color: '#8b5cf6',
          hasOptions: false,
          formFields: [
            {
              name: 'type', label: 'Type d\'option', type: 'select', required: true, placeholder: 'Choisir...',
              options: [
                { value: 'service', label: 'Service' },
                { value: 'equipment', label: 'Équipement' },
                { value: 'supplement', label: 'Supplément' },
                { value: 'insurance', label: 'Assurance' }
              ]
            },
            { name: 'max_quantity', label: 'Quantité maximum', type: 'number', min: 1, placeholder: '1' },
            { name: 'duration', label: 'Durée (si applicable)', type: 'number', unit: 'min', placeholder: '60' },
            {
              name: 'availability', label: 'Disponibilité', type: 'textarea', rows: 2, fullWidth: true,
              placeholder: 'Conditions de disponibilité...'
            }
          ]
        }
      }
    }
  },

  computed: {
    isEditing() {
      return this.action === 'edit'
    },

    typeConfig() {
      return this.productConfigs[this.productType] || this.productConfigs.activity
    },

    // Récupérer l'ID depuis les paramètres de la route
    productId() {
      return this.$route.params.id || this.productIdProp
    },

    selectedCategory() {
      return this.categories.find(c => c.id === this.form.category_id)
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
        await Promise.all([
          this.fetchCategories(),
          this.fetchTags(),
          this.typeConfig.hasOptions ? this.fetchOptions() : Promise.resolve()
        ])

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
      // Initialiser les champs spécifiques du type
      this.form.productable = {}
      this.typeConfig.formFields.forEach(field => {
        if (field.type === 'checkbox') {
          this.form.productable[field.name] = false
        } else if (field.type === 'tags') {
          this.form.productable[field.name] = []
        } else if (field.type === 'number') {
          this.form.productable[field.name] = null
        } else {
          this.form.productable[field.name] = ''
        }
      })
    },

    async fetchProduct() {
      try {
        console.log('Fetching product for edit:', this.productId)

        const response = await axios.get(`/api/products/${this.productId}`, {
          headers: {
            'Accept': 'application/ld+json',
            'Content-Type': 'application/json'
          }
        })

        this.product = response.data
        console.log('Product loaded:', this.product)

        // Charger les données productable si nécessaire
        if (this.product.productable && typeof this.product.productable === 'string') {
          try {
            const productableResponse = await axios.get(this.product.productable)
            this.product.productableData = productableResponse.data
          } catch (error) {
            console.warn('Could not fetch productable data:', error)
            this.product.productableData = {}
          }
        } else {
          this.product.productableData = this.product.productable || {}
        }

        this.populateForm()
      } catch (error) {
        console.error('Erreur lors du chargement du produit:', error)
        this.error = 'Erreur lors du chargement du produit'
      }
    },

    populateForm() {
      if (!this.product) return

      console.log('Populating form with product:', this.product)

      this.form = {
        name: this.product.name || '',
        description: this.product.description || '',
        price: this.product.price || 0,
        category_id: this.getCategoryId(this.product.category),
        status: this.product.status !== false,
        is_draft: this.product.isDraft || this.product.is_draft || false,
        image: this.product.image || null,
        selectedTags: this.extractTagIds(this.product.globalTags),
        customTags: [],
        selectedOptions: this.extractOptions(this.product.options),
        productable: { ...this.product.productableData }
      }

      console.log('Form populated:', this.form)
    },

    getCategoryId(category) {
      if (!category) return ''
      if (typeof category === 'object' && category.id) return category.id
      if (typeof category === 'string') {
        // Extract ID from IRI
        const match = category.match(/\/(\d+)$/)
        return match ? match[1] : ''
      }
      return category
    },

    extractTagIds(tags) {
      if (!Array.isArray(tags)) return []
      return tags.map(tag => {
        if (typeof tag === 'object' && tag.id) return tag.id
        if (typeof tag === 'string') {
          const match = tag.match(/\/(\d+)$/)
          return match ? match[1] : tag
        }
        return tag
      }).filter(Boolean)
    },

    extractOptions(options) {
      if (!Array.isArray(options)) return []
      return options.map(option => ({
        id: typeof option === 'object' ? option.id : option,
        required: option.pivot?.required || false,
        max_quantity: option.pivot?.max_quantity || 1
      }))
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

        console.log('Categories loaded:', this.categories)
      } catch (error) {
        console.error('Erreur lors du chargement des catégories:', error)
        this.categories = []
      }
    },

    async fetchTags() {
      try {
        // Pour l'instant, simulation avec des données factices
        this.availableTags = [
          { id: 1, name: 'Populaire' },
          { id: 2, name: 'Nouveau' },
          { id: 3, name: 'Recommandé' },
          { id: 4, name: 'Famille' },
          { id: 5, name: 'Aventure' }
        ]
      } catch (error) {
        console.error('Erreur lors du chargement des tags:', error)
        this.availableTags = []
      }
    },

    async fetchOptions() {
      try {
        const response = await axios.get('/api/products', {
          params: { type: 'option' },
          headers: {
            'Accept': 'application/ld+json',
            'Content-Type': 'application/json'
          }
        })

        if (response.data && response.data.member) {
          this.availableOptions = response.data.member.filter(p => p.productableType === 'App\\Models\\Option')
        } else {
          this.availableOptions = []
        }

        console.log('Options loaded:', this.availableOptions)
      } catch (error) {
        console.error('Erreur lors du chargement des options:', error)
        this.availableOptions = []
      }
    },

    // Gestion des images
    getValidImageUrl(imageUrl) {
      if (!imageUrl) return null

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
        return null
      }
    },

    getPlaceholderImage() {
      const svg = `
        <svg width="300" height="200" xmlns="http://www.w3.org/2000/svg">
          <rect width="300" height="200" fill="#f3f4f6"/>
          <text x="150" y="100" text-anchor="middle" dy=".3em" font-family="Arial, sans-serif" font-size="14" fill="#9ca3af">
            Aperçu du produit
          </text>
        </svg>
      `
      return 'data:image/svg+xml;base64,' + btoa(svg)
    },

    selectImage() {
      this.$refs.imageInput.click()
    },

    changeImage() {
      this.$refs.imageInput.click()
    },

    removeImage() {
      this.form.image = null
      this.imagePreview = null
      if (this.$refs.imageInput) {
        this.$refs.imageInput.value = ''
      }
    },

    handleImageUpload(event) {
      const file = event.target.files[0]
      if (!file) return

      // Validation du fichier
      if (file.size > 5 * 1024 * 1024) {
        this.error = 'Le fichier est trop volumineux (max 5MB)'
        return
      }

      // Créer l'aperçu
      const reader = new FileReader()
      reader.onload = (e) => {
        this.imagePreview = e.target.result
      }
      reader.readAsDataURL(file)

      // Stocker le fichier pour l'upload
      this.form.imageFile = file
    },

    handleImageError(event) {
      console.warn('Image failed to load:', event.target.src)
      event.target.src = this.getPlaceholderImage()
      event.target.onerror = null
    },

    // Gestion des tags
    addTag(fieldName, event) {
      const value = event.target.value.trim()
      if (!value) return

      if (!this.form.productable[fieldName]) {
        this.form.productable[fieldName] = []
      }

      if (!this.form.productable[fieldName].includes(value)) {
        this.form.productable[fieldName].push(value)
      }

      event.target.value = ''
    },

    removeTag(fieldName, tag) {
      const index = this.form.productable[fieldName].indexOf(tag)
      if (index > -1) {
        this.form.productable[fieldName].splice(index, 1)
      }
    },

    addCustomTag(event) {
      const value = event.target.value.trim()
      if (!value) return

      if (!this.form.customTags.includes(value)) {
        this.form.customTags.push(value)
      }

      event.target.value = ''
    },

    removeCustomTag(tag) {
      const index = this.form.customTags.indexOf(tag)
      if (index > -1) {
        this.form.customTags.splice(index, 1)
      }
    },

    // Gestion des options
    isOptionSelected(optionId) {
      return this.form.selectedOptions.some(o => o.id === optionId)
    },

    getOptionConfig(optionId) {
      let config = this.form.selectedOptions.find(o => o.id === optionId)
      if (!config) {
        config = { id: optionId, required: false, max_quantity: 1 }
        this.form.selectedOptions.push(config)
      }
      return config
    },

    toggleOption(optionId) {
      const index = this.form.selectedOptions.findIndex(o => o.id === optionId)
      if (index > -1) {
        this.form.selectedOptions.splice(index, 1)
      } else {
        this.form.selectedOptions.push({
          id: optionId,
          required: false,
          max_quantity: 1
        })
      }
    },

    // Actions du formulaire
    async submitForm() {
      if (!this.validateForm()) return

      this.saving = true
      this.error = null

      try {
        const payload = this.buildPayload()

        let response
        if (this.isEditing) {
          response = await axios.patch(`/api/products/${this.productId}`, payload, {
            headers: {
              'Accept': 'application/ld+json',
              'Content-Type': 'application/json'
            }
          })
        } else {
          response = await axios.post('/api/products', payload, {
            headers: {
              'Accept': 'application/ld+json',
              'Content-Type': 'application/json'
            }
          })
        }

        console.log('Product saved:', response.data)

        // Redirection
        this.$router.push({
          name: 'ProductDetail',
          params: { type: this.productType, id: response.data.id }
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
      // Construction du payload selon le format attendu par l'API
      const payload = {
        name: this.form.name,
        description: this.form.description,
        price: this.form.price,
        status: this.form.status,
        isDraft: this.form.is_draft,
        productableType: this.getProductableType()
      }

      // Ajouter la catégorie si sélectionnée
      if (this.form.category_id) {
        payload.category = `/api/categories/${this.form.category_id}`
      }

      // Ajouter l'image si présente
      if (this.form.image) {
        payload.image = this.form.image
      }

      // Ajouter les données spécifiques du productable
      if (Object.keys(this.form.productable).length > 0) {
        payload.productable = { ...this.form.productable }
      }

      return payload
    },

    getProductableType() {
      const typeMap = {
        'activity': 'App\\Models\\Activity',
        'room': 'App\\Models\\Room',
        'menu': 'App\\Models\\Menu',
        'option': 'App\\Models\\Option'
      }
      return typeMap[this.productType] || 'App\\Models\\Activity'
    },

    async saveDraft() {
      const originalDraftStatus = this.form.is_draft
      this.form.is_draft = true

      try {
        await this.submitForm()
      } catch (error) {
        this.form.is_draft = originalDraftStatus
        throw error
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

      // Validation des champs spécifiques
      this.typeConfig.formFields.forEach(field => {
        if (field.required && !this.form.productable[field.name]) {
          this.errors[`productable.${field.name}`] = `${field.label} est obligatoire`
        }
      })

      return Object.keys(this.errors).length === 0
    },

    resetForm() {
      if (confirm('Êtes-vous sûr de vouloir réinitialiser le formulaire ?')) {
        if (this.isEditing) {
          this.populateForm()
        } else {
          this.initializeForm()
          this.form = {
            name: '',
            description: '',
            price: 0,
            category_id: '',
            status: true,
            is_draft: false,
            image: null,
            selectedTags: [],
            customTags: [],
            selectedOptions: [],
            productable: this.form.productable
          }
        }
        this.errors = {}
        this.imagePreview = null
      }
    },

    previewProduct() {
      this.$router.push({
        name: 'ProductDetail',
        params: { type: this.productType, id: this.productId }
      })
    },

    // Utilitaires
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
    },

    truncateText(text, length) {
      if (!text) return ''
      return text.length > length ? text.substring(0, length) + '...' : text
    }
  }
}
</script>

