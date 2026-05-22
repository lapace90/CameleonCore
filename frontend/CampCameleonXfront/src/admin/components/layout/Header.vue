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
        <div class="search-box" ref="searchContainer">
          <AppIcon name="search" />
          <input
            type="text"
            placeholder="Rechercher..."
            v-model="searchQuery"
            @input="handleSearch"
            @keydown.esc="closeSearch"
            @focus="onSearchFocus"
            autocomplete="off"
          >
          <button v-if="searchQuery" class="search-clear" @click="clearSearch">
            <AppIcon name="x" />
          </button>

          <!-- Dropdown résultats -->
          <div class="search-dropdown" v-show="showSearchResults">
            <div v-if="isSearching" class="search-state">
              <span class="search-spinner"></span>
              <span>Recherche en cours...</span>
            </div>

            <div v-else-if="hasResults" class="search-results">
              <!-- Produits -->
              <div v-if="searchResults.products.length" class="results-section">
                <div class="results-section-title">Produits</div>
                <button
                  v-for="product in searchResults.products"
                  :key="'p-' + product.id"
                  class="result-item"
                  @click="navigateTo({ name: 'ProductDetail', params: { type: product.urlType, id: product.id } })"
                >
                  <AppIcon name="package" class="result-icon" />
                  <div class="result-text">
                    <span class="result-name">{{ product.name }}</span>
                    <span class="result-meta">{{ product.typeLabel }}</span>
                  </div>
                </button>
              </div>

              <!-- Utilisateurs -->
              <div v-if="searchResults.users.length" class="results-section">
                <div class="results-section-title">Utilisateurs</div>
                <button
                  v-for="user in searchResults.users"
                  :key="'u-' + user.id"
                  class="result-item"
                  @click="navigateTo({ name: 'UserDetail', params: { id: user.id } })"
                >
                  <AppIcon name="user" class="result-icon" />
                  <div class="result-text">
                    <span class="result-name">{{ user.name }}</span>
                    <span class="result-meta">{{ user.email }}</span>
                  </div>
                </button>
              </div>

              <!-- Réservations -->
              <div v-if="searchResults.reservations.length" class="results-section">
                <div class="results-section-title">Réservations</div>
                <button
                  v-for="res in searchResults.reservations"
                  :key="'r-' + res.id"
                  class="result-item"
                  @click="navigateTo({ name: 'ReservationDetail', params: { id: res.id } })"
                >
                  <AppIcon name="calendar" class="result-icon" />
                  <div class="result-text">
                    <span class="result-name">{{ res.customer_name || res.customerName || '#' + res.id }}</span>
                    <span class="result-meta">{{ res.status || 'Réservation' }}</span>
                  </div>
                </button>
              </div>
            </div>

            <div v-else-if="searchQuery.length >= 2" class="search-state">
              <span>Aucun résultat pour "{{ searchQuery }}"</span>
            </div>
          </div>
        </div>

        <!-- Notifications -->
        <div class="header-notification" ref="notificationContainer">
          <button class="notification-btn" @click="toggleNotifications">
            <AppIcon name="bell" />
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
            <AppIcon name="chevron-down" :class="{ 'rotated': showUserMenu }" />
          </button>

          <!-- Dropdown user menu -->
          <div class="user-dropdown" v-show="showUserMenu" @click.stop>
            <router-link to="/admin/profile" class="dropdown-item" @click="closeDropdowns">
              <AppIcon name="user" />
              <span>Mon profil</span>
            </router-link>
            <router-link to="/admin/settings" class="dropdown-item" @click="closeDropdowns">
              <AppIcon name="settings" />
              <span>Paramètres</span>
            </router-link>
            <div class="dropdown-divider"></div>
            <button class="dropdown-item logout-btn" @click="handleLogout">
              <AppIcon name="log-out" />
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
import httpClient from '@/services/httpClient'

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
      notificationCount: 0,
      showSearchResults: false,
      isSearching: false,
      searchResults: { products: [], users: [], reservations: [] },
      searchDebounceTimer: null
    }
  },

  computed: {
    hasResults() {
      const r = this.searchResults
      return r.products.length > 0 || r.users.length > 0 || r.reservations.length > 0
    },

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
    // Gestionnaire pour la recherche (avec debounce)
    handleSearch() {
      clearTimeout(this.searchDebounceTimer)
      const q = this.searchQuery.trim()
      if (q.length < 2) {
        this.showSearchResults = false
        this.searchResults = { products: [], users: [], reservations: [] }
        return
      }
      this.showSearchResults = true
      this.isSearching = true
      this.searchDebounceTimer = setTimeout(() => this.performSearch(q), 350)
    },

    onSearchFocus() {
      if (this.searchQuery.trim().length >= 2) {
        this.showSearchResults = true
      }
    },

    async performSearch(q) {
      try {
        const [prodRes, usersRes, resRes] = await Promise.allSettled([
          httpClient.get('/products', { params: { search: q, per_page: 4 } }),
          httpClient.get('/admin/users', { params: { name: q } }),
          httpClient.get('/admin/reservations', { params: { search: q, itemsPerPage: 4 } })
        ])

        const productableTypeMap = {
          'App\\Models\\Room': 'room',
          'App\\Models\\Activity': 'activity',
          'App\\Models\\Menu': 'menu',
          'App\\Models\\Dish': 'dish',
          'App\\Models\\Ingredient': 'ingredient'
        }
        const typeLabelMap = {
          room: 'Chambre', activity: 'Activité', menu: 'Menu',
          dish: 'Plat', ingredient: 'Ingrédient'
        }

        let products = []
        if (prodRes.status === 'fulfilled') {
          const raw = prodRes.value.data
          const list = raw?.member || raw?.['hydra:member'] || (Array.isArray(raw) ? raw : [])
          products = list.slice(0, 4).map(p => {
            const urlType = productableTypeMap[p.productableType] || p.productableType || 'room'
            return { id: p.id, name: p.name, urlType, typeLabel: typeLabelMap[urlType] || urlType }
          })
        }

        let users = []
        if (usersRes.status === 'fulfilled') {
          const raw = usersRes.value.data
          const list = raw?.['hydra:member'] || raw?.data || (Array.isArray(raw) ? raw : [])
          const lq = q.toLowerCase()
          users = list
            .filter(u => u.name?.toLowerCase().includes(lq) || u.email?.toLowerCase().includes(lq))
            .slice(0, 4)
            .map(u => ({ id: u.id, name: u.name, email: u.email }))
        }

        let reservations = []
        if (resRes.status === 'fulfilled') {
          const raw = resRes.value.data
          const list = raw?.['hydra:member'] || raw?.data || (Array.isArray(raw) ? raw : [])
          reservations = list.slice(0, 4).map(r => ({
            id: r.id,
            customer_name: r.customer_name || r.customerName || r.customer?.name,
            status: r.status
          }))
        }

        this.searchResults = { products, users, reservations }
      } catch {
        this.searchResults = { products: [], users: [], reservations: [] }
      } finally {
        this.isSearching = false
      }
    },

    clearSearch() {
      this.searchQuery = ''
      this.closeSearch()
    },

    closeSearch() {
      this.showSearchResults = false
      this.searchResults = { products: [], users: [], reservations: [] }
    },

    navigateTo(route) {
      this.closeSearch()
      this.searchQuery = ''
      this.$router.push(route)
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
      const notificationContainer = this.$refs.notificationContainer
      const userMenuContainer = this.$refs.userMenuContainer
      const searchContainer = this.$refs.searchContainer

      if (notificationContainer && !notificationContainer.contains(event.target)) {
        this.showNotifications = false
      }
      if (userMenuContainer && !userMenuContainer.contains(event.target)) {
        this.showUserMenu = false
      }
      if (searchContainer && !searchContainer.contains(event.target)) {
        this.showSearchResults = false
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

    .app-icon {
      position: absolute;
      left: 0.75rem;
      color: #adb5bd;
      font-size: 0.875rem;
      pointer-events: none;
    }

    input {
      padding: 0.5rem 2rem 0.5rem 2.5rem;
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

    .search-clear {
      position: absolute;
      right: 0.5rem;
      background: none;
      border: none;
      cursor: pointer;
      color: #adb5bd;
      display: flex;
      align-items: center;
      padding: 0.125rem;
      border-radius: 50%;

      &:hover {
        color: #495057;
        background: #e9ecef;
      }

      .app-icon {
        position: static;
        font-size: 0.75rem;
        pointer-events: none;
      }
    }

    .search-dropdown {
      position: absolute;
      top: calc(100% + 0.5rem);
      left: 0;
      width: 340px;
      background: white;
      border-radius: 0.5rem;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
      border: 1px solid #e9ecef;
      z-index: 1100;
      overflow: hidden;
      animation: dropdownAppear 0.15s ease-out;

      .search-state {
        display: flex;
        align-items: center;
        gap: 0.625rem;
        padding: 1rem 1.25rem;
        font-size: 0.875rem;
        color: #6c757d;
      }

      .search-spinner {
        width: 14px;
        height: 14px;
        border: 2px solid #dee2e6;
        border-top-color: #5e72e4;
        border-radius: 50%;
        animation: spin 0.6s linear infinite;
        flex-shrink: 0;
      }

      .results-section {
        &:not(:last-child) {
          border-bottom: 1px solid #f1f3f5;
        }
      }

      .results-section-title {
        padding: 0.5rem 1rem 0.375rem;
        font-size: 0.7rem;
        font-weight: 700;
        letter-spacing: 0.05em;
        text-transform: uppercase;
        color: #adb5bd;
        background: #f8f9fa;
      }

      .result-item {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        width: 100%;
        padding: 0.625rem 1rem;
        background: none;
        border: none;
        cursor: pointer;
        text-align: left;
        transition: background 0.15s;

        &:hover {
          background: #f8f9ff;
        }

        .result-icon {
          position: static;
          color: #5e72e4;
          font-size: 0.875rem;
          flex-shrink: 0;
        }

        .result-text {
          display: flex;
          flex-direction: column;
          min-width: 0;

          .result-name {
            font-size: 0.875rem;
            color: #32325d;
            font-weight: 500;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
          }

          .result-meta {
            font-size: 0.75rem;
            color: #8898aa;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
          }
        }
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

      .app-icon {
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

        .app-icon {
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

@keyframes spin {
  to { transform: rotate(360deg); }
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