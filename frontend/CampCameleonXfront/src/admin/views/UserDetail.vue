<template>
  <div class="user-detail-page">
    <!-- Loading -->
    <div v-if="loading" class="loading-state">
      <div class="spinner"></div>
      <p>Chargement des détails utilisateur...</p>
    </div>

    <!-- Erreur -->
    <div v-else-if="error" class="error-state">
      <i class="fas fa-exclamation-triangle"></i>
      <h3>{{ error }}</h3>
      <button @click="fetchUser" class="btn btn-outline btn-sm">
        <i class="fas fa-redo"></i>
        Réessayer
      </button>
    </div>

    <!-- Contenu principal -->
    <div v-else-if="user" class="user-detail-container">
      <!-- Header -->
      <div class="detail-header">
        <div class="header-navigation">
          <router-link to="/admin/users" class="back-link">
            <i class="fas fa-arrow-left"></i>
            Retour aux utilisateurs
          </router-link>
          <div class="breadcrumb">
            <span>Utilisateurs</span>
            <i class="fas fa-chevron-right"></i>
            <span>{{ user.name }}</span>
          </div>
        </div>

        <div class="header-actions">
          <router-link :to="`/admin/users/${user.id}/edit`" class="btn btn-primary btn-sm">
            <i class="fas fa-edit"></i>
            Modifier
          </router-link>
          
          <button 
            @click="toggleUserStatus" 
            class="btn btn-sm"
            :class="user.status === 'active' ? 'btn-warning' : 'btn-success'"
          >
            <i :class="user.status === 'active' ? 'fas fa-pause' : 'fas fa-play'"></i>
            {{ user.status === 'active' ? 'Suspendre' : 'Activer' }}
          </button>
        </div>
      </div>

      <!-- Titre et statut -->
      <div class="user-title-section">
        <div class="user-header">
          <div class="user-avatar-large">
            <i class="fas fa-user"></i>
            <div class="status-indicator" :class="getStatusClass(user.status)"></div>
          </div>
          
          <div class="user-info-main">
            <h1 class="user-name">{{ user.name }}</h1>
            <div class="user-email">{{ user.email }}</div>
            
            <div class="user-meta">
              <div class="meta-item">
                <i class="fas fa-calendar-plus"></i>
                <span>Inscrit le {{ formatDate(user.created_at) }}</span>
              </div>
              
              <div v-if="user.last_login_at" class="meta-item">
                <i class="fas fa-clock"></i>
                <span>Dernière connexion {{ getRelativeTime(user.last_login_at) }}</span>
              </div>
              
              <div v-if="user.email_verified_at" class="meta-item">
                <i class="fas fa-check-circle text-success"></i>
                <span>Email vérifié</span>
              </div>
              
              <div v-else class="meta-item">
                <i class="fas fa-exclamation-triangle text-warning"></i>
                <span>Email non vérifié</span>
              </div>
            </div>
          </div>
        </div>

        <div class="status-controls">
          <div class="status-badge" :class="getStatusClass(user.status)">
            <i :class="getStatusIcon(user.status)"></i>
            <span>{{ getStatusLabel(user.status) }}</span>
          </div>
        </div>
      </div>

      <!-- Contenu en colonnes -->
      <div class="user-detail-content">
        <!-- Colonne gauche - Rôles et permissions -->
        <div class="detail-column">
          <!-- Rôles -->
          <div class="detail-section">
            <h3 class="section-title">
              <i class="fas fa-shield-alt"></i>
              Rôles
            </h3>
            
            <div v-if="user.role || (user.additional_roles && user.additional_roles.length > 0)" class="roles-display">
              <!-- Rôle principal -->
              <div v-if="user.role" class="role-item role-primary">
                <div class="role-header">
                  <i class="fas fa-crown"></i>
                  <span class="role-name">{{ user.role.name }}</span>
                  <span class="role-badge">Principal</span>
                </div>
                <div v-if="user.role.description" class="role-description">
                  {{ user.role.description }}
                </div>
              </div>
              
              <!-- Rôles additionnels -->
              <div v-if="user.additional_roles && user.additional_roles.length > 0" class="additional-roles">
                <h4 class="subsection-title">Rôles additionnels</h4>
                <div 
                  v-for="role in user.additional_roles" 
                  :key="role.id"
                  class="role-item role-secondary"
                >
                  <div class="role-header">
                    <i class="fas fa-plus-circle"></i>
                    <span class="role-name">{{ role.name }}</span>
                  </div>
                  <div v-if="role.description" class="role-description">
                    {{ role.description }}
                  </div>
                </div>
              </div>
            </div>
            
            <div v-else class="empty-state-small">
              <i class="fas fa-shield-alt"></i>
              <p>Aucun rôle assigné</p>
            </div>
          </div>

          <!-- Permissions -->
          <div class="detail-section">
            <h3 class="section-title">
              <i class="fas fa-key"></i>
              Permissions
              <span class="section-count">({{ user.all_permissions ? user.all_permissions.length : 0 }})</span>
            </h3>
            
            <div v-if="user.all_permissions && user.all_permissions.length > 0" class="permissions-display">
              <!-- Permissions par catégorie -->
              <div 
                v-for="(categoryPerms, category) in permissionsByCategory" 
                :key="category"
                class="permission-category"
              >
                <h4 class="permission-category-title">
                  <i :class="getCategoryIcon(category)"></i>
                  {{ getCategoryLabel(category) }}
                  <span class="category-count">({{ categoryPerms.length }})</span>
                </h4>
                
                <div class="permissions-grid">
                  <div 
                    v-for="permission in categoryPerms" 
                    :key="permission.id"
                    class="permission-item"
                  >
                    <span class="permission-name">{{ permission.name }}</span>
                    <span class="permission-action" :class="getActionClass(permission.action)">
                      {{ permission.action }}
                    </span>
                  </div>
                </div>
              </div>
            </div>
            
            <div v-else class="empty-state-small">
              <i class="fas fa-key"></i>
              <p>Aucune permission</p>
            </div>
          </div>
        </div>

        <!-- Colonne droite - Activité et informations -->
        <div class="detail-column">
          <!-- Informations générales -->
          <div class="detail-section">
            <h3 class="section-title">
              <i class="fas fa-info-circle"></i>
              Informations générales
            </h3>
            
            <div class="info-grid">
              <div class="info-item">
                <label>ID utilisateur</label>
                <value>#{{ user.id }}</value>
              </div>
              
              <div class="info-item">
                <label>Statut</label>
                <value>
                  <span class="status-inline" :class="getStatusClass(user.status)">
                    {{ getStatusLabel(user.status) }}
                  </span>
                </value>
              </div>
              
              <div class="info-item">
                <label>Date d'inscription</label>
                <value>{{ formatDateTime(user.created_at) }}</value>
              </div>
              
              <div class="info-item">
                <label>Dernière mise à jour</label>
                <value>{{ formatDateTime(user.updated_at) }}</value>
              </div>
              
              <div v-if="user.last_login_at" class="info-item">
                <label>Dernière connexion</label>
                <value>{{ formatDateTime(user.last_login_at) }}</value>
              </div>
              
              <div v-if="user.last_login_ip" class="info-item">
                <label>Dernière IP</label>
                <value class="font-mono">{{ user.last_login_ip }}</value>
              </div>
            </div>
          </div>

          <!-- Sécurité -->
          <div class="detail-section">
            <h3 class="section-title">
              <i class="fas fa-lock"></i>
              Sécurité
            </h3>
            
            <div class="security-info">
              <div class="security-item">
                <div class="security-icon">
                  <i :class="user.email_verified_at ? 'fas fa-check-circle text-success' : 'fas fa-exclamation-triangle text-warning'"></i>
                </div>
                <div class="security-content">
                  <span class="security-label">Vérification email</span>
                  <span class="security-value">
                    {{ user.email_verified_at ? 'Vérifié le ' + formatDate(user.email_verified_at) : 'Non vérifié' }}
                  </span>
                </div>
              </div>
              
              <div class="security-item">
                <div class="security-icon">
                  <i :class="user.password_reset_required ? 'fas fa-exclamation-circle text-warning' : 'fas fa-key text-success'"></i>
                </div>
                <div class="security-content">
                  <span class="security-label">Mot de passe</span>
                  <span class="security-value">
                    {{ user.password_reset_required ? 'Réinitialisation requise' : 'OK' }}
                  </span>
                </div>
              </div>
            </div>

            <div class="security-actions">
              <button @click="resetPassword" class="btn btn-outline btn-sm">
                <i class="fas fa-key"></i>
                Forcer réinitialisation
              </button>
              
              <button v-if="!user.email_verified_at" @click="sendVerificationEmail" class="btn btn-outline btn-sm">
                <i class="fas fa-envelope"></i>
                Renvoyer vérification
              </button>
            </div>
          </div>

          <!-- Actions rapides -->
          <div class="detail-section">
            <h3 class="section-title">
              <i class="fas fa-bolt"></i>
              Actions rapides
            </h3>
            
            <div class="quick-actions">
              <router-link :to="`/admin/users/${user.id}/edit`" class="quick-action-btn">
                <i class="fas fa-edit"></i>
                <span>Modifier</span>
              </router-link>
              
              <button @click="duplicateUser" class="quick-action-btn">
                <i class="fas fa-copy"></i>
                <span>Dupliquer</span>
              </button>
              
              <button @click="exportUserData" class="quick-action-btn">
                <i class="fas fa-download"></i>
                <span>Exporter</span>
              </button>
              
              <button @click="viewUserActivity" class="quick-action-btn">
                <i class="fas fa-history"></i>
                <span>Activité</span>
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios'

export default {
  name: 'UserDetail',
  data() {
    return {
      user: null,
      loading: true,
      error: null
    }
  },
  computed: {
    userId() {
      return this.$route.params.id
    },
    
    permissionsByCategory() {
      if (!this.user?.all_permissions) return {}
      
      const categories = {}
      this.user.all_permissions.forEach(permission => {
        const category = this.getPermissionCategory(permission.action)
        if (!categories[category]) {
          categories[category] = []
        }
        categories[category].push(permission)
      })
      
      return categories
    }
  },
  async created() {
    await this.fetchUser()
  },
  methods: {
    async fetchUser() {
      this.loading = true
      this.error = null

      try {
        const response = await axios.get(`/api/admin/users/${this.userId}`)
        this.user = response.data
      } catch (error) {
        console.error('Erreur lors du chargement de l\'utilisateur:', error)
        if (error.response?.status === 404) {
          this.error = 'Utilisateur introuvable'
        } else {
          this.error = 'Impossible de charger les détails de l\'utilisateur'
        }
      } finally {
        this.loading = false
      }
    },

    async toggleUserStatus() {
      const newStatus = this.user.status === 'active' ? 'inactive' : 'active'
      const action = newStatus === 'active' ? 'activer' : 'suspendre'
      
      if (!confirm(`${action} l'utilisateur "${this.user.name}" ?`)) {
        return
      }

      try {
        await axios.patch(`/api/admin/users/${this.user.id}/status`, { status: newStatus })
        this.user.status = newStatus
      } catch (error) {
        console.error('Erreur lors du changement de statut:', error)
        alert('Impossible de modifier le statut de l\'utilisateur')
      }
    },

    async resetPassword() {
      if (!confirm(`Forcer la réinitialisation du mot de passe pour "${this.user.name}" ?`)) {
        return
      }

      try {
        await axios.post(`/api/admin/users/${this.user.id}/reset-password`)
        this.user.password_reset_required = true
        alert('L\'utilisateur devra changer son mot de passe à la prochaine connexion')
      } catch (error) {
        console.error('Erreur lors de la réinitialisation:', error)
        alert('Impossible de forcer la réinitialisation du mot de passe')
      }
    },

    async sendVerificationEmail() {
      try {
        await axios.post(`/api/admin/users/${this.user.id}/send-verification`)
        alert('Email de vérification envoyé')
      } catch (error) {
        console.error('Erreur lors de l\'envoi:', error)
        alert('Impossible d\'envoyer l\'email de vérification')
      }
    },

    async duplicateUser() {
      if (!confirm(`Créer un nouvel utilisateur basé sur "${this.user.name}" ?`)) {
        return
      }

      try {
        const response = await axios.post(`/api/admin/users/${this.user.id}/duplicate`)
        this.$router.push(`/admin/users/${response.data.user.id}/edit`)
      } catch (error) {
        console.error('Erreur lors de la duplication:', error)
        alert('Impossible de dupliquer l\'utilisateur')
      }
    },

    async exportUserData() {
      try {
        const response = await axios.get(`/api/admin/users/${this.user.id}/export`)
        const blob = new Blob([JSON.stringify(response.data, null, 2)], { type: 'application/json' })
        const url = window.URL.createObjectURL(blob)
        const a = document.createElement('a')
        a.href = url
        a.download = `user-${this.user.id}-${this.user.name.replace(/\s+/g, '-')}.json`
        document.body.appendChild(a)
        a.click()
        window.URL.revokeObjectURL(url)
        document.body.removeChild(a)
      } catch (error) {
        console.error('Erreur lors de l\'export:', error)
        alert('Impossible d\'exporter les données utilisateur')
      }
    },

    viewUserActivity() {
      // Naviguer vers la page d'activité de l'utilisateur
      this.$router.push(`/admin/users/${this.user.id}/activity`)
    },

    // Utilitaires
    formatDate(dateString) {
      if (!dateString) return ''
      const date = new Date(dateString)
      return date.toLocaleDateString('fr-FR')
    },

    formatDateTime(dateString) {
      if (!dateString) return ''
      const date = new Date(dateString)
      return date.toLocaleString('fr-FR')
    },

    getRelativeTime(dateString) {
      if (!dateString) return ''
      const date = new Date(dateString)
      const now = new Date()
      const diffMs = now - date
      const diffDays = Math.floor(diffMs / (1000 * 60 * 60 * 24))
      
      if (diffDays === 0) return 'aujourd\'hui'
      if (diffDays === 1) return 'hier'
      if (diffDays < 7) return `il y a ${diffDays} jours`
      if (diffDays < 30) return `il y a ${Math.floor(diffDays / 7)} semaine(s)`
      return `il y a plus d'un mois`
    },

    getStatusClass(status) {
      const classes = {
        'active': 'status-active',
        'inactive': 'status-warning', 
        'blocked': 'status-danger'
      }
      return classes[status] || 'status-unknown'
    },

    getStatusIcon(status) {
      const icons = {
        'active': 'fas fa-check-circle',
        'inactive': 'fas fa-pause-circle',
        'blocked': 'fas fa-ban'
      }
      return icons[status] || 'fas fa-question-circle'
    },

    getStatusLabel(status) {
      const labels = {
        'active': 'Actif',
        'inactive': 'Suspendu',
        'blocked': 'Bloqué'
      }
      return labels[status] || status
    },

    getPermissionCategory(action) {
      const action_lower = action.toLowerCase()
      
      if (['create', 'add', 'store'].includes(action_lower)) return 'create'
      if (['read', 'view', 'show', 'list', 'index'].includes(action_lower)) return 'read'
      if (['update', 'edit', 'modify'].includes(action_lower)) return 'update'  
      if (['delete', 'destroy', 'remove'].includes(action_lower)) return 'delete'
      if (['manage', 'admin', 'control'].includes(action_lower)) return 'admin'
      
      return 'other'
    },

    getCategoryLabel(category) {
      const labels = {
        'create': 'Création',
        'read': 'Lecture',
        'update': 'Modification',
        'delete': 'Suppression',
        'admin': 'Administration',
        'other': 'Autres'
      }
      return labels[category] || category
    },

    getCategoryIcon(category) {
      const icons = {
        'create': 'fas fa-plus',
        'read': 'fas fa-eye',
        'update': 'fas fa-edit',
        'delete': 'fas fa-trash',
        'admin': 'fas fa-cog',
        'other': 'fas fa-star'
      }
      return icons[category] || 'fas fa-key'
    },

    getActionClass(action) {
      const classes = {
        'create': 'badge-success',
        'read': 'badge-info',
        'update': 'badge-warning',
        'delete': 'badge-danger',
        'admin': 'badge-primary'
      }
      return classes[action.toLowerCase()] || 'badge-secondary'
    }
  }
}
</script>