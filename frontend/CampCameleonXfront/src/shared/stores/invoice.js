// src/shared/stores/invoiceStore.js
import { defineStore } from 'pinia'
import InvoiceApi from '@/services/InvoiceApi'

export const useInvoiceStore = defineStore('invoice', {
  state: () => ({
    // 📊 Données
    invoices: [],
    currentInvoice: null,
    stats: null,

    // 🎭 État UI
    loading: false,
    error: null,
    successMessage: null,

    // 🔍 Filtres
    filters: {
      status: '',
      customer_id: '',
      start_date: '',
      end_date: '',
      search: '',
      sort_by: 'created_at',
      sort_order: 'desc',
      per_page: 15
    },

    // 📄 Pagination
    pagination: {
      current_page: 1,
      last_page: 1,
      per_page: 15,
      total: 0
    },

    // ⏰ Cache
    lastFetch: null,
    cacheTimeout: 5 * 60 * 1000 // 5 minutes
  }),

  getters: {
    /**
     * Factures non payées
     */
    unpaidInvoices: (state) => {
      return state.invoices.filter(
        invoice => invoice.status === 'pending' || invoice.status === 'overdue'
      )
    },

    /**
     * Factures en retard
     */
    overdueInvoices: (state) => {
      return state.invoices.filter(invoice => invoice.is_overdue)
    },

    /**
     * Factures payées
     */
    paidInvoices: (state) => {
      return state.invoices.filter(invoice => invoice.status === 'paid')
    },

    /**
     * Revenu total (factures payées uniquement)
     */
    totalRevenue: (state) => {
      return state.invoices
        .filter(invoice => invoice.status === 'paid')
        .reduce((sum, invoice) => sum + parseFloat(invoice.amount || 0), 0)
    },

    /**
     * Montant en attente
     */
    pendingAmount: (state) => {
      return state.invoices
        .filter(invoice => invoice.status === 'pending' || invoice.status === 'overdue')
        .reduce((sum, invoice) => sum + parseFloat(invoice.amount || 0), 0)
    },

    /**
     * Facture par ID
     */
    getInvoiceById: (state) => (id) => {
      return state.invoices.find(invoice => invoice.id === id)
    },

    /**
     * Vérifie si le cache est valide
     */
    isCacheValid: (state) => {
      if (!state.lastFetch) return false
      return (Date.now() - state.lastFetch) < state.cacheTimeout
    }
  },

  actions: {
    // ===============================
    // 🔧 GESTION DES MESSAGES
    // ===============================

    setSuccess(message, duration = 5000) {
      this.successMessage = message
      this.error = null

      if (duration > 0) {
        setTimeout(() => {
          this.successMessage = null
        }, duration)
      }
    },

    setError(message) {
      this.error = message
      this.successMessage = null
    },

    clearMessages() {
      this.error = null
      this.successMessage = null
    },

    // ===============================
    // 📥 RÉCUPÉRATION DES DONNÉES
    // ===============================

    /**
     * Récupérer la liste des factures
     * @param {number} page - Numéro de page
     * @param {boolean} forceRefresh - Forcer le rafraîchissement (ignorer le cache)
     */
    async fetchInvoices(page = 1, forceRefresh = false) {
      // Vérifier le cache si c'est la même page
      if (!forceRefresh && page === this.pagination.current_page && this.isCacheValid) {
        console.log('📦 Utilisation du cache pour les factures')
        return {
          data: this.invoices,
          meta: this.pagination
        }
      }

      this.loading = true
      this.error = null

      try {
        const params = {
          page,
          ...this.filters
        }

        const response = await InvoiceApi.getAll(params)

        this.invoices = response.data
        this.pagination = response.meta
        this.lastFetch = Date.now()

        return response
      } catch (err) {
        this.setError(err.message || 'Erreur lors du chargement des factures')
        throw err
      } finally {
        this.loading = false
      }
    },

    /**
     * Récupérer une facture par son ID
     * @param {number|string} id - ID de la facture
     * @param {boolean} forceRefresh - Forcer le rafraîchissement
     */
    async fetchInvoiceById(id, forceRefresh = false) {
      // Vérifier si la facture est déjà en cache
      if (!forceRefresh && this.currentInvoice?.id === id) {
        console.log('📦 Utilisation du cache pour la facture')
        return this.currentInvoice
      }

      this.loading = true
      this.error = null

      try {
        const invoice = await InvoiceApi.getById(id)
        this.currentInvoice = invoice

        // Mettre à jour dans la liste si elle existe
        const index = this.invoices.findIndex(inv => inv.id === invoice.id)
        if (index !== -1) {
          this.invoices[index] = invoice
        }

        return invoice
      } catch (err) {
        this.setError(err.message || `Erreur lors du chargement de la facture ${id}`)
        throw err
      } finally {
        this.loading = false
      }
    },

    /**
     * Récupérer les statistiques
     * @param {boolean} forceRefresh - Forcer le rafraîchissement
     */
    async fetchStats(forceRefresh = false) {
      if (!forceRefresh && this.stats && this.isCacheValid) {
        console.log('📦 Utilisation du cache pour les stats')
        return this.stats
      }

      try {
        const stats = await InvoiceApi.getStats()
        this.stats = stats
        return stats
      } catch (err) {
        this.setError(err.message || 'Erreur lors du chargement des statistiques')
        throw err
      }
    },

    // ===============================
    // ✏️ CRÉATION ET MODIFICATION
    // ===============================

    /**
     * Créer une nouvelle facture
     * @param {Object} invoiceData - Données de la facture
     */
    async createInvoice(invoiceData) {
      this.loading = true
      this.error = null

      try {
        const invoice = await InvoiceApi.create(invoiceData)
        
        // Ajouter à la liste locale
        this.invoices.unshift(invoice)
        this.pagination.total += 1

        this.setSuccess('Facture créée avec succès')
        return invoice
      } catch (err) {
        this.setError(err.message || 'Erreur lors de la création de la facture')
        throw err
      } finally {
        this.loading = false
      }
    },

    /**
     * Mettre à jour une facture
     * @param {number|string} id - ID de la facture
     * @param {Object} invoiceData - Données à mettre à jour
     */
    async updateInvoice(id, invoiceData) {
      this.loading = true
      this.error = null

      try {
        const invoice = await InvoiceApi.update(id, invoiceData)

        // Mettre à jour dans la liste locale
        const index = this.invoices.findIndex(inv => inv.id === id)
        if (index !== -1) {
          this.invoices[index] = invoice
        }

        // Mettre à jour la facture courante si c'est elle
        if (this.currentInvoice?.id === id) {
          this.currentInvoice = invoice
        }

        this.setSuccess('Facture mise à jour avec succès')
        return invoice
      } catch (err) {
        this.setError(err.message || 'Erreur lors de la mise à jour de la facture')
        throw err
      } finally {
        this.loading = false
      }
    },

    /**
     * Supprimer une facture
     * @param {number|string} id - ID de la facture
     */
    async deleteInvoice(id) {
      this.loading = true
      this.error = null

      try {
        await InvoiceApi.delete(id)

        // Retirer de la liste locale
        this.invoices = this.invoices.filter(inv => inv.id !== id)
        this.pagination.total -= 1

        // Effacer la facture courante si c'est elle
        if (this.currentInvoice?.id === id) {
          this.currentInvoice = null
        }

        this.setSuccess('Facture supprimée avec succès')
      } catch (err) {
        this.setError(err.message || 'Erreur lors de la suppression de la facture')
        throw err
      } finally {
        this.loading = false
      }
    },

    // ===============================
    // 🎬 ACTIONS SPÉCIFIQUES
    // ===============================

    /**
     * Marquer une facture comme payée
     * @param {number|string} id - ID de la facture
     * @param {string} paymentMethod - Méthode de paiement
     */
    async markAsPaid(id, paymentMethod = 'card') {
      this.loading = true
      this.error = null

      try {
        const invoice = await InvoiceApi.markAsPaid(id, paymentMethod)

        // Mettre à jour dans la liste locale
        const index = this.invoices.findIndex(inv => inv.id === id)
        if (index !== -1) {
          this.invoices[index] = invoice
        }

        // Mettre à jour la facture courante si c'est elle
        if (this.currentInvoice?.id === id) {
          this.currentInvoice = invoice
        }

        this.setSuccess('Facture marquée comme payée')
        
        // Rafraîchir les stats
        this.fetchStats(true)

        return invoice
      } catch (err) {
        this.setError(err.message || 'Erreur lors du marquage de la facture comme payée')
        throw err
      } finally {
        this.loading = false
      }
    },

    /**
     * Envoyer une facture par email
     * @param {number|string} id - ID de la facture
     */
    async sendEmail(id) {
      this.loading = true
      this.error = null

      try {
        const result = await InvoiceApi.sendEmail(id)
        this.setSuccess('Email envoyé avec succès')
        
        // Rafraîchir la facture pour mettre à jour le compteur d'envois
        await this.fetchInvoiceById(id, true)
        
        return result
      } catch (err) {
        this.setError(err.message || 'Erreur lors de l\'envoi de l\'email')
        throw err
      } finally {
        this.loading = false
      }
    },

    /**
     * Télécharger le PDF d'une facture
     * @param {number|string} id - ID de la facture
     * @param {string} filename - Nom du fichier
     */
    async downloadPdf(id, filename = null) {
      this.error = null

      try {
        await InvoiceApi.downloadPdf(id, filename)
        this.setSuccess('PDF téléchargé avec succès')
      } catch (err) {
        this.setError(err.message || 'Erreur lors du téléchargement du PDF')
        throw err
      }
    },

    /**
     * Annuler une facture
     * @param {number|string} id - ID de la facture
     * @param {string} reason - Raison de l'annulation
     */
    async cancelInvoice(id, reason = '') {
      this.loading = true
      this.error = null

      try {
        const invoice = await InvoiceApi.cancel(id, reason)

        // Mettre à jour dans la liste locale
        const index = this.invoices.findIndex(inv => inv.id === id)
        if (index !== -1) {
          this.invoices[index] = invoice
        }

        // Mettre à jour la facture courante si c'est elle
        if (this.currentInvoice?.id === id) {
          this.currentInvoice = invoice
        }

        this.setSuccess('Facture annulée')
        return invoice
      } catch (err) {
        this.setError(err.message || 'Erreur lors de l\'annulation de la facture')
        throw err
      } finally {
        this.loading = false
      }
    },

    // ===============================
    // 🔍 GESTION DES FILTRES
    // ===============================

    /**
     * Définir les filtres
     * @param {Object} filters - Nouveaux filtres
     */
    setFilters(filters) {
      this.filters = {
        ...this.filters,
        ...filters
      }
      this.lastFetch = null // Invalider le cache
    },

    /**
     * Réinitialiser les filtres
     */
    resetFilters() {
      this.filters = {
        status: '',
        customer_id: '',
        start_date: '',
        end_date: '',
        search: '',
        sort_by: 'created_at',
        sort_order: 'desc',
        per_page: 15
      }
      this.lastFetch = null // Invalider le cache
    },

    // ===============================
    // 🧹 UTILITAIRES
    // ===============================

    /**
     * Invalider le cache
     */
    invalidateCache() {
      this.lastFetch = null
    },

    /**
     * Réinitialiser le store
     */
    reset() {
      this.invoices = []
      this.currentInvoice = null
      this.stats = null
      this.loading = false
      this.error = null
      this.successMessage = null
      this.resetFilters()
      this.pagination = {
        current_page: 1,
        last_page: 1,
        per_page: 15,
        total: 0
      }
      this.lastFetch = null
    }
  }
})