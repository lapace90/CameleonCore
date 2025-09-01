<template>
  <div v-if="show" class="modal-overlay" @click="$emit('close')">
    <div class="modal-content" @click.stop>
      <div class="modal-header">
        <h3>
          <i class="fas fa-user"></i>
          {{ user.name }}
        </h3>
        <button @click="$emit('close')" class="btn-close">&times;</button>
      </div>
      
      <div class="modal-body">
        <div class="user-details-modal">
          <div class="detail-section">
            <h4>Informations générales</h4>
            <div class="info-grid">
              <div class="info-item">
                <strong>Email:</strong> {{ user.email }}
              </div>
              <div class="info-item">
                <strong>Statut:</strong> 
                <span class="status-indicator" :class="getStatusClass(user.status)">
                  {{ getStatusLabel(user.status) }}
                </span>
              </div>
              <div class="info-item">
                <strong>Inscription:</strong> {{ formatDate(user.created_at) }}
              </div>
              <div class="info-item">
                <strong>Dernière connexion:</strong> 
                {{ user.last_login_at ? formatDate(user.last_login_at) : 'Jamais' }}
              </div>
            </div>
          </div>
          
          <div class="detail-section">
            <h4>Rôles et permissions</h4>
            <div class="roles-permissions-detail">
              <div v-if="user.role" class="role-detail">
                <h5>Rôle principal</h5>
                <div class="role-badge role-primary">{{ user.role.name }}</div>
              </div>
              
              <div v-if="user.additional_roles && user.additional_roles.length > 0" class="role-detail">
                <h5>Rôles additionnels</h5>
                <div class="roles-list">
                  <span 
                    v-for="role in user.additional_roles" 
                    :key="role.id"
                    class="role-badge role-secondary"
                  >
                    {{ role.name }}
                  </span>
                </div>
              </div>
              
              <div v-if="user.permissions && user.permissions.length > 0" class="permissions-detail">
                <h5>Permissions directes</h5>
                <div class="permissions-list">
                  <span 
                    v-for="permission in user.permissions" 
                    :key="permission.id"
                    class="permission-badge"
                    :class="getActionClass(permission.action)"
                  >
                    {{ permission.name }}
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
  name: 'UserModal',
  props: {
    show: {
      type: Boolean,
      default: false
    },
    user: {
      type: Object,
      default: () => ({})
    }
  },
  emits: ['close'],
  methods: {
    formatDate(dateString) {
      if (!dateString) return ''
      const date = new Date(dateString)
      return date.toLocaleDateString('fr-FR', {
        day: '2-digit',
        month: '2-digit', 
        year: 'numeric'
      })
    },

    getStatusClass(status) {
      const classes = {
        'active': 'status-active',
        'inactive': 'status-inactive', 
        'blocked': 'status-blocked'
      }
      return classes[status] || 'status-unknown'
    },

    getStatusLabel(status) {
      const labels = {
        'active': 'Actif',
        'inactive': 'Suspendu',
        'blocked': 'Bloqué'
      }
      return labels[status] || status
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