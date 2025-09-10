<template>
  <div class="roles-management">
    <!-- En-tête avec actions -->
    <div class="page-header">
      <div class="header-content">
        <h1 class="page-title">
          <i class="fas fa-shield-alt"></i>
          Gestion des rôles
        </h1>
        <p class="page-subtitle">
          {{ rolesData?.meta?.total || 0 }} rôles • {{ rolesData?.meta?.stats?.total_users || 0 }} utilisateurs assignés
        </p>
        <div class="header-actions">
          <router-link to="/admin/permissions" class="btn btn-outline btn-sm">
            <i class="fas fa-key"></i>
            Permissions
          </router-link>

          <button @click="openCreateModal" class="btn btn-primary btn-sm">
            <i class="fas fa-plus"></i>
            Nouveau rôle
          </button>
        </div>
      </div>
    </div>

    <!-- Messages de succès/erreur -->
    <div v-if="successMessage" class="alert alert-success">
      <i class="fas fa-check-circle"></i>
      {{ successMessage }}
      <button @click="successMessage = null" class="btn-close">&times;</button>
    </div>

    <div v-if="error" class="alert alert-error">
      <i class="fas fa-exclamation-triangle"></i>
      {{ error }}
      <button @click="error = null" class="btn-close">&times;</button>
    </div>

    <!-- Filtres et recherche -->
    <div class="filters-section">
      <div class="search-box">
        <i class="fas fa-search search-icon"></i>
        <input v-model="searchQuery" type="text" placeholder="Rechercher un rôle..." class="search-input">
      </div>

      <div class="filter-options">
        <select v-model="filterType" class="filter-select">
          <option value="">Tous les rôles</option>
          <option value="critical">Rôles critiques</option>
          <option value="deletable">Modifiables</option>
          <option value="with_users">Avec utilisateurs</option>
        </select>
      </div>

      <div class="results-info">
        {{ filteredRoles.length }} rôle(s) trouvé(s)
      </div>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="loading-container">
      <i class="fas fa-spinner fa-spin"></i>
      <span>Chargement des rôles...</span>
    </div>

    <!-- Grille des rôles -->
    <div v-else class="roles-grid">
      <div v-for="role in filteredRoles" :key="role.id" class="role-card" :class="{ 'critical': role.is_critical }">
        <!-- En-tête de la carte -->
        <div class="card-header" :style="{ borderTopColor: role.color }">
          <div class="role-info">
            <div class="role-icon" :style="{ backgroundColor: role.color }">
              <i :class="`fas ${role.icon}`"></i>
            </div>
            <div class="role-details">
              <h3 class="role-name">{{ role.name }}</h3>
              <p class="role-slug">{{ role.slug }}</p>
            </div>
          </div>

          <div class="role-badges">
            <span v-if="role.is_critical" class="badge badge-critical">
              <i class="fas fa-shield-alt"></i>
              Critique
            </span>
          </div>
        </div>

        <!-- Description -->
        <div class="card-content">
          <p class="role-description">
            {{ role.description || 'Aucune description' }}
          </p>

          <!-- Statistiques -->
          <div class="role-stats">
            <div class="stat-item">
              <i class="fas fa-key"></i>
              <span>{{ role.permissions_count }} permission(s)</span>
            </div>
            <div class="stat-item">
              <i class="fas fa-users"></i>
              <span>{{ role.total_users_count }} utilisateur(s)</span>
            </div>
          </div>

          <!-- Aperçu permissions (3 premières) -->
          <div v-if="role.permissions && role.permissions.length > 0" class="permissions-preview">
            <div class="preview-title">Permissions :</div>
            <div class="permission-tags">
              <span v-for="permission in role.permissions.slice(0, 3)" :key="permission.id" class="permission-tag">
                {{ permission.name }}
              </span>
              <span v-if="role.permissions.length > 3" class="more-tag">
                +{{ role.permissions.length - 3 }} autres
              </span>
            </div>
          </div>

          <div v-else class="no-permissions">
            <i class="fas fa-info-circle"></i>
            Aucune permission assignée
          </div>
        </div>

        <!-- Actions -->
        <div class="card-actions">
          <button @click="viewRoleDetails(role)" class="btn btn-outline btn-sm" title="Voir les détails">
            <i class="fas fa-eye"></i>
            Détails
          </button>

          <button @click="editRole(role)" class="btn btn-outline btn-sm" title="Modifier">
            <i class="fas fa-edit"></i>
            Modifier
          </button>

          <button @click="deleteRole(role)" class="btn btn-danger btn-sm" :disabled="!role.can_be_deleted"
            :title="!role.can_be_deleted ? 'Ce rôle ne peut pas être supprimé' : 'Supprimer'">
            <i class="fas fa-trash"></i>
            Supprimer
          </button>
        </div>
      </div>
    </div>

    <!-- Modales séparées (comme on a créé) -->
    <RoleCreateModal v-if="showCreateModal" @close="showCreateModal = false" @created="handleRoleCreated" />

    <RoleEditModal v-if="showEditModal && selectedRole" :role="selectedRole" @close="showEditModal = false"
      @updated="handleRoleUpdated" />

    <RoleDetailsModal v-if="showDetailsModal && selectedRole" :role="selectedRole" @close="showDetailsModal = false"
      @edit="handleEditFromDetails" />

    <RoleDeleteModal v-if="showDeleteModal && selectedRole" :role="selectedRole" @close="showDeleteModal = false"
      @deleted="handleRoleDeleted" />
  </div>
</template>

<script>
// ✅ UTILISE le service et store qu'on a créés
import { useRolesStore } from '@/shared/stores/roles'
import { mapState, mapActions } from 'pinia'

// ✅ UTILISE les modales qu'on a créées  
import RoleCreateModal from '@/admin/components/modals/RoleCreateModal.vue'
import RoleEditModal from '@/admin/components/modals/RoleEditModal.vue'
import RoleDetailsModal from '@/admin/components/modals/RoleDetailsModal.vue'
import RoleDeleteModal from '@/admin/components/modals/DeleteRoleModal.vue'

export default {
  name: 'RolesManagement',

  components: {
    RoleCreateModal,
    RoleEditModal,
    RoleDetailsModal,
    RoleDeleteModal
  },

  // ✅ UTILISE le store qu'on a créé
  setup() {
    const rolesStore = useRolesStore()
    return { rolesStore }
  },

  data() {
    return {
      // Filtres (gardés locaux pour la réactivité)
      searchQuery: '',
      filterType: '',

      // Modales (gardés locaux)
      showCreateModal: false,
      showEditModal: false,
      showDetailsModal: false,
      showDeleteModal: false,
      selectedRole: null,
    }
  },

  // ✅ UTILISE le store au lieu de data locales
  computed: {
    ...mapState(useRolesStore, {
      rolesData: 'rolesData',
      loading: 'loading',
      error: 'error',
      successMessage: 'successMessage'
    }),

    roles() {
      return this.rolesData?.data || []
    },

    filteredRoles() {
      let filtered = this.roles

      // Filtre par recherche
      if (this.searchQuery) {
        const query = this.searchQuery.toLowerCase()
        filtered = filtered.filter(role =>
          role.name.toLowerCase().includes(query) ||
          (role.slug?.toLowerCase().includes(query) ?? false) ||
          role.description?.toLowerCase().includes(query)
        )
      }

      // Filtre par type
      if (this.filterType) {
        switch (this.filterType) {
          case 'critical':
            filtered = filtered.filter(role => role.is_critical)
            break
          case 'deletable':
            filtered = filtered.filter(role => this.isDeletable(role))
            break
          case 'with_users':
            filtered = filtered.filter(role => role.total_users_count > 0)
            break
        }
      }

      return filtered
    }
  },

  // ✅ UTILISE le store au lieu de loadRoles/loadPermissions
  async mounted() {
    await this.loadAllData({ force: true })
  },

  methods: {
    // ✅ UTILISE les actions du store
    ...mapActions(useRolesStore, [
      'loadAllData',
      'clearMessages'
    ]),

    isDeletable(role) {
      const count =
        role.total_users_count ??
        role.users_count ??
        (Array.isArray(role.users) ? role.users.length : 0)
      return !role.is_critical && Number(count) === 0
    },

    // Actions modales (simples)
    openCreateModal() {
      this.showCreateModal = true
    },

    viewRoleDetails(role) {
      this.selectedRole = role
      this.showDetailsModal = true
    },

    editRole(role) {
      this.selectedRole = role
      this.showEditModal = true
    },

    deleteRole(role) {
      this.selectedRole = role
      this.showDeleteModal = true
    },

    // Handlers événements modales
    handleRoleCreated() {
      // Le store gère déjà le message et reload
      this.showCreateModal = false
    },

    handleRoleUpdated() {
      // Le store gère déjà le message et reload
      this.showEditModal = false
      this.selectedRole = null
    },

    handleRoleDeleted() {
      // Le store gère déjà le message et reload  
      this.showDeleteModal = false
      this.selectedRole = null
    },

    handleEditFromDetails(role) {
      this.showDetailsModal = false
      this.selectedRole = role
      this.showEditModal = true
    }
  }
}
</script>
