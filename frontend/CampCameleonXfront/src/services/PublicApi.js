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
      const url = `${this.baseURL}/quote-requests`

      console.log('🔍 DEBUG PublicApi - Payload reçu:', payload);

      // ✅ CORRECTION : Utiliser directement product_ids du payload
      let productIds = [];

      if (payload.product_ids && Array.isArray(payload.product_ids)) {
        // ✅ Nouveau format depuis QuoteModal (direct)
        productIds = payload.product_ids;
        console.log('✅ Product IDs trouvés directement:', productIds);
      } else {
        // ✅ Fallback ancienne structure (pour compatibility)
        if (payload.activities && Array.isArray(payload.activities)) {
          productIds.push(...payload.activities);
        }
        if (payload.menus && Array.isArray(payload.menus)) {
          productIds.push(...payload.menus);
        }
        if (payload.room) {
          productIds.push(payload.room);
        }
        console.log('✅ Product IDs reconstruits:', productIds);
      }

      // ✅ Validation critique
      if (!productIds || productIds.length === 0) {
        console.error('❌ Aucun product_id trouvé:', payload);
        throw new Error('Aucun produit sélectionné. Veuillez sélectionner au moins un hébergement.');
      }

      // ✅ Payload backend corrigé
      const requestData = {
        // ✅ Contact
        email: payload.email || payload.contact?.email,
        contact: {
          name: payload.contact?.name || payload.name,
          last_name: payload.contact?.last_name || payload.last_name,
          phone: payload.contact?.phone || payload.phone,
          message: payload.contact?.message || payload.message || ''
        },

        // ✅ CLEF : Product IDs (garantis non vides)
        product_ids: productIds,

        // ✅ Dates
        dates: {
          checkin: payload.dates?.checkin || payload.dates?.start,
          endExclusive: payload.dates?.endExclusive || payload.dates?.checkout,
          guests: payload.dates?.guests || 2
        },

        // ✅ Prix
        total_price: payload.total_price || payload.amount || 0,
        source: 'website'
      };

      console.log('💾 Envoi demande devis CORRIGÉ:', {
        endpoint: url,
        email: requestData.email,
        product_ids: requestData.product_ids,  // ← Plus jamais vide !
        product_ids_count: requestData.product_ids.length,
        total_price: requestData.total_price,
        dates: requestData.dates
      });

      // ✅ Appel API
      const response = await axios.post(url, requestData, {
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json'
        }
      });

      console.log('✅ Devis créé avec succès:', {
        id: response.data.id,
        reference: response.data.quote_reference,
        status: response.data.status,
        email: response.data.email,
        products_count: response.data.selected_product_ids?.length || 0
      });

      return {
        success: true,
        quote_request: response.data,
        message: 'Devis sauvegardé ! Un email de confirmation vous a été envoyé.',
        next_step: 'validation_email'
      };

    } catch (error) {
      console.error('❌ Erreur sauvegarde devis:', error);

      if (error.response?.status === 422) {
        const violations = error.response.data.violations || [];
        const errorMessages = violations.map(v => v.message).join(', ');
        throw new Error(`Données invalides: ${errorMessages}`);
      }

      if (error.response?.status === 429) {
        throw new Error('Trop de demandes. Veuillez patienter avant de réessayer.');
      }

      if (error.response?.status === 500) {
        throw new Error('Erreur serveur. Veuillez réessayer dans quelques instants.');
      }

      if (error.message.includes('email invalide')) {
        throw new Error('Adresse email invalide. Veuillez vérifier et réessayer.');
      }

      throw new Error('Erreur lors de la sauvegarde. Veuillez réessayer.');
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
  /**
   * Créer une session de paiement Stripe depuis un devis validé
   * @param {number} quoteId - ID du devis validé
   */
  async createStripeSession(quoteId) {
    try {
      const url = `${this.baseURL}/stripe/create-payment-session`;

      console.log('💳 Création session Stripe:', { quoteId, url });

      const response = await axios.post(url, {
        quote_id: quoteId
      }, {
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json'
        }
      });

      console.log('✅ Session Stripe créée:', {
        session_id: response.data.session_id,
        quote_reference: response.data.quote_reference
      });

      return {
        success: true,
        session_id: response.data.session_id,
        checkout_url: response.data.checkout_url,
        quote_reference: response.data.quote_reference
      };

    } catch (error) {
      console.error('❌ Erreur création session Stripe:', error);

      // Gestion erreurs spécifiques
      if (error.response?.status === 400) {
        const errorData = error.response.data;
        if (errorData.error.includes('validé par email')) {
          throw new Error('Le devis doit être validé par email avant le paiement');
        }
        throw new Error(errorData.error || 'Devis invalide pour le paiement');
      }

      if (error.response?.status === 422) {
        const violations = error.response.data.details || {};
        const errorMessages = Object.values(violations).flat().join(', ');
        throw new Error(`Données invalides: ${errorMessages}`);
      }

      if (error.response?.status === 404) {
        throw new Error('Devis introuvable. Veuillez refaire une demande de devis.');
      }

      throw new Error('Erreur lors de la création de la session de paiement. Veuillez réessayer.');
    }
  }

  /**
   * Demander un conseil personnalisé
   * @param {Object} adviceData - Données pour la demande de conseil
   */
  async requestAdvice(adviceData) {
    try {
      // Pour l'instant, utiliser le même endpoint que les devis
      // mais avec un flag spécial pour identifier que c'est une demande de conseil
      const requestData = {
        email: adviceData.email,
        contact: {
          name: adviceData.contact.name,
          last_name: adviceData.contact.last_name,
          phone: adviceData.contact.phone,
          message: `[DEMANDE CONSEIL] ${adviceData.message || 'Conseil personnalisé demandé'}`
        },
        product_ids: adviceData.selected_products ?
          this.extractProductIds(adviceData.selected_products) : [],
        dates: adviceData.dates,
        total_price: adviceData.total_price || 0,
        source: 'website_advice' // Identifier le type de demande
      };

      console.log('👨‍💼 Envoi demande conseil:', {
        email: requestData.email,
        message_preview: requestData.contact.message.substring(0, 100) + '...'
      });

      // Utiliser le même endpoint pour créer un "devis-conseil"
      const response = await this.saveQuote(requestData);

      if (response.success) {
        console.log('✅ Demande conseil créée:', {
          reference: response.quote_request.quote_reference,
          type: 'conseil'
        });

        return {
          success: true,
          advice_request: response.quote_request,
          message: 'Demande de conseil transmise avec succès ! Nous vous contacterons rapidement.'
        };
      }

      return response;

    } catch (error) {
      console.error('❌ Erreur demande conseil:', error);
      throw new Error('Erreur lors de l\'envoi de la demande de conseil. Veuillez réessayer.');
    }
  }

  /**
   * Extraire les IDs des produits depuis l'objet complexe
   */
  extractProductIds(selectedProducts) {
    const ids = [];

    // Activités
    if (selectedProducts.activities) {
      selectedProducts.activities.forEach(activity => {
        if (activity.id) ids.push(activity.id);
      });
    }

    // Hébergement
    if (selectedProducts.room && selectedProducts.room.id) {
      ids.push(selectedProducts.room.id);
    }

    // Menus
    if (selectedProducts.menus) {
      selectedProducts.menus.forEach(menu => {
        if (menu.id) ids.push(menu.id);
      });
    }

    return ids;
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