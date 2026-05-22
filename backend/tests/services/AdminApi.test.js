// tests/services/AdminApi.test.js
import { describe, it, expect, vi, beforeEach } from 'vitest'
import AdminApi from '@/services/AdminApi'

// Mock axios
const mockAxios = {
  get: vi.fn(),
  post: vi.fn(),
  put: vi.fn(),
  delete: vi.fn()
}

vi.mock('axios', () => ({
  default: {
    create: () => mockAxios
  }
}))

describe('AdminApi', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  describe('Calendar Events', () => {
    it('should fetch calendar events', async () => {
      const mockEvents = [
        { id: 1, title: 'Event 1', start: '2024-03-15' },
        { id: 2, title: 'Event 2', start: '2024-03-16' }
      ]

      mockAxios.get.mockResolvedValue({ data: mockEvents })

      const result = await AdminApi.getCalendarEvents()

      expect(mockAxios.get).toHaveBeenCalledWith('/admin/calendar/events')
      expect(result.data).toEqual(mockEvents)
    })

    it('should fetch calendar events with filters', async () => {
      const filters = { status: 'confirmed', start: '2024-03-01', end: '2024-03-31' }
      mockAxios.get.mockResolvedValue({ data: [] })

      await AdminApi.getCalendarEvents(filters)

      expect(mockAxios.get).toHaveBeenCalledWith('/admin/calendar/events', { params: filters })
    })
  })

  describe('Reservations CRUD', () => {
    it('should create reservation', async () => {
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

      mockAxios.post.mockResolvedValue({ data: mockResponse })

      const result = await AdminApi.createReservation(reservationData)

      expect(mockAxios.post).toHaveBeenCalledWith('/admin/reservations', reservationData)
      expect(result.data).toEqual(mockResponse)
    })

    it('should update reservation', async () => {
      const updateData = { status: 'confirmed', amount: '500.00' }
      const mockResponse = { 
        success: true, 
        reservation: { id: 1, ...updateData } 
      }

      mockAxios.put.mockResolvedValue({ data: mockResponse })

      const result = await AdminApi.updateReservation(1, updateData)

      expect(mockAxios.put).toHaveBeenCalledWith('/admin/reservations/1', updateData)
      expect(result.data).toEqual(mockResponse)
    })

    it('should delete reservation', async () => {
      mockAxios.delete.mockResolvedValue({ data: { success: true } })

      const result = await AdminApi.deleteReservation(1)

      expect(mockAxios.delete).toHaveBeenCalledWith('/admin/reservations/1')
      expect(result.data.success).toBe(true)
    })
  })

  describe('Error Handling', () => {
    it('should handle network errors', async () => {
      const networkError = new Error('Network Error')
      mockAxios.get.mockRejectedValue(networkError)

      await expect(AdminApi.getCalendarEvents()).rejects.toThrow('Network Error')
    })

    it('should handle API errors', async () => {
      const apiError = {
        response: {
          status: 400,
          data: { message: 'Validation failed' }
        }
      }

      mockAxios.post.mockRejectedValue(apiError)

      await expect(AdminApi.createReservation({})).rejects.toEqual(apiError)
    })
  })
})