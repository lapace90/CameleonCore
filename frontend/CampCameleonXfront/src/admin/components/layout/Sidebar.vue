<template>
  <aside class="admin-sidebar">
    <!-- Logo/Brand -->
    <div class="sidebar-header">
      <router-link to="/admin/dashboard" class="sidebar-brand">
        <i class="fas fa-campground"></i>
        <span class="brand-text">Camp Admin</span>
      </router-link>
    </div>

    <!-- Navigation -->
    <nav class="sidebar-nav">
      <ul class="nav-list">
        <!-- Dashboard -->
        <li class="nav-item">
          <router-link to="/admin/dashboard" class="nav-link">
            <i class="fas fa-tachometer-alt"></i>
            <span>Tableau de bord</span>
          </router-link>
        </li>

        <!-- Divider -->
        <li class="nav-divider">
          <hr>
          <span>Catalogue</span>
        </li>

        <!-- Menu produits avec sous-menu -->
        <li class="nav-item" :class="{ 'has-submenu': true, 'open': openSubmenu === 'products' }">
          <button @click="toggleSubmenu('products')" class="nav-link nav-toggle">
            <i class="fas fa-box"></i>
            <span>Produits</span>
            <i class="fas fa-chevron-down nav-arrow"></i>
          </button>
          <ul class="nav-submenu" v-show="openSubmenu === 'products'">
            <li class="nav-subitem">
              <router-link to="/admin/products/dish" class="nav-sublink">
                <i class="fas fa-utensils"></i>
                <span>Plats</span>
              </router-link>
            </li>
            <li class="nav-subitem">
              <router-link to="/admin/products/ingredient" class="nav-sublink">
                <i class="fas fa-leaf"></i>
                <span>Ingrédients</span>
              </router-link>
            </li>
            <li class="nav-subitem">
              <router-link to="/admin/products/activity" class="nav-sublink">
                <i class="fas fa-person-hiking"></i>
                <span>Activités</span>
              </router-link>
            </li>
            <li class="nav-subitem">
              <router-link to="/admin/products/room" class="nav-sublink">
                <i class="fas fa-bed"></i>
                <span>Chambres</span>
              </router-link>
            </li>
          </ul>
        </li>

        <!-- Catégories -->
        <li class="nav-item">
          <router-link to="/admin/categories" class="nav-link">
            <i class="fas fa-tags"></i>
            <span>Catégories</span>
          </router-link>
        </li>

        <!-- Tags -->
        <li class="nav-item">
          <router-link to="/admin/tags" class="nav-link">
            <i class="fas fa-bookmark"></i>
            <span>Tags</span>
          </router-link>
        </li>

        <!-- Divider -->
        <li class="nav-divider">
          <hr>
          <span>Utilisateurs & Sécurité</span>
        </li>

        <!-- Utilisateurs -->
        <li class="nav-item">
          <router-link to="/admin/users" class="nav-link">
            <i class="fas fa-users"></i>
            <span>Utilisateurs</span>
            <span v-if="userStats.total" class="nav-badge">{{ userStats.total }}</span>
          </router-link>
        </li>

        <!-- Menu rôles et permissions avec sous-menu -->
        <li class="nav-item" :class="{ 'has-submenu': true, 'open': openSubmenu === 'security' }">
          <button @click="toggleSubmenu('security')" class="nav-link nav-toggle">
            <i class="fas fa-shield-alt"></i>
            <span>Rôles & Permissions</span>
            <i class="fas fa-chevron-down nav-arrow"></i>
          </button>
          <ul class="nav-submenu" v-show="openSubmenu === 'security'">
            <li class="nav-subitem">
              <router-link to="/admin/roles" class="nav-sublink">
                <i class="fas fa-user-shield"></i>
                <span>Rôles</span>
                <span v-if="securityStats.roles" class="nav-subbadge">{{ securityStats.roles }}</span>
              </router-link>
            </li>
            <li class="nav-subitem">
              <router-link to="/admin/permissions" class="nav-sublink">
                <i class="fas fa-key"></i>
                <span>Permissions</span>
                <span v-if="securityStats.permissions" class="nav-subbadge">{{ securityStats.permissions }}</span>
              </router-link>
            </li>
          </ul>
        </li>

        <!-- Divider -->
        <li class="nav-divider">
          <hr>
          <span>Business</span>
        </li>

        <!-- Commandes/Réservations -->
        <li class="nav-item">
          <router-link to="/admin/orders" class="nav-link">
            <i class="fas fa-shopping-cart"></i>
            <span>Commandes</span>
            <span v-if="businessStats.pending_orders" class="nav-badge nav-badge-warning">
              {{ businessStats.pending_orders }}
            </span>
          </router-link>
        </li>

        <!-- Clients -->
        <li class="nav-item">
          <router-link to="/admin/customers" class="nav-link">
            <i class="fas fa-user-friends"></i>
            <span>Clients</span>
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
          <span>Système</span>
        </li>

        <!-- Paramètres -->
        <li class="nav-item">
          <router-link to="/admin/settings" class="nav-link">
            <i class="fas fa-cog"></i>
            <span>Paramètres</span>
          </router-link>
        </li>

        <!-- Logs (pour super admin seulement) -->
        <li v-if="canViewLogs" class="nav-item">
          <router-link to="/admin/logs" class="nav-link">
            <i class="fas fa-file-alt"></i>
            <span>Logs système</span>
            <span v-if="systemStats.error_logs" class="nav-badge nav-badge-danger">
              {{ systemStats.error_logs }}
            </span>
          </router-link>
        </li>

        <!-- Divider -->
        <li class="nav-divider">
          <hr>
        </li>

        <!-- Retour au site -->
        <li class="nav-item">
          <router-link to="/home" class="nav-link nav-link-external">
            <i class="fas fa-external-link-alt"></i>
            <span>Voir le site</span>
          </router-link>
        </li>

        <!-- Mode maintenance (admin seulement) -->
        <li v-if="canManageMaintenance" class="nav-item">
          <button @click="toggleMaintenance" class="nav-link nav-button" :class="{ 'text-warning': isMaintenanceMode }">
            <i :class="isMaintenanceMode ? 'fas fa-tools' : 'fas fa-wrench'"></i>
            <span>{{ isMaintenanceMode ? 'Mode maintenance' : 'Maintenance' }}</span>
          </button>
        </li>
      </ul>
    </nav>

    <!-- User profile in sidebar -->
    <div class="sidebar-user" v-if="authStore.currentUser">
      <div class="user-avatar" :class="getUserStatusClass()">
        <i class="fas fa-user"></i>
      </div>
      <div class="user-info">
        <span class="user-name">{{ authStore.currentUser.name }}</span>
        <span class="user-role">{{ getUserRoleDisplay() }}</span>
      </div>
      <div class="user-actions">
        <button @click="showUserMenu = !showUserMenu" class="user-menu-toggle">
          <i class="fas fa-ellipsis-v"></i>
        </button>
        
        <!-- Menu utilisateur -->
        <div v-if="showUserMenu" class="user-menu" @click.stop>
          <router-link to="/admin/profile" class="user-menu-item">
            <i class="fas fa-user-edit"></i>
            Mon profil
          </router-link>
          
          <button @click="changePassword" class="user-menu-item">
            <i class="fas fa-key"></i>
            Changer le mot de passe
          </button>
          
          <div class="user-menu-divider"></div>
          
          <button @click="logout" class="user-menu-item text-danger">
            <i class="fas fa-sign-out-alt"></i>
            Se déconnecter
          </button>
        </div>
      </div>
    </div>
  </aside>
</template>

<script>
import { useAuthStore } from '@/shared/stores/auth'
import axios from 'axios'

export default {
  name: 'AdminSidebar',
  setup() {
    const authStore = useAuthStore()
    return { authStore }
  },
  data() {
    return {
      openSubmenu: null,
      showUserMenu: false,
      userStats: {
        total: 0,
        active: 0,
        pending: 0
      },
      securityStats: {
        roles: 0,
        permissions: 0
      },
      businessStats: {
        pending_orders: 0,
        new_customers: 0
      },
      systemStats: {
        error_logs: 0
      },
      isMaintenanceMode: false
    }
  },
  computed: {
    canViewLogs() {
      return this.authStore.isSuperAdmin
    },

    canManageMaintenance() {
      return this.authStore.hasPermission('maintenance') || this.authStore.isSuperAdmin
    }
  },
  created() {
    this.loadStats()
    this.checkMaintenanceMode()
    
    // Écouter les clics pour fermer le menu utilisateur
    document.addEventListener('click', this.closeUserMenu)
  },
  beforeUnmount() {
    document.removeEventListener('click', this.closeUserMenu)
  },
  methods: {
    toggleSubmenu(menuName) {
      this.openSubmenu = this.openSubmenu === menuName ? null : menuName
    },

    async loadStats() {
      try {
        // Charger les statistiques utilisateurs avec gestion d'erreur
        try {
          const userStatsResponse = await axios.get('/api/admin/users/stats')
          this.userStats = {
            total: userStatsResponse.data.total_users || 0,
            active: userStatsResponse.data.active_users || 0,
            pending: userStatsResponse.data.unverified_users || 0
          }
        } catch (error) {
          console.warn('Stats utilisateurs non disponibles:', error.response?.status)
          // Valeurs par défaut si l'endpoint n'existe pas encore
          this.userStats = { total: 0, active: 0, pending: 0 }
        }

        // Charger les statistiques rôles/permissions avec gestion d'erreur
        try {
          const [rolesResponse, permissionsResponse] = await Promise.all([
            axios.get('/api/admin/roles/stats').catch(() => ({ data: { total_roles: 0 } })),
            axios.get('/api/admin/permissions/stats').catch(() => ({ data: { total_permissions: 0 } }))
          ])
          
          this.securityStats = {
            roles: rolesResponse.data.total_roles || 0,
            permissions: permissionsResponse.data.total_permissions || 0
          }
        } catch (error) {
          console.warn('Stats sécurité non disponibles')
          this.securityStats = { roles: 0, permissions: 0 }
        }

      } catch (error) {
        console.warn('Erreur lors du chargement des statistiques:', error)
        // Ne pas afficher d'erreur pour ne pas encombrer l'interface
      }
    },

    async checkMaintenanceMode() {
      try {
        const response = await axios.get('/api/admin/settings/maintenance-status')
        this.isMaintenanceMode = response.data.maintenance_mode
      } catch (error) {
        console.warn('Mode maintenance non disponible')
        this.isMaintenanceMode = false
      }
    },

    async toggleMaintenance() {
      if (!confirm(`${this.isMaintenanceMode ? 'Désactiver' : 'Activer'} le mode maintenance ?`)) {
        return
      }

      try {
        await axios.post('/api/admin/settings/maintenance', {
          enabled: !this.isMaintenanceMode
        })
        
        this.isMaintenanceMode = !this.isMaintenanceMode
        
        const message = this.isMaintenanceMode 
          ? 'Mode maintenance activé' 
          : 'Mode maintenance désactivé'
          
        console.info(message)
        
      } catch (error) {
        console.error('Erreur lors du changement de mode maintenance:', error)
      }
    },

    getUserStatusClass() {
      const status = this.authStore.currentUser?.status
      return {
        'user-avatar-active': status === 'active',
        'user-avatar-warning': status === 'inactive',
        'user-avatar-danger': status === 'blocked'
      }
    },

    getUserRoleDisplay() {
      if (!this.authStore.currentUser?.role) {
        return 'Aucun rôle'
      }

      let display = this.authStore.currentUser.role.name
      
      // Ajouter les rôles additionnels s'il y en a
      if (this.authStore.userRoles && this.authStore.userRoles.length > 1) {
        const additionalRoles = this.authStore.userRoles.length - 1
        display += ` +${additionalRoles}`
      }

      return display
    },

    closeUserMenu() {
      this.showUserMenu = false
    },

    changePassword() {
      this.showUserMenu = false
      // Naviguer vers la page de changement de mot de passe
      this.$router.push('/admin/profile/change-password')
    },

    async logout() {
      this.showUserMenu = false
      
      try {
        await this.authStore.logout()
        this.$router.push('/login')
      } catch (error) {
        console.error('Erreur lors de la déconnexion:', error)
      }
    }
  }
}
</script>

<!-- <style lang="scss">
// Styles additionnels pour les nouvelles fonctionnalités

.nav-badge {
  background: #3b82f6;
  color: white;
  font-size: 0.75rem;
  padding: 0.125rem 0.375rem;
  border-radius: 0.75rem;
  font-weight: 600;
  margin-left: auto;

  &.nav-badge-warning {
    background: #f59e0b;
  }

  &.nav-badge-danger {
    background: #ef4444;
  }

  &.nav-badge-success {
    background: #10b981;
  }
}

.nav-subbadge {
  background: rgba(255, 255, 255, 0.2);
  color: inherit;
  font-size: 0.625rem;
  padding: 0.125rem 0.25rem;
  border-radius: 0.5rem;
  font-weight: 500;
  margin-left: auto;
}

.nav-button {
  background: none;
  border: none;
  width: 100%;
  text-align: left;
  cursor: pointer;
  
  &.text-warning {
    color: #f59e0b !important;
    
    i {
      color: #f59e0b !important;
    }
  }
}

.user-avatar {
  position: relative;
  
  &::after {
    content: '';
    position: absolute;
    bottom: 2px;
    right: 2px;
    width: 8px;
    height: 8px;
    border-radius: 50%;
    border: 2px solid white;
  }
  
  &.user-avatar-active::after {
    background: #10b981; // Vert pour actif
  }
  
  &.user-avatar-warning::after {
    background: #f59e0b; // Orange pour suspendu
  }
  
  &.user-avatar-danger::after {
    background: #ef4444; // Rouge pour bloqué
  }
}

.user-actions {
  position: relative;
}

.user-menu-toggle {
  background: none;
  border: none;
  color: #6b7280;
  cursor: pointer;
  padding: 0.25rem;
  border-radius: 4px;
  transition: all 0.2s ease;
  
  &:hover {
    background: rgba(255, 255, 255, 0.1);
    color: white;
  }
}

.user-menu {
  position: absolute;
  bottom: 100%;
  right: 0;
  background: white;
  border-radius: 8px;
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
  min-width: 180px;
  z-index: 1000;
  overflow: hidden;
  margin-bottom: 0.5rem;
}

.user-menu-item {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.75rem 1rem;
  color: #374151;
  text-decoration: none;
  background: none;
  border: none;
  width: 100%;
  text-align: left;
  cursor: pointer;
  font-size: 0.875rem;
  transition: background-color 0.2s ease;
  
  &:hover {
    background: #f3f4f6;
  }
  
  &.text-danger {
    color: #dc2626;
    
    &:hover {
      background: #fee2e2;
    }
  }
  
  i {
    width: 16px;
    text-align: center;
    color: #6b7280;
  }
  
  &.text-danger i {
    color: #dc2626;
  }
}

.user-menu-divider {
  height: 1px;
  background: #e5e7eb;
  margin: 0.5rem 0;
}

// Animation pour les sous-menus
.nav-submenu {
  animation: slideDown 0.2s ease-out;
}

@keyframes slideDown {
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
  .nav-badge {
    font-size: 0.625rem;
    padding: 0.1rem 0.3rem;
  }
  
  .user-menu {
    right: 0;
    left: auto;
    min-width: 160px;
  }
}
</style> -->