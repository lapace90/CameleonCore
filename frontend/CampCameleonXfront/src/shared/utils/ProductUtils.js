// utils/display.js

const PLACEHOLDER_URL = 'https://via.placeholder.com/600x400?text=Image+indisponible'

function isDataUrl(s) { return typeof s === 'string' && s.startsWith('data:') }
function isBlobUrl(s) { return typeof s === 'string' && s.startsWith('blob:') }
function isHttpUrl(s) {
  try { return /^https?:\/\//i.test(new URL(s).href) } catch { return false }
}
function isRelativePath(s) {
  // accepte /images/foo.png ou images/foo.png (pas de schéma, pas de data:)
  return typeof s === 'string' && !/^[a-z]+:\/\//i.test(s) && !s.startsWith('data:')
}

export function formatPrice(price) {
  // supporte nombres, strings "12,50" ou "12.5"
  const n = typeof price === 'string'
    ? parseFloat(price.replace(',', '.'))
    : (typeof price === 'number' ? price : NaN)

  const value = Number.isFinite(n) ? n : 0
  return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(value)
}

export function getFieldLabel(field) {
  const labels = {
    guide: 'Guide',
    duration: 'Durée',
    meeting_point: 'Point de rendez-vous',
    max_people: 'Capacité maximum',
    difficulty_level: 'Niveau de difficulté',
    capacity: 'Capacité'
  }
  return labels[field] || (field ? field.charAt(0).toUpperCase() + field.slice(1) : '')
}

export function getValidImageUrl(imageUrl) {
  if (!imageUrl) return PLACEHOLDER_URL
  if (isDataUrl(imageUrl)) return PLACEHOLDER_URL // on refuse base64
  if (isBlobUrl(imageUrl)) return imageUrl        // ok pour preview locale
  if (isHttpUrl(imageUrl)) return imageUrl        // http(s) absolu
  if (isRelativePath(imageUrl)) return imageUrl   // chemin relatif (/, ./, images/…)
  return PLACEHOLDER_URL
}

export function getStatIcon(key) {
  const icons = {
    views: 'fas fa-eye',
    reservations_count: 'fas fa-shopping-cart',
    total_revenue: 'fas fa-euro-sign',
    monthly_revenue: 'fas fa-chart-line',
    average_rating: 'fas fa-star'
  }
  return icons[key] || 'fas fa-info'
}

export function getStatLabel(key) {
  const labels = {
    views: 'Vues',
    reservations_count: 'Réservations',
    total_revenue: 'CA total',
    monthly_revenue: 'CA mensuel',
    average_rating: 'Note moyenne'
  }
  return labels[key] || key
}

export function formatStatValue(value, key) {
  if (key && key.includes('revenue')) return formatPrice(value)
  return value
}
