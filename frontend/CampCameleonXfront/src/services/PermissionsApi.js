import httpClient from './httpClient'

class PermissionsApi {
  // Route existante (custom)
  static async getGrouped() {
    try {
      const response = await httpClient.get('/admin/permissions/grouped')
      return response.data
    } catch (error) {
      console.error('Erreur lors du chargement des permissions groupées:', error)
      throw error
    }
  }

  // Routes API Platform standard
  static async getAll() {
    try {
      const response = await httpClient.get('/permissions')
      return response.data
    } catch (error) {
      console.error('Erreur lors du chargement des permissions:', error)
      throw error
    }
  }

  static async getById(id) {
    try {
      const response = await httpClient.get(`/permissions/${id}`)
      return response.data
    } catch (error) {
      console.error(`Erreur lors du chargement de la permission ${id}:`, error)
      throw error
    }
  }

  static async create(permissionData) {
    try {
      const response = await httpClient.post('/permissions', permissionData)
      return response.data
    } catch (error) {
      console.error('Erreur lors de la création de la permission:', error)
      throw error
    }
  }

  static async update(id, permissionData) {
    try {
      const response = await httpClient.put(`/permissions/${id}`, permissionData)
      return response.data
    } catch (error) {
      console.error(`Erreur lors de la mise à jour de la permission ${id}:`, error)
      throw error
    }
  }

  static async delete(id) {
    try {
      await httpClient.delete(`/permissions/${id}`)
    } catch (error) {
      console.error(`Erreur lors de la suppression de la permission ${id}:`, error)
      throw error
    }
  }
}

export default PermissionsApi