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
      rooms: [
        { id: 4, name: 'Tente Berbère Luxe', price: 120 }
      ],
      menus: [
        { id: 6, name: 'Menu Traditionnel', price: 40 }
      ]
    }

    //  Structure correcte avec checkin/checkout
    mockDates = {
      checkin: '2024-03-15',
      checkout: '2024-03-18',  // checkout inclusif
      guests: 2
    }

    //  Structure correcte avec activity/menu (singulier)
    mockSelected = {
      activity: [1, 2], 
      room: [4],
      menu: [6]  
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

      // Calcul attendu:
      // Room: 120 * 3 nuits = 360
      // Activity 1: 80 * 2 guests = 160  
      // Activity 2: 50 * 2 guests = 100
      // Menu: 40 * 2 guests = 80
      // Total: 360 + 160 + 100 + 80 = 700
      expect(result.total).toBe(700)
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
      expect(roomLine.unit).toBe(120)  //  "unit" pas "unitPrice"
      expect(roomLine.qty).toBe(3)     // 3 nuits
      expect(roomLine.lineTotal).toBe(360)

      // Vérifier lignes activités
      const activity1Line = result.lines.find(l => l.type === 'activity' && l.id === 1)
      expect(activity1Line).toBeDefined()
      expect(activity1Line.unit).toBe(80)
      expect(activity1Line.qty).toBe(2)  // 2 guests
      expect(activity1Line.lineTotal).toBe(160)

      const activity2Line = result.lines.find(l => l.type === 'activity' && l.id === 2)
      expect(activity2Line).toBeDefined()
      expect(activity2Line.unit).toBe(50)
      expect(activity2Line.qty).toBe(2)  // 2 guests
      expect(activity2Line.lineTotal).toBe(100)

      // Vérifier ligne menu
      const menuLine = result.lines.find(l => l.type === 'menu')
      expect(menuLine).toBeDefined()
      expect(menuLine.id).toBe(6)
      expect(menuLine.unit).toBe(40)
      expect(menuLine.qty).toBe(2)  // 2 guests
      expect(menuLine.lineTotal).toBe(80)
    })
  })

  describe('Gestion des quantités avec overrides', () => {
    it('should apply activity quantity overrides', () => {
      const overrides = {
        activity: { 1: 3 }  // Override activité 1 à 3 unités
      }

      const result = computeQuoteTotal({
        selected: mockSelected,
        catalog: mockCatalog,
        dates: mockDates,
        rules: { capQtyToGuests: false }, overrides
      })

      const activityLine = result.lines.find(l => l.type === 'activity' && l.id === 1)
      expect(activityLine).toBeDefined()
      expect(activityLine.qty).toBe(3)  // Override appliqué
      expect(activityLine.lineTotal).toBe(240)  // 80 * 3 = 240

      // Vérifier que l'activité 2 n'est pas affectée
      const activity2Line = result.lines.find(l => l.type === 'activity' && l.id === 2)
      expect(activity2Line.qty).toBe(2)  // Toujours 2 guests
    })

    it('should apply menu quantity overrides', () => {
      const overrides = {
        menu: { 6: 5 }  // Override menu à 5 unités
      }

      const result = computeQuoteTotal({
        selected: mockSelected,
        catalog: mockCatalog,
        dates: mockDates,
        rules: { capQtyToGuests: false }, overrides
      })

      const menuLine = result.lines.find(l => l.type === 'menu' && l.id === 6)
      expect(menuLine).toBeDefined()
      expect(menuLine.qty).toBe(5)  // Override appliqué
      expect(menuLine.lineTotal).toBe(200)  // 40 * 5 = 200
    })
  })

  describe('Gestion des cas limites', () => {
    it('should handle empty selections', () => {
      const result = computeQuoteTotal({
        selected: { activity: [], room: [], menu: [] },  
        catalog: mockCatalog,
        dates: mockDates,
        overrides: {}
      })

      expect(result.total).toBe(0)
      expect(result.lines).toHaveLength(0)
    })

    it('should handle missing catalog items', () => {
      const incompleteSelected = {
        activity: [999], // ID inexistant
        room: [4],       //  Array au lieu de number
        menu: [6]
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
      expect(result.lines).toHaveLength(2)  // room + menu seulement
    })

    it('should handle null or undefined selected properties', () => {
      const result = computeQuoteTotal({
        selected: {
          activity: null,
          room: undefined,
          menu: []
        },
        catalog: mockCatalog,
        dates: mockDates,
        overrides: {}
      })

      expect(result.total).toBe(0)
      expect(result.lines).toHaveLength(0)
    })

    it('should handle zero guests gracefully', () => {
      const result = computeQuoteTotal({
        selected: mockSelected,
        catalog: mockCatalog,
        dates: { ...mockDates, guests: 0 },  // 0 guests → devient 1
        overrides: {}
      })

      const activityLine = result.lines.find(l => l.type === 'activity')
      expect(activityLine.qty).toBe(1)  // Math.max(1, 0) = 1 guest × activityPerGuest
    })
  })

  describe('Règles de calcul personnalisées', () => {
    it('should apply custom rules configuration', () => {
      const customRules = {
        roomPerNight: false,          // Room pas multiplié par nuits
        activityPerGuest: false,      // Activity pas multiplié par guests
        menuPerGuest: false,          // Menu pas multiplié par guests
        capQtyToGuests: false         // Pas de limitation par guests
      }

      const result = computeQuoteTotal({
        selected: mockSelected,
        catalog: mockCatalog,
        dates: mockDates,
        rules: customRules,
        overrides: {}
      })

      // Avec ces règles, tout devrait être en quantité 1
      const roomLine = result.lines.find(l => l.type === 'room')
      expect(roomLine.qty).toBe(1)  // Pas multiplié par nuits

      const activityLine = result.lines.find(l => l.type === 'activity')
      expect(activityLine.qty).toBe(1)  // Pas multiplié par guests

      const menuLine = result.lines.find(l => l.type === 'menu')
      expect(menuLine.qty).toBe(1)  // Pas multiplié par guests
    })
  })
})