/**
 * Demo Events Helper
 * Émet les events pour le guide démo si activé
 */

function isDemoEnabled() {
  if (typeof window === 'undefined') return false
  const params = new URLSearchParams(window.location.search)
  if (params.get('demo') === 'off') return false
  if (localStorage.getItem('campcameleon-demo-disabled') === 'true') return false
  return true
}

export function emitDemoStep(step) {
  if (isDemoEnabled()) {
    window.dispatchEvent(new CustomEvent('demo:step-change', { detail: { step } }))
  }
}

export function emitDemoModalClose() {
  if (isDemoEnabled()) {
    window.dispatchEvent(new CustomEvent('demo:modal-close'))
  }
}

export function emitDemoValidation() {
  if (isDemoEnabled()) {
    window.dispatchEvent(new CustomEvent('demo:validation'))
  }
}