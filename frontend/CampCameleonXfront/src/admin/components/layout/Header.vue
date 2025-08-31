<template>
  <header class="admin-header">
    <div class="header-content">
      <!-- Left side - Page title and breadcrumb -->
      <div class="header-left">
        <h1 class="page-title">{{ pageTitle }}</h1>

        <!-- 🔥 REMPLACEMENT : Votre ancien breadcrumb par le nouveau composant -->
        <Breadcrumb :items="breadcrumbs" />
      </div>

      <!-- Right side - Actions and user menu (GARDE VOTRE CODE EXISTANT) -->
      <div class="header-right">
        <!-- Search -->
        <div class="search-box">
          <i class="fas fa-search"></i>
          <input type="text" placeholder="Rechercher..." v-model="searchQuery" @input="handleSearch">
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
            <!-- Vos notifications existantes -->
          </div>
        </div>

        <!-- User menu -->
        <div class="user-menu">
          <button class="logout-btn btn-sm" @click="handleLogout">
            <i class="fas fa-sign-out-alt"></i>
          </button>
        </div>
      </div>
    </div>
  </header>
</template>

<script>
import Breadcrumb from '@/admin/components/Breadcrumb.vue'
import { useAuthStore } from '@/shared/stores/auth'

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
      const route = this.$route

      if (route.name === 'AdminDashboard') {
        return 'Dashboard'
      }

      // Routes produits
      if (route.path.includes('/products/')) {
        const type = route.params.type
        const productLabels = {
          ingredient: 'Ingrédient',
          activity: 'Activité',
          dish: 'Plat',
          menu: 'Menu',
          room: 'Hébergement'
        }
        const label = productLabels[type] || 'Produit'

        if (route.name === 'ProductCreate') {
          return `Créer ${label}`
        } else if (route.name === 'ProductEdit') {
          return `Éditer ${label}`
        } else if (route.name === 'ProductDetail') {
          return `Détails ${label}`
        }

        return label
      }

      // Autres routes
      const titles = {
        'AdminUsers': 'Gestion des utilisateurs',
        'AdminAnalytics': 'Analytics',
        'AdminSettings': 'Paramètres',
        'FullAgenda': 'Agenda'
      }

      return titles[route.name] || 'Admin'
    }
    ,
    breadcrumbs() {
      // Génère les breadcrumbs basés sur la route actuelle
      const route = this.$route;
      let breadcrumbs = [];

      // Page d'accueil
      breadcrumbs.push({ name: 'Accueil', path: '/admin/dashboard' });

      // Page actuelle
      if (route.name) {
        breadcrumbs.push({ name: this.pageTitle, path: null });
      }

      // Ajouter des routes spécifiques si nécessaire
      if (route.name === 'ProductDetail' || route.name === 'ProductForm') {
        breadcrumbs.push({ name: 'Produits', path: '/admin/products' });
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
    handleLogout() {
      this.auth.logout()
      this.$router.push('/admin/login')
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
}
</script>
