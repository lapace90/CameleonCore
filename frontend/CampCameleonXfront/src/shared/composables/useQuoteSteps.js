import { ref, computed } from 'vue'

export function useQuoteSteps() {
  const currentStep = ref(1)
  
  const nextStep = () => {
    if (currentStep.value < 5) {
      currentStep.value++
    }
  }
  
  const previousStep = () => {
    if (currentStep.value > 1) {
      currentStep.value--
    }
  }
  
  // Validation basique pour chaque étape
  const canProceed = computed(() => {
    // TODO: Implémenter la vraie logique de validation
    return true
  })
  
  return {
    currentStep,
    nextStep,
    previousStep,
    canProceed
  }
}