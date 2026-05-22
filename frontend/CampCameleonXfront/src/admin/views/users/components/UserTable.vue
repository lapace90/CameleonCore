<template>
  <div class="table-container">
    <!-- LoadingState remplace les 3 états -->
    <LoadingState v-if="loading || error || users.length === 0" :state="loading ? 'loading' : error ? 'error' : 'empty'"
      loading-text="Chargement des utilisateurs..." :error-message="error" empty-title="Aucun utilisateur trouvé"
      :empty-message="hasFilters ? 'Essayez de modifier vos critères de recherche' : 'Commencez par créer votre premier utilisateur'"
      empty-icon="users" :show-action="!hasFilters" action-label="Créer un utilisateur" @retry="$emit('retry')"
      @action="$router.push('/admin/users/create')" />

    <!-- Tableau des utilisateurs -->
    <table v-else class="users-table table">
      <thead>
        <tr>
          <th width="40">
            <input type="checkbox" @change="toggleSelectAll" :checked="allSelected"
              :indeterminate.prop="someSelected" />
          </th>
          <th>
            <button @click="$emit('sort', 'name')" class="sort-button btn btn-sm">
              Utilisateur
              <AppIcon :name="getSortIcon('name')" />
            </button>
          </th>
          <th>
            <button @click="$emit('sort', 'email')" class="sort-button btn btn-sm">
              Email
              <AppIcon :name="getSortIcon('email')" />
            </button>
          </th>
          <th>Rôles</th>
          <th>Statut</th>
          <th>
            <button @click="$emit('sort', 'created_at')" class="sort-button btn btn-sm">
              Inscription
              <AppIcon :name="getSortIcon('created_at')" />
            </button>
          </th>
          <th>
            <button @click="$emit('sort', 'last_login')" class="sort-button btn btn-sm">
              Dernière connexion
              <AppIcon :name="getSortIcon('last_login')" />
            </button>
          </th>
          <th width="120">Actions</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="user in paginatedUsers" :key="user.id" class="user-row"
          :class="{ 'selected': selectedUsers.includes(user.id) }">
          <!-- Sélection -->
          <td>
            <input type="checkbox" :value="user.id" :checked="selectedUsers.includes(user.id)"
              @change="toggleUser(user.id)" />
          </td>

          <!-- Utilisateur -->
          <td class="user-info-cell">
            <div class="user-info">
              <div class="user-avatar">
                <AppIcon name="user" />
              </div>
              <div class="user-details">
                <div class="user-name">{{ user.name }}</div>
                <div class="user-id">#{{ user.id }}</div>
              </div>
            </div>
          </td>

          <!-- Email -->
          <td class="user-email">
            <div class="email-info">
              <span class="email-address">{{ user.email }}</span>
              <div class="email-status">
                <AppIcon name="circle-check" v-if="user.email_verified_at" title="Email vérifié" />
                <AppIcon name="triangle-alert" v-else title="Email non vérifié" />
              </div>
            </div>
          </td>

          <!-- Rôles -->
          <td class="user-roles">
            <div class="roles-list">
              <span v-if="user.role" class="role-badge role-primary">
                {{ user.role.name }}
              </span>

              <span v-for="role in user.additional_roles || []" :key="role.id" class="role-badge role-secondary">
                {{ role.name }}
              </span>

              <span v-if="!user.role && (!user.additional_roles || user.additional_roles.length === 0)"
                class="text-muted">
                Aucun rôle
              </span>
            </div>
          </td>

          <!-- Statut -->
          <td class="user-status">
            <div class="status-indicator" :class="getStatusClass(user.status)">
              <AppIcon :name="getStatusIcon(user.status)" />
              <span>{{ getStatusLabel(user.status) }}</span>
            </div>
          </td>

          <!-- Date d'inscription -->
          <td class="user-created">
            <div class="date-info">
              <div class="date-primary">{{ formatDate(user.created_at) }}</div>
              <div class="date-relative">{{ getRelativeTime(user.created_at) }}</div>
            </div>
          </td>

          <!-- Dernière connexion -->
          <td class="user-last-login">
            <div v-if="user.last_login_at" class="date-info">
              <div class="date-primary">{{ formatDate(user.last_login_at) }}</div>
              <div class="date-relative">{{ getRelativeTime(user.last_login_at) }}</div>
            </div>
            <div v-else class="text-muted">Jamais</div>
          </td>

          <!-- Actions -->
          <td class="actions-col">
            <div class="action-buttons">
              <button @click="$emit('view-user', user)" class="btn-icon" title="Voir les détails">
                <AppIcon name="eye" />
              </button>

              <router-link :to="`/admin/users/${user.id}/edit`" class="btn-icon" title="Modifier">
                <AppIcon name="pencil" />
              </router-link>

              <button @click="$emit('toggle-status', user)" class="btn-icon"
                :title="user.status === 'active' ? 'Suspendre' : 'Activer'">
                <AppIcon :name="user.status === 'active' ? 'pause' : 'play'" />
              </button>

              <button @click="$emit('delete-user', user)" class="btn-icon text-danger" title="Supprimer">
                <AppIcon name="trash-2" />
              </button>
            </div>
          </td>
        </tr>
      </tbody>
    </table>

    <!-- Pagination -->
    <div v-if="totalPages > 1" class="users-pagination">
      <div class="pagination-info">
        Affichage de {{ startItem }} à {{ endItem }} sur {{ totalUsers }} utilisateurs
      </div>

      <div class="pagination-controls">
        <button @click="$emit('page-change', currentPage - 1)" :disabled="currentPage === 1"
          class="btn btn-outline btn-sm">
          <AppIcon name="chevron-left" />
        </button>

        <div class="pagination-pages">
          <button v-for="page in visiblePages" :key="page" @click="$emit('page-change', page)" class="btn btn-sm"
            :class="page === currentPage ? 'btn-primary' : 'btn-outline'">
            {{ page }}
          </button>
        </div>

        <button @click="$emit('page-change', currentPage + 1)" :disabled="currentPage === totalPages"
          class="btn btn-outline btn-sm">
          <AppIcon name="chevron-right" />
        </button>
      </div>
    </div>
  </div>
</template>

<script>
import LoadingState from '@/admin/components/ui/LoadingState.vue'

export default {
  name: 'UserTable',
  components: { LoadingState },
  props: {
    users: {
      type: Array,
      required: true
    },
    selectedUsers: {
      type: Array,
      default: () => []
    },
    loading: {
      type: Boolean,
      default: false
    },
    error: {
      type: String,
      default: null
    },
    hasFilters: {
      type: Boolean,
      default: false
    },
    sortField: {
      type: String,
      default: 'name'
    },
    sortDirection: {
      type: String,
      default: 'asc'
    },
    currentPage: {
      type: Number,
      default: 1
    },
    itemsPerPage: {
      type: Number,
      default: 10
    }
  },
  emits: [
    'update:selectedUsers',
    'sort',
    'view-user',
    'toggle-status',
    'delete-user',
    'retry',
    'page-change'
  ],
  computed: {
    totalUsers() {
      return this.users.length
    },

    totalPages() {
      return Math.ceil(this.users.length / this.itemsPerPage)
    },

    paginatedUsers() {
      const start = (this.currentPage - 1) * this.itemsPerPage
      const end = start + this.itemsPerPage
      return this.users.slice(start, end)
    },

    startItem() {
      return (this.currentPage - 1) * this.itemsPerPage + 1
    },

    endItem() {
      return Math.min(this.currentPage * this.itemsPerPage, this.users.length)
    },

    visiblePages() {
      const pages = []
      const start = Math.max(1, this.currentPage - 2)
      const end = Math.min(this.totalPages, this.currentPage + 2)

      for (let i = start; i <= end; i++) {
        pages.push(i)
      }

      return pages
    },

    allSelected() {
      return this.paginatedUsers.length > 0 &&
        this.selectedUsers.length === this.paginatedUsers.length
    },

    someSelected() {
      return this.selectedUsers.length > 0 &&
        this.selectedUsers.length < this.paginatedUsers.length
    }
  },
  methods: {
    toggleSelectAll() {
      if (this.allSelected) {
        this.$emit('update:selectedUsers', [])
      } else {
        this.$emit('update:selectedUsers', [...this.paginatedUsers.map(user => user.id)])
      }
    },

    toggleUser(userId) {
      const selected = [...this.selectedUsers]
      const index = selected.indexOf(userId)

      if (index > -1) {
        selected.splice(index, 1)
      } else {
        selected.push(userId)
      }

      this.$emit('update:selectedUsers', selected)
    },

    getSortIcon(field) {
      if (this.sortField !== field) {
        return 'arrow-up-down'
      }
      return this.sortDirection === 'asc'
        ? 'chevron-up'
        : 'chevron-down'
    },

    formatDate(dateString) {
      if (!dateString) return ''
      const date = new Date(dateString)
      return date.toLocaleDateString('fr-FR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric'
      })
    },

    getRelativeTime(dateString) {
      if (!dateString) return ''
      const date = new Date(dateString)
      const now = new Date()
      const diffMs = now - date
      const diffDays = Math.floor(diffMs / (1000 * 60 * 60 * 24))

      if (diffDays === 0) return 'Aujourd\'hui'
      if (diffDays === 1) return 'Hier'
      if (diffDays < 7) return `Il y a ${diffDays} jours`
      if (diffDays < 30) return `Il y a ${Math.floor(diffDays / 7)} semaine(s)`
      if (diffDays < 365) return `Il y a ${Math.floor(diffDays / 30)} mois`
      return `Il y a ${Math.floor(diffDays / 365)} an(s)`
    },

    getStatusClass(status) {
      const classes = {
        'active': 'status-active',
        'inactive': 'status-inactive',
        'blocked': 'status-blocked'
      }
      return classes[status] || 'status-unknown'
    },

    getStatusIcon(status) {
      const icons = {
        'active': 'circle-check',
        'inactive': 'circle-pause',
        'blocked': 'ban'
      }
      return icons[status] || 'circle-help'
    },

    getStatusLabel(status) {
      const labels = {
        'active': 'Actif',
        'inactive': 'Suspendu',
        'blocked': 'Bloqué'
      }
      return labels[status] || status
    }
  }
}
</script>