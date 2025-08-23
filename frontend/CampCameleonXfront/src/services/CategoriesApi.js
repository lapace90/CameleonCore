// src/services/CategoriesApi.js - FIX SIMPLE
import axios from 'axios'

class CategoriesApi {
  static defaultHeaders = {
    'Accept': 'application/json',
    'Content-Type': 'application/json'
  }

  static async getAll() {
    try {
      const response = await axios.get('/api/categories', {
        headers: this.defaultHeaders
      })
      return response.data
    } catch (error) {
      console.error('Erreur categories:', error)
      throw error
    }
  }

  static async getByType(type) {
    try {
      const response = await axios.get(`/api/categories?type=${type}`, {
        headers: this.defaultHeaders
      })
      return response.data
    } catch (error) {
      throw error
    }
  }

  static async getById(id) {
    try {
      const response = await axios.get(`/api/categories/${id}`, {
        headers: this.defaultHeaders
      })
      return response.data
    } catch (error) {
      throw error
    }
  }

  static async create(data) {
    try {
      const response = await axios.post('/api/categories', data, {
        headers: this.defaultHeaders
      })
      return response.data
    } catch (error) {
      throw error
    }
  }

  static async update(id, data) {
    try {
      const response = await axios.put(`/api/categories/${id}`, data, {
        headers: this.defaultHeaders
      })
      return response.data
    } catch (error) {
      throw error
    }
  }

  static async delete(id) {
    try {
      await axios.delete(`/api/categories/${id}`, {
        headers: this.defaultHeaders
      })
    } catch (error) {
      throw error
    }
  }
}

export default CategoriesApi