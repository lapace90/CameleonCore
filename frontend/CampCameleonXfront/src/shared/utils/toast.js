export function showToast(message, type = 'success') {
  // Create container if not exists
  let container = document.querySelector('.toast-container')
  if (!container) {
    container = document.createElement('div')
    container.className = 'toast-container'
    document.body.appendChild(container)
  }

  const toast = document.createElement('div')
  toast.className = `toast toast-${type}`
  toast.textContent = message
  container.appendChild(toast)

  // Trigger show animation
  requestAnimationFrame(() => {
    toast.classList.add('show')
  })

  // Auto remove after 3s
  setTimeout(() => {
    toast.classList.remove('show')
    setTimeout(() => toast.remove(), 300)
  }, 3000)
}