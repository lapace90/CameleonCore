<template>
  <aside class="admin-sidebar">
    <!-- Brand -->
    <div class="sidebar-brand">
      <h2>⛺ CampCameleonX</h2>
      <span class="brand-subtitle">Admin Dashboard</span>
    </div>
    
    <!-- Navigation -->
    <nav class="sidebar-nav">
      <ul class="nav-list">
        <!-- Dashboard -->
        <li class="nav-item">
          <router-link to="/admin/dashboard" class="nav-link">
            <i class="fas fa-chart-pie"></i>
            <span>Dashboard</span>
          </router-link>
        </li>
        
        <!-- Users -->
        <li class="nav-item">
          <router-link to="/admin/users" class="nav-link">
            <i class="fas fa-users"></i>
            <span>Utilisateurs</span>
          </router-link>
        </li>
        
        <!-- Analytics -->
        <li class="nav-item">
          <router-link to="/admin/analytics" class="nav-link">
            <i class="fas fa-chart-line"></i>
            <span>Analytics</span>
          </router-link>
        </li>
        
        <!-- Divider -->
        <li class="nav-divider">
          <hr>
          <span>Gestion</span>
        </li>
        
        <!-- Settings -->
        <li class="nav-item">
          <router-link to="/admin/settings" class="nav-link">
            <i class="fas fa-cog"></i>
            <span>Paramètres</span>
          </router-link>
        </li>
        
        <!-- Back to site -->
        <li class="nav-item">
          <router-link to="/home" class="nav-link nav-link-external">
            <i class="fas fa-external-link-alt"></i>
            <span>Voir le site</span>
          </router-link>
        </li>
      </ul>
    </nav>
    
    <!-- User profile in sidebar -->
    <div class="sidebar-user">
      <div class="user-avatar">
        <img src="https://via.placeholder.com/40" alt="User">
      </div>
      <div class="user-info">
        <span class="user-name">Admin User</span>
        <span class="user-role">Administrateur</span>
      </div>
    </div>
  </aside>
</template>

<script>
export default {
  name: 'AdminSidebar'
}
</script>

