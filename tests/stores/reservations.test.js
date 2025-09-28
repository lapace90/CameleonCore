// tests/stores/reservations.test.js
import { describe, it, expect, vi, beforeEach } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'
import { useReservationsStore } from '@/shared/stores/reservations'

// Mock des services
const mockAdminApi = {
  getCalendarEvents: vi.fn(),
  createReservation: vi.fn(),
  updateReservation: vi.fn(),
  deleteReservation: vi.fn()
}

vi.mock('@/services/AdminApi', () => ({ default: mockAdminApi }))

describe('useReservationsStore', () => {
  let store

  beforeEach(() => {
    setActivePinia(createPinia())
    store = useReservationsStore()
    vi.clearAllMocks()
  })

  describe('État initial', () => {
    it('should have correct initial state', () => {
      expect(store.reservations).toEqual([])
      expect(store.calendarEvents).toEqual([])
      expect(store.loading).toBe(false)
      expect(store.error).toBeNull()
      expect(store.filters).toEqual({})
      expect(store.selectedReservation).toBeNull()
    })
  })

  describe('Actions - Chargement des données', () => {
    it('should load calendar events successfully', async () => {
      const mockEvents = [
        { id: 1, title: 'Event 1', start: '2024-03-15' },
        { id: 2, title: 'Event 2', start: '2024-03-16' }
      ]

      mockAdminApi.getCalendarEvents.mockResolvedValue({ data: mockEvents })

      await store.loadCalendarEvents()

      expect(store.calendarEvents).toEqual(mockEvents)
      expect(store.loading).toBe(false)
      expect(store.error).toBeNull()
      expect(mockAdminApi.getCalendarEvents).toHaveBeenCalledWith({})
    })

    it('should load calendar events with filters', async () => {
      const filters = { status: 'confirmed', start: '2024-03-01' }
      mockAdminApi.getCalendarEvents.mockResolvedValue({ data: [] })

      await store.loadCalendarEvents(filters)

      expect(mockAdminApi.getCalendarEvents).toHaveBeenCalledWith(filters)
      expect(store.filters).toEqual(filters)
    })

    it('should handle loading errors', async () => {
      const error = new Error('API Error')
      mockAdminApi.getCalendarEvents.mockRejectedValue(error)

      await store.loadCalendarEvents()

      expect(store.calendarEvents).toEqual([])
      expect(store.loading).toBe(false)
      expect(store.error).toBe('API Error')
    })

    it('should set loading state during API calls', async () => {
      let resolvePromise
      const promise = new Promise((resolve) => {
        resolvePromise = resolve
      })
      mockAdminApi.getCalendarEvents.mockReturnValue(promise)

      const loadPromise = store.loadCalendarEvents()
      
      // Pendant le chargement
      expect(store.loading).toBe(true)

      resolvePromise({ data: [] })
      await loadPromise

      // Après le chargement
      expect(store.loading).toBe(false)
    })
  })

  describe('Actions - CRUD Réservations', () => {
    it('should create reservation and update store', async () => {
      const reservationData = {
        checkin: '2024-03-15',
        checkout: '2024-03-18',
        customerEmail: 'test@test.com',
        amount: '450.00'
      }

      const mockResponse = {
        success: true,
        reservation: { id: 1, ...reservationData }
      }

      mockAdminApi.createReservation.mockResolvedValue({ data: mockResponse })
      mockAdminApi.getCalendarEvents.mockResolvedValue({ data: [] })

      const result = await store.createReservation(reservationData)

      expect(result).toEqual(mockResponse)
      expect(mockAdminApi.createReservation).toHaveBeenCalledWith(reservationData)
      expect(mockAdminApi.getCalendarEvents).toHaveBeenCalled() // Refresh automatique
    })

    it('should update reservation and refresh events', async () => {
      const updateData = { status: 'confirmed' }
      const mockResponse = {
        success: true,
        reservation: { id: 1, status: 'confirmed' }
      }

      mockAdminApi.updateReservation.mockResolvedValue({ data: mockResponse })
      mockAdminApi.getCalendarEvents.mockResolvedValue({ data: [] })

      const result = await store.updateReservation(1, updateData)

      expect(result).toEqual(mockResponse)
      expect(mockAdminApi.updateReservation).toHaveBeenCalledWith(1, updateData)
      expect(mockAdminApi.getCalendarEvents).toHaveBeenCalled()
    })

    it('should delete reservation and refresh events', async () => {
      mockAdminApi.deleteReservation.mockResolvedValue({ data: { success: true } })
      mockAdminApi.getCalendarEvents.mockResolvedValue({ data: [] })

      const result = await store.deleteReservation(1)

      expect(result.success).toBe(true)
      expect(mockAdminApi.deleteReservation).toHaveBeenCalledWith(1)
      expect(mockAdminApi.getCalendarEvents).toHaveBeenCalled()
    })

    it('should handle CRUD errors gracefully', async () => {
      const error = new Error('Validation failed')
      mockAdminApi.createReservation.mockRejectedValue(error)

      await expect(store.createReservation({})).rejects.toThrow('Validation failed')
      expect(store.error).toBe('Validation failed')
    })
  })

  describe('Getters', () => {
    beforeEach(() => {
      store.calendarEvents = [
        { id: 1, type: 'reservation', extendedProps: { status: 'confirmed' } },
        { id: 2, type: 'reservation', extendedProps: { status: 'pending' } },
        { id: 3, type: 'event', title: 'Maintenance' },
        { id: 4, type: 'reservation', extendedProps: { status: 'confirmed' } }
      ]
    })

    it('should filter reservations only', () => {
      const reservations = store.reservationsOnly
      expect(reservations).toHaveLength(3)
      expect(reservations.every(r => r.type === 'reservation')).toBe(true)
    })

    it('should filter events only', () => {
      const events = store.eventsOnly
      expect(events).toHaveLength(1)
      expect(events[0].type).toBe('event')
    })

    it('should group reservations by status', () => {
      const grouped = store.reservationsByStatus
      expect(grouped.confirmed).toHaveLength(2)
      expect(grouped.pending).toHaveLength(1)
    })

    it('should calculate total reservations', () => {
      expect(store.totalReservations).toBe(3)
    })

    it('should identify if has pending reservations', () => {
      expect(store.hasPendingReservations).toBe(true)
      
      // Supprimer les réservations pending
      store.calendarEvents = store.calendarEvents.filter(
        e => e.extendedProps?.status !== 'pending'
      )
      expect(store.hasPendingReservations).toBe(false)
    })
  })

  describe('Mutations d\'état', () => {
    it('should set selected reservation', () => {
      const reservation = { id: 1, title: 'Test Reservation' }
      store.setSelectedReservation(reservation)
      expect(store.selectedReservation).toEqual(reservation)
    })

    it('should clear selected reservation', () => {
      store.selectedReservation = { id: 1 }
      store.clearSelectedReservation()
      expect(store.selectedReservation).toBeNull()
    })

    it('should update filters', () => {
      const newFilters = { status: 'confirmed', customer: 'John' }
      store.updateFilters(newFilters)
      expect(store.filters).toEqual(newFilters)
    })

    it('should clear error', () => {
      store.error = 'Some error'
      store.clearError()
      expect(store.error).toBeNull()
    })
  })

  describe('Actions composées', () => {
    it('should refresh data completely', async () => {
      const mockEvents = [{ id: 1, title: 'Event' }]
      mockAdminApi.getCalendarEvents.mockResolvedValue({ data: mockEvents })

      await store.refreshData()

      expect(store.calendarEvents).toEqual(mockEvents)
      expect(store.error).toBeNull()
    })

    it('should apply filters and reload', async () => {
      const filters = { status: 'pending' }
      mockAdminApi.getCalendarEvents.mockResolvedValue({ data: [] })

      await store.applyFilters(filters)

      expect(store.filters).toEqual(filters)
      expect(mockAdminApi.getCalendarEvents).toHaveBeenCalledWith(filters)
    })
  })
})

// tests/stores/auth.test.js
import { useAuthStore } from '@/shared/stores/auth'

describe('useAuthStore', () => {
  let store

  beforeEach(() => {
    setActivePinia(createPinia())
    store = useAuthStore()
    
    // Clear localStorage
    localStorage.clear()
  })

  describe('État initial', () => {
    it('should have correct initial state', () => {
      expect(store.user).toBeNull()
      expect(store.token).toBeNull()
      expect(store.isAuthenticated).toBe(false)
      expect(store.loading).toBe(false)
    })
  })

  describe('Actions d\'authentification', () => {
    it('should login successfully', async () => {
      const loginData = { email: 'admin@test.com', password: 'password' }
      const mockResponse = {
        user: { id: 1, name: 'Admin', email: 'admin@test.com' },
        token: 'mock-token-123'
      }

      // Mock API login
      const mockLogin = vi.fn().mockResolvedValue(mockResponse)
      store.api = { login: mockLogin }

      await store.login(loginData)

      expect(store.user).toEqual(mockResponse.user)
      expect(store.token).toBe(mockResponse.token)
      expect(store.isAuthenticated).toBe(true)
      expect(localStorage.getItem('auth_token')).toBe(mockResponse.token)
    })

    it('should handle login errors', async () => {
      const mockLogin = vi.fn().mockRejectedValue(new Error('Invalid credentials'))
      store.api = { login: mockLogin }

      await expect(store.login({})).rejects.toThrow('Invalid credentials')
      
      expect(store.user).toBeNull()
      expect(store.token).toBeNull()
      expect(store.isAuthenticated).toBe(false)
    })

    it('should logout and clear data', () => {
      // Set authenticated state
      store.user = { id: 1, name: 'Test' }
      store.token = 'test-token'
      localStorage.setItem('auth_token', 'test-token')

      store.logout()

      expect(store.user).toBeNull()
      expect(store.token).toBeNull()
      expect(store.isAuthenticated).toBe(false)
      expect(localStorage.getItem('auth_token')).toBeNull()
    })
  })

  describe('Restauration de session', () => {
    it('should restore session from localStorage', async () => {
      const storedToken = 'stored-token-123'
      const mockUser = { id: 1, name: 'Restored User' }
      
      localStorage.setItem('auth_token', storedToken)
      
      const mockGetProfile = vi.fn().mockResolvedValue(mockUser)
      store.api = { getProfile: mockGetProfile }

      await store.restoreSession()

      expect(store.token).toBe(storedToken)
      expect(store.user).toEqual(mockUser)
      expect(store.isAuthenticated).toBe(true)
    })

    it('should handle invalid stored token', async () => {
      localStorage.setItem('auth_token', 'invalid-token')
      
      const mockGetProfile = vi.fn().mockRejectedValue(new Error('Unauthorized'))
      store.api = { getProfile: mockGetProfile }

      await store.restoreSession()

      expect(store.token).toBeNull()
      expect(store.user).toBeNull()
      expect(store.isAuthenticated).toBe(false)
      expect(localStorage.getItem('auth_token')).toBeNull()
    })
  })

  describe('Getters', () => {
    it('should check if user has role', () => {
      store.user = {
        id: 1,
        roles: ['admin', 'manager']
      }

      expect(store.hasRole('admin')).toBe(true)
      expect(store.hasRole('user')).toBe(false)
    })

    it('should check if user has permission', () => {
      store.user = {
        id: 1,
        permissions: ['create_reservation', 'view_dashboard']
      }

      expect(store.hasPermission('create_reservation')).toBe(true)
      expect(store.hasPermission('delete_user')).toBe(false)
    })

    it('should return user display name', () => {
      store.user = { name: 'John', last_name: 'Doe' }
      expect(store.userDisplayName).toBe('John Doe')

      store.user = { name: 'John' }
      expect(store.userDisplayName).toBe('John')

      store.user = null
      expect(store.userDisplayName).toBe('')
    })
  })
})

// tests/stores/notifications.test.js
import { useNotificationsStore } from '@/shared/stores/notifications'

describe('useNotificationsStore', () => {
  let store

  beforeEach(() => {
    setActivePinia(createPinia())
    store = useNotificationsStore()
  })

  describe('État initial', () => {
    it('should have correct initial state', () => {
      expect(store.notifications).toEqual([])
      expect(store.unreadCount).toBe(0)
      expect(store.loading).toBe(false)
    })
  })

  describe('Gestion des notifications', () => {
    it('should add new notification', () => {
      const notification = {
        id: 1,
        title: 'Nouvelle réservation',
        message: 'Une nouvelle réservation a été créée',
        type: 'success',
        read: false
      }

      store.addNotification(notification)

      expect(store.notifications).toContain(notification)
      expect(store.unreadCount).toBe(1)
    })

    it('should mark notification as read', () => {
      const notification = { id: 1, read: false }
      store.notifications = [notification]

      store.markAsRead(1)

      expect(store.notifications[0].read).toBe(true)
      expect(store.unreadCount).toBe(0)
    })

    it('should mark all notifications as read', () => {
      store.notifications = [
        { id: 1, read: false },
        { id: 2, read: false },
        { id: 3, read: true }
      ]

      store.markAllAsRead()

      expect(store.notifications.every(n => n.read)).toBe(true)
      expect(store.unreadCount).toBe(0)
    })

    it('should remove notification', () => {
      store.notifications = [
        { id: 1, title: 'Test 1' },
        { id: 2, title: 'Test 2' }
      ]

      store.removeNotification(1)

      expect(store.notifications).toHaveLength(1)
      expect(store.notifications[0].id).toBe(2)
    })

    it('should clear all notifications', () => {
      store.notifications = [
        { id: 1, title: 'Test 1' },
        { id: 2, title: 'Test 2' }
      ]

      store.clearAll()

      expect(store.notifications).toEqual([])
      expect(store.unreadCount).toBe(0)
    })
  })

  describe('Getters', () => {
    beforeEach(() => {
      store.notifications = [
        { id: 1, read: false, type: 'success' },
        { id: 2, read: false, type: 'error' },
        { id: 3, read: true, type: 'info' },
        { id: 4, read: false, type: 'warning' }
      ]
    })

    it('should count unread notifications', () => {
      expect(store.unreadCount).toBe(3)
    })

    it('should get unread notifications', () => {
      const unread = store.unreadNotifications
      expect(unread).toHaveLength(3)
      expect(unread.every(n => !n.read)).toBe(true)
    })

    it('should filter by notification type', () => {
      const errors = store.getByType('error')
      expect(errors).toHaveLength(1)
      expect(errors[0].type).toBe('error')
    })

    it('should get recent notifications', () => {
      // Ajouter des timestamps
      store.notifications = store.notifications.map((n, index) => ({
        ...n,
        created_at: new Date(Date.now() - index * 1000 * 60).toISOString()
      }))

      const recent = store.recentNotifications
      expect(recent).toHaveLength(4) // Toutes sont récentes dans ce test
    })
  })

  describe('Actions asynchrones', () => {
    it('should fetch notifications from API', async () => {
      const mockNotifications = [
        { id: 1, title: 'Test 1', read: false },
        { id: 2, title: 'Test 2', read: true }
      ]

      const mockApi = {
        getNotifications: vi.fn().mockResolvedValue({ data: mockNotifications })
      }
      store.api = mockApi

      await store.fetchNotifications()

      expect(store.notifications).toEqual(mockNotifications)
      expect(store.unreadCount).toBe(1)
    })

    it('should handle API errors', async () => {
      const mockApi = {
        getNotifications: vi.fn().mockRejectedValue(new Error('API Error'))
      }
      store.api = mockApi

      await store.fetchNotifications()

      expect(store.notifications).toEqual([])
      expect(store.error).toBe('API Error')
    })
  })
})