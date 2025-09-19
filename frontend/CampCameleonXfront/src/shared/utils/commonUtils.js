// ===========================
// COMMON UTILS - CampCameleonX
// Méthodes utilitaires factorisées pour réduire la duplication
// ===========================

// ===== 💰 FORMATAGE PRIX =====
/**
 * Formate un montant en euros
 * @param {number|string} amount - Montant à formater
 * @param {string} locale - Locale (défaut: 'fr-FR')
 * @returns {string} Prix formaté (ex: "42,50 €")
 */
export function formatPrice(amount, locale = 'fr-FR') {
  const numAmount = safeNumber(amount, 0)
  return new Intl.NumberFormat(locale, {
    style: 'currency',
    currency: 'EUR'
  }).format(numAmount)
}

// ===== 📅 FORMATAGE DATES =====
/**
 * Formate une date en français
 * @param {string|Date} dateString - Date à formater
 * @param {object} options - Options de formatage
 * @returns {string} Date formatée
 */
export function formatDate(dateString, options = {}) {
  if (!dateString) return ''
  
  try {
    const date = new Date(dateString)
    const defaultOptions = {
      day: 'numeric',
      month: 'long',
      year: 'numeric',
      ...options
    }
    return date.toLocaleDateString('fr-FR', defaultOptions)
  } catch (error) {
    console.warn('Erreur formatage date:', dateString)
    return ''
  }
}

/**
 * Formate une date avec heure
 * @param {string|Date} dateString - Date à formater
 * @returns {string} Date et heure formatées
 */
export function formatDateTime(dateString) {
  return formatDate(dateString, {
    day: 'numeric',
    month: 'long',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

/**
 * Convertit une date ISO en format input[type="date"]
 * @param {string} isoString - Date ISO
 * @returns {string} Format YYYY-MM-DD
 */
export function toLocalDateInput(isoString) {
  if (!isoString) return ''
  try {
    const date = new Date(isoString)
    const year = date.getFullYear()
    const month = String(date.getMonth() + 1).padStart(2, '0')
    const day = String(date.getDate()).padStart(2, '0')
    return `${year}-${month}-${day}`
  } catch (error) {
    console.warn('Erreur conversion date:', isoString)
    return ''
  }
}

/**
 * Convertit endExclusive en date inclusive pour affichage
 * @param {string} endExclusiveYmd - Date exclusive YYYY-MM-DD
 * @returns {string} Date inclusive YYYY-MM-DD
 */
export function displayEndInclusive(endExclusiveYmd) {
  if (!endExclusiveYmd) return ''
  try {
    const date = new Date(endExclusiveYmd)
    date.setDate(date.getDate() - 1)
    return toLocalDateInput(date.toISOString())
  } catch (error) {
    console.warn('Erreur conversion end exclusive:', endExclusiveYmd)
    return ''
  }
}

// ===== 🔢 SÉCURITÉ NUMÉRIQUE =====
/**
 * Sécurise un nombre contre NaN/Infinity
 * @param {any} value - Valeur à sécuriser
 * @param {number} defaultValue - Valeur par défaut
 * @returns {number} Nombre sécurisé
 */
export function safeNumber(value, defaultValue = 0) {
  const num = Number(value)
  return isNaN(num) || !isFinite(num) ? defaultValue : num
}

/**
 * Calcule une moyenne sécurisée de prix
 * @param {Array} items - Tableau d'objets avec propriété price
 * @param {string} priceField - Nom du champ prix (défaut: 'price')
 * @returns {number} Moyenne sécurisée
 */
export function calculateAveragePrice(items = [], priceField = 'price') {
  if (!Array.isArray(items) || items.length === 0) return 0
  
  const validItems = items.filter(item => {
    const price = safeNumber(item[priceField])
    return price > 0
  })
  
  if (validItems.length === 0) return 0
  
  const total = validItems.reduce((sum, item) => {
    return sum + safeNumber(item[priceField])
  }, 0)
  
  return safeNumber(total / validItems.length)
}

// ===== ✅ VALIDATION =====
/**
 * Valide une adresse email
 * @param {string} email - Email à valider
 * @returns {boolean} Validité
 */
export function isValidEmail(email) {
  if (!email || typeof email !== 'string') return false
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
  return emailRegex.test(email.trim())
}

/**
 * Valide un numéro de téléphone français
 * @param {string} phone - Téléphone à valider
 * @returns {boolean} Validité
 */
export function isValidPhone(phone) {
  if (!phone || typeof phone !== 'string') return false
  const phoneRegex = /^(?:(?:\+|00)33|0)\s*[1-9](?:[\s.-]*\d{2}){4}$/
  return phoneRegex.test(phone.replace(/\s/g, ''))
}

/**
 * Vérifie si un formulaire est valide
 * @param {object} form - Données du formulaire
 * @param {object} rules - Règles de validation
 * @returns {object} { isValid, errors }
 */
export function validateForm(form, rules) {
  const errors = {}
  
  Object.keys(rules).forEach(field => {
    const rule = rules[field]
    const value = form[field]
    
    // Required
    if (rule.required && (!value || (typeof value === 'string' && !value.trim()))) {
      errors[field] = rule.message || `${field} est requis`
      return
    }
    
    // Email
    if (rule.type === 'email' && value && !isValidEmail(value)) {
      errors[field] = rule.message || 'Email invalide'
      return
    }
    
    // Phone
    if (rule.type === 'phone' && value && !isValidPhone(value)) {
      errors[field] = rule.message || 'Téléphone invalide'
      return
    }
    
    // Min/Max length
    if (rule.minLength && value && value.length < rule.minLength) {
      errors[field] = rule.message || `Minimum ${rule.minLength} caractères`
      return
    }
    
    if (rule.maxLength && value && value.length > rule.maxLength) {
      errors[field] = rule.message || `Maximum ${rule.maxLength} caractères`
      return
    }
  })
  
  return {
    isValid: Object.keys(errors).length === 0,
    errors
  }
}

// ===== 🎨 CLASSES CSS DYNAMIQUES =====
/**
 * Génère des classes CSS pour un statut
 * @param {string} status - Statut (draft, sent, validated, etc.)
 * @param {string} prefix - Préfixe des classes (défaut: 'status')
 * @returns {object} Classes CSS
 */
export function getStatusClasses(status, prefix = 'status') {
  const classes = {}
  classes[`${prefix}-${status}`] = true
  
  // Aliases communs
  if (status === 'validated' || status === 'confirmed') {
    classes[`${prefix}-success`] = true
  }
  if (status === 'draft' || status === 'pending') {
    classes[`${prefix}-warning`] = true
  }
  if (status === 'cancelled' || status === 'rejected') {
    classes[`${prefix}-danger`] = true
  }
  
  return classes
}

/**
 * Génère le texte d'affichage pour un statut
 * @param {string} status - Statut
 * @param {object} customLabels - Labels personnalisés
 * @returns {string} Texte affiché
 */
export function getStatusText(status, customLabels = {}) {
  const defaultLabels = {
    draft: 'Brouillon',
    sent: 'Envoyé',
    validated: 'Validé',
    confirmed: 'Confirmé',
    pending: 'En attente',
    cancelled: 'Annulé',
    rejected: 'Rejeté',
    active: 'Actif',
    inactive: 'Inactif'
  }
  
  const labels = { ...defaultLabels, ...customLabels }
  return labels[status] || status
}

// ===== 📊 MANIPULATION DE DONNÉES =====
/**
 * Normalise un objet avec des alias de propriétés
 * @param {object} obj - Objet à normaliser
 * @param {object} aliases - Mapping des alias { newProp: ['oldProp1', 'oldProp2'] }
 * @returns {object} Objet normalisé
 */
export function normalizeObject(obj = {}, aliases = {}) {
  const normalized = { ...obj }
  
  Object.keys(aliases).forEach(newProp => {
    const oldProps = Array.isArray(aliases[newProp]) ? aliases[newProp] : [aliases[newProp]]
    
    for (const oldProp of oldProps) {
      if (obj[oldProp] !== undefined) {
        normalized[newProp] = obj[oldProp]
        break
      }
    }
  })
  
  return normalized
}

/**
 * Groupe un tableau par une propriété
 * @param {Array} array - Tableau à grouper
 * @param {string|function} key - Propriété ou fonction de groupage
 * @returns {object} Objets groupés
 */
export function groupBy(array, key) {
  if (!Array.isArray(array)) return {}
  
  return array.reduce((groups, item) => {
    const groupKey = typeof key === 'function' ? key(item) : item[key]
    if (!groups[groupKey]) {
      groups[groupKey] = []
    }
    groups[groupKey].push(item)
    return groups
  }, {})
}

/**
 * Déduplique un tableau par une propriété
 * @param {Array} array - Tableau à dédupliquer
 * @param {string} key - Propriété unique
 * @returns {Array} Tableau dédupliqué
 */
export function uniqueBy(array, key) {
  if (!Array.isArray(array)) return []
  
  const seen = new Set()
  return array.filter(item => {
    const keyValue = item[key]
    if (seen.has(keyValue)) return false
    seen.add(keyValue)
    return true
  })
}

// ===== ⏰ GESTION DU CACHE =====
/**
 * Vérifie si des données sont encore valides en cache
 * @param {number|null} lastFetch - Timestamp du dernier fetch
 * @param {number} ttl - Durée de vie en ms (défaut: 5 min)
 * @returns {boolean} Validité du cache
 */
export function isCacheValid(lastFetch, ttl = 5 * 60 * 1000) {
  if (!lastFetch) return false
  return (Date.now() - lastFetch) < ttl
}

/**
 * Crée une clé de cache composée
 * @param {...any} parts - Parties de la clé
 * @returns {string} Clé de cache
 */
export function createCacheKey(...parts) {
  return parts
    .filter(part => part !== null && part !== undefined)
    .map(part => String(part))
    .join(':')
}

// ===== 🔤 TEXTE ET STRINGS =====
/**
 * Met en forme un nom complet
 * @param {string} firstName - Prénom
 * @param {string} lastName - Nom
 * @returns {string} Nom complet formaté
 */
export function formatFullName(firstName = '', lastName = '') {
  const first = (firstName || '').trim()
  const last = (lastName || '').trim()
  
  if (!first && !last) return 'Utilisateur'
  if (!first) return last
  if (!last) return first
  
  return `${first} ${last}`
}

/**
 * Génère un pluriel intelligent
 * @param {number} count - Nombre
 * @param {string} singular - Forme singulier
 * @param {string} plural - Forme pluriel (optionnel)
 * @returns {string} Forme correcte
 */
export function pluralize(count, singular, plural) {
  if (count <= 1) return singular
  return plural || `${singular}s`
}

/**
 * Tronque un texte intelligemment
 * @param {string} text - Texte à tronquer
 * @param {number} maxLength - Longueur max
 * @param {string} suffix - Suffixe (défaut: '...')
 * @returns {string} Texte tronqué
 */
export function truncateText(text, maxLength, suffix = '...') {
  if (!text || text.length <= maxLength) return text
  return text.substring(0, maxLength - suffix.length) + suffix
}

// ===== 🚀 EXPORT DES UTILS EXISTANTS =====
// Réexporter les utils existants pour centraliser les imports
export { formatPrice as formatPriceFromUtils } from '@/shared/utils/ProductUtils'
export { showToast } from '@/shared/utils/toast'