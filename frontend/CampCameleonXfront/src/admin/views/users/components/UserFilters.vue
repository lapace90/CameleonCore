<template>
  <div class="users-table-card">
    <div class="table-header">
      <div class="search-filter">
        <div class="search-box">
          <i class="fas fa-search search-icon"></i>
          <input 
            :value="searchQuery"
            @input="$emit('update:searchQuery', $event.target.value)"
            type="text" 
            placeholder="Rechercher un utilisateur..." 
            class="search-input"
          >
        </div>
        
        <div class="filter-options">
          <select 
            :value="roleFilter" 
            @change="$emit('update:roleFilter', $event.target.value)"
            class="filter-select"
          >
            <option value="">Tous les rôles</option>
            <option 
              v-for="role in availableRoles" 
              :key="role.id" 
              :value="role.id"
            >
              {{ role.name }}
            </option>
          </select>
          
          <select 
            :value="statusFilter" 
            @change="$emit('update:statusFilter', $event.target.value)"
            class="filter-select"
          >
            <option value="">Tous les statuts</option>
            <option value="active">Actifs</option>
            <option value="inactive">Suspendus</option>
            <option value="blocked">Bloqués</option>
          </select>
        </div>

        <div class="bulk-actions" v-if="selectedUsers.length > 0">
          <select 
            :value="bulkAction" 
            @change="$emit('update:bulkAction', $event.target.value)"
            class="filter-select"
          >
            <option value="">Actions en masse ({{ selectedUsers.length }})</option>
            <option value="activate">Activer</option>
            <option value="suspend">Suspendre</option>
            <option value="assign-role">Assigner un rôle</option>
            <option value="export">Exporter</option>
          </select>
          
          <button 
            @click="$emit('execute-bulk-action')" 
            class="btn btn-outline btn-sm" 
            :disabled="!bulkAction"
          >
            <i class="fas fa-play"></i>
            Exécuter
          </button>
        </div>

        <div class="results-info">
          {{ filteredCount }} utilisateur(s) trouvé(s)
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'UserFilters',
  props: {
    searchQuery: {
      type: String,
      default: ''
    },
    roleFilter: {
      type: String,
      default: ''
    },
    statusFilter: {
      type: String,
      default: ''
    },
    availableRoles: {
      type: Array,
      default: () => []
    },
    selectedUsers: {
      type: Array,
      default: () => []
    },
    bulkAction: {
      type: String,
      default: ''
    },
    filteredCount: {
      type: Number,
      required: true
    }
  },
  emits: [
    'update:searchQuery',
    'update:roleFilter',
    'update:statusFilter', 
    'update:bulkAction',
    'execute-bulk-action'
  ]
}
</script>