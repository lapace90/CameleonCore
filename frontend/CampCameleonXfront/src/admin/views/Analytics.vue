<template>
  <div class="analytics-page">
    <div class="page-header">
      <div class="date-range">
        <label for="date-select">Trier par: </label>
        <select class="date-select">
          <option>7 derniers jours</option>
          <option>30 derniers jours</option>
          <option>3 derniers mois</option>
        </select>
      </div>
    </div>
    
    <div class="analytics-grid">
      <div class="analytics-card">
        <h3>Trafic du site</h3>
        <div class="chart-placeholder">
          <i class="fas fa-chart-area"></i>
          <p>Graphique de trafic</p>
        </div>
      </div>
      
      <div class="analytics-card">
        <h3>Sources de trafic</h3>
        <div class="chart-placeholder">
          <i class="fas fa-chart-pie"></i>
          <p>Répartition des sources</p>
        </div>
      </div>
      
      <div class="analytics-card">
        <h3>Conversions</h3>
        <div class="chart-placeholder">
          <i class="fas fa-chart-line"></i>
          <p>Taux de conversion</p>
        </div>
      </div>
      
      <div class="analytics-card">
        <h3>Géolocalisation</h3>
        <div class="chart-placeholder">
          <i class="fas fa-globe"></i>
          <p>Carte des visiteurs</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'AdminAnalytics'
}
</script>

<style scoped>
.analytics-page {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.analytics-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 1.5rem;
}

.analytics-card {
  background: white;
  border-radius: 0.75rem;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.12);
  border: 1px solid #e9ecef;
  padding: 1.5rem;
}

.analytics-card h3 {
  color: #32325d;
  margin: 0 0 1rem 0;
  font-size: 1.125rem;
}

.chart-placeholder {
  padding: 3rem 1rem;
  text-align: center;
  color: #8898aa;
  background: #f8f9fe;
  border-radius: 0.5rem;
}

.chart-placeholder i {
  font-size: 2.5rem;
  display: block;
  margin-bottom: 1rem;
  opacity: 0.5;
}
</style>