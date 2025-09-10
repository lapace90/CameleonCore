<template>
  <div v-if="show" class="modal-overlay" @click.self="$emit('close')">
    <div class="modal-content" style="border-radius: 16px;" @click.stop>
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
        <!-- ⚠️ même wrapper que la modal des rôles -->
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
                    {{ getStatusLabel(user.status) }}
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
          <!-- Statistiques -->
          <div class="detail-section">
              <div class="stat-card ">
                <i class="fas fa-shield"></i>
                <div>
                  <span class="stat-label">Rôles ({{ rolesCount }})</span>
                  <div class="roles-list">
                    <div v-if="rolesCount > 0" class="detail-section"></div>
                    <span v-for="r in allRoles" :key="r.id || r.slug || r.name" class="role-badge role-secondary">
                      {{ r.name }}
                    </span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
  </div>
</template>

<script>
export default {
  name: 'UserDetailsModal',
  props: {
    show: { type: Boolean, default: false },
    user: { type: Object, required: true },
    // optionnel : te permet d’accentuer la couleur de l’icône comme pour les rôles
    headerColor: { type: String, default: null }
  },
  emits: ['close'],
  computed: {
    // Toutes les sources possibles combinées, sans doublons
    allRoles() {
      const items = []
      const pushIf = (r) => { if (r && r.name) items.push(r) }

      // alias possibles selon tes payloads
      pushIf(this.user.role)
      pushIf(this.user.primary_role)

      if (Array.isArray(this.user.additional_roles)) {
        this.user.additional_roles.forEach(pushIf)
      }
      if (Array.isArray(this.user.roles)) {
        this.user.roles.forEach(pushIf)
      }

      // dédoublonnage par id/slug/nom
      const seen = new Set()
      return items.filter(r => {
        const key = r.id ?? r.slug ?? r.name
        if (seen.has(key)) return false
        seen.add(key)
        return true
      })
    },
    rolesCount() {
      // Priorité au tableau agrégé
      const c = this.allRoles.length
      // sinon fallback sur un éventuel compteur serveur
      return c || Number(this.user.roles_count || 0)
    },
    primaryRole() {
      return this.user.role || this.user.primary_role || null
    },
    isActive() {
      const s = String(this.user.status ?? '').toLowerCase()
      return ['active', 'enabled', '1', 'true'].includes(s)
    },
    isInactive() {
      const s = String(this.user.status ?? '').toLowerCase()
      return ['inactive', 'disabled', '0', 'false'].includes(s)
    },
    statusBadgeClass() {
      // même logique “badge-*” que la modal des rôles
      if (this.isActive) return 'badge-success'
      if (this.isInactive) return 'badge-danger'
      const s = String(this.user.status ?? '').toLowerCase()
      if (['pending', 'invited', 'waiting'].includes(s)) return 'badge-warning'
      return 'badge-normal'
    }
  },
  methods: {
    formatDate(d) {
      if (!d) return ''
      const date = new Date(d)
      return isNaN(date) ? d : date.toLocaleString()
    },
    getStatusLabel(status) {
      const s = String(status ?? '').toLowerCase()
      if (['active', 'enabled', '1', 'true'].includes(s)) return 'Actif'
      if (['inactive', 'disabled', '0', 'false'].includes(s)) return 'Inactif'
      if (['pending', 'invited', 'waiting'].includes(s)) return 'En attente'
      return s ? s.charAt(0).toUpperCase() + s.slice(1) : 'Inconnu'
    },
    mapActionToCategory(action) {
      const a = String(action || '').toLowerCase()
      if (a.startsWith('view') || a.startsWith('read')) return 'read'
      if (a.startsWith('create') || a.startsWith('add')) return 'create'
      if (a.startsWith('update') || a.startsWith('edit') || a.startsWith('patch')) return 'update'
      if (a.startsWith('delete') || a.startsWith('remove') || a.startsWith('destroy')) return 'delete'
      return 'other'
    }
  }
}
</script>
<style scoped>
.detail-item {
  padding-bottom: 1.2rem;
}
</style>