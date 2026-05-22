<template>
  <div class="product-detail-container">
    <!-- Loading -->
    <LoadingState v-if="loading" state="loading" variant="inline"
      loading-text="Chargement des détails utilisateur..." />

    <!-- Erreur -->
    <div v-else-if="error" class="error-state">
      <AppIcon name="triangle-alert" />
      <h3>{{ error }}</h3>
      <button @click="fetchUser" class="btn btn-outline btn-sm">
        <AppIcon name="rotate-cw" />
        Réessayer
      </button>
    </div>

    <!-- Contenu principal -->
    <div v-else-if="user">
      <!-- Header -->
      <div class="page-header">
        <div class="header-navigation">
          <router-link to="/admin/users" class="back-link">
            <AppIcon name="arrow-left" />
            Retour aux utilisateurs
          </router-link>
          <div class="breadcrumb">
            <span>Utilisateurs</span>
            <AppIcon name="chevron-right" />
            <span>{{ user.name }}</span>
          </div>
        </div>

        <div class="header-actions">
          <router-link :to="`/admin/users/${user.id}/edit`" class="btn btn-primary btn-sm">
            <AppIcon name="pencil" />
            Modifier
          </router-link>

          <button @click="toggleUserStatus" class="btn btn-sm"
            :class="user.status === 'active' ? 'btn-warning' : 'btn-success'">
            <AppIcon :name="user.status === 'active' ? 'pause' : 'play'" />
            {{ user.status === 'active' ? 'Suspendre' : 'Activer' }}
          </button>
        </div>
      </div>

      <!-- Titre et statut -->
      <div class="page-title-section">
        <!-- Avatar au lieu du badge -->
        <div class="user-title-header">
          <div class="user-avatar-large">
            <img v-if="user.avatar" :src="user.avatar" :alt="user.name" class="avatar-image" />
            <AppIcon name="user" v-else />
          </div>
        </div>
        <div class="user-title-info mt-6">

          <h1 class="page-title">{{ user.name }}</h1>
          <p class="user-subtitle">
            <a :href="`mailto:${user.email}`" class="email-link">
              {{ user.email }}
            </a>
          </p>
        </div>

        <div class="meta-inline m-3 " style="text-align: right;">
          <div class="status-badge mb-4" :class="getStatusClass(user.status)">
            <AppIcon :name="getStatusIcon(user.status)" style="padding-right: 4px;" />
            <span>{{ getStatusLabel(user.status) }}</span>
          </div>
          <div class="meta-item">
            <AppIcon name="calendar-plus" />
            <span>Inscrit le {{ formatDate(user.created_at) }}</span>
          </div>

          <div v-if="user.last_login_at" class="meta-item">
            <AppIcon name="clock" />
            <span>Dernière connexion {{ getRelativeTime(user.last_login_at) }}</span>
          </div>

          <div v-if="user.email_verified_at" class="meta-item ">
            <AppIcon name="circle-check" />
            <span>Email vérifié</span>
          </div>

          <div v-else class="meta-item">
            <AppIcon name="triangle-alert" />
            <span>Email non vérifié</span>
          </div>

        </div>
      </div>

      <!-- Contenu en colonnes -->
      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
        <!-- Colonne gauche - Rôles et permissions -->
        <div>
          <!-- Rôles -->
          <div
            style="background: white; border-radius: 12px; padding: 1.5rem; margin-bottom: 1.5rem; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);">
            <h3 style="margin: 0 0 1rem 0; font-size: 1.25rem; font-weight: 600; color: #1f2937;">
              <AppIcon name="shield" />
              Rôles
            </h3>

            <div v-if="user.role || (user.additional_roles && user.additional_roles.length > 0)">
              <!-- Rôle principal -->
              <div v-if="user.role"
                style="margin-bottom: 1rem; padding: 1rem; background: #f0fdf4; border-radius: 8px; border: 1px solid #bbf7d0;">
                <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">
                  <AppIcon name="crown" style="color: #d97706;" />
                  <span style="font-weight: 600;">{{ user.role.name }}</span>
                  <span class="badge badge-warning">Principal</span>
                </div>
                <div v-if="user.role.description" style="color: #6b7280; font-size: 0.875rem;">
                  {{ user.role.description }}
                </div>
              </div>

              <!-- Rôles additionnels -->
              <div v-if="user.additional_roles && user.additional_roles.length > 0">
                <h4 style="margin: 1rem 0 0.5rem 0; font-size: 1rem; color: #374151;">Rôles additionnels</h4>
                <div v-for="role in user.additional_roles" :key="role.id"
                  style="margin-bottom: 0.5rem; padding: 0.75rem; background: #f8fafc; border-radius: 6px; border: 1px solid #e2e8f0;">
                  <div style="display: flex; align-items: center; gap: 0.5rem;">
                    <AppIcon name="circle-plus" style="color: #6366f1;" />
                    <span style="font-weight: 500;">{{ role.name }}</span>
                  </div>
                  <div v-if="role.description" style="color: #6b7280; font-size: 0.875rem; margin-top: 0.25rem;">
                    {{ role.description }}
                  </div>
                </div>
              </div>
            </div>

            <div v-else style="text-align: center; padding: 2rem; color: #6b7280;">
              <AppIcon name="shield" style="font-size: 2rem; margin-bottom: 0.5rem;" />
              <p>Aucun rôle assigné</p>
            </div>
          </div>

          <!-- Permissions -->
          <div
            style="background: white; border-radius: 12px; padding: 1.5rem; margin-bottom: 1.5rem; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);">
            <h3 style="margin: 0 0 1rem 0; font-size: 1.25rem; font-weight: 600; color: #1f2937;">
              <AppIcon name="key" />
              Permissions
              <span style="color: #6b7280; font-size: 0.875rem;">({{ user.permissions_count || 0 }})</span>
            </h3>

            <!-- Permissions avec accordéons -->
            <div v-if="user.all_permissions && user.all_permissions.length > 0">
              <PermissionsAccordion :permissions="user.all_permissions" mode="readonly" :show-actions="true"
                :default-open-categories="['users']" />

              <!-- Résumé global -->
              <div
                style="margin-top: 1.5rem; padding: 1rem; background: #f0fdf4; border-radius: 8px; border: 1px solid #bbf7d0;">
                <div style="display: flex; align-items: center; gap: 1rem;">
                  <div>
                    <AppIcon name="check" style="color: var(--success);" />
                    <strong class="pl-2">Permissions : {{ user.permissions_count }}</strong>
                  </div>
                  <div>
                    <AppIcon name="layers" style="color: #0891b2;" />
                    <strong class="px-2">Catégories : {{ Object.keys(groupedPermissions).length }}</strong>
                  </div>
                  <div>
                    <AppIcon name="settings-2" style="color: var(--terracotta);" />
                    <strong class="px-2">Rôles : {{ (user.role ? 1 : 0) + (user.additional_roles?.length || 0)
                      }}</strong>
                  </div>
                </div>
              </div>
            </div>

            <!-- Aucune permission -->
            <div v-else style="text-align: center; padding: 2rem; color: #6b7280;">
              <AppIcon name="key" style="font-size: 2rem; margin-bottom: 0.5rem;" />
              <p>Aucune permission assignée</p>
              <small>Les permissions sont héritées des rôles assignés</small>
            </div>
          </div>
        </div>
        <!-- Colonne droite - Informations -->
        <div>
          <!-- Informations générales -->
          <div
            style="background: white; border-radius: 12px; padding: 1.5rem; margin-bottom: 1.5rem; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);">
            <h3 style="margin: 0 0 1rem 0; font-size: 1.25rem; font-weight: 600; color: #1f2937;">
              <AppIcon name="info" />
              Informations générales
            </h3>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
              <div style="display: flex; flex-direction: column; gap: 0.25rem;">
                <label style="font-size: 0.875rem; font-weight: 600; color: #6b7280;">ID utilisateur</label>
                <span style="color: #1f2937;">#{{ user.id }}</span>
              </div>

              <div style="display: flex; flex-direction: column; gap: 0.25rem;">
                <label style="font-size: 0.875rem; font-weight: 600; color: #6b7280;">Statut</label>
                <span class="status-badge" :class="getStatusClass(user.status)">
                  {{ getStatusLabel(user.status) }}
                </span>
              </div>

              <div style="display: flex; flex-direction: column; gap: 0.25rem;">
                <label style="font-size: 0.875rem; font-weight: 600; color: #6b7280;">Date d'inscription</label>
                <span style="color: #1f2937;">{{ formatDateTime(user.created_at) }}</span>
              </div>

              <div style="display: flex; flex-direction: column; gap: 0.25rem;">
                <label style="font-size: 0.875rem; font-weight: 600; color: #6b7280;">Dernière mise à jour</label>
                <span style="color: #1f2937;">{{ formatDateTime(user.updated_at) }}</span>
              </div>

              <div v-if="user.last_login_at" style="display: flex; flex-direction: column; gap: 0.25rem;">
                <label style="font-size: 0.875rem; font-weight: 600; color: #6b7280;">Dernière connexion</label>
                <span style="color: #1f2937;">{{ formatDateTime(user.last_login_at) }}</span>
              </div>

              <div v-if="user.last_login_ip" style="display: flex; flex-direction: column; gap: 0.25rem;">
                <label style="font-size: 0.875rem; font-weight: 600; color: #6b7280;">Dernière IP</label>
                <span style="color: #1f2937; font-family: monospace;">{{ user.last_login_ip }}</span>
              </div>
            </div>
          </div>

          <!-- Sécurité -->
          <div
            style="background: white; border-radius: 12px; padding: 1.5rem; margin-bottom: 1.5rem; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);">
            <h3 style="margin: 0 0 1rem 0; font-size: 1.25rem; font-weight: 600; color: #1f2937;">
              <AppIcon name="lock" />
              Sécurité
            </h3>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
              <div
                style="display: flex; align-items: center; gap: 0.75rem; padding: 1rem; background: #f9fafb; border-radius: 8px;">
                <div
                  style="width: 40px; height: 40px; border-radius: 50%; background: #f3f4f6; display: flex; align-items: center; justify-content: center;">
                  <AppIcon :name="user.email_verified_at ? 'circle-check' : 'triangle-alert'" :class="user.email_verified_at ? 'text-success' : 'text-warning'" />
                </div>
                <div>
                  <span style="display: block; font-size: 1rem; font-weight: 600; color: #1f2937;">Vérification
                    email</span>
                  <span style="font-size: 0.875rem; color: #6b7280;">
                    {{ user.email_verified_at ? 'Vérifié le ' + formatDate(user.email_verified_at) : 'Non vérifié' }}
                  </span>
                </div>
              </div>

              <div
                style="display: flex; align-items: center; gap: 0.75rem; padding: 1rem; background: #f9fafb; border-radius: 8px;">
                <div
                  style="width: 40px; height: 40px; border-radius: 50%; background: #f3f4f6; display: flex; align-items: center; justify-content: center;">
                  <AppIcon :name="user.password_reset_required ? 'circle-alert' : 'key'" :class="user.password_reset_required ? 'text-warning' : 'text-success'" />
                </div>
                <div>
                  <span style="display: block; font-size: 1rem; font-weight: 600; color: #1f2937;">Mot de passe</span>
                  <span style="font-size: 0.875rem; color: #6b7280;">
                    {{ user.password_reset_required ? 'Réinitialisation requise' : 'OK' }}
                  </span>
                </div>
              </div>
            </div>

            <div style="display: flex; gap: 0.5rem;">
              <button @click="resetPassword" class="btn btn-outline btn-sm">
                <AppIcon name="key" />
                Forcer réinitialisation
              </button>

              <button v-if="!user.email_verified_at" @click="sendVerificationEmail" class="btn btn-outline btn-sm">
                <AppIcon name="mail" />
                Renvoyer vérification
              </button>
            </div>
          </div>

          <!-- Actions rapides -->
          <div
            style="background: white; border-radius: 12px; padding: 1.5rem; margin-bottom: 1.5rem; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);">
            <h3 style="margin: 0 0 1rem 0; font-size: 1.25rem; font-weight: 600; color: #1f2937;">
              <AppIcon name="zap" />
              Actions rapides
            </h3>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.5rem;">
              <router-link :to="`/admin/users/${user.id}/edit`" class="btn btn-primary btn-sm">
                <AppIcon name="pencil" />
                <span>Modifier</span>
              </router-link>

              <button @click="duplicateUser" class="btn btn-secondary btn-sm">
                <AppIcon name="copy" />
                <span>Dupliquer</span>
              </button>

              <button @click="exportUserData" class="btn btn-secondary btn-sm">
                <AppIcon name="download" />
                <span>Exporter</span>
              </button>

              <button @click="viewUserActivity" class="btn btn-secondary btn-sm">
                <AppIcon name="history" />
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
import UsersApi from '@/services/UsersApi'
import PermissionsAccordion from '@/admin/components/ui/PermissionsAccordion.vue'
import LoadingState from '@/admin/components/ui/LoadingState.vue'

export default {
  name: 'UserDetail',
  components: {
    PermissionsAccordion,
    LoadingState
  },
  data() {
    return {
      user: null,
      loading: true,
      error: null,
    }
  },
  computed: {
    userId() {
      return this.$route.params.id
    },
    // Grouper les permissions par catégorie
    groupedPermissions() {
      if (!this.user.all_permissions) return {}

      return this.user.all_permissions.reduce((groups, permission) => {
        const category = permission.category || 'general'
        if (!groups[category]) {
          groups[category] = []
        }
        groups[category].push(permission)
        return groups
      }, {})
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
        this.user = await UsersApi.getById(this.userId)
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
        await UsersApi.toggleStatus(this.user.id, newStatus)
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
        await UsersApi.resetPassword(this.user.id)
        this.user.password_reset_required = true
        alert('L\'utilisateur devra changer son mot de passe à la prochaine connexion')
      } catch (error) {
        console.error('Erreur lors de la réinitialisation:', error)
        alert('Impossible de forcer la réinitialisation du mot de passe')
      }
    }
    ,

    async sendVerificationEmail() {
      try {
        await UsersApi.sendVerificationEmail(this.user.id)
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
        const response = await UsersApi.duplicate(this.user.id)
        this.$router.push(`/admin/users/${response.user.id}/edit`)
      } catch (error) {
        console.error('Erreur lors de la duplication:', error)
        alert('Impossible de dupliquer l\'utilisateur')
      }
    },

    async exportUserData() {
      try {
        const response = await axios.get(`/admin/users/${this.user.id}/export`)
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
      this.$router.push(`/admin/users/${this.user.id}`)
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
    },
  }
}
</script>