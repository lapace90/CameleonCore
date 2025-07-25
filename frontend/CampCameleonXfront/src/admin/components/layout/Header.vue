<template>
  <header class="admin-header">
    <div class="header-content">
      <!-- Left side - Page title and breadcrumb -->
      <div class="header-left">
        <h1 class="page-title">{{ pageTitle }}</h1>
        <nav class="breadcrumb" v-if="breadcrumbs.length">
          <router-link 
            v-for="(crumb, index) in breadcrumbs" 
            :key="index"
            :to="crumb.path"
            class="breadcrumb-item"
            :class="{ 'active': index === breadcrumbs.length - 1 }"
          >
            {{ crumb.name }}
          </router-link>
        </nav>
      </div>
      
      <!-- Right side - Actions and user menu -->
      <div class="header-right">
        <!-- Search -->
        <div class="search-box">
          <i class="fas fa-search"></i>
          <input 
            type="text" 
            placeholder="Rechercher..." 
            v-model="searchQuery"
            @input="handleSearch"
          >
        </div>
        
        <!-- Notifications -->
        <div class="header-notification">
          <button class="notification-btn" @click="toggleNotifications">
            <i class="fas fa-bell"></i>
            <span class="notification-badge" v-if="notificationCount">{{ notificationCount }}</span>
          </button>
          
          <!-- Dropdown notifications -->
          <div class="notification-dropdown" v-show="showNotifications">
            <div class="dropdown-header">
              <h6>Notifications</h6>
              <span class="badge">{{ notificationCount }} nouvelles</span>
            </div>
            <div class="notification-item" v-for="notification in notifications" :key="notification.id">
              <div class="notification-icon">
                <i :class="notification.icon"></i>
              </div>
              <div class="notification-content">
                <p class="notification-text">{{ notification.message }}</p>
                <span class="notification-time">{{ notification.time }}</span>
              </div>
            </div>
            <div class="dropdown-footer">
              <a href="#" class="view-all">Voir toutes les notifications</a>
            </div>
          </div>
        </div>
        
        <!-- User menu -->
        <div class="user-menu">
          <button class="user-btn" @click="toggleUserMenu">
            <img src="https://via.placeholder.com/32" alt="User" class="user-avatar">
            <span class="user-name">Admin User</span>
            <i class="fas fa-chevron-down"></i>
          </button>
          
          <!-- User dropdown -->
          <div class="user-dropdown" v-show="showUserMenu">
            <div class="dropdown-header">
              <img src="https://via.placeholder.com/50" alt="User" class="user-avatar-large">
              <div class="user-details">
                <span class="user-name">Admin User</span>
                <span class="user-email">admin@campcameleonx.com</span>
              </div>
            </div>
            <hr class="dropdown-divider">
            <a href="#" class="dropdown-item">
              <i class="fas fa-user"></i>
              Mon profil
            </a>
            <a href="#" class="dropdown-item">
              <i class="fas fa-cog"></i>
              Paramètres
            </a>
            <hr class="dropdown-divider">
            <a href="#" class="dropdown-item text-danger" @click="logout">
              <i class="fas fa-sign-out-alt"></i>
              Déconnexion
            </a>
          </div>
        </div>
      </div>
    </div>
  </header>
</template>

<script>
export default {
  name: 'AdminHeader',
  data() {
    return {
      searchQuery: '',
      showNotifications: false,
      showUserMenu: false,
      notificationCount: 3,
      notifications: [
        {
          id: 1,
          icon: 'fas fa-user text-success',
          message: 'Nouvel utilisateur inscrit',
          time: 'Il y a 2 minutes'
        },
        {
          id: 2,
          icon: 'fas fa-shopping-cart text-warning',
          message: 'Nouvelle réservation',
          time: 'Il y a 5 minutes'
        },
        {
          id: 3,
          icon: 'fas fa-exclamation-triangle text-danger',
          message: 'Erreur système détectée',
          time: 'Il y a 10 minutes'
        }
      ]
    }
  },
  computed: {
    pageTitle() {
      // Génère le titre basé sur la route actuelle
      const routeName = this.$route.name;
      const titles = {
        'AdminDashboard': 'Dashboard',
        'AdminUsers': 'Gestion des utilisateurs',
        'AdminAnalytics': 'Analytics',
        'FullAgenda': 'Agenda',
        'AdminSettings': 'Paramètres',
        // 'ProductShow': this.$type.name,
        // 'ProductDetail': this.$product.name,
        // 'ProductForm': this.$product.name       
      };
      return titles[routeName] || 'Admin';
    },
    breadcrumbs() {
      // Génère le fil d'Ariane basé sur la route
      const route = this.$route;
      const breadcrumbs = [
        { name: 'Admin', path: '/admin' }
      ];
      
      if (route.name !== 'AdminDashboard') {
        breadcrumbs.push({
          name: this.pageTitle,
          path: route.path
        });
      }
      
      return breadcrumbs;
    }
  },
  methods: {
    handleSearch() {
      // Logique de recherche
      console.log('Recherche:', this.searchQuery);
    },
    toggleNotifications() {
      this.showNotifications = !this.showNotifications;
      this.showUserMenu = false;
    },
    toggleUserMenu() {
      this.showUserMenu = !this.showUserMenu;
      this.showNotifications = false;
    },
    logout() {
      // Logique de déconnexion
      console.log('Déconnexion');
      this.$router.push('/home');
    }
  },
  mounted() {
    // Fermer les dropdowns en cliquant ailleurs
    document.addEventListener('click', (e) => {
      if (!this.$el.contains(e.target)) {
        this.showNotifications = false;
        this.showUserMenu = false;
      }
    });
  }
}
</script>

