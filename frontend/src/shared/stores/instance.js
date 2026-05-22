import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import httpClient from '@/services/httpClient'

export const useInstanceStore = defineStore('instance', () => {
  // ===========================
  // STATE
  // ===========================
  const config = ref(null)
  const loading = ref(false)
  const error = ref(null)

  // ===========================
  // GETTERS
  // ===========================
  const name = computed(() => config.value?.name ?? '')
  const type = computed(() => config.value?.type ?? 'hotel')
  const logo = computed(() => config.value?.logo ?? '/images/logo.png')
  const country = computed(() => config.value?.country ?? 'FR')
  const modules = computed(() => config.value?.modules ?? {})
  const productables = computed(() => config.value?.productables ?? [])
  const features = computed(() => config.value?.features ?? {})
  const contact = computed(() => config.value?.contact ?? {})

  // Helpers modules
  const hasModule = (key) => modules.value[key] === true
  const hasFeature = (key) => features.value[key] === true
  const hasProductable = (key) => productables.value.includes(key)

  const isReady = computed(() => config.value !== null)

  // ===========================
  // ACTIONS
  // ===========================
  async function load() {
    if (config.value) return // déjà chargé

    loading.value = true
    error.value = null

    try {
      const data = await httpClient.get('/config/public')
      config.value = data
    } catch (e) {
      console.error('❌ Instance config load failed:', e)
      error.value = 'Impossible de charger la configuration'
      throw e
    } finally {
      loading.value = false
    }
  }

  return {
    // state
    config,
    loading,
    error,

    // getters
    name,
    type,
    logo,
    country,
    modules,
    productables,
    features,
    contact,
    isReady,

    // helpers
    hasModule,
    hasFeature,
    hasProductable,

    // actions
    load,
  }
})
