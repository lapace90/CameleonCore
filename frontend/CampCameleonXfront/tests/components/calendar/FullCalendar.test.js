import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'

// Mocks simplifiés
vi.mock('@fullcalendar/vue3', () => ({
  default: { name: 'FullCalendar', template: '<div>Calendar Mock</div>' }
}))

vi.mock('@/services/AdminApi', () => ({
  default: {
    getCalendarEvents: vi.fn(() => Promise.resolve({ data: [] })),
    createReservation: vi.fn(() => Promise.resolve({ success: true })),
    updateReservation: vi.fn(() => Promise.resolve({ success: true }))
  }
}))

import FullCalendar from '@/shared/components/calendar/FullCalendar.vue'

describe('FullCalendar.vue - Tests Essentiels', () => {
  let wrapper, pinia

  beforeEach(() => {
    pinia = createPinia()
    setActivePinia(pinia)
    
    wrapper = mount(FullCalendar, {
      global: { plugins: [pinia] }
    })
  })

  it('should render calendar', () => {
    expect(wrapper.exists()).toBe(true)
  })

  it('should have default view', () => {
    expect(wrapper.vm.currentView).toBe('dayGridMonth')
  })

  it('should open create modal', () => {
    wrapper.vm.openCreateModal()
    expect(wrapper.vm.showModal).toBe(true)
  })

  it('should handle view changes', () => {
    wrapper.vm.changeView('timeGridWeek')
    expect(wrapper.vm.currentView).toBe('timeGridWeek')
  })
})