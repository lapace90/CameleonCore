import { reactive } from 'vue'

export function useQuoteSelections() {
  const selectedItems = reactive({
    activities: [],
    room: null,
    menus: []
  })
  
  const selectedDates = reactive({
    start: '',
    endExclusive: '',
    guests: 2
  })
  
  const contactInfo = reactive({
    name: '',
    last_name: '',
    email: '',
    phone: '',
    message: ''
  })
  
  const qtyOverrides = reactive({
    activity: {},
    menu: {}
  })
  
  // Méthodes de gestion des sélections
  const resetSelections = () => {
    selectedItems.activities = []
    selectedItems.room = null
    selectedItems.menus = []
    
    selectedDates.start = ''
    selectedDates.endExclusive = ''
    selectedDates.guests = 2
    
    Object.keys(contactInfo).forEach(key => {
      contactInfo[key] = ''
    })
    
    qtyOverrides.activity = {}
    qtyOverrides.menu = {}
  }
  
  return {
    selectedItems,
    selectedDates,
    contactInfo,
    qtyOverrides,
    resetSelections
  }
}
