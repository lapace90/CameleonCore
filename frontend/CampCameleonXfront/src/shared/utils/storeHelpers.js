// ===========================
// STORE HELPERS - CampCameleonX
// Helpers communs pour les stores Pinia
// ===========================

import { isCacheValid, createCacheKey } from './commonUtils'

// ===== 📨 GESTION DES MESSAGES =====
/**
 * Mixin pour la gestion des messages dans les stores
 * @returns {object} Actions de gestion des messages
 */
export function createMessageActions() {
  return {
    /**
     * Définit un message de succès
     * @param {string} message - Message de succès
     * @param {number} duration - Durée d'affichage en ms (défaut: 5000)
     */
    setSuccess(message, duration = 5000) {
      this.successMessage = message
      this.error = null
      
      if (duration > 0) {
        setTimeout(() => {
          this.successMessage = null
        }, duration)
      }
    },

    /**
     * Définit un message d'erreur
     * @param {string} message - Message d'erreur
     */
    setError(message) {
      this.error = message
      this.successMessage = null
    },

    /**
     * Efface tous les messages
     */
    clearMessages() {
      this.error = null
      this.successMessage = null
    },

    /**
     * Gère une erreur API standardisée
     * @param {Error|object} error - Erreur à traiter
     * @param {string} defaultMessage - Message par défaut
     */
    handleApiError(error, defaultMessage = 'Une erreur est survenue') {
      const message = error?.response?.data?.message || 
                     error?.message || 
                     defaultMessage
      this.setError(message)
      console.error('Erreur API:', error)
    }
  }
}

// ===== ⏰ GESTION DU CACHE =====
/**
 * Mixin pour la gestion du cache dans les stores
 * @param {number} defaultTTL - TTL par défaut en ms
 * @returns {object} Actions de gestion du cache
 */
export function createCacheActions(defaultTTL = 5 * 60 * 1000) {
  return {
    /**
     * Vérifie si des données en cache sont valides
     * @param {string} cacheKey - Clé de cache
     * @param {number} ttl - TTL personnalisé
     * @returns {boolean} Validité du cache
     */
    isCacheValid(cacheKey, ttl = defaultTTL) {
      const lastFetch = this.lastFetch?.[cacheKey]
      return isCacheValid(lastFetch, ttl)
    },

    /**
     * Met à jour le timestamp de cache
     * @param {string} cacheKey - Clé de cache
     */
    updateCacheTimestamp(cacheKey) {
      if (!this.lastFetch) this.lastFetch = {}
      this.lastFetch[cacheKey] = Date.now()
    },

    /**
     * Invalide un cache spécifique
     * @param {string} cacheKey - Clé de cache
     */
    invalidateCache(cacheKey) {
      if (this.lastFetch && this.lastFetch[cacheKey]) {
        delete this.lastFetch[cacheKey]
      }
    },

    /**
     * Invalide tous les caches
     */
    invalidateAllCache() {
      this.lastFetch = {}
    }
  }
}

// ===== GESTION DES REQUÊTES =====
/**
 * Mixin pour éviter les requêtes en doublon
 * @returns {object} Actions de gestion des requêtes
 */
export function createRequestActions() {
  return {
    /**
     * Vérifie si une requête est en cours
     * @param {string} requestKey - Clé de la requête
     * @returns {boolean} État de la requête
     */
    isRequestInFlight(requestKey) {
      return this._inflightRequests?.has(requestKey) || false
    },

    /**
     * Marque une requête comme en cours
     * @param {string} requestKey - Clé de la requête
     * @param {Promise} promise - Promise de la requête
     * @returns {Promise} Promise originale
     */
    setRequestInFlight(requestKey, promise) {
      if (!this._inflightRequests) {
        this._inflightRequests = new Map()
      }
      
      this._inflightRequests.set(requestKey, promise)
      
      // Nettoyer automatiquement à la fin
      promise.finally(() => {
        this._inflightRequests?.delete(requestKey)
      })
      
      return promise
    },

    /**
     * Récupère une requête en cours ou en lance une nouvelle
     * @param {string} requestKey - Clé de la requête
     * @param {function} requestFn - Fonction qui lance la requête
     * @returns {Promise} Promise de la requête
     */
    async getOrCreateRequest(requestKey, requestFn) {
      // Si requête en cours, la retourner
      if (this.isRequestInFlight(requestKey)) {
        return this._inflightRequests.get(requestKey)
      }
      
      // Sinon, créer nouvelle requête
      const promise = requestFn()
      return this.setRequestInFlight(requestKey, promise)
    }
  }
}

// ===== 📊 GESTION DE L'ÉTAT UI =====
/**
 * Mixin pour les états de chargement standardisés
 * @returns {object} Actions de gestion de l'état UI
 */
export function createLoadingActions() {
  return {
    /**
     * Démarre un état de chargement
     * @param {string} loadingKey - Clé du chargement (optionnel)
     */
    startLoading(loadingKey = 'default') {
      if (loadingKey === 'default') {
        this.loading = true
      } else {
        if (!this.loadingStates) this.loadingStates = {}
        this.loadingStates[loadingKey] = true
      }
      this.error = null
    },

    /**
     * Arrête un état de chargement
     * @param {string} loadingKey - Clé du chargement (optionnel)
     */
    stopLoading(loadingKey = 'default') {
      if (loadingKey === 'default') {
        this.loading = false
      } else {
        if (this.loadingStates) {
          this.loadingStates[loadingKey] = false
        }
      }
    },

    /**
     * Vérifie si un chargement est en cours
     * @param {string} loadingKey - Clé du chargement (optionnel)
     * @returns {boolean} État du chargement
     */
    isLoading(loadingKey = 'default') {
      if (loadingKey === 'default') {
        return this.loading || false
      }
      return this.loadingStates?.[loadingKey] || false
    },

    /**
     * Exécute une action avec gestion automatique du loading
     * @param {function} action - Action à exécuter
     * @param {string} loadingKey - Clé du chargement
     * @returns {Promise} Résultat de l'action
     */
    async withLoading(action, loadingKey = 'default') {
      this.startLoading(loadingKey)
      try {
        return await action()
      } finally {
        this.stopLoading(loadingKey)
      }
    }
  }
}

// ===== 🔍 GESTION DES DONNÉES =====
/**
 * Mixin pour la normalisation et indexation des données
 * @param {function} normalizer - Fonction de normalisation
 * @returns {object} Actions de gestion des données
 */
export function createDataActions(normalizer = (item) => item) {
  return {
    /**
     * Met à jour une collection avec indexation automatique
     * @param {Array} items - Nouveaux éléments
     * @param {string} itemsKey - Clé pour stocker les éléments (défaut: 'items')
     * @param {string} indexKey - Clé pour l'index (défaut: 'mapById')
     */
    updateCollection(items, itemsKey = 'items', indexKey = 'mapById') {
      const normalizedItems = items.map(normalizer)
      
      this[itemsKey] = normalizedItems
      this[indexKey] = Object.fromEntries(
        normalizedItems.map(item => [item.id, item])
      )
    },

    /**
     * Ajoute ou met à jour un élément dans la collection
     * @param {object} item - Élément à ajouter/mettre à jour
     * @param {string} itemsKey - Clé de la collection
     * @param {string} indexKey - Clé de l'index
     */
    upsertItem(item, itemsKey = 'items', indexKey = 'mapById') {
      const normalizedItem = normalizer(item)
      
      // Mettre à jour ou ajouter dans le tableau
      const existingIndex = this[itemsKey].findIndex(i => i.id === normalizedItem.id)
      if (existingIndex >= 0) {
        this[itemsKey][existingIndex] = normalizedItem
      } else {
        this[itemsKey].push(normalizedItem)
      }
      
      // Mettre à jour l'index
      this[indexKey][normalizedItem.id] = normalizedItem
    },

    /**
     * Supprime un élément de la collection
     * @param {number|string} id - ID de l'élément à supprimer
     * @param {string} itemsKey - Clé de la collection
     * @param {string} indexKey - Clé de l'index
     */
    removeItem(id, itemsKey = 'items', indexKey = 'mapById') {
      this[itemsKey] = this[itemsKey].filter(item => item.id !== id)
      delete this[indexKey][id]
    },

    /**
     * Trouve un élément par ID
     * @param {number|string} id - ID recherché
     * @param {string} indexKey - Clé de l'index
     * @returns {object|null} Élément trouvé
     */
    findById(id, indexKey = 'mapById') {
      return this[indexKey]?.[id] || null
    }
  }
}

// ===== 📄 GESTION DE LA PAGINATION =====
/**
 * Mixin pour la gestion de la pagination
 * @returns {object} Actions de pagination
 */
export function createPaginationActions() {
  return {
    /**
     * Met à jour les métadonnées de pagination
     * @param {object} meta - Métadonnées de pagination
     */
    updatePagination(meta) {
      this.currentPage = meta.currentPage || meta.current_page || 1
      this.lastPage = meta.lastPage || meta.last_page || 1
      this.totalItems = meta.totalItems || meta.total || 0
      this.perPage = meta.perPage || meta.per_page || 10
    },

    /**
     * Vérifie s'il y a une page suivante
     * @returns {boolean} Présence d'une page suivante
     */
    hasNextPage() {
      return this.currentPage < this.lastPage
    },

    /**
     * Vérifie s'il y a une page précédente
     * @returns {boolean} Présence d'une page précédente
     */
    hasPreviousPage() {
      return this.currentPage > 1
    },

    /**
     * Réinitialise la pagination
     */
    resetPagination() {
      this.currentPage = 1
      this.lastPage = 1
      this.totalItems = 0
    }
  }
}

// ===== 🛠️ HELPER FACTORY =====
/**
 * Crée un store de base avec tous les mixins communs
 * @param {object} options - Options de configuration
 * @returns {object} Actions combinées
 */
export function createBaseStoreActions(options = {}) {
  const {
    ttl = 5 * 60 * 1000,
    normalizer = (item) => item,
    enableMessages = true,
    enableCache = true,
    enableRequests = true,
    enableLoading = true,
    enableData = true,
    enablePagination = false
  } = options

  let actions = {}

  if (enableMessages) {
    Object.assign(actions, createMessageActions())
  }

  if (enableCache) {
    Object.assign(actions, createCacheActions(ttl))
  }

  if (enableRequests) {
    Object.assign(actions, createRequestActions())
  }

  if (enableLoading) {
    Object.assign(actions, createLoadingActions())
  }

  if (enableData) {
    Object.assign(actions, createDataActions(normalizer))
  }

  if (enablePagination) {
    Object.assign(actions, createPaginationActions())
  }

  return actions
}

// ===== 📦 ÉTAT DE BASE =====
/**
 * Crée un état de base pour un store
 * @param {object} options - Options de configuration
 * @returns {function} Fonction d'état Pinia
 */
export function createBaseStoreState(options = {}) {
  const {
    enableMessages = true,
    enableCache = true,
    enableRequests = true,
    enableLoading = true,
    enableData = true,
    enablePagination = false,
    customState = {}
  } = options

  return () => {
    const state = { ...customState }

    if (enableMessages) {
      state.error = null
      state.successMessage = null
    }

    if (enableCache) {
      state.lastFetch = {}
    }

    if (enableRequests) {
      state._inflightRequests = new Map()
    }

    if (enableLoading) {
      state.loading = false
      state.loadingStates = {}
    }

    if (enableData) {
      state.items = []
      state.mapById = {}
    }

    if (enablePagination) {
      state.currentPage = 1
      state.lastPage = 1
      state.totalItems = 0
      state.perPage = 10
    }

    return state
  }
}