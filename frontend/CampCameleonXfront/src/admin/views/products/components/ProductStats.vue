<template>
  <div class="stats-container">
    <div class="stat-item">
      <div class="stat-icon">
        <i class="fas fa-boxes"></i>
      </div>
      <div class="stat-content">
        <span class="stat-number">{{ safeStats.total }}</span>
        <span class="stat-label">Total</span>
      </div>
    </div>
    
    <div class="stat-item">
      <div class="stat-icon success">
        <i class="fas fa-check-circle"></i>
      </div>
      <div class="stat-content">
        <span class="stat-number">{{ safeStats.active }}</span>
        <span class="stat-label">Actifs</span>
      </div>
    </div>
    
    <div class="stat-item">
      <div class="stat-icon warning">
        <i class="fas fa-edit"></i>
      </div>
      <div class="stat-content">
        <span class="stat-number">{{ safeStats.draft }}</span>
        <span class="stat-label">Brouillons</span>
      </div>
    </div>
    
    <div class="stat-item">
      <div class="stat-icon info">
        <i class="fas fa-calculator"></i>
      </div>
      <div class="stat-content">
        <span class="stat-number">{{ formatPrice(safeStats.averagePrice) }}</span>
        <span class="stat-label">Prix moyen</span>
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
    },
    // ✅ NOUVEAU : Products pour calculer la vraie moyenne
    products: {
      type: Array,
      default: () => []
    }
  },

  computed: {
    // ✅ Stats sécurisées sans NaN
    safeStats() {
      const rawStats = this.stats || {}
      
      return {
        total: this.safeNumber(rawStats.total, 0),
        active: this.safeNumber(rawStats.active, 0),
        draft: this.safeNumber(rawStats.draft, 0),
        revenue: this.safeNumber(rawStats.revenue, 0),
        averagePrice: this.calculateAveragePrice()
      }
    }
  },

  methods: {
    // ✅ Fonction pour éviter NaN
    safeNumber(value, defaultValue = 0) {
      const num = Number(value)
      return isNaN(num) || !isFinite(num) ? defaultValue : num
    },

    // ✅ Calcul correct de la moyenne des prix
    calculateAveragePrice() {
      if (!this.products || this.products.length === 0) {
        return 0
      }

      const validProducts = this.products.filter(product => {
        const price = Number(product.price)
        return !isNaN(price) && isFinite(price) && price > 0
      })

      if (validProducts.length === 0) {
        return 0
      }

      const totalPrice = validProducts.reduce((sum, product) => {
        return sum + Number(product.price)
      }, 0)

      const average = totalPrice / validProducts.length
      return this.safeNumber(average, 0)
    },

    formatPrice(price) {
      const safePrice = this.safeNumber(price, 0)
      
      return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'EUR',
        minimumFractionDigits: 0,
        maximumFractionDigits: 2
      }).format(safePrice)
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
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.stat-item:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
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