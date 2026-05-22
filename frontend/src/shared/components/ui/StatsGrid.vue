<template>
  <div :class="gridClasses">
    <div
      v-for="(stat, index) in stats"
      :key="index"
      class="stat-item"
    >
      <div :class="['stat-icon', `bg-${stat.color || 'primary'}`]">
        <i :class="stat.icon"></i>
      </div>
      <div class="stat-info">
        <h4>{{ stat.value }}</h4>
        <p>{{ stat.label }}</p>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'StatsGrid',
  props: {
    stats: {
      type: Array,
      required: true
    },
    columns: {
      type: Number,
      default: 3
    }
  },
  
  computed: {
    gridClasses() {
      return [
        'stats-grid',
        `stats-grid-${this.columns}`
      ]
    }
  }
}
</script>

<style scoped>
.stats-grid {
  display: grid;
  gap: 1.5rem;
}

.stats-grid-1 {
  grid-template-columns: 1fr;
}

.stats-grid-2 {
  grid-template-columns: repeat(2, 1fr);
}

.stats-grid-3 {
  grid-template-columns: repeat(3, 1fr);
}

.stats-grid-4 {
  grid-template-columns: repeat(4, 1fr);
}

.stat-item {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1rem;
  background: var(--bg-light);
  border-radius: var(--border-radius-lg);
}

.stat-icon {
  width: 3rem;
  height: 3rem;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 1.2rem;
}

.stat-info h4 {
  margin: 0 0 0.25rem 0;
  font-size: 1.25rem;
  font-weight: 700;
  color: var(--text-primary);
}

.stat-info p {
  margin: 0;
  font-size: 0.875rem;
  color: var(--text-muted);
}

@media (max-width: 768px) {
  .stats-grid-2,
  .stats-grid-3,
  .stats-grid-4 {
    grid-template-columns: 1fr;
  }
}
</style>