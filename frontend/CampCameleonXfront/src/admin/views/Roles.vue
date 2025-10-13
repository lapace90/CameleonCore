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

    <!--  AdminFilterBar -->
    <AdminFilterBar
      v-model="filters"
      :default-filters="defaultFilters"
      :fields="roleFilterFields"
      search-placeholder="Rechercher un rôle..."
      reset-label="Réinitialiser"
      @apply="applyFilters"
    >
      <!-- Résultats personnalisés -->
      <template #results="{ activeCount }">
        <span class="results-info">
          <i class="fas fa-shield-alt"></i>
          {{ filteredRolesCount }} rôle(s)
          <span v-if="activeCount > 0" class="text-muted">
            · {{ activeCount }} filtre(s)
          </span>
        </span>
      </template>
    </AdminFilterBar>

    <!-- Loading -->
    <div v-if="loading" class="loading-state">
      <LoadingState 
        state="loading" 
        loading-text="Chargement des rôles..." 
        :container-class="'py-5'"
      />
    </div>

    <!-- Liste des rôles -->
    <div v-else-if="roles.length > 0" class="roles-grid">
      <div v-for="role in filteredRoles" :key="role.id" class="role-card">
        <div class="role-header">
          <div class="role-info">
            <h3 class="role-name">{{ role.name }}</h3>
            <span class="role-slug">{{ role.slug }}</span>
          </div>
          <div class="role-actions">
            <button @click="editRole(role)" class="btn-icon" title="Modifier">
              <i class="fas fa-edit"></i>
            </button>
            <button 
              v-if="!role.is_system" 
              @click="deleteRole(role)" 
              class="btn-icon text-danger" 
              title="Supprimer"
            >
              <i class="fas fa-trash"></i>
            </button>
          </div>
        </div>

        <p v-if="role.description" class="role-description">
          {{ role.description }}
        </p>

        <div class="role-stats">
          <div class="stat">
            <i class="fas fa-users"></i>
            <span>{{ role.users_count || 0 }} utilisateurs</span>
          </div>
          <div class="stat">
            <i class="fas fa-key"></i>
            <span>{{ role.permissions_count || 0 }} permissions</span>
          </div>
        </div>

        <div v-if="role.is_system" class="system-badge">
          <i class="fas fa-lock"></i>
          Rôle système
        </div>
      </div>
    </div>

    <!-- Empty state -->
    <div v-else class="empty-state">
      <i class="fas fa-shield-alt fa-3x"></i>
      <h3>Aucun rôle trouvé</h3>
      <p v-if="hasActiveFilters">
        Aucun rôle ne correspond à vos critères de recherche.
      </p>
      <p v-else>
        Créez votre premier rôle pour commencer.
      </p>
      <button @click="openCreateModal" class="btn btn-primary">
        <i class="fas fa-plus"></i>
        Créer un rôle
      </button>
    </div>

    <!-- Modal création/édition (à implémenter) -->
  </div>
</template>

<script>
import AdminFilterBar from '@/admin/components/ui/AdminFilterBar.vue'
import RolesApi from '@/services/RolesApi'
import LoadingState from '@/admin/components/ui/LoadingState.vue'

export default {
  name: 'Roles',
  components: {
    AdminFilterBar,
    LoadingState
  },

  data() {
    return {
      roles: [],
      rolesData: null,
      loading: false,
      error: null,
      successMessage: null,

      // ✅ Filtres avec valeurs par défaut
      defaultFilters: {
        search: '',
        type: '',
        users: ''
      },

      filters: {
        search: '',
        type: '',
        users: ''
      }
    }
  },

  computed: {
    // ✅ Configuration des champs de filtres
    roleFilterFields() {
      return [
        {
          key: 'type',
          type: 'select',
          placeholder: 'Tous les types',
          options: [
            { value: 'system', label: 'Rôles système' },
            { value: 'custom', label: 'Rôles personnalisés' }
          ]
        },
        {
          key: 'users',
          type: 'select',
          placeholder: 'Tous les rôles',
          options: [
            { value: 'with_users', label: 'Avec utilisateurs' },
            { value: 'without_users', label: 'Sans utilisateurs' }
          ]
        }
      ]
    },

    hasActiveFilters() {
      return !!(this.filters.search || this.filters.type || this.filters.users)
    },

    filteredRoles() {
      let filtered = [...this.roles]

      // Recherche textuelle
      if (this.filters.search) {
        const query = this.filters.search.toLowerCase()
        filtered = filtered.filter(role =>
          role.name.toLowerCase().includes(query) ||
          role.slug.toLowerCase().includes(query) ||
          role.description?.toLowerCase().includes(query)
        )
      }

      // Filtre par type
      if (this.filters.type === 'system') {
        filtered = filtered.filter(role => role.is_system)
      } else if (this.filters.type === 'custom') {
        filtered = filtered.filter(role => !role.is_system)
      }

      // Filtre par utilisateurs
      if (this.filters.users === 'with_users') {
        filtered = filtered.filter(role => (role.users_count || 0) > 0)
      } else if (this.filters.users === 'without_users') {
        filtered = filtered.filter(role => (role.users_count || 0) === 0)
      }

      return filtered
    },

    filteredRolesCount() {
      return this.filteredRoles.length
    }
  },

  async mounted() {
    await this.fetchRoles()
  },

  methods: {
    //  Utiliser RolesApi au lieu d'AdminApi
    async fetchRoles() {
      this.loading = true
      this.error = null

      try {
        const response = await RolesApi.getAll()
        
        // Normaliser la réponse (support API Platform hydra)
        if (response && response['hydra:member']) {
          this.rolesData = {
            data: response['hydra:member'],
            meta: {
              total: response['hydra:totalItems'] || 0
            }
          }
          this.roles = response['hydra:member']
        } else if (response && response.data) {
          this.rolesData = response
          this.roles = response.data
        } else if (Array.isArray(response)) {
          this.roles = response
          this.rolesData = { data: response, meta: { total: response.length } }
        } else {
          this.roles = []
          this.rolesData = { data: [], meta: { total: 0 } }
        }
      } catch (err) {
        console.error('Erreur chargement rôles:', err)
        this.error = 'Erreur lors du chargement des rôles'
      } finally {
        this.loading = false
      }
    },

    applyFilters() {
      // Les filtres sont déjà appliqués via computed filteredRoles
      // Cette méthode peut être utilisée pour des actions supplémentaires
    },

    openCreateModal() {
      // TODO: Implémenter modal de création
      alert('Modal de création à implémenter')
    },

    editRole(role) {
      // TODO: Implémenter modal d'édition
      alert(`Édition du rôle ${role.name} à implémenter`)
    },

    //  Utiliser RolesApi
    async deleteRole(role) {
      if (!confirm(`Êtes-vous sûr de vouloir supprimer le rôle "${role.name}" ?`)) {
        return
      }

      try {
        await RolesApi.delete(role.id)
        this.successMessage = `Rôle "${role.name}" supprimé avec succès`
        await this.fetchRoles()

        setTimeout(() => {
          this.successMessage = null
        }, 3000)
      } catch (err) {
        console.error('Erreur suppression rôle:', err)
        this.error = err.response?.data?.message || 'Erreur lors de la suppression'
      }
    }
  }
}
</script>

<style scoped>
.roles-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
  gap: 1.5rem;
  margin-top: 2rem;
}

.role-card {
  background: #ffffff;
  border-radius: 12px;
  padding: 1.5rem;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
  transition: all 0.2s;
}

.role-card:hover {
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
  transform: translateY(-2px);
}

.role-header {
  display: flex;
  justify-content: space-between;
  align-items: start;
  margin-bottom: 1rem;
}

.role-info {
  flex: 1;
}

.role-name {
  font-size: 1.25rem;
  font-weight: 600;
  color: #32325d;
  margin: 0 0 0.25rem 0;
}

.role-slug {
  display: inline-block;
  padding: 0.25rem 0.75rem;
  background: #f7fafc;
  color: #8898aa;
  font-size: 0.75rem;
  font-weight: 500;
  border-radius: 6px;
  font-family: 'Monaco', 'Courier New', monospace;
}

.role-actions {
  display: flex;
  gap: 0.5rem;
}

.role-description {
  color: #8898aa;
  font-size: 0.875rem;
  line-height: 1.5;
  margin: 0 0 1rem 0;
}

.role-stats {
  display: flex;
  gap: 1.5rem;
  padding-top: 1rem;
  border-top: 1px solid #e9ecef;
}

.stat {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  color: #8898aa;
  font-size: 0.875rem;
}

.stat i {
  color: #5e72e4;
}

.system-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem 1rem;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  font-size: 0.75rem;
  font-weight: 600;
  border-radius: 8px;
  margin-top: 1rem;
}

.empty-state {
  text-align: center;
  padding: 4rem 2rem;
  background: #ffffff;
  border-radius: 12px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
}

.empty-state i {
  color: #8898aa;
  margin-bottom: 1rem;
}

.empty-state h3 {
  color: #32325d;
  margin-bottom: 0.5rem;
}

.empty-state p {
  color: #8898aa;
  margin-bottom: 1.5rem;
}
</style>
