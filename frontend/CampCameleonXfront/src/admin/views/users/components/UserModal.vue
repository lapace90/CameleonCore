<template>
  <div v-if="show" class="modal-overlay" @click.self="$emit('close')">
    <div class="modal-content" style="border-radius: 16px;" @click.stop>

      <!-- État de chargement si user null -->
      <div v-if="!user" class="modal-loading">
        <div class="spinner"></div>
        <p>Chargement...</p>
      </div>

      <!-- Contenu principal si user existe -->
      <template v-else>
        <div class="modal-header">
          <h3>
            <i class="fas fa-user" :style="{ color: headerColor }"></i>
            {{ user.name }}
          </h3>
          <button @click="$emit('close')" class="btn-close">
            <i class="fas fa-times"></i>
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
                      <i v-if="isActive" class="fas fa-check-circle"></i>
                      <i v-else-if="isInactive" class="fas fa-ban"></i>
                      <i v-else class="fas fa-clock"></i>
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
                <i class="fas fa-shield"></i>
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
              <i class="fas fa-eye"></i>
              Voir le détail complet
            </router-link>
          </div>
        </div>
      </template>

    </div>
  </div>
</template>

<script>
export default {
  name: 'UserDetailsModal',
  props: {
    show: { type: Boolean, default: false },
    user: { type: [Object, null], default: null },
    headerColor: { type: String, default: null }
  },
  emits: ['close'],

  computed: {
    totalRolesCount() {
      if (!this.user) return 0;

      let count = 0;
      if (this.user.role) count++;
      if (this.user.additional_roles?.length) count += this.user.additional_roles.length;

      return count;
    },

    isActive() {
      if (!this.user) return false;
      const status = String(this.user.status || '').toLowerCase();
      return ['active', 'enabled', '1', 'true'].includes(status);
    },

    isInactive() {
      if (!this.user) return false;
      const status = String(this.user.status || '').toLowerCase();
      return ['inactive', 'disabled', '0', 'false'].includes(status);
    },

    statusLabel() {
      if (!this.user) return 'Inconnu';
      const status = String(this.user.status || '').toLowerCase();

      if (this.isActive) return 'Actif';
      if (this.isInactive) return 'Inactif';
      if (['pending', 'invited', 'waiting'].includes(status)) return 'En attente';

      return status ? status.charAt(0).toUpperCase() + status.slice(1) : 'Inconnu';
    },

    statusBadgeClass() {
      if (this.isActive) return 'badge-success';
      if (this.isInactive) return 'badge-danger';
      if (['pending', 'invited', 'waiting'].includes(String(this.user?.status || '').toLowerCase())) return 'badge-warning';
      return 'badge-normal';
    }
  },

  methods: {
    formatDate(dateString) {
      if (!dateString) return '';
      const date = new Date(dateString);
      return isNaN(date) ? dateString : date.toLocaleString('fr-FR');
    }
  }
}
</script>

<style scoped>
.modal-loading {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 3rem;
  color: #6c757d;
}

.spinner {
  width: 2rem;
  height: 2rem;
  border: 3px solid #e9ecef;
  border-top: 3px solid #007bff;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin-bottom: 1rem;
}

@keyframes spin {
  0% {
    transform: rotate(0deg);
  }

  100% {
    transform: rotate(360deg);
  }
}

.detail-item {
  padding-bottom: 1.2rem;
}

.stat-card {
  display: flex;
  align-items: flex-start;
  gap: 1rem;
  padding: 1rem;
  background: #f8f9fa;
  border-radius: 8px;
}

.stat-card i {
  font-size: 1.2rem;
  color: #6c757d;
  margin-top: 0.2rem;
}

.stat-label {
  font-weight: 600;
  color: #495057;
  display: block;
  margin-bottom: 0.5rem;
}

.roles-list {
  display: flex;
  flex-wrap: wrap;
  gap: 0.25rem;
}

.role-badge {
  display: inline-block;
  padding: 3px 8px;
  border-radius: 12px;
  font-size: 12px;
  font-weight: 500;
}

.role-badge.role-primary {
  background: linear-gradient(135deg, #ffd700, #ffed4e);
  color: #8b5a00;
  border: 1px solid #e6c200;
}

.role-badge.role-secondary {
  background: #e9ecef;
  color: #495057;
  border: 1px solid #ced4da;
}

.footer-actions {
  display: flex;
  gap: 0.5rem;
  justify-content: flex-end;
}
</style>