<!-- src/admin/views/Profile.vue -->
<template>
  <div class="product-form-container">
    <!-- Header -->
    <div class="form-header">
      <div class="header-navigation mb-4">
        <router-link to="/admin/dashboard" class="back-link">
          <i class="fas fa-arrow-left"></i>
          Retour au tableau de bord
        </router-link>

      </div>
    </div>

    <!-- Titre + actions -->
    <div class="page-title-section">
      <div class="product-type-badge" style="background-color:#7c3aed;">
        <i class="fas fa-user-circle"></i> Profil
      </div>
      <h1 class="page-title">Mon profil</h1>

      <div class="header-actions">
        <BaseButton v-if="!isEditing" variant="primary" @click="enableEditing">
          <i class="fas fa-edit pr-1"></i> Modifier
        </BaseButton>

        <div v-else class="edit-actions ">

          <BaseButton variant="outline" @click="cancelEditing">
            Annuler
          </BaseButton>

          <BaseButton variant="primary" :loading="saving" @click="saveProfile">
            <i class="fas fa-save pr-1"></i> Enregistrer
          </BaseButton>
        </div>
      </div>
    </div>

    <!-- Messages -->
    <div v-if="successMessage" class="alert alert-success">
      <i class="fas fa-check-circle"></i>
      {{ successMessage }}
      <button @click="successMessage = ''" class="btn-close">&times;</button>
    </div>
    <div v-if="errorMessage" class="alert alert-danger">
      <i class="fas fa-exclamation-triangle"></i>
      {{ errorMessage }}
      <button @click="errorMessage = ''" class="btn-close">&times;</button>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="loading-state">
      <div class="spinner"></div>
      <p>Chargement du profil...</p>
    </div>

    <!-- Formulaire principal -->
    <form v-else class="product-form" @submit.prevent="saveProfile">
      <div class="form-content">
        <!-- Colonne gauche -->
        <aside class="form-left">
          <div class="sticky-col">
            <BaseCard title="Photo de profil">
              <template #actions>
                <BaseButton v-if="isEditing" variant="outline" size="sm" @click="$refs.avatarUpload.openFilePicker()">
                  <i class="fas fa-camera"></i> Changer
                </BaseButton>
              </template>

              <div class="avatar-section">
                <ImageUpload ref="avatarUpload" v-model="form.avatar" shape="circle" size="xl"
                  :placeholder-text="form.name || 'Votre avatar'" placeholder-icon="fas fa-user" :allow-remove="true"
                  :disabled="!isEditing" help="Formats acceptés: JPG/PNG/GIF (max 2MB)"
                  @upload-error="handleUploadError" />
              </div>
            </BaseCard>
          </div>

          <!-- Infos complémentaires SOUS l'image -->
          <BaseCard title="Informations complémentaires" icon="fas fa-info-circle">
            <div class="form-grid">
              <div class="form-group form-full-width">
                <label class="form-label">Adresse</label>
                <BaseInput v-model="form.address" placeholder="Votre adresse" :disabled="!isEditing"
                  icon="fas fa-map-marker-alt" />
                <label class="form-label">Ville</label>
                <BaseInput v-model="form.city" placeholder="Votre ville" :disabled="!isEditing" />
                <label class="form-label">Code postal</label>
                <BaseInput v-model="form.postalCode" placeholder="Code postal" :disabled="!isEditing" />
              </div>
            </div>
          </BaseCard>
        </aside>

        <!-- Colonne droite -->
        <section class="form-right">
          <div class="card-stack">
            <BaseCard title="Informations personnelles" icon="fas fa-id-card">
              <div class="form-grid">
                <div class="form-group form-full-width">
                  <label class="form-label">Nom complet *</label>
                  <BaseInput v-model="form.name" placeholder="Votre nom complet" :disabled="!isEditing"
                    :error="errors.name" required />
                </div>

                <div class="form-group form-full-width">
                  <label class="form-label">Email *</label>
                  <BaseInput v-model="form.email" type="email" placeholder="votre.email@exemple.com"
                    :disabled="!isEditing" :error="errors.email" icon="fas fa-envelope" required />
                </div>

                <div class="form-group form-full-width">
                  <label class="form-label">Téléphone</label>
                  <BaseInput v-model="form.phone" type="tel" placeholder="+33 6 XX XX XX XX" :disabled="!isEditing"
                    :error="errors.phone" icon="fas fa-phone" />
                </div>
              </div>
            </BaseCard>

            <BaseCard title="Sécurité du compte" icon="fas fa-key">
              <div class="form-grid">
                <div class="form-group form-full-width">
                  <label class="form-label">Mot de passe actuel</label>
                  <BaseInput v-model="passwordForm.current" type="password" placeholder="Mot de passe actuel"
                    :error="passwordErrors.current" :disabled="!isEditing" />
                </div>

                <div class="form-group">
                  <label class="form-label">Nouveau mot de passe</label>
                  <BaseInput v-model="passwordForm.new" type="password" placeholder="Nouveau mot de passe"
                    :error="passwordErrors.new" :disabled="!isEditing" />
                </div>

                <div class="form-group">
                  <label class="form-label">Confirmer le mot de passe</label>
                  <BaseInput v-model="passwordForm.confirm" type="password" placeholder="Confirmer le mot de passe"
                    :error="passwordErrors.confirm" :disabled="!isEditing" />
                </div>

                <div class="form-group form-full-width">
                  <BaseButton variant="warning" type="button" @click="changePassword" :loading="changingPassword"
                    :disabled="!isEditing || !passwordForm.current || !passwordForm.new || !passwordForm.confirm">
                    <i class="fas fa-key"></i> Changer le mot de passe
                  </BaseButton>
                </div>
              </div>
            </BaseCard>

            <div class="form-section">
              <div class="form-actions">
                <BaseButton variant="outline" type="button" @click="cancelEditing" :disabled="saving">
                  <i class="fas fa-undo"></i> Annuler les modifications
                </BaseButton>
                <BaseButton variant="primary" type="submit" :loading="saving" :disabled="saving || !isEditing">
                  <i class="fas fa-save"></i>
                  {{ saving ? 'Enregistrement...' : 'Enregistrer' }}
                </BaseButton>
              </div>
            </div>
          </div>
        </section>
      </div>

    </form>
  </div>
</template>

<script>
import { ref, reactive, computed, onMounted } from 'vue'
import { useAuthStore } from '@/shared/stores/auth'
import BaseButton from '@/shared/components/ui/BaseButton.vue'
import BaseInput from '@/shared/components/ui/BaseInput.vue'
import BaseCard from '@/shared/components/ui/BaseCard.vue'
import ImageUpload from '@/admin/components/ui/ImageUpload.vue'

export default {
  name: 'ProfileView',
  components: { BaseButton, BaseInput, BaseCard, ImageUpload },

  setup() {
    const authStore = useAuthStore()

    // états UI
    const loading = ref(true)
    const isEditing = ref(false)
    const saving = ref(false)
    const changingPassword = ref(false)

    // messages
    const successMessage = ref('')
    const errorMessage = ref('')

    // source de vérité côté store
    const user = computed(() => authStore.user || {})
    const originalData = ref({})

    // form local (cohérent avec tes champs)
    const form = reactive({
      name: '',
      email: '',
      phone: '',
      address: '',
      city: '',
      postalCode: '',
      avatar: null
    })

    const errors = reactive({ name: '', email: '', phone: '' })

    // form mot de passe (séparé)
    const passwordForm = reactive({ current: '', new: '', confirm: '' })
    const passwordErrors = reactive({ current: '', new: '', confirm: '' })

    // hydrate depuis le store
    const loadUserData = () => {
      console.log('🔍 user.value dans Profile:', user.value)
      console.log('🔍 user.value.postal_code:', user.value?.postal_code)
      form.name = user.value?.name || ''
      form.email = user.value?.email || ''
      form.phone = user.value?.phone || ''
      form.address = user.value?.address || ''
      form.city = user.value?.city || ''
      form.postalCode = user.value?.postal_code || ''
      form.avatar = user.value?.avatar || null
      console.log('🔍 form après assignation:', form)
      originalData.value = { ...form }
      loading.value = false
    }

    // UI helpers
    const enableEditing = () => { isEditing.value = true; clearMessages() }
    const cancelEditing = () => {
      Object.assign(form, originalData.value)
      isEditing.value = false
      clearErrors(); clearMessages()
    }
    const clearErrors = () => { errors.name = ''; errors.email = ''; errors.phone = '' }
    const clearMessages = () => { successMessage.value = ''; errorMessage.value = '' }

    // validations
    const validateForm = () => {
      clearErrors()
      let ok = true
      if (!form.name.trim()) { errors.name = 'Le nom est requis'; ok = false }
      if (!form.email.trim()) { errors.email = "L'email est requis"; ok = false }
      else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.email)) { errors.email = 'Format email invalide'; ok = false }
      if (form.phone && !/^[\+]?[\d\s\-\(\)]{8,}$/.test(form.phone)) { errors.phone = 'Format téléphone invalide'; ok = false }
      return ok
    }

    // sauvegarde profil via store
    const saveProfile = async () => {
      if (!validateForm()) return
      saving.value = true
      clearMessages()
      try {
        const payload = {
          name: form.name,
          email: form.email,
          phone: form.phone,
          address: form.address,
          city: form.city,
          // ✅ CORRECTION : envoyer postal_code (snake_case) 
          postal_code: form.postalCode,
          avatar: form.avatar,
        }
        await authStore.updateProfile(payload)
        // nouvelle base de comparaison
        originalData.value = {
          ...form,
          // ✅ Synchroniser avec la réponse du serveur
          postalCode: form.postalCode
        }
        isEditing.value = false
        successMessage.value = 'Profil mis à jour avec succès !'
        setTimeout(() => (successMessage.value = ''), 5000)
      } catch (e) {
        errorMessage.value = e?.message || 'Erreur lors de la sauvegarde'
      } finally {
        saving.value = false
      }
    }

    // mot de passe via store
    const validatePasswordForm = () => {
      passwordErrors.current = ''
      passwordErrors.new = ''
      passwordErrors.confirm = ''
      if (!passwordForm.current) { passwordErrors.current = 'Mot de passe actuel requis'; return false }
      if (!passwordForm.new) { passwordErrors.new = 'Nouveau mot de passe requis'; return false }
      if (passwordForm.new.length < 8) { passwordErrors.new = '8 caractères minimum'; return false }
      if (passwordForm.new !== passwordForm.confirm) { passwordErrors.confirm = 'Ne correspond pas'; return false }
      return true
    }

    const changePassword = async () => {
      if (!validatePasswordForm()) return
      changingPassword.value = true
      clearMessages()
      try {
        await authStore.changePassword({ current: passwordForm.current, new: passwordForm.new })
        passwordForm.current = ''
        passwordForm.new = ''
        passwordForm.confirm = ''
        successMessage.value = 'Mot de passe changé avec succès !'
        setTimeout(() => (successMessage.value = ''), 5000)
      } catch (e) {
        passwordErrors.current = e?.message || 'Erreur lors du changement de mot de passe'
      } finally {
        changingPassword.value = false
      }
    }

    const handleUploadError = (msg) => { errorMessage.value = msg }

    onMounted(loadUserData)

    return {
      // états
      loading, isEditing, saving, changingPassword,
      successMessage, errorMessage,

      // data
      form, errors, passwordForm, passwordErrors,

      // actions
      enableEditing, cancelEditing, saveProfile, changePassword,
      handleUploadError, clearMessages
    }
  }
}
</script>
<style scoped>
.product-form .form-content {
  display: grid;
  grid-template-columns: 340px minmax(0, 1fr);
  gap: 1.25rem;
  align-items: start;
}

.form-left .sticky-col {
  position: sticky;
  top: 1rem;
}

.form-right .card-stack {
  display: grid;
  grid-auto-rows: min-content;
  gap: 1rem;
}

/* Grille interne des cards */
.form-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 0.75rem 1rem;
}

.form-grid .form-full-width {
  grid-column: 1 / -1;
}

/* Optionnel : harmonise les espacements si BaseCard n'ajoute pas déjà */
.base-card+.base-card,
.card-stack>*+* {
  margin-top: 0;
  /* évite les doubles marges si BaseCard en a déjà */
}

/* Responsive */
@media (max-width: 992px) {
  .product-form .form-content {
    grid-template-columns: 1fr;
  }

  .form-left .sticky-col {
    position: static;
  }

  .form-grid {
    grid-template-columns: 1fr;
  }
}
</style>