import axios from 'axios'
import { buildTypeConfigFromProductableType } from '@/shared/configs/productConfigs'

class ProductsApi {

  constructor() {
    // baseURL défini globalement dans main.js
    this.defaultHeaders = {
      'Accept': 'application/json'
    }
    console.log('ProductsApi initialized')
  }

  // ==========================================
  // PRODUCTS CRUD
  // ==========================================
  async getProducts(params = {}) {
    try {
      console.log("1: ", new Date())
      const searchParams = new URLSearchParams()
      Object.entries(params).forEach(([key, value]) => {
        if (value !== null && value !== undefined && value !== '') {
          searchParams.append(key, value)
        }
      })
      const url = `/products${searchParams.toString() ? '?' + searchParams.toString() : ''}`
      
      console.log("2", new Date())
      const response = await axios.get(url, {
        headers: this.defaultHeaders
      })
      console.log("3: ", new Date())

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

  async getProduct(id) {
    try {
      const { data: raw } = await axios.get(`/products/${id}`, {
        headers: this.defaultHeaders
      })
      console.log('Données brutes reçues du backend:', raw)

      let product
      if (raw?.['@type'] === 'Collection' && Array.isArray(raw.member)) {
        const first = raw.member[0]
        product = Array.isArray(first) ? this._rowToProduct(first) : this._normalizeProduct(first || {})
      } else if (Array.isArray(raw)) {
        const first = raw[0] || {}
        product = Array.isArray(first) ? this._rowToProduct(first) : this._normalizeProduct(first)
      } else {
        product = this._normalizeProduct(raw || {})
      }

      if (!product.typeConfig && product.productableType) {
        product.typeConfig = this.buildTypeConfig(product.productableType)
      }

      console.log('✅ Produit.typeConfig:', product.typeConfig)
      console.log('✅ Produit.productableDetail:', product.productableDetail)
      return product
    } catch (error) {
      console.error(`Erreur lors de la récupération du produit ${id}:`, error)
      throw this.handleError(error)
    }
  }

  buildTypeConfig(productableType) {
    return buildTypeConfigFromProductableType(productableType)
  }

  async createProduct(productData) {
    try {
      console.log('productData:', productData)
      // axios infèrera Content-Type: application/json automatiquement pour les objets JS
      const response = await axios.post(`/products`, productData, {
        headers: this.defaultHeaders
      })
      return response.data
    } catch (error) {
      console.error('Erreur lors de la création du produit:', error)
      throw this.handleError(error)
    }
  }

  async updateProduct(id, productData) {
    try {
      const response = await axios.patch(`/products/${id}`, productData, {
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

  async deleteProduct(id) {
    try {
      await axios.delete(`/products/${id}`, {
        headers: this.defaultHeaders
      })
      return true
    } catch (error) {
      console.error(`Erreur lors de la suppression du produit ${id}:`, error)
      throw this.handleError(error)
    }
  }

  async duplicateProduct(id) {
    try {
      const originalProduct = await this.getProduct(id)
      const duplicateData = {
        name: `${originalProduct.name} (copie)`,
        description: originalProduct.description,
        price: originalProduct.price,
        category_id: originalProduct.category?.id,
        productableType: originalProduct.typeConfig?.class,
        productable: originalProduct.productable_detail || {},
        status: false,
        is_draft: true
      }
      return await this.createProduct(duplicateData)
    } catch (error) {
      console.error(`Erreur lors de la duplication du produit ${id}:`, error)
      throw this.handleError(error)
    }
  }

  // ==========================================
  // RELATIONS / UTILS (inchangés)
  // ==========================================
  async getCategories() {
    try {
      const response = await axios.get(`/categories`, {
        headers: this.defaultHeaders
      })
      return response.data.member || response.data || []
    } catch (error) {
      console.warn('Impossible de charger les catégories:', error)
      return []
    }
  }

  async getRelationProducts(type) {
    const typeMap = {
      'ingredients': 'App\\Models\\Ingredient',
      'dishes': 'App\\Models\\Dish',
      'menus': 'App\\Models\\Menu',
      'rooms': 'App\\Models\\Room',
      'activities': 'App\\Models\\Activity'
    }
    const apiType = typeMap[type]
    if (!apiType) throw new Error(`Type de relation non supporté: ${type}`)
    try {
      return await this.getProducts({
        type: apiType,
        per_page: 100,
        status: 'active'
      })
    } catch (error) {
      console.error(`Erreur lors de la récupération des ${type}:`, error)
      return { data: [], totalItems: 0 }
    }
  }

  async updateProductRelations(productId, productableId, productableType, relations) {
    try {
      const endpoints = {
        'App\\Models\\Menu': 'menus',
        'App\\Models\\Dish': 'dishes',
        'App\\Models\\Ingredient': 'ingredients',
        'App\\Models\\Room': 'rooms',
        'App\\Models\\Activity': 'activities'
      }
      const endpoint = endpoints[productableType]
      if (!endpoint) throw new Error(`Type productable non supporté: ${productableType}`)

      const response = await axios.patch(`/${endpoint}/${productableId}`, relations, {
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
  // UPLOAD (fix spécial FormData)
  // ==========================================
  async uploadToMediaObjects(file) {
    try {
      console.log('📤 Début upload vers /api/media_objects', {
        fileName: file?.name,
        fileSize: file?.size,
        fileType: file?.type
      })

      if (!file || !(file instanceof File)) {
        throw new Error('Fichier invalide')
      }

      if (file.size > 5 * 1024 * 1024) {
        throw new Error('Fichier trop volumineux (max 5MB)')
      }

      const allowedTypes = [
        'image/jpeg', 'image/jpg', 'image/png',
        'image/gif', 'image/webp', 'image/heic', 'image/avif'
      ]
      if (!allowedTypes.includes(file.type)) {
        throw new Error('Format de fichier non supporté')
      }

      const formData = new FormData()
      formData.append('file', file, file.name)

      // debug console avant envoi
      console.log('📝 FormData created -> has file:', formData.has('file'), 'keys:', [...formData.keys()])

      // IMPORTANT: forcer l'envoi brut du FormData (ne pas définir Content-Type)
      const response = await axios.post(`/media_objects`, formData, {
        headers: {
          'Accept': 'application/json'
          // pas de Content-Type ici, axios/gw doit ajouter le boundary
        },
        transformRequest: (data) => data,
        timeout: 30000
      })

      console.log('✅ Upload réussi:', {
        id: response.data.id,
        contentUrl: response.data.contentUrl || response.data.content_url,
        fileName: response.data.fileName || response.data.file_name
      })

      return response.data
    } catch (error) {
      console.error('❌ Erreur upload MediaObject:', {
        status: error.response?.status,
        statusText: error.response?.statusText,
        data: error.response?.data,
        message: error.message
      })

      if (error.response?.status === 413) throw new Error('Fichier trop volumineux')
      if (error.response?.status === 422) throw new Error('Format de fichier non supporté')
      if (error.response?.status === 500) throw new Error('Erreur serveur lors de l\'upload')
      throw new Error(error.response?.data?.message || error.message || 'Erreur lors de l\'upload')
    }
  }

  // ==========================================
  // ERROR HANDLING / UTIL
  // ==========================================
  handleError(error) {
    if (error.response) {
      const status = error.response.status
      const data = error.response.data
      switch (status) {
        case 400: return new Error(data.message || 'Données invalides')
        case 401: return new Error('Non autorisé')
        case 403: return new Error('Accès interdit')
        case 404: return new Error('Ressource introuvable')
        case 422: return new Error(data.message || 'Erreur de validation')
        case 500: return new Error('Erreur serveur')
        default: return new Error(data.message || `Erreur HTTP ${status}`)
      }
    } else if (error.request) {
      return new Error('Erreur de connexion au serveur')
    } else {
      return new Error(error.message || 'Erreur inconnue')
    }
  }

  _rowToProduct(row = []) {
    const [
      id, name, description, price, formatted_price,
      status, is_draft, status_label, status_class,
      type_config,
      productableType,
      image,
      _dupType,
      productable_data,
      productable_detail,
      globalTags = [],
      category = null,
      specificTags = [],
      _unused18,
      relations = {},
      metrics = {},
      created_at,
      updated_at
    ] = row

    const p = {
      id, name, description, price, formatted_price, status, is_draft,
      status_label, status_class, image, category, globalTags, specificTags,
      relations, metrics, created_at, updated_at,
      type_config, productableType, productable_data, productable_detail
    }
    p.typeConfig = p.type_config ?? null
    p.productableDetail = p.productable_detail ?? p.productable_data ?? null
    p.productableData = p.productableDetail
    return p
  }

  _normalizeProduct(obj = {}) {
    const p = { ...obj }
    p.typeConfig = p.typeConfig ?? p.type_config ?? null
    p.productableDetail = p.productableDetail ?? p.productable_detail ?? null
    p.productableData = p.productableData ?? p.productableDetail ?? p.productable_detail ?? null
    return p
  }

}

export default new ProductsApi()
