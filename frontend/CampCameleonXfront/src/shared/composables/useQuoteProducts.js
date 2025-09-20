import { ref, computed } from 'vue'

export function useQuoteProducts() {
  const allProducts = ref([])
  const loading = ref(false)
  
  const loadProducts = async () => {
    loading.value = true
    try {
      // TODO: Copier la logique exacte de QuoteModal
      const response = await publicApi.getProducts({ per_page: 100 })
      allProducts.value = response.data || []
    } catch (error) {
      console.error('Erreur chargement produits:', error)
    } finally {
      loading.value = false
    }
  }
  
  // Filtres comme dans l'original
  const relevantProducts = computed(() => {
    return allProducts.value.filter(p => {
      const label = p.typeConfig?.label
      return ['Activités', 'Menus', 'Hébergements', 'Rooms'].includes(label)
    })
  })
  
  const availableActivities = computed(() => {
    return relevantProducts.value.filter(p => p.typeConfig?.label === 'Activités')
  })
  
  const availableMenus = computed(() => {
    return relevantProducts.value.filter(p => p.typeConfig?.label === 'Menus')
  })
  
  const availableRooms = computed(() => {
    return relevantProducts.value
      .filter(p => p.typeConfig?.label === 'Hébergements' || p.typeConfig?.label === 'Rooms')
  })
  
  return {
    allProducts,
    loading,
    loadProducts,
    relevantProducts,
    availableActivities,
    availableMenus,
    availableRooms
  }
}