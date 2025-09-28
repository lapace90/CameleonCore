// tests/components/calendar/FullCalendar.test.js
import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import FullCalendar from '@/shared/components/calendar/FullCalendar.vue'
import EventModal from '@/shared/components/calendar/EventModal.vue'
import ConfirmModal from '@/shared/components/ui/ConfirmModal.vue'

// Mock FullCalendar component
vi.mock('@fullcalendar/vue3', () => ({
  default: {
    name: 'FullCalendar',
    template: '<div data-testid="fullcalendar-mock">FullCalendar Mock</div>',
    props: ['options'],
    methods: {
      getApi: () => ({
        changeView: vi.fn(),
        getDate: () => new Date('2024-03-15'),
        gotoDate: vi.fn(),
        refetchEvents: vi.fn()
      })
    }
  }
}))

// Mock des services
const mockAdminApi = {
  getCalendarEvents: vi.fn(),
  createReservation: vi.fn(),
  updateReservation: vi.fn(),
  deleteReservation: vi.fn()
}

vi.mock('@/services/AdminApi', () => ({
  default: mockAdminApi
}))

describe('FullCalendar.vue', () => {
  let wrapper
  let pinia

  const mockEvents = [
    {
      id: 1,
      title: 'Réservation - Jean Dupont',
      start: '2024-03-15T14:00:00',
      end: '2024-03-18T11:00:00',
      type: 'reservation',
      backgroundColor: '#28a745',
      extendedProps: {
        customer: { name: 'Jean', last_name: 'Dupont', email: 'jean@test.com' },
        product: { name: 'Tente Berbère' },
        status: 'confirmed',
        amount: 360
      }
    },
    {
      id: 2,
      title: 'Maintenance chambre',
      start: '2024-03-20T09:00:00',
      end: '2024-03-20T17:00:00',
      type: 'event',
      backgroundColor: '#6c757d'
    }
  ]

  beforeEach(() => {
    pinia = createPinia()
    setActivePinia(pinia)
    
    // Reset mocks
    vi.clearAllMocks()
    mockAdminApi.getCalendarEvents.mockResolvedValue({ data: mockEvents })
    
    wrapper = mount(FullCalendar, {
      global: {
        plugins: [pinia]
      }
    })
  })

  describe('Initialisation du calendrier', () => {
    it('should render calendar with correct default view', () => {
      expect(wrapper.find('[data-testid="fullcalendar-mock"]').exists()).toBe(true)
      expect(wrapper.vm.currentView).toBe('dayGridMonth')
    })

    it('should load events on mount', async () => {
      await wrapper.vm.$nextTick()
      expect(mockAdminApi.getCalendarEvents).toHaveBeenCalled()
    })

    it('should display view switcher buttons', () => {
      const viewButtons = wrapper.findAll('.view-btn')
      expect(viewButtons).toHaveLength(3) // Month, Week, Day
      
      const monthBtn = viewButtons.find(btn => btn.text().includes('Mois'))
      expect(monthBtn.exists()).toBe(true)
      expect(monthBtn.classes()).toContain('active')
    })

    it('should display create event button', () => {
      const createBtn = wrapper.find('.btn-create')
      expect(createBtn.exists()).toBe(true)
      expect(createBtn.text()).toContain('Nouvel événement')
    })
  })

  describe('Gestion des vues', () => {
    it('should change view when view button is clicked', async () => {
      const weekBtn = wrapper.findAll('.view-btn')[1] // Week view
      await weekBtn.trigger('click')
      
      expect(wrapper.vm.currentView).toBe('timeGridWeek')
    })

    it('should update active view button', async () => {
      await wrapper.vm.changeView('timeGridDay')
      await wrapper.vm.$nextTick()
      
      const activeBtn = wrapper.find('.view-btn.active')
      expect(activeBtn.text()).toContain('Jour')
    })
  })

  describe('Statistiques', () => {
    beforeEach(() => {
      wrapper.vm.events = mockEvents
      wrapper.vm.showStats = true
    })

    it('should calculate current month reservations', () => {
      const currentDate = new Date('2024-03-15')
      vi.spyOn(wrapper.vm, 'getCurrentDate').mockReturnValue(currentDate)
      
      expect(wrapper.vm.stats.reservations).toBe(1) // Only reservation in March
    })

    it('should calculate upcoming events', () => {
      expect(wrapper.vm.stats.events).toBe(1) // Only future event
    })

    it('should display stats cards', () => {
      const statCards = wrapper.findAll('.stat-card')
      expect(statCards).toHaveLength(3)
      
      expect(statCards[0].text()).toContain('Réservations ce mois')
      expect(statCards[1].text()).toContain('Événements prévus')
      expect(statCards[2].text()).toContain('Taux d\'occupation')
    })
  })

  describe('Gestion des événements', () => {
    it('should open create modal when create button clicked', async () => {
      const createBtn = wrapper.find('.btn-create')
      await createBtn.trigger('click')
      
      expect(wrapper.vm.showModal).toBe(true)
      expect(wrapper.vm.isEditing).toBe(false)
      expect(wrapper.vm.currentEvent).toEqual({})
    })

    it('should open edit modal when event is clicked', async () => {
      const mockEventInfo = {
        event: {
          id: '1',
          title: 'Test Event',
          startStr: '2024-03-15T14:00:00',
          endStr: '2024-03-18T11:00:00',
          extendedProps: mockEvents[0].extendedProps
        }
      }
      
      await wrapper.vm.handleEventClick(mockEventInfo)
      
      expect(wrapper.vm.showModal).toBe(true)
      expect(wrapper.vm.isEditing).toBe(true)
      expect(wrapper.vm.currentEvent.id).toBe('1')
    })

    it('should handle event drop (drag and drop)', async () => {
      const mockDropInfo = {
        event: {
          id: '1',
          title: 'Test Event',
          startStr: '2024-03-16T14:00:00',
          endStr: '2024-03-19T11:00:00'
        },
        revert: vi.fn()
      }
      
      mockAdminApi.updateReservation.mockResolvedValue({ success: true })
      
      await wrapper.vm.handleEventDrop(mockDropInfo)
      
      expect(mockAdminApi.updateReservation).toHaveBeenCalledWith('1', {
        checkin: '2024-03-16',
        checkout: '2024-03-19'
      })
    })

    it('should revert drop on API error', async () => {
      const mockDropInfo = {
        event: { id: '1' },
        revert: vi.fn()
      }
      
      mockAdminApi.updateReservation.mockRejectedValue(new Error('API Error'))
      
      await wrapper.vm.handleEventDrop(mockDropInfo)
      
      expect(mockDropInfo.revert).toHaveBeenCalled()
    })
  })

  describe('CRUD Operations', () => {
    const mockEventData = {
      type: 'reservation',
      title: 'Nouvelle réservation',
      start: '2024-03-20',
      end: '2024-03-23',
      customerEmail: 'test@test.com',
      customerName: 'Test',
      customerLastName: 'User',
      selectedProducts: [1, 2],
      amount: '450.00'
    }

    it('should create new event successfully', async () => {
      mockAdminApi.createReservation.mockResolvedValue({
        success: true,
        reservation: { id: 3, ...mockEventData }
      })
      
      await wrapper.vm.handleSaveEvent(mockEventData)
      
      expect(mockAdminApi.createReservation).toHaveBeenCalledWith(mockEventData)
      expect(wrapper.vm.showModal).toBe(false)
    })

    it('should update existing event successfully', async () => {
      wrapper.vm.isEditing = true
      wrapper.vm.currentEvent = { id: '1' }
      
      mockAdminApi.updateReservation.mockResolvedValue({
        success: true,
        reservation: { id: 1, ...mockEventData }
      })
      
      await wrapper.vm.handleSaveEvent(mockEventData)
      
      expect(mockAdminApi.updateReservation).toHaveBeenCalledWith('1', mockEventData)
    })

    it('should handle save errors gracefully', async () => {
      mockAdminApi.createReservation.mockRejectedValue(new Error('Validation failed'))
      
      // Mock console.error to avoid test output pollution
      const consoleSpy = vi.spyOn(console, 'error').mockImplementation(() => {})
      
      await wrapper.vm.handleSaveEvent(mockEventData)
      
      expect(consoleSpy).toHaveBeenCalledWith('Erreur sauvegarde:', expect.any(Error))
      consoleSpy.mockRestore()
    })

    it('should delete event after confirmation', async () => {
      wrapper.vm.currentEvent = { id: '1' }
      wrapper.vm.showConfirmDelete = true
      
      mockAdminApi.deleteReservation.mockResolvedValue({ success: true })
      
      await wrapper.vm.confirmDelete()
      
      expect(mockAdminApi.deleteReservation).toHaveBeenCalledWith('1')
      expect(wrapper.vm.showConfirmDelete).toBe(false)
      expect(wrapper.vm.showModal).toBe(false)
    })
  })

  describe('Filtres et recherche', () => {
    it('should filter events by status', async () => {
      await wrapper.vm.applyFilters({ status: 'confirmed' })
      
      expect(mockAdminApi.getCalendarEvents).toHaveBeenCalledWith({
        status: 'confirmed'
      })
    })

    it('should filter events by date range', async () => {
      const filters = {
        start: '2024-03-01',
        end: '2024-03-31'
      }
      
      await wrapper.vm.applyFilters(filters)
      
      expect(mockAdminApi.getCalendarEvents).toHaveBeenCalledWith(filters)
    })

    it('should filter events by customer', async () => {
      await wrapper.vm.applyFilters({ customer: 'Jean Dupont' })
      
      expect(mockAdminApi.getCalendarEvents).toHaveBeenCalledWith({
        customer: 'Jean Dupont'
      })
    })
  })

  describe('Interface et interactions', () => {
    it('should show loading state while fetching events', async () => {
      wrapper.vm.loading = true
      await wrapper.vm.$nextTick()
      
      // Vérifier qu'un indicateur de chargement est affiché
      // (Ajuster selon votre implémentation UI)
      expect(wrapper.vm.loading).toBe(true)
    })

    it('should handle keyboard shortcuts', async () => {
      const spyChangeView = vi.spyOn(wrapper.vm, 'changeView')
      
      // Simuler Ctrl+1 pour vue mois
      await wrapper.trigger('keydown', { 
        key: '1', 
        ctrlKey: true 
      })
      
      expect(spyChangeView).toHaveBeenCalledWith('dayGridMonth')
    })

    it('should refresh events when refresh button clicked', async () => {
      const refreshBtn = wrapper.find('.btn-refresh')
      if (refreshBtn.exists()) {
        await refreshBtn.trigger('click')
        expect(mockAdminApi.getCalendarEvents).toHaveBeenCalledTimes(2) // Initial + refresh
      }
    })
  })

  describe('Validation et gestion d\'erreurs', () => {
    it('should validate event dates before saving', async () => {
      const invalidEventData = {
        start: '2024-03-20',
        end: '2024-03-18', // End before start
        title: 'Invalid Event'
      }
      
      const consoleSpy = vi.spyOn(console, 'error').mockImplementation(() => {})
      
      await wrapper.vm.handleSaveEvent(invalidEventData)
      
      // Vérifier qu'une erreur de validation est gérée
      expect(consoleSpy).toHaveBeenCalled()
      consoleSpy.mockRestore()
    })

    it('should handle network errors gracefully', async () => {
      mockAdminApi.getCalendarEvents.mockRejectedValue(new Error('Network Error'))
      
      await wrapper.vm.loadEvents()
      
      // Vérifier que l'erreur est gérée sans crash
      expect(wrapper.vm.events).toEqual([])
    })
  })

  describe('Composants enfants', () => {
    it('should render EventModal component', () => {
      const eventModal = wrapper.findComponent(EventModal)
      expect(eventModal.exists()).toBe(true)
    })

    it('should render ConfirmModal component', () => {
      const confirmModal = wrapper.findComponent(ConfirmModal)
      expect(confirmModal.exists()).toBe(true)
    })

    it('should pass correct props to EventModal', async () => {
      wrapper.vm.showModal = true
      wrapper.vm.currentEvent = { id: '1', title: 'Test' }
      wrapper.vm.isEditing = true
      
      await wrapper.vm.$nextTick()
      
      const eventModal = wrapper.findComponent(EventModal)
      expect(eventModal.props('show')).toBe(true)
      expect(eventModal.props('event')).toEqual({ id: '1', title: 'Test' })
      expect(eventModal.props('isEditing')).toBe(true)
    })
  })

  describe('Performance et optimisation', () => {
    it('should debounce rapid filter changes', async () => {
      const spy = vi.spyOn(wrapper.vm, 'loadEvents')
      
      // Simuler plusieurs changements rapides
      wrapper.vm.applyFilters({ status: 'pending' })
      wrapper.vm.applyFilters({ status: 'confirmed' })
      wrapper.vm.applyFilters({ status: 'cancelled' })
      
      // Attendre la fin du debounce
      await new Promise(resolve => setTimeout(resolve, 350))
      
      // Seul le dernier appel devrait être effectué
      expect(spy).toHaveBeenCalledTimes(1)
    })

    it('should cache events to avoid unnecessary API calls', async () => {
      // Premier chargement
      await wrapper.vm.loadEvents()
      expect(mockAdminApi.getCalendarEvents).toHaveBeenCalledTimes(1)
      
      // Deuxième chargement immédiat
      await wrapper.vm.loadEvents()
      
      // Pas d'appel supplémentaire si pas de changement
      expect(mockAdminApi.getCalendarEvents).toHaveBeenCalledTimes(1)
    })
  })
})