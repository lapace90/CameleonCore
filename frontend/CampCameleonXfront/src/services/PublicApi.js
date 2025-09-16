// frontend/CampCameleonXfront/src/services/PublicApi.js
// ✅ Service public unifié : toutes les requêtes HTTP front passent par ici
// - Parsing robuste (array brut, Laravel Resource, API Platform Hydra)
// - Normalisation du param `type` (slug -> FQCN) via productConfigs
// - Normalisation des objets produits (typeConfig, productableDetail/Data, placeholders)
// - Cache simple en mémoire (5 minutes)

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
  // Sauvegarde devis (pour suivi ultérieur)
  // =============================
  async saveQuote(payload) {
    const url = `${this.baseURL}/saved-quotes`
    const response = await axios.post(url, { ...payload, status: 'saved' }, {
      headers: { 'Content-Type': 'application/json', Accept: 'application/json' }
    })
    return response.data
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
