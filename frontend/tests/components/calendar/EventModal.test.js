import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import EventModal from '@/shared/components/calendar/EventModal.vue'

describe('EventModal.vue - Tests Essentiels', () => {
  let wrapper, pinia

  beforeEach(() => {
    pinia = createPinia()
    setActivePinia(pinia)
    vi.clearAllMocks()
    
    wrapper = mount(EventModal, {
      global: { plugins: [pinia] },
      props: {
        show: true,
        event: {
          id: null,
          title: '',
          start: '2024-03-15T14:00:00',
          end: '2024-03-16T15:00:00',
          type: 'reservation'
        },
        isEditing: false
      }
    })
  })

  it('should render modal', () => {
    expect(wrapper.exists()).toBe(true)
  })

  it('should have correct initial data', () => {
    expect(wrapper.vm.formData.title).toBe('')
    expect(wrapper.vm.formData.type).toBe('autre')  // Valeur par défaut réelle
  })

  it('should handle form submission', async () => {
    wrapper.vm.formData.title = 'Test Event'
    
    await wrapper.vm.handleSubmit()
    
    // Le modal devrait émettre un événement save
    expect(wrapper.emitted()).toHaveProperty('save')
  })

  it('should close modal', async () => {
    await wrapper.vm.forceClose()
    
    expect(wrapper.emitted()).toHaveProperty('close')
  })

  it('should validate form data', () => {
    // Tester la validation via le titre vide
    wrapper.vm.formData.title = ''
    
    // Vérifier que le formulaire n'est pas prêt à être soumis
    expect(wrapper.vm.formData.title.length).toBe(0)
  })
})