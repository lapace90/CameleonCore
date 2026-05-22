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
        <!-- Divider -->
        <li class="nav-divider">
          <hr>
          <span>Gestion</span>
        </li>
        <!-- Agenda -->
        <li class="nav-item">
          <router-link to="/admin/agenda" class="nav-link">
            <AppIcon name="calendar" />
            <span>Agenda</span>
          </router-link>
        </li>

        <!-- Réservations -->
        <li class="nav-item">
          <router-link to="/admin/reservations" class="nav-link">
            <AppIcon name="calendar-check" />
            <span>Réservations</span>
          </router-link>
        </li>

        <!-- 💰 NOUVEAU : Facturation -->
        <li class="nav-item">
          <router-link to="/admin/invoices" class="nav-link">
            <AppIcon name="receipt" />
            <span>Facturation</span>
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
            <AppIcon name="utensils" />
            <span>Restauration</span>
            <AppIcon name="chevron-down" :class="{ rotated: openSubmenu === 'restauration' }"
              style="margin-left: auto; font-size: 0.8rem;" />
          </a>
          <ul v-show="openSubmenu === 'restauration'" style="list-style: none; padding-left: 2rem; margin: 0;">
            <li>
              <router-link to="/admin/products/menu" class="nav-link"
                style="padding-left: 2rem; font-size: 0.9rem;">Gérer les Menus</router-link>
            </li>
            <li>
              <router-link to="/admin/products/dish" class="nav-link"
                style="padding-left: 2rem; font-size: 0.9rem;">Gérer les Plats</router-link>
            </li>
            <li>
              <router-link to="/admin/products/ingredient" class="nav-link"
                style="padding-left: 2rem; font-size: 0.9rem;">Ingrédients</router-link>
            </li>
            <li>
              <router-link to="/admin/products/stock" class="nav-link"
                style="padding-left: 2rem; font-size: 0.9rem;">Stock et Courses</router-link>
            </li>
          </ul>
        </li>

        <!-- Activités -->
        <li class="nav-item">
          <router-link to="/admin/products/activity" class="nav-link">
            <AppIcon name="footprints" />
            <span>Activités</span>
          </router-link>
        </li>

        <!-- Chambres -->
        <li class="nav-item">
          <router-link to="/admin/products/room" class="nav-link">
            <AppIcon name="bed" />
            <span>Chambres</span>
          </router-link>
        </li>

        <!-- Catégories -->
        <li class="nav-item">
          <router-link to="/admin/categories" class="nav-link">
            <AppIcon name="tags" />
            <span>Catégories</span>
          </router-link>
        </li>

        <!-- Divider -->
        <li class="nav-divider">
          <hr>
          <span>Administration</span>
        </li>

        <!-- Users -->
        <li class="nav-item">
          <router-link to="/admin/users" class="nav-link">
            <AppIcon name="users" />
            <span>Utilisateurs</span>
          </router-link>
        </li>

        <!-- Reviews -->
        <li class="nav-item">
          <router-link to="/admin/reviews" class="nav-link">
            <AppIcon name="star" />
            <span>Avis Clients</span>
          </router-link>
        </li>

        <!-- Analytics -->
        <li class="nav-item">
          <router-link to="/admin/analytics" class="nav-link">
            <AppIcon name="trending-up" />
            <span>Analytics</span>
          </router-link>
        </li>

        <!-- Settings -->
        <li class="nav-item">
          <router-link to="/admin/settings" class="nav-link">
            <AppIcon name="settings" />
            <span>Paramètres</span>
          </router-link>
        </li>

        <!-- Back to site -->
        <li class="nav-item">
          <router-link to="/home" class="nav-link nav-link-external">
            <AppIcon name="external-link" />
            <span>Voir le site</span>
          </router-link>
        </li>
      </ul>
    </nav>

    <!-- User profile in sidebar -->
    <div class="sidebar-user">
      <div class="user-avatar">
        <!-- <img src="" alt="User"> -->
      </div>
      <div class="user-info">
        <span class="user-name"></span>
        <span class="user-role"></span>
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
}
</style>