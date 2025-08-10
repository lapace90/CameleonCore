<template>
  <div class="pagination-container">
    <div class="pagination-info">
      Affichage de {{ fromItem }} à {{ toItem }} sur {{ pagination.total }} résultats
    </div>
    
    <div class="pagination-controls">
      <button @click="goToPage(pagination.currentPage - 1)" 
        :disabled="pagination.currentPage === 1" class="btn btn-outline btn-sm">
        <i class="fas fa-chevron-left"></i>
        Précédent
      </button>

      <div class="pagination-pages">
        <button v-for="page in visiblePages" :key="page" 
          @click="goToPage(page)" class="btn btn-sm"
          :class="page === pagination.currentPage ? 'btn-primary' : 'btn-outline'">
          {{ page }}
        </button>
      </div>

      <button @click="goToPage(pagination.currentPage + 1)" 
        :disabled="pagination.currentPage === pagination.lastPage" class="btn btn-outline btn-sm">
        Suivant
        <i class="fas fa-chevron-right"></i>
      </button>
    </div>
  </div>
</template>

<script>
export default {
  name: 'Pagination',
  props: {
    pagination: { type: Object, required: true }
  },
  computed: {
    fromItem() {
      return (this.pagination.currentPage - 1) * this.pagination.perPage + 1
    },
    toItem() {
      return Math.min(this.pagination.currentPage * this.pagination.perPage, this.pagination.total)
    },
    visiblePages() {
      const pages = []
      const current = this.pagination.currentPage
      const last = this.pagination.lastPage
      
      for (let i = Math.max(1, current - 2); i <= Math.min(last, current + 2); i++) {
        pages.push(i)
      }
      
      return pages
    }
  },
  methods: {
    goToPage(page) {
      if (page >= 1 && page <= this.pagination.lastPage) {
        this.$emit('page-change', page)
      }
    }
  }
}
</script>

<style scoped>
.pagination-container {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 20px 0;
  border-top: 1px solid #e5e7eb;
}

.pagination-info {
  font-size: 14px;
  color: #6b7280;
}

.pagination-controls {
  display: flex;
  align-items: center;
  gap: 8px;
}

.pagination-pages {
  display: flex;
  gap: 4px;
}

@media (max-width: 768px) {
  .pagination-container {
    flex-direction: column;
    gap: 16px;
  }
  
  .pagination-info {
    order: 2;
  }
}
</style>