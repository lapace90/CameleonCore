// ===================================
// FICHIER: frontend/CampCameleonXfront/src/services/AdminApi.js  
// À CRÉER - Service API pour les appels admin
// ===================================

import axios from 'axios'
import { useAuthStore } from '@/shared/stores/auth'

class AdminApi {
  constructor() {
    this.baseURL = import.meta.env.VITE_API_URL || 'http://localhost:8000/api'
    
    // Intercepteur pour ajouter le token automatiquement
    axios.interceptors.request.use((config) => {
      const authStore = useAuthStore()
      if (authStore.token) {
        config.headers.Authorization = `Bearer ${authStore.token}`
      }
      return config
    })
  }

  // =============================
  // RÉSERVATIONS & CALENDRIER
  // =============================
  
  /**
   * Récupérer les événements pour FullCalendar
   */
  async getCalendarEvents(params = {}) {
    const queryString = new URLSearchParams(params).toString()
    const url = `${this.baseURL}/reservations/calendar/events?${queryString}`
    
    console.log('📅 AdminApi.getCalendarEvents:', url)
    
    const response = await axios.get(url)
    return response.data
  }

  /**
   * CRUD Réservations
   */
// AdminApi.js
async getReservationsForCalendar({ start, end }) {
  try {
    const res = await axios.get(`${this.baseURL}/admin/calendar/reservations`, { params: { start, end } })
    return res.data || []
  } catch (e) {
    throw this.handleError(e)
  }
}

  async getReservation(id) {
    const response = await axios.get(`${this.baseURL}/reservations/calendar/${id}`)
    return response.data
  }

  async createReservation(data) {
    const response = await axios.post(`${this.baseURL}/reservations/calendar`, data)
    return response.data
  }

async updateReservation(id, payload) {
  try {
    const res = await axios.patch(`${this.baseURL}/reservations/${id}`, payload, {
      headers: { 'Content-Type': 'application/merge-patch+json' }
    })
    return res.data
  } catch (e) {
    throw this.handleError(e)
  }
}

  async deleteReservation(id) {
    const response = await axios.delete(`${this.baseURL}/reservations/calendar/${id}`)
    return response.data
  }

  // =============================
  // DASHBOARD & STATS
  // =============================
  
  /**
   * Récupérer les statistiques dashboard
   */
  async getDashboardStats() {
    const response = await axios.get(`${this.baseURL}/admin/dashboard/stats`)
    return response.data
  }

  /**
   * Récupérer les notifications admin
   */
  async getNotifications(limit = 10) {
    const response = await axios.get(`${this.baseURL}/admin/notifications?limit=${limit}`)
    return response.data?.['hydra:member'] || response.data || []
  }

  /**
   * Marquer une notification comme lue
   */
  async markNotificationAsRead(notificationId) {
    const response = await axios.patch(`${this.baseURL}/admin/notifications/${notificationId}/read`)
    return response.data
  }

  // =============================
  // GESTION D'ERREURS
  // =============================
  
  /**
   * Wrapper générique avec gestion d'erreurs
   */
  async get(endpoint) {
    try {
      const response = await axios.get(`${this.baseURL}${endpoint}`)
      return response
    } catch (error) {
      console.error(`❌ AdminApi GET ${endpoint}:`, error)
      throw this.handleError(error)
    }
  }

  async post(endpoint, data) {
    try {
      const response = await axios.post(`${this.baseURL}${endpoint}`, data)
      return response
    } catch (error) {
      console.error(`❌ AdminApi POST ${endpoint}:`, error)
      throw this.handleError(error)
    }
  }

  handleError(error) {
    if (error.response?.status === 401) {
      // Token expiré, rediriger vers login
      const authStore = useAuthStore()
      authStore.logout()
      return new Error('Session expirée')
    }
    
    return error.response?.data?.message || error.message || 'Erreur API'
  }
}

export default new AdminApi()