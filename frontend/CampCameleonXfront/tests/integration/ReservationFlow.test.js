import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import FullCalendar from '@/shared/components/calendar/FullCalendar.vue'

describe('Flux Complet de Réservation', () => {
  let pinia

  beforeEach(() => {
    pinia = createPinia()
    setActivePinia(pinia)
    vi.clearAllMocks()
  })

  describe('Flux Admin - Création réservation via calendrier', () => {
    it('should create reservation through calendar modal', async () => {
      const wrapper = mount(FullCalendar, {
        global: { 
          plugins: [pinia],
          stubs: {
            'EventModal': true,
            'ConfirmModal': true,
            'EventDetailsModal': true
          }
        },
        props: { mode: 'admin' }
      })

      // Ouvrir modal de création
      await wrapper.vm.openCreateModal()
      expect(wrapper.vm.showModal).toBe(true)
      expect(wrapper.vm.isEditing).toBe(false)
    })

    it('should handle date selection in calendar', async () => {
      const wrapper = mount(FullCalendar, {
        global: { 
          plugins: [pinia],
          stubs: {
            'EventModal': true,
            'ConfirmModal': true,
            'EventDetailsModal': true
          }
        },
        props: { mode: 'admin' }
      })

      await wrapper.vm.handleDateSelect({
        start: new Date('2024-03-15'),
        end: new Date('2024-03-16'),
        allDay: true
      })
      
      expect(wrapper.vm.showModal).toBe(true)
    })
  })
})