// ===================================
//  Service API pour les appels admin
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
    // DASHBOARD & STATS
    // =============================

    async getDashboardStats() {
        try {
            const response = await axios.get(`${this.baseURL}/admin/dashboard/stats`)
            return response.data
        } catch (error) {
            console.error('❌ Erreur stats dashboard:', error)
            throw this.handleError(error)
        }
    }

    /**
     * ✅ CORRECTION: Récupérer les notifications avec gestion d'erreur améliorée
     */
    async getNotifications(limit = 10) {
        try {
            console.log(`📡 Récupération de ${limit} notifications...`)
            const startTime = Date.now()

            const response = await axios.get(`${this.baseURL}/admin/notifications`, {
                params: { limit },
                timeout: 30000 // ✅ Timeout de 10 secondes
            })

            const duration = Date.now() - startTime
            console.log(`✅ Notifications récupérées en ${duration}ms`)

            return response.data?.['hydra:member'] || response.data || []
        } catch (error) {
            console.error('❌ Erreur récupération notifications:', error)

            if (error.code === 'ECONNABORTED') {
                throw new Error('Timeout: Le serveur met trop de temps à répondre')
            }

            throw this.handleError(error)
        }
    }

    /**
     * ✅ CORRECTION: Endpoint correct pour marquer comme lue
     */
    async markNotificationAsRead(notificationId) {
        try {
            console.log(`📝 Marquage notification ${notificationId} comme lue...`)

            const response = await axios.patch(
                `${this.baseURL}/admin/notifications/${notificationId}/mark-read`, // ✅ CORRECTION: /read → /mark-read
                {}, // Pas de body nécessaire pour un PATCH simple
                {
                    timeout: 30000, // ✅ Timeout réduit pour les actions rapides
                    headers: {
                        'Content-Type': 'application/json'
                    }
                }
            )

            console.log(`✅ Notification ${notificationId} marquée comme lue`)
            return response.data
        } catch (error) {
            console.error(`❌ Erreur marquage notification ${notificationId}:`, error)
            throw this.handleError(error)
        }
    }

    /**
     * ✅ AJOUT: Marquer toutes les notifications comme lues (batch)
     */
    async markAllNotificationsAsRead(notificationIds) {
        try {
            console.log(`📝 Marquage de ${notificationIds.length} notifications...`)

            const promises = notificationIds.map(id =>
                this.markNotificationAsRead(id).catch(error => {
                    console.warn(`⚠️ Échec marquage notification ${id}:`, error.message)
                    return null // Continuer avec les autres même si une échoue
                })
            )

            const results = await Promise.allSettled(promises)
            const successCount = results.filter(r => r.status === 'fulfilled' && r.value !== null).length

            console.log(`✅ ${successCount}/${notificationIds.length} notifications marquées`)
            return successCount
        } catch (error) {
            console.error('❌ Erreur marquage batch:', error)
            throw error
        }
    }

    // =============================
    // RÉSERVATIONS & CALENDRIER
    // =============================
    async getReservations(params = {}) {
        try {
            const queryParams = {}

            const normalized = {
                page: params.page ?? params.currentPage,
                itemsPerPage: params.itemsPerPage ?? params.perPage,
                search: params.search ?? params.query,
                status: params.status,
                startDate: params.startDate,
                endDate: params.endDate
            }

            Object.entries(normalized).forEach(([key, value]) => {
                if (value !== undefined && value !== null && value !== '') {
                    queryParams[key] = value
                }
            })

            const sortField = params.sortField || params.sort?.field
            const sortDirection = params.sortDirection || params.sort?.direction
            if (sortField) {
                queryParams[`order[${sortField}]`] = sortDirection || 'asc'
            }

            const response = await axios.get(`${this.baseURL}/admin/reservations`, {
                params: queryParams,
                timeout: 30000
            })

            const payload = response.data ?? {}
            const items = Array.isArray(payload)
                ? payload
                : payload['hydra:member'] || payload.member || payload.data || payload.items || []

            const extractPage = (url) => {
                if (!url || typeof url !== 'string') return null
                const match = url.match(/[?&]page=(\d+)/)
                return match ? Number(match[1]) : null
            }

            const hydraView = payload['hydra:view'] || payload.view || {}
            const totalItems = Number(
                payload['hydra:totalItems'] ??
                payload.total ??
                payload.meta?.total ??
                payload.pagination?.total ??
                items.length
            ) || 0

            const fallbackPerPage = Number(queryParams.itemsPerPage) || (items.length > 0 ? items.length : 10)

            const rawPerPage = payload['hydra:itemsPerPage'] ??
                payload.meta?.per_page ??
                payload.pagination?.per_page ??
                queryParams.itemsPerPage ??
                (items.length > 0 ? items.length : null)

            const perPageFromResponse = Number(rawPerPage ?? fallbackPerPage) || fallbackPerPage

            const currentPage = Number(
                extractPage(hydraView['hydra:self']) ??
                payload.meta?.current_page ??
                payload.pagination?.current_page ??
                queryParams.page ??
                1
            ) || 1

            const lastPage = Number(
                extractPage(hydraView['hydra:last']) ??
                payload.meta?.last_page ??
                payload.pagination?.last_page ??
                (perPageFromResponse ? Math.ceil(totalItems / perPageFromResponse) : 1)
            ) || Math.max(1, Math.ceil(totalItems / (perPageFromResponse || 1)))

            return {
                data: items,
                meta: {
                    total: totalItems,
                    perPage: perPageFromResponse,
                    currentPage,
                    lastPage
                }
            }
        } catch (error) {
            console.error('❌ Erreur liste réservations:', error)
            throw this.handleError(error)
        }
    }

    async getCalendarEvents(startDate, endDate) {
        const params = new URLSearchParams({
            start: startDate,
            end: endDate
        })

        const response = await axios.get(`${this.baseURL}/admin/calendar/events?${params}`)
        return response.data
    }

    async getReservationsForCalendar({ start, end }) {
        try {
            const response = await axios.get(`${this.baseURL}/admin/calendar/reservations`, {
                params: { start, end },
                timeout: 30000 // ✅ Timeout plus long pour les données calendrier
            })
            return response.data || []
        } catch (error) {
            console.error('❌ Erreur réservations calendrier:', error)
            throw this.handleError(error)
        }
    }

    async getReservation(id) {
        try {
            console.log(`📋 Chargement réservation #${id}`)

            const response = await axios.get(`${this.baseURL}/admin/reservations/${id}`, {
                timeout: 30000 // 10 secondes max
            })

            console.log(`✅ Réservation #${id} chargée:`, response.data)
            return response.data

        } catch (error) {
            console.error(`❌ Erreur récupération réservation ${id}:`, error)

            // Message d'erreur plus spécifique
            if (error.response?.status === 404) {
                throw new Error(`Réservation #${id} introuvable`)
            }

            throw this.handleError(error)
        }
    }

    async createReservation(data) {
        try {
            const response = await axios.post(`${this.baseURL}/admin/reservations`, data)
            console.log('✅ Réservation créée:', response.data.id)
            return response.data
        } catch (error) {
            console.error('❌ Erreur création réservation:', error)
            throw this.handleError(error)
        }
    }

    async updateReservation(id, payload) {
        try {
            const response = await axios.put(`${this.baseURL}/admin/reservations/${id}`, payload)
            console.log('✅ Réservation mise à jour:', id)
            return response.data
        } catch (error) {
            console.error(`❌ Erreur mise à jour réservation ${id}:`, error)
            throw this.handleError(error)
        }
    }

    // =============================
    // ÉVÉNEMENTS CALENDRIER
    // =============================

    async createEvent(data) {
        try {
            const response = await axios.post(`${this.baseURL}/admin/events`, data)
            return response.data
        } catch (error) {
            throw this.handleError(error)
        }
    }

    async updateEvent(id, data) {
        try {
            const response = await axios.put(`${this.baseURL}/admin/events/${id}`, data)
            return response.data
        } catch (error) {
            throw this.handleError(error)
        }
    }

    async deleteEvent(id) {
        try {
            const response = await axios.delete(`${this.baseURL}/admin/events/${id}`)
            return response.data
        } catch (error) {
            throw this.handleError(error)
        }
    }

    async deleteReservation(id) {
        try {
            const response = await axios.delete(`${this.baseURL}/admin/reservations/${id}`)
            return response.data
        } catch (error) {
            throw this.handleError(error)
        }
    }

    // =============================
    // MÉTHODES UTILITAIRES
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
    // GESTION D'ERREURS AMÉLIORÉE
    // =============================

    handleError(error) {
        // ✅ Gestion spécifique des timeouts
        if (error.code === 'ECONNABORTED') {
            return new Error('Le serveur met trop de temps à répondre. Vérifiez votre connexion.')
        }

        // ✅ Gestion des erreurs d'authentification
        if (error.response?.status === 401) {
            const authStore = useAuthStore()
            authStore.logout()
            return new Error('Session expirée, veuillez vous reconnecter')
        }

        // ✅ Gestion des erreurs de serveur
        if (error.response?.status >= 500) {
            return new Error('Erreur serveur temporaire. Veuillez réessayer.')
        }

        // ✅ Gestion des erreurs de validation
        if (error.response?.status === 422) {
            const validationErrors = error.response.data?.errors || {}
            const firstError = Object.values(validationErrors)[0]?.[0]
            return new Error(firstError || 'Données invalides')
        }

        // ✅ Gestion des erreurs 404
        if (error.response?.status === 404) {
            return new Error('Ressource non trouvée')
        }

        // ✅ Erreur générique avec message du serveur
        const serverMessage = error.response?.data?.message ||
            error.response?.data?.error ||
            error.message

        return new Error(serverMessage || 'Erreur inconnue')
    }

    // ✅ Méthodes d'aide pour les autres composants
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
}

export default new AdminApi()