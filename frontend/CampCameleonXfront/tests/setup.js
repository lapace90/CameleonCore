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

global.ResizeObserver = vi.fn().mockImplementation(() => ({
  observe: vi.fn(),
  unobserve: vi.fn(),
  disconnect: vi.fn(),
}))

// Mock API FullCalendar complète
const mockCalendarApi = {
  changeView: vi.fn(),
  refetchEvents: vi.fn(),
  getEvents: vi.fn(() => []),
  render: vi.fn(),
  destroy: vi.fn()
}

vi.mock('@fullcalendar/vue3', () => ({
  default: {
    name: 'FullCalendar',
    template: '<div data-testid="fullcalendar-mock">FullCalendar Mock</div>',
    props: ['options'],
    methods: {
      getApi() {
        return mockCalendarApi
      }
    }
  }
}))

vi.mock('@/services/AdminApi', () => ({
  default: {
    getCalendarEvents: vi.fn(() => Promise.resolve({ data: [] })),
    // 👉 shape attendu par FullCalendar.vue :
    // stats.reservations.total
    // stats.occupancy.current_guests / checkins_today / checkouts_today
    getDashboardStats: vi.fn(() => Promise.resolve({
      reservations: { total: 0 },
      occupancy: { current_guests: 0, checkins_today: 0, checkouts_today: 0 }
    })),
    createReservation: vi.fn(() => Promise.resolve({ success: true })),
    updateReservation: vi.fn(() => Promise.resolve({ success: true }))
  }
}))

vi.mock('@/services/ProductsApi', () => ({
  default: {
    getProducts: vi.fn(() => Promise.resolve({ data: [] }))
  }
}))

config.global.mocks = {
  $t: (key) => key,
  $route: { params: {}, query: {} },
  $router: { push: vi.fn(), replace: vi.fn() }
}

global.mockCalendarApi = mockCalendarApi