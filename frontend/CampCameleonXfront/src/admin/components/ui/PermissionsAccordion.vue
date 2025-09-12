<!-- path=> C:\Projects\CampCameleonX\frontend\CampCameleonXfront\src\admin\components\ui\PermissionsAccordion.vue -->
<template>
  <div class="permissions-accordion">
    <!-- Actions rapides (optionnelles) -->
    <div v-if="showActions" class="permission-actions">
      <button type="button" @click="expandAll" class="btn btn-outline btn-sm">
        <i class="fas fa-expand-arrows-alt"></i>
        Tout développer
      </button>
      <button type="button" @click="collapseAll" class="btn btn-outline btn-sm">
        <i class="fas fa-compress-arrows-alt"></i>
        Tout réduire
      </button>
      
      <!-- Actions supplémentaires selon le mode -->
      <template v-if="mode === 'editable'">
        <button type="button" @click="selectAll" class="btn btn-outline btn-sm">
          <i class="fas fa-check-square"></i>
          Tout sélectionner
        </button>
        <button type="button" @click="clearAll" class="btn btn-outline btn-sm">
          <i class="fas fa-square"></i>
          Tout désélectionner
        </button>
      </template>
      
      <div class="spacer"></div>
      
      <!-- Statistiques -->
      <div class="stats">
        <span v-if="mode === 'editable'">
          <i class="fas fa-check-circle"></i>
          {{ selectedCount }}/{{ totalCount }} sélectionnées
        </span>
        <span v-else>
          <i class="fas fa-layer-group"></i>
          {{ Object.keys(groupedPermissions).length }} catégorie(s)
        </span>
        <span>
          <i class="fas fa-shield-check"></i>
          {{ totalCount }} permission(s)
        </span>
      </div>
    </div>

    <!-- Accordéons par catégorie -->
    <div class="accordion-container">
      <div v-for="(permissions, category) in groupedPermissions" :key="category" 
           class="category-accordion">
        
        <!-- Header de la catégorie -->
        <div class="category-header" @click="toggleCategory(category)">
          <div class="category-left">
            <i :class="getCategoryIcon(category)" 
               :style="`color: ${getCategoryColor(category)};`"></i>
            <div class="category-info">
              <span class="category-title">{{ getCategoryName(category) }}</span>
              <span class="category-count">
                <template v-if="mode === 'editable'">
                  ({{ getCategorySelectedCount(category) }}/{{ permissions.length }})
                </template>
                <template v-else>
                  ({{ permissions.length }})
                </template>
              </span>
            </div>
          </div>
          
          <div class="category-right">
            <!-- Actions rapides par catégorie (mode éditable) -->
            <button v-if="mode === 'editable' && isCategoryOpen(category)"
                    type="button"
                    @click.stop="toggleCategorySelection(category)"
                    class="btn btn-outline btn-xs">
              {{ isCategoryFullySelected(category) ? 'Désélectionner' : 'Sélectionner' }}
            </button>
            
            <!-- Badge compteur -->
            <span class="permission-badge"
                  :style="`background: ${getCategoryColor(category)}20; color: ${getCategoryColor(category)};`">
              {{ permissions.length }}
            </span>
            
            <!-- Icône état -->
            <i :class="isCategoryOpen(category) ? 'fas fa-chevron-up' : 'fas fa-chevron-down'"
               class="toggle-icon"></i>
          </div>
        </div>

        <!-- Contenu accordéon -->
        <transition name="accordion">
          <div v-if="isCategoryOpen(category)" class="category-content">
            
            <!-- Mode lecture seule -->
            <template v-if="mode === 'readonly'">
              <div class="permissions-grid readonly">
                <div v-for="permission in permissions" :key="permission.id" 
                     class="permission-card readonly"
                     :style="`border-left-color: ${getCategoryColor(permission.category)};`">
                  <div class="permission-info">
                    <span class="permission-name">{{ permission.name }}</span>
                    <code class="permission-action">{{ permission.action }}</code>
                  </div>
                </div>
              </div>
            </template>

            <!-- Mode éditable -->
            <template v-else>
              <div class="permissions-grid editable">
                <label v-for="permission in permissions" :key="permission.id" 
                       class="permission-card editable">
                  <div class="permission-checkbox">
                    <input type="checkbox" 
                           :value="permission.id"
                           :checked="isSelected(permission.id)"
                           @change="togglePermission(permission.id)"
                           class="permission-input">
                    <div class="checkbox-custom"></div>
                  </div>
                  
                  <div class="permission-info">
                    <span class="permission-name"
                          :class="{ 'selected': isSelected(permission.id), 'changed': isChanged(permission.id) }">
                      {{ permission.name }}
                    </span>
                    <code class="permission-action">{{ permission.action }}</code>
                  </div>
                </label>
              </div>
            </template>
          </div>
        </transition>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'PermissionsAccordion',
  
  props: {
    permissions: {
      type: Array,
      default: () => []
    },
    selectedPermissions: {
      type: Array,
      default: () => []
    },
    originalPermissions: {
      type: Array,
      default: () => []
    },
    mode: {
      type: String,
      default: 'readonly', // 'readonly' | 'editable'
      validator: value => ['readonly', 'editable'].includes(value)
    },
    showActions: {
      type: Boolean,
      default: true
    },
    defaultOpenCategories: {
      type: Array,
      default: () => ['users'] // Catégories ouvertes par défaut
    }
  },

  emits: ['update:selectedPermissions', 'permission-changed'],

  data() {
    return {
      openCategories: new Set()
    }
  },

  computed: {
    groupedPermissions() {
      return this.permissions.reduce((groups, permission) => {
        const category = permission.category || 'general'
        if (!groups[category]) {
          groups[category] = []
        }
        groups[category].push(permission)
        return groups
      }, {})
    },

    totalCount() {
      return this.permissions.length
    },

    selectedCount() {
      return this.selectedPermissions.length
    }
  },

  methods: {
    // === Gestion accordéons ===
    toggleCategory(category) {
      if (this.openCategories.has(category)) {
        this.openCategories.delete(category)
      } else {
        this.openCategories.add(category)
      }
      this.openCategories = new Set(this.openCategories)
    },

    isCategoryOpen(category) {
      return this.openCategories.has(category)
    },

    expandAll() {
      this.openCategories = new Set(Object.keys(this.groupedPermissions))
    },

    collapseAll() {
      this.openCategories = new Set()
    },

    // === Gestion sélections (mode éditable) ===
    isSelected(permissionId) {
      return this.selectedPermissions.includes(String(permissionId))
    },

    isChanged(permissionId) {
      if (this.mode === 'readonly') return false
      const wasSelected = this.originalPermissions.includes(String(permissionId))
      const isSelected = this.selectedPermissions.includes(String(permissionId))
      return wasSelected !== isSelected
    },

    togglePermission(permissionId) {
      if (this.mode === 'readonly') return
      
      const selected = [...this.selectedPermissions]
      const index = selected.indexOf(String(permissionId))
      
      if (index > -1) {
        selected.splice(index, 1)
      } else {
        selected.push(String(permissionId))
      }
      
      this.$emit('update:selectedPermissions', selected)
      this.$emit('permission-changed', { permissionId, selected: index === -1 })
    },

    selectAll() {
      const allIds = this.permissions.map(p => String(p.id))
      this.$emit('update:selectedPermissions', allIds)
    },

    clearAll() {
      this.$emit('update:selectedPermissions', [])
    },

    // === Gestion par catégorie ===
    getCategorySelectedCount(category) {
      const categoryPermissions = this.groupedPermissions[category] || []
      return categoryPermissions.filter(p => this.isSelected(p.id)).length
    },

    isCategoryFullySelected(category) {
      const categoryPermissions = this.groupedPermissions[category] || []
      return categoryPermissions.every(p => this.isSelected(p.id))
    },

    toggleCategorySelection(category) {
      const categoryPermissions = this.groupedPermissions[category] || []
      const categoryIds = categoryPermissions.map(p => String(p.id))
      const allSelected = this.isCategoryFullySelected(category)
      
      let newSelected = [...this.selectedPermissions]
      
      if (allSelected) {
        // Désélectionner toute la catégorie
        newSelected = newSelected.filter(id => !categoryIds.includes(id))
      } else {
        // Sélectionner toute la catégorie
        categoryIds.forEach(id => {
          if (!newSelected.includes(id)) {
            newSelected.push(id)
          }
        })
      }
      
      this.$emit('update:selectedPermissions', newSelected)
    },

    // === Helpers d'affichage ===
    getCategoryIcon(category) {
      const icons = {
        'users': 'fas fa-users',
        'roles': 'fas fa-user-shield',
        'permissions': 'fas fa-key',
        'products': 'fas fa-box',
        'reservations': 'fas fa-calendar-check',
        'categories': 'fas fa-tags',
        'dashboard': 'fas fa-tachometer-alt',
        'settings': 'fas fa-cog',
        'general': 'fas fa-shield-alt'
      }
      return icons[category] || 'fas fa-shield-alt'
    },

    getCategoryColor(category) {
      const colors = {
        'users': '#3b82f6',        // blue
        'roles': '#8b5cf6',        // purple  
        'permissions': '#f59e0b',  // amber
        'products': '#10b981',     // emerald
        'reservations': '#ef4444', // red
        'categories': '#06b6d4',   // cyan
        'dashboard': '#6366f1',    // indigo
        'settings': '#6b7280',     // gray
        'general': '#6b7280'       // gray
      }
      return colors[category] || '#6b7280'
    },

    getCategoryName(category) {
      const names = {
        'users': 'Gestion des utilisateurs',
        'roles': 'Gestion des rôles',
        'permissions': 'Gestion des permissions',
        'products': 'Gestion des produits',
        'reservations': 'Gestion des réservations',
        'categories': 'Gestion des catégories',
        'dashboard': 'Tableau de bord',
        'settings': 'Paramètres système',
        'general': 'Permissions générales'
      }
      return names[category] || category.charAt(0).toUpperCase() + category.slice(1)
    }
  }
}
</script>

<style scoped>
/* === Container === */
.permissions-accordion {
  width: 100%;
}

.permission-actions {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  flex-wrap: wrap;
  margin-bottom: 1rem;
  padding: 0.75rem;
  background: #f8fafc;
  border-radius: 8px;
  font-size: 0.875rem;
}

.permission-actions .spacer {
  flex: 1 1 auto;
}

.permission-actions .stats {
  display: flex;
  align-items: center;
  gap: 1rem;
  color: #6b7280;
}

.permission-actions .stats span {
  display: flex;
  align-items: center;
  gap: 0.25rem;
}

/* === Accordéon === */
.accordion-container {
  margin-top: 0.75rem;
}

.category-accordion {
  margin-bottom: 0.75rem;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  overflow: hidden;
}

.category-header {
  padding: .6rem;
  cursor: pointer;
  user-select: none;
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.category-accordion:hover {
  transition: background-color 0.2s;
  background-color: #f3f4f6;
}

.category-left {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.category-info {
  display: flex;
  flex-direction: column;
  gap: 0.125rem;
}

.category-title {
  font-weight: 600;
  font-size: 1rem;
  color: #1f2937;
}

.category-count {
  color: #6b7280;
  font-size: 0.875rem;
}

.category-right {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.permission-badge {
  padding: 0.25rem 0.5rem;
  border-radius: 12px;
  font-size: 0.75rem;
  font-weight: 600;
}

.toggle-icon {
  color: #6b7280;
  transition: transform 0.2s;
}

/* === Contenu accordéon === */
.category-content {
  padding: 1rem;
  border-top: 1px solid #e5e7eb;
  background: white;
}

/* === Grilles de permissions === */
.permissions-grid {
  display: grid;
  gap: 0.75rem;
}

.permissions-grid.readonly {
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
}

.permissions-grid.editable {
  grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
}

/* === Cartes de permissions === */
.permission-card {
  padding: 0.5rem;
  border-radius: 6px;
  transition: all 0.2s ease;
}

.permission-card.readonly {
  background: #f8fafc;
  border-left: 3px solid #e5e7eb;
}

.permission-card.readonly:hover {
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.permission-card.editable {
  background: #fafafa;
  border: 1px solid #e5e7eb;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.permission-card.editable:hover {
  background: #f0f9ff;
  border-color: #3b82f6;
}

/* === Checkboxes === */
.permission-checkbox {
  position: relative;
  flex-shrink: 0;
}

.permission-input {
  position: absolute;
  opacity: 0;
  cursor: pointer;
}

.checkbox-custom {
  width: 18px;
  height: 18px;
  border: 2px solid #d1d5db;
  border-radius: 4px;
  background: white;
  transition: all 0.2s;
}

.permission-input:checked + .checkbox-custom {
  background: #3b82f6;
  border-color: #3b82f6;
}

.permission-input:checked + .checkbox-custom::after {
  content: '✓';
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  color: white;
  font-size: 12px;
  font-weight: bold;
}

/* === Info permissions === */
.permission-info {
  display: flex;
  flex-direction: column;
  padding-top: 0.25rem;
  min-width: 0;
}

.permission-name {
  font-weight: 500;
  font-size: 0.875rem;
  color: #1f2937;
  transition: color 0.2s;
}

.permission-name.selected {
  color: #1d4ed8;
  font-weight: 600;
}

.permission-name.changed {
  background: #fef3c7;
  padding: 0.125rem 0.25rem;
  border-radius: 4px;
}

.permission-action {
  font-size: 0.75rem;
  background: #e5e7eb;
  color: #374151;
  padding: 0.125rem 0.375rem;
  border-radius: 4px;
  font-family: monospace;
  width: fit-content;
}

/* === Animations === */
.accordion-enter-active, .accordion-leave-active {
  transition: all 0.3s ease;
  overflow: hidden;
}

.accordion-enter-from, .accordion-leave-to {
  max-height: 0;
  opacity: 0;
}

.accordion-enter-to, .accordion-leave-from {
  max-height: 1000px;
  opacity: 1;
}

/* === Responsive === */
@media (max-width: 768px) {
  .permission-actions {
    flex-direction: column;
    align-items: stretch;
  }
  
  .permission-actions .stats {
    order: -1;
    justify-content: center;
    margin-bottom: 0.5rem;
  }
  
  .permissions-grid {
    grid-template-columns: 1fr;
  }
}
</style>