<template>
  <header class="admin-header">
    <div class="header-content">
      <!-- Left side - Page title and breadcrumb -->
      <div class="header-left">
        <h1 class="page-title">{{ pageTitle }}</h1>
        
        <!-- 🔥 Breadcrumb simplifié - génération automatique -->
        <Breadcrumb />
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
              <span class="mark-all-read" @click="markAllRead">Tout marquer comme lu</span>
            </div>
            
            <div class="notification-list">
              <div 
                v-for="notification in notifications" 
                :key="notification.id"
                class="notification-item"
              >
                <div class="notification-icon">
                  <i :class="notification.icon"></i>
                </div>
                <div class="notification-content">
                  <p class="notification-message">{{ notification.message }}</p>
                  <span class="notification-time">{{ notification.time }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- User menu -->
        <div class="user-menu">
          <button class="user-btn" @click="toggleUserMenu">
            <img 
              :src="auth.user?.avatar || '/default-avatar.png'" 
              :alt="auth.user?.name || 'User'"
              class="user-avatar"
            >
            <span class="user-name">{{ auth.user?.name || 'Admin' }}</span>
            <i class="fas fa-chevron-down"></i>
          </button>

          <!-- Dropdown user menu -->
          <div class="user-dropdown" v-show="showUserMenu">
            <router-link to="/admin/profile" class="dropdown-item">
              <i class="fas fa-user"></i>
              <span>Mon profil</span>
            </router-link>
            <router-link to="/admin/settings" class="dropdown-item">
              <i class="fas fa-cog"></i>
              <span>Paramètres</span>
            </router-link>
            <div class="dropdown-divider"></div>
            <button class="dropdown-item logout-btn" @click="handleLogout">
              <i class="fas fa-sign-out-alt"></i>
              <span>Se déconnecter</span>
            </button>
          </div>
        </div>
      </div>
    </div>
  </header>
</template>

<script>
import Breadcrumb from '@/admin/components/Breadcrumb.vue'
import { useAuthStore } from '@/shared/stores/auth'
import { PRODUCT_CONFIGS } from '@/shared/configs/productConfigs'

export default {
  name: 'AdminHeader',

  components: {
    Breadcrumb
  },

  setup() {
    const auth = useAuthStore()
    return { auth }
  },

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
          icon: 'fas fa-calendar text-warning',
          message: 'Nouvelle réservation',
          time: 'Il y a 5 minutes'
        },
        {
          id: 3,
          icon: 'fas fa-exclamation-triangle text-danger',
          message: 'Alerte système',
          time: 'Il y a 10 minutes'
        }
      ]
    }
  },

  computed: {
    pageTitle() {
      const route = this.$route

      // Page dashboard
      if (route.name === 'AdminDashboard') {
        return 'Dashboard'
      }

      // Routes produits
      if (route.path.includes('/products/')) {
        const type = route.params.type
        const config = PRODUCT_CONFIGS[type]
        const label = config?.label || 'Produit'

        if (route.name === 'ProductCreate') {
          return `Nouveau ${config?.singular || label}`
        } else if (route.name === 'ProductEdit') {
          return `Modifier ${config?.singular || label}`
        } else if (route.name === 'ProductDetail') {
          return `Détails ${config?.singular || label}`
        } else {
          return label
        }
      }

      // Autres routes avec titres personnalisés
      const routeTitles = {
        'AdminUsers': 'Gestion des utilisateurs',
        'UserCreate': 'Nouvel utilisateur', 
        'UserEdit': 'Modifier utilisateur',
        'AdminRoles': 'Rôles',
        'RoleCreate': 'Nouveau rôle',
        'RoleEdit': 'Modifier rôle',
        'AdminPermissions': 'Permissions',
        'AdminCategories': 'Catégories',
        'CategoryCreate': 'Nouvelle catégorie',
        'CategoryEdit': 'Modifier catégorie',
        'AdminAnalytics': 'Analytics',
        'AdminSettings': 'Paramètres',
        'AdminReservations': 'Réservations',
        'FullAgenda': 'Planning'
      }

      return routeTitles[route.name] || route.meta?.title || 'Administration'
    }
  },

  methods: {
    handleSearch() {
      console.log('Recherche:', this.searchQuery)
      // Implémenter la logique de recherche
    },

    toggleNotifications() {
      this.showNotifications = !this.showNotifications
      this.showUserMenu = false
    },

    toggleUserMenu() {
      this.showUserMenu = !this.showUserMenu  
      this.showNotifications = false
    },

    markAllRead() {
      this.notificationCount = 0
      this.showNotifications = false
      // Implémenter la logique de marquage lu
    },

    handleLogout() {
      this.auth.logout()
      this.$router.push('/admin/login')
    },

    // Fermer les dropdowns en cliquant ailleurs
    handleClickOutside(event) {
      if (!this.$el.contains(event.target)) {
        this.showNotifications = false
        this.showUserMenu = false
      }
    }
  },

  mounted() {
    document.addEventListener('click', this.handleClickOutside)
  },

  beforeUnmount() {
    document.removeEventListener('click', this.handleClickOutside)
  }
}
</script>

<style lang="scss" scoped>
.admin-header {
  padding: 0 2rem;
  top: 0;

  .header-content {
    display: flex;
    align-items: center;
    justify-content: space-between;
    height: 100%;
    max-width: 100%;
  }


  // Styles pour les notifications
  .header-notification {
    position: relative;

    .notification-btn {
      background: none;
      border: none;
      color: var(--text-light);
      font-size: 1.2rem;
      padding: 0.5rem;
      border-radius: 0.375rem;
      cursor: pointer;
      position: relative;
      transition: color 0.2s ease;

      &:hover {
        color: var(--primary);
        background: var(--text-light);
      }
    }

    .notification-dropdown {
      

      .dropdown-header {
        padding: 1rem 1.25rem 0.75rem;
        border-bottom: 1px solid #e9ecef;
        display: flex;
        justify-content: space-between;
        align-items: center;

        .mark-all-read {
          font-size: 0.875rem;
          color: var(--primary);
          cursor: pointer;
          text-decoration: none;

          &:hover {
            color: #576ff8;
          }
        }
      }

      .notification-list {
        max-height: 300px;
        overflow-y: auto;
      }

      .notification-item {
        padding: 1rem 1.25rem;
        border-bottom: 1px solid #f6f9fc;
        display: flex;
        align-items: flex-start;
        gap: 0.75rem;

        &:last-child {
          border-bottom: none;
        }

        &:hover {
          background: #f8f9ff;
        }


       
      }
    }
  }

  // Styles pour le menu utilisateur
  .user-menu {
    position: relative;

    .user-btn {
      display: flex;
      align-items: center;
      gap: 0.5rem;
      background: none;
      border: none;
      padding: 0.5rem;
      border-radius: 0.375rem;
      cursor: pointer;
      transition: background-color 0.2s ease;

      &:hover {
        background: #f8f9ff;
      }

      .user-name {
        font-size: 0.875rem;
        font-weight: 500;
        color: #32325d;
        max-width: 120px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
      }

      i {
        font-size: 0.75rem;
        color: #adb5bd;
        transition: transform 0.2s ease;
      }

      &:hover i {
        transform: rotate(180deg);
      }
    }

    .user-dropdown {
      position: absolute;
      top: 100%;
      right: 0;
      width: 200px;
      background: white;
      border-radius: 0.5rem;
      box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
      border: 1px solid #e9ecef;
      margin-top: 0.5rem;
      z-index: 1050;
      padding: 0.5rem 0;

      .dropdown-item {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.75rem 1.25rem;
        color: #32325d;
        text-decoration: none;
        font-size: 0.875rem;
        transition: background-color 0.2s ease;
        border: none;
        background: none;
        width: 100%;
        text-align: left;
        cursor: pointer;

        &:hover {
          background: #f8f9ff;
          color: #5e72e4;
        }

        i {
          font-size: 0.875rem;
          width: 16px;
        }
      }

      .dropdown-divider {
        height: 1px;
        background: #e9ecef;
        margin: 0.5rem 0;
      }

      .logout-btn {
        color: #f5365c;

        &:hover {
          background: #fef5f5;
          color: #f5365c;
        }
      }
    }
  }
}

// Responsive
@media (max-width: 768px) {
  .admin-header {
    padding: 0 1rem;
    height: 70px;

    .header-left .page-title {
      font-size: 1.25rem;
    }

    .search-box {
      display: none; // Masquer sur mobile
    }

    .user-menu .user-name {
      display: none; // Masquer le nom sur mobile
    }
  }
}
</style>