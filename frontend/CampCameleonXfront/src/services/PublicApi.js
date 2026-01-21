import httpClient from './httpClient'
import { getProductableType, buildTypeConfigFromProductableType } from '@/shared/configs/productConfigs'

class PublicApi {
  constructor() {
    this.cache = new Map()
    this.cacheTimeout = 5 * 60 * 1000 // 5 minutes
  }

  // =============================
  // Helpers
  // =============================

  normalizeProduct(obj = {}) {
    const p = { ...obj }

    p.productableDetail = p.productableDetail ?? p.productable_data ?? p.productable_detail ?? null
    p.productableData = p.productableData ?? p.productableDetail ?? null

    const pt = p.productable_type || p.productableType
    if (pt) {
      p.typeConfig = buildTypeConfigFromProductableType(pt)
    }

    p.image = p.image || '/images/placeholder-product.svg'
    if (!p.formatted_price && typeof p.price !== 'undefined') {
      try {
        p.formatted_price = new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' })
          .format(Number(p.price || 0))
      } catch (_) { /* no-op */ }
    }

    return p
  }

  normalizeTypeParam(params = {}) {
    if (!params || !params.type) return params
    const t = String(params.type)
    if (!t.includes('\\')) return { ...params, type: getProductableType(t) }
    return params
  }

  buildURL(path, params = {}) {
    const qp = new URLSearchParams()
    Object.entries(params).forEach(([key, value]) => {
      if (value !== null && value !== undefined && value !== '') {
        qp.append(key, value)
      }
    })
    return `${path}${qp.toString() ? '?' + qp.toString() : ''}`
  }

  parseResponsePayload(payload) {
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

  async getProducts(params = {}) {
    const defaults = { mode: 'light', per_page: 20 }
    const normalized = this.normalizeTypeParam({ ...defaults, ...params })

    const cacheKey = this.getCacheKey('products', normalized)
    const hit = this.cache.get(cacheKey)
    if (hit && Date.now() - hit.timestamp < this.cacheTimeout) return hit.data

    const url = this.buildURL('/products', normalized)
    const response = await httpClient.get(url)
    const result = this.parseResponsePayload(response.data)

    this.cache.set(cacheKey, { data: result, timestamp: Date.now() })
    return result
  }

  async getProduct(id) {
    const response = await httpClient.get(`/products/${id}`)
    return this.normalizeProduct(response.data)
  }

  // =============================
  // Raccourcis typés
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
    const response = await httpClient.get(url)
    const payload = response.data

    const items = Array.isArray(payload)
      ? payload
      : (payload?.data ?? payload?.['hydra:member'] ?? payload?.member ?? [])

    this.cache.set(cacheKey, { data: items, timestamp: Date.now() })
    return items
  }

  // =============================
  // Devis
  // =============================
  async createQuoteRequest(payload) {
    const response = await httpClient.post('/quotes', payload)
    return response.data
  }

  async saveQuote(payload) {
    try {
      let items = []

      if (payload.items && Array.isArray(payload.items)) {
        items = payload.items
          .map(it => ({
            product_id: Number(it.product_id),
            quantity: Math.max(1, Number(it.quantity) || 1)
          }))
          .filter(it => it.product_id && it.quantity > 0)
      }
      else if (payload.product_ids && Array.isArray(payload.product_ids)) {
        const productCounts = {}
        for (const id of payload.product_ids) {
          productCounts[id] = (productCounts[id] || 0) + 1
        }

        items = Object.entries(productCounts).map(([productId, quantity]) => ({
          product_id: Number(productId),
          quantity: quantity
        }))
      }

      if (!items.length) {
        throw new Error('Aucun produit sélectionné.')
      }

      const apiPayload = {
        email: payload.email,
        contact: payload.contact || {},
        dates: payload.dates || {},
        items: items,
        total_price: payload.total_price || payload.total_amount || 0
      }

      const response = await httpClient.post('/quote-requests', apiPayload)

      return {
        success: true,
        quote_request: response.data,
        message: 'Devis sauvegardé ! Un email de confirmation vous a été envoyé.',
        next_step: response.data.next_step || 'validation_email'
      }

    } catch (error) {
      console.error('Erreur sauvegarde devis:', error)

      if (error.response?.status === 422) {
        const violations = error.response.data.details || {}
        const errorMessages = Object.values(violations).flat().join(', ')
        throw new Error(`Données invalides: ${errorMessages}`)
      }

      throw new Error(error.response?.data?.message || 'Erreur lors de la sauvegarde du devis.')
    }
  }

  async validateQuoteByEmail(quoteId, validationToken) {
    try {
      const response = await httpClient.post(`/quote-requests/${quoteId}/validate-email`, {
        validation_token: validationToken
      })

      return {
        success: response.data.status === 'validated',
        message: response.data.status === 'validated'
          ? 'Devis validé ! Il vous a été envoyé par email.'
          : 'Lien expiré ou invalide.',
        validated: response.data.status === 'validated'
      }

    } catch (error) {
      console.error('Erreur validation devis:', error)

      if (error.response?.status === 404) {
        throw new Error('Devis introuvable ou lien invalide.')
      }

      throw new Error('Erreur lors de la validation.')
    }
  }

  async createStripeSession(quoteId) {
    try {
      const response = await httpClient.post('/stripe/create-payment-session', {
        quote_id: quoteId
      })

      return {
        success: true,
        session_id: response.data.session_id,
        checkout_url: response.data.checkout_url,
        quote_reference: response.data.quote_reference
      }

    } catch (error) {
      console.error('Erreur création session Stripe:', error)

      if (error.response?.status === 400) {
        const errorData = error.response.data
        if (errorData.error?.includes('validé par email')) {
          throw new Error('Le devis doit être validé par email avant le paiement')
        }
        throw new Error(errorData.error || 'Devis invalide pour le paiement')
      }

      if (error.response?.status === 422) {
        const violations = error.response.data.details || {}
        const errorMessages = Object.values(violations).flat().join(', ')
        throw new Error(`Données invalides: ${errorMessages}`)
      }

      if (error.response?.status === 404) {
        throw new Error('Devis introuvable. Veuillez refaire une demande de devis.')
      }

      throw new Error('Erreur lors de la création de la session de paiement. Veuillez réessayer.')
    }
  }

  async getQuoteForEdit(quoteId, editToken) {
    try {
      const response = await httpClient.get(`/quote-requests/${quoteId}/edit/${editToken}`)

      return {
        success: true,
        quote: response.data
      }

    } catch (error) {
      console.error('Erreur récupération devis:', error)

      if (error.response?.status === 404) {
        throw new Error('Devis introuvable ou lien invalide.')
      }

      if (error.response?.status === 400) {
        const errorMessage = error.response.data?.error || 'Devis non modifiable'
        throw new Error(errorMessage)
      }

      throw new Error('Erreur lors du chargement du devis.')
    }
  }

  async updateQuote(quoteId, editToken, quoteData) {
    try {
      const response = await httpClient.patch(`/quote-requests/${quoteId}/edit/${editToken}`, quoteData)

      return {
        success: true,
        quote: response.data
      }

    } catch (error) {
      console.error('Erreur mise à jour devis:', error)

      if (error.response?.status === 400) {
        const errorMessage = error.response.data?.error || 'Erreur de validation'
        throw new Error(errorMessage)
      }

      if (error.response?.status === 422) {
        const violations = error.response.data.details || {}
        const errorMessages = Object.values(violations).flat().join(', ')
        throw new Error(`Données invalides: ${errorMessages}`)
      }

      throw new Error('Erreur lors de la mise à jour du devis.')
    }
  }

  async checkQuoteStatus(reference) {
    try {
      const response = await httpClient.get(`/quote-requests/status/${reference}`)
      return response.data
    } catch (error) {
      console.error('Erreur vérification statut:', error)
      throw new Error('Impossible de vérifier le statut du devis.')
    }
  }

  // =============================
  // Réservations directes
  // =============================
  async createReservation(payload) {
    const response = await httpClient.post('/reservations', payload)
    return response.data
  }

  async findOrCreateCustomer(customerData) {
    const response = await httpClient.post('/customers/find-or-create', {
      email: customerData.customer_email || '',
      name: customerData.customer_name,
      phone: customerData.customer_phone || ''
    })
    return response.data
  }

  // =============================
  // Demande de conseil
  // =============================
  async requestAdvice(adviceData) {
    try {
      const requestData = {
        email: adviceData.email,
        contact: {
          name: adviceData.contact.name,
          last_name: adviceData.contact.last_name,
          phone: adviceData.contact.phone,
          message: `[DEMANDE CONSEIL] ${adviceData.message || 'Conseil personnalisé demandé'}`
        },
        product_ids: adviceData.selected_products ? this.extractProductIds(adviceData.selected_products) : [],
        dates: adviceData.dates,
        total_price: adviceData.total_price || 0,
        source: 'website_advice'
      }

      const response = await this.saveQuote(requestData)

      if (response.success) {
        return {
          success: true,
          advice_request: response.quote_request,
          message: 'Demande de conseil transmise avec succès ! Nous vous contacterons rapidement.'
        }
      }

      return response

    } catch (error) {
      console.error('Erreur demande conseil:', error)
      throw new Error('Erreur lors de l\'envoi de la demande de conseil. Veuillez réessayer.')
    }
  }

  async requestPersonalAdvice(payload) {
    const response = await httpClient.post('/advice-requests', payload)
    return response.data
  }

  extractProductIds(selectedProducts) {
    const ids = []

    if (selectedProducts.activities) {
      selectedProducts.activities.forEach(activity => {
        if (activity.id) ids.push(activity.id)
      })
    }

    if (selectedProducts.room && selectedProducts.room.id) {
      ids.push(selectedProducts.room.id)
    }

    if (selectedProducts.menus) {
      selectedProducts.menus.forEach(menu => {
        if (menu.id) ids.push(menu.id)
      })
    }

    return ids
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