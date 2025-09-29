import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import FullCalendar from '@/shared/components/calendar/FullCalendar.vue'
import AdminApi from '@/services/AdminApi'

describe('FullCalendar.vue - Tests Essentiels', () => {
  let wrapper, pinia

  beforeEach(() => {
    pinia = createPinia()
    setActivePinia(pinia)
    vi.clearAllMocks()
    
    wrapper = mount(FullCalendar, {
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

    return wrapper.vm.$nextTick()
  })

  it('should render calendar', () => {
    expect(wrapper.exists()).toBe(true)
  })

  it('should have default view', () => {
    expect(wrapper.vm.currentView).toBe('dayGridMonth')
  })

  it('should open create modal', async () => {
    await wrapper.vm.openCreateModal()
    expect(wrapper.vm.showModal).toBe(true)
  })

  it('should handle view changes', async () => {
    await wrapper.vm.changeView('timeGridWeek')
    expect(wrapper.vm.currentView).toBe('timeGridWeek')
    expect(global.mockCalendarApi.changeView).toHaveBeenCalledWith('timeGridWeek')
  })

  it('should refresh calendar', async () => {
    await wrapper.vm.refreshCalendar()
    expect(global.mockCalendarApi.refetchEvents).toHaveBeenCalled()
  })

  it('should load initial stats', async () => {
    const adminApiMock = vi.mocked(AdminApi)
    await wrapper.vm.$nextTick()
    
    expect(adminApiMock.getDashboardStats).toHaveBeenCalled()
    expect(wrapper.vm.stats.reservations).toBe(0)
  })

  it('should handle event creation', async () => {
    const adminApiMock = vi.mocked(AdminApi)
    
    await wrapper.vm.handleSaveEvent({
      title: 'Test Event',
      start: '2024-03-15T14:00:00',
      type: 'reservation'
    })
    
    expect(adminApiMock.createReservation).toHaveBeenCalled()
  })

  it('should close modal properly', async () => {
    await wrapper.vm.openCreateModal()
    expect(wrapper.vm.showModal).toBe(true)
    
    await wrapper.vm.closeModal()
    expect(wrapper.vm.showModal).toBe(false)
  })

  it('should handle date selection', async () => {
    await wrapper.vm.handleDateSelect({
      start: new Date('2024-03-15'),
      end: new Date('2024-03-16'),
      allDay: true
    })
    
    expect(wrapper.vm.showModal).toBe(true)
  })
})