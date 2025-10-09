import axios from 'axios'

class ReviewsApi {
  /**
   *  Récupérer toutes les reviews publiées (PUBLIC)
   */
  static async getPublished(params = {}) {
    try {
      const response = await axios.get('/api/reviews', { params })
      return response.data
    } catch (error) {
      console.error('❌ Erreur lors du chargement des avis:', error)
      throw error
    }
  }

  /**
   *  Récupérer une review par ID (PUBLIC si publiée, ADMIN sinon)
   */
  static async getById(id) {
    try {
      const response = await axios.get(`/api/reviews/${id}`)
      return response.data
    } catch (error) {
      console.error(`❌ Erreur lors du chargement de l'avis ${id}:`, error)
      throw error
    }
  }

  /**
   *  Créer une nouvelle review (PUBLIC)
   */
  static async create(reviewData) {
    try {
      const response = await axios.post('/api/reviews', reviewData)
      return response.data
    } catch (error) {
      console.error('❌ Erreur lors de la création de l\'avis:', error)
      throw error
    }
  }

  /**
   * 🔒 ADMIN - Récupérer toutes les reviews (y compris non publiées)
   */
  static async getAllAdmin(params = {}) {
    try {
      const response = await axios.get('/api/reviews', { params })
      return response.data
    } catch (error) {
      console.error('❌ Erreur lors du chargement des avis (admin):', error)
      throw error
    }
  }

  /**
   * 🔒 ADMIN - Mettre à jour une review
   */
  static async update(id, reviewData) {
    try {
      const response = await axios.put(`/api/reviews/${id}`, reviewData)
      return response.data
    } catch (error) {
      console.error(`❌ Erreur lors de la mise à jour de l'avis ${id}:`, error)
      throw error
    }
  }

  /**
   * 🔒 ADMIN - Supprimer une review
   */
  static async delete(id) {
    try {
      await axios.delete(`/api/reviews/${id}`)
      return true
    } catch (error) {
      console.error(`❌ Erreur lors de la suppression de l'avis ${id}:`, error)
      throw error
    }
  }

  /**
   * 🔒 ADMIN - Publier une review (approuver)
   */
  static async publish(id) {
    try {
      const response = await axios.put(`/api/reviews/${id}`, {
        status: 'approved',
        is_published: true
      })
      return response.data
    } catch (error) {
      console.error(`❌ Erreur lors de la publication de l'avis ${id}:`, error)
      throw error
    }
  }

  /**
   * 🔒 ADMIN - Rejeter une review
   */
  static async reject(id) {
    try {
      const response = await axios.put(`/api/reviews/${id}`, {
        status: 'rejected',
        is_published: false
      })
      return response.data
    } catch (error) {
      console.error(`❌ Erreur lors du rejet de l'avis ${id}:`, error)
      throw error
    }
  }

  /**
   * 🔒 ADMIN - Mettre en avant une review
   */
  static async toggleFeatured(id, featured) {
    try {
      const response = await axios.put(`/api/reviews/${id}`, {
        featured
      })
      return response.data
    } catch (error) {
      console.error(`❌ Erreur lors de la mise en avant de l'avis ${id}:`, error)
      throw error
    }
  }
}

export default ReviewsApi