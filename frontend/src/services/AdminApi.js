// ===================================
// Service API pour les appels admin
// ===================================

import httpClient from './httpClient'

class AdminApi {
    // =============================
    // DASHBOARD & STATS
    // =============================

    async getDashboardStats() {
        try {
            const response = await httpClient.get('/admin/dashboard/stats')
            return response.data
        } catch (error) {
            console.error('Erreur stats dashboard:', error)
            throw this.handleError(error)
        }
    }

    // =============================
    // NOTIFICATIONS
    // =============================

    async getNotifications(limit = 10) {
        try {
            const response = await httpClient.get('/admin/notifications', {
                params: { limit },
                timeout: 30000
            })

            return response.data?.['hydra:member'] || response.data || []
        } catch (error) {
            console.error('Erreur récupération notifications:', error)

            if (error.code === 'ECONNABORTED') {
                throw new Error('Timeout: Le serveur met trop de temps à répondre')
            }

            throw this.handleError(error)
        }
    }

    async markNotificationAsRead(notificationId) {
        try {
            const response = await httpClient.patch(
                `/admin/notifications/${notificationId}/mark-read`,
                {},
                {
                    headers: {
                        'Content-Type': 'application/merge-patch+json',
                        'Accept': 'application/json',
                    },
                }
            )

            return response.data
        } catch (error) {
            console.error(`Erreur marquage notification ${notificationId}:`, error)
            throw this.handleError(error)
        }
    }

    async markAllNotificationsAsRead(notificationIds) {
        try {
            const promises = notificationIds.map(id =>
                this.markNotificationAsRead(id).catch(error => {
                    console.warn(`Échec marquage notification ${id}:`, error.message)
                    return null
                })
            )

            const results = await Promise.allSettled(promises)
            const successCount = results.filter(r => r.status === 'fulfilled' && r.value !== null).length

            return successCount
        } catch (error) {
            console.error('Erreur marquage batch:', error)
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
                payment_status: params.payment_status ?? params.paymentStatus,
                'order[created_at]': params.sortOrder ?? 'desc',
            }

            Object.entries(normalized).forEach(([key, value]) => {
                if (value !== undefined && value !== null && value !== '') {
                    queryParams[key] = value
                }
            })

            const fallbackPerPage = queryParams.itemsPerPage || 15

            const response = await httpClient.get('/admin/reservations', {
                params: queryParams,
                timeout: 30000
            })

            const payload = response.data

            const items = payload?.['hydra:member'] ?? payload?.data ?? payload ?? []
            const hydraView = payload?.['hydra:view'] || {}

            const extractPage = (url) => {
                if (!url) return null
                const match = url.match(/[?&]page=(\d+)/)
                return match ? parseInt(match[1], 10) : null
            }

            const totalItems = Number(
                payload?.['hydra:totalItems'] ??
                payload?.meta?.total ??
                payload?.pagination?.total ??
                items.length
            ) || 0

            const perPageFromResponse = Number(
                payload?.meta?.per_page ??
                payload?.pagination?.per_page ??
                fallbackPerPage) || fallbackPerPage

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
            console.error('Erreur liste réservations:', error)
            throw this.handleError(error)
        }
    }

    async getCalendarEvents(startDate, endDate) {
        const params = new URLSearchParams({
            start: startDate,
            end: endDate
        })

        const response = await httpClient.get(`/admin/calendar/events?${params}`)
        return response.data
    }

    async getReservationsForCalendar({ start, end }) {
        try {
            const response = await httpClient.get('/admin/calendar/reservations', {
                params: { start, end },
                timeout: 30000
            })
            return response.data || []
        } catch (error) {
            console.error('Erreur réservations calendrier:', error)
            throw this.handleError(error)
        }
    }

    async getReservation(id) {
        try {
            const response = await httpClient.get(`/admin/reservations/${id}`, {
                timeout: 30000
            })

            return response.data

        } catch (error) {
            console.error(`Erreur récupération réservation ${id}:`, error)

            if (error.response?.status === 404) {
                throw new Error(`Réservation #${id} introuvable`)
            }

            throw this.handleError(error)
        }
    }

    async createReservation(data) {
        try {
            const response = await httpClient.post('/admin/reservations', data)
            return response.data
        } catch (error) {
            console.error('Erreur création réservation:', error)
            throw this.handleError(error)
        }
    }

    async updateReservation(id, payload) {
        try {
            const response = await httpClient.put(`/admin/reservations/${id}`, payload)
            return response.data
        } catch (error) {
            console.error(`Erreur mise à jour réservation ${id}:`, error)
            throw this.handleError(error)
        }
    }

    async deleteReservation(id) {
        try {
            const response = await httpClient.delete(`/admin/reservations/${id}`)
            return response.data
        } catch (error) {
            throw this.handleError(error)
        }
    }

    // =============================
    // ÉVÉNEMENTS CALENDRIER
    // =============================

    async createEvent(data) {
        try {
            const response = await httpClient.post('/admin/events', data)
            return response.data
        } catch (error) {
            throw this.handleError(error)
        }
    }

    async updateEvent(id, data) {
        try {
            const response = await httpClient.put(`/admin/events/${id}`, data)
            return response.data
        } catch (error) {
            throw this.handleError(error)
        }
    }

    async deleteEvent(id) {
        try {
            const response = await httpClient.delete(`/admin/events/${id}`)
            return response.data
        } catch (error) {
            throw this.handleError(error)
        }
    }

    // =============================
    // MÉTHODES UTILITAIRES
    // =============================

    async createEventOrReservation(data) {
        if (data.type === 'reservation' || data.customerName) {
            return this.createReservation(data)
        }
        return this.createEvent(data)
    }

    async updateEventOrReservation(id, data) {
        if (data.type === 'reservation' || data.customerName || id.startsWith('reservation_')) {
            const realId = id.replace('reservation_', '')
            return this.updateReservation(realId, data)
        }

        const realId = id.replace('event_', '')
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
    // CHECK-IN / CHECK-OUT
    // =============================

    async doReservationCheckIn(reservationId, at = null) {
        const payload = { status: 'checked_in' }
        if (at) payload.actual_checkin = at
        return this.updateReservation(reservationId, payload)
    }

    async doReservationCheckOut(reservationId, at = null) {
        const payload = { status: 'checked_out' }
        if (at) payload.actual_checkout = at
        return this.updateReservation(reservationId, payload)
    }

    // =============================
    // GESTION D'ERREURS
    // =============================

    handleError(error) {
        if (error.code === 'ECONNABORTED') {
            return new Error('Le serveur met trop de temps à répondre. Vérifiez votre connexion.')
        }

        if (error.response?.status === 401) {
            return new Error('Session expirée, veuillez vous reconnecter')
        }

        if (error.response?.status >= 500) {
            return new Error('Erreur serveur temporaire. Veuillez réessayer.')
        }

        if (error.response?.status === 422) {
            const validationErrors = error.response.data?.errors || {}
            const firstError = Object.values(validationErrors)[0]?.[0]
            return new Error(firstError || 'Données invalides')
        }

        if (error.response?.status === 404) {
            return new Error('Ressource non trouvée')
        }

        const serverMessage = error.response?.data?.message ||
            error.response?.data?.error ||
            error.message

        return new Error(serverMessage || 'Erreur inconnue')
    }

    // =============================
    // MÉTHODES GÉNÉRIQUES
    // =============================

    async get(endpoint) {
        try {
            const response = await httpClient.get(endpoint)
            return response
        } catch (error) {
            console.error(`AdminApi GET ${endpoint}:`, error)
            throw this.handleError(error)
        }
    }

    async post(endpoint, data) {
        try {
            const response = await httpClient.post(endpoint, data)
            return response
        } catch (error) {
            console.error(`AdminApi POST ${endpoint}:`, error)
            throw this.handleError(error)
        }
    }
}

export default new AdminApi()
