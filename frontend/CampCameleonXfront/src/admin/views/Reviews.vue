<!-- frontend/CampCameleonXfront/src/admin/views/Reviews.vue -->
<template>
  <div class="invoices-page content-wrapper">
    <!-- Header -->
    <div class="page-header">
      <div class="header-left">
        <h1 class="page-title">
          <i class="fas fa-comments"></i>
          Gestion des Avis
        </h1>
        <p class="page-subtitle">{{ reviews.length }} avis • {{ pendingCount }} en attente</p>
      </div>
      <div class="header-actions">
        <button type="button" class="btn btn-outline btn-sm" @click="fetchReviews">
          <i class="fas fa-sync"></i>
          Actualiser
        </button>
      </div>
    </div>

    <!-- Filtres -->
    <div class="card mb-3">
      <div class="filters-section">
        <div class="filter-tabs">
          <button 
            v-for="filter in filters" 
            :key="filter.value"
            @click="currentFilter = filter.value"
            :class="{ 'active': currentFilter === filter.value }"
            class="filter-tab"
          >
            {{ filter.label }}
            <span v-if="filter.count" class="badge">{{ filter.count }}</span>
          </button>
        </div>

        <div class="filter-search">
          <input 
            type="text" 
            v-model="searchQuery"
            placeholder="Rechercher par nom ou email..."
            class="search-input"
          >
          <i class="fas fa-search"></i>
        </div>
      </div>
    </div>

    <!-- Messages -->
    <div v-if="successMessage" class="alert alert-success">
      <i class="fas fa-check-circle"></i>
      {{ successMessage }}
      <button @click="successMessage = null" class="btn-close">&times;</button>
    </div>

    <div v-if="error" class="alert alert-danger">
      <i class="fas fa-exclamation-triangle"></i>
      {{ error }}
      <button @click="error = null" class="btn-close">&times;</button>
    </div>

    <!-- Loading - UTILISE LoadingState -->
    <LoadingState
      v-if="loading"
      state="loading"
      variant="card"
      loading-text="Chargement des avis..."
    />

    <!-- Empty state - UTILISE LoadingState -->
    <LoadingState
      v-else-if="filteredReviews.length === 0"
      state="empty"
      variant="card"
      empty-title="Aucun avis"
      :empty-message="getEmptyStateMessage()"
      empty-icon="fas fa-comments"
    />

    <!-- Liste des avis -->
    <div v-else>
      <div v-for="review in filteredReviews" :key="review.id" class="card mb-3">
        <div class="review-item">
          <!-- En-tête -->
          <div class="review-header">
            <div class="review-info">
              <h3 class="review-name">{{ review.client_name }}</h3>
              <span v-if="review.location" class="review-location">
                <i class="fas fa-map-marker-alt"></i>
                {{ review.location }}
              </span>
            </div>
            
            <div class="review-meta">
              <div class="review-rating">
                <i v-for="n in 5" :key="n" :class="n <= review.rating ? 'fas fa-star' : 'far fa-star'"></i>
              </div>
              <span class="review-date">{{ formatDate(review.created_at) }}</span>
            </div>
          </div>

          <!-- Contenu -->
          <div class="review-content">
            <p>{{ review.testimonial_text }}</p>
            
            <div class="review-badges">
              <span class="badge-cat">{{ getCategoryLabel(review.category) }}</span>
              <span :class="`badge-status badge-${review.status}`">
                {{ getStatusLabel(review.status) }}
              </span>
              <span v-if="review.featured" class="badge-featured">
                <i class="fas fa-star"></i> Mise en avant
              </span>
            </div>

            <!-- Photos si présentes -->
            <div v-if="review.photos && review.photos.length > 0" class="review-photos">
              <img 
                v-for="(photo, idx) in review.photos" 
                :key="idx" 
                :src="photo.url" 
                :alt="photo.alt || 'Photo'" 
                class="review-photo"
              >
            </div>
          </div>

          <!-- Actions -->
          <div class="review-actions">
            <template v-if="review.status === 'pending'">
              <button @click="approveReview(review.id)" class="btn btn-success btn-sm">
                <i class="fas fa-check"></i>
                Approuver
              </button>
              <button @click="rejectReview(review.id)" class="btn btn-danger btn-sm">
                <i class="fas fa-times"></i>
                Rejeter
              </button>
            </template>

            <template v-else-if="review.status === 'approved'">
              <button 
                @click="toggleFeatured(review.id, !review.featured)" 
                :class="review.featured ? 'btn btn-warning btn-sm' : 'btn btn-secondary btn-sm'"
              >
                <i :class="review.featured ? 'fas fa-star-half-alt' : 'fas fa-star'"></i>
                {{ review.featured ? 'Retirer la vedette' : 'Mettre en vedette' }}
              </button>
              <button @click="unpublishReview(review.id)" class="btn btn-secondary btn-sm">
                <i class="fas fa-eye-slash"></i>
                Dépublier
              </button>
            </template>

            <template v-else-if="review.status === 'rejected'">
              <button @click="approveReview(review.id)" class="btn btn-success btn-sm">
                <i class="fas fa-undo"></i>
                Réapprouver
              </button>
            </template>

            <button @click="deleteReview(review.id)" class="btn btn-danger btn-sm">
              <i class="fas fa-trash"></i>
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import ReviewsApi from '@/services/ReviewsApi'
import LoadingState from '@/admin/components/ui/LoadingState.vue'

export default {
  name: 'ReviewsManagement',
  components: { LoadingState },

  data() {
    return {
      reviews: [],
      loading: false,
      currentFilter: 'all',
      searchQuery: '',
      successMessage: null,
      error: null,
      refreshInterval: null
    }
  },

  computed: {
    filters() {
      return [
        { label: 'Tous', value: 'all', count: this.reviews.length },
        { label: 'En attente', value: 'pending', count: this.pendingCount },
        { label: 'Approuvés', value: 'approved', count: this.approvedCount },
        { label: 'Rejetés', value: 'rejected', count: this.rejectedCount }
      ]
    },

    pendingCount() {
      return this.reviews.filter(r => r.status === 'pending').length
    },

    approvedCount() {
      return this.reviews.filter(r => r.status === 'approved').length
    },

    rejectedCount() {
      return this.reviews.filter(r => r.status === 'rejected').length
    },

    filteredReviews() {
      let filtered = this.reviews

      if (this.currentFilter !== 'all') {
        filtered = filtered.filter(r => r.status === this.currentFilter)
      }

      if (this.searchQuery.trim()) {
        const query = this.searchQuery.toLowerCase()
        filtered = filtered.filter(r => 
          r.client_name.toLowerCase().includes(query) ||
          r.email.toLowerCase().includes(query) ||
          (r.location && r.location.toLowerCase().includes(query))
        )
      }

      return filtered
    }
  },

  mounted() {
    this.fetchReviews()
    
    // Auto-refresh toutes les 30 secondes pour voir les nouveaux avis
    this.refreshInterval = setInterval(() => {
      if (!this.loading) {
        this.fetchReviews()
      }
    }, 30000)
  },

  beforeUnmount() {
    if (this.refreshInterval) {
      clearInterval(this.refreshInterval)
    }
  },

  methods: {
    async fetchReviews() {
      this.loading = true
      try {
        const response = await ReviewsApi.getAllAdmin()
        this.reviews = Array.isArray(response) ? response : []
        console.log('✅ Avis chargés:', this.reviews.length)
      } catch (error) {
        console.error('❌ Erreur lors du chargement des avis:', error)
        this.error = 'Erreur lors du chargement des avis'
        this.reviews = []
      } finally {
        this.loading = false
      }
    },

    async approveReview(id) {
      try {
        await ReviewsApi.publish(id)
        this.successMessage = 'Avis approuvé et publié avec succès'
        await this.fetchReviews()
      } catch (error) {
        console.error('❌ Erreur:', error)
        this.error = 'Erreur lors de l\'approbation de l\'avis'
      }
    },

    async rejectReview(id) {
      if (!confirm('Êtes-vous sûr de vouloir rejeter cet avis ?')) {
        return
      }

      try {
        await ReviewsApi.reject(id)
        this.successMessage = 'Avis rejeté'
        await this.fetchReviews()
      } catch (error) {
        console.error('❌ Erreur:', error)
        this.error = 'Erreur lors du rejet de l\'avis'
      }
    },

    async unpublishReview(id) {
      try {
        await ReviewsApi.update(id, { is_published: false })
        this.successMessage = 'Avis dépublié'
        await this.fetchReviews()
      } catch (error) {
        console.error('❌ Erreur:', error)
        this.error = 'Erreur lors de la dépublication de l\'avis'
      }
    },

    async toggleFeatured(id, featured) {
      try {
        await ReviewsApi.toggleFeatured(id, featured)
        this.successMessage = featured ? 'Avis mis en vedette' : 'Vedette retirée'
        await this.fetchReviews()
      } catch (error) {
        console.error('❌ Erreur:', error)
        this.error = 'Erreur lors de la mise à jour'
      }
    },

    async deleteReview(id) {
      if (!confirm('Êtes-vous sûr de vouloir supprimer définitivement cet avis ?')) {
        return
      }

      try {
        await ReviewsApi.delete(id)
        this.successMessage = 'Avis supprimé définitivement'
        await this.fetchReviews()
      } catch (error) {
        console.error('❌ Erreur:', error)
        this.error = 'Erreur lors de la suppression de l\'avis'
      }
    },

    formatDate(dateString) {
      if (!dateString) return '-'
      const date = new Date(dateString)
      return date.toLocaleDateString('fr-FR', {
        day: 'numeric',
        month: 'short',
        year: 'numeric'
      })
    },

    getCategoryLabel(category) {
      const labels = {
        all: 'Général',
        couples: 'En couple',
        families: 'En famille',
        solo: 'Solo',
        groups: 'En groupe'
      }
      return labels[category] || category
    },

    getStatusLabel(status) {
      const labels = {
        pending: 'En attente',
        approved: 'Approuvé',
        rejected: 'Rejeté'
      }
      return labels[status] || status
    },

    getEmptyStateMessage() {
      if (this.currentFilter === 'pending') {
        return 'Aucun avis en attente de validation'
      } else if (this.currentFilter === 'approved') {
        return 'Aucun avis approuvé pour le moment'
      } else if (this.currentFilter === 'rejected') {
        return 'Aucun avis rejeté'
      }
      return 'Aucun avis pour le moment'
    }
  }
}
</script>

<style scoped>
.filters-section {
  padding: 1.5rem;
}

.filter-tabs {
  display: flex;
  gap: 0.5rem;
  flex-wrap: wrap;
  margin-bottom: 1rem;
}

.filter-tab {
  padding: 0.5rem 1rem;
  border: 1px solid #dee2e6;
  background: white;
  border-radius: 6px;
  cursor: pointer;
  transition: all 0.2s;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.filter-tab:hover {
  background: #f8f9fa;
}

.filter-tab.active {
  background: var(--primary);
  color: white;
  border-color: var(--primary);
}

.filter-tab .badge {
  background: rgba(0,0,0,0.1);
  padding: 0.25rem 0.5rem;
  border-radius: 12px;
  font-size: 0.75rem;
}

.filter-tab.active .badge {
  background: rgba(255,255,255,0.3);
}

.filter-search {
  position: relative;
}

.search-input {
  width: 100%;
  padding: 0.75rem 2.5rem 0.75rem 1rem;
  border: 1px solid #dee2e6;
  border-radius: 6px;
  font-size: 0.875rem;
}

.filter-search i {
  position: absolute;
  right: 1rem;
  top: 50%;
  transform: translateY(-50%);
  color: #6b7280;
}

.review-item {
  padding: 1.5rem;
}

.review-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 1rem;
  flex-wrap: wrap;
  gap: 1rem;
}

.review-name {
  font-size: 1.125rem;
  font-weight: 600;
  margin: 0 0 0.25rem 0;
}

.review-location {
  font-size: 0.875rem;
  color: #6b7280;
}

.review-location i {
  margin-right: 0.25rem;
}

.review-meta {
  text-align: right;
}

.review-rating {
  color: #ffc107;
  margin-bottom: 0.25rem;
}

.review-rating i {
  font-size: 0.875rem;
}

.review-date {
  font-size: 0.875rem;
  color: #6b7280;
}

.review-content p {
  margin-bottom: 1rem;
  line-height: 1.6;
}

.review-badges {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
  margin-bottom: 1rem;
}

.badge-cat,
.badge-status,
.badge-featured {
  padding: 0.25rem 0.75rem;
  border-radius: 12px;
  font-size: 0.875rem;
  font-weight: 500;
}

.badge-cat {
  background: rgba(94, 114, 228, 0.1);
  color: #5e72e4;
}

.badge-status {
  font-weight: 600;
}

.badge-pending {
  background: rgba(255, 193, 7, 0.1);
  color: #ff8b00;
}

.badge-approved {
  background: rgba(40, 167, 69, 0.1);
  color: #28a745;
}

.badge-rejected {
  background: rgba(220, 53, 69, 0.1);
  color: #dc3545;
}

.badge-featured {
  background: rgba(255, 193, 7, 0.2);
  color: #ff8b00;
}

.review-photos {
  display: flex;
  gap: 0.5rem;
  margin-top: 1rem;
}

.review-photo {
  width: 80px;
  height: 80px;
  object-fit: cover;
  border-radius: 6px;
  border: 2px solid #dee2e6;
}

.review-actions {
  display: flex;
  gap: 0.5rem;
  flex-wrap: wrap;
  padding-top: 1rem;
  border-top: 1px solid #dee2e6;
}
</style>