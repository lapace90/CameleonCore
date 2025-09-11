// src/services/UsersApi.js - VERSION CORRIGÉE
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
   * 🚀 OPTIMISATION : Récupérer rôles avec mode light (ultra rapide)
   */
  static async getRoles() {
    try {
      // 🚀 MODE LIGHT : Juste id, name, description - 10x plus rapide !
      const response = await axios.get('/api/roles?mode=light')
      
      // Format: { data: [...], meta: {...} }
      if (response.data && Array.isArray(response.data.data)) {
        return response.data.data
      }
      
      // Fallback pour autres formats
      return Array.isArray(response.data) 
        ? response.data 
        : response.data['hydra:member'] || []
        
    } catch (error) {
      console.error('Erreur lors du chargement des rôles:', error)
      throw error
    }
  }

  /**
   * 🔧 NOUVELLE : Récupérer rôles complets (pour interface de gestion)
   */
  static async getRolesFull() {
    try {
      const response = await axios.get('/api/roles?mode=full')
      
      if (response.data && Array.isArray(response.data.data)) {
        return response.data.data
      }
      
      return Array.isArray(response.data) 
        ? response.data 
        : response.data['hydra:member'] || []
        
    } catch (error) {
      console.error('Erreur lors du chargement des rôles complets:', error)
      throw error
    }
  }

  /**
   * Récupérer un utilisateur par ID - fetchUser()
   */
 static async getById(userId) {
    try {
      console.log('🔍 UsersApi.getById - Début requête:', userId)
      
      const response = await axios.get(`/api/admin/users/${userId}`)
      
      // 🔍 DEBUG 1: Réponse HTTP brute
      console.log('🔍 UsersApi - Réponse HTTP status:', response.status)
      console.log('🔍 UsersApi - Headers réponse:', response.headers)
      console.log('🔍 UsersApi - response.data BRUT:', JSON.stringify(response.data, null, 2))
      
      // 🔍 DEBUG 2: Structure des données
      console.log('🔍 UsersApi - Type de response.data:', typeof response.data)
      console.log('🔍 UsersApi - Object.keys(response.data):', Object.keys(response.data))
      
      // 🔍 DEBUG 3: Vérifier spécifiquement les rôles
      console.log('🔍 UsersApi - response.data.role:', response.data.role)
      console.log('🔍 UsersApi - response.data.roles:', response.data.roles)
      console.log('🔍 UsersApi - response.data.additional_roles:', response.data.additional_roles)
      console.log('🔍 UsersApi - response.data.additionalRoles:', response.data.additionalRoles)
      
      // 🔍 DEBUG 4: Tous les champs qui contiennent "role"
      const roleFields = Object.keys(response.data).filter(key => 
        key.toLowerCase().includes('role')
      )
      console.log('🔍 UsersApi - Champs contenant "role":', roleFields)
      roleFields.forEach(field => {
        console.log(`🔍 UsersApi - ${field}:`, response.data[field])
      })
      
      return response.data
    } catch (error) {
      console.error('❌ UsersApi.getById - Erreur:', error)
      console.error('❌ UsersApi.getById - Error response:', error.response?.data)
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
   * Mettre à jour un utilisateur - submitForm() PATCH
   */
  static async update(userId, payload) {
    try {
      const response = await axios.patch(`/api/admin/users/${userId}`, payload)
      return response.data
    } catch (error) {
      console.error('Erreur lors de la mise à jour:', error)
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

  // ==========================================
  // 🔧 MÉTHODES SUPPLÉMENTAIRES OPTIONNELLES
  // ==========================================

  /**
   * 🔧 ALTERNATIVE : Si tu veux utiliser RolesApi directement
   * Cette méthode peut remplacer getRoles() ci-dessus
   */
  static async getRolesFromRolesApi() {
    try {
      // Réutiliser RolesApi qui fonctionne déjà
      const { default: RolesApi } = await import('./RolesApi')
      const response = await RolesApi.getAll()
      
      // RolesApi retourne { data: [...], meta: {...} }
      return Array.isArray(response.data) ? response.data : []
    } catch (error) {
      console.error('Erreur lors du chargement des rôles via RolesApi:', error)
      throw error
    }
  }

  /**
   * Permissions (pour plus tard si besoin, mais tu ne veux pas de liens directs)
   * ⚠️ NOTE : Selon ton architecture RBAC, les permissions directes 
   *     aux users sont limitées aux super-admins
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
}

export default UsersApi