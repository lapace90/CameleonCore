import { describe, it, expect, vi, beforeEach } from 'vitest'

// Pour ce test, on unmock le store pour tester la vraie logique
vi.unmock('@/shared/stores/instance')

import { setActivePinia, createPinia } from 'pinia'
import { useInstanceStore } from '@/shared/stores/instance'

describe('useInstanceStore', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
  })

  describe('état initial', () => {
    it('should start with null config', () => {
      const store = useInstanceStore()
      expect(store.config).toBeNull()
      expect(store.isReady).toBe(false)
    })

    it('should have default getters', () => {
      const store = useInstanceStore()
      expect(store.name).toBe('')
      expect(store.type).toBe('hotel')
      expect(store.country).toBe('FR')
      expect(store.modules).toEqual({})
      expect(store.productables).toEqual([])
      expect(store.features).toEqual({})
    })
  })

  describe('helpers', () => {
    it('hasModule returns false when config not loaded', () => {
      const store = useInstanceStore()
      expect(store.hasModule('booking')).toBe(false)
    })

    it('hasProductable returns false when config not loaded', () => {
      const store = useInstanceStore()
      expect(store.hasProductable('room')).toBe(false)
    })

    it('hasFeature returns false when config not loaded', () => {
      const store = useInstanceStore()
      expect(store.hasFeature('deposit_payment')).toBe(false)
    })
  })

  describe('après chargement', () => {
    it('should populate state from API response', async () => {
      const store = useInstanceStore()

      // Simuler le chargement en définissant config directement
      store.config = {
        name: 'Test Traiteur',
        type: 'traiteur',
        country: 'FR',
        modules: { booking: true, invoicing: true, calendar: true, rbac: false },
        productables: ['menu', 'dish'],
        features: { deposit_payment: true, deposit_percentage: 30, guest_count: true, checkin_checkout: false },
        contact: { phone: '0612345678', email: 'test@test.fr' },
      }

      expect(store.isReady).toBe(true)
      expect(store.name).toBe('Test Traiteur')
      expect(store.type).toBe('traiteur')
    })

    it('hasModule works after load', async () => {
      const store = useInstanceStore()
      store.config = {
        modules: { booking: true, rbac: false },
        productables: [],
        features: {},
      }

      expect(store.hasModule('booking')).toBe(true)
      expect(store.hasModule('rbac')).toBe(false)
      expect(store.hasModule('nonexistent')).toBe(false)
    })

    it('hasProductable works after load', async () => {
      const store = useInstanceStore()
      store.config = {
        modules: {},
        productables: ['menu', 'dish'],
        features: {},
      }

      expect(store.hasProductable('menu')).toBe(true)
      expect(store.hasProductable('dish')).toBe(true)
      expect(store.hasProductable('room')).toBe(false)
    })

    it('hasFeature works after load', async () => {
      const store = useInstanceStore()
      store.config = {
        modules: {},
        productables: [],
        features: { deposit_payment: true, checkin_checkout: false },
      }

      expect(store.hasFeature('deposit_payment')).toBe(true)
      expect(store.hasFeature('checkin_checkout')).toBe(false)
    })
  })

  describe('scénarios instance', () => {
    it('traiteur config has no room productable', () => {
      const store = useInstanceStore()
      store.config = {
        type: 'traiteur',
        modules: { booking: true, rbac: false, quote_builder: false },
        productables: ['menu', 'dish'],
        features: { guest_count: true, checkin_checkout: false },
      }

      expect(store.hasProductable('room')).toBe(false)
      expect(store.hasProductable('menu')).toBe(true)
      expect(store.hasModule('rbac')).toBe(false)
      expect(store.hasFeature('checkin_checkout')).toBe(false)
    })

    it('hotel config has all productables', () => {
      const store = useInstanceStore()
      store.config = {
        type: 'hotel',
        modules: { booking: true, rbac: true, quote_builder: true },
        productables: ['room', 'activity', 'menu', 'dish', 'ingredient'],
        features: { guest_count: true, checkin_checkout: true },
      }

      expect(store.hasProductable('room')).toBe(true)
      expect(store.hasProductable('activity')).toBe(true)
      expect(store.hasModule('rbac')).toBe(true)
      expect(store.hasFeature('checkin_checkout')).toBe(true)
    })

    it('load is idempotent', async () => {
      const store = useInstanceStore()
      store.config = { modules: {}, productables: [], features: {} }

      // Appeler load une deuxième fois ne devrait pas recharger
      await store.load()
      expect(store.config).not.toBeNull()
    })
  })
})
