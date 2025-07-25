<template>
  <aside class="admin-sidebar">
    <!-- Brand -->
    <router-link to="/admin/dashboard" class="nav-link">
      <div class="sidebar-brand">
        <h2>🦎 CampCameleonX</h2>
        <span class="brand-subtitle">Admin Dashboard</span>
      </div>
    </router-link>
    <!-- Navigation -->
    <nav class="sidebar-nav">
      <ul class="nav-list">
        <!-- Agenda -->
        <li class="nav-item">
          <router-link to="/admin/agenda" class="nav-link">
            <i class="fas fa-calendar"></i>
            <span>Agenda</span>
          </router-link>
        </li>
        <!-- Divider -->
        <li class="nav-divider">
          <hr>
          <span>Services</span>
        </li>
        <!-- Restauration avec sous-menu -->
         <li class="nav-item">
          <a @click="toggleSubmenu('restauration')" class="nav-link" style="cursor: pointer;">
            <i class="fa-solid fa-utensils"></i>
            <span>Restauration</span>
            <i class="fas fa-chevron-down" :class="{ rotated: openSubmenu === 'restauration' }" style="margin-left: auto; font-size: 0.8rem;"></i>
          </a>
          <ul v-show="openSubmenu === 'restauration'" style="list-style: none; padding-left: 2rem; margin: 0;">
            <li><router-link to="/admin/products/menu" class="nav-link" style="padding-left: 2rem; font-size: 0.9rem;">Gérer les Menus</router-link></li>
            <li><router-link to="/admin/products/dish" class="nav-link" style="padding-left: 2rem; font-size: 0.9rem;">Gérer les Plats</router-link></li>
            <li><router-link to="/admin/products/ingredient" class="nav-link" style="padding-left: 2rem; font-size: 0.9rem;">Ingrédients</router-link></li>
            <!-- <li><router-link to="/admin/restauration/reservations" class="nav-link" style="padding-left: 2rem; font-size: 0.9rem;">Réservations</router-link></li> Penser à l'URL -->
            <li><router-link to="/admin/products/stock" class="nav-link" style="padding-left: 2rem; font-size: 0.9rem;">Stock et Courses</router-link></li>
          </ul>
        </li>
        <!-- Activités -->
        <li class="nav-item">
          <router-link to="/admin/products/activity" class="nav-link">
            <i class="fa-solid fa-person-hiking"></i>
            <span>Activités</span>
          </router-link>
        </li>
        <!-- Chambres -->
        <li class="nav-item">
          <router-link to="/admin/products/room" class="nav-link">
            <i class="fa-solid fa-bed"></i>
            <span>Chambres</span>
          </router-link>
        </li>
        <!-- Divider -->
        <li class="nav-divider">
          <hr>
          <span>???</span>
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
        <!-- <img src="https://via.placeholder.com/40" alt="User"> -->
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
  name: 'AdminSidebar',
  data() {
    return {
      openSubmenu: null
    }
  },
  methods: {
    toggleSubmenu(menuName) {
      this.openSubmenu = this.openSubmenu === menuName ? null : menuName
    }
  }
}
</script>

<style scoped>
.submenu-toggle {
  justify-content: space-between;
}
.submenu {
  list-style: none;
  padding-left: 2rem;
}
.submenu-link {
  display: block;
  padding: 0.5rem 1rem;
  color: #9ca3af;
  text-decoration: none;
}
.rotated {
  transform: rotate(180deg);
}</style>