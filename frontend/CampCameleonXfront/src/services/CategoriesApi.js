import httpClient from './httpClient'

class CategoriesApi {
  // Cache semplice con TTL
  static cache = new Map()
  static CACHE_TTL = 5 * 60 * 1000 // 5 minuti

  static async getAll() {
    const cacheKey = 'all_categories'
    const cached = this.cache.get(cacheKey)
    
    if (cached && Date.now() - cached.timestamp < this.CACHE_TTL) {
      return cached.data
    }

    try {
      const response = await httpClient.get('/categories')
      const data = response.data
      
      // Salva in cache
      this.cache.set(cacheKey, {
        data,
        timestamp: Date.now()
      })
      
      return data
    } catch (error) {
      console.error('Errore categories:', error)
      throw error
    }
  }

  static async getByType(type) {
    try {
      const response = await httpClient.get('/categories', {
        params: { type }
      })
      return response.data
    } catch (error) {
      throw error
    }
  }

  static async getById(id) {
    try {
      const response = await httpClient.get(`/categories/${id}`)
      return response.data
    } catch (error) {
      throw error
    }
  }

  static async create(data) {
    try {
      const response = await httpClient.post('/categories', data)
      this.clearCache() // Invalida cache dopo modifica
      return response.data
    } catch (error) {
      throw error
    }
  }

  static async update(id, data) {
    try {
      const response = await httpClient.put(`/categories/${id}`, data)
      this.clearCache() // Invalida cache dopo modifica
      return response.data
    } catch (error) {
      throw error
    }
  }

  static async delete(id) {
    try {
      await httpClient.delete(`/categories/${id}`)
      this.clearCache() // Invalida cache dopo modifica
    } catch (error) {
      throw error
    }
  }

  // Metodi di cache management
  static clearCache() {
    this.cache.clear()
  }

  static clearCacheByKey(key) {
    this.cache.delete(key)
  }
}

export default CategoriesApi