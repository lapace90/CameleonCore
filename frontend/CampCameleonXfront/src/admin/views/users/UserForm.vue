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
              <h3>Rôles et hiérarchie
                <small style="color: #6b7280;">({{ availableRoles.length }} disponible{{ availableRoles.length > 1 ? 's'
                  : '' }})</small>
              </h3>

              <!-- Interface unique et simple -->
              <div class="roles-smart-interface">
                <!-- Sélection des rôles -->
                <div class="form-group">
                  <label class="form-label">Rôles attribués</label>
                  <div class="roles-selection">
                    <div v-for="role in availableRoles" :key="role.id" class="role-item">
                      <!-- Checkbox pour sélectionner le rôle -->
                      <label class="checkbox-container">
                        <input type="checkbox" :value="role.id" v-model="selectedRoles"
                          @change="onRoleSelectionChange" />
                        <div class="checkbox-custom"></div>
                        <span class="role-info">
                          <strong>{{ role.name }}</strong>
                          <small v-if="role.description">{{ role.description }}</small>
                        </span>
                      </label>

                      <!-- Bouton radio pour le rôle principal (seulement si sélectionné) -->
                      <div v-if="selectedRoles.includes(role.id) || form.role_id == role.id" class="principal-selector">
                        <input type="radio" :value="role.id" v-model="form.role_id" :id="`principal-${role.id}`"
                          name="principal_role" @change="onPrincipalRoleChange" />
                        <label :for="`principal-${role.id}`" class="radio-label">
                          <i class="fas fa-crown"></i> Principal
                        </label>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Résumé automatique (seulement si des rôles sont sélectionnés) -->
                <div v-if="selectedRoles.length > 0" class="roles-summary">
                  <div class="summary-header">
                    <i class="fas fa-info-circle"></i>
                    <strong>Configuration actuelle</strong>
                  </div>

                  <div class="role-hierarchy">
                    <!-- Rôle principal -->
                    <div v-if="form.role_id" class="principal-role">
                      <i class="fas fa-crown text-warning"></i>
                      <span><strong>Principal :</strong> {{ user.role?.name || 'Rôle principal' }}</span>
                    </div>

                    <!-- Rôles additionnels -->
                    <div v-if="user.additional_roles && user.additional_roles.length > 0" class="additional-roles">
                      <i class="fas fa-users text-info"></i>
                      <span><strong>Additionnels :</strong>
                        {{user.additional_roles.map(role => role.name).join(', ')}}
                      </span>
                    </div>
                    <!-- Si aucun principal mais des rôles sélectionnés -->
                    <div v-if="selectedRoles.length > 1 && !form.role_id" class="needs-principal">
                      <i class="fas fa-exclamation-triangle text-warning"></i>
                      <span><strong>Action requise :</strong> Choisissez le rôle principal avec les boutons radio
                        jaunes</span>
                    </div>
                  </div>

                  <!-- Permissions preview -->
                  <div class="permissions-preview">
                    <small>
                      <i class="fas fa-key"></i>
                      Cet utilisateur aura environ <strong>{{ estimatedPermissions }}</strong> permission(s) via ses
                      rôles
                    </small>
                  </div>
                </div>

                <!-- Message d'aide contextuelle selon la situation -->
                <div v-if="selectedRoles.length === 0" class="help-message help-empty">
                  <i class="fas fa-hand-pointer"></i>
                  <span>Sélectionnez un ou plusieurs rôles pour cet utilisateur en cochant les cases</span>
                </div>

                <div v-else-if="selectedRoles.length === 1" class="help-message help-single">
                  <i class="fas fa-check-circle text-success"></i>
                  <span>Parfait ! Le rôle sélectionné est automatiquement défini comme <strong>rôle
                      principal</strong></span>
                </div>
              </div>

              <!-- Info RBAC -->
              <div class="form-info">
                <i class="fas fa-info-circle px-3" style="color: #0ea5e9;"></i>
                <strong>Architecture RBAC :</strong>
                <p>Les permissions sont gérées via les rôles. Un utilisateur peut avoir un rôle principal et plusieurs
                  rôles additionnels.</p>
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

      selectedRoles: [],

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
    additionalRolesComputed() {
      return this.selectedRoles.filter(id => id !== parseInt(this.form.role_id))
    },

    /**
     * ✅ NOUVELLE computed : Estimation du nombre de permissions
     */
    estimatedPermissions() {
      // Logique simplifiée - vous pouvez l'améliorer en comptant vraiment les permissions
      const basePermissions = this.selectedRoles.length * 8 // Estimation moyenne
      return Math.min(basePermissions, 49) // Max 49 permissions total

    },

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

    /**
  * ✅ NOUVELLE MÉTHODE : Gestion intelligente de la sélection des rôles
  */
    onRoleSelectionChange() {
      console.log('🔄 Changement sélection rôles:', this.selectedRoles)

      // Convertir en nombres pour éviter les problèmes de comparaison
      const roleIds = this.selectedRoles.map(id => parseInt(id))

      if (roleIds.length === 0) {
        // Cas 1: Aucun rôle sélectionné
        this.form.role_id = ''
        this.form.additional_roles = []

      } else if (roleIds.length === 1) {
        // Cas 2: Un seul rôle → automatiquement principal
        this.form.role_id = roleIds[0]
        this.form.additional_roles = []

        console.log('✅ Rôle unique défini comme principal:', roleIds[0])

      } else {
        // Cas 3: Plusieurs rôles → gérer principal + additionnels

        // Si pas de rôle principal ou si le principal n'est plus sélectionné
        if (!this.form.role_id || !roleIds.includes(parseInt(this.form.role_id))) {
          // Prendre le premier comme principal par défaut
          this.form.role_id = roleIds[0]
        }

        // Calculer les rôles additionnels
        this.form.additional_roles = roleIds
          .filter(id => id !== parseInt(this.form.role_id))
          .map(id => String(id)) // API attend des strings

        console.log('🔄 Rôles multiples:', {
          principal: this.form.role_id,
          additionnels: this.form.additional_roles
        })
      }

      this.validateRoleSelection()
    },

    /**
     * ✅ NOUVELLE MÉTHODE : Changement du rôle principal via radio
     */
    onPrincipalRoleChange() {
      console.log('👑 Changement rôle principal:', this.form.role_id)

      // Recalculer les rôles additionnels
      this.form.additional_roles = this.selectedRoles
        .filter(id => id !== parseInt(this.form.role_id))
        .map(id => String(id))

      console.log('✅ Nouveaux rôles additionnels:', this.form.additional_roles)
    },

    /**
     * ✅ NOUVELLE MÉTHODE : Support pour l'interface classique (fallback)
     */
    onLegacyRoleChange() {
      if (this.form.role_id) {
        // Ajouter le rôle principal aux rôles sélectionnés s'il n'y est pas
        if (!this.selectedRoles.includes(parseInt(this.form.role_id))) {
          this.selectedRoles.push(parseInt(this.form.role_id))
        }
      }
      this.onRoleSelectionChange()
    },

    /**
     * ✅ NOUVELLE MÉTHODE : Support pour les rôles additionnels classiques
     */
    onLegacyAdditionalChange() {
      // Synchroniser avec la nouvelle interface
      const allRoles = []

      if (this.form.role_id) {
        allRoles.push(parseInt(this.form.role_id))
      }

      this.form.additional_roles.forEach(id => {
        const roleId = parseInt(id)
        if (!allRoles.includes(roleId)) {
          allRoles.push(roleId)
        }
      })

      this.selectedRoles = allRoles
      this.onRoleSelectionChange()
    },

    /**
     * ✅ NOUVELLE MÉTHODE : Validation de la sélection
     */
    validateRoleSelection() {
      // Validation business logic
      if (this.selectedRoles.length > 5) {
        console.warn('⚠️ Plus de 5 rôles sélectionnés, performance impact possible')
      }

      // Nettoyer les doublons
      this.selectedRoles = [...new Set(this.selectedRoles)]
    },

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
        role_id: this.user.role?.id || '',
        additional_roles: this.getAdditionalRolesFromUser(),
        status: this.user.status || 'active',
        reset_password: false,
        password: '',
        password_confirmation: ''
      }

      this.syncSelectedRoles()
    },

    // Extraire les rôles additionnels selon le format API
    getAdditionalRolesFromUser() {
      // Essayer différents formats possibles de l'API
      if (this.user.additionalRoles && Array.isArray(this.user.additionalRoles)) {
        return this.user.additionalRoles.map(role => String(role.id))
      }

      if (this.user.additional_roles && Array.isArray(this.user.additional_roles)) {
        return this.user.additional_roles.map(role => String(role.id))
      }

      if (this.user.roles && Array.isArray(this.user.roles)) {
        return this.user.roles.map(role => String(role.id))
      }

      return []
    },

    /**
     * Synchroniser selectedRoles depuis le formulaire
     */
    syncSelectedRoles() {
      const allRoles = []

      // Ajouter le rôle principal
      if (this.form.role_id) {
        allRoles.push(parseInt(this.form.role_id))
      }

      // Ajouter les rôles additionnels
      if (this.form.additional_roles) {
        this.form.additional_roles.forEach(id => {
          const roleId = parseInt(id)
          if (!allRoles.includes(roleId)) {
            allRoles.push(roleId)
          }
        })
      }

      this.selectedRoles = allRoles

      console.log('🔄 Synchronisation selectedRoles:', {
        principal: this.form.role_id,
        additionnels: this.form.additional_roles,
        selected: this.selectedRoles
      })
    },


    /**
     *  Préparation des données pour l'API
     */
    async submitForm() {
      if (!this.validateForm()) return

      this.saving = true
      this.clearMessages()

      try {
        // ✅ S'assurer que les rôles additionnels sont synchronisés
        this.form.additional_roles = this.selectedRoles
          .filter(id => id !== parseInt(this.form.role_id))
          .map(id => String(id))

        const payload = { ...this.form }

        // Nettoyer les champs vides
        if (!payload.password) delete payload.password
        if (!payload.password_confirmation) delete payload.password_confirmation

        console.log('📤 Envoi payload:', payload)

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

    /**
     * Réinitialiser le formulaire
     */
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
      this.selectedRoles = []
      this.errors = {}
    },

    /**
     * Validation avec les nouveaux champs
     */
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

      //  Au moins un rôle requis
      if (this.selectedRoles.length === 0) {
        errors.roles = ['Au moins un rôle doit être sélectionné']
      }

      this.errors = errors
      return Object.keys(errors).length === 0
    },
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

.roles-smart-interface {
  background: #f8fafc;
  border-radius: 8px;
  padding: 1.5rem;
  margin-bottom: 1rem;
}

.role-item {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  padding: 1rem;
  background: white;
  border-radius: 6px;
  border: 1px solid #e5e7eb;
  margin-bottom: 0.75rem;
  transition: all 0.2s ease;
}

.role-item:hover {
  border-color: #3b82f6;
  box-shadow: 0 1px 3px rgba(59, 130, 246, 0.1);
}

.checkbox-container {
  display: flex;
  align-items: flex-start;
  gap: 0.75rem;
  cursor: pointer;
  flex: 1;
}

.checkbox-custom {
  width: 20px;
  height: 20px;
  border: 2px solid #d1d5db;
  border-radius: 4px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  margin-top: 2px;
}

.checkbox-container input[type="checkbox"] {
  display: none;
}

.checkbox-container input[type="checkbox"]:checked+.checkbox-custom {
  background: #3b82f6;
  border-color: #3b82f6;
}

.checkbox-container input[type="checkbox"]:checked+.checkbox-custom::after {
  content: '✓';
  color: white;
  font-size: 12px;
  font-weight: bold;
}

.role-info strong {
  display: block;
  color: #1f2937;
  font-weight: 600;
  margin-bottom: 2px;
}

.role-info small {
  color: #6b7280;
  font-size: 0.875rem;
  line-height: 1.3;
}

.principal-selector {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  flex-shrink: 0;
}

.principal-selector input[type="radio"] {
  display: none;
}

.radio-label {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem 1rem;
  background: #fef3c7;
  border: 1px solid #f59e0b;
  border-radius: 20px;
  color: #d97706;
  font-size: 0.875rem;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s ease;
}

.principal-selector input[type="radio"]:checked+.radio-label {
  background: #fbbf24;
  color: white;
}

.roles-summary {
  background: white;
  border: 1px solid #e5e7eb;
  border-radius: 6px;
  padding: 1rem;
  margin-top: 1rem;
}

.summary-header {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-bottom: 0.75rem;
  color: #374151;
}

.role-hierarchy>div {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-bottom: 0.5rem;
}

.permissions-preview {
  padding-top: 0.75rem;
  border-top: 1px solid #e5e7eb;
  margin-top: 0.75rem;
}

.context-help {
  margin-top: 1rem;
  padding: 0.75rem 1rem;
  border-radius: 6px;
}

.help-empty {
  background: #f3f4f6;
  border: 1px solid #d1d5db;
  color: #6b7280;
}

.help-single {
  background: #f0fdf4;
  border: 1px solid #bbf7d0;
  color: #15803d;
}

.help-multiple {
  background: #fffbeb;
  border: 1px solid #fed7aa;
  color: #c2410c;
}

.help-complete {
  background: #eff6ff;
  border: 1px solid #bfdbfe;
  color: #1d4ed8;
}

.context-help>div {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.advanced-mode {
  margin-top: 1rem;
  border: 1px solid #e5e7eb;
  border-radius: 6px;
  padding: 1rem;
}

.advanced-mode summary {
  cursor: pointer;
  font-weight: 500;
  color: #6b7280;
  padding: 0.5rem;
}

.text-success {
  color: #10b981;
}

.text-warning {
  color: #f59e0b;
}

.text-info {
  color: #3b82f6;
}

.text-primary {
  color: #6366f1;
}
</style>
