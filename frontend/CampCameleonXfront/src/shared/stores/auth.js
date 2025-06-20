import { defineStore } from 'pinia'

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: null,
    token: null,
    isAuthenticated: false,
    loading: false,
    error: null
  }),

  getters: {
    isAdmin: (state) => state.user?.role === 'admin',
    userName: (state) => state.user?.firstName + ' ' + state.user?.lastName,
    userInitials: (state) => {
      if (!state.user) return '';
      return (state.user.firstName?.[0] || '') + (state.user.lastName?.[0] || '');
    }
  },

  actions: {
    async login(credentials) {
      this.loading = true;
      this.error = null;

      try {
        // Simulation d'appel API
        const response = await fetch('/api/auth/login', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
          },
          body: JSON.stringify(credentials),
        });

        if (!response.ok) {
          throw new Error('Identifiants invalides');
        }

        const data = await response.json();
        
        this.user = data.user;
        this.token = data.token;
        this.isAuthenticated = true;

        // Sauvegarder dans localStorage
        localStorage.setItem('auth_token', data.token);
        localStorage.setItem('user', JSON.stringify(data.user));

        return data;
      } catch (error) {
        this.error = error.message;
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async logout() {
      try {
        // Simulation d'appel API
        await fetch('/api/auth/logout', {
          method: 'POST',
          headers: {
            'Authorization': `Bearer ${this.token}`,
          },
        });
      } catch (error) {
        console.error('Erreur lors de la déconnexion:', error);
      }

      // Reset du store
      this.user = null;
      this.token = null;
      this.isAuthenticated = false;
      this.error = null;

      // Nettoyer localStorage
      localStorage.removeItem('auth_token');
      localStorage.removeItem('user');
    },

    async checkAuth() {
      const token = localStorage.getItem('auth_token');
      const user = localStorage.getItem('user');

      if (token && user) {
        try {
          this.token = token;
          this.user = JSON.parse(user);
          this.isAuthenticated = true;

          // Vérifier la validité du token
          await this.verifyToken();
        } catch (error) {
          console.error('Token invalide:', error);
          this.logout();
        }
      }
    },

    async verifyToken() {
      try {
        const response = await fetch('/api/auth/verify', {
          headers: {
            'Authorization': `Bearer ${this.token}`,
          },
        });

        if (!response.ok) {
          throw new Error('Token invalide');
        }

        const data = await response.json();
        this.user = data.user;
      } catch (error) {
        throw error;
      }
    },

    clearError() {
      this.error = null;
    }
  }
});
