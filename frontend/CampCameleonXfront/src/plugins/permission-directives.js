import { nextTick } from 'vue'
import { useAuthStore } from '@/shared/stores/auth'

/**
 * Directive v-permission
 * Affiche/masque un élément selon les permissions de l'utilisateur
 * 
 * Usage:
 * v-permission="'create-users'"
 * v-permission="['create-users', 'update-users']" (OR)
 * v-permission.all="['create-users', 'update-users']" (AND)
 */
const permissionDirective = {
  mounted(el, binding, vnode) {
    checkPermission(el, binding, vnode)
  },
  
  updated(el, binding, vnode) {
    checkPermission(el, binding, vnode)
  }
}

/**
 * Directive v-role
 * Affiche/masque un élément selon les rôles de l'utilisateur
 * 
 * Usage:
 * v-role="'admin'"
 * v-role="['admin', 'manager']" (OR)
 * v-role.all="['admin', 'manager']" (AND)
 */
const roleDirective = {
  mounted(el, binding, vnode) {
    checkRole(el, binding, vnode)
  },
  
  updated(el, binding, vnode) {
    checkRole(el, binding, vnode)
  }
}

/**
 * Directive v-admin-only
 * Affiche l'élément seulement pour les administrateurs
 * 
 * Usage:
 * v-admin-only
 */
const adminOnlyDirective = {
  mounted(el, binding, vnode) {
    checkAdminOnly(el, vnode)
  },
  
  updated(el, binding, vnode) {
    checkAdminOnly(el, vnode)
  }
}

/**
 * Directive v-super-admin-only
 * Affiche l'élément seulement pour les super administrateurs
 * 
 * Usage:
 * v-super-admin-only
 */
const superAdminOnlyDirective = {
  mounted(el, binding, vnode) {
    checkSuperAdminOnly(el, vnode)
  },
  
  updated(el, binding, vnode) {
    checkSuperAdminOnly(el, vnode)
  }
}

/**
 * Directive v-auth-only
 * Affiche l'élément seulement pour les utilisateurs connectés
 * 
 * Usage:
 * v-auth-only
 */
const authOnlyDirective = {
  mounted(el, binding, vnode) {
    checkAuthOnly(el, vnode)
  },
  
  updated(el, binding, vnode) {
    checkAuthOnly(el, vnode)
  }
}

/**
 * Directive v-guest-only
 * Affiche l'élément seulement pour les utilisateurs non connectés
 * 
 * Usage:
 * v-guest-only
 */
const guestOnlyDirective = {
  mounted(el, binding, vnode) {
    checkGuestOnly(el, vnode)
  },
  
  updated(el, binding, vnode) {
    checkGuestOnly(el, vnode)
  }
}

// ===========================
// FONCTIONS DE VÉRIFICATION
// ===========================

function checkPermission(el, binding, vnode) {
  const authStore = useAuthStore()
  
  const permissions = normalizeValue(binding.value)
  const isAllRequired = binding.modifiers.all
  const hasPermission = checkUserPermissions(authStore, permissions, isAllRequired)
  
  toggleElement(el, hasPermission)
}

function checkRole(el, binding, vnode) {
  const authStore = useAuthStore()

  const roles = normalizeValue(binding.value)
  const isAllRequired = binding.modifiers.all
  const hasRole = checkUserRoles(authStore, roles, isAllRequired)
  
  toggleElement(el, hasRole)
}

function checkAdminOnly(el, vnode) {
  const authStore = useAuthStore()
  toggleElement(el, authStore.isAdmin)
}

function checkSuperAdminOnly(el, vnode) {
  const authStore = useAuthStore()
  toggleElement(el, authStore.isSuperAdmin)
}

function checkAuthOnly(el, vnode) {
  const authStore = useAuthStore()
  toggleElement(el, authStore.isAuthenticated)
}

function checkGuestOnly(el, vnode) {
  const authStore = useAuthStore()
  toggleElement(el, !authStore.isAuthenticated)
}

// ===========================
// FONCTIONS UTILITAIRES
// ===========================

function normalizeValue(value) {
  if (!value) return []
  return Array.isArray(value) ? value : [value]
}

function checkUserPermissions(authStore, permissions, isAllRequired = false) {
  if (!permissions.length) return true
  
  if (isAllRequired) {
    // Toutes les permissions sont requises (AND)
    return permissions.every(permission => 
      authStore.hasPermission(permission)
    )
  } else {
    // Au moins une permission est requise (OR)
    return permissions.some(permission => 
      authStore.hasPermission(permission)
    )
  }
}

function checkUserRoles(authStore, roles, isAllRequired = false) {
  if (!roles.length) return true
  
  if (isAllRequired) {
    // Tous les rôles sont requis (AND)
    return roles.every(role => 
      authStore.hasRole(role)
    )
  } else {
    // Au moins un rôle est requis (OR)
    return roles.some(role => 
      authStore.hasRole(role)
    )
  }
}

function toggleElement(el, show) {
  if (show) {
    // Afficher l'élément
    el.style.display = ''
    el.removeAttribute('disabled')
    el.classList.remove('permission-hidden')
  } else {
    // Masquer l'élément
    el.style.display = 'none'
    el.setAttribute('disabled', 'disabled')
    el.classList.add('permission-hidden')
  }
}

// ===========================
// COMPOSABLES UTILITAIRES
// ===========================

/**
 * Composable pour vérifier les permissions dans les composants
 */
export function usePermissions() {
  const authStore = useAuthStore()
  
  const hasPermission = (permission) => {
    return authStore.hasPermission(permission)
  }
  
  const hasAnyPermission = (permissions) => {
    return permissions.some(permission => hasPermission(permission))
  }
  
  const hasAllPermissions = (permissions) => {
    return permissions.every(permission => hasPermission(permission))
  }
  
  const hasRole = (role) => {
    return authStore.hasRole(role)
  }
  
  const hasAnyRole = (roles) => {
    return roles.some(role => hasRole(role))
  }
  
  const isAdmin = () => {
    return authStore.isAdmin
  }
  
  const isSuperAdmin = () => {
    return authStore.isSuperAdmin
  }
  
  const canAccessAdmin = () => {
    return authStore.canAccessAdmin
  }
  
  return {
    hasPermission,
    hasAnyPermission,
    hasAllPermissions,
    hasRole,
    hasAnyRole,
    isAdmin,
    isSuperAdmin,
    canAccessAdmin
  }
}

/**
 * Mixin pour les composants Options API
 */
export const permissionMixin = {
  computed: {
    $permissions() {
      const authStore = useAuthStore()
      return authStore.userPermissions
    },
    
    $roles() {
      const authStore = useAuthStore()
      return authStore.userRoles
    },
    
    $isAdmin() {
      const authStore = useAuthStore()
      return authStore.isAdmin
    },
    
    $isSuperAdmin() {
      const authStore = useAuthStore()
      return authStore.isSuperAdmin
    },
    
    $canAccessAdmin() {
      const authStore = useAuthStore()
      return authStore.canAccessAdmin
    }
  },
  
  methods: {
    $hasPermission(permission) {
      const authStore = useAuthStore()
      return authStore.hasPermission(permission)
    },
    
    $hasAnyPermission(permissions) {
      return permissions.some(permission => this.$hasPermission(permission))
    },
    
    $hasAllPermissions(permissions) {
      return permissions.every(permission => this.$hasPermission(permission))
    },
    
    $hasRole(role) {
      const authStore = useAuthStore()
      return authStore.hasRole(role)
    },
    
    $hasAnyRole(roles) {
      return roles.some(role => this.$hasRole(role))
    },
    
    $canManage(resource) {
      return this.$hasAnyPermission([
        `manage-${resource}`,
        `create-${resource}`,
        `update-${resource}`,
        `delete-${resource}`
      ]) || this.$isAdmin
    }
  }
}

// ===========================
// PLUGIN D'INSTALLATION
// ===========================

/**
 * Plugin for Vue.js that adds permission-based directives and global methods
 * @module permission-directives
 * 
 * @exports {Object} default - Vue plugin object
 * @property {Function} install - Plugin installation function
 * 
 * @param {Object} app - Vue application instance
 * @param {Object} [options={}] - Plugin options (currently unused)
 * 
 * Registers the following custom directives:
 * - v-permission: Check for specific permissions
 * - v-role: Check for specific roles
 * - v-admin-only: Restrict access to admins
 * - v-super-admin-only: Restrict access to super admins
 * - v-auth-only: Restrict access to authenticated users
 * - v-guest-only: Restrict access to guests
 * 
 * Adds the following global methods:
 * - $hasPermission(permission): Check if user has specific permission
 * - $hasRole(role): Check if user has specific role
 * - $isAdmin(): Check if user is admin
 * - $isSuperAdmin(): Check if user is super admin
 * 
 * Also adds CSS styles for hidden elements with .permission-hidden class
 */
export default {
  install(app, options = {}) {
    // Enregistrer les directives
    app.directive('permission', permissionDirective)
    app.directive('role', roleDirective)
    app.directive('admin-only', adminOnlyDirective)
    app.directive('super-admin-only', superAdminOnlyDirective)
    app.directive('auth-only', authOnlyDirective)
    app.directive('guest-only', guestOnlyDirective)
    
    // Ajouter les méthodes globales (compatibles avec Pinia)
    app.config.globalProperties.$hasPermission = function(permission) {
      const authStore = useAuthStore()
      return authStore.hasPermission(permission)
    }
    
    app.config.globalProperties.$hasRole = function(role) {
      const authStore = useAuthStore()
      return authStore.hasRole(role)
    }
    
    app.config.globalProperties.$isAdmin = function() {
      const authStore = useAuthStore()
      return authStore.isAdmin
    }
    
    app.config.globalProperties.$isSuperAdmin = function() {
      const authStore = useAuthStore()
      return authStore.isSuperAdmin
    }
    
    // CSS par défaut pour les éléments masqués
    const style = document.createElement('style')
    style.textContent = `
      .permission-hidden {
        display: none !important;
      }
      
      [disabled].permission-hidden {
        opacity: 0.5;
        pointer-events: none;
      }
    `
    document.head.appendChild(style)
    
    console.info('🔐 Directives de permissions installées (Pinia)')
  }
}

// ===========================
// EXEMPLES D'USAGE
// ===========================

/*
<!-- Dans vos templates Vue -->

<!-- Afficher seulement si l'utilisateur peut créer des utilisateurs -->
<button v-permission="'create-users'">
  Créer un utilisateur
</button>

<!-- Afficher si l'utilisateur a au moins une des permissions (OR) -->
<div v-permission="['create-users', 'update-users']">
  Gestion des utilisateurs
</div>

<!-- Afficher seulement si l'utilisateur a TOUTES les permissions (AND) -->
<div v-permission.all="['create-users', 'manage-roles']">
  Administration complète
</div>

<!-- Afficher seulement pour les admins -->
<section v-admin-only>
  Panneau d'administration
</section>

<!-- Afficher seulement pour les super admins -->
<button v-super-admin-only @click="deleteEverything">
  Tout supprimer
</button>

<!-- Afficher selon les rôles -->
<div v-role="'manager'">
  Interface gestionnaire
</div>

<div v-role="['admin', 'manager']">
  Interface pour admin ou manager
</div>

<!-- Utilisateur connecté seulement -->
<nav v-auth-only>
  <router-link to="/profile">Mon profil</router-link>
</nav>

<!-- Utilisateur non connecté seulement -->
<div v-guest-only>
  <router-link to="/login">Se connecter</router-link>
</div>

<!-- Dans vos scripts (Composition API) -->
<script setup>
import { usePermissions } from '@/plugins/permission-directives'

const { hasPermission, hasRole, isAdmin } = usePermissions()

const canEdit = hasPermission('update-users')
const isManager = hasRole('manager') 
const showAdminPanel = isAdmin()
</script>

<!-- Dans vos scripts (Options API) -->
<script>
import { permissionMixin } from '@/plugins/permission-directives'

export default {
  mixins: [permissionMixin],
  
  computed: {
    canDeleteUsers() {
      return this.$hasPermission('delete-users') || this.$isAdmin
    },
    
    showAdvancedOptions() {
      return this.$hasAnyRole(['admin', 'super-admin'])
    }
  }
}
</script>
*/