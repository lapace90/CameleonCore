// tests/integration/ReservationFlow.test.js
import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createRouter, createWebHistory } from 'vue-router'
import FullCalendar from '@/shared/components/calendar/FullCalendar.vue'
import QuoteModal from '@/public/views/QuoteModal.vue'

// Mock des services
const mockAdminApi = {
  getCalendarEvents: vi.fn(),
  createReservation: vi.fn(),
  updateReservation: vi.fn(),
  deleteReservation: vi.fn()
}

const mockPublicApi = {
  getProducts: vi.fn(),
  saveQuote: vi.fn(),
  createStripeSession: vi.fn()
}

vi.mock('@/services/AdminApi', () => ({ default: mockAdminApi }))
vi.mock('@/services/PublicApi', () => ({ publicApi: mockPublicApi }))
vi.mock('@fullcalendar/vue3', () => ({ default: { name: 'FullCalendar', template: '<div>Calendar</div>' } }))

describe('Flux Complet de Réservation', () => {
  let pinia
  let router

  beforeEach(() => {
    pinia = createPinia()
    setActivePinia(pinia)
    
    router = createRouter({
      history: createWebHistory(),
      routes: []
    })
    
    vi.clearAllMocks()
  })

  describe('Flux Admin - Création réservation via calendrier', () => {
    it('should create reservation through calendar modal', async () => {
      // Arrange
      const mockProducts = [
        { id: 1, name: 'Tente Luxe', price: 120, typeConfig: { label: 'Hébergements' } },
        { id: 2, name: 'Randonnée', price: 80, typeConfig: { label: 'Activités' } }
      ]

      mockAdminApi.getCalendarEvents.mockResolvedValue({ data: [] })
      mockAdminApi.createReservation.mockResolvedValue({
        success: true,
        reservation: {
          id: 1,
          customer: { name: 'Jean', last_name: 'Dupont' },
          checkin: '2024-03-15',
          checkout: '2024-03-18',
          amount: 450
        }
      })

      const wrapper = mount(FullCalendar, {
        global: { plugins: [pinia, router] }
      })

      // Act - Ouvrir modal de création
      await wrapper.vm.openCreateModal()
      expect(wrapper.vm.showModal).toBe(true)
      expect(wrapper.vm.isEditing).toBe(false)

      // Simuler la saisie dans EventModal
      const eventData = {
        type: 'reservation',
        title: 'Réservation - Jean Dupont',
        start: '2024-03-15',
        end: '2024-03-18',
        customerEmail: 'jean@test.com',
        customerName: 'Jean',
        customerLastName: 'Dupont',
        selectedProducts: [1, 2],
        amount: '450.00'
      }

      // Act - Sauvegarder l'événement
      await wrapper.vm.handleSaveEvent(eventData)

      // Assert
      expect(mockAdminApi.createReservation).toHaveBeenCalledWith(eventData)
      expect(wrapper.vm.showModal).toBe(false)
    })

    it('should edit reservation via drag and drop', async () => {
      // Arrange
      const initialEvent = {
        id: '1',
        title: 'Réservation - Jean Dupont',
        start: '2024-03-15T14:00:00',
        end: '2024-03-18T11:00:00'
      }

      mockAdminApi.getCalendarEvents.mockResolvedValue({ data: [initialEvent] })
      mockAdminApi.updateReservation.mockResolvedValue({ success: true })

      const wrapper = mount(FullCalendar, {
        global: { plugins: [pinia, router] }
      })

      // Act - Simuler drag & drop
      const dropInfo = {
        event: {
          id: '1',
          startStr: '2024-03-16T14:00:00',
          endStr: '2024-03-19T11:00:00'
        },
        revert: vi.fn()
      }

      await wrapper.vm.handleEventDrop(dropInfo)

      // Assert
      expect(mockAdminApi.updateReservation).toHaveBeenCalledWith('1', {
        checkin: '2024-03-16',
        checkout: '2024-03-19'
      })
      expect(dropInfo.revert).not.toHaveBeenCalled()
    })

    it('should handle drag drop errors gracefully', async () => {
      // Arrange
      mockAdminApi.updateReservation.mockRejectedValue(new Error('Conflict detected'))
      
      const wrapper = mount(FullCalendar, {
        global: { plugins: [pinia, router] }
      })

      const dropInfo = {
        event: { id: '1' },
        revert: vi.fn()
      }

      const consoleSpy = vi.spyOn(console, 'error').mockImplementation(() => {})

      // Act
      await wrapper.vm.handleEventDrop(dropInfo)

      // Assert
      expect(dropInfo.revert).toHaveBeenCalled()
      expect(consoleSpy).toHaveBeenCalledWith('Erreur drag & drop:', expect.any(Error))
      
      consoleSpy.mockRestore()
    })
  })

  describe('Flux Public - Devis vers réservation', () => {
    it('should complete full booking flow from quote to payment', async () => {
      // Arrange
      const mockProducts = [
        { id: 1, name: 'Tente Berbère', price: 120, typeConfig: { label: 'Hébergements' } },
        { id: 2, name: 'Menu Traditionnel', price: 40, typeConfig: { label: 'Menus' } }
      ]

      mockPublicApi.getProducts.mockResolvedValue({ data: mockProducts })
      mockPublicApi.saveQuote.mockResolvedValue({
        success: true,
        quote_request: { id: 1, status: 'validated' }
      })
      mockPublicApi.createStripeSession.mockResolvedValue({
        success: true,
        checkout_url: 'https://checkout.stripe.com/test'
      })

      const wrapper = mount(QuoteModal, {
        props: { show: true },
        global: { plugins: [pinia], stubs: ['teleport'] }
      })

      // Act - Étape 1: Sélection dates
      wrapper.vm.selectedDates = {
        start: '2024-03-15',
        endExclusive: '2024-03-18',
        guests: 2
      }
      await wrapper.vm.nextStep()
      expect(wrapper.vm.currentStep).toBe(2)

      // Act - Étape 2: Sélection produits
      wrapper.vm.selectRoom(mockProducts[0])
      wrapper.vm.toggleMenu(mockProducts[1])
      await wrapper.vm.nextStep()
      expect(wrapper.vm.currentStep).toBe(3)

      // Act - Étape 3: Informations contact
      wrapper.vm.contactInfo = {
        name: 'Marie',
        last_name: 'Martin',
        email: 'marie@test.com',
        phone: '+33123456789',
        message: 'Séjour romantique'
      }
      await wrapper.vm.nextStep()
      expect(wrapper.vm.currentStep).toBe(4)

      // Mock window.location.href
      const mockLocation = { href: '' }
      Object.defineProperty(window, 'location', {
        value: mockLocation,
        writable: true
      })

      // Act - Étape 4: Finalisation et paiement
      await wrapper.vm.createReservationAndPay()

      // Assert
      expect(mockPublicApi.saveQuote).toHaveBeenCalledWith(
        expect.objectContaining({
          email: 'marie@test.com',
          contact: expect.objectContaining({
            name: 'Marie',
            last_name: 'Martin'
          }),
          dates: expect.objectContaining({
            checkin: '2024-03-15',
            endExclusive: '2024-03-18',
            guests: 2
          }),
          product_ids: expect.any(Array)
        })
      )
      expect(mockPublicApi.createStripeSession).toHaveBeenCalledWith(1)
      expect(window.location.href).toBe('https://checkout.stripe.com/test')
    })

    it('should handle email validation flow', async () => {
      // Arrange
      mockPublicApi.saveQuote.mockResolvedValue({
        success: true,
        quote_request: { 
          id: 1, 
          status: 'draft', 
          quote_reference: 'QT-001' 
        }
      })

      const wrapper = mount(QuoteModal, {
        props: { show: true },
        global: { plugins: [pinia], stubs: ['teleport'] }
      })

      // Setup complet
      wrapper.vm.selectedDates = {
        start: '2024-03-15',
        endExclusive: '2024-03-18',
        guests: 2
      }
      wrapper.vm.selectedItems.room = { id: 1, name: 'Tente', price: 120 }
      wrapper.vm.contactInfo = {
        name: 'Test',
        last_name: 'User',
        email: 'test@test.com'
      }

      const alertSpy = vi.spyOn(window, 'alert').mockImplementation(() => {})
      const closeSpy = vi.spyOn(wrapper.vm, 'closeModal')

      // Act
      await wrapper.vm.createReservationAndPay()

      // Assert
      expect(alertSpy).toHaveBeenCalledWith(
        expect.stringContaining('📧 Validation email requise')
      )
      expect(closeSpy).toHaveBeenCalled()
      
      alertSpy.mockRestore()
    })
  })

  describe('Gestion des erreurs intégrée', () => {
    it('should handle calendar loading failures', async () => {
      // Arrange
      mockAdminApi.getCalendarEvents.mockRejectedValue(new Error('Server Error'))
      
      const wrapper = mount(FullCalendar, {
        global: { plugins: [pinia, router] }
      })

      const consoleSpy = vi.spyOn(console, 'error').mockImplementation(() => {})

      // Act
      await wrapper.vm.loadEvents()

      // Assert
      expect(wrapper.vm.events).toEqual([])
      expect(consoleSpy).toHaveBeenCalled()
      
      consoleSpy.mockRestore()
    })

    it('should handle quote saving failures gracefully', async () => {
      // Arrange
      mockPublicApi.saveQuote.mockRejectedValue(new Error('Validation Error'))
      
      const wrapper = mount(QuoteModal, {
        props: { show: true },
        global: { plugins: [pinia], stubs: ['teleport'] }
      })

      // Setup minimal valid data
      wrapper.vm.selectedItems.room = { id: 1 }
      wrapper.vm.contactInfo = { email: 'test@test.com' }

      const alertSpy = vi.spyOn(window, 'alert').mockImplementation(() => {})

      // Act
      await wrapper.vm.saveQuote()

      // Assert
      expect(alertSpy).toHaveBeenCalledWith(
        expect.stringContaining('❌')
      )
      
      alertSpy.mockRestore()
    })
  })

  describe('Synchronisation état/UI', () => {
    it('should maintain consistent state during navigation', async () => {
      const wrapper = mount(QuoteModal, {
        props: { show: true },
        global: { plugins: [pinia], stubs: ['teleport'] }
      })

      // Navigation avant-arrière
      wrapper.vm.currentStep = 2
      await wrapper.vm.prevStep()
      expect(wrapper.vm.currentStep).toBe(1)

      wrapper.vm.currentStep = 4
      await wrapper.vm.prevStep()
      expect(wrapper.vm.currentStep).toBe(3)

      // État des sélections préservé
      wrapper.vm.selectedItems.room = { id: 1, name: 'Test Room' }
      wrapper.vm.currentStep = 1
      await wrapper.vm.nextStep()
      await wrapper.vm.nextStep()
      
      expect(wrapper.vm.selectedItems.room).toEqual({ id: 1, name: 'Test Room' })
    })

    it('should update UI based on loading states', async () => {
      const wrapper = mount(QuoteModal, {
        props: { show: true },
        global: { plugins: [pinia], stubs: ['teleport'] }
      })

      // Loading state
      wrapper.vm.isSubmitting = 'booking'
      await wrapper.vm.$nextTick()

      const submitButtons = wrapper.findAll('.btn-primary')
      submitButtons.forEach(btn => {
        expect(btn.attributes('disabled')).toBeDefined()
      })

      // Non-loading state
      wrapper.vm.isSubmitting = false
      await wrapper.vm.$nextTick()

      expect(wrapper.vm.canSubmit ? 'enabled' : 'disabled').toBe('enabled')
    })
  })
})

// tests/integration/CalendarReservationSync.test.js
describe('Synchronisation Calendrier-Réservations', () => {
  let calendarWrapper
  let pinia

  beforeEach(() => {
    pinia = createPinia()
    setActivePinia(pinia)
    
    mockAdminApi.getCalendarEvents.mockResolvedValue({ data: [] })
    
    calendarWrapper = mount(FullCalendar, {
      global: { plugins: [pinia] }
    })
  })

  it('should refresh calendar after creating reservation', async () => {
    const refreshSpy = vi.spyOn(calendarWrapper.vm, 'loadEvents')
    
    // Simuler création réservation
    mockAdminApi.createReservation.mockResolvedValue({
      success: true,
      reservation: { id: 1 }
    })

    await calendarWrapper.vm.handleSaveEvent({
      type: 'reservation',
      title: 'Test',
      start: '2024-03-15',
      end: '2024-03-18'
    })

    expect(refreshSpy).toHaveBeenCalled()
  })

  it('should maintain selected date range after refresh', async () => {
    const initialDateRange = {
      start: '2024-03-01',
      end: '2024-03-31'
    }

    calendarWrapper.vm.currentDateRange = initialDateRange
    await calendarWrapper.vm.loadEvents()

    expect(mockAdminApi.getCalendarEvents).toHaveBeenCalledWith(
      expect.objectContaining(initialDateRange)
    )
  })

  it('should filter events by status correctly', async () => {
    const mockEvents = [
      { id: 1, status: 'confirmed', backgroundColor: '#28a745' },
      { id: 2, status: 'pending', backgroundColor: '#ffc107' },
      { id: 3, status: 'cancelled', backgroundColor: '#dc3545' }
    ]

    mockAdminApi.getCalendarEvents.mockResolvedValue({ data: mockEvents })
    
    await calendarWrapper.vm.applyFilters({ status: 'confirmed' })

    expect(mockAdminApi.getCalendarEvents).toHaveBeenCalledWith({
      status: 'confirmed'
    })
  })
})

// tests/integration/DataFlow.test.js
describe('Flux de Données Between Components', () => {
  it('should propagate pricing changes through quote modal', async () => {
    const mockComputeQuoteTotal = vi.fn().mockReturnValue({
      total: 500,
      nights: 3,
      lines: []
    })

    vi.doMock('@/shared/composables/useQuotePricing', () => ({
      computeQuoteTotal: mockComputeQuoteTotal
    }))

    const wrapper = mount(QuoteModal, {
      props: { show: true },
      global: { plugins: [createPinia()], stubs: ['teleport'] }
    })

    // Modifier sélections
    wrapper.vm.selectedItems.room = { id: 1, price: 120 }
    wrapper.vm.selectedItems.activities = [{ id: 2, price: 80 }]
    wrapper.vm.selectedDates.guests = 4

    await wrapper.vm.$nextTick()

    expect(mockComputeQuoteTotal).toHaveBeenCalledWith(
      expect.objectContaining({
        selected: expect.objectContaining({
          room: { id: 1, price: 120 }
        })
      })
    )
  })

  it('should handle concurrent state updates', async () => {
    const wrapper = mount(QuoteModal, {
      props: { show: true },
      global: { plugins: [createPinia()], stubs: ['teleport'] }
    })

    // Simuler plusieurs mises à jour simultanées
    const promises = [
      wrapper.vm.loadProducts(),
      wrapper.setProps({ show: false }),
      wrapper.setProps({ show: true })
    ]

    await Promise.all(promises)

    // L'état final devrait être cohérent
    expect(wrapper.vm.show).toBe(true)
    expect(wrapper.vm.loading).toBe(false)
  })
})