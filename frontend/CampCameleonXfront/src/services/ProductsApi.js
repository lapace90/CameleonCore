// services/ProductsApi.js
import axios from 'axios'

class ProductsApi {

  constructor() {
    this.baseURL = '/api'
    this.defaultHeaders = {
      'Accept': 'application/ld+json',
      'Content-Type': 'application/json'
    }
    console.log('ProductsApi initialized')
  }
  // ==========================================
  // PRODUCTS CRUD
  // ==========================================

  /**
   * Récupérer la liste des produits avec filtres
   */
  async getProducts(params = {}) {
    try {
      const searchParams = new URLSearchParams()

      Object.entries(params).forEach(([key, value]) => {
        if (value !== null && value !== undefined && value !== '') {
          searchParams.append(key, value)
        }
      })

      const url = `${this.baseURL}/products${searchParams.toString() ? '?' + searchParams.toString() : ''}`

      const response = await axios.get(url, {
        headers: this.defaultHeaders
      })

      return {
        data: response.data.member || response.data || [],
        totalItems: response.data.totalItems || 0,
        currentPage: response.data.view?.currentPage || 1,
        lastPage: response.data.view?.lastPage || 1
      }
    } catch (error) {
      console.error('Erreur lors de la récupération des produits:', error)
      throw this.handleError(error)
    }
  }

  /**
   * Récupérer un produit par son ID
   */
  async getProduct(id) {
    try {
      const response = await axios.get(`${this.baseURL}/products/${id}`, {
        headers: this.defaultHeaders
      })

      const product = response.data

      // ✅ SIMPLIFICATION : Avec la correction backend, 
      // typeConfig et productableDetail sont maintenant inclus directement
      // Plus besoin de récupérer productable séparément

      // Assurer la compatibilité avec l'ancien code qui attend productableData
      if (!product.productableData && product.productableDetail) {
        product.productableData = product.productableDetail
      }

      console.log('✅ Produit récupéré avec typeConfig:', product.typeConfig)
      console.log('✅ ProductableDetail inclus:', product.productableDetail)

      return product
    } catch (error) {
      console.error(`Erreur lors de la récupération du produit ${id}:`, error)
      throw this.handleError(error)
    }
  }

  /**
   * Créer un nouveau produit
   */
  async createProduct(productData) {
    try {
      const response = await axios.post(`${this.baseURL}/products`, productData, {
        headers: this.defaultHeaders
      })
      return response.data
    } catch (error) {
      console.error('Erreur lors de la création du produit:', error)
      throw this.handleError(error)
    }
  }

  /**
   * Mettre à jour un produit
   */
  async updateProduct(id, productData) {
    try {
      const response = await axios.patch(`${this.baseURL}/products/${id}`, productData, {
        headers: {
          ...this.defaultHeaders,
          'Content-Type': 'application/merge-patch+json'
        }
      })
      return response.data
    } catch (error) {
      console.error(`Erreur lors de la mise à jour du produit ${id}:`, error)
      throw this.handleError(error)
    }
  }

  /**
   * Supprimer un produit
   */
  async deleteProduct(id) {
    try {
      await axios.delete(`${this.baseURL}/products/${id}`, {
        headers: this.defaultHeaders
      })
      return true
    } catch (error) {
      console.error(`Erreur lors de la suppression du produit ${id}:`, error)
      throw this.handleError(error)
    }
  }

  /**
   * Dupliquer un produit
   */
  async duplicateProduct(id) {
    try {
      // Récupérer le produit original
      const originalProduct = await this.getProduct(id)

      // Préparer les données pour la duplication
      const duplicateData = {
        name: `${originalProduct.name} (copie)`,
        description: originalProduct.description,
        price: originalProduct.price,
        category_id: originalProduct.category?.id,
        productableType: originalProduct.typeConfig?.class,
        productable: originalProduct.productable_detail || {},
        status: false, // Créer en inactif par défaut
        is_draft: true // Créer comme brouillon
      }

      return await this.createProduct(duplicateData)
    } catch (error) {
      console.error(`Erreur lors de la duplication du produit ${id}:`, error)
      throw this.handleError(error)
    }
  }

  // ==========================================
  // ACTIONS EN LOT
  // ==========================================

  /**
   * Exécuter une action sur plusieurs produits
   */
  async bulkAction(productIds, action) {
    try {
      const promises = productIds.map(async (id) => {
        switch (action) {
          case 'activate':
            return this.updateProduct(id, { status: true, is_draft: false })
          case 'deactivate':
            return this.updateProduct(id, { status: false })
          case 'draft':
            return this.updateProduct(id, { is_draft: true })
          case 'delete':
            return this.deleteProduct(id)
          default:
            throw new Error(`Action non supportée: ${action}`)
        }
      })

      const results = await Promise.allSettled(promises)

      // Vérifier si toutes les actions ont réussi
      const failures = results.filter(result => result.status === 'rejected')
      if (failures.length > 0) {
        console.error('Certaines actions ont échoué:', failures)
        throw new Error(`${failures.length} action(s) ont échoué`)
      }

      return results
    } catch (error) {
      console.error(`Erreur lors de l'action en lot ${action}:`, error)
      throw this.handleError(error)
    }
  }

  // ==========================================
  // DONNÉES AUXILIAIRES
  // ==========================================

  /**
   * Récupérer les catégories
   */
  async getCategories() {
    try {
      const response = await axios.get(`${this.baseURL}/categories`, {
        headers: this.defaultHeaders
      })
      return response.data.member || response.data || []
    } catch (error) {
      console.warn('Impossible de charger les catégories:', error)
      return []
    }
  }

  // ==========================================
  // RELATIONS
  // ==========================================

  /**
   * Récupérer les produits disponibles pour les relations
   */
  async getRelationProducts(type) {
    const typeMap = {
      'ingredients': 'App\\Models\\Ingredient',
      'dishes': 'App\\Models\\Dish',
      'menus': 'App\\Models\\Menu'
    }

    const apiType = typeMap[type]
    if (!apiType) {
      throw new Error(`Type de relation non supporté: ${type}`)
    }

    try {
      return await this.getProducts({
        type: apiType,
        per_page: 100, // Récupérer plus d'éléments pour les relations
        status: 'active' // Seulement les produits actifs
      })
    } catch (error) {
      console.error(`Erreur lors de la récupération des ${type}:`, error)
      return { data: [], totalItems: 0 }
    }
  }

  /**
   * Mettre à jour les relations d'un produit
   */
  async updateProductRelations(productId, productableId, productableType, relations) {
    try {
      const endpoints = {
        'App\\Models\\Menu': 'menus',
        'App\\Models\\Dish': 'dishes',
        'App\\Models\\Ingredient': 'ingredients'
      }

      const endpoint = endpoints[productableType]
      if (!endpoint) {
        throw new Error(`Type productable non supporté: ${productableType}`)
      }

      const response = await axios.patch(`${this.baseURL}/${endpoint}/${productableId}`, relations, {
        headers: {
          ...this.defaultHeaders,
          'Content-Type': 'application/merge-patch+json'
        }
      })

      return response.data
    } catch (error) {
      console.error('Erreur lors de la mise à jour des relations:', error)
      throw this.handleError(error)
    }
  }

  // ==========================================
  // UTILITAIRES
  // ==========================================

  /**
   * Upload d'image
   */
  async uploadImage(file, productId = null) {
    try {
      const formData = new FormData()
      formData.append('image', file)
      if (productId) {
        formData.append('product_id', productId)
      }

      const response = await axios.post(`${this.baseURL}/products/upload-image`, formData, {
        headers: {
          'Content-Type': 'multipart/form-data',
          'Accept': 'application/json'
        }
      })

      return response.data
    } catch (error) {
      console.error('Erreur lors de l\'upload d\'image:', error)
      throw this.handleError(error)
    }
  }

  /**
   * Exporter les produits
   */
  async exportProducts(params = {}, format = 'csv') {
    try {
      const searchParams = new URLSearchParams({
        ...params,
        format
      })

      const response = await axios.get(`${this.baseURL}/products/export?${searchParams.toString()}`, {
        responseType: 'blob',
        headers: {
          'Accept': format === 'csv' ? 'text/csv' : 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        }
      })

      // Créer un lien de téléchargement
      const url = window.URL.createObjectURL(new Blob([response.data]))
      const link = document.createElement('a')
      link.href = url
      link.setAttribute('download', `products.${format}`)
      document.body.appendChild(link)
      link.click()
      link.remove()
      window.URL.revokeObjectURL(url)

      return true
    } catch (error) {
      console.error('Erreur lors de l\'export:', error)
      throw this.handleError(error)
    }
  }

  // ==========================================
  // GESTION D'ERREURS
  // ==========================================

  /**
   * Traitement standardisé des erreurs
   */
  handleError(error) {
    if (error.response) {
      // Erreur de réponse HTTP
      const status = error.response.status
      const data = error.response.data

      switch (status) {
        case 400:
          return new Error(data.message || 'Données invalides')
        case 401:
          return new Error('Non autorisé')
        case 403:
          return new Error('Accès interdit')
        case 404:
          return new Error('Ressource introuvable')
        case 422:
          return new Error(data.message || 'Erreur de validation')
        case 500:
          return new Error('Erreur serveur')
        default:
          return new Error(data.message || `Erreur HTTP ${status}`)
      }
    } else if (error.request) {
      // Erreur de réseau
      return new Error('Erreur de connexion au serveur')
    } else {
      // Autre erreur
      return new Error(error.message || 'Erreur inconnue')
    }
  }

  /**
  * Récupérer les relations d'un productable
  */
  async getProductRelations(productableId, productableType) {
    try {
      const endpoints = {
        'App\\Models\\Menu': 'menus',
        'App\\Models\\Dish': 'dishes',
        'App\\Models\\Ingredient': 'ingredients'
      }

      const endpoint = endpoints[productableType]
      if (!endpoint) {
        throw new Error(`Type productable non supporté: ${productableType}`)
      }

      const response = await axios.get(`${this.baseURL}/${endpoint}/${productableId}`, {
        headers: this.defaultHeaders
      })

      return response.data
    } catch (error) {
      console.error('Erreur lors de la récupération des relations:', error)
      return {}
    }
  }

}

// Export d'une instance unique
export default new ProductsApi()