import httpClient from './httpClient'

class UsersApi {
  /**
   * Récupérer tous les utilisateurs
   */
  static async getAll() {
    try {
      const response = await httpClient.get('/admin/users')
      return Array.isArray(response.data)
        ? response.data
        : response.data['hydra:member'] || []
    } catch (error) {
      console.error('Erreur lors du chargement des utilisateurs:', error)
      throw error
    }
  }

  /**
   * Récupérer les rôles (version légère)
   */
  static async getRoles() {
    try {
      const response = await httpClient.get('/roles')
      
      if (response.data && Array.isArray(response.data.data)) {
        return response.data.data
      }
      
      return Array.isArray(response.data) 
        ? response.data 
        : response.data['hydra:member'] || []
        
    } catch (error) {
      console.error('Erreur lors du chargement des rôles:', error)
      throw error
    }
  }

  /**
   * Récupérer un utilisateur par ID
   */
  static async getById(userId) {
    try {
      const response = await httpClient.get(`/admin/users/${userId}`)
      return response.data
    } catch (error) {
      console.error('Erreur lors du chargement de l\'utilisateur:', error)
      throw error
    }
  }

  /**
   * Créer un utilisateur
   */
  static async create(payload) {
    try {
      const response = await httpClient.post('/admin/users', payload)
      return response.data
    } catch (error) {
      console.error('Erreur lors de la création:', error)
      throw error
    }
  }

  /**
   * Mettre à jour un utilisateur
   */
  static async update(userId, payload) {
    try {
      const response = await httpClient.patch(`/admin/users/${userId}`, payload)
      return response.data
    } catch (error) {
      console.error('Erreur lors de la mise à jour:', error)
      throw error
    }
  }

  /**
   * Supprimer un utilisateur
   */
  static async delete(userId) {
    try {
      await httpClient.delete(`/admin/users/${userId}`)
      return true
    } catch (error) {
      console.error('Erreur lors de la suppression:', error)
      throw error
    }
  }
}

export default UsersApi