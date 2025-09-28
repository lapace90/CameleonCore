// tests/composables/useQuotePricing.test.js
import { describe, it, expect, beforeEach } from 'vitest'
import { computeQuoteTotal } from '@/shared/composables/useQuotePricing'

describe('useQuotePricing', () => {
  let mockCatalog
  let mockDates
  let mockSelected

  beforeEach(() => {
    mockCatalog = {
      activities: [
        { id: 1, name: 'Randonnée Chamelière', price: 80 },
        { id: 2, name: 'Visite Oasis', price: 50 }
      ],
      rooms: [  // ✅ "rooms" avec s
        { id: 4, name: 'Tente Berbère Luxe', price: 120 }
      ],
      menus: [
        { id: 6, name: 'Menu Traditionnel', price: 40 }
      ]
    }

    mockDates = {
      start: '2024-03-15',
      endExclusive: '2024-03-18',
      guests: 2
    }

    mockSelected = {
      activities: [1, 2],
      room: [4],  // ✅ Array au lieu de nombre
      menus: [6]
    }
  })

  describe('Calculs de base', () => {
    it('should compute nights correctly', () => {
      const result = computeQuoteTotal({
        selected: mockSelected,
        catalog: mockCatalog,
        dates: mockDates,
        overrides: {}
      })

      expect(result.nights).toBe(3) // 15-18 mars = 3 nuits
    })

    it('should calculate total without overrides', () => {
      const result = computeQuoteTotal({
        selected: mockSelected,
        catalog: mockCatalog,
        dates: mockDates,
        overrides: {}
      })

      expect(result.total).toBeGreaterThan(0)
      expect(result.lines).toHaveLength(4) // room + 2 activities + menu
    })

    it('should generate correct line items', () => {
      const result = computeQuoteTotal({
        selected: mockSelected,
        catalog: mockCatalog,
        dates: mockDates,
        overrides: {}
      })

      expect(result.lines).toHaveLength(4)
      
      // Vérifier ligne hébergement
      const roomLine = result.lines.find(l => l.type === 'room')
      expect(roomLine).toBeDefined()
      expect(roomLine.id).toBe(4)
      expect(roomLine.unitPrice).toBe(120)
    })
  })

  describe('Gestion des quantités avec overrides', () => {
    it('should apply activity quantity overrides', () => {
      const overrides = {
        activity: { 1: 3 }
      }

      const result = computeQuoteTotal({
        selected: mockSelected,
        catalog: mockCatalog,
        dates: mockDates,
        overrides
      })

      const activityLine = result.lines.find(l => l.type === 'activity' && l.id === 1)
      expect(activityLine.qty).toBe(3)
    })
  })

  describe('Gestion des cas limites', () => {
    it('should handle empty selections', () => {
      const result = computeQuoteTotal({
        selected: { activities: [], room: null, menus: [] },
        catalog: mockCatalog,
        dates: mockDates,
        overrides: {}
      })

      expect(result.total).toBe(0)
      expect(result.lines).toHaveLength(0)
    })

    it('should handle missing catalog items', () => {
      const incompleteSelected = {
        activities: [999], // ID inexistant
        room: 4,
        menus: [6]
      }

      const result = computeQuoteTotal({
        selected: incompleteSelected,
        catalog: mockCatalog,
        dates: mockDates,
        overrides: {}
      })

      // Ne devrait inclure que les éléments valides
      expect(result.lines.find(l => l.id === 999)).toBeUndefined()
      expect(result.lines.find(l => l.id === 4)).toBeDefined()
    })
  })
})