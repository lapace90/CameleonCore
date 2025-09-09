// frontend/CampCameleonXfront/src/shared/stores/roles.js
import { defineStore } from 'pinia'
import RolesApi from '@/services/RolesApi'

const TTL = 1000 * 60 * 5 // 5 min de cache

export const useRolesStore = defineStore('roles', {
  state: () => ({
    // Données
    roles: [],
    availablePermissions: { categories: [] }, // format groupé stable
    // Index rapide
    roleById: new Map(),

    // UI
    loading: false,
    error: null,
    successMessage: null,

    // Cache & promesses en cours
    _lastRolesAt: 0,
    _lastPermsAt: 0,
    _inflightRoles: null,
    _inflightPerms: null,
  }),

  getters: {
    rolesData: (state) => ({
      data: state.roles,
      meta: {
        total: state.roles.length,
        stats: {
          total_users: state.roles.reduce((sum, r) => sum + (r.total_users_count || 0), 0),
        }
      }
    }),

    flatPermissions: (state) => {
      const out = []
      for (const cat of state.availablePermissions.categories || []) {
        if (cat?.permissions?.length) out.push(...cat.permissions)
      }
      return out
    }
  },

  actions: {
    // ---------------------------
    // Helpers messages
    // ---------------------------
    setSuccess(message) {
      this.successMessage = message
      this.error = null
      setTimeout(() => { this.successMessage = null }, 5000)
    },
    setError(message) {
      this.error = message
      this.successMessage = null
    },
    clearMessages() {
      this.error = null
      this.successMessage = null
    },

    // ---------------------------
    // Normalisation
    // ---------------------------
    _normalizePermissionId(p) {
      return { ...p, id: String(p.id) }
    },
    _normalizePermissionsGrouped(input) {
      // Accepte {categories:[{permissions:[]},...]} ou {permissions:[...]}
      if (Array.isArray(input?.categories)) {
        return {
          categories: input.categories.map(cat => ({
            ...cat,
            key: cat.key || cat.name,
            permissions: (cat.permissions || []).map(this._normalizePermissionId),
          }))
        }
      }
      if (Array.isArray(input?.permissions)) {
        const byKey = {}
        for (const p of input.permissions) {
          const key = p.category || 'general'
          if (!byKey[key]) {
            byKey[key] = {
              key,
              name: key.charAt(0).toUpperCase() + key.slice(1),
              icon: 'fa-key',
              color: undefined,
              permissions: [],
            }
          }
          byKey[key].permissions.push(this._normalizePermissionId(p))
        }
        return { categories: Object.values(byKey) }
      }
      return { categories: [] }
    },

    _indexRoles(roles) {
      this.roleById = new Map()
      for (const r of roles) this.roleById.set(r.id, r)
    },

    // ---------------------------
    // Chargements avec cache TTL
    // ---------------------------
    async ensureRoles({ force = false } = {}) {
      const now = Date.now()
      if (!force && this.roles.length && (now - this._lastRolesAt < TTL)) {
        return this.roles
      }
      if (this._inflightRoles) return this._inflightRoles

      this._inflightRoles = (async () => {
        try {
          this.loading = true
          this.clearMessages()
          const roles = await RolesApi.getAll() // ⚠️ renvoie déjà data
          this.roles = Array.isArray(roles) ? roles : (roles?.data || [])
          this._indexRoles(this.roles)
          this._lastRolesAt = Date.now()
          return this.roles
        } catch (e) {
          console.error('❌ ensureRoles:', e)
          this.setError('Impossible de charger les rôles')
          throw e
        } finally {
          this.loading = false
          this._inflightRoles = null
        }
      })()

      return this._inflightRoles
    },

    async ensurePermissions({ force = false } = {}) {
      const now = Date.now()
      if (!force && this.availablePermissions.categories.length && (now - this._lastPermsAt < TTL)) {
        return this.availablePermissions
      }
      if (this._inflightPerms) return this._inflightPerms

      this._inflightPerms = (async () => {
        try {
          this.loading = true
          const raw = await RolesApi.getAllPermissions() // ⚠️ renvoie déjà data
          this.availablePermissions = this._normalizePermissionsGrouped(raw)
          this._lastPermsAt = Date.now()
          return this.availablePermissions
        } catch (e) {
          console.error('❌ ensurePermissions:', e)
          this.setError('Impossible de charger les permissions')
          throw e
        } finally {
          this.loading = false
          this._inflightPerms = null
        }
      })()

      return this._inflightPerms
    },

    // Charge tout (avec SWR simple)
    async loadAllData({ force = false } = {}) {
      await Promise.all([
        this.ensureRoles({ force }),
        this.ensurePermissions({ force }),
      ])
    },

    // ---------------------------
    // CRUD (optimistic + patch local)
    // ---------------------------
    async createRole(roleData) {
      try {
        const created = await RolesApi.create(roleData) // idéalement renvoie l’entité créée
        // Patch local sans tout recharger
        if (created?.id) {
          this.roles = [created, ...this.roles]
          this.roleById.set(created.id, created)
        } else {
          await this.ensureRoles({ force: true }) // fallback
        }
        this.setSuccess(`Rôle "${roleData.name}" créé avec succès`)
      } catch (error) {
        this.setError(error.response?.data?.message || 'Erreur lors de la création')
        throw error
      }
    },

    async updateRole(roleId, roleData) {
      try {
        const updated = await RolesApi.update(roleId, roleData) // idéalement renvoie l’entité
        if (updated?.id) {
          const idx = this.roles.findIndex(r => r.id === roleId)
          if (idx !== -1) this.roles.splice(idx, 1, updated)
          this.roleById.set(updated.id, updated)
        } else {
          // Minimal si l’API ne renvoie pas l’entité : patch local
          const current = this.roleById.get(roleId)
          if (current) {
            const merged = { ...current, ...roleData }
            const idx = this.roles.findIndex(r => r.id === roleId)
            if (idx !== -1) this.roles.splice(idx, 1, merged)
            this.roleById.set(roleId, merged)
          } else {
            await this.ensureRoles({ force: true })
          }
        }
        this.setSuccess(`Rôle "${roleData.name || this.roleById.get(roleId)?.name || 'mis à jour'}" mis à jour`)
      } catch (error) {
        this.setError(error.response?.data?.message || 'Erreur lors de la mise à jour')
        throw error
      }
    },

    async deleteRole(roleId) {
      try {
        await RolesApi.delete(roleId)
        const idx = this.roles.findIndex(r => r.id === roleId)
        if (idx !== -1) this.roles.splice(idx, 1)
        this.roleById.delete(roleId)
        this.setSuccess(`Rôle supprimé avec succès`)
      } catch (error) {
        this.setError(error.response?.data?.message || 'Erreur lors de la suppression')
        throw error
      }
    },
  }
})
