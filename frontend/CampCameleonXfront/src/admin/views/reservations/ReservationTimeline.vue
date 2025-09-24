<template>
  <div class="reservation-timeline">
    <div v-if="!hasItems" class="empty-state">
      <i class="fas fa-envelope-open-text"></i>
      <p>Aucune communication enregistrée pour le moment.</p>
    </div>
    <ul v-else class="timeline-list">
      <li v-for="(item, index) in normalizedItems" :key="index" class="timeline-item">
        <div class="timeline-marker" :class="`timeline-marker--${item.type}`">
          <i :class="item.icon"></i>
        </div>
        <div class="timeline-content">
          <div class="timeline-header">
            <div class="timeline-title">{{ item.title }}</div>
            <div class="timeline-date">{{ item.formattedDate }}</div>
          </div>
          <p v-if="item.message" class="timeline-message">{{ item.message }}</p>
          <ul v-if="item.meta && item.meta.length" class="timeline-meta">
            <li v-for="meta in item.meta" :key="meta.label">
              <span class="meta-label">{{ meta.label }}</span>
              <span class="meta-value">{{ meta.value }}</span>
            </li>
          </ul>
        </div>
      </li>
    </ul>
  </div>
</template>

<script>
const ICONS_BY_TYPE = {
  email: 'fas fa-envelope',
  call: 'fas fa-phone-alt',
  sms: 'fas fa-sms',
  note: 'fas fa-sticky-note',
  notification: 'fas fa-bell',
  default: 'fas fa-comment-dots'
}

export default {
  name: 'ReservationTimeline',
  props: {
    items: {
      type: Array,
      default: () => []
    }
  },
  computed: {
    hasItems() {
      return Array.isArray(this.items) && this.items.length > 0
    },
    normalizedItems() {
      if (!this.hasItems) {
        return []
      }

      return this.items.map((entry) => {
        const type = (entry.type || entry.channel || 'default').toString().toLowerCase()
        const title = entry.title || this.getFallbackTitle(type)
        const date = entry.date || entry.created_at || entry.createdAt || entry.sent_at || entry.sentAt
        const message = entry.message || entry.body || entry.note || ''
        const user = this.normalizeUser(entry)
        const recipient = entry.recipient || entry.to || entry.target || ''
        const status = entry.status || entry.state || ''
        const icon = ICONS_BY_TYPE[type] || ICONS_BY_TYPE.default

        const meta = []
        if (user) {
          meta.push({ label: 'Par', value: user })
        }
        if (recipient) {
          meta.push({ label: 'Destinataire', value: recipient })
        }
        if (status) {
          meta.push({ label: 'Statut', value: this.formatStatus(status) })
        }
        if (entry.meta && typeof entry.meta === 'object' && !Array.isArray(entry.meta)) {
          Object.entries(entry.meta).forEach(([label, value]) => {
            if (value !== null && value !== undefined && value !== '') {
              meta.push({ label, value })
            }
          })
        }

        return {
          title,
          message,
          type,
          icon,
          formattedDate: this.formatDate(date),
          meta
        }
      })
    }
  },
  methods: {
    getFallbackTitle(type) {
      switch (type) {
        case 'email':
          return 'Email envoyé'
        case 'call':
          return 'Appel téléphonique'
        case 'sms':
          return 'SMS envoyé'
        case 'note':
          return 'Note interne'
        default:
          return 'Communication'
      }
    },
    normalizeUser(entry) {
      if (entry.user) {
        if (typeof entry.user === 'string') {
          return entry.user
        }
        if (entry.user.name) {
          return entry.user.name
        }
        if (entry.user.full_name) {
          return entry.user.full_name
        }
      }

      return entry.author || entry.created_by || entry.createdBy || ''
    },
    formatDate(value) {
      if (!value) {
        return 'Date inconnue'
      }

      const date = new Date(value)
      if (Number.isNaN(date.getTime())) {
        return value
      }

      return new Intl.DateTimeFormat('fr-FR', {
        dateStyle: 'medium',
        timeStyle: 'short'
      }).format(date)
    },
    formatStatus(value) {
      const normalized = value.toString().toLowerCase()
      if (normalized === 'sent') return 'Envoyé'
      if (normalized === 'delivered') return 'Livré'
      if (normalized === 'failed') return 'Échec'
      if (normalized === 'pending') return 'En attente'
      return value
    }
  }
}
</script>

<style scoped>
.reservation-timeline {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.timeline-list {
  list-style: none;
  margin: 0;
  padding: 0;
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.timeline-item {
  display: grid;
  grid-template-columns: auto 1fr;
  gap: 1rem;
  position: relative;
}

.timeline-marker {
  width: 2.25rem;
  height: 2.25rem;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--bg-white);
  font-size: 0.95rem;
  box-shadow: var(--shadow);
}

.timeline-marker::after {
  content: '';
  position: absolute;
  top: 2.5rem;
  left: 1.05rem;
  width: 2px;
  height: calc(100% - 2.5rem);
  background: rgba(101, 108, 151, 0.25);
}

.timeline-item:last-child .timeline-marker::after {
  display: none;
}

.timeline-marker--email {
  background: linear-gradient(135deg, #5e72e4, #825ee4);
}

.timeline-marker--call {
  background: linear-gradient(135deg, #2dce89, #2d8f63);
}

.timeline-marker--sms {
  background: linear-gradient(135deg, #11cdef, #5e72e4);
}

.timeline-marker--note {
  background: linear-gradient(135deg, #f6ad55, #ed8936);
}

.timeline-marker--notification {
  background: linear-gradient(135deg, #fb6340, #f56036);
}

.timeline-marker--default {
  background: linear-gradient(135deg, #a0aec0, #718096);
}

.timeline-content {
  background: #fdf9f5;
  border-radius: 0.85rem;
  border: 1px solid rgba(65, 36, 28, 0.08);
  padding: 1rem 1.25rem;
  box-shadow: var(--shadow-sm);
}

.timeline-header {
  display: flex;
  justify-content: space-between;
  gap: 1rem;
  margin-bottom: 0.5rem;
  align-items: baseline;
}

.timeline-title {
  font-weight: 600;
  color: var(--text-primary);
}

.timeline-date {
  font-size: 0.85rem;
  color: var(--text-muted);
}

.timeline-message {
  margin: 0;
  color: var(--text-secondary);
  line-height: 1.5;
}

.timeline-meta {
  margin: 0.75rem 0 0;
  padding: 0;
  list-style: none;
  display: flex;
  flex-wrap: wrap;
  gap: 0.75rem 1.5rem;
}

.timeline-meta li {
  display: flex;
  gap: 0.35rem;
  font-size: 0.85rem;
}

.meta-label {
  font-weight: 600;
  color: var(--text-secondary);
}

.meta-value {
  color: var(--text-primary);
}

.empty-state {
  text-align: center;
  color: var(--text-muted);
}

.empty-state i {
  font-size: 2rem;
  margin-bottom: 0.5rem;
  color: var(--primary);
}

@media (max-width: 768px) {
  .timeline-header {
    flex-direction: column;
    align-items: flex-start;
  }

  .timeline-meta {
    flex-direction: column;
    gap: 0.5rem;
  }
}
</style>