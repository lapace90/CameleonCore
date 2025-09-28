import { vi } from 'vitest'
import { config } from '@vue/test-utils'

// Mock des APIs globales
Object.defineProperty(window, 'matchMedia', {
  writable: true,
  value: vi.fn().mockImplementation(query => ({
    matches: false,
    media: query,
    onchange: null,
    addListener: vi.fn(),
    removeListener: vi.fn(),
    addEventListener: vi.fn(),
    removeEventListener: vi.fn(),
    dispatchEvent: vi.fn(),
  })),
})

// Mock window.ResizeObserver
global.ResizeObserver = vi.fn().mockImplementation(() => ({
  observe: vi.fn(),
  unobserve: vi.fn(),
  disconnect: vi.fn(),
}))

// Mock des modules problématiques
vi.mock('@fullcalendar/vue3', () => ({
  default: {
    name: 'FullCalendar',
    template: '<div data-testid="fullcalendar-mock">FullCalendar Mock</div>',
    props: ['options']
  }
}))

// Configuration globale Vue Test Utils
config.global.mocks = {
  $t: (key) => key, // Mock i18n
  $route: { params: {}, query: {} },
  $router: { push: vi.fn(), replace: vi.fn() }
}

// Helpers globaux pour les tests
global.testHelpers = {
  // Helper pour créer un mock d'événement FullCalendar
  createMockCalendarEvent: (overrides = {}) => ({
    id: '1',
    title: 'Test Event',
    start: '2024-03-15T14:00:00',
    end: '2024-03-18T11:00:00',
    type: 'reservation',
    backgroundColor: '#28a745',
    extendedProps: {
      customer: { name: 'Test', last_name: 'User', email: 'test@test.com' },
      product: { name: 'Test Product' },
      status: 'confirmed',
      amount: 450
    },
    ...overrides
  }),

  // Helper pour créer un mock de produit
  createMockProduct: (type = 'room', overrides = {}) => ({
    id: 1,
    name: 'Test Product',
    price: 100,
    description: 'Test description',
    typeConfig: { label: type === 'room' ? 'Hébergements' : 'Activités' },
    productableData: type === 'room' ? { capacity: 4 } : { duration: 120 },
    ...overrides
  }),

  // Helper pour créer des données de réservation
  createMockReservationData: (overrides = {}) => ({
    checkin: '2024-03-15',
    checkout: '2024-03-18',
    amount: '450.00',
    customer_data: {
      email: 'test@test.com',
      name: 'Test',
      last_name: 'User',
      phone: '+33123456789'
    },
    number_of_adults: 2,
    number_of_children: 0,
    product_id: 1,
    ...overrides
  }),

  // Helper pour attendre les mises à jour asynchrones
  waitFor: async (callback, options = {}) => {
    const { timeout = 1000, interval = 50 } = options
    const start = Date.now()
    
    while (Date.now() - start < timeout) {
      try {
        const result = await callback()
        if (result) return result
      } catch (e) {
        // Ignorer les erreurs et continuer à essayer
      }
      await new Promise(resolve => setTimeout(resolve, interval))
    }
    
    throw new Error(`waitFor timeout after ${timeout}ms`)
  }
}