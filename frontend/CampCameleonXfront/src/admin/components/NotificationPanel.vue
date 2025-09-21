<template>
  <div class="notification-panel">
    <!-- Header -->
    <div class="panel-header">
      <h3 class="panel-title">
        <i class="fas fa-bell"></i>
        Notifications 
        <span v-if="unreadCount > 0" class="badge badge-danger">{{ unreadCount }}</span>
      </h3>
      
      <button 
        v-if="notifications.length > 0"
        @click="markAllAsRead" 
        class="btn btn-sm btn-outline-secondary"
      >
        Tout marquer lu
      </button>
    </div>

    <!-- Liste des notifications -->
    <div class="notifications-list">
      <div v-if="isLoading" class="loading-state">
        <i class="fas fa-spinner fa-spin"></i>
        Chargement...
      </div>

      <div v-else-if="notifications.length === 0" class="empty-state">
        <i class="fas fa-check-circle text-success"></i>
        <p>Aucune nouvelle notification</p>
      </div>

      <div v-else>
        <div 
          v-for="notification in notifications" 
          :key="notification.id"
          class="notification-item"
          :class="{ 'unread': !notification.read, 'read': notification.read }"
          @click="handleNotificationClick(notification)"
        >
          <!-- Icon selon le type -->
          <div class="notification-icon">
            <i :class="getNotificationIcon(notification.type)" :style="{ color: getNotificationColor(notification.type) }"></i>
          </div>

          <!-- Contenu -->
          <div class="notification-content">
            <div class="notification-title">{{ notification.title }}</div>
            <div class="notification-message">{{ notification.message }}</div>
            
            <!-- Métadonnées -->
            <div class="notification-meta">
              <span class="time">{{ formatTime(notification.created_at) }}</span>
              <span v-if="notification.data?.customer_name" class="customer">
                {{ notification.data.customer_name }}
              </span>
              <span v-if="notification.data?.amount" class="amount">
                {{ formatMoney(notification.data.amount) }}
              </span>
            </div>

            <!-- Actions -->
            <div v-if="notification.actions" class="notification-actions">
              <router-link 
                v-for="action in notification.actions" 
                :key="action.label"
                :to="action.url" 
                :class="`btn btn-sm btn-${action.type}`"
                @click.stop
              >
                {{ action.label }}
              </router-link>
            </div>
          </div>

          <!-- Badge non lu -->
          <div v-if="!notification.read" class="unread-badge"></div>
        </div>
      </div>
    </div>

    <!-- Footer -->
    <div v-if="notifications.length > 0" class="panel-footer">
      <router-link to="/admin/notifications" class="btn btn-sm btn-primary">
        Voir toutes les notifications
      </router-link>
    </div>
  </div>
</template>

<script>
import AdminApi from '@/services/AdminApi'

export default {
  name: 'NotificationPanel',
  
  props: {
    limit: {
      type: Number,
      default: 5
    },
    autoRefresh: {
      type: Boolean,
      default: true
    }
  },

  data() {
    return {
      notifications: [],
      isLoading: true,
      refreshInterval: null
    }
  },

  computed: {
    unreadCount() {
      return this.notifications.filter(n => !n.read).length
    }
  },

  async mounted() {
    await this.loadNotifications()
    
    if (this.autoRefresh) {
      // Actualisation toutes les 30 secondes
      this.refreshInterval = setInterval(() => {
        this.loadNotifications(false)
      }, 30 * 1000)
    }
  },

  beforeUnmount() {
    if (this.refreshInterval) {
      clearInterval(this.refreshInterval)
    }
  },

  methods: {
    /**
     * Charger les notifications
     */
    async loadNotifications(showLoader = true) {
      try {
        if (showLoader) this.isLoading = true

        const response = await AdminApi.getNotifications(this.limit)
        this.notifications = Array.isArray(response) ? response : []

        console.log(`✅ ${this.notifications.length} notifications chargées`)

      } catch (error) {
        console.error('❌ Erreur chargement notifications:', error)
      } finally {
        if (showLoader) this.isLoading = false
      }
    },

    /**
     * Gérer le clic sur une notification
     */
    async handleNotificationClick(notification) {
      if (!notification.read) {
        await this.markAsRead(notification.id)
      }

      // Redirection vers l'action principale si définie
      if (notification.actions && notification.actions[0]) {
        this.$router.push(notification.actions[0].url)
      }
    },

    /**
     * Marquer une notification comme lue
     */
    async markAsRead(notificationId) {
      try {
        // Marquer côté serveur
        await AdminApi.markNotificationAsRead(notificationId)
        
        // Marquer côté client
        const notification = this.notifications.find(n => n.id === notificationId)
        if (notification) {
          notification.read = true
        }

      } catch (error) {
        console.error('❌ Erreur marquage notification:', error)
      }
    },

    /**
     * Marquer toutes comme lues
     */
    async markAllAsRead() {
      try {
        const unreadIds = this.notifications
          .filter(n => !n.read)
          .map(n => n.id)

        await Promise.all(unreadIds.map(id => AdminApi.markNotificationAsRead(id)))
        
        // Marquer côté client
        this.notifications.forEach(n => n.read = true)

        this.$toast?.success('Toutes les notifications marquées comme lues')

      } catch (error) {
        console.error('❌ Erreur marquage global:', error)
      }
    },

    /**
     * Utilitaires d'affichage
     */
    getNotificationIcon(type) {
      const icons = {
        reservation_created: 'fas fa-calendar-plus',
        payment_received: 'fas fa-credit-card',
        booking_cancelled: 'fas fa-times-circle',
        quote_validated: 'fas fa-check-circle',
        system: 'fas fa-cog'
      }
      return icons[type] || 'fas fa-info-circle'
    },

    getNotificationColor(type) {
      const colors = {
        reservation_created: '#28a745',
        payment_received: '#007bff', 
        booking_cancelled: '#dc3545',
        quote_validated: '#ffc107',
        system: '#6c757d'
      }
      return colors[type] || '#6c757d'
    },

    formatTime(dateString) {
      if (!dateString) return ''
      
      const date = new Date(dateString)
      const now = new Date()
      const diffMs = now - date
      const diffMins = Math.floor(diffMs / 60000)
      
      if (diffMins < 1) return 'À l\'instant'
      if (diffMins < 60) return `Il y a ${diffMins}min`
      if (diffMins < 1440) return `Il y a ${Math.floor(diffMins / 60)}h`
      
      return date.toLocaleDateString('fr-FR')
    },

    formatMoney(amount) {
      return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'EUR'
      }).format(amount || 0)
    }
  }
}
</script>

<style scoped>
.notification-panel {
  background: white;
  border: 1px solid #e9ecef;
  border-radius: 8px;
  overflow: hidden;
}

.panel-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1rem;
  border-bottom: 1px solid #e9ecef;
  background: #f8f9fa;
}

.panel-title {
  margin: 0;
  font-size: 1.1rem;
  font-weight: 600;
}

.badge {
  font-size: 0.7rem;
  padding: 0.2rem 0.4rem;
  border-radius: 50%;
  margin-left: 0.5rem;
}

.notifications-list {
  max-height: 400px;
  overflow-y: auto;
}

.notification-item {
  display: flex;
  padding: 0.75rem 1rem;
  border-bottom: 1px solid #f1f3f4;
  cursor: pointer;
  transition: background-color 0.2s;
}

.notification-item:hover {
  background-color: #f8f9fa;
}

.notification-item.unread {
  background-color: #fff3cd;
  border-left: 4px solid #ffc107;
}

.notification-icon {
  flex-shrink: 0;
  margin-right: 0.75rem;
  font-size: 1.2rem;
}

.notification-content {
  flex: 1;
}

.notification-title {
  font-weight: 600;
  margin-bottom: 0.25rem;
}

.notification-message {
  color: #6c757d;
  font-size: 0.9rem;
  margin-bottom: 0.5rem;
}

.notification-meta {
  display: flex;
  gap: 1rem;
  font-size: 0.8rem;
  color: #6c757d;
  margin-bottom: 0.5rem;
}

.notification-actions {
  display: flex;
  gap: 0.5rem;
}

.unread-badge {
  width: 8px;
  height: 8px;
  background: #007bff;
  border-radius: 50%;
  margin-left: 0.5rem;
  margin-top: 0.25rem;
  flex-shrink: 0;
}

.loading-state,
.empty-state {
  padding: 2rem;
  text-align: center;
  color: #6c757d;
}

.panel-footer {
  padding: 0.75rem 1rem;
  border-top: 1px solid #e9ecef;
  background: #f8f9fa;
  text-align: center;
}
</style>