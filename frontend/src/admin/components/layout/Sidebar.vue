<template>
  <aside class="admin-sidebar">
    <!-- Brand -->
    <router-link to="/admin/dashboard" class="nav-link">
      <div class="sidebar-brand">
        <h2>🦎 {{ instanceName }}</h2>
        <span class="brand-subtitle">Administration</span>
      </div>
    </router-link>

    <!-- Navigation -->
    <nav class="sidebar-nav">
      <ul class="nav-list">
        <!-- Sections dynamiques -->
        <template v-for="section in menuSections" :key="section.label">
          <!-- Divider -->
          <li class="nav-divider">
            <hr>
            <span>{{ section.label }}</span>
          </li>

          <!-- Items de la section -->
          <template v-for="item in section.items" :key="item.route">
            <!-- Item avec sous-menu -->
            <li v-if="item.children" class="nav-item">
              <a @click="toggleSubmenu(item.key)" class="nav-link" style="cursor: pointer;">
                <AppIcon :name="item.icon" />
                <span>{{ item.label }}</span>
                <AppIcon
                  name="chevron-down"
                  :class="{ rotated: openSubmenu === item.key }"
                  style="margin-left: auto; font-size: 0.8rem;"
                />
              </a>
              <ul v-show="openSubmenu === item.key" class="submenu">
                <li v-for="child in item.children" :key="child.route">
                  <router-link :to="child.route" class="nav-link submenu-link">
                    {{ child.label }}
                  </router-link>
                </li>
              </ul>
            </li>

            <!-- Item simple -->
            <li v-else class="nav-item">
              <router-link :to="item.route" class="nav-link">
                <AppIcon :name="item.icon" />
                <span>{{ item.label }}</span>
              </router-link>
            </li>
          </template>
        </template>

        <!-- Toujours visible -->
        <li class="nav-divider">
          <hr>
          <span>Système</span>
        </li>
        <li class="nav-item">
          <router-link to="/admin/settings" class="nav-link">
            <AppIcon name="settings" />
            <span>Paramètres</span>
          </router-link>
        </li>
        <li class="nav-item">
          <router-link to="/home" class="nav-link nav-link-external">
            <AppIcon name="external-link" />
            <span>Voir le site</span>
          </router-link>
        </li>
      </ul>
    </nav>

    <!-- User profile -->
    <div class="sidebar-user">
      <div class="user-avatar"></div>
      <div class="user-info">
        <span class="user-name"></span>
        <span class="user-role"></span>
      </div>
    </div>
  </aside>
</template>

<script>
import { useInstanceStore } from '@/shared/stores/instance'
import { PRODUCT_CONFIGS } from '@/shared/configs/productConfigs'

export default {
  name: 'AdminSidebar',

  data() {
    return {
      openSubmenu: null
    }
  },

  computed: {
    instance() {
      return useInstanceStore()
    },

    instanceName() {
      return this.instance.name || 'CameleonCore'
    },

    menuSections() {
      const sections = []

      // === GESTION ===
      const gestion = []

      if (this.instance.hasModule('calendar')) {
        gestion.push({ label: 'Agenda', icon: 'calendar', route: '/admin/agenda' })
      }
      if (this.instance.hasModule('booking')) {
        gestion.push({ label: 'Réservations', icon: 'calendar-check', route: '/admin/reservations' })
      }
      if (this.instance.hasModule('invoicing')) {
        gestion.push({ label: 'Facturation', icon: 'receipt', route: '/admin/invoices' })
      }

      if (gestion.length) {
        sections.push({ label: 'Gestion', items: gestion })
      }

      // === SERVICES (productables) ===
      const services = this.buildProductableItems()
      if (services.length) {
        sections.push({ label: 'Services', items: services })
      }

      // Catégories (toujours si au moins un productable)
      if (this.instance.productables.length) {
        const lastSection = sections[sections.length - 1]
        lastSection.items.push({ label: 'Catégories', icon: 'tags', route: '/admin/categories' })
      }

      // === ADMINISTRATION ===
      const admin = []

      if (this.instance.hasModule('rbac')) {
        admin.push({ label: 'Utilisateurs', icon: 'users', route: '/admin/users' })
      }
      if (this.instance.hasModule('staff')) {
        admin.push({ label: 'Planning Staff', icon: 'calendar-clock', route: '/admin/staff' })
      }
      if (this.instance.hasModule('reviews')) {
        admin.push({ label: 'Avis Clients', icon: 'star', route: '/admin/reviews' })
      }
      if (this.instance.hasModule('analytics')) {
        admin.push({ label: 'Analytics', icon: 'trending-up', route: '/admin/analytics' })
      }

      if (admin.length) {
        sections.push({ label: 'Administration', items: admin })
      }

      return sections
    }
  },

  methods: {
    toggleSubmenu(menuName) {
      this.openSubmenu = this.openSubmenu === menuName ? null : menuName
    },

    buildProductableItems() {
      const active = this.instance.productables
      if (!active.length) return []

      // Grouper restauration (menu, dish, ingredient) en sous-menu
      const restauration = ['menu', 'dish', 'ingredient'].filter(t => active.includes(t))
      const others = active.filter(t => !['menu', 'dish', 'ingredient'].includes(t))

      const items = []

      if (restauration.length) {
        items.push({
          label: 'Restauration',
          icon: 'utensils',
          key: 'restauration',
          children: restauration.map(type => ({
            label: PRODUCT_CONFIGS[type]?.label ?? type,
            route: `/admin/products/${type}`
          }))
        })
      }

      // Les autres productables (room, activity) sont des liens directs
      others.forEach(type => {
        const config = PRODUCT_CONFIGS[type]
        if (config) {
          items.push({
            label: config.label,
            icon: config.icon,
            route: `/admin/products/${type}`
          })
        }
      })

      return items
    }
  }
}
</script>

<style scoped>
.submenu {
  list-style: none;
  padding-left: 2rem;
  margin: 0;
}

.submenu-link {
  padding-left: 2rem;
  font-size: 0.9rem;
}

.rotated {
  transform: rotate(180deg);
}
</style>
