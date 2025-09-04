export function formatPrice(price) {
  return new Intl.NumberFormat('fr-FR', {
    style: 'currency',
    currency: 'EUR'
  }).format(parseFloat(price) || 0)
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
  return labels[field] || field.charAt(0).toUpperCase() + field.slice(1)
}

export function getPlaceholderImage() {
  const svg = `
    <svg width="300" height="200" xmlns="http://www.w3.org/2000/svg">
      <rect width="300" height="200" fill="#f3f4f6"/>
      <text x="150" y="100" text-anchor="middle" dy=".3em" font-family="Arial, sans-serif" font-size="14" fill="#9ca3af">
        Aperçu
      </text>
    </svg>
  `
  return 'data:image/svg+xml;base64,' + btoa(svg)
}

export function getValidImageUrl(imageUrl) {
  if (!imageUrl) return getPlaceholderImage()

  try {
    new URL(imageUrl)
    return imageUrl
  } catch (error) {
    return getPlaceholderImage()
  }
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
  if (key.includes('revenue')) {
    return formatPrice(value)
  }
  return value
}