<template>
  <teleport to="body">
    <div class="notifications-container">
      <transition-group name="notification" tag="div" class="notifications-list">
        <div
          v-for="notification in notifications"
          :key="notification.id"
          :class="[
            'notification',
            `notification-${notification.type}`,
            { 'notification-dismissible': notification.dismissible }
          ]"
          @click="notification.dismissible && dismiss(notification.id)"
        >
          <!-- Icône -->
          <div class="notification-icon">
            <AppIcon :name="getIcon(notification.type)" :spin="notification.type === 'loading'" />
          </div>

          <!-- Contenu -->
          <div class="notification-content">
            <h4 v-if="notification.title" class="notification-title">
              {{ notification.title }}
            </h4>
            <p class="notification-message">
              {{ notification.message }}
            </p>
            
            <!-- Actions personnalisées -->
            <div v-if="notification.actions" class="notification-actions">
              <button
                v-for="action in notification.actions"
                :key="action.label"
                @click.stop="handleAction(action, notification)"
                class="notification-action-btn"
                :class="action.style || 'primary'"
              >
                {{ action.label }}
              </button>
            </div>
          </div>

          <!-- Bouton fermer -->
          <button
            v-if="notification.dismissible"
            @click.stop="dismiss(notification.id)"
            class="notification-close"
            title="Fermer"
          >
            <AppIcon name="x" />
          </button>

          <!-- Barre de progression pour auto-dismiss -->
          <div
            v-if="notification.autoHide && notification.duration"
            class="notification-progress"
            :style="{ animationDuration: `${notification.duration}ms` }"
          ></div>
        </div>
      </transition-group>
    </div>
  </teleport>
</template>

<script>
import { ref, reactive, nextTick } from 'vue'

// Store global des notifications
const notifications = ref([])
let notificationId = 0

export default {
  name: 'NotificationSystem',
  
  setup() {
    const dismiss = (id) => {
      const index = notifications.value.findIndex(n => n.id === id)
      if (index > -1) {
        notifications.value.splice(index, 1)
      }
    }

    const handleAction = (action, notification) => {
      if (typeof action.handler === 'function') {
        action.handler(notification)
      }
      
      // Fermer la notification après l'action si spécifié
      if (action.dismiss !== false) {
        dismiss(notification.id)
      }
    }

    const getIcon = (type) => {
      const icons = {
        success: 'circle-check',
        error: 'circle-alert',
        warning: 'triangle-alert',
        info: 'info',
        loading: 'loader-circle'
      }
      return icons[type] || icons.info
    }

    return {
      notifications,
      dismiss,
      handleAction,
      getIcon
    }
  }
}

// ===========================
// API DE NOTIFICATION
// ===========================

export const notify = {
  /**
   * Afficher une notification
   */
  show(options) {
    const notification = {
      id: ++notificationId,
      type: options.type || 'info',
      title: options.title,
      message: options.message || '',
      dismissible: options.dismissible !== false,
      autoHide: options.autoHide !== false,
      duration: options.duration || 5000,
      actions: options.actions || null,
      ...options
    }

    notifications.value.push(notification)

    // Auto-hide après la durée spécifiée
    if (notification.autoHide && notification.duration > 0) {
      setTimeout(() => {
        const index = notifications.value.findIndex(n => n.id === notification.id)
        if (index > -1) {
          notifications.value.splice(index, 1)
        }
      }, notification.duration)
    }

    return notification.id
  },

  /**
   * Notification de succès
   */
  success(message, options = {}) {
    return this.show({
      type: 'success',
      message,
      duration: 4000,
      ...options
    })
  },

  /**
   * Notification d'erreur
   */
  error(message, options = {}) {
    return this.show({
      type: 'error',
      message,
      duration: 8000,
      autoHide: false,
      ...options
    })
  },

  /**
   * Notification d'avertissement
   */
  warning(message, options = {}) {
    return this.show({
      type: 'warning',
      message,
      duration: 6000,
      ...options
    })
  },

  /**
   * Notification d'information
   */
  info(message, options = {}) {
    return this.show({
      type: 'info',
      message,
      duration: 5000,
      ...options
    })
  },

  /**
   * Notification de chargement
   */
  loading(message, options = {}) {
    return this.show({
      type: 'loading',
      message,
      autoHide: false,
      dismissible: false,
      ...options
    })
  },

  /**
   * Fermer une notification spécifique
   */
  dismiss(id) {
    const index = notifications.value.findIndex(n => n.id === id)
    if (index > -1) {
      notifications.value.splice(index, 1)
    }
  },

  /**
   * Fermer toutes les notifications
   */
  clear() {
    notifications.value.splice(0)
  },

  /**
   * Fermer toutes les notifications d'un type
   */
  clearType(type) {
    for (let i = notifications.value.length - 1; i >= 0; i--) {
      if (notifications.value[i].type === type) {
        notifications.value.splice(i, 1)
      }
    }
  }
}

// ===========================
// PLUGIN VUE
// ===========================

export const NotificationPlugin = {
  install(app) {
    // Ajouter l'API aux propriétés globales
    app.config.globalProperties.$notify = notify
    
    // Composable pour Composition API
    app.provide('notify', notify)
  }
}

// ===========================
// COMPOSABLE
// ===========================

export function useNotifications() {
  return notify
}

// ===========================
// HELPERS POUR INTÉGRATIONS COMMUNES
// ===========================

/**
 * Helper pour les erreurs d'API
 */
export function notifyApiError(error, defaultMessage = 'Une erreur est survenue') {
  let message = defaultMessage
  let title = 'Erreur'

  if (error.response) {
    // Erreur HTTP
    const status = error.response.status
    const data = error.response.data

    title = `Erreur ${status}`
    
    if (data.message) {
      message = data.message
    } else if (data.errors) {
      // Erreurs de validation Laravel
      const errors = Object.values(data.errors).flat()
      message = errors.join('\n')
    } else {
      message = `Erreur HTTP ${status}`
    }
  } else if (error.message) {
    message = error.message
  }

  return notify.error(message, { title })
}

/**
 * Helper pour les succès d'API
 */
export function notifyApiSuccess(response, defaultMessage = 'Opération réussie') {
  const message = response.data?.message || defaultMessage
  return notify.success(message)
}

/**
 * Helper pour les actions utilisateur
 */
export function notifyUserAction(action, resource) {
  const messages = {
    created: `${resource} créé avec succès`,
    updated: `${resource} mis à jour avec succès`,
    deleted: `${resource} supprimé avec succès`,
    restored: `${resource} restauré avec succès`
  }
  
  const message = messages[action] || `Action ${action} effectuée`
  return notify.success(message)
}

/**
 * Helper pour les confirmations avec actions
 */
export function notifyConfirm(message, onConfirm, options = {}) {
  return notify.warning(message, {
    autoHide: false,
    actions: [
      {
        label: 'Annuler',
        style: 'secondary',
        dismiss: true
      },
      {
        label: options.confirmText || 'Confirmer',
        style: 'primary',
        handler: onConfirm,
        dismiss: true
      }
    ],
    ...options
  })
}
</script>

<style lang="scss">
// ===========================
// CONTENEUR DES NOTIFICATIONS
// ===========================

.notifications-container {
  position: fixed;
  top: 20px;
  right: 20px;
  z-index: 9999;
  width: 400px;
  max-width: 90vw;
  pointer-events: none;
}

.notifications-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

// ===========================
// NOTIFICATION INDIVIDUELLE
// ===========================

.notification {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  padding: 16px;
  background: white;
  border-radius: 12px;
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
  border-left: 4px solid;
  pointer-events: auto;
  position: relative;
  overflow: hidden;
  max-width: 100%;
  
  // Types de notifications
  &.notification-success {
    border-left-color: #10b981;
    
    .notification-icon {
      color: #10b981;
    }
  }
  
  &.notification-error {
    border-left-color: #ef4444;
    
    .notification-icon {
      color: #ef4444;
    }
  }
  
  &.notification-warning {
    border-left-color: #f59e0b;
    
    .notification-icon {
      color: #f59e0b;
    }
  }
  
  &.notification-info {
    border-left-color: #3b82f6;
    
    .notification-icon {
      color: #3b82f6;
    }
  }
  
  &.notification-loading {
    border-left-color: #6b7280;
    
    .notification-icon {
      color: #6b7280;
    }
  }
  
  // Notification cliquable
  &.notification-dismissible {
    cursor: pointer;
    
    &:hover {
      box-shadow: 0 15px 30px rgba(0, 0, 0, 0.2);
      transform: translateY(-2px);
    }
  }
}

// ===========================
// CONTENU DE LA NOTIFICATION
// ===========================

.notification-icon {
  flex-shrink: 0;
  font-size: 20px;
  margin-top: 2px;
}

.notification-content {
  flex: 1;
  min-width: 0;
}

.notification-title {
  margin: 0 0 4px 0;
  font-size: 16px;
  font-weight: 600;
  color: #1f2937;
  line-height: 1.2;
}

.notification-message {
  margin: 0;
  font-size: 14px;
  color: #6b7280;
  line-height: 1.4;
  white-space: pre-line;
}

// ===========================
// ACTIONS
// ===========================

.notification-actions {
  margin-top: 12px;
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
}

.notification-action-btn {
  padding: 6px 12px;
  border: none;
  border-radius: 6px;
  font-size: 12px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s ease;
  
  &.primary {
    background: #3b82f6;
    color: white;
    
    &:hover {
      background: #2563eb;
    }
  }
  
  &.secondary {
    background: #e5e7eb;
    color: #6b7280;
    
    &:hover {
      background: #d1d5db;
    }
  }
  
  &.danger {
    background: #ef4444;
    color: white;
    
    &:hover {
      background: #dc2626;
    }
  }
}

// ===========================
// BOUTON FERMER
// ===========================

.notification-close {
  flex-shrink: 0;
  width: 24px;
  height: 24px;
  border: none;
  background: none;
  border-radius: 4px;
  cursor: pointer;
  color: #9ca3af;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 12px;
  transition: all 0.2s ease;
  
  &:hover {
    background: #f3f4f6;
    color: #6b7280;
  }
}

// ===========================
// BARRE DE PROGRESSION
// ===========================

.notification-progress {
  position: absolute;
  bottom: 0;
  left: 0;
  height: 3px;
  background: linear-gradient(90deg, #3b82f6, #1d4ed8);
  border-radius: 0 0 12px 12px;
  animation: progress-shrink linear;
  transform-origin: left center;
}

@keyframes progress-shrink {
  from {
    width: 100%;
  }
  to {
    width: 0%;
  }
}

// ===========================
// ANIMATIONS
// ===========================

.notification-enter-active {
  transition: all 0.3s ease-out;
}

.notification-leave-active {
  transition: all 0.3s ease-in;
}

.notification-enter-from {
  opacity: 0;
  transform: translateX(100%);
}

.notification-leave-to {
  opacity: 0;
  transform: translateX(100%);
}

.notification-move {
  transition: transform 0.3s ease;
}

// ===========================
// RESPONSIVE
// ===========================

@media (max-width: 480px) {
  .notifications-container {
    top: 10px;
    right: 10px;
    left: 10px;
    width: auto;
  }
  
  .notification {
    padding: 12px;
  }
  
  .notification-actions {
    flex-direction: column;
  }
  
  .notification-action-btn {
    width: 100%;
    justify-content: center;
  }
}

// ===========================
// THÈME SOMBRE (optionnel)
// ===========================

@media (prefers-color-scheme: dark) {
  .notification {
    background: #1f2937;
    color: #f9fafb;
    
    .notification-title {
      color: #f9fafb;
    }
    
    .notification-message {
      color: #d1d5db;
    }
    
    .notification-close {
      color: #9ca3af;
      
      &:hover {
        background: #374151;
        color: #f3f4f6;
      }
    }
    
    .notification-action-btn.secondary {
      background: #374151;
      color: #d1d5db;
      
      &:hover {
        background: #4b5563;
      }
    }
  }
}
</style>

<!--
EXEMPLES D'USAGE :

// Dans un composant Vue
export default {
  methods: {
    async saveUser() {
      try {
        const loadingId = this.$notify.loading('Enregistrement en cours...')
        
        await this.$http.post('/api/users', this.form)
        
        this.$notify.dismiss(loadingId)
        this.$notify.success('Utilisateur créé avec succès')
        
      } catch (error) {
        this.$notify.dismiss(loadingId)
        notifyApiError(error, 'Erreur lors de la création')
      }
    },
    
    confirmDelete() {
      notifyConfirm(
        'Êtes-vous sûr de vouloir supprimer cet utilisateur ?',
        () => this.deleteUser(),
        { confirmText: 'Supprimer' }
      )
    }
  }
}

// Avec Composition API
import { useNotifications } from '@/components/NotificationSystem.vue'

export default {
  setup() {
    const notify = useNotifications()
    
    const handleSuccess = () => {
      notify.success('Opération réussie!')
    }
    
    const handleError = () => {
      notify.error('Une erreur est survenue', {
        title: 'Erreur critique',
        actions: [
          {
            label: 'Réessayer',
            handler: () => retry()
          }
        ]
      })
    }
    
    return { handleSuccess, handleError }
  }
}
-->