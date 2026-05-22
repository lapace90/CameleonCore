import { ref, onMounted } from 'vue'

const COOKIE_CONSENT_KEY = 'campx_cookie_consent'
const COOKIE_EXPIRY_DAYS = 365

export function useCookieConsent() {
  const showBanner = ref(false)
  const consent = ref(null)

  // Récupérer le consentement existant
  const getConsent = () => {
    const saved = localStorage.getItem(COOKIE_CONSENT_KEY)
    return saved ? JSON.parse(saved) : null
  }

  // Sauvegarder le consentement
  const saveConsent = (value) => {
    const consentData = {
      value,
      timestamp: new Date().toISOString(),
      expires: new Date(Date.now() + COOKIE_EXPIRY_DAYS * 24 * 60 * 60 * 1000).toISOString()
    }
    localStorage.setItem(COOKIE_CONSENT_KEY, JSON.stringify(consentData))
    consent.value = value
    showBanner.value = false
  }

  // Accepter tous les cookies
  const acceptAll = () => {
    saveConsent('all')
  }

  // Accepter uniquement les cookies essentiels
  const acceptEssential = () => {
    saveConsent('essential')
  }

  // Vérifier si le consentement est donné
  const hasConsent = (type = 'essential') => {
    const current = getConsent()
    if (!current) return false
    if (type === 'essential') return true
    return current.value === 'all'
  }

  // Initialisation
  onMounted(() => {
    const existing = getConsent()
    if (!existing) {
      // Afficher la bannière après un court délai pour meilleure UX
      setTimeout(() => {
        showBanner.value = true
      }, 1000)
    } else {
      consent.value = existing.value
    }
  })

  return {
    showBanner,
    consent,
    acceptAll,
    acceptEssential,
    hasConsent
  }
}