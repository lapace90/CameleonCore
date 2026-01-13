<template>
  <header class="admin-header">
    <div class="header-content">
      <!-- Left side - Page title and breadcrumb -->
      <div class="header-left">
        <h1 class="page-title">{{ pageTitle }}</h1>
        
        <!-- Breadcrumb simplifié - génération automatique -->
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
        <div class="header-notification" ref="notificationContainer">
          <button class="notification-btn" @click="toggleNotifications">
            <i class="fas fa-bell"></i>
            <span class="notification-badge" v-if="notificationCount > 0">{{ notificationCount }}</span>
          </button>

          <!-- Notification Dropdown -->
          <div 
            class="notification-dropdown" 
            v-show="showNotifications"
            @click.stop
          >
            <NotificationPanel 
              :limit="10" 
              :autoRefresh="false"
              @notification-count-changed="handleNotificationCountChanged"
              @notification-clicked="handleNotificationClick"
            />
          </div>
        </div>

        <!-- User menu -->
        <div class="user-menu" ref="userMenuContainer">
          <button class="user-btn" @click="toggleUserMenu">
            <img 
              :src="auth.user?.avatar || '/default-avatar.png'" 
              :alt="auth.user?.name || 'User'"
              class="user-avatar"
            >
            <span class="user-name">{{ auth.user?.name || 'Admin' }}</span>
            <i class="fas fa-chevron-down" :class="{ 'rotated': showUserMenu }"></i>
          </button>

          <!-- Dropdown user menu -->
          <div class="user-dropdown" v-show="showUserMenu" @click.stop>
            <router-link to="/admin/profile" class="dropdown-item" @click="closeDropdowns">
              <i class="fas fa-user"></i>
              <span>Mon profil</span>
            </router-link>
            <router-link to="/admin/settings" class="dropdown-item" @click="closeDropdowns">
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
import NotificationPanel from '@/admin/components/NotificationPanel.vue'
import { useAuthStore } from '@/shared/stores/auth'

export default {
  name: 'AdminHeader',

  components: {
    Breadcrumb,
    NotificationPanel
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
      notificationCount: 0
    }
  },

  computed: {
    pageTitle() {
      // Génération automatique du titre depuis la route
      const routeName = this.$route.name
      const routeParams = this.$route.params

      // Mapping des titres de page
      const pageTitles = {
        'AdminDashboard': 'Dashboard',
        'FullAgenda': 'Agenda',
        'AdminUsers': 'Utilisateurs',
        'AdminRoles': 'Rôles et Permissions',
        'AdminCategories': 'Catégories',
        'ProductsShow': this.getProductTitle(routeParams.type),
        'ProductCreate': `Créer ${this.getProductLabel(routeParams.type)}`,
        'ProductEdit': `Modifier ${this.getProductLabel(routeParams.type)}`,
        'AdminProfile': 'Mon Profil',
        'AdminSettings': 'Paramètres'
      }

      return pageTitles[routeName] || 'Administration'
    }
  },

  mounted() {
    // Écouter les clics globaux pour fermer les dropdowns
    document.addEventListener('click', this.handleGlobalClick)
  },

  beforeUnmount() {
    document.removeEventListener('click', this.handleGlobalClick)
  },

  methods: {
    // Gestionnaire pour la recherche
    handleSearch() {
      if (this.searchQuery.length > 2) {
        // TODO: Implémenter la logique de recherche
      }
    },

    // Toggle notifications
    toggleNotifications() {
      this.showNotifications = !this.showNotifications
      this.showUserMenu = false // Fermer l'autre dropdown
    },

    // Toggle user menu
    toggleUserMenu() {
      this.showUserMenu = !this.showUserMenu
      this.showNotifications = false // Fermer l'autre dropdown
    },

    // Gestionnaire global des clics
    handleGlobalClick(event) {
      // Vérifier si le clic est à l'intérieur des conteneurs de dropdown
      const notificationContainer = this.$refs.notificationContainer
      const userMenuContainer = this.$refs.userMenuContainer

      let clickInsideNotification = false
      let clickInsideUserMenu = false

      if (notificationContainer) {
        clickInsideNotification = notificationContainer.contains(event.target)
      }

      if (userMenuContainer) {
        clickInsideUserMenu = userMenuContainer.contains(event.target)
      }

      // Fermer les dropdowns si le clic est à l'extérieur
      if (!clickInsideNotification) {
        this.showNotifications = false
      }

      if (!clickInsideUserMenu) {
        this.showUserMenu = false
      }
    },

    // Gestionnaire pour le count de notifications
    handleNotificationCountChanged(count) {
      this.notificationCount = count
    },

    // Gestionnaire pour le clic sur une notification
    handleNotificationClick(notification) {
      // Fermer le dropdown
      this.showNotifications = false
      
      // Émettre un événement si nécessaire pour d'autres composants
      this.$emit('notification-clicked', notification)
    },

    // Fermer les dropdowns manuellement
    closeDropdowns() {
      this.showNotifications = false
      this.showUserMenu = false
    },

    // Logout
    async handleLogout() {
      try {
        await this.auth.logout()
        this.closeDropdowns() // Fermer les dropdowns avant de rediriger
        this.$router.push('/admin/login')
      } catch (error) {
        console.error('❌ Erreur logout:', error)
      }
    },

    // Helpers pour les titres de produits
    getProductTitle(type) {
      const titles = {
        'room': 'Chambres',
        'activity': 'Activités',
        'menu': 'Menus',
        'dish': 'Plats',
        'ingredient': 'Ingrédients'
      }
      return titles[type] || 'Produits'
    },

    getProductLabel(type) {
      const labels = {
        'room': 'une Chambre',
        'activity': 'une Activité', 
        'menu': 'un Menu',
        'dish': 'un Plat',
        'ingredient': 'un Ingrédient'
      }
      return labels[type] || 'un Produit'
    }
  }
}
</script>

<style lang="scss" scoped>
.admin-header {

  .header-content {
    align-items: center;
    justify-content: space-between;
    max-width: 100%;
  }

  .header-left {
    display: flex;
    flex-direction: column;

    .page-title {
      margin: 0;
      font-size: 1.5rem;
      font-weight: 600;
    }
  }

  .header-right {
    display: flex;
    align-items: center;
    gap: 1.5rem;
  }

  .search-box {
    position: relative;
    display: flex;
    align-items: center;

    i {
      position: absolute;
      left: 0.75rem;
      color: #adb5bd;
      font-size: 0.875rem;
    }

    input {
      padding: 0.5rem 0.75rem 0.5rem 2.5rem;
      border: 1px solid #dee2e6;
      border-radius: 0.375rem;
      background: #f8f9fa;
      font-size: 0.875rem;
      width: 250px;
      transition: all 0.2s ease;

      &:focus {
        outline: none;
        border-color: #5e72e4;
        background: white;
        box-shadow: 0 0 0 3px rgba(94, 114, 228, 0.1);
      }

      &::placeholder {
        color: #adb5bd;
      }
    }
  }

  // Styles pour les notifications
  .header-notification {
    position: relative;

    .notification-badge {
      position: absolute;
      top: 2px;
      right: 2px;
      background: #f5365c;
      color: white;
      font-size: 0.625rem;
      font-weight: 600;
      padding: 0.125rem 0.375rem;
      border-radius: 10px;
      min-width: 18px;
      height: 18px;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .notification-dropdown {
      position: absolute;
      top: 100%;
      right: 0;
      background: white;
      border-radius: 8px;
      box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
      width: 320px;
      margin-top: 0.5rem;
      z-index: 1001;
      overflow: hidden;
      animation: dropdownAppear 0.2s ease-out;
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

      .user-avatar {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        border: 2px solid #dee2e6;
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

        &.rotated {
          transform: rotate(180deg);
        }
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
      animation: dropdownAppear 0.2s ease-out;

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

@keyframes dropdownAppear {
  from {
    opacity: 0;
    transform: translateY(-10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
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