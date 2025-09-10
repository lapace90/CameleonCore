<!-- UserForm.vue - VERSION OPTIMISÉE pour la performance -->
<template>
  <div class="product-form-container">
    <!-- Message d'erreur -->
    <div v-if="error" class="error-message">
      <div class="alert alert-danger">
        <i class="fas fa-exclamation-triangle"></i>
        {{ error }}
        <button @click="clearMessages" class="btn-close">&times;</button>
      </div>
    </div>

    <!-- Loading minimal -->
    <div v-if="loading" class="loading-state">
      <div class="spinner"></div>
      <p>Chargement...</p>
    </div>

    <!-- Contenu principal -->
    <div v-else>
      <!-- Header -->
      <div class="form-header">
        <div class="header-navigation">
          <router-link to="/admin/users" class="back-link">
            <i class="fas fa-arrow-left"></i>
            Retour aux utilisateurs
          </router-link>
          <div class="breadcrumb">
            <span>Utilisateurs</span>
            <i class="fas fa-chevron-right"></i>
            <span>{{ isEditing ? user?.name : 'Nouvel utilisateur' }}</span>
          </div>
        </div>
      </div>

      <!-- Titre -->
      <div class="page-title-section">
        <div class="product-type-badge" style="background-color: #059669;">
          <i class="fas fa-user"></i>
          Utilisateur
        </div>
        <h1 class="page-title">
          {{ isEditing ? `Modifier "${user?.name}"` : 'Nouvel utilisateur' }}
        </h1>
      </div>

      <!-- Formulaire -->
      <form @submit.prevent="submitForm" class="product-form">
        <div class="form-content">
          <!-- Avatar (simplifié) -->
          <div class="form-left">
            <div class="form-section">
              <h3>Avatar</h3>
              <div class="avatar-upload">
                <div class="avatar-preview">
                  <div class="avatar-circle">
                    <i class="fas fa-user"></i>
                  </div>
                </div>
                <div class="avatar-actions">
                  <button type="button" class="btn btn-outline btn-sm">
                    <i class="fas fa-upload"></i>
                    Changer l'avatar
                  </button>
                </div>
              </div>
            </div>
          </div>

          <!-- Formulaire principal -->
          <div class="form-right">
            <!-- Informations de base -->
            <div class="form-section">
              <h3>Informations générales</h3>
              <div class="form-grid">
                <div class="form-group">
                  <label class="form-label required">Nom complet</label>
                  <input v-model="form.name" type="text" class="form-input" placeholder="Nom et prénom" required
                    :class="{ 'error': errors.name }" />
                  <span v-if="errors.name" class="error-message">{{ errors.name[0] }}</span>
                </div>

                <div class="form-group">
                  <label class="form-label required">Email</label>
                  <input v-model="form.email" type="email" class="form-input" placeholder="utilisateur@exemple.com"
                    required :class="{ 'error': errors.email }" />
                  <span v-if="errors.email" class="error-message">{{ errors.email[0] }}</span>
                </div>

                <div class="form-group" v-if="!isEditing">
                  <label class="form-label required">Mot de passe</label>
                  <input v-model="form.password" type="password" class="form-input" placeholder="••••••••" required
                    :class="{ 'error': errors.password }" />
                  <span v-if="errors.password" class="error-message">{{ errors.password[0] }}</span>
                </div>

                <div class="form-group" v-if="!isEditing">
                  <label class="form-label required">Confirmer le mot de passe</label>
                  <input v-model="form.password_confirmation" type="password" class="form-input" placeholder="••••••••"
                    required />
                </div>
              </div>
            </div>

            <!-- 🚀 SECTION RÔLES OPTIMISÉE -->
            <div class="form-section">
              <h3>Rôles
                <small style="color: #6b7280;">({{ availableRoles.length }} disponible{{ availableRoles.length > 1 ? 's'
                  : '' }})</small>
              </h3>

              <!-- Rôle principal -->
              <div class="form-group">
                <label class="form-label">Rôle principal</label>
                <select v-model="form.role_id" class="form-input">
                  <option value="">Sélectionner un rôle</option>
                  <option v-for="role in availableRoles" :key="role.id" :value="role.id">
                    {{ role.name }}
                    <!-- <span v-if="role.description"> - {{ role.description }}</span> -->
                  </option>
                </select>
              </div>

              <!-- Rôles additionnels -->
              <div class="form-group">
                <label class="form-label">Rôles additionnels</label>
                <div class="roles-grid">
                  <label v-for="role in availableRoles" :key="`add-${role.id}`" class="checkbox-itlem"
                    :class="{ 'disabled': form.role_id === role.id }">
                    <input type="checkbox" :value="role.id" v-model="form.additional_roles"
                      :disabled="form.role_id === role.id" />
                    <span class="checkbox-label px-3">
                      {{ role.name }}
                      <small v-if="form.role_id === role.id" style="color: #6b7280;">(principal)</small>
                    </span>
                  </label>
                </div>
              </div>

              <!-- Info RBAC -->
              <div class="form-info">
                <i class="fas fa-info-circle px-3" style="color: #0ea5e9;"></i>
                <strong>Architecture RBAC :</strong>
                <p>Les permissions sont gérées via les rôles. Pas de permissions directes aux utilisateurs.</p>
              </div>
            </div>

            <!-- Statut -->
            <div class="form-section ">
              <h3>Statut</h3>
              <div class="radio-group p-4">
                <label class="radio-item">
                  <input type="radio" v-model="form.status" value="active" />
                  <span class="radio-label">
                    <i class="fas fa-check-circle" style="color: #10b981;"></i>
                    Actif
                  </span>
                </label>
                <label class="radio-item ">
                  <input type="radio" v-model="form.status" value="suspended" />
                  <span class="radio-label">
                    <i class="fas fa-pause-circle" style="color: #f59e0b;"></i>
                    Suspendu
                  </span>
                </label>
                <label class="radio-item">
                  <input type="radio" v-model="form.status" value="blocked" />
                  <span class="radio-label">
                    <i class="fas fa-ban" style="color: #ef4444;"></i>
                    Bloqué
                  </span>
                </label>
              </div>

              <div v-if="isEditing" class="form-group">
                <label class="checkbox-item">
                  <input type="checkbox" v-model="form.reset_password" />
                  <span class="checkbox-label">
                    Forcer la réinitialisation du mot de passe à la prochaine connexion
                  </span>
                </label>
              </div>
            </div>

            <!-- Actions -->
            <div class="form-actions">
              <div class="actions-left">
                <button type="button" @click="resetForm" class="btn btn-outline btn-sm">
                  <i class="fas fa-undo"></i>
                  Réinitialiser
                </button>
              </div>

              <div class="actions-right">
                <button v-if="isEditing" type="button" @click="handleDeleteUser" class="btn btn-danger btn-sm"
                  :disabled="saving">
                  <i class="fas fa-trash"></i>
                  Supprimer
                </button>

                <button type="submit" class="btn btn-primary btn-sm" :disabled="saving || availableRoles.length === 0">
                  <i v-if="saving" class="fas fa-spinner fa-spin"></i>
                  <i v-else class="fas fa-check"></i>
                  {{ isEditing ? 'Mettre à jour' : 'Créer l\'utilisateur' }}
                </button>
              </div>
            </div>
          </div>
        </div>
      </form>
    </div>
  </div>
</template>

<script>
import { useUsersStore } from '@/shared/stores/users'
import { useAuthStore } from '@/shared/stores/auth'
import { mapState, mapActions } from 'pinia'

export default {
  name: 'UserForm',
  props: {
    action: { type: String, default: 'create' }
  },

  data() {
    return {
      form: {
        name: '',
        email: '',
        role_id: '',
        additional_roles: [],
        status: 'active',
        reset_password: false,
        password: '',
        password_confirmation: ''
      },

      saving: false,
      errors: {}
    }
  },

  computed: {
    ...mapState(useUsersStore, {
      loading: 'loading',
      error: 'error',
      availableRoles: 'availableRoles',
      currentUser: 'currentUser'
    }),

    isEditing() {
      return this.action === 'edit' && !!this.$route.params.id
    },

    userId() {
      return this.$route.params.id
    },

    user() {
      return this.currentUser
    }
  },

  setup() {
    const usersStore = useUsersStore()
    const authStore = useAuthStore()

    return {
      usersStore,
      authStore
    }
  },

  methods: {
    ...mapActions(useUsersStore, [
      'loadAllData',
      'fetchUserById',
      'createUser',
      'updateUser',
      'deleteUser',
      'clearMessages'
    ]),

    // 🚀 OPTIMISATION : Charger SEULEMENT les rôles (ultra rapide)
    async loadFormData() {
      try {
        console.log('🚀 Chargement rôles mode rapide...')

        // 🚀 rolesOnly=true : charge juste les rôles avec ?mode=light
        await this.loadAllData({ rolesOnly: true })

        console.log('✅ Rôles rapides chargés:', {
          count: this.availableRoles.length,
          time: 'ultra-rapide !'
        })

        if (this.availableRoles.length === 0) {
          console.warn('⚠️ Aucun rôle chargé')
        }

      } catch (error) {
        console.error('❌ Erreur chargement rôles:', error)
        this.usersStore.error = 'Impossible de charger les rôles'
      }
    },

    async loadUserForEditing() {
      if (!this.isEditing) return

      try {
        console.log(`🔄 Chargement utilisateur ${this.userId}...`)
        await this.fetchUserById(this.userId)

        if (this.user) {
          this.populateForm()
          console.log('✅ Formulaire rempli')
        } else {
          this.usersStore.error = 'Utilisateur introuvable'
        }

      } catch (error) {
        console.error('❌ Erreur chargement utilisateur:', error)
        this.usersStore.error = 'Impossible de charger l\'utilisateur'
      }
    },

    populateForm() {
      if (!this.user) return

      this.form = {
        name: this.user.name || '',
        email: this.user.email || '',
        role_id: this.user.role_id || '',
        additional_roles: this.user.roles?.map(role => String(role.id)) || [],
        status: this.user.status || 'active',
        reset_password: false,
        password: '',
        password_confirmation: ''
      }
    },

    async submitForm() {
      if (!this.validateForm()) return

      this.saving = true
      this.clearMessages()

      try {
        const payload = { ...this.form }

        if (!payload.password) delete payload.password
        if (!payload.password_confirmation) delete payload.password_confirmation

        if (this.isEditing) {
          await this.updateUser(this.userId, payload)
        } else {
          await this.createUser(payload)
        }

        this.$router.push({
          path: '/admin/users',
          query: {
            success: this.isEditing ? 'Utilisateur mis à jour' : 'Utilisateur créé'
          }
        })

      } catch (error) {
        console.error('❌ Erreur soumission:', error)
      } finally {
        this.saving = false
      }
    },

    async handleDeleteUser() {
      if (!this.user || !confirm(`Supprimer "${this.user.name}" ?`)) return

      try {
        await this.deleteUser(this.user.id)
        this.$router.push({
          path: '/admin/users',
          query: { success: 'Utilisateur supprimé' }
        })
      } catch (error) {
        console.error('❌ Erreur suppression:', error)
      }
    },

    resetForm() {
      this.form = {
        name: '',
        email: '',
        role_id: '',
        additional_roles: [],
        status: 'active',
        reset_password: false,
        password: '',
        password_confirmation: ''
      }
      this.errors = {}
    },

    validateForm() {
      const errors = {}

      if (!this.form.name.trim()) {
        errors.name = ['Le nom est obligatoire']
      }

      if (!this.form.email.trim()) {
        errors.email = ['L\'email est obligatoire']
      }

      if (!this.isEditing && !this.form.password) {
        errors.password = ['Le mot de passe est obligatoire']
      }

      if (this.form.password && this.form.password !== this.form.password_confirmation) {
        errors.password_confirmation = ['Confirmation incorrecte']
      }

      this.errors = errors
      return Object.keys(errors).length === 0
    }
  },

  // 🚀 OPTIMISATION : Chargement minimal et rapide
  async created() {
    console.log('🚀 UserForm optimisé - Chargement rapide...')

    // 🚀 Charger SEULEMENT les rôles (pas les users)
    await this.loadFormData()

    // Si édition, charger l'utilisateur
    if (this.isEditing) {
      await this.loadUserForEditing()
    }

    console.log('✅ UserForm prêt rapidement !')
  },

  watch: {
    '$route.params.id': {
      handler(newId, oldId) {
        if (newId !== oldId && this.isEditing) {
          this.loadUserForEditing()
        }
      }
    }
  },

  beforeUnmount() {
    this.clearMessages()
  }
}
</script>

<style scoped>

.loading-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 2rem;
  gap: 1rem;
}

.spinner {
  width: 40px;
  height: 40px;
  border: 4px solid #f3f4f6;
  border-top: 4px solid #3b82f6;
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  0% {
    transform: rotate(0deg);
  }

  100% {
    transform: rotate(360deg);
  }
}

.error-message .alert {
  background: #fef2f2;
  border: 1px solid #fecaca;
  color: #dc2626;
  padding: 1rem;
  border-radius: 6px;
  display: flex;
  align-items: center;
  justify-content: space-between;
}
</style>
