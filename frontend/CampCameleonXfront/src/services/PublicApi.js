// frontend/CampCameleonXfront/src/services/PublicApi.js
// ✅ Service public unifié - LOGIQUE SIMPLE avec product_ids

import axios from 'axios'
import { getProductableType, buildTypeConfigFromProductableType } from '@/shared/configs/productConfigs'

class PublicApi {
  constructor() {
    this.baseURL = '/api'
    this.cache = new Map()
    this.cacheTimeout = 5 * 60 * 1000 // 5 minutes
  }

  // =============================
  // Helpers
  // =============================

  // Normalise/structure un produit pour le site public
  normalizeProduct(obj = {}) {
    const p = { ...obj }

    // Alias unifiés
    p.productableDetail = p.productableDetail ?? p.productable_data ?? p.productable_detail ?? null
    p.productableData = p.productableData ?? p.productableDetail ?? null

    // typeConfig à partir du productable_type
    const pt = p.productable_type || p.productableType
    if (pt) {
      p.typeConfig = buildTypeConfigFromProductableType(pt)
    }

    // Secours visuels et prix formaté
    p.image = p.image || '/images/placeholder-product.svg'
    if (!p.formatted_price && typeof p.price !== 'undefined') {
      try {
        p.formatted_price = new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' })
          .format(Number(p.price || 0))
      } catch (_) { /* no-op */ }
    }

    return p
  }

  // Normalise `type` : accepte slug ('activity') ou FQCN ('App\\Models\\Activity')
  normalizeTypeParam(params = {}) {
    if (!params || !params.type) return params
    const t = String(params.type)
    if (!t.includes('\\')) return { ...params, type: getProductableType(t) }
    return params
  }

  // Construit l'URL avec query string propre
  buildURL(path, params = {}) {
    const qp = new URLSearchParams()
    Object.entries(params).forEach(([key, value]) => {
      if (value !== null && value !== undefined && value !== '') {
        qp.append(key, value)
      }
    })
    return `${this.baseURL}${path}${qp.toString() ? '?' + qp.toString() : ''}`
  }

  // Parse universel pour différentes formes de payload
  parseResponsePayload(payload) {
    // payload peut être: Array, { data: [] }, { 'hydra:member': [] }, { member: [] }
    const rawItems = Array.isArray(payload)
      ? payload
      : (payload?.data ?? payload?.['hydra:member'] ?? payload?.member ?? [])

    const items = (rawItems || []).map(i => this.normalizeProduct(i))

    const pagination = (payload && typeof payload === 'object' && payload.pagination)
      ? {
        current_page: payload.pagination.current_page ?? 1,
        last_page: payload.pagination.last_page ?? 1,
        total: payload.pagination.total ?? items.length,
      }
      : { current_page: 1, last_page: 1, total: items.length }

    return { data: items, pagination }
  }

  getCacheKey(type, params = {}) {
    const sorted = Object.keys(params)
      .sort()
      .map(k => `${k}=${encodeURIComponent(params[k])}`)
      .join('&')
    return `${type}:${sorted}`
  }

  // =============================
  // Produits (collection + détail)
  // =============================

  /**
   * Liste des produits (tous types) avec cache et parsing robuste.
   * @param {Object} params - Ex: { type, status, search, per_page, page, mode }
   */
  async getProducts(params = {}) {
    const defaults = { mode: 'light', per_page: 20 }
    const normalized = this.normalizeTypeParam({ ...defaults, ...params })

    const cacheKey = this.getCacheKey('products', normalized)
    const hit = this.cache.get(cacheKey)
    if (hit && Date.now() - hit.timestamp < this.cacheTimeout) return hit.data

    const url = this.buildURL('/products', normalized)
    const response = await axios.get(url, { headers: { Accept: 'application/json' } })
    const result = this.parseResponsePayload(response.data)

    this.cache.set(cacheKey, { data: result, timestamp: Date.now() })
    return result
  }

  /** Détail produit (normalisé) */
  async getProduct(id) {
    const url = `${this.baseURL}/products/${id}`
    const response = await axios.get(url, { headers: { Accept: 'application/json' } })
    return this.normalizeProduct(response.data)
  }

  // =============================
  // Raccourcis typés (lisibilité côté composants)
  // =============================
  async getActivities(params = {}) {
    return this.getProducts({ ...params, type: 'App\\Models\\Activity' })
  }
  async getMenus(params = {}) {
    return this.getProducts({ ...params, type: 'App\\Models\\Menu' })
  }
  async getRooms(params = {}) {
    return this.getProducts({ ...params, type: 'App\\Models\\Room' })
  }

  // =============================
  // Recherche
  // =============================
  async searchProducts(query, type = null, extra = {}) {
    const params = { search: query, ...extra }
    if (type) params.type = type
    return this.getProducts(params)
  }

  // =============================
  // Catégories (site public)
  // =============================
  async getCategories(params = {}) {
    const defaults = { mode: 'light' }
    const normalized = { ...defaults, ...params }
    const cacheKey = this.getCacheKey('categories', normalized)

    const hit = this.cache.get(cacheKey)
    if (hit && Date.now() - hit.timestamp < this.cacheTimeout) return hit.data

    const url = this.buildURL('/categories', normalized)
    const response = await axios.get(url, { headers: { Accept: 'application/json' } })
    const payload = response.data

    const items = Array.isArray(payload)
      ? payload
      : (payload?.data ?? payload?.['hydra:member'] ?? payload?.member ?? [])

    this.cache.set(cacheKey, { data: items, timestamp: Date.now() })
    return items
  }

  // =============================
  // Devis (formulaire de la modale)
  // =============================
  async createQuoteRequest(payload) {
    const url = `${this.baseURL}/quotes`
    const response = await axios.post(url, payload, {
      headers: { 'Content-Type': 'application/json', Accept: 'application/json' }
    })
    return response.data
  }

  // =============================
  // Réservations directes
  // =============================
  async createReservation(payload) {
    const url = `${this.baseURL}/reservations`
    const response = await axios.post(url, payload, {
      headers: { 'Content-Type': 'application/json', Accept: 'application/json' }
    })
    return response.data
  }

  // =============================
  // SAUVEGARDE DEVIS - VERSION SIMPLIFIÉE
  // =============================
  
  async saveQuote(payload) {
    try {
      // 🎯 Nouveau endpoint API Platform avec validation email
      const url = `${this.baseURL}/quote-requests`
      
      // 📋 LOGIQUE SIMPLE : Collecter tous les product_ids sélectionnés
      const productIds = []
      
      // Ajouter les IDs des activités sélectionnées
      if (payload.activities && Array.isArray(payload.activities)) {
        productIds.push(...payload.activities) // payload.activities contient déjà les IDs
      }
      
      // Ajouter les IDs des menus sélectionnés  
      if (payload.menus && Array.isArray(payload.menus)) {
        productIds.push(...payload.menus) // payload.menus contient déjà les IDs
      }
      
      // Ajouter l'ID de l'hébergement sélectionné
      if (payload.room) {
        productIds.push(payload.room) // payload.room contient l'ID
      }

      // 📋 Payload simplifié pour QuoteRequest
      const requestData = {
        // ✅ SIMPLE : Liste plate des product_ids
        product_ids: productIds,
        
        // ✅ Contact client (extraction depuis payload existant)
        email: payload.contact?.email || payload.email,
        name: payload.contact?.name || payload.name,
        phone: payload.contact?.phone || payload.phone,
        message: payload.contact?.message || payload.message,
        
        // ✅ Dates séjour
        dates: {
          checkin: payload.dates?.start || payload.dates?.checkin,
          checkout: payload.dates?.endExclusive || payload.dates?.checkout,
          guests: payload.dates?.guests || 2
        },
        
        // ✅ Prix total
        total_price: payload.total_price || payload.amount || 0,
        
        // ✅ Source
        source: 'website'
      }

      console.log('💾 Envoi demande devis SIMPLE:', {
        endpoint: url,
        email: requestData.email,
        product_ids: requestData.product_ids,
        total_price: requestData.total_price,
        dates: requestData.dates
      })

      // 📨 Appel API - Créera le devis + enverra email validation
      const response = await axios.post(url, requestData, {
        headers: { 
          'Content-Type': 'application/json', 
          'Accept': 'application/json' 
        }
      })

      // 📊 Log succès
      console.log('✅ Devis créé avec succès:', {
        id: response.data.id,
        reference: response.data.quote_reference,
        status: response.data.status,
        email: response.data.email,
        products_count: response.data.selected_product_ids?.length || 0
      })

      return {
        success: true,
        quote_request: response.data,
        message: 'Devis sauvegardé ! Un email de confirmation vous a été envoyé.',
        next_step: 'validation_email'
      }

    } catch (error) {
      console.error('❌ Erreur sauvegarde devis:', error)
      
      // 🛡️ Gestion erreurs spécifiques
      if (error.response?.status === 422) {
        const violations = error.response.data.violations || []
        const errorMessages = violations.map(v => v.message).join(', ')
        throw new Error(`Données invalides: ${errorMessages}`)
      }
      
      if (error.response?.status === 429) {
        throw new Error('Trop de demandes. Veuillez patienter avant de réessayer.')
      }
      
      if (error.message.includes('email invalide')) {
        throw new Error('Adresse email invalide. Veuillez vérifier et réessayer.')
      }

      throw new Error('Erreur lors de la sauvegarde. Veuillez réessayer.')
    }
  }

  // =============================
  // NOUVELLE MÉTHODE : Validation devis via email
  // =============================

  /**
   * Valider un devis via le token reçu par email
   * @param {number} quoteId - ID du devis
   * @param {string} token - Token de validation
   */
  async validateQuote(quoteId, token) {
    try {
      const url = `${this.baseURL}/quote-requests/${quoteId}/validate/${token}`
      
      console.log('🔐 Validation devis:', { quoteId, url })
      
      const response = await axios.get(url, {
        headers: { 'Accept': 'application/json' }
      })

      console.log('✅ Devis validé:', response.data)

      return {
        success: true,
        quote_request: response.data,
        message: response.data.status === 'validated' 
          ? 'Devis validé ! Il vous a été envoyé par email.'
          : 'Lien expiré ou invalide.',
        validated: response.data.status === 'validated'
      }

    } catch (error) {
      console.error('❌ Erreur validation devis:', error)
      
      if (error.response?.status === 404) {
        throw new Error('Devis introuvable ou lien invalide.')
      }
      
      throw new Error('Erreur lors de la validation.')
    }
  }

  // =============================
  // MÉTHODE HELPER : Vérifier statut devis
  // =============================

  /**
   * Vérifier le statut d'un devis
   * @param {string} reference - Référence du devis
   */
  async checkQuoteStatus(reference) {
    try {
      const url = `${this.baseURL}/quote-requests/status/${reference}`
      const response = await axios.get(url)
      return response.data
    } catch (error) {
      console.error('Erreur vérification statut:', error)
      throw new Error('Impossible de vérifier le statut du devis.')
    }
  }

  // =============================
  // Demande de conseil personnalisé
  // =============================
  async requestPersonalAdvice(payload) {
    const url = `${this.baseURL}/advice-requests`
    const response = await axios.post(url, payload, {
      headers: { 'Content-Type': 'application/json', Accept: 'application/json' }
    })
    return response.data
  }

  // =============================
  // Maintenance du cache
  // =============================
  clearCache() {
    this.cache.clear()
  }

  cleanExpiredCache() {
    const now = Date.now()
    for (const [key, value] of this.cache.entries()) {
      if (now - value.timestamp > this.cacheTimeout) this.cache.delete(key)
    }
  }
}

export const publicApi = new PublicApi()
export default PublicApi