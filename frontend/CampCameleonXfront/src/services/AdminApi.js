// ===================================
// FICHIER: frontend/CampCameleonXfront/src/services/AdminApi.js  
// CORRIGÉ - Service API pour les appels admin
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

    async getReservationsForCalendar({ start, end }) {
        try {
            const res = await axios.get(`${this.baseURL}/admin/calendar/reservations`, { params: { start, end } })
            return res.data || []
        } catch (e) {
            throw this.handleError(e)
        }
    }

    async getReservation(id) {
        const response = await axios.get(`${this.baseURL}/admin/reservations/${id}`)
        return response.data
    }

    async createReservation(data) {
        const response = await axios.post(`${this.baseURL}/admin/reservations`, data)
        return response.data
    }

    async updateReservation(id, payload) {
        try {
            const res = await axios.patch(`${this.baseURL}/admin/reservations/${id}`, payload, {
                headers: { 'Content-Type': 'application/merge-patch+json' }
            })
            return res.data
        } catch (e) {
            throw this.handleError(e)
        }
    }

    async deleteReservation(id) {
        const response = await axios.delete(`${this.baseURL}/admin/reservations/${id}`)
        return response.data
    }

    // =============================
    // ÉVÉNEMENTS GÉNÉRIQUES - NOUVEAU
    // =============================

    async createEvent(data) {
        const response = await axios.post(`${this.baseURL}/admin/events`, data)
        return response.data
    }

    async updateEvent(id, data) {
        const response = await axios.put(`${this.baseURL}/admin/events/${id}`, data)
        return response.data
    }

    async deleteEvent(id) {
        const response = await axios.delete(`${this.baseURL}/admin/events/${id}`)
        return response.data
    }

    // =============================
    // CALENDRIER UNIFIÉ - NOUVEAU
    // =============================

    async getCalendarEvents(startDate, endDate) {
        const params = new URLSearchParams({
            start: startDate,
            end: endDate
        })
        
        const response = await axios.get(`${this.baseURL}/admin/calendar/events?${params}`)
        return response.data
    }

    // =============================
    // ROUTAGE INTELLIGENT - NOUVEAU
    // =============================

    async createEventOrReservation(data) {
        // Si c'est une réservation (avec customer data)
        if (data.type === 'reservation' || data.customerName) {
            return this.createReservation(data)
        }

        // Sinon, c'est un événement générique
        return this.createEvent(data)
    }

    async updateEventOrReservation(id, data) {
        // Détecter le type depuis l'ID ou les données
        if (data.type === 'reservation' || data.customerName || id.startsWith('reservation_')) {
            const realId = id.replace('reservation_', '') // Enlever le préfixe si présent
            return this.updateReservation(realId, data)
        }

        const realId = id.replace('event_', '') // Enlever le préfixe si présent
        return this.updateEvent(realId, data)
    }

    async deleteEventOrReservation(id, type) {
        if (type === 'reservation' || id.startsWith('reservation_')) {
            const realId = id.replace('reservation_', '')
            return this.deleteReservation(realId)
        }

        const realId = id.replace('event_', '')
        return this.deleteEvent(realId)
    }

    // =============================
    // DASHBOARD & STATS
    // =============================

    async getDashboardStats() {
        const response = await axios.get(`${this.baseURL}/admin/dashboard/stats`)
        return response.data
    }

    async getNotifications(limit = 10) {
        const response = await axios.get(`${this.baseURL}/admin/notifications?limit=${limit}`)
        return response.data?.['hydra:member'] || response.data || []
    }

    async markNotificationAsRead(notificationId) {
        const response = await axios.patch(`${this.baseURL}/admin/notifications/${notificationId}/read`)
        return response.data
    }

    // =============================
    // GESTION D'ERREURS
    // =============================

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