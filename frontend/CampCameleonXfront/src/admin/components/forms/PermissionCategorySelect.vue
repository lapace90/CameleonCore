<!-- frontend/CampCameleonXfront/src/admin/components/PermissionCategorySelect.vue -->
<template>
  <div class="permission-category-select">
    <label class="form-label">
      <AppIcon name="tags" />
      Catégorie de permission
    </label>

    <!-- Sélection par badges -->
    <div class="categories-grid">
      <button 
        v-for="category in availableCategories" 
        :key="category.key"
        type="button" 
        class="cat-badge"
        :class="{ 
          'cat-badge-active': modelValue === category.key,
          [`category-${category.key}`]: true 
        }"
        @click="selectCategory(category.key)"
        :title="category.description"
      >
        <AppIcon :name="category.icon" :style="{ color: category.color }" />
        <span class="cat-name">{{ category.name }}</span>
        <span v-if="category.count" class="category-count">{{ category.count }}</span>
      </button>
    </div>

    <!-- Catégorie sélectionnée -->
    <div v-if="selectedCategory" class="selected-category">
      <div class="selected-info">
        <AppIcon :name="selectedCategory.icon" :style="{ color: selectedCategory.color }" />
        <div class="selected-details">
          <span class="selected-name">{{ selectedCategory.name }}</span>
          <small class="selected-description">{{ selectedCategory.description }}</small>
        </div>
      </div>
      <button 
        type="button" 
        class="btn-clear" 
        @click="clearSelection"
        title="Aucune catégorie"
      >
        <AppIcon name="x" />
      </button>
    </div>

    <!-- Aperçu automatique si pas de sélection manuelle -->
    <div v-if="!modelValue && autoDetectedCategory" class="auto-preview">
      <AppIcon name="lightbulb" />
      <span class="text-muted">
        Sera automatiquement classée dans : 
        <strong>{{ autoDetectedCategory.name }}</strong>
      </span>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  modelValue: {
    type: String,
    default: ''
  },
  actionPreview: {
    type: String,
    default: ''
  }
})

const emit = defineEmits(['update:modelValue'])

// Catégories disponibles (même logique que le serveur)
const availableCategories = [
  {
    key: 'system',
    name: 'Administration Système',
    description: 'Permissions d\'administration système et maintenance',
    icon: 'settings-2',
    color: '#f5365c',
    count: null
  },
  {
    key: 'users',
    name: 'Gestion Utilisateurs',
    description: 'Gestion des utilisateurs, rôles et permissions',
    icon: 'users',
    color: '#5e72e4',
    count: null
  },
  {
    key: 'accommodations',
    name: 'Hébergements',
    description: 'Gestion des hébergements et disponibilités',
    icon: 'home',
    color: '#2dce89',
    count: null
  },
  {
    key: 'activities',
    name: 'Activités',
    description: 'Gestion des activités proposées',
    icon: 'mountain',
    color: '#fb6340',
    count: null
  },
  {
    key: 'bookings',
    name: 'Réservations',
    description: 'Système de réservations et planning',
    icon: 'calendar-check',
    color: '#11cdef',
    count: null
  },
  {
    key: 'reception',
    name: 'Réception',
    description: 'Accueil et gestion quotidienne',
    icon: 'bell-ring',
    color: '#8965e0',
    count: null
  },
  {
    key: 'customers',
    name: 'Clients',
    description: 'Gestion de la clientèle',
    icon: 'users',
    color: '#ffd600',
    count: null
  },
  {
    key: 'restaurant',
    name: 'Restaurant',
    description: 'Gestion du restaurant et des commandes',
    icon: 'utensils',
    color: '#2dce89',
    count: null
  },
  {
    key: 'finance',
    name: 'Finance',
    description: 'Gestion financière et comptabilité',
    icon: 'coins',
    color: '#f56565',
    count: null
  },
  {
    key: 'analytics',
    name: 'Analyses',
    description: 'Tableaux de bord et rapports',
    icon: 'trending-up',
    color: '#2196f3',
    count: null
  },
  {
    key: 'communication',
    name: 'Communication',
    description: 'Messagerie et notifications',
    icon: 'message-circle',
    color: '#5603ad',
    count: null
  },
  {
    key: 'other',
    name: 'Autres',
    description: 'Permissions diverses',
    icon: 'ellipsis',
    color: '#8898aa',
    count: null
  }
]

// Catégorie sélectionnée
const selectedCategory = computed(() => {
  return availableCategories.find(cat => cat.key === props.modelValue)
})

// Auto-détection basée sur l'action (même logique que le serveur)
const autoDetectedCategory = computed(() => {
  if (!props.actionPreview) return null
  
  const action = props.actionPreview.toLowerCase()
  
  const categoryActions = {
    'system': ['system-admin', 'admin-access', 'maintenance-mode', 'clear-cache', 'view-logs'],
    'users': ['users-read', 'users-create', 'users-update', 'users-delete', 'roles-manage'],
    'accommodations': ['accommodations-read', 'accommodations-manage', 'rooms-status'],
    'activities': ['activities-read', 'activities-manage'],
    'bookings': ['bookings-read-all', 'bookings-create', 'bookings-update', 'bookings-cancel', 'bookings-confirm', 'planning-manage'],
    'reception': ['checkin', 'checkout', 'arrivals-today', 'departures-today', 'keys-manage'],
    'customers': ['customers-read', 'customers-create', 'customers-update', 'customers-history'],
    'restaurant': ['menus-read', 'menus-manage', 'dishes-manage', 'orders-take', 'orders-manage'],
    'finance': ['payments-read', 'payments-collect', 'invoicing-manage', 'finance-stats'],
    'analytics': ['dashboard-view', 'occupancy-stats', 'revenue-reports', 'data-export'],
    'communication': ['messages-customers', 'notifications-team']
  }

  for (const [categoryKey, actions] of Object.entries(categoryActions)) {
    if (actions.includes(action)) {
      return availableCategories.find(cat => cat.key === categoryKey)
    }
  }

  return availableCategories.find(cat => cat.key === 'other')
})

const selectCategory = (categoryKey) => {
  emit('update:modelValue', categoryKey)
}

const clearSelection = () => {
  emit('update:modelValue', '')
}
</script>

<style scoped>
.permission-category-select {
  margin-bottom: 1.5rem;
}

.form-label {
  display: block;
  margin-bottom: 0.75rem;
  font-weight: 500;
  color: #334155;
}

.categories-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 0.75rem;
  margin-bottom: 1rem;
}

.cat-badge {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.75rem 1rem;
  border: 2px solid #e2e8f0;
  border-radius: 8px;
  background: white;
  cursor: pointer;
  transition: all 0.2s ease;
  font-size: 0.875rem;
}

.cat-badge:hover {
  border-color: #cbd5e1;
  box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

.cat-badge-active {
  border-color: #3b82f6 !important;
  background: #eff6ff;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.cat-name {
  flex: 1;
  text-align: left;
}

.category-count {
  font-size: 0.75rem;
  background: #f1f5f9;
  color: #64748b;
  padding: 0.125rem 0.375rem;
  border-radius: 9999px;
}

.selected-category {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0.75rem;
  background: #f8fafc;
  border-radius: 6px;
  border: 1px solid #e2e8f0;
}

.selected-info {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.selected-details {
  display: flex;
  flex-direction: column;
}

.selected-name {
  font-weight: 500;
  color: #1e293b;
}

.selected-description {
  color: #64748b;
  font-size: 0.875rem;
}

.btn-clear {
  background: none;
  border: none;
  color: #64748b;
  cursor: pointer;
  padding: 0.25rem;
  border-radius: 4px;
  transition: all 0.2s ease;
}

.btn-clear:hover {
  background: #e2e8f0;
  color: #374151;
}

.auto-preview {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.75rem;
  background: #f0f9ff;
  border-radius: 6px;
  border: 1px solid #bae6fd;
  font-size: 0.875rem;
}
</style>