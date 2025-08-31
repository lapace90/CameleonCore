import { defineStore } from 'pinia'

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: null,
    token: null,
    isAuthenticated: false,
    loading: false,
    error: null,
    users: JSON.parse(localStorage.getItem('users') || '[]')
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
        if (
          credentials.email === 'admin@campcanteloup.fr' &&
          credentials.password === 'password'
        ) {
          const user = {
            email: credentials.email,
            role: 'admin',
            firstName: 'Admin',
            lastName: 'CampCameleonX'
          }
          const token = 'dummy-token'

          this.user = user
          this.token = token
          this.isAuthenticated = true

          localStorage.setItem('auth_token', token)
          localStorage.setItem('user', JSON.stringify(user))

          return { user, token }
        }

        const registered = this.users.find(
          (u) => u.email === credentials.email && u.password === credentials.password
        )

        if (registered) {
          const token = 'dummy-user-token'
          this.user = { ...registered }
          this.token = token
          this.isAuthenticated = true
          localStorage.setItem('auth_token', token)
          localStorage.setItem('user', JSON.stringify(this.user))
          return { user: this.user, token }
        }

        throw new Error('Identifiants invalides')
      } catch (error) {
        this.error = error.message
        throw error
      } finally {
        this.loading = false
      }
    },

    async register(payload) {
      this.loading = true
      this.error = null

      try {
        if (this.users.some((u) => u.email === payload.email)) {
          throw new Error('Email déjà utilisé')
        }

        const newUser = {
          firstName: payload.firstName,
          lastName: payload.lastName,
          email: payload.email,
          password: payload.password,
          role: 'user'
        }

        this.users.push(newUser)
        localStorage.setItem('users', JSON.stringify(this.users))

        const token = 'dummy-user-token'
        this.user = { ...newUser }
        this.token = token
        this.isAuthenticated = true
        localStorage.setItem('auth_token', token)
        localStorage.setItem('user', JSON.stringify(this.user))

        return { user: this.user, token }
      } catch (error) {
        this.error = error.message
        throw error
      } finally {
        this.loading = false
      }
    },

    async logout() {
      // Reset du store
      this.user = null
      this.token = null
      this.isAuthenticated = false
      this.error = null

      // Nettoyer localStorage
      localStorage.removeItem('auth_token')
      localStorage.removeItem('user')
    },

    async checkAuth() {
      // Vérifier si un token est stocké
      const token = localStorage.getItem('auth_token')
      const user = localStorage.getItem('user')

      if (token && user) {
        try {
          this.token = token
          this.user = JSON.parse(user)
          this.isAuthenticated = true

          // Vérifier la validité du token
          await this.verifyToken()
        } catch (error) {
          console.error('Token invalide:', error)
          this.logout()
        }
      }
    },

    async verifyToken() {
      // Simuler une vérification de token
      if (!['dummy-token', 'dummy-user-token'].includes(this.token)) {
        throw new Error('Token invalide')
      }
    },

    clearError() {
      this.error = null;
    }
  }
});