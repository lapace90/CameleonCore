<template>
  <div class="product-detail-container">
    <!-- Loading -->
    <div v-if="loading" class="loading-state">
      <div class="spinner"></div>
      <p>Chargement du produit...</p>
    </div>

    <!-- Contenu principal -->
    <div v-else-if="product" class="product-detail">
      <!-- Header avec navigation -->
      <div class="detail-header">
        <div class="header-navigation">
          <router-link :to="{ name: 'ProductsShow', params: { type: type } }" class="back-link">
            <i class="fas fa-arrow-left"></i>
            Retour à {{ typeConfig.label }}
          </router-link>

          <div class="breadcrumb">
            <span>{{ typeConfig.label }}</span>
            <i class="fas fa-chevron-right"></i>
            <span>{{ product.name }}</span>
          </div>
        </div>

        <div class="header-actions">
          <button @click="duplicateProduct" class="btn btn-secondary">
            <i class="fas fa-copy"></i>
            Dupliquer
          </button>
          <router-link :to="{ name: 'ProductEdit', params: { type: type, id: product.id } }" class="btn btn-primary">
            <i class="fas fa-edit"></i>
            Modifier
          </router-link>
          <button @click="deleteProduct" class="btn btn-danger">
            <i class="fas fa-trash"></i>
            Supprimer
          </button>
        </div>
      </div>

      <!-- Titre et statut -->
      <div class="product-title-section">
        <div class="title-left">
          <div class="product-type-badge" :style="{ backgroundColor: typeConfig.color }">
            <i :class="typeConfig.icon"></i>
            {{ typeConfig.singular }}
          </div>
          <h1 class="product-title">{{ product.name }}</h1>
          <p class="product-category">{{ getProductCategoryName() }}</p>
        </div>

        <div class="title-right">
          <div class="status-controls">
            <div class="status-indicator" :class="getStatusClass(product)">
              <i :class="getStatusIcon(product)"></i>
              {{ getStatusLabel(product) }}
            </div>
            <button @click="toggleStatus" class="btn btn-sm" :class="product.status ? 'btn-warning' : 'btn-success'">
              <i :class="product.status ? 'fas fa-pause' : 'fas fa-play'"></i>
              {{ product.status ? 'Désactiver' : 'Activer' }}
            </button>
          </div>
          <div class="product-price-display">
            {{ formatPrice(product.price) }}
          </div>
        </div>
      </div>

      <!-- Contenu principal -->
      <div class="product-content">
        <!-- Colonne gauche - Image et stats -->
        <div class="content-left">
          <div class="product-gallery">
            <div class="main-image">
              <img :src="getValidImageUrl(product.image)" :alt="product.name" @error="handleImageError" />
              <div class="image-actions">
                <button @click="openImageEditor" class="image-action-btn">
                  <i class="fas fa-edit"></i>
                  Modifier l'image
                </button>
              </div>
            </div>

            <!-- Galerie supplémentaire -->
            <div v-if="product.images && product.images.length > 0" class="image-thumbnails">
              <div v-for="(image, index) in product.images" :key="index" class="thumbnail" @click="selectImage(image)">
                <img :src="image.url" :alt="`Image ${index + 1}`" />
              </div>
            </div>
          </div>

          <!-- Métriques et stats -->
          <div class="product-metrics">
            <h3>Statistiques</h3>
            <div class="metrics-grid">
              <div class="metric-item">
                <div class="metric-icon">
                  <i class="fas fa-eye"></i>
                </div>
                <div class="metric-content">
                  <span class="metric-number">{{ product.views || 0 }}</span>
                  <span class="metric-label">Vues</span>
                </div>
              </div>
              <div class="metric-item">
                <div class="metric-icon">
                  <i class="fas fa-shopping-cart"></i>
                </div>
                <div class="metric-content">
                  <span class="metric-number">{{ product.reservations_count || 0 }}</span>
                  <span class="metric-label">Réservations</span>
                </div>
              </div>
              <div class="metric-item">
                <div class="metric-icon">
                  <i class="fas fa-star"></i>
                </div>
                <div class="metric-content">
                  <span class="metric-number">{{ product.average_rating || '-' }}</span>
                  <span class="metric-label">Note</span>
                </div>
              </div>
              <div class="metric-item">
                <div class="metric-icon">
                  <i class="fas fa-euro-sign"></i>
                </div>
                <div class="metric-content">
                  <span class="metric-number">{{ formatPrice(product.total_revenue || 0) }}</span>
                  <span class="metric-label">CA total</span>
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
                <span>{{ formatPrice(product.price) }}</span>
              </div>
              <div class="info-item">
                <label>Catégorie</label>
                <span>{{ getProductCategoryName() }}</span>
              </div>
              <div class="info-item">
                <label>Statut</label>
                <span class="status-badge" :class="getStatusClass(product)">
                  {{ getStatusLabel(product) }}
                </span>
              </div>
              <div class="info-item full-width">
                <label>Description</label>
                <div class="description-content">{{ product.description || 'Aucune description' }}</div>
              </div>
            </div>
          </div>

          <!-- Informations spécifiques au type -->
          <div v-if="productableData" class="info-section">
            <h3>Détails {{ typeConfig.singular.toLowerCase() }}</h3>
            <div class="info-grid">
              <div v-for="field in typeConfig.detailFields" :key="field" class="info-item"
                :class="{ 'full-width': isFullWidthField(field) }"
                v-show="productableData[field] !== null && productableData[field] !== '' && productableData[field] !== undefined">
                <label>{{ getFieldLabel(field) }}</label>
                <span>{{ formatFieldValue(productableData[field], field) }}</span>
              </div>
            </div>
          </div>

          <!-- Tags -->
          <div v-if="product.globalTags && product.globalTags.length > 0" class="info-section">
            <h3>Tags</h3>
            <div class="tags-container">
              <span v-for="tag in product.globalTags" :key="tag.id || tag" class="tag">
                {{ typeof tag === 'object' ? tag.name : tag }}
              </span>
            </div>
          </div>

          <!-- Options associées -->
          <div v-if="product.options && product.options.length > 0" class="info-section">
            <h3>Options disponibles</h3>
            <div class="options-list">
              <div v-for="option in product.options" :key="option.id || option" class="option-item">
                <div class="option-info">
                  <span class="option-name">{{ typeof option === 'object' ? option.name : option }}</span>
                  <span class="option-price" v-if="option.price">{{ formatPrice(option.price) }}</span>
                </div>
                <div class="option-meta" v-if="option.pivot">
                  <span v-if="option.pivot.required" class="option-required">Obligatoire</span>
                  <span v-if="option.pivot.max_quantity" class="option-quantity">
                    Max: {{ option.pivot.max_quantity }}
                  </span>
                </div>
              </div>
            </div>
          </div>

          <!-- Réservations récentes -->
          <div v-if="recentReservations.length > 0" class="info-section">
            <h3>Réservations récentes</h3>
            <div class="reservations-list">
              <div v-for="reservation in recentReservations" :key="reservation.id" class="reservation-item">
                <div class="reservation-info">
                  <span class="reservation-customer">{{ reservation.customer_name }} </span>
                  <span class="reservation-date"> - {{ formatDate(reservation.created_at) }}</span>
                </div>
                <div class="reservation-amount">
                  {{ formatPrice(reservation.total_amount) }}
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Actions en bas -->
      <div class="quick-actions-bar">
        <div class="actions-left">
          <span class="last-updated">
            Dernière modification : {{ formatDate(product.updatedAt || product.updated_at) }}
          </span>
        </div>
        <div class="actions-right">
          <button @click="exportProduct" class="btn btn-outline">
            <i class="fas fa-download"></i>
            Exporter
          </button>
          <button @click="shareProduct" class="btn btn-outline">
            <i class="fas fa-share"></i>
            Partager
          </button>
          <router-link :to="{ name: 'ProductEdit', params: { type: type, id: product.id } }" class="btn btn-primary">
            <i class="fas fa-edit"></i>
            Modifier ce {{ typeConfig.singular.toLowerCase() }}
          </router-link>
        </div>
      </div>
    </div>

    <!-- État d'erreur -->
    <div v-else class="error-state">
      <div class="error-icon">
        <i class="fas fa-exclamation-triangle"></i>
      </div>
      <h3>Produit introuvable</h3>
      <p>Le produit demandé n'existe pas ou a été supprimé.</p>
      <router-link :to="{ name: 'ProductsShow', params: { type: type } }" class="btn btn-primary">
        <i class="fas fa-arrow-left"></i>
        Retour à la liste
      </router-link>
    </div>
  </div>
</template>

<script>
import axios from 'axios'

export default {
  name: 'ProductDetail',
  props: {
    type: {
      type: String,
      required: true
    }
  },
  data() {
    return {
      loading: false,
      product: null,
      productableData: null,
      categoryData: null,
      recentReservations: [],
      error: null,

      productConfigs: {
        activity: {
          label: 'Activités',
          singular: 'Activité',
          icon: 'fas fa-hiking',
          color: '#3b82f6',
          detailFields: ['duration', 'capacity', 'difficulty', 'equipment', 'location', 'age_min', 'age_max']
        },
        menu: {
          label: 'Menus',
          singular: 'Menu',
          icon: 'fas fa-utensils',
          color: '#10b981',
          detailFields: ['ingredients', 'allergens', 'preparation_time', 'cooking_method', 'nutritional_info']
        },
        room: {
          label: 'Hébergements',
          singular: 'Hébergement',
          icon: 'fas fa-bed',
          color: '#f59e0b',
          detailFields: ['capacity', 'surface', 'amenities', 'bed_type', 'bathroom_type', 'view_type']
        },
        option: {
          label: 'Options',
          singular: 'Option',
          icon: 'fas fa-puzzle-piece',
          color: '#8b5cf6',
          detailFields: ['type', 'required', 'max_quantity', 'description']
        }
      }
    }
  },

  computed: {
    typeConfig() {
      return this.productConfigs[this.type] || this.productConfigs.activity
    },
    productId() {
      return this.$route.params.id
    }
  },

  async created() {
    await this.fetchProduct()
    await this.fetchRecentReservations()
  },

  methods: {
    async fetchProduct() {
      this.loading = true
      this.error = null
      
      try {
        console.log('Fetching product:', this.productId)
        
        // Récupération du produit principal
        const response = await axios.get(`/api/products/${this.productId}`, {
          headers: {
            'Accept': 'application/ld+json',
            'Content-Type': 'application/json'
          }
        })
        
        this.product = response.data
        console.log('Product data:', this.product)
        
        // Récupération des données productable si elles existent
        if (this.product.productable && typeof this.product.productable === 'string') {
          try {
            const productableResponse = await axios.get(this.product.productable, {
              headers: {
                'Accept': 'application/ld+json',
                'Content-Type': 'application/json'
              }
            })
            this.productableData = productableResponse.data
            console.log('Productable data:', this.productableData)
          } catch (productableError) {
            console.warn('Could not fetch productable data:', productableError)
            this.productableData = this.product.productable
          }
        } else if (typeof this.product.productable === 'object') {
          this.productableData = this.product.productable
        }
        
        // Récupération de la catégorie si c'est une IRI
        if (this.product.category && typeof this.product.category === 'string') {
          try {
            const categoryResponse = await axios.get(this.product.category, {
              headers: {
                'Accept': 'application/ld+json',
                'Content-Type': 'application/json'
              }
            })
            this.categoryData = categoryResponse.data
            console.log('Category data:', this.categoryData)
          } catch (categoryError) {
            console.warn('Could not fetch category data:', categoryError)
          }
        } else if (typeof this.product.category === 'object') {
          this.categoryData = this.product.category
        }
        
      } catch (error) {
        console.error('Erreur lors du chargement du produit:', error)
        this.error = 'Erreur lors du chargement du produit'
        this.product = null
      } finally {
        this.loading = false
      }
    },

    async fetchRecentReservations() {
      try {
        // Pour l'instant, simulation avec des données factices
        // À remplacer par un vrai appel API quand disponible
        this.recentReservations = [
          {
            id: 1,
            customer_name: 'Marie Dupont',
            created_at: new Date(Date.now() - 86400000).toISOString(), // hier
            total_amount: this.product?.price || 50
          },
          {
            id: 2,
            customer_name: 'Jean Martin',
            created_at: new Date(Date.now() - 172800000).toISOString(), // avant-hier
            total_amount: this.product?.price || 50
          }
        ]
      } catch (error) {
        console.error('Erreur lors du chargement des réservations:', error)
        this.recentReservations = []
      }
    },

    async toggleStatus() {
      try {
        const newStatus = !this.product.status
        
        const response = await axios.patch(`/api/products/${this.product.id}`, {
          status: newStatus
        })
        
        this.product.status = newStatus
        console.log(`Produit ${this.product.status ? 'activé' : 'désactivé'}`)
      } catch (error) {
        console.error('Erreur lors de la modification du statut:', error)
      }
    },

    async duplicateProduct() {
      try {
        const duplicatedData = { ...this.product }
        delete duplicatedData.id
        delete duplicatedData['@id']
        delete duplicatedData['@type']
        duplicatedData.name = `${this.product.name} (copie)`
        
        const response = await axios.post('/api/products', duplicatedData)
        console.log('Produit dupliqué:', response.data)
        
        // Redirection vers la liste avec un message de succès
        this.$router.push({
          name: 'ProductsShow',
          params: { type: this.type }
        })
      } catch (error) {
        console.error('Erreur lors de la duplication:', error)
      }
    },

    async deleteProduct() {
      if (!confirm(`Êtes-vous sûr de vouloir supprimer "${this.product.name}" ?`)) return

      try {
        await axios.delete(`/api/products/${this.product.id}`)
        console.log('Produit supprimé')
        
        this.$router.push({ 
          name: 'ProductsShow', 
          params: { type: this.type } 
        })
      } catch (error) {
        console.error('Erreur lors de la suppression:', error)
      }
    },

    // Gestion des images
    getValidImageUrl(imageUrl) {
      if (!imageUrl) {
        return this.getPlaceholderImage()
      }

      // Vérification des URLs malformées
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
        console.warn('Invalid image URL:', imageUrl)
        return this.getPlaceholderImage()
      }
    },

    getPlaceholderImage() {
      const svg = `
        <svg width="400" height="300" xmlns="http://www.w3.org/2000/svg">
          <rect width="400" height="300" fill="#f3f4f6"/>
          <text x="200" y="150" text-anchor="middle" dy=".3em" font-family="Arial, sans-serif" font-size="16" fill="#9ca3af">
            Image non disponible
          </text>
        </svg>
      `
      return 'data:image/svg+xml;base64,' + btoa(svg)
    },

    handleImageError(event) {
      console.warn('Image failed to load:', event.target.src)
      event.target.src = this.getPlaceholderImage()
      event.target.onerror = null
    },

    // Utilitaires pour les catégories
    getProductCategoryName() {
      if (this.categoryData) {
        return this.categoryData.name
      }
      if (this.product.category && typeof this.product.category === 'object') {
        return this.product.category.name
      }
      return 'Non définie'
    },

    // Utilitaires pour les statuts
    getStatusClass(product) {
      if (product.isDraft || product.is_draft) return 'status-draft'
      return product.status ? 'status-active' : 'status-inactive'
    },

    getStatusLabel(product) {
      if (product.isDraft || product.is_draft) return 'Brouillon'
      return product.status ? 'Actif' : 'Inactif'
    },

    getStatusIcon(product) {
      if (product.isDraft || product.is_draft) return 'fas fa-edit'
      return product.status ? 'fas fa-check-circle' : 'fas fa-pause-circle'
    },

    // Utilitaires pour les champs
    getFieldLabel(field) {
      const labels = {
        duration: 'Durée',
        capacity: 'Capacité',
        difficulty: 'Difficulté',
        equipment: 'Équipement requis',
        location: 'Lieu',
        age_min: 'Âge minimum',
        age_max: 'Âge maximum',
        ingredients: 'Ingrédients',
        allergens: 'Allergènes',
        preparation_time: 'Temps de préparation',
        cooking_method: 'Méthode de cuisson',
        nutritional_info: 'Informations nutritionnelles',
        surface: 'Surface',
        amenities: 'Équipements',
        bed_type: 'Type de lit',
        bathroom_type: 'Type de salle de bain',
        view_type: 'Type de vue',
        type: 'Type',
        required: 'Obligatoire',
        max_quantity: 'Quantité maximum',
        description: 'Description'
      }
      return labels[field] || field.charAt(0).toUpperCase() + field.slice(1)
    },

    formatFieldValue(value, field) {
      if (value === null || value === undefined || value === '') return 'Non défini'

      if (field === 'duration') return `${value} minutes`
      if (field === 'capacity') return `${value} personne(s)`
      if (field === 'surface') return `${value} m²`
      if (field === 'preparation_time') return `${value} minutes`
      if (field === 'required') return value ? 'Oui' : 'Non'
      if (field === 'age_min' || field === 'age_max') return `${value} ans`

      if (Array.isArray(value)) return value.join(', ')
      if (typeof value === 'object') return JSON.stringify(value)

      return value.toString()
    },

    isFullWidthField(field) {
      const fullWidthFields = ['ingredients', 'equipment', 'amenities', 'nutritional_info', 'description']
      return fullWidthFields.includes(field)
    },

    // Utilitaires de formatage
    formatPrice(price) {
      return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'EUR'
      }).format(parseFloat(price) || 0)
    },

    formatDate(date) {
      if (!date) return 'Non définie'
      
      try {
        return new Date(date).toLocaleDateString('fr-FR', {
          year: 'numeric',
          month: 'long',
          day: 'numeric',
          hour: '2-digit',
          minute: '2-digit'
        })
      } catch (error) {
        return 'Date invalide'
      }
    },

    // Actions sur les images
    selectImage(image) {
      console.log('Image sélectionnée:', image)
      // TODO: Logique pour changer l'image principale
    },

    openImageEditor() {
      console.log('Ouverture de l\'éditeur d\'image')
      // TODO: Logique pour ouvrir l'éditeur d'image
    },

    // Actions d'export/partage
    exportProduct() {
      console.log('Export du produit')
      // TODO: Implémenter l'export
    },

    shareProduct() {
      if (navigator.share) {
        navigator.share({
          title: this.product.name,
          text: this.product.description,
          url: window.location.href
        }).catch(err => console.log('Erreur lors du partage:', err))
      } else {
        // Fallback pour les navigateurs qui ne supportent pas l'API de partage
        const url = window.location.href
        navigator.clipboard.writeText(url).then(() => {
          alert('Lien copié dans le presse-papiers!')
        }).catch(() => {
          alert(`Partagez ce lien: ${url}`)
        })
      }
    }
  }
}
</script>

<style scoped>
.product-detail-container {
  padding: 2rem;
  max-width: 1200px;
  margin: 0 auto;
}

.loading-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 4rem 2rem;
}

.spinner {
  width: 2.5rem;
  height: 2.5rem;
  border: 3px solid #e5e7eb;
  border-top: 3px solid #3b82f6;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin-bottom: 1rem;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.detail-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 2rem;
  padding-bottom: 1rem;
  border-bottom: 1px solid #e5e7eb;
}

.back-link {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  color: #6b7280;
  text-decoration: none;
  font-weight: 500;
}

.back-link:hover {
  color: #3b82f6;
}

.breadcrumb {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  color: #9ca3af;
  font-size: 0.875rem;
  margin-top: 0.5rem;
}

.header-actions {
  display: flex;
  gap: 1rem;
}

.product-title-section {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 2rem;
}

.product-type-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem 1rem;
  border-radius: 9999px;
  color: white;
  font-size: 0.875rem;
  font-weight: 600;
  margin-bottom: 1rem;
}

.product-title {
  margin: 0 0 0.5rem 0;
  font-size: 2.5rem;
  font-weight: 700;
  color: #1f2937;
}

.product-category {
  margin: 0;
  color: #6b7280;
  font-size: 1.125rem;
}

.status-controls {
  display: flex;
  align-items: center;
  gap: 1rem;
  margin-bottom: 1rem;
}

.status-indicator {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem 1rem;
  border-radius: 9999px;
  font-weight: 600;
  font-size: 0.875rem;
}

.status-active {
  background: #d1fae5;
  color: #065f46;
}

.status-inactive {
  background: #fee2e2;
  color: #991b1b;
}

.status-draft {
  background: #fef3c7;
  color: #92400e;
}

.product-price-display {
  font-size: 2rem;
  font-weight: 700;
  color: #059669;
}

.product-content {
  display: grid;
  grid-template-columns: 1fr 2fr;
  gap: 2rem;
  margin-bottom: 2rem;
}

.main-image {
  position: relative;
  border-radius: 12px;
  overflow: hidden;
  margin-bottom: 1rem;
}

.main-image img {
  width: 100%;
  height: 300px;
  object-fit: cover;
}

.image-actions {
  position: absolute;
  bottom: 1rem;
  right: 1rem;
}

.image-action-btn {
  background: rgba(255, 255, 255, 0.9);
  border: none;
  border-radius: 8px;
  padding: 0.5rem 1rem;
  font-size: 0.875rem;
  cursor: pointer;
  backdrop-filter: blur(10px);
}

.product-metrics {
  background: white;
  border-radius: 12px;
  padding: 1.5rem;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.metrics-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
}

.metric-item {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.metric-icon {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  background: #f3f4f6;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #6b7280;
}

.metric-number {
  display: block;
  font-size: 1.25rem;
  font-weight: 700;
  color: #1f2937;
}

.metric-label {
  font-size: 0.875rem;
  color: #6b7280;
}

.info-section {
  background: white;
  border-radius: 12px;
  padding: 1.5rem;
  margin-bottom: 1.5rem;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.info-section h3 {
  margin: 0 0 1rem 0;
  font-size: 1.25rem;
  font-weight: 600;
  color: #1f2937;
}

.info-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
}

.info-item {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.info-item.full-width {
  grid-column: 1 / -1;
}

.info-item label {
  font-size: 0.875rem;
  font-weight: 600;
  color: #6b7280;
}

.info-item span {
  color: #1f2937;
}

.description-content {
  background: #f9fafb;
  padding: 1rem;
  border-radius: 8px;
  line-height: 1.6;
}

.tags-container {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
}

.tag {
  background: #e5e7eb;
  color: #374151;
  padding: 0.25rem 0.75rem;
  border-radius: 9999px;
  font-size: 0.875rem;
}

.options-list,
.reservations-list {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.option-item,
.reservation-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1rem;
  background: #f9fafb;
  border-radius: 8px;
}

.quick-actions-bar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1.5rem;
  background: white;
  border-radius: 12px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.last-updated {
  color: #6b7280;
  font-size: 0.875rem;
}

.actions-right {
  display: flex;
  gap: 1rem;
}

.error-state {
  text-align: center;
  padding: 4rem 2rem;
}

.error-icon {
  font-size: 4rem;
  color: #dc2626;
  margin-bottom: 1rem;
}

/* Buttons */
.btn {
  padding: 0.75rem 1.5rem;
  border: none;
  border-radius: 8px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s ease;
  text-decoration: none;
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.875rem;
}

.btn-primary {
  background: #3b82f6;
  color: white;
}

.btn-secondary {
  background: #6b7280;
  color: white;
}

.btn-success {
  background: #059669;
  color: white;
}

.btn-warning {
  background: #d97706;
  color: white;
}

.btn-danger {
  background: #dc2626;
  color: white;
}

.btn-outline {
  background: white;
  border: 1px solid #d1d5db;
  color: #374151;
}

.btn-sm {
  padding: 0.5rem 1rem;
  font-size: 0.8rem;
}

@media (max-width: 768px) {
  .product-content {
    grid-template-columns: 1fr;
  }
  
  .product-title-section {
    flex-direction: column;
    gap: 1rem;
  }
  
  .detail-header {
    flex-direction: column;
    gap: 1rem;
    align-items: stretch;
  }
  
  .info-grid {
    grid-template-columns: 1fr;
  }
  
  .metrics-grid {
    grid-template-columns: 1fr;
  }
  
  .quick-actions-bar {
    flex-direction: column;
    gap: 1rem;
  }
}
</style>