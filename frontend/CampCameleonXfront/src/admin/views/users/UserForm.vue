<template>
  <div class="product-form-container">
    <!-- Message d'erreur -->
    <div v-if="error" class="error-message">
      <div class="alert alert-danger">
        <i class="fas fa-exclamation-triangle"></i>
        {{ error }}
        <button @click="error = null" class="btn-close">&times;</button>
      </div>
    </div>

    <!-- Loading -->
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

      <!-- Formulaire principal -->
      <form @submit.prevent="submitForm" class="product-form">
        <div class="form-content">
          <!-- Avatar (optionnel) -->
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

          <!-- Informations utilisateur -->
          <div class="form-right">
            <div class="form-section">
              <h3>Informations générales</h3>
              <div class="form-grid">
                <div class="form-group">
                  <label class="form-label required">Nom complet</label>
                  <input v-model="form.name" type="text" class="form-input" placeholder="Nom et prénom" required
                    :class="{ 'error': errors.name }" />
                  <span v-if="errors.name" class="error-message">{{ errors.name }}</span>
                </div>

                <div class="form-group">
                  <label class="form-label required">Email</label>
                  <input v-model="form.email" type="email" class="form-input" placeholder="utilisateur@exemple.com"
                    required :class="{ 'error': errors.email }" />
                  <span v-if="errors.email" class="error-message">{{ errors.email }}</span>
                </div>

                <div class="form-group" v-if="!isEditing">
                  <label class="form-label required">Mot de passe</label>
                  <input v-model="form.password" type="password" class="form-input" placeholder="••••••••" required
                    :class="{ 'error': errors.password }" />
                  <span v-if="errors.password" class="error-message">{{ errors.password }}</span>
                </div>

                <div class="form-group" v-if="!isEditing">
                  <label class="form-label required">Confirmer le mot de passe</label>
                  <input v-model="form.password_confirmation" type="password" class="form-input" placeholder="••••••••"
                    required />
                </div>
              </div>
            </div>

            <!-- Attribution des rôles -->
            <div class="form-section">
              <h3>Rôles</h3>

              <div class="form-group">
                <label class="form-label">Rôle principal</label>
                <select v-model="form.role_id" class="form-input">
                  <option value="">Sélectionner un rôle</option>
                  <option v-for="role in roles" :key="role.id" :value="role.id">
                    {{ role.name }}
                  </option>
                </select>
              </div>

              <!-- Rôles multiples -->
              <div class="form-group" v-if="roles.length > 0">
                <label class="form-label">Rôles additionnels</label>
                <div class="roles-grid">
                  <label v-for="role in roles" :key="role.id" class="role-checkbox">
                    <input type="checkbox" :value="role.id" v-model="form.additional_roles" />
                    <div class="checkbox-content">
                      <div class="role-info">
                        <span class="role-name">{{ role.name }}</span>
                        <span class="role-description">{{ role.description }}</span>
                      </div>
                    </div>
                  </label>
                </div>
              </div>
            </div>

            <!-- Statut -->
            <div class="form-section">
              <h3>Statut du compte</h3>
              <div class="form-group">
                <label class="form-label">État</label>
                <div class="radio-group">
                  <label class="radio-item">
                    <input type="radio" v-model="form.status" value="active" name="status" />
                    <span class="radio-label">
                      <i class="fas fa-check-circle text-success"></i>
                      Actif
                    </span>
                  </label>
                  <label class="radio-item">
                    <input type="radio" v-model="form.status" value="inactive" name="status" />
                    <span class="radio-label">
                      <i class="fas fa-pause-circle text-warning"></i>
                      Suspendu
                    </span>
                  </label>
                  <label class="radio-item">
                    <input type="radio" v-model="form.status" value="blocked" name="status" />
                    <span class="radio-label">
                      <i class="fas fa-ban text-danger"></i>
                      Bloqué
                    </span>
                  </label>
                </div>
              </div>

              <div class="form-group" v-if="isEditing">
                <label class="form-label">
                  <input type="checkbox" v-model="form.reset_password" />
                  Forcer la réinitialisation du mot de passe à la prochaine connexion
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
                <button v-if="isEditing" type="button" @click="deleteUser" class="btn btn-danger btn-sm"
                  :disabled="saving">
                  <i class="fas fa-trash"></i>
                  Supprimer
                </button>

                <button type="submit" class="btn btn-primary btn-sm" :disabled="saving">
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
// 🚀 OPTIMISATION : Utiliser les stores au lieu d'appels UsersApi directs
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
      // Formulaire (garde en local pour la réactivité)
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
      
      // États UI (garde en local)
      saving: false,
      errors: {}
    }
  },

  // 🚀 OPTIMISATION : Computed depuis le store
  computed: {
    // Map store state
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

    // 🚀 User data depuis le store
    user() {
      return this.currentUser
    },

    // Aliases pour compatibilité template
    roles() {
      return this.availableRoles
    }
  },

  // 🚀 OPTIMISATION : Setup pour les stores
  setup() {
    const usersStore = useUsersStore()
    const authStore = useAuthStore()
    
    return {
      usersStore,
      authStore
    }
  },

  // 🚀 OPTIMISATION : Méthodes du store
  methods: {
    ...mapActions(useUsersStore, [
      'loadAllData',
      'fetchUserById', 
      'createUser',
      'updateUser',
      'deleteUser',
      'clearMessages'
    ]),

    // 🚀 OPTIMISATION : Charger données via store avec cache
    async loadFormData() {
      try {
        // 🚀 UNE SEULE méthode charge tout avec cache intelligent
        await this.loadAllData()
        
        console.log('✅ Rôles chargés depuis store:', {
          roles: this.availableRoles.length,
        })
        
      } catch (error) {
        console.error('Erreur lors du chargement des données:', error)
        this.usersStore.error = 'Impossible de charger les rôles'
      }
    },

    // 🚀 OPTIMISATION : Charger user via store avec cache
    async loadUserForEditing() {
      if (!this.isEditing) return

      try {
        // 🚀 Utiliser le store avec cache - pas de requête si déjà en cache
        await this.fetchUserById(this.userId)
        
        // Remplir le formulaire avec les données du store
        if (this.user) {
          this.populateForm()
        } else {
          this.usersStore.error = 'Utilisateur introuvable'
        }
        
      } catch (error) {
        console.error('Erreur lors du chargement de l\'utilisateur:', error)
        this.usersStore.error = 'Impossible de charger les données utilisateur'
      }
    },

    // 🚀 NOUVELLE : Populer le formulaire depuis les données store
    populateForm() {
      if (!this.user) return

      this.form = {
        name: this.user.name || '',
        email: this.user.email || '',
        role_id: this.user.role_id || '',
        additional_roles: this.user.roles?.map(role => role.id) || [],
        status: this.user.status || 'active',
        reset_password: false,
        password: '',
        password_confirmation: ''
      }

      console.log('✅ Formulaire peuplé depuis store:', this.form)
    },

    // 🚀 OPTIMISATION : Submit via store
    async submitForm() {
      this.saving = true
      this.errors = {}
      
      // Clear messages précédents
      this.clearMessages()

      try {
        const payload = { ...this.form }

        // Si on édite, on ne envoie le mot de passe que s'il est rempli
        if (this.isEditing && !payload.password) {
          delete payload.password
          delete payload.password_confirmation
        }

        let result
        if (this.isEditing) {
          // 🚀 Utiliser le store au lieu d'UsersApi direct
          result = await this.updateUser(this.userId, payload)
          console.log('✅ Utilisateur mis à jour via store')
        } else {
          // 🚀 Utiliser le store au lieu d'UsersApi direct  
          result = await this.createUser(payload)
          console.log('✅ Utilisateur créé via store')
        }

        // Message de succès et redirection
        const successMessage = this.isEditing 
          ? 'Utilisateur modifié avec succès' 
          : 'Utilisateur créé avec succès'

        this.$router.push({
          path: '/admin/users',
          query: { success: successMessage }
        })

      } catch (error) {
        console.error('Erreur lors de la sauvegarde:', error)

        // Gestion des erreurs de validation Laravel
        if (error.response?.status === 422 && error.response.data.errors) {
          this.errors = error.response.data.errors
        } else {
          // L'erreur est déjà dans le store, mais on peut aussi l'afficher localement
          this.errors = {
            general: error.message || 'Erreur lors de la sauvegarde'
          }
        }
      } finally {
        this.saving = false
      }
    },

    // 🚀 OPTIMISATION : Delete via store
    async deleteUser() {
      if (!this.isEditing || !this.user) return
      
      if (!confirm('Êtes-vous sûr de vouloir supprimer cet utilisateur ?')) {
        return
      }

      try {
        // 🚀 Utiliser le store
        await this.deleteUser(this.user.id)
        
        // Redirection avec message
        this.$router.push({
          path: '/admin/users',
          query: { success: 'Utilisateur supprimé avec succès' }
        })

      } catch (error) {
        console.error('Erreur lors de la suppression:', error)
        alert('Impossible de supprimer l\'utilisateur')
      }
    },

    // Reset form
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

    // Validate form locale 
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
        errors.password_confirmation = ['La confirmation ne correspond pas']
      }
      
      this.errors = errors
      return Object.keys(errors).length === 0
    },

    // UI Helpers (garde existants)
    handleCancel() {
      this.$router.push('/admin/users')
    }
  },

  // 🚀 OPTIMISATION : Lifecycle hooks
  async created() {
    // 🚀 Charger les données du formulaire (roles avec cache)
    await this.loadFormData()
    
    // 🚀 Si édition, charger l'utilisateur (avec cache)
    if (this.isEditing) {
      await this.loadUserForEditing()
    }
    
    console.log('✅ UserForm initialisé avec cache intelligent')
  },

  // 🚀 Watch pour réagir aux changements de route
  watch: {
    '$route.params.id': {
      immediate: false,
      handler(newId, oldId) {
        if (newId !== oldId && this.isEditing) {
          this.loadUserForEditing()
        }
      }
    }
  },

  // 🚀 Cleanup
  beforeUnmount() {
    this.clearMessages()
  }
}
</script>
