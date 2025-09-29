// src/services/InvoiceApi.js
import httpClient from './httpClient'

/**
 * Service API pour la gestion des factures
 * Utilise httpClient pour bénéficier des intercepteurs centralisés
 */
class InvoiceApi {
  /**
   * Endpoint de base pour les factures
   */
  static baseEndpoint = '/admin/invoices'

  /**
   * Récupérer la liste des factures avec filtres et pagination
   * @param {Object} params - Paramètres de requête (page, status, search, etc.)
   * @returns {Promise<Object>} - Liste paginée des factures
   */
  static async getAll(params = {}) {
    try {
      // Nettoyer les paramètres vides
      const cleanParams = Object.entries(params).reduce((acc, [key, value]) => {
        if (value !== null && value !== undefined && value !== '') {
          acc[key] = value
        }
        return acc
      }, {})

      const response = await httpClient.get(this.baseEndpoint, { 
        params: cleanParams 
      })

      // Normaliser la réponse (support Laravel pagination)
      return {
        data: response.data.data || response.data,
        meta: response.data.meta || {
          current_page: response.data.current_page || 1,
          last_page: response.data.last_page || 1,
          per_page: response.data.per_page || 15,
          total: response.data.total || 0
        }
      }
    } catch (error) {
      console.error('Erreur lors de la récupération des factures:', error)
      throw this.handleError(error)
    }
  }

  /**
   * Récupérer une facture par son ID
   * @param {number|string} id - ID de la facture
   * @returns {Promise<Object>} - Détails de la facture
   */
  static async getById(id) {
    try {
      const response = await httpClient.get(`${this.baseEndpoint}/${id}`)
      return response.data
    } catch (error) {
      console.error(`Erreur lors de la récupération de la facture ${id}:`, error)
      throw this.handleError(error)
    }
  }

  /**
   * Récupérer les statistiques des factures
   * @returns {Promise<Object>} - Statistiques (total, payées, impayées, revenus)
   */
  static async getStats() {
    try {
      const response = await httpClient.get(`${this.baseEndpoint}/stats`)
      return response.data
    } catch (error) {
      console.error('Erreur lors de la récupération des statistiques:', error)
      throw this.handleError(error)
    }
  }

  /**
   * Créer une nouvelle facture
   * @param {Object} invoiceData - Données de la facture
   * @returns {Promise<Object>} - Facture créée
   */
  static async create(invoiceData) {
    try {
      const response = await httpClient.post(this.baseEndpoint, invoiceData)
      return response.data
    } catch (error) {
      console.error('Erreur lors de la création de la facture:', error)
      throw this.handleError(error)
    }
  }

  /**
   * Mettre à jour une facture
   * @param {number|string} id - ID de la facture
   * @param {Object} invoiceData - Données à mettre à jour
   * @returns {Promise<Object>} - Facture mise à jour
   */
  static async update(id, invoiceData) {
    try {
      const response = await httpClient.put(`${this.baseEndpoint}/${id}`, invoiceData)
      return response.data
    } catch (error) {
      console.error(`Erreur lors de la mise à jour de la facture ${id}:`, error)
      throw this.handleError(error)
    }
  }

  /**
   * Supprimer une facture
   * @param {number|string} id - ID de la facture
   * @returns {Promise<void>}
   */
  static async delete(id) {
    try {
      await httpClient.delete(`${this.baseEndpoint}/${id}`)
    } catch (error) {
      console.error(`Erreur lors de la suppression de la facture ${id}:`, error)
      throw this.handleError(error)
    }
  }

  /**
   * Marquer une facture comme payée
   * @param {number|string} id - ID de la facture
   * @param {string} paymentMethod - Méthode de paiement (card, cash, bank_transfer, check)
   * @returns {Promise<Object>} - Facture mise à jour
   */
  static async markAsPaid(id, paymentMethod = 'card') {
    try {
      const response = await httpClient.post(`${this.baseEndpoint}/${id}/mark-paid`, {
        payment_method: paymentMethod,
        payment_date: new Date().toISOString()
      })
      return response.data
    } catch (error) {
      console.error(`Erreur lors du marquage de la facture ${id} comme payée:`, error)
      throw this.handleError(error)
    }
  }

  /**
   * Envoyer une facture par email au client
   * @param {number|string} id - ID de la facture
   * @returns {Promise<Object>} - Résultat de l'envoi
   */
  static async sendEmail(id) {
    try {
      const response = await httpClient.post(`${this.baseEndpoint}/${id}/send-email`)
      return response.data
    } catch (error) {
      console.error(`Erreur lors de l'envoi de la facture ${id} par email:`, error)
      throw this.handleError(error)
    }
  }

  /**
   * Télécharger le PDF d'une facture
   * @param {number|string} id - ID de la facture
   * @param {string} filename - Nom du fichier (optionnel)
   * @returns {Promise<void>}
   */
  static async downloadPdf(id, filename = null) {
    try {
      const response = await httpClient.get(`${this.baseEndpoint}/${id}/pdf`, {
        responseType: 'blob' // Important pour les fichiers binaires
      })

      // Créer un lien de téléchargement
      const url = window.URL.createObjectURL(new Blob([response.data]))
      const link = document.createElement('a')
      link.href = url
      
      // Extraire le nom du fichier de l'en-tête ou utiliser celui fourni
      const contentDisposition = response.headers['content-disposition']
      const filenameFromHeader = contentDisposition
        ? contentDisposition.split('filename=')[1]?.replace(/"/g, '')
        : null
      
      link.setAttribute('download', filename || filenameFromHeader || `facture-${id}.pdf`)
      document.body.appendChild(link)
      link.click()
      
      // Nettoyer
      link.parentNode.removeChild(link)
      window.URL.revokeObjectURL(url)
    } catch (error) {
      console.error(`Erreur lors du téléchargement du PDF de la facture ${id}:`, error)
      throw this.handleError(error)
    }
  }

  /**
   * Récupérer les factures d'un client spécifique
   * @param {number|string} customerId - ID du client
   * @param {Object} params - Paramètres additionnels
   * @returns {Promise<Object>} - Liste des factures du client
   */
  static async getByCustomer(customerId, params = {}) {
    try {
      const response = await httpClient.get(this.baseEndpoint, {
        params: {
          ...params,
          customer_id: customerId
        }
      })
      return {
        data: response.data.data || response.data,
        meta: response.data.meta || {}
      }
    } catch (error) {
      console.error(`Erreur lors de la récupération des factures du client ${customerId}:`, error)
      throw this.handleError(error)
    }
  }

  /**
   * Récupérer les factures d'une réservation
   * @param {number|string} reservationId - ID de la réservation
   * @returns {Promise<Object>} - Facture(s) liée(s) à la réservation
   */
  static async getByReservation(reservationId) {
    try {
      const response = await httpClient.get(this.baseEndpoint, {
        params: {
          reservation_id: reservationId
        }
      })
      return response.data
    } catch (error) {
      console.error(`Erreur lors de la récupération de la facture de la réservation ${reservationId}:`, error)
      throw this.handleError(error)
    }
  }

  /**
   * Annuler une facture
   * @param {number|string} id - ID de la facture
   * @param {string} reason - Raison de l'annulation
   * @returns {Promise<Object>} - Facture annulée
   */
  static async cancel(id, reason = '') {
    try {
      const response = await httpClient.put(`${this.baseEndpoint}/${id}`, {
        status: 'canceled',
        notes: reason
      })
      return response.data
    } catch (error) {
      console.error(`Erreur lors de l'annulation de la facture ${id}:`, error)
      throw this.handleError(error)
    }
  }

  /**
   * Gestion centralisée des erreurs
   * @param {Error} error - Erreur à traiter
   * @returns {Error} - Erreur formatée
   * @private
   */
  static handleError(error) {
    // Erreur réseau
    if (!error.response) {
      return new Error('Erreur de connexion. Vérifiez votre connexion internet.')
    }

    // Erreur 401 - Non autorisé (géré par l'intercepteur mais on garde par sécurité)
    if (error.response.status === 401) {
      return new Error('Session expirée. Veuillez vous reconnecter.')
    }

    // Erreur 403 - Interdit
    if (error.response.status === 403) {
      return new Error('Vous n\'avez pas les permissions nécessaires.')
    }

    // Erreur 404 - Non trouvé
    if (error.response.status === 404) {
      return new Error('Facture introuvable.')
    }

    // Erreur 422 - Validation
    if (error.response.status === 422) {
      const validationErrors = error.response.data?.errors || {}
      const firstError = Object.values(validationErrors)[0]?.[0]
      return new Error(firstError || 'Données invalides.')
    }

    // Erreur 500 - Serveur
    if (error.response.status >= 500) {
      return new Error('Erreur serveur. Veuillez réessayer plus tard.')
    }

    // Message d'erreur du serveur ou générique
    const serverMessage = error.response?.data?.message || 
                         error.response?.data?.error || 
                         error.message

    return new Error(serverMessage || 'Une erreur est survenue.')
  }
}

export default InvoiceApi