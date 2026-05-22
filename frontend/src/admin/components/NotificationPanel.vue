<template>
  <div class="notification-panel">
    <!-- Header -->
    <div class="panel-header">
      <h3 class="panel-title">
        <AppIcon name="bell" />
        Notifications 
        <span v-if="unreadCount > 0" class="badge badge-danger">{{ unreadCount }}</span>
      </h3>
      
      <button 
        v-if="notifications.length > 0"
        @click="markAllAsRead" 
        class="btn btn-sm btn-outline-secondary"
        :disabled="unreadCount === 0"
      >
        Tout marquer lu
      </button>
    </div>

    <!-- Liste des notifications -->
    <div class="notifications-list">
      <div v-if="isLoading" class="loading-state">
        <AppIcon name="loader-circle" :spin="true" />
        Chargement...
      </div>

      <div v-else-if="notifications.length === 0" class="empty-state">
        <AppIcon name="circle-check" />
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
            <AppIcon :name="getNotificationIcon(notification.type)" :style="{ color: getNotificationColor(notification.type) }" />
          </div>

          <!-- Contenu -->
          <div class="notification-content">
            <div class="notification-title">{{ notification.title }}</div>
            <div class="notification-message">{{ notification.message }}</div>
            
            <!-- Métadonnées -->
            <div class="notification-meta">
              <!--  utiliser createdAt au lieu de created_at -->
              <span class="time">{{ formatTime(notification.createdAt || notification.created_at) }}</span>
              <span v-if="notification.data?.customer_name" class="customer">
                {{ notification.data.customer_name }}
              </span>
              <span v-if="notification.data?.client_name" class="customer">
                {{ notification.data.client_name }}
              </span>
              <span v-if="notification.data?.amount" class="amount">
                {{ formatMoney(notification.data.amount) }}
              </span>
              <span v-if="notification.data?.rating" class="rating">
                {{ notification.data.rating }}⭐
              </span>
            </div>

            <!-- Actions -->
            <div v-if="notification.actions && notification.actions.length > 0" class="notification-actions">
              <router-link 
                v-for="action in notification.actions" 
                :key="action.label"
                :to="action.url" 
                :class="`btn btn-sm btn-${action.type}`"
                @click.stop="handleActionClick(action, notification)"
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
    <div v-if="notifications.length > 5" class="panel-footer">
      <button @click="loadMoreNotifications" class="btn btn-sm btn-primary">
        Voir plus de notifications
      </button>
    </div>
  </div>
</template>

<script>
import AdminApi from '@/services/AdminApi'

export default {
  name: 'NotificationPanel',
  
  emits: ['notification-count-changed', 'notification-clicked', 'action-clicked'],
  
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
      refreshInterval: null,
      currentLimit: this.limit
    }
  },

  computed: {
    unreadCount() {
      return this.notifications.filter(n => !n.read).length
    }
  },

  watch: {
    unreadCount(newCount) {
      this.$emit('notification-count-changed', newCount)
    }
  },

  async mounted() {
    console.log('🔔 NotificationPanel mounted')
    await this.loadNotifications()
    
    if (this.autoRefresh) {
      this.refreshInterval = setInterval(() => {
        this.loadNotifications(false)
      }, 60 * 1000)
    }
  },

  beforeUnmount() {
    if (this.refreshInterval) {
      clearInterval(this.refreshInterval)
    }
  },

  methods: {
    async loadNotifications(showLoader = true) {
      try {
        if (showLoader) this.isLoading = true

        const response = await AdminApi.getNotifications(this.currentLimit)
        this.notifications = Array.isArray(response) ? response : []

        console.log(`✅ ${this.notifications.length} notifications chargées`)

      } catch (error) {
        console.error('❌ Erreur chargement notifications:', error)
        this.notifications = []
      } finally {
        if (showLoader) this.isLoading = false
      }
    },

    async handleNotificationClick(notification) {
      console.log('🖱️ Clic sur notification:', notification.id)
      
      if (!notification.read) {
        await this.markAsRead(notification.id)
      }

      this.$emit('notification-clicked', notification)

      if (notification.actions && notification.actions[0]) {
        this.$router.push(notification.actions[0].url)
      }
    },

    handleActionClick(action, notification) {
      this.$emit('action-clicked', { action, notification })
    },

    async markAsRead(notificationId) {
      try {
        console.log(`📝 Marquage notification ${notificationId}...`)
        
        await AdminApi.markNotificationAsRead(notificationId)
        
        const notification = this.notifications.find(n => n.id === notificationId)
        if (notification) {
          notification.read = true
        }

        console.log(`✅ Notification ${notificationId} marquée comme lue`)

      } catch (error) {
        console.error(`❌ Erreur marquage notification ${notificationId}:`, error)
      }
    },

    async markAllAsRead() {
      try {
        const unreadIds = this.notifications
          .filter(n => !n.read)
          .map(n => n.id)

        if (unreadIds.length === 0) return

        console.log(`📝 Marquage de ${unreadIds.length} notifications...`)

        const successCount = await AdminApi.markAllNotificationsAsRead(unreadIds)
        
        this.notifications.forEach(n => {
          if (unreadIds.includes(n.id)) {
            n.read = true
          }
        })

        console.log(`✅ ${successCount} notifications marquées`)

      } catch (error) {
        console.error('❌ Erreur marquage batch:', error)
      }
    },

    async loadMoreNotifications() {
      this.currentLimit += 10
      await this.loadNotifications(true)
    },

    // ===========================
    // UTILITAIRES
    // ===========================

    getNotificationIcon(type) {
      const icons = {
        'new_reservation': 'calendar-plus',
        'reservation': 'calendar-plus',
        'new_review': 'star', 
        'review': 'star', 
        'payment': 'credit-card',
        'user': 'user-plus',
        'system': 'settings',
        'warning': 'triangle-alert',
        'success': 'circle-check',
        'info': 'info',
        'default': 'bell'
      }
      
      return icons[type] || icons.default
    },

    getNotificationColor(type) {
      const colors = {
        'new_reservation': '#28a745',
        'reservation': '#28a745',
        'new_review': '#ffc107', 
        'review': '#ffc107', 
        'payment': '#17a2b8', 
        'user': '#6f42c1',
        'system': '#6c757d',
        'warning': '#ffc107',
        'success': '#28a745',
        'info': '#17a2b8',
        'default': '#6c757d'
      }
      
      return colors[type] || colors.default
    },

    formatTime(dateString) {
      if (!dateString) return 'Maintenant'
      
      try {
        const date = new Date(dateString)
        const now = new Date()
        const diff = now.getTime() - date.getTime()
        
        const minutes = Math.floor(diff / (1000 * 60))
        const hours = Math.floor(diff / (1000 * 60 * 60))
        const days = Math.floor(diff / (1000 * 60 * 60 * 24))
        
        if (minutes < 1) return 'À l\'instant'
        if (minutes < 60) return `Il y a ${minutes}min`
        if (hours < 24) return `Il y a ${hours}h`
        if (days < 7) return `Il y a ${days}j`
        
        return date.toLocaleDateString('fr-FR')
      } catch (error) {
        return 'Date invalide'
      }
    },

    formatMoney(amount) {
      if (!amount) return ''
      
      try {
        return new Intl.NumberFormat('fr-FR', {
          style: 'currency',
          currency: 'EUR'
        }).format(parseFloat(amount))
      } catch (error) {
        return `${amount}€`
      }
    }
  }
}
</script>

<style lang="scss" scoped>
.notification-panel {
  background: white;
  border-radius: 12px;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
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

.badge-danger {
  background: #dc3545;
  color: white;
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
  
  &:hover {
    background-color: #f8f9fa;
  }
  
  &:last-child {
    border-bottom: none;
  }
  
  &.unread {
    background-color: #fff3cd;
    border-left: 4px solid #ffc107;
  }
}

.notification-icon {
  flex-shrink: 0;
  margin-right: 0.75rem;
  font-size: 1.2rem;
  margin-top: 0.1rem;
}

.notification-content {
  flex: 1;
}

.notification-title {
  font-weight: 600;
  margin-bottom: 0.25rem;
  font-size: 0.9rem;
}

.notification-message {
  color: #6c757d;
  font-size: 0.85rem;
  margin-bottom: 0.5rem;
  line-height: 1.4;
}

.notification-meta {
  display: flex;
  gap: 1rem;
  font-size: 0.8rem;
  color: #6c757d;
  margin-bottom: 0.5rem;
  flex-wrap: wrap;
}

.notification-actions {
  display: flex;
  gap: 0.5rem;
  flex-wrap: wrap;
  
  .btn {
    padding: 0.25rem 0.5rem;
    font-size: 0.75rem;
    text-decoration: none;
    border-radius: 4px;
    
    &.btn-primary {
      background-color: #007bff;
      color: white;
      border: 1px solid #007bff;
      
      &:hover {
        background-color: #0056b3;
      }
    }
    
    &.btn-secondary {
      background-color: #6c757d;
      color: white;
      border: 1px solid #6c757d;
      
      &:hover {
        background-color: #5a6268;
      }
    }
  }
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
  
  i {
    font-size: 2rem;
    margin-bottom: 1rem;
    display: block;
  }
  
  p {
    margin: 0;
    font-size: 0.9rem;
  }
}

.loading-state i {
  color: #007bff;
}

.empty-state i {
  color: #28a745;
}

.panel-footer {
  padding: 0.75rem 1rem;
  border-top: 1px solid #e9ecef;
  background: #f8f9fa;
  text-align: center;
  
  .btn {
    background: #007bff;
    color: white;
    border: none;
    padding: 0.5rem 1rem;
    border-radius: 6px;
    font-size: 0.85rem;
    cursor: pointer;
    
    &:hover {
      background: #0056b3;
    }
  }
}

.btn {
  &.btn-sm {
    padding: 0.25rem 0.5rem;
    font-size: 0.8rem;
  }
  
  &.btn-outline-secondary {
    color: #6c757d;
    border: 1px solid #6c757d;
    background: transparent;
    
    &:hover:not(:disabled) {
      background: #6c757d;
      color: white;
    }
    
    &:disabled {
      opacity: 0.5;
      cursor: not-allowed;
    }
  }
}
</style>