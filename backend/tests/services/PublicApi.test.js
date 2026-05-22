// tests/services/PublicApi.test.js
import { describe, it, expect, vi, beforeEach } from 'vitest'
import { publicApi } from '@/services/PublicApi'

// Mock axios
const mockAxios = {
  get: vi.fn(),
  post: vi.fn()
}

vi.mock('axios', () => ({
  default: {
    create: () => mockAxios
  }
}))

describe('PublicApi', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  describe('Products', () => {
    it('should fetch products', async () => {
      const mockProducts = [
        { id: 1, name: 'Product 1', price: 100 },
        { id: 2, name: 'Product 2', price: 200 }
      ]

      mockAxios.get.mockResolvedValue({ data: mockProducts })

      const result = await publicApi.getProducts()

      expect(mockAxios.get).toHaveBeenCalledWith('/products')
      expect(result.data).toEqual(mockProducts)
    })

    it('should fetch products with filters', async () => {
      const filters = { type: 'Room', status: 'active' }
      mockAxios.get.mockResolvedValue({ data: [] })

      await publicApi.getProducts(filters)

      expect(mockAxios.get).toHaveBeenCalledWith('/products', { params: filters })
    })
  })

  describe('Quote Management', () => {
    it('should save quote', async () => {
      const quoteData = {
        email: 'test@test.com',
        contact: { name: 'Test', last_name: 'User' },
        dates: { checkin: '2024-03-15', checkout: '2024-03-18' },
        product_ids: [1, 2]
      }

      const mockResponse = {
        success: true,
        quote_request: { id: 1, quote_reference: 'QT-001' }
      }

      mockAxios.post.mockResolvedValue({ data: mockResponse })

      const result = await publicApi.saveQuote(quoteData)

      expect(mockAxios.post).toHaveBeenCalledWith('/quote-requests', quoteData)
      expect(result).toEqual(mockResponse)
    })

    it('should create stripe session', async () => {
      const mockResponse = {
        success: true,
        checkout_url: 'https://checkout.stripe.com/test'
      }

      mockAxios.post.mockResolvedValue({ data: mockResponse })

      const result = await publicApi.createStripeSession(1)

      expect(mockAxios.post).toHaveBeenCalledWith('/quote-requests/1/payment', {})
      expect(result).toEqual(mockResponse)
    })

    it('should request advice', async () => {
      const adviceData = {
        contact: { name: 'Test', email: 'test@test.com' },
        message: 'Need help choosing'
      }

      const mockResponse = {
        success: true,
        message: 'Demande envoyée'
      }

      mockAxios.post.mockResolvedValue({ data: mockResponse })

      const result = await publicApi.requestAdvice(adviceData)

      expect(mockAxios.post).toHaveBeenCalledWith('/advice-requests', adviceData)
      expect(result).toEqual(mockResponse)
    })
  })
})