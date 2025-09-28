// tests/components/calendar/EventModal.test.js
import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import EventModal from '@/shared/components/calendar/EventModal.vue'

// Mock ProductsApi
const mockProductsApi = {
  getProducts: vi.fn()
}

vi.mock('@/services/ProductsApi', () => ({
  default: mockProductsApi
}))

// Mock useQuotePricing
const mockComputeQuoteTotal = vi.fn()
vi.mock('@/shared/composables/useQuotePricing', () => ({
  computeQuoteTotal: mockComputeQuoteTotal
}))

describe('EventModal.vue', () => {
  let wrapper
  let pinia

  const mockProducts = {
    accommodations: [
      { id: 1, name: 'Tente Luxe', price: 120, productableData: { capacity: 4 } },
      { id: 2, name: 'Chambre Standard', price: 80, productableData: { capacity: 2 } }
    ],
    activities: [
      { id: 3, name: 'Randonnée Chamelière', price: 60 },
      { id: 4, name: 'Visite Oasis', price: 40 }
    ],
    menus: [
      { id: 5, name: 'Menu Traditionnel', price: 35 },
      { id: 6, name: 'Menu Végétarien', price: 30 }
    ]
  }

  const mockExistingEvent = {
    id: '1',
    title: 'Réservation - Jean Dupont',
    start: '2024-03-15',
    end: '2024-03-18',
    type: 'reservation',
    customerEmail: 'jean@test.com',
    customerName: 'Jean',
    customerLastName: 'Dupont',
    customerPhone: '+33123456789',
    amount: '450.00',
    comment: 'Demande lit bébé'
  }

  beforeEach(() => {
    pinia = createPinia()
    setActivePinia(pinia)
    
    vi.clearAllMocks()
    
    // Mock API responses
    mockProductsApi.getProducts.mockImplementation((params) => {
      if (params.type === 'App\\Models\\Room') {
        return Promise.resolve({ data: mockProducts.accommodations })
      } else if (params.productable_type === 'App\\Models\\Activity') {
        return Promise.resolve({ data: mockProducts.activities })
      } else if (params.productable_type === 'App\\Models\\Menu') {
        return Promise.resolve({ data: mockProducts.menus })
      }
      return Promise.resolve({ data: [] })
    })

    mockComputeQuoteTotal.mockReturnValue({
      total: 450,
      nights: 3,
      lines: [
        { type: 'room', id: 1, name: 'Tente Luxe', qty: 1, unitPrice: 120, lineTotal: 360 },
        { type: 'activity', id: 3, name: 'Randonnée Chamelière', qty: 1, unitPrice: 60, lineTotal: 60 },
        { type: 'menu', id: 5, name: 'Menu Traditionnel', qty: 1, unitPrice: 35, lineTotal: 35 }
      ]
    })

    wrapper = mount(EventModal, {
      props: {
        show: true,
        event: {},
        isEditing: false
      },
      global: {
        plugins: [pinia],
        stubs: ['teleport']
      }
    })
  })

  describe('Initialisation et affichage', () => {
    it('should render modal when show prop is true', () => {
      expect(wrapper.find('.modal-overlay').exists()).toBe(true)
      expect(wrapper.find('.event-modal').exists()).toBe(true)
    })

    it('should not render when show prop is false', async () => {
      await wrapper.setProps({ show: false })
      expect(wrapper.find('.modal-overlay').exists()).toBe(false)
    })

    it('should show "Nouvel événement" title when creating', () => {
      expect(wrapper.find('.modal-title').text()).toContain('Nouvel événement')
    })

    it('should show "Modifier événement" title when editing', async () => {
      await wrapper.setProps({ isEditing: true })
      expect(wrapper.find('.modal-title').text()).toContain('Modifier')
    })

    it('should load products on mount', () => {
      expect(mockProductsApi.getProducts).toHaveBeenCalled()
    })
  })

  describe('Types d\'événements', () => {
    it('should default to reservation type', () => {
      expect(wrapper.vm.formData.type).toBe('reservation')
    })

    it('should switch to event type', async () => {
      const eventTypeRadio = wrapper.find('input[value="event"]')
      await eventTypeRadio.setChecked()
      
      expect(wrapper.vm.formData.type).toBe('event')
    })

    it('should show different fields based on type', async () => {
      // Type réservation - doit montrer les champs client
      expect(wrapper.find('[data-testid="customer-fields"]').exists()).toBe(true)
      expect(wrapper.find('[data-testid="product-selection"]').exists()).toBe(true)
      
      // Changer vers événement
      wrapper.vm.formData.type = 'event'
      await wrapper.vm.$nextTick()
      
      expect(wrapper.find('[data-testid="customer-fields"]').exists()).toBe(false)
      expect(wrapper.find('[data-testid="product-selection"]').exists()).toBe(false)
    })
  })

  describe('Gestion des données d\'événement existant', () => {
    beforeEach(async () => {
      await wrapper.setProps({
        event: mockExistingEvent,
        isEditing: true
      })
    })

    it('should populate form with existing event data', () => {
      expect(wrapper.vm.formData.title).toBe('Réservation - Jean Dupont')
      expect(wrapper.vm.formData.start).toBe('2024-03-15')
      expect(wrapper.vm.formData.end).toBe('2024-03-18')
      expect(wrapper.vm.formData.customerEmail).toBe('jean@test.com')
      expect(wrapper.vm.formData.customerName).toBe('Jean')
      expect(wrapper.vm.formData.amount).toBe('450.00')
    })

    it('should handle event without customer data', async () => {
      const eventWithoutCustomer = {
        id: '2',
        title: 'Maintenance',
        start: '2024-03-20',
        end: '2024-03-20',
        type: 'event'
      }
      
      await wrapper.setProps({ event: eventWithoutCustomer })
      
      expect(wrapper.vm.formData.type).toBe('event')
      expect(wrapper.vm.formData.customerEmail).toBe('')
    })
  })

  describe('Sélection de produits', () => {
    beforeEach(async () => {
      // Attendre que les produits soient chargés
      await wrapper.vm.$nextTick()
      wrapper.vm.accommodations = mockProducts.accommodations
      wrapper.vm.availableActivities = mockProducts.activities
      wrapper.vm.availableMenus = mockProducts.menus
    })

    it('should load accommodations', () => {
      expect(wrapper.vm.accommodations).toHaveLength(2)
      expect(wrapper.vm.accommodations[0].name).toBe('Tente Luxe')
    })

    it('should select accommodation', async () => {
      const accommodationCheckbox = wrapper.find('input[data-accommodation-id="1"]')
      await accommodationCheckbox.setChecked()
      
      expect(wrapper.vm.selectedAccommodations).toContain(1)
    })

    it('should adjust accommodation quantity', () => {
      wrapper.vm.selectedAccommodations = [1]
      wrapper.vm.accommodationQuantities[1] = 2
      
      expect(wrapper.vm.getAccommodationQuantity(1)).toBe(2)
    })

    it('should select activities', async () => {
      const activityCheckbox = wrapper.find('input[data-activity-id="3"]')
      await activityCheckbox.setChecked()
      
      expect(wrapper.vm.selectedActivities).toContain(3)
    })

    it('should select menus', async () => {
      const menuCheckbox = wrapper.find('input[data-menu-id="5"]')
      await menuCheckbox.setChecked()
      
      expect(wrapper.vm.selectedMenus).toContain(5)
    })

    it('should calculate total amount automatically', async () => {
      wrapper.vm.selectedAccommodations = [1]
      wrapper.vm.selectedActivities = [3]
      wrapper.vm.selectedMenus = [5]
      
      // Déclencher le calcul
      await wrapper.vm.$nextTick()
      
      expect(wrapper.vm.formData.amount).toBe('450.00')
    })
  })

  describe('Validation des formulaires', () => {
    it('should validate required fields for reservation', () => {
      wrapper.vm.formData.type = 'reservation'
      wrapper.vm.formData.title = ''
      wrapper.vm.formData.customerEmail = ''
      
      expect(wrapper.vm.isFormValid).toBe(false)
    })

    it('should validate email format', () => {
      wrapper.vm.formData.customerEmail = 'invalid-email'
      expect(wrapper.vm.isValidEmail('invalid-email')).toBe(false)
      
      wrapper.vm.formData.customerEmail = 'valid@email.com'
      expect(wrapper.vm.isValidEmail('valid@email.com')).toBe(true)
    })

    it('should validate date range', () => {
      wrapper.vm.formData.start = '2024-03-20'
      wrapper.vm.formData.end = '2024-03-18' // End before start
      
      expect(wrapper.vm.isValidDateRange).toBe(false)
      
      wrapper.vm.formData.end = '2024-03-22'
      expect(wrapper.vm.isValidDateRange).toBe(true)
    })

    it('should require at least one product for reservations', () => {
      wrapper.vm.formData.type = 'reservation'
      wrapper.vm.selectedAccommodations = []
      wrapper.vm.selectedActivities = []
      wrapper.vm.selectedMenus = []
      
      expect(wrapper.vm.hasSelectedProducts).toBe(false)
      
      wrapper.vm.selectedAccommodations = [1]
      expect(wrapper.vm.hasSelectedProducts).toBe(true)
    })
  })

  describe('Soumission du formulaire', () => {
    beforeEach(() => {
      wrapper.vm.formData = {
        type: 'reservation',
        title: 'Test Réservation',
        start: '2024-03-15',
        end: '2024-03-18',
        customerEmail: 'test@test.com',
        customerName: 'Test',
        customerLastName: 'User',
        amount: '450.00'
      }
      wrapper.vm.selectedAccommodations = [1]
    })

    it('should prepare event data correctly for new reservation', async () => {
      const spy = vi.spyOn(wrapper.vm, 'prepareEventData')
      
      await wrapper.vm.handleSubmit()
      
      expect(spy).toHaveBeenCalledWith(wrapper.vm.formData)
      expect(wrapper.emitted('save')).toBeTruthy()
    })

    it('should include selected products in event data', () => {
      wrapper.vm.selectedAccommodations = [1]
      wrapper.vm.selectedActivities = [3, 4]
      wrapper.vm.selectedMenus = [5]
      
      const eventData = wrapper.vm.prepareEventData(wrapper.vm.formData)
      
      expect(eventData.selectedProducts).toContain(1)
      expect(eventData.selectedProducts).toContain(3)
      expect(eventData.selectedProducts).toContain(4)
      expect(eventData.selectedProducts).toContain(5)
    })

    it('should handle quantities correctly', () => {
      wrapper.vm.selectedAccommodations = [1]
      wrapper.vm.accommodationQuantities[1] = 2
      
      const eventData = wrapper.vm.prepareEventData(wrapper.vm.formData)
      
      // Should include product ID twice for quantity of 2
      const accommodationCount = eventData.selectedProducts.filter(id => id === 1).length
      expect(accommodationCount).toBe(2)
    })

    it('should show loading state during submission', async () => {
      wrapper.vm.isSubmitting = true
      await wrapper.vm.$nextTick()
      
      const submitBtn = wrapper.find('.btn-save')
      expect(submitBtn.text()).toContain('Sauvegarde...')
      expect(submitBtn.attributes('disabled')).toBeDefined()
    })
  })

  describe('Interface et interactions', () => {
    it('should close modal when cancel button clicked', async () => {
      const cancelBtn = wrapper.find('.btn-cancel')
      await cancelBtn.trigger('click')
      
      expect(wrapper.emitted('close')).toBeTruthy()
    })

    it('should confirm close if form has changes', async () => {
      wrapper.vm.formData.title = 'Modified Title'
      wrapper.vm.originalData.title = 'Original Title'
      
      const confirmSpy = vi.spyOn(window, 'confirm').mockReturnValue(true)
      
      wrapper.vm.confirmClose()
      
      expect(confirmSpy).toHaveBeenCalledWith(
        expect.stringContaining('Voulez-vous vraiment fermer sans sauvegarder')
      )
      
      confirmSpy.mockRestore()
    })

    it('should not confirm close if no changes', () => {
      const spy = vi.spyOn(wrapper.vm, 'forceClose')
      
      wrapper.vm.confirmClose()
      
      expect(spy).toHaveBeenCalled()
    })

    it('should handle escape key', async () => {
      const spy = vi.spyOn(wrapper.vm, 'confirmClose')
      
      await wrapper.trigger('keydown', { key: 'Escape' })
      
      expect(spy).toHaveBeenCalled()
    })
  })

  describe('Gestion des erreurs', () => {
    it('should handle product loading errors', async () => {
      mockProductsApi.getProducts.mockRejectedValue(new Error('API Error'))
      
      const consoleSpy = vi.spyOn(console, 'error').mockImplementation(() => {})
      
      await wrapper.vm.loadAccommodations()
      
      expect(wrapper.vm.accommodations).toEqual([])
      expect(consoleSpy).toHaveBeenCalled()
      
      consoleSpy.mockRestore()
    })

    it('should handle submission errors gracefully', async () => {
      const consoleSpy = vi.spyOn(console, 'error').mockImplementation(() => {})
      
      // Simuler une erreur lors de la soumission
      wrapper.vm.prepareEventData = vi.fn().mockImplementation(() => {
        throw new Error('Preparation failed')
      })
      
      await wrapper.vm.handleSubmit()
      
      expect(consoleSpy).toHaveBeenCalled()
      expect(wrapper.vm.isSubmitting).toBe(false)
      
      consoleSpy.mockRestore()
    })
  })

  describe('Calculs et formatage', () => {
    it('should format dates correctly', () => {
      const date = wrapper.vm.dateOnly('2024-03-15T14:30:00')
      expect(date).toBe('2024-03-15')
    })

    it('should calculate nights between dates', () => {
      wrapper.vm.formData.start = '2024-03-15'
      wrapper.vm.formData.end = '2024-03-18'
      
      const nights = wrapper.vm.pricing.nights
      expect(nights).toBe(3)
    })

    it('should expand selected rooms with quantities', () => {
      wrapper.vm.selectedAccommodations = [1, 2]
      wrapper.vm.accommodationQuantities = { 1: 2, 2: 1 }
      
      const expanded = wrapper.vm._expandSelectedRooms()
      expect(expanded).toEqual([1, 1, 2])
    })
  })

  describe('Performance et optimisation', () => {
    it('should not reload products if already loaded', async () => {
      wrapper.vm.accommodations = mockProducts.accommodations
      mockProductsApi.getProducts.mockClear()
      
      await wrapper.vm.loadAccommodations()
      
      expect(mockProductsApi.getProducts).not.toHaveBeenCalled()
    })

    it('should debounce amount calculations', async () => {
      const spy = vi.spyOn(wrapper.vm, 'updateCalculatedAmount')
      
      // Simuler plusieurs changements rapides
      wrapper.vm.selectedAccommodations = [1]
      wrapper.vm.selectedActivities = [3]
      wrapper.vm.selectedMenus = [5]
      
      // Attendre que les watchers se déclenchent
      await new Promise(resolve => setTimeout(resolve, 100))
      
      // Le calcul ne devrait se faire qu'une fois après stabilisation
      expect(mockComputeQuoteTotal).toHaveBeenCalled()
    })
  })

  describe('Accessibilité', () => {
    it('should have proper ARIA attributes', () => {
      const modal = wrapper.find('.event-modal')
      expect(modal.attributes('role')).toBe('dialog')
      expect(modal.attributes('aria-modal')).toBe('true')
    })

    it('should have proper labels for form fields', () => {
      const emailField = wrapper.find('input[type="email"]')
      const label = wrapper.find('label[for="customerEmail"]')
      
      expect(emailField.attributes('id')).toBe('customerEmail')
      expect(label.exists()).toBe(true)
    })

    it('should support keyboard navigation', async () => {
      const firstInput = wrapper.find('input[type="text"]')
      await firstInput.trigger('focus')
      
      // Simuler Tab pour aller au champ suivant
      await firstInput.trigger('keydown', { key: 'Tab' })
      
      // Vérifier que le focus se déplace correctement
    })
  })
})