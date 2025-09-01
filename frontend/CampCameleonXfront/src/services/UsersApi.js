// src/services/UsersApi.js
import axios from 'axios'

class UsersApi {
  // ==========================================
  // MÉTHODES DE BASE (Users.vue)
  // ==========================================

  /**
   * Récupérer tous les utilisateurs - fetchUsers()
   */
  static async getAll() {
    try {
      const response = await axios.get('/api/admin/users')
      return Array.isArray(response.data)
        ? response.data
        : response.data['hydra:member'] || []
    } catch (error) {
      console.error('Erreur lors du chargement des utilisateurs:', error)
      throw error
    }
  }

  /**
   * Récupérer tous les rôles - fetchRoles()
   */
  static async getRoles() {
    try {
      const response = await axios.get('/api/roles')
      return Array.isArray(response.data)
        ? response.data
        : response.data['hydra:member'] || []
    } catch (error) {
      console.error('Erreur lors du chargement des rôles:', error)
      throw error
    }
  }

  /**
   * Basculer le statut d'un utilisateur - toggleUserStatus()
   */
  static async toggleStatus(userId, newStatus) {
    try {
      await axios.patch(`/api/admin/users/${userId}/status`, { status: newStatus })
      return true
    } catch (error) {
      console.error('Erreur lors du changement de statut:', error)
      throw error
    }
  }

  /**
   * Supprimer un utilisateur - deleteUser()
   */
  static async delete(userId) {
    try {
      await axios.delete(`/api/admin/users/${userId}`)
      return true
    } catch (error) {
      console.error('Erreur lors de la suppression:', error)
      throw error
    }
  }

  /**
   * Action en masse - confirmBulkAction()
   */
  static async bulkAction(payload) {
    try {
      await axios.post('/api/admin/users/bulk-action', payload)
      return true
    } catch (error) {
      console.error('Erreur lors de l\'action en masse:', error)
      throw error
    }
  }

  // ==========================================
  // MÉTHODES UserDetail.vue
  // ==========================================

  /**
   * Récupérer un utilisateur par ID - fetchUser()
   */
  static async getById(userId) {
    try {
      const response = await axios.get(`/api/admin/users/${userId}`)
      return response.data
    } catch (error) {
      console.error('Erreur lors du chargement de l\'utilisateur:', error)
      throw error
    }
  }

  /**
   * Réinitialiser le mot de passe - resetPassword()
   */
  static async resetPassword(userId) {
    try {
      await axios.post(`/api/admin/users/${userId}/reset-password`)
      return true
    } catch (error) {
      console.error('Erreur lors de la réinitialisation:', error)
      throw error
    }
  }

  /**
   * Envoyer email de vérification - sendVerificationEmail()
   */
  static async sendVerificationEmail(userId) {
    try {
      await axios.post(`/api/admin/users/${userId}/send-verification`)
      return true
    } catch (error) {
      console.error('Erreur lors de l\'envoi:', error)
      throw error
    }
  }

  /**
   * Dupliquer un utilisateur - duplicateUser()
   */
  static async duplicate(userId) {
    try {
      const response = await axios.post(`/api/admin/users/${userId}/duplicate`)
      return response.data
    } catch (error) {
      console.error('Erreur lors de la duplication:', error)
      throw error
    }
  }

  // ==========================================
  // MÉTHODES UserForm.vue  
  // ==========================================

  /**
   * Récupérer permissions - loadRolesAndPermissions()
   */
  static async getPermissions() {
    try {
      const response = await axios.get('/api/permissions')
      return Array.isArray(response.data) 
        ? response.data 
        : response.data['hydra:member'] || []
    } catch (error) {
      console.error('Erreur lors du chargement des permissions:', error)
      throw error
    }
  }

  /**
   * Créer un utilisateur - submitForm() POST
   */
  static async create(payload) {
    try {
      const response = await axios.post('/api/admin/users', payload)
      return response.data
    } catch (error) {
      console.error('Erreur lors de la création:', error)
      throw error
    }
  }

  /**
   * Mettre à jour un utilisateur - submitForm() PUT
   */
  static async update(userId, payload) {
    try {
      const response = await axios.put(`/api/admin/users/${userId}`, payload)
      return response.data
    } catch (error) {
      console.error('Erreur lors de la mise à jour:', error)
      throw error
    }
  }
}

export default UsersApi