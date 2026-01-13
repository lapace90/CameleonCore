// frontend/CampCameleonXfront/src/services/PermissionsApi.js
// SERVICE SIMPLE basé sur tes vraies routes API Platform

import axios from 'axios'

class PermissionsApi {
  // Route existante (custom)
  static async getGrouped() {
    try {
      const response = await axios.get('/api/admin/permissions/grouped')
      return response.data
    } catch (error) {
      console.error('Erreur lors du chargement des permissions groupées:', error)
      throw error
    }
  }

  // Routes API Platform standard
  static async getAll() {
    try {
      const response = await axios.get('/api/permissions')
      return response.data
    } catch (error) {
      console.error('Erreur lors du chargement des permissions:', error)
      throw error
    }
  }

  static async getById(id) {
    try {
      const response = await axios.get(`/api/permissions/${id}`)
      return response.data
    } catch (error) {
      console.error(`Erreur lors du chargement de la permission ${id}:`, error)
      throw error
    }
  }

  static async create(permissionData) {
    try {
      const response = await axios.post('/api/permissions', permissionData)
      return response.data
    } catch (error) {
      console.error('Erreur lors de la création de la permission:', error)
      throw error
    }
  }

  static async update(id, permissionData) {
    try {
      const response = await axios.put(`/api/permissions/${id}`, permissionData)
      return response.data
    } catch (error) {
      console.error(`Erreur lors de la mise à jour de la permission ${id}:`, error)
      throw error
    }
  }

  static async delete(id) {
    try {
      await axios.delete(`/api/permissions/${id}`)
    } catch (error) {
      console.error(`Erreur lors de la suppression de la permission ${id}:`, error)
      throw error
    }
  }
}

export default PermissionsApi