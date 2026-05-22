<template>
  <div v-if="show" class="modal-overlay" @click.self="$emit('close')">
    <div class="modal-content" style="border-radius: 16px;" @click.stop>

      <!-- État de chargement si user null -->
      <LoadingState v-if="!user" state="loading" loading-text="Chargement de l'utilisateur..." />

      <!-- Contenu principal si user existe -->
      <template v-else>
        <div class="modal-header">
          <h3>
            <AppIcon name="user" :style="{ color: headerColor }" />
            {{ user.name }}
          </h3>
          <button @click="$emit('close')" class="btn-close">
            <AppIcon name="x" />
          </button>
        </div>

        <div class="modal-body">
          <div class="role-details-content py-3">

            <!-- Informations générales -->
            <div class="detail-section">
              <h4>Informations générales</h4>
              <div class="detail-grid py-3">
                <div class="detail-item">
                  <span class="label">Nom :</span>
                  <span class="value">{{ user.name }}</span>
                </div>

                <div class="detail-item">
                  <span class="label">Email :</span>
                  <span class="value">{{ user.email }}</span>
                </div>

                <div class="detail-item">
                  <span class="label">Statut :</span>
                  <span class="value">
                    <span class="badge" :class="statusBadgeClass">
                      <AppIcon name="circle-check" v-if="isActive" />
                      <AppIcon name="ban" v-else-if="isInactive" />
                      <AppIcon name="clock" v-else />
                      {{ statusLabel }}
                    </span>
                  </span>
                </div>

                <div class="detail-item">
                  <span class="label">Inscription :</span>
                  <span class="value">{{ formatDate(user.created_at) }}</span>
                </div>

                <div class="detail-item">
                  <span class="label">Dernière connexion :</span>
                  <span class="value">
                    {{ user.last_login_at ? formatDate(user.last_login_at) : 'Jamais' }}
                  </span>
                </div>
              </div>
            </div>

            <!-- Statistiques rôles -->
            <div class="detail-section">
              <div class="stat-card">
                <AppIcon name="shield" />
                <div>
                  <span class="stat-label">Rôles ({{ totalRolesCount }})</span>
                  <div class="roles-list">
                    <!-- Rôle principal -->
                    <span v-if="user.role" class="role-badge role-primary">
                      👑 {{ user.role.name }}
                    </span>

                    <!-- Rôles additionnels -->
                    <span v-for="role in (user.additional_roles || [])" :key="role.id"
                      class="role-badge role-secondary">
                      {{ role.name }}
                    </span>
                  </div>
                </div>
              </div>
            </div>

          </div>
        </div>

        <!-- Footer avec actions -->
        <div class="modal-footer">
          <div class="footer-actions">
            <button @click="$emit('close')" class="btn btn-outline btn-sm">
              Fermer
            </button>

            <router-link :to="`/admin/users/${user.id}`" class="btn btn-primary btn-sm" @click="$emit('close')">
              <AppIcon name="eye" />
              Voir le détail complet
            </router-link>
          </div>
        </div>
      </template>

    </div>
  </div>
</template>

<script>
import LoadingState from '@/admin/components/ui/LoadingState.vue'

export default {
  name: 'UserDetailsModal',
  components: { LoadingState },
  props: {
    show: { type: Boolean, default: false },
    user: { type: [Object, null], default: null },
    headerColor: { type: String, default: null }
  },
  emits: ['close'],

  computed: {
    totalRolesCount() {
      if (!this.user) return 0

      let count = 0
      if (this.user.role) count++
      if (this.user.additional_roles?.length) count += this.user.additional_roles.length

      return count
    },

    isActive() {
      if (!this.user) return false
      const status = String(this.user.status || '').toLowerCase()
      return ['active', 'enabled', '1', 'true'].includes(status)
    },

    isInactive() {
      if (!this.user) return false
      const status = String(this.user.status || '').toLowerCase()
      return ['inactive', 'disabled', '0', 'false'].includes(status)
    },

    statusLabel() {
      if (!this.user) return 'Inconnu'
      const status = String(this.user.status || '').toLowerCase()

      if (this.isActive) return 'Actif'
      if (this.isInactive) return 'Inactif'
      if (['pending', 'invited', 'waiting'].includes(status)) return 'En attente'

      return status ? status.charAt(0).toUpperCase() + status.slice(1) : 'Inconnu'
    },

    statusBadgeClass() {
      if (this.isActive) return 'badge-success'
      if (this.isInactive) return 'badge-danger'
      if (['pending', 'invited', 'waiting'].includes(String(this.user?.status || '').toLowerCase())) return 'badge-warning'
      return 'badge-normal'
    }
  },

  methods: {
    formatDate(dateString) {
      if (!dateString) return ''
      const date = new Date(dateString)
      return isNaN(date) ? dateString : date.toLocaleString('fr-FR')
    }
  }
}
</script>

<style scoped>
.detail-item {
  padding-bottom: 1rem;
}

.stat-card {
  display: flex;
  align-items: flex-start;
  gap: 1rem;
  padding: 1rem;
  background: #f8f9fa;
  border-radius: 8px;
}

.stat-card .app-icon {
  font-size: 1.2rem;
  color: #6c757d;
  margin-top: 0.2rem;
}

.stat-label {
  font-weight: 600;
  color: #495057;
  margin-bottom: 0.5rem;
}

.footer-actions {
  display: flex;
  gap: 0.5rem;
}

.modal-footer {
  border-top: 1px solid #e9ecef;
  padding: 1rem 1.5rem;
  justify-content: center;
}
</style>