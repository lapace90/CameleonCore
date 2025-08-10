<template>
  <div class="stats-container">
    <div class="stat-item">
      <div class="stat-icon">
        <i class="fas fa-boxes"></i>
      </div>
      <div class="stat-content">
        <span class="stat-number">{{ stats.total }}</span>
        <span class="stat-label">Total</span>
      </div>
    </div>
    
    <div class="stat-item">
      <div class="stat-icon success">
        <i class="fas fa-check-circle"></i>
      </div>
      <div class="stat-content">
        <span class="stat-number">{{ stats.active }}</span>
        <span class="stat-label">Actifs</span>
      </div>
    </div>
    
    <div class="stat-item">
      <div class="stat-icon warning">
        <i class="fas fa-edit"></i>
      </div>
      <div class="stat-content">
        <span class="stat-number">{{ stats.draft }}</span>
        <span class="stat-label">Brouillons</span>
      </div>
    </div>
    
    <div class="stat-item">
      <div class="stat-icon info">
        <i class="fas fa-euro-sign"></i>
      </div>
      <div class="stat-content">
        <span class="stat-number">{{ formatPrice(stats.revenue) }}</span>
        <span class="stat-label">Valeur moyenne</span>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'ProductStats',
  props: {
    stats: { 
      type: Object, 
      default: () => ({ total: 0, active: 0, draft: 0, revenue: 0 })
    }
  },
  methods: {
    formatPrice(price) {
      return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'EUR'
      }).format(price || 0)
    }
  }
}
</script>

<style scoped>
.stats-container {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 20px;
  margin-bottom: 30px;
}

.stat-item {
  display: flex;
  align-items: center;
  gap: 16px;
  padding: 20px;
  background: white;
  border: 1px solid #e5e7eb;
  border-radius: 12px;
}

.stat-icon {
  width: 48px;
  height: 48px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 20px;
  color: white;
  background: #6b7280;
}

.stat-icon.success {
  background: #10b981;
}

.stat-icon.warning {
  background: #f59e0b;
}

.stat-icon.info {
  background: #3b82f6;
}

.stat-number {
  display: block;
  font-size: 24px;
  font-weight: 700;
  color: #111827;
}

.stat-label {
  display: block;
  font-size: 14px;
  color: #6b7280;
}
</style>