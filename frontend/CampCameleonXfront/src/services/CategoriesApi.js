// frontend/CampCameleonXfront/src/services/CategoriesApi.js
import ApiService from './ApiService'

class CategoriesApi {
  /**
   * Récupérer toutes les catégories
   */
  static async getAll() {
    try {
      const response = await ApiService.get('/categories')
      return response.data
    } catch (error) {
      console.error('Erreur lors de la récupération des catégories:', error)
      throw error
    }
  }

  /**
   * Récupérer les catégories par type
   */
  static async getByType(type) {
    try {
      const response = await ApiService.get(`/categories?type=${type}`)
      return response.data
    } catch (error) {
      console.error(`Erreur lors de la récupération des catégories ${type}:`, error)
      throw error
    }
  }

  /**
   * Récupérer une catégorie par ID
   */
  static async getById(id) {
    try {
      const response = await ApiService.get(`/categories/${id}`)
      return response.data
    } catch (error) {
      console.error(`Erreur lors de la récupération de la catégorie ${id}:`, error)
      throw error
    }
  }

  /**
   * Créer une nouvelle catégorie
   */
  static async create(categoryData) {
    try {
      const response = await ApiService.post('/categories', categoryData)
      return response.data
    } catch (error) {
      console.error('Erreur lors de la création de la catégorie:', error)
      throw error
    }
  }

  /**
   * Mettre à jour une catégorie
   */
  static async update(id, categoryData) {
    try {
      const response = await ApiService.put(`/categories/${id}`, categoryData)
      return response.data
    } catch (error) {
      console.error(`Erreur lors de la mise à jour de la catégorie ${id}:`, error)
      throw error
    }
  }

  /**
   * Supprimer une catégorie
   */
  static async delete(id) {
    try {
      await ApiService.delete(`/categories/${id}`)
    } catch (error) {
      console.error(`Erreur lors de la suppression de la catégorie ${id}:`, error)
      throw error
    }
  }

  /**
   * Obtenir les statistiques des catégories
   */
  static async getStats() {
    try {
      const response = await ApiService.get('/categories/stats')
      return response.data
    } catch (error) {
      console.error('Erreur lors de la récupération des statistiques:', error)
      throw error
    }
  }

  /**
   * Récupérer le nombre de produits par catégorie
   */
  static async getProductCounts() {
    try {
      const response = await ApiService.get('/categories/product-counts')
      return response.data
    } catch (error) {
      console.error('Erreur lors de la récupération des compteurs de produits:', error)
      throw error
    }
  }
}

export default CategoriesApi