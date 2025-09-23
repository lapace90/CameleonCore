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

        <!-- ✅ CORRECTION: Utiliser NotificationPanel au lieu de notifications hardcodées -->
        <div class="header-notification">
          <button class="notification-btn" @click="toggleNotifications">
            <i class="fas fa-bell"></i>
            <span class="notification-badge" v-if="notificationCount > 0">{{ notificationCount }}</span>
          </button>

          <!-- ✅ Remplacement du dropdown par NotificationPanel -->
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
      notificationCount: 0 // ✅ CORRECTION: Initialiser à 0, sera mis à jour par NotificationPanel
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
    // ✅ CORRECTION: Fermer les dropdowns en cliquant ailleurs
    document.addEventListener('click', this.closeDropdowns)
  },

  beforeUnmount() {
    document.removeEventListener('click', this.closeDropdowns)
  },

  methods: {
    // ✅ Gestionnaire pour la recherche
    handleSearch() {
      if (this.searchQuery.length > 2) {
        console.log('🔍 Recherche:', this.searchQuery)
        // TODO: Implémenter la logique de recherche
      }
    },

    // ✅ Toggle notifications
    toggleNotifications() {
      this.showNotifications = !this.showNotifications
      this.showUserMenu = false // Fermer l'autre dropdown
    },

    // ✅ Toggle user menu
    toggleUserMenu() {
      this.showUserMenu = !this.showUserMenu
      this.showNotifications = false // Fermer l'autre dropdown
    },

    // ✅ NOUVEAU: Gestionnaire pour le count de notifications
    handleNotificationCountChanged(count) {
      this.notificationCount = count
    },

    // ✅ NOUVEAU: Gestionnaire pour le clic sur une notification
    handleNotificationClick(notification) {
      // Fermer le dropdown
      this.showNotifications = false
      
      // Émettre un événement si nécessaire pour d'autres composants
      this.$emit('notification-clicked', notification)
    },

    // ✅ Fermer les dropdowns
    closeDropdowns() {
      this.showNotifications = false
      this.showUserMenu = false
    },

    // ✅ Logout
    async handleLogout() {
      try {
        await this.auth.logout()
        this.$router.push('/admin/login')
      } catch (error) {
        console.error('❌ Erreur logout:', error)
      }
    },

    // ✅ Helpers pour les titres de produits
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