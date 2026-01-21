import httpClient from './httpClient'
import { buildTypeConfigFromProductableType } from '@/shared/configs/productConfigs'

class ProductsApi {

  // ==========================================
  // PRODUCTS CRUD
  // ==========================================
  async getProducts(params = {}) {
    try {
      const searchParams = new URLSearchParams()
      Object.entries(params).forEach(([key, value]) => {
        if (value !== null && value !== undefined && value !== '') {
          searchParams.append(key, value)
        }
      })
      const url = `/products${searchParams.toString() ? '?' + searchParams.toString() : ''}`
      
      const response = await httpClient.get(url)

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
      const { data: raw } = await httpClient.get(`/products/${id}`)

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
      const response = await httpClient.post('/products', productData)
      return response.data
    } catch (error) {
      console.error('Erreur lors de la création du produit:', error)
      throw this.handleError(error)
    }
  }

  async updateProduct(id, productData) {
    try {
      const response = await httpClient.patch(`/products/${id}`, productData, {
        headers: {
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
      await httpClient.delete(`/products/${id}`)
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
  // RELATIONS / UTILS
  // ==========================================
  async getCategories() {
    try {
      const response = await httpClient.get('/categories')
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
    const productableType = typeMap[type] || type
    try {
      const response = await httpClient.get('/products', {
        params: { type: productableType, per_page: 100 }
      })
      return response.data.member || response.data || []
    } catch (error) {
      console.warn(`Impossible de charger les produits de type ${type}:`, error)
      return []
    }
  }

  // ==========================================
  // MEDIA UPLOAD
  // ==========================================
  async uploadImage(file) {
    try {
      const formData = new FormData()
      formData.append('file', file)

      const response = await httpClient.post('/media_objects', formData, {
        headers: {
          'Content-Type': 'multipart/form-data'
        }
      })

      return response.data
    } catch (error) {
      console.error('Erreur upload MediaObject:', error)

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