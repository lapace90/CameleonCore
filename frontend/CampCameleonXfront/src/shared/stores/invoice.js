import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import AdminApi from '@/services/AdminApi'

export const useInvoiceStore = defineStore('invoice', () => {
  // State
  const invoices = ref([])
  const currentInvoice = ref(null)
  const stats = ref(null)
  const loading = ref(false)
  const error = ref(null)
  const filters = ref({
    status: '',
    customer_id: '',
    start_date: '',
    end_date: '',
    search: '',
    sort_by: 'created_at',
    sort_order: 'desc',
    per_page: 15
  })
  const pagination = ref({
    current_page: 1,
    last_page: 1,
    per_page: 15,
    total: 0
  })

  // Getters
  const unpaidInvoices = computed(() => 
    invoices.value.filter(inv => inv.status === 'pending')
  )

  const overdueInvoices = computed(() => 
    invoices.value.filter(inv => inv.is_overdue)
  )

  const paidInvoices = computed(() => 
    invoices.value.filter(inv => inv.status === 'paid')
  )

  const totalRevenue = computed(() => 
    paidInvoices.value.reduce((sum, inv) => sum + parseFloat(inv.amount), 0)
  )

  // Actions
  async function fetchInvoices(page = 1) {
    loading.value = true
    error.value = null

    try {
      const params = {
        page,
        ...filters.value
      }

      // Nettoyer les paramètres vides
      Object.keys(params).forEach(key => {
        if (params[key] === '' || params[key] === null) {
          delete params[key]
        }
      })

      const response = await AdminApi.get('/admin/invoices', { params })

      invoices.value = response.data.data || []
      
      // Pagination
      if (response.data.meta) {
        pagination.value = {
          current_page: response.data.meta.current_page,
          last_page: response.data.meta.last_page,
          per_page: response.data.meta.per_page,
          total: response.data.meta.total
        }
      }

      return response.data
    } catch (err) {
      error.value = err.response?.data?.message || 'Erreur lors du chargement des factures'
      console.error('Erreur fetchInvoices:', err)
      throw err
    } finally {
      loading.value = false
    }
  }

  async function fetchInvoice(id) {
    loading.value = true
    error.value = null

    try {
      const response = await AdminApi.get(`/admin/invoices/${id}`)
      currentInvoice.value = response.data
      return response.data
    } catch (err) {
      error.value = err.response?.data?.message || 'Erreur lors du chargement de la facture'
      console.error('Erreur fetchInvoice:', err)
      throw err
    } finally {
      loading.value = false
    }
  }

  async function fetchStats() {
    loading.value = true
    error.value = null

    try {
      const response = await AdminApi.get('/admin/invoices/stats')
      stats.value = response.data
      return response.data
    } catch (err) {
      error.value = err.response?.data?.message || 'Erreur lors du chargement des statistiques'
      console.error('Erreur fetchStats:', err)
      throw err
    } finally {
      loading.value = false
    }
  }

  async function createInvoice(data) {
    loading.value = true
    error.value = null

    try {
      const response = await AdminApi.post('/admin/invoices', data)
      await fetchInvoices() // Recharger la liste
      return response.data
    } catch (err) {
      error.value = err.response?.data?.message || 'Erreur lors de la création de la facture'
      console.error('Erreur createInvoice:', err)
      throw err
    } finally {
      loading.value = false
    }
  }

  async function updateInvoice(id, data) {
    loading.value = true
    error.value = null

    try {
      const response = await AdminApi.put(`/admin/invoices/${id}`, data)
      
      // Mettre à jour dans la liste
      const index = invoices.value.findIndex(inv => inv.id === id)
      if (index !== -1) {
        invoices.value[index] = response.data
      }

      // Mettre à jour currentInvoice si c'est celle-ci
      if (currentInvoice.value?.id === id) {
        currentInvoice.value = response.data
      }

      return response.data
    } catch (err) {
      error.value = err.response?.data?.message || 'Erreur lors de la mise à jour de la facture'
      console.error('Erreur updateInvoice:', err)
      throw err
    } finally {
      loading.value = false
    }
  }

  async function deleteInvoice(id) {
    loading.value = true
    error.value = null

    try {
      await AdminApi.delete(`/admin/invoices/${id}`)
      
      // Retirer de la liste
      invoices.value = invoices.value.filter(inv => inv.id !== id)
      
      // Réinitialiser currentInvoice si c'était celle-ci
      if (currentInvoice.value?.id === id) {
        currentInvoice.value = null
      }
    } catch (err) {
      error.value = err.response?.data?.message || 'Erreur lors de la suppression de la facture'
      console.error('Erreur deleteInvoice:', err)
      throw err
    } finally {
      loading.value = false
    }
  }

  async function markAsPaid(id, paymentMethod = 'card') {
    loading.value = true
    error.value = null

    try {
      const response = await AdminApi.post(`/admin/invoices/${id}/mark-paid`, {
        payment_method: paymentMethod
      })

      // Mettre à jour dans la liste
      const index = invoices.value.findIndex(inv => inv.id === id)
      if (index !== -1) {
        invoices.value[index] = response.data
      }

      // Mettre à jour currentInvoice
      if (currentInvoice.value?.id === id) {
        currentInvoice.value = response.data
      }

      return response.data
    } catch (err) {
      error.value = err.response?.data?.message || 'Erreur lors du marquage comme payée'
      console.error('Erreur markAsPaid:', err)
      throw err
    } finally {
      loading.value = false
    }
  }

  async function sendEmail(id) {
    loading.value = true
    error.value = null

    try {
      const response = await AdminApi.post(`/admin/invoices/${id}/send-email`)
      
      // Mettre à jour sent_at dans la liste
      const index = invoices.value.findIndex(inv => inv.id === id)
      if (index !== -1 && response.data.sent_at) {
        invoices.value[index].sent_at = response.data.sent_at
        invoices.value[index].sent_count = response.data.sent_count
      }

      return response.data
    } catch (err) {
      error.value = err.response?.data?.message || 'Erreur lors de l\'envoi de l\'email'
      console.error('Erreur sendEmail:', err)
      throw err
    } finally {
      loading.value = false
    }
  }

  async function downloadPdf(id) {
    loading.value = true
    error.value = null

    try {
      const response = await AdminApi.get(`/admin/invoices/${id}/pdf`, {
        responseType: 'blob'
      })

      // Créer un lien de téléchargement
      const url = window.URL.createObjectURL(new Blob([response.data]))
      const link = document.createElement('a')
      link.href = url
      link.setAttribute('download', `facture-${id}.pdf`)
      document.body.appendChild(link)
      link.click()
      link.remove()
      window.URL.revokeObjectURL(url)
    } catch (err) {
      error.value = err.response?.data?.message || 'Erreur lors du téléchargement du PDF'
      console.error('Erreur downloadPdf:', err)
      throw err
    } finally {
      loading.value = false
    }
  }

  function setFilters(newFilters) {
    filters.value = { ...filters.value, ...newFilters }
  }

  function resetFilters() {
    filters.value = {
      status: '',
      customer_id: '',
      start_date: '',
      end_date: '',
      search: '',
      sort_by: 'created_at',
      sort_order: 'desc',
      per_page: 15
    }
  }

  function clearError() {
    error.value = null
  }

  function $reset() {
    invoices.value = []
    currentInvoice.value = null
    stats.value = null
    loading.value = false
    error.value = null
    resetFilters()
    pagination.value = {
      current_page: 1,
      last_page: 1,
      per_page: 15,
      total: 0
    }
  }

  return {
    // State
    invoices,
    currentInvoice,
    stats,
    loading,
    error,
    filters,
    pagination,

    // Getters
    unpaidInvoices,
    overdueInvoices,
    paidInvoices,
    totalRevenue,

    // Actions
    fetchInvoices,
    fetchInvoice,
    fetchStats,
    createInvoice,
    updateInvoice,
    deleteInvoice,
    markAsPaid,
    sendEmail,
    downloadPdf,
    setFilters,
    resetFilters,
    clearError,
    $reset
  }
})