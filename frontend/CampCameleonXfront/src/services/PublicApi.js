// frontend/CampCameleonXfront/src/services/PublicApi.js
// 🚀 API OPTIMISÉE POUR LE SITE CLIENT

import axios from 'axios'

class PublicApi {
  constructor() {
    this.baseURL = '/api'
    this.cache = new Map()
    this.cacheTimeout = 5 * 60 * 1000 // 5 minutes
  }

  /**
   * 🚀 OPTIMISATION 1: Mode LIGHT pour le site client
   * Seulement les données essentielles pour l'affichage
   */
  async getProducts(params = {}) {
    const cacheKey = this.getCacheKey('products', params)

    // Vérifier le cache
    if (this.cache.has(cacheKey)) {
      const cached = this.cache.get(cacheKey)
      if (Date.now() - cached.timestamp < this.cacheTimeout) {
        return cached.data
      }
    }

    try {
      // 🚀 AJOUT du mode=light pour performance
      const optimizedParams = {
        mode: 'light',
        per_page: 12, // Plus petit pour site client
        ...params
      }

      const searchParams = new URLSearchParams()
      Object.entries(optimizedParams).forEach(([key, value]) => {
        if (value !== null && value !== undefined && value !== '') {
          searchParams.append(key, value)
        }
      })

      const response = await axios.get(`${this.baseURL}/products?${searchParams.toString()}`, {
        headers: { 'Accept': 'application/json' }
      })

      // Format simple pour site client
      const result = {
        data: response.data.data || response.data.member || [],
        pagination: {
          current_page: response.data.current_page || 1,
          last_page: response.data.last_page || 1,
          total: response.data.total || 0,
          per_page: response.data.per_page || 12
        }
      }

      // 🚀 CACHE la réponse
      this.cache.set(cacheKey, {
        data: result,
        timestamp: Date.now()
      })

      return result

    } catch (error) {
      console.error('Erreur lors du chargement des produits:', error)
      throw error
    }
  }

  /**
   * 🚀 OPTIMISATION 2: Produit détaillé (pour modale)
   */
  async getProduct(id) {
    const cacheKey = this.getCacheKey('product', { id })

    if (this.cache.has(cacheKey)) {
      const cached = this.cache.get(cacheKey)
      if (Date.now() - cached.timestamp < this.cacheTimeout) {
        return cached.data
      }
    }

    try {
      const response = await axios.get(`${this.baseURL}/products/${id}?mode=light`)

      const product = response.data

      // 🚀 CACHE le produit
      this.cache.set(cacheKey, {
        data: product,
        timestamp: Date.now()
      })

      return product

    } catch (error) {
      console.error(`Erreur lors du chargement du produit ${id}:`, error)
      throw error
    }
  }

  /**
   * 🚀 OPTIMISATION 3: Catégories avec cache agressif
   */
  async getCategories() {
    const cacheKey = 'categories'

    // Cache plus long pour les catégories (15 min)
    if (this.cache.has(cacheKey)) {
      const cached = this.cache.get(cacheKey)
      if (Date.now() - cached.timestamp < 15 * 60 * 1000) {
        return cached.data
      }
    }

    try {
      const response = await axios.get(`${this.baseURL}/categories?mode=light`)
      const categories = response.data.data || response.data.member || []

      this.cache.set(cacheKey, {
        data: categories,
        timestamp: Date.now()
      })

      return categories

    } catch (error) {
      console.error('Erreur lors du chargement des catégories:', error)
      return []
    }
  }

  /**
   * 🎯 MÉTHODES SPÉCIFIQUES SITE CLIENT
   */

  // Récupérer produits par type pour les pages dédiées
  async getActivities(params = {}) {
    return this.getProducts({
      type: 'App\\Models\\Activity',
      ...params
    })
  }

  async getRooms(params = {}) {
    return this.getProducts({
      type: 'App\\Models\\Room',
      ...params
    })
  }

  async getMenus(params = {}) {
    return this.getProducts({
      type: 'App\\Models\\Menu',
      ...params
    })
  }
  // Méthode manquante 3
  async getAvailability(params = {}) {
    try {
      const searchParams = new URLSearchParams()
      Object.entries(params).forEach(([key, value]) => {
        if (value !== null && value !== undefined && value !== '') {
          searchParams.append(key, value)
        }
      })

      const response = await axios.get(`${this.baseURL}/availability?${searchParams.toString()}`)
      return { data: response.data }
    } catch (error) {
      console.error('Erreur disponibilité:', error)
      // Fallback pour que votre modal ne casse pas
      return {
        data: {
          unavailable_dates: [],
          rooms: {},
          activities: {}
        }
      }
    }
  }
  // Recherche rapide
  async searchProducts(query, type = null) {
    const params = { search: query }
    if (type) params.type = type

    return this.getProducts(params)
  }

  /**
   * 🚀 FUTURS : Méthodes pour la modale de devis
   */

  // Vérifier disponibilité d'un produit
  async checkAvailability(productId, dates) {
    try {
      const response = await axios.post(`${this.baseURL}/products/${productId}/check-availability`, {
        start_date: dates.start,
        end_date: dates.end
      })

      return response.data.available || false
    } catch (error) {
      console.error('Erreur lors de la vérification de disponibilité:', error)
      return false
    }
  }

  // Calculer le prix d'un devis
  async calculateQuote(items) {
    try {
      const response = await axios.post(`${this.baseURL}/quote/calculate`, {
        items: items
      })

      return response.data
    } catch (error) {
      console.error('Erreur lors du calcul du devis:', error)
      throw error
    }
  }

  // Créer une demande de devis
  async createQuoteRequest(quoteData) {
    try {
      const response = await axios.post(`${this.baseURL}/quote/request`, quoteData)
      return response.data
    } catch (error) {
      console.error('Erreur lors de la création du devis:', error)
      throw error
    }
  }

  /**
   * 🔧 UTILITAIRES
   */

  getCacheKey(type, params = {}) {
    const sortedParams = Object.keys(params)
      .sort()
      .map(key => `${key}=${params[key]}`)
      .join('&')

    return `${type}_${sortedParams}`
  }

  // Vider le cache si nécessaire
  clearCache() {
    this.cache.clear()
  }

  // Nettoyer le cache expiré
  cleanExpiredCache() {
    const now = Date.now()

    for (const [key, value] of this.cache.entries()) {
      if (now - value.timestamp > this.cacheTimeout) {
        this.cache.delete(key)
      }
    }
  }
}

// Instance singleton pour le site client
export const publicApi = new PublicApi()
export default PublicApi