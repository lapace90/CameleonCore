// plugins/permission-directives.js - Directives Vue pour la gestion des permissions

import { nextTick } from 'vue'

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
  const store = getStore(vnode)
  if (!store) return

  const permissions = normalizeValue(binding.value)
  const isAllRequired = binding.modifiers.all
  const hasPermission = checkUserPermissions(store, permissions, isAllRequired)
  
  toggleElement(el, hasPermission)
}

function checkRole(el, binding, vnode) {
  const store = getStore(vnode)
  if (!store) return

  const roles = normalizeValue(binding.value)
  const isAllRequired = binding.modifiers.all
  const hasRole = checkUserRoles(store, roles, isAllRequired)
  
  toggleElement(el, hasRole)
}

function checkAdminOnly(el, vnode) {
  const store = getStore(vnode)
  if (!store) return

  const isAdmin = store.getters['auth/isAdmin']
  toggleElement(el, isAdmin)
}

function checkSuperAdminOnly(el, vnode) {
  const store = getStore(vnode)
  if (!store) return

  const isSuperAdmin = store.getters['auth/isSuperAdmin']
  toggleElement(el, isSuperAdmin)
}

function checkAuthOnly(el, vnode) {
  const store = getStore(vnode)
  if (!store) return

  const isAuthenticated = store.getters['auth/isAuthenticated']
  toggleElement(el, isAuthenticated)
}

function checkGuestOnly(el, vnode) {
  const store = getStore(vnode)
  if (!store) return

  const isAuthenticated = store.getters['auth/isAuthenticated']
  toggleElement(el, !isAuthenticated)
}

// ===========================
// FONCTIONS UTILITAIRES
// ===========================

function getStore(vnode) {
  // Pour Vue 3 avec Composition API
  const instance = vnode.ctx || vnode.component?.ctx
  return instance?.$store || instance?.appContext?.app?.config?.globalProperties?.$store
}

function normalizeValue(value) {
  if (!value) return []
  return Array.isArray(value) ? value : [value]
}

function checkUserPermissions(store, permissions, isAllRequired = false) {
  if (!permissions.length) return true
  
  if (isAllRequired) {
    // Toutes les permissions sont requises (AND)
    return permissions.every(permission => 
      store.getters['auth/hasPermission'](permission)
    )
  } else {
    // Au moins une permission est requise (OR)
    return permissions.some(permission => 
      store.getters['auth/hasPermission'](permission)
    )
  }
}

function checkUserRoles(store, roles, isAllRequired = false) {
  if (!roles.length) return true
  
  if (isAllRequired) {
    // Tous les rôles sont requis (AND)
    return roles.every(role => 
      store.getters['auth/hasRole'](role)
    )
  } else {
    // Au moins un rôle est requis (OR)
    return roles.some(role => 
      store.getters['auth/hasRole'](role)
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
  const { store } = getCurrentInstance().appContext.app.config.globalProperties
  
  const hasPermission = (permission) => {
    return store.getters['auth/hasPermission'](permission)
  }
  
  const hasAnyPermission = (permissions) => {
    return permissions.some(permission => hasPermission(permission))
  }
  
  const hasAllPermissions = (permissions) => {
    return permissions.every(permission => hasPermission(permission))
  }
  
  const hasRole = (role) => {
    return store.getters['auth/hasRole'](role)
  }
  
  const hasAnyRole = (roles) => {
    return store.getters['auth/hasAnyRole'](roles)
  }
  
  const isAdmin = () => {
    return store.getters['auth/isAdmin']
  }
  
  const isSuperAdmin = () => {
    return store.getters['auth/isSuperAdmin']
  }
  
  const canAccessAdmin = () => {
    return store.getters['auth/canAccessAdmin']
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
      return this.$store.getters['auth/userPermissions']
    },
    
    $roles() {
      return this.$store.getters['auth/userRoles']
    },
    
    $isAdmin() {
      return this.$store.getters['auth/isAdmin']
    },
    
    $isSuperAdmin() {
      return this.$store.getters['auth/isSuperAdmin']
    },
    
    $canAccessAdmin() {
      return this.$store.getters['auth/canAccessAdmin']
    }
  },
  
  methods: {
    $hasPermission(permission) {
      return this.$store.getters['auth/hasPermission'](permission)
    },
    
    $hasAnyPermission(permissions) {
      return permissions.some(permission => this.$hasPermission(permission))
    },
    
    $hasAllPermissions(permissions) {
      return permissions.every(permission => this.$hasPermission(permission))
    },
    
    $hasRole(role) {
      return this.$store.getters['auth/hasRole'](role)
    },
    
    $hasAnyRole(roles) {
      return this.$store.getters['auth/hasAnyRole'](roles)
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

export default {
  install(app, options = {}) {
    // Enregistrer les directives
    app.directive('permission', permissionDirective)
    app.directive('role', roleDirective)
    app.directive('admin-only', adminOnlyDirective)
    app.directive('super-admin-only', superAdminOnlyDirective)
    app.directive('auth-only', authOnlyDirective)
    app.directive('guest-only', guestOnlyDirective)
    
    // Ajouter les méthodes globales
    app.config.globalProperties.$hasPermission = function(permission) {
      return this.$store.getters['auth/hasPermission'](permission)
    }
    
    app.config.globalProperties.$hasRole = function(role) {
      return this.$store.getters['auth/hasRole'](role)
    }
    
    app.config.globalProperties.$isAdmin = function() {
      return this.$store.getters['auth/isAdmin']
    }
    
    app.config.globalProperties.$isSuperAdmin = function() {
      return this.$store.getters['auth/isSuperAdmin']
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
    
    console.info('🔐 Directives de permissions installées')
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