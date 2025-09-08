import axios from 'axios'

class PermissionsApi {
  static async getGrouped() {
    try {
      const response = await axios.get('/api/admin/permissions/grouped')
      return response.data
    } catch (error) {
      console.error('Erreur lors du chargement des permissions groupées:', error)
      throw error
    }
  }
}

export default PermissionsApi