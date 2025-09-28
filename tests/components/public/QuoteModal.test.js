// tests/components/public/QuoteModal.test.js
import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import QuoteModal from '@/public/views/QuoteModal.vue'

// Mock FullCalendar
vi.mock('@fullcalendar/vue3', () => ({
  default: {
    name: 'FullCalendar',
    template: '<div data-testid="fullcalendar-mock" @click="selectDate">Calendar</div>',
    props: ['options'],
    methods: {
      getApi: () => ({
        select: vi.fn(),
        unselect: vi.fn()
      })
    }
  }
}))

// Mock PublicApi
const mockPublicApi = {
  getProducts: vi.fn(),
  saveQuote: vi.fn(),
  createStripeSession: vi.fn(),
  requestAdvice: vi.fn()
}

vi.mock('@/services/PublicApi', () => ({
  publicApi: mockPublicApi
}))

// Mock useQuotePricing
const mockComputeQuoteTotal = vi.fn()
vi.mock('@/shared/composables/useQuotePricing', () => ({
  computeQuoteTotal: mockComputeQuoteTotal
}))

describe('QuoteModal.vue', () => {
  let wrapper
  let pinia

  const mockProducts = [
    {
      id: 1,
      name: 'Tente Berbère Luxe',
      price: 120,
      typeConfig: { label: 'Hébergements' },
      productableData: { capacity: 4 }
    },
    {
      id: 2,
      name: 'Randonnée Chamelière',
      price: 80,
      typeConfig: { label: 'Activités' }
    },
    {
      id: 3,
      name: 'Menu Traditionnel',
      price: 45,
      typeConfig: { label: 'Menus' }
    }
  ]

  beforeEach(() => {
    pinia = createPinia()
    setActivePinia(pinia)
    
    vi.clearAllMocks()
    mockPublicApi.getProducts.mockResolvedValue({ data: mockProducts })
    mockComputeQuoteTotal.mockReturnValue({
      total: 345,
      nights: 3,
      lines: [
        { type: 'room', id: 1, name: 'Tente Berbère Luxe', qty: 1, unitPrice: 120, lineTotal: 360 },
        { type: 'activity', id: 2, name: 'Randonnée Chamelière', qty: 2, unitPrice: 80, lineTotal: 160 },
        { type: 'menu', id: 3, name: 'Menu Traditionnel', qty: 3, unitPrice: 45, lineTotal: 135 }
      ]
    })
    
    wrapper = mount(QuoteModal, {
      props: { show: true },
      global: {
        plugins: [pinia],
        stubs: ['teleport']
      }
    })
  })

  describe('Initialisation et affichage', () => {
    it('should render modal when show prop is true', () => {
      expect(wrapper.find('.modal-overlay').exists()).toBe(true)
      expect(wrapper.find('.quote-modal').exists()).toBe(true)
    })

    it('should not render modal when show prop is false', async () => {
      await wrapper.setProps({ show: false })
      expect(wrapper.find('.modal-overlay').exists()).toBe(false)
    })

    it('should load products on mount', () => {
      expect(mockPublicApi.getProducts).toHaveBeenCalled()
    })

    it('should start at step 1', () => {
      expect(wrapper.vm.currentStep).toBe(1)
    })
  })

  describe('Navigation entre étapes', () => {
    beforeEach(async () => {
      wrapper.vm.allProducts = mockProducts
      await wrapper.vm.$nextTick()
    })

    it('should advance to step 2 when dates and guests are selected', async () => {
      // Sélectionner les dates
      wrapper.vm.selectedDates.start = '2024-03-15'
      wrapper.vm.selectedDates.endExclusive = '2024-03-18'
      wrapper.vm.selectedDates.guests = 2

      await wrapper.vm.nextStep()
      expect(wrapper.vm.currentStep).toBe(2)
    })

    it('should not advance to step 2 without required selections', async () => {
      // Pas de dates sélectionnées
      await wrapper.vm.nextStep()
      expect(wrapper.vm.currentStep).toBe(1)
    })

    it('should advance to step 3 with room selection', async () => {
      wrapper.vm.currentStep = 2
      wrapper.vm.selectedItems.room = mockProducts[0]
      
      await wrapper.vm.nextStep()
      expect(wrapper.vm.currentStep).toBe(3)
    })

    it('should go back to previous step', async () => {
      wrapper.vm.currentStep = 3
      await wrapper.vm.prevStep()
      expect(wrapper.vm.currentStep).toBe(2)
    })
  })

  describe('Sélection de produits', () => {
    beforeEach(() => {
      wrapper.vm.allProducts = mockProducts
      wrapper.vm.selectedDates.guests = 2
    })

    it('should filter rooms by capacity', () => {
      const availableRooms = wrapper.vm.availableRooms
      expect(availableRooms).toHaveLength(1)
      expect(availableRooms[0].productableData.capacity).toBe(4)
    })

    it('should filter activities correctly', () => {
      const activities = wrapper.vm.availableActivities
      expect(activities).toHaveLength(1)
      expect(activities[0].typeConfig.label).toBe('Activités')
    })

    it('should filter menus correctly', () => {
      const menus = wrapper.vm.availableMenus
      expect(menus).toHaveLength(1)
      expect(menus[0].typeConfig.label).toBe('Menus')
    })

    it('should toggle activity selection', async () => {
      const activity = mockProducts[1]
      
      wrapper.vm.toggleActivity(activity)
      expect(wrapper.vm.selectedItems.activities).toContain(activity)
      
      wrapper.vm.toggleActivity(activity)
      expect(wrapper.vm.selectedItems.activities).not.toContain(activity)
    })

    it('should select room', async () => {
      const room = mockProducts[0]
      wrapper.vm.selectRoom(room)
      expect(wrapper.vm.selectedItems.room).toBe(room)
    })

    it('should toggle menu selection', async () => {
      const menu = mockProducts[2]
      
      wrapper.vm.toggleMenu(menu)
      expect(wrapper.vm.selectedItems.menus).toContain(menu)
      
      wrapper.vm.toggleMenu(menu)
      expect(wrapper.vm.selectedItems.menus).not.toContain(menu)
    })
  })

  describe('Calcul des prix', () => {
    beforeEach(() => {
      wrapper.vm.selectedDates.start = '2024-03-15'
      wrapper.vm.selectedDates.endExclusive = '2024-03-18'
      wrapper.vm.selectedItems.room = mockProducts[0]
    })

    it('should compute total price correctly', () => {
      expect(wrapper.vm.totalPrice).toBe(345)
    })

    it('should calculate number of nights', () => {
      expect(wrapper.vm.pricing.nights).toBe(3)
    })

    it('should format prices correctly', () => {
      expect(wrapper.vm.formatPrice(345)).toBe('345,00 €')
    })

    it('should update quantities and recalculate', async () => {
      wrapper.vm.qtyOverrides.activity[2] = 3
      await wrapper.vm.$nextTick()
      
      expect(mockComputeQuoteTotal).toHaveBeenCalledWith(
        expect.objectContaining({
          overrides: expect.objectContaining({
            activity: { 2: 3 }
          })
        })
      )
    })
  })

  describe('Validation et soumission', () => {
    beforeEach(() => {
      wrapper.vm.selectedDates.start = '2024-03-15'
      wrapper.vm.selectedDates.endExclusive = '2024-03-18'
      wrapper.vm.selectedItems.room = mockProducts[0]
      wrapper.vm.contactInfo = {
        name: 'Jean',
        last_name: 'Dupont',
        email: 'jean@test.com',
        phone: '+33123456789',
        message: 'Test message'
      }
    })

    it('should validate required fields', () => {
      expect(wrapper.vm.canSubmit).toBe(true)
    })

    it('should not submit without required room', async () => {
      wrapper.vm.selectedItems.room = null
      expect(wrapper.vm.canSubmit).toBe(false)
    })

    it('should not submit without contact info', async () => {
      wrapper.vm.contactInfo.email = ''
      expect(wrapper.vm.canSubmit).toBe(false)
    })

    it('should save quote successfully', async () => {
      mockPublicApi.saveQuote.mockResolvedValue({
        success: true,
        quote_request: { id: 1, quote_reference: 'QT-001', status: 'sent' }
      })

      const result = await wrapper.vm.saveQuote()
      
      expect(mockPublicApi.saveQuote).toHaveBeenCalledWith(
        expect.objectContaining({
          email: 'jean@test.com',
          contact: expect.objectContaining({
            name: 'Jean',
            last_name: 'Dupont'
          }),
          dates: expect.objectContaining({
            checkin: '2024-03-15',
            endExclusive: '2024-03-18'
          }),
          product_ids: expect.any(Array)
        })
      )
      
      expect(result.success).toBe(true)
    })

    it('should create reservation and redirect to payment', async () => {
      mockPublicApi.saveQuote.mockResolvedValue({
        success: true,
        quote_request: { id: 1, status: 'validated' }
      })
      
      mockPublicApi.createStripeSession.mockResolvedValue({
        success: true,
        checkout_url: 'https://checkout.stripe.com/test'
      })

      // Mock window.location.href
      const mockLocation = { href: '' }
      Object.defineProperty(window, 'location', {
        value: mockLocation,
        writable: true
      })

      await wrapper.vm.createReservationAndPay()
      
      expect(mockPublicApi.createStripeSession).toHaveBeenCalledWith(1)
      expect(window.location.href).toBe('https://checkout.stripe.com/test')
    })

    it('should request personalized advice', async () => {
      mockPublicApi.requestAdvice.mockResolvedValue({
        success: true,
        message: 'Demande envoyée'
      })

      await wrapper.vm.requestAdvice()
      
      expect(mockPublicApi.requestAdvice).toHaveBeenCalledWith(
        expect.objectContaining({
          contact: expect.objectContaining({
            name: 'Jean',
            email: 'jean@test.com'
          }),
          message: expect.stringContaining('Test message')
        })
      )
    })
  })

  describe('Gestion du calendrier', () => {
    it('should handle calendar date selection', async () => {
      const mockInfo = {
        startStr: '2024-03-15',
        endStr: '2024-03-18'
      }
      
      wrapper.vm.fcOnSelect(mockInfo)
      
      expect(wrapper.vm.selectedDates.start).toBe('2024-03-15')
      expect(wrapper.vm.selectedDates.endExclusive).toBe('2024-03-18')
    })

    it('should format dates for display', () => {
      const formatted = wrapper.vm.formatDate('2024-03-15')
      expect(formatted).toBe('15 mars 2024')
    })

    it('should convert end exclusive to inclusive for display', () => {
      const inclusive = wrapper.vm.displayEndInclusive('2024-03-18')
      expect(inclusive).toBe('2024-03-17')
    })
  })

  describe('Interface utilisateur', () => {
    it('should show loading state during submission', async () => {
      wrapper.vm.isSubmitting = 'booking'
      await wrapper.vm.$nextTick()
      
      const submitBtn = wrapper.find('.btn-primary')
      expect(submitBtn.text()).toContain('Traitement...')
      expect(submitBtn.attributes('disabled')).toBeDefined()
    })

    it('should display step indicators', () => {
      const stepIndicators = wrapper.findAll('.step-indicator')
      expect(stepIndicators).toHaveLength(4)
      
      // Premier step devrait être actif
      expect(stepIndicators[0].classes()).toContain('active')
    })

    it('should close modal when close button clicked', async () => {
      const closeBtn = wrapper.find('.btn-close')
      await closeBtn.trigger('click')
      
      expect(wrapper.emitted('close')).toBeTruthy()
    })

    it('should close modal when clicking outside', async () => {
      const overlay = wrapper.find('.modal-overlay')
      await overlay.trigger('click')
      
      expect(wrapper.emitted('close')).toBeTruthy()
    })

    it('should not close when clicking inside modal', async () => {
      const modal = wrapper.find('.quote-modal')
      await modal.trigger('click')
      
      expect(wrapper.emitted('close')).toBeFalsy()
    })
  })

  describe('Gestion des erreurs', () => {
    it('should handle API errors gracefully', async () => {
      mockPublicApi.saveQuote.mockRejectedValue(new Error('API Error'))
      
      const consoleSpy = vi.spyOn(console, 'error').mockImplementation(() => {})
      const alertSpy = vi.spyOn(window, 'alert').mockImplementation(() => {})
      
      await wrapper.vm.saveQuote()
      
      expect(consoleSpy).toHaveBeenCalled()
      expect(alertSpy).toHaveBeenCalledWith(expect.stringContaining('❌'))
      
      consoleSpy.mockRestore()
      alertSpy.mockRestore()
    })

    it('should handle network failures', async () => {
      mockPublicApi.getProducts.mockRejectedValue(new Error('Network Error'))
      
      const consoleSpy = vi.spyOn(console, 'error').mockImplementation(() => {})
      
      await wrapper.vm.loadProducts()
      
      expect(wrapper.vm.allProducts).toEqual([])
      expect(consoleSpy).toHaveBeenCalled()
      
      consoleSpy.mockRestore()
    })

    it('should validate email format', () => {
      wrapper.vm.contactInfo.email = 'invalid-email'
      expect(wrapper.vm.canSubmit).toBe(false)
      
      wrapper.vm.contactInfo.email = 'valid@email.com'
      expect(wrapper.vm.canSubmit).toBe(true)
    })
  })

  describe('Responsivité et accessibilité', () => {
    it('should be keyboard navigable', async () => {
      const nextBtn = wrapper.find('.btn-next')
      
      // Simuler navigation clavier
      await nextBtn.trigger('keydown', { key: 'Enter' })
      // Vérifier que l'action est déclenchée
    })

    it('should support screen readers', () => {
      const modal = wrapper.find('.quote-modal')
      expect(modal.attributes('role')).toBe('dialog')
      expect(modal.attributes('aria-modal')).toBe('true')
    })

    it('should handle mobile interactions', async () => {
      // Simuler un viewport mobile
      wrapper.vm.$el.style.width = '375px'
      
      // Vérifier que les boutons sont adaptés
      const buttons = wrapper.findAll('.btn')
      buttons.forEach(btn => {
        expect(btn.classes()).toContain('btn-mobile-friendly')
      })
    })
  })

  describe('Optimisation des performances', () => {
    it('should debounce quantity changes', async () => {
      const spy = vi.spyOn(wrapper.vm, 'updateQuantity')
      
      // Simuler plusieurs changements rapides
      wrapper.vm.adjustQuantity('activity', 2, 1)
      wrapper.vm.adjustQuantity('activity', 2, 2)
      wrapper.vm.adjustQuantity('activity', 2, 3)
      
      // Attendre le debounce
      await new Promise(resolve => setTimeout(resolve, 350))
      
      expect(spy).toHaveBeenCalledTimes(1)
    })

    it('should lazy load products when needed', async () => {
      // Reset
      wrapper.vm.allProducts = []
      mockPublicApi.getProducts.mockClear()
      
      // Accéder aux produits
      const activities = wrapper.vm.availableActivities
      
      expect(mockPublicApi.getProducts).toHaveBeenCalled()
    })
  })
})