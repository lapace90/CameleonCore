<!-- frontend/CampCameleonXfront/src/admin/components/modals/PermissionDetailsModal.vue -->
<template>
  <div class="modal-overlay" @click.self="$emit('close')">
    <div class="modal-container" style="max-width: 700px;">
      <div class="modal-header">
        <h3>
          <i class="fas fa-eye"></i>
          Détails de la permission
        </h3>
        <button @click="$emit('close')" class="btn-close">
          <i class="fas fa-times"></i>
        </button>
      </div>

      <div class="modal-body">
        <!-- En-tête permission -->
        <div class="permission-overview">
          <div class="permission-title">
            <h4>{{ permission.name }}</h4>
            <div class="permission-badges">
              <span :class="getBadgeClass(permission.badge_class)">
                {{ permission.action_label }}
              </span>
              <span v-if="permission.category" class="badge badge-secondary">
                <i class="fas fa-tag"></i>
                {{ getCategoryDisplayName(permission.category) }}
              </span>
            </div>
          </div>

          <!-- Indicateurs de statut -->
          <div class="permission-indicators">
            <span v-if="permission.is_critical" class="indicator critical" title="Permission critique">
              <i class="fas fa-exclamation-triangle"></i>
              Critique
            </span>
            <span v-if="permission.is_system" class="indicator system" title="Permission système">
              <i class="fas fa-shield-alt"></i>
              Système
            </span>
            <span v-if="permission.requires_confirmation" class="indicator confirmation" title="Confirmation requise">
              <i class="fas fa-lock"></i>
              Confirmation requise
            </span>
          </div>
        </div>

        <!-- Code d'action -->
        <div class="detail-section">
          <h5><i class="fas fa-code"></i> Code d'action</h5>
          <div class="code-display">
            <code>{{ permission.action }}</code>
            <button @click="copyToClipboard(permission.action)" class="copy-btn" title="Copier">
              <i class="fas fa-copy"></i>
            </button>
          </div>
        </div>

        <!-- Statistiques d'usage -->
        <div class="detail-section">
          <h5><i class="fas fa-chart-bar"></i> Statistiques d'usage</h5>
          <div class="usage-stats">
            <div class="stat-group">
              <div class="stat-item">
                <div class="stat-number">{{ permission.roles_count }}</div>
                <div class="stat-label">Rôle(s)</div>
              </div>
              <div class="stat-item">
                <div class="stat-number">{{ permission.users_count }}</div>
                <div class="stat-label">Utilisateur(s)</div>
              </div>
              <div class="stat-item">
                <div :class="getUsageClass(permission.usage_status)" class="usage-badge">
                  {{ getUsageLabel(permission.usage_status) }}
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Rôles ayant cette permission -->
        <div v-if="permission.roles && permission.roles.length > 0" class="detail-section">
          <h5><i class="fas fa-users-cog"></i> Rôles ayant cette permission</h5>
          <div class="roles-list">
            <div v-for="role in permission.roles" :key="role.id" class="role-item">
              <div class="role-info">
                <strong>{{ role.name }}</strong>
                <span class="role-slug">{{ role.slug }}</span>
              </div>
              <span class="role-badge">Actif</span>
            </div>
          </div>
        </div>

        <!-- Message si aucun rôle -->
        <div v-else class="detail-section">
          <h5><i class="fas fa-users-cog"></i> Rôles ayant cette permission</h5>
          <div class="empty-state">
            <i class="fas fa-info-circle"></i>
            <p>Cette permission n'est assignée à aucun rôle.</p>
          </div>
        </div>

        <!-- Informations techniques -->
        <div class="detail-section">
          <h5><i class="fas fa-info-circle"></i> Informations techniques</h5>
          <div class="tech-info">
            <div class="info-row">
              <span class="info-label">ID :</span>
              <span class="info-value">{{ permission.id }}</span>
            </div>
            <div class="info-row">
              <span class="info-label">Catégorie :</span>
              <span class="info-value">{{ permission.category || 'Auto-détectée' }}</span>
            </div>
            <div class="info-row">
              <span class="info-label">Type :</span>
              <span class="info-value">{{ getActionType(permission.action) }}</span>
            </div>
            <div class="info-row">
              <span class="info-label">Suppression :</span>
              <span class="info-value" :class="permission.roles_count > 0 ? 'text-danger' : 'text-success'">
                {{ permission.roles_count > 0 ? 'Bloquée (en cours d\'usage)' : 'Autorisée' }}
              </span>
            </div>
          </div>
        </div>
      </div>

      <div class="modal-footer">
        <button @click="$emit('close')" class="btn btn-secondary btn-sm">
          Fermer
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  permission: {
    type: Object,
    required: true
  }
})

const emit = defineEmits(['close'])

// Helpers repris du composant parent
const getBadgeClass = (badgeClass) => {
  const mapping = {
    'badge-danger': 'status-inactive',
    'badge-success': 'status-active',
    'badge-warning': 'status-draft',
    'badge-info': 'category-badge',
    'badge-secondary': 'category-badge'
  }
  return mapping[badgeClass] || 'category-badge'
}

const getUsageLabel = (status) => {
  const labels = {
    'unused': 'Non utilisée',
    'limited': 'Usage limité',
    'moderate': 'Usage modéré',
    'widespread': 'Très utilisée'
  }
  return labels[status] || status
}

const getUsageClass = (status) => {
  const classes = {
    'unused': 'status-inactive',
    'limited': 'status-draft',
    'moderate': 'status-active',
    'widespread': 'status-active'
  }
  return classes[status] || 'status-inactive'
}

const getCategoryDisplayName = (key) => {
  const names = {
    'system': 'Administration Système',
    'users': 'Gestion Utilisateurs',
    'accommodations': 'Hébergements',
    'activities': 'Activités',
    'bookings': 'Réservations',
    'reception': 'Réception',
    'customers': 'Clients',
    'restaurant': 'Restaurant',
    'finance': 'Finance',
    'analytics': 'Analyses',
    'communication': 'Communication',
    'other': 'Autres'
  }
  return names[key] || key
}

const getActionType = (action) => {
  if (action.includes('create') || action.includes('add')) return 'Création'
  if (action.includes('read') || action.includes('view')) return 'Lecture'
  if (action.includes('update') || action.includes('edit')) return 'Modification'
  if (action.includes('delete') || action.includes('remove')) return 'Suppression'
  if (action.includes('manage') || action.includes('admin')) return 'Administration'
  return 'Autre'
}

const copyToClipboard = async (text) => {
  try {
    await navigator.clipboard.writeText(text)
    // Vous pourriez ajouter un toast de confirmation ici
    console.log('Code copié dans le presse-papiers')
  } catch (err) {
    console.error('Erreur lors de la copie:', err)
  }
}
</script>

<style scoped>
.permission-overview {
  background: var(--bg-light);
  padding: 1.5rem;
  border-radius: 8px;
  margin-bottom: 2rem;
}

.permission-title {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 1rem;
}

.permission-title h4 {
  margin: 0;
  font-size: 1.25rem;
  color: var(--text-primary);
}

.permission-badges {
  display: flex;
  gap: 0.5rem;
  flex-wrap: wrap;
}

.permission-indicators {
  display: flex;
  gap: 1rem;
  flex-wrap: wrap;
}

.indicator {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem 1rem;
  border-radius: 20px;
  font-size: 0.875rem;
  font-weight: 500;
}

.indicator.critical {
  background: color-mix(in srgb, var(--danger) 15%, var(--bg-white));
  color: var(--danger);
}

.indicator.system {
  background: color-mix(in srgb, var(--warning) 15%, var(--bg-white));
  color: var(--warning);
}

.indicator.confirmation {
  background: color-mix(in srgb, var(--info) 15%, var(--bg-white));
  color: var(--info);
}

.detail-section {
  margin-bottom: 2rem;
  padding-bottom: 1.5rem;
  border-bottom: 1px solid var(--bg-secondary);
}

.detail-section:last-child {
  border-bottom: none;
  margin-bottom: 0;
}

.detail-section h5 {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  margin: 0 0 1rem 0;
  font-size: 1rem;
  color: var(--text-primary);
  font-weight: 600;
}

.code-display {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  background: var(--bg-white);
  padding: 1rem;
  border-radius: 6px;
  border: 1px solid var(--bg-secondary);
}

.code-display code {
  flex: 1;
  font-family: 'Monaco', 'Consolas', monospace;
  font-size: 0.875rem;
  color: var(--primary);
  background: none;
  padding: 0;
}

.copy-btn {
  background: var(--bg-light);
  border: 1px solid var(--bg-secondary);
  border-radius: 4px;
  padding: 0.5rem;
  cursor: pointer;
  color: var(--text-muted);
  transition: all 0.2s ease;
}

.copy-btn:hover {
  background: var(--bg-secondary);
  color: var(--text-primary);
}

.usage-stats {
  background: var(--bg-white);
  padding: 1.5rem;
  border-radius: 6px;
  border: 1px solid var(--bg-secondary);
}

.stat-group {
  display: flex;
  gap: 2rem;
  align-items: center;
}

.stat-item {
  text-align: center;
}

.stat-number {
  font-size: 1.75rem;
  font-weight: 700;
  color: var(--primary);
  line-height: 1;
}

.usage-badge {
  padding: 0.5rem 1rem;
  border-radius: 20px;
  font-size: 0.875rem;
  font-weight: 500;
}

.roles-list {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.role-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1rem;
  background: var(--bg-white);
  border-radius: 6px;
  border: 1px solid var(--bg-secondary);
}

.role-info strong {
  display: block;
  color: var(--text-primary);
  margin-bottom: 0.25rem;
}

.role-slug {
  color: var(--text-muted);
  font-size: 0.875rem;
  font-family: monospace;
}

.role-badge {
  padding: 0.25rem 0.75rem;
  background: color-mix(in srgb, var(--success) 15%, var(--bg-white));
  color: var(--success);
  border-radius: 12px;
  font-size: 0.75rem;
  font-weight: 500;
}

.empty-state {
  text-align: center;
  padding: 2rem;
  color: var(--text-secondary);
}

.empty-state i {
  font-size: 2rem;
  margin-bottom: 1rem;
  color: var(--text-muted);
}

.tech-info {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.info-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.75rem;
  background: var(--bg-white);
  border-radius: 4px;
  border: 1px solid var(--bg-secondary);
}

.info-label {
  font-weight: 500;
  color: var(--text-secondary);
}

.info-value {
  color: var(--text-primary);
}
</style>