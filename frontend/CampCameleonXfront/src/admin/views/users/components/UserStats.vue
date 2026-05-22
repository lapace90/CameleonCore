<template>
  <div class="users-stats">
    <div class="stat-card">
      <div class="stat-icon">
        <AppIcon name="users" />
      </div>
      <div class="stat-content">
        <div class="stat-number">{{ totalUsers }}</div>
        <div class="stat-label">Utilisateurs totaux</div>
      </div>
    </div>
    
    <div class="stat-card">
      <div class="stat-icon warning">
        <AppIcon name="user-check" />
      </div>
      <div class="stat-content">
        <div class="stat-number">{{ activeUsers }}</div>
        <div class="stat-label">Utilisateurs actifs</div>
      </div>
    </div>
    
    <div class="stat-card">
      <div class="stat-icon  success">
        <AppIcon name="crown" />
      </div>
      <div class="stat-content">
        <div class="stat-number">{{ adminUsers }}</div>
        <div class="stat-label">Administrateurs</div>
      </div>
    </div>
    
    <div class="stat-card">
      <div class="stat-icon  info">
        <AppIcon name="user-plus" />
      </div>
      <div class="stat-content">
        <div class="stat-number">{{ recentUsers }}</div>
        <div class="stat-label">Nouveaux (7j)</div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'UserStats',
  props: {
    totalUsers: {
      type: Number,
      required: true
    },
    activeUsers: {
      type: Number,
      required: true
    },
    adminUsers: {
      type: Number,
      required: true
    },
    recentUsers: {
      type: Number,
      required: true
    }
  }
}
</script>

<style scoped lang="scss">

.users-stats {
  display: grid;
  gap: 1rem;
  min-width: 100%;
  margin-bottom: 1.5rem;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
}
</style>
