import { defineStore } from 'pinia'

export const useUserStore = defineStore('user', {
  state: () => ({
    profile: null,
    preferences: {
      theme: 'light',
      language: 'fr',
      notifications: {
        email: true,
        push: true,
        sms: false
      }
    },
    bookings: [],
    loading: false,
    error: null
  }),

  getters: {
    hasActiveBookings: (state) => {
      return state.bookings.some(booking => 
        booking.status === 'confirmed' && new Date(booking.endDate) > new Date()
      );
    },
    
    upcomingBookings: (state) => {
      const now = new Date();
      return state.bookings.filter(booking => 
        booking.status === 'confirmed' && 
        new Date(booking.startDate) > now
      ).sort((a, b) => new Date(a.startDate) - new Date(b.startDate));
    },

    pastBookings: (state) => {
      const now = new Date();
      return state.bookings.filter(booking => 
        new Date(booking.endDate) < now
      ).sort((a, b) => new Date(b.endDate) - new Date(a.endDate));
    }
  },

  actions: {
    async fetchProfile() {
      this.loading = true;
      this.error = null;

      try {
        const response = await fetch('/api/user/profile', {
          headers: {
            'Authorization': `Bearer ${this.getAuthToken()}`,
          },
        });

        if (!response.ok) {
          throw new Error('Impossible de récupérer le profil');
        }

        const data = await response.json();
        this.profile = data;
        
        return data;
      } catch (error) {
        this.error = error.message;
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async updateProfile(profileData) {
      this.loading = true;
      this.error = null;

      try {
        const response = await fetch('/api/user/profile', {
          method: 'PUT',
          headers: {
            'Content-Type': 'application/json',
            'Authorization': `Bearer ${this.getAuthToken()}`,
          },
          body: JSON.stringify(profileData),
        });

        if (!response.ok) {
          throw new Error('Impossible de mettre à jour le profil');
        }

        const data = await response.json();
        this.profile = data;
        
        return data;
      } catch (error) {
        this.error = error.message;
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async fetchBookings() {
      this.loading = true;
      this.error = null;

      try {
        const response = await fetch('/api/user/bookings', {
          headers: {
            'Authorization': `Bearer ${this.getAuthToken()}`,
          },
        });

        if (!response.ok) {
          throw new Error('Impossible de récupérer les réservations');
        }

        const data = await response.json();
        this.bookings = data;
        
        return data;
      } catch (error) {
        this.error = error.message;
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async updatePreferences(newPreferences) {
      this.preferences = { ...this.preferences, ...newPreferences };
      
      // Sauvegarder dans localStorage
      localStorage.setItem('user_preferences', JSON.stringify(this.preferences));

      // Synchroniser avec le serveur
      try {
        await fetch('/api/user/preferences', {
          method: 'PUT',
          headers: {
            'Content-Type': 'application/json',
            'Authorization': `Bearer ${this.getAuthToken()}`,
          },
          body: JSON.stringify(this.preferences),
        });
      } catch (error) {
        console.error('Erreur lors de la sauvegarde des préférences:', error);
      }
    },

    loadPreferencesFromStorage() {
      const stored = localStorage.getItem('user_preferences');
      if (stored) {
        try {
          this.preferences = { ...this.preferences, ...JSON.parse(stored) };
        } catch (error) {
          console.error('Erreur lors du chargement des préférences:', error);
        }
      }
    },

    getAuthToken() {
      return localStorage.getItem('auth_token');
    },

    clearError() {
      this.error = null;
    }
  }
});
