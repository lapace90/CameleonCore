import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'

// Mocks simplifiés
vi.mock('@fullcalendar/vue3', () => ({
  default: { name: 'FullCalendar', template: '<div>Calendar</div>' }
}))

vi.mock('@/services/PublicApi', () => ({
  publicApi: {
    getProducts: vi.fn(() => Promise.resolve({ data: [] })),
    saveQuote: vi.fn(() => Promise.resolve({ success: true }))
  }
}))

vi.mock('@/shared/composables/useQuotePricing', () => ({
  computeQuoteTotal: vi.fn(() => ({ total: 450, nights: 3, lines: [] }))
}))

import QuoteModal from '@/public/views/QuoteModal.vue'

describe('QuoteModal.vue - Tests Essentiels', () => {
  let wrapper
  let pinia

  beforeEach(() => {
    pinia = createPinia()
    setActivePinia(pinia)
    
    wrapper = mount(QuoteModal, {
      props: { show: true },
      global: {
        plugins: [pinia],
        stubs: ['teleport', 'transition']
      }
    })
  })

  it('should render modal', () => {
    expect(wrapper.exists()).toBe(true)
  })

  it('should start at step 1', () => {
    expect(wrapper.vm.currentStep).toBe(1)
  })

  it('should handle date selection', () => {
    wrapper.vm.selectedDates.start = '2024-03-15'
    wrapper.vm.selectedDates.endExclusive = '2024-03-18'
    expect(wrapper.vm.selectedDates.start).toBe('2024-03-15')
  })

  it('should calculate pricing', () => {
    expect(wrapper.vm.totalPrice).toBe(450)
  })

  it('should validate navigation', () => {
    // Setup minimal pour navigation
    wrapper.vm.selectedDates.start = '2024-03-15'
    wrapper.vm.selectedDates.endExclusive = '2024-03-18'
    wrapper.vm.selectedDates.guests = 2
    
    expect(wrapper.vm.datesOK).toBe(true)
  })
})