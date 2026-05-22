import { defineStore } from 'pinia'

export const useAppStore = defineStore('app', {
  state: () => ({
    // Layout
    sidebarOpen: false,
    sidebarCollapsed: false,
    
    // Navigation
    currentRoute: null,
    breadcrumbs: [],
    
    // UI State
    loading: false,
    notifications: [],
    modals: {},
    
    // App Settings
    settings: {
      appName: 'CampCameleonX',
      version: '1.0.0',
      environment: 'development',
      apiUrl: '/api',
      theme: 'light'
    },

    // Mobile detection
    isMobile: false,
    
    // Error handling
    globalError: null
  }),

  getters: {
    unreadNotifications: (state) => {
      return state.notifications.filter(n => !n.read);
    },
    
    unreadNotificationCount: (state) => {
      return state.notifications.filter(n => !n.read).length;
    },

    isModalOpen: (state) => (modalId) => {
      return !!state.modals[modalId];
    }
  },

  actions: {
    // Sidebar
    toggleSidebar() {
      this.sidebarOpen = !this.sidebarOpen;
    },

    closeSidebar() {
      this.sidebarOpen = false;
    },

    toggleSidebarCollapse() {
      this.sidebarCollapsed = !this.sidebarCollapsed;
    },

    // Loading
    setLoading(status) {
      this.loading = status;
    },

    // Notifications
    addNotification(notification) {
      const id = Date.now().toString();
      this.notifications.unshift({
        id,
        timestamp: new Date(),
        read: false,
        ...notification
      });
      
      // Auto-remove after delay if specified
      if (notification.autoRemove !== false) {
        setTimeout(() => {
          this.removeNotification(id);
        }, notification.duration || 5000);
      }
    },

    markNotificationAsRead(id) {
      const notification = this.notifications.find(n => n.id === id);
      if (notification) {
        notification.read = true;
      }
    },

    removeNotification(id) {
      const index = this.notifications.findIndex(n => n.id === id);
      if (index > -1) {
        this.notifications.splice(index, 1);
      }
    },

    clearAllNotifications() {
      this.notifications = [];
    },

    // Modals
    openModal(modalId, data = null) {
      this.modals[modalId] = {
        isOpen: true,
        data
      };
    },

    closeModal(modalId) {
      if (this.modals[modalId]) {
        this.modals[modalId].isOpen = false;
      }
    },

    // Navigation
    setBreadcrumbs(breadcrumbs) {
      this.breadcrumbs = breadcrumbs;
    },

    setCurrentRoute(route) {
      this.currentRoute = route;
    },

    // Mobile detection
    checkMobile() {
      this.isMobile = window.innerWidth < 768;
    },

    // Error handling
    setGlobalError(error) {
      this.globalError = error;
      console.error('Global error:', error);
    },

    clearGlobalError() {
      this.globalError = null;
    },

    // Settings
    updateSetting(key, value) {
      this.settings[key] = value;
      
      // Sauvegarder dans localStorage
      localStorage.setItem('app_settings', JSON.stringify(this.settings));
    },

    loadSettingsFromStorage() {
      const stored = localStorage.getItem('app_settings');
      if (stored) {
        try {
          this.settings = { ...this.settings, ...JSON.parse(stored) };
        } catch (error) {
          console.error('Erreur lors du chargement des paramètres:', error);
        }
      }
    },

    // Initialization
    async initialize() {
      // Charger les paramètres
      this.loadSettingsFromStorage();
      
      // Vérifier si mobile
      this.checkMobile();
      
      // Écouter le redimensionnement
      window.addEventListener('resize', this.checkMobile);
      
      // Autres initialisations...
    }
  }
});