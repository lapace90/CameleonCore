
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

export function selectImage() {
  this.$refs.imageInput.click()
}

export function changeImage() {
  this.$refs.imageInput.click()
}

export function removeImage() {
  this.form.image = null
  this.imagePreview = null
  if (this.$refs.imageInput) {
    this.$refs.imageInput.value = ''
  }
}

export function handleImageUpload(event) {
  const file = event.target.files[0]
  if (!file) return

  if (file.size > 5 * 1024 * 1024) {
    this.error = 'Fichier trop volumineux (max 5MB)'
    return
  }

  const reader = new FileReader()
  reader.onload = (e) => {
    this.imagePreview = e.target.result
  }
  reader.readAsDataURL(file)

  this.form.imageFile = file
}

export function handleImageError(event) {
  event.target.src = getPlaceholderImage()
  event.target.onerror = null
}