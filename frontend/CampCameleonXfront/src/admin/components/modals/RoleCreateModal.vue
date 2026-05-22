<!-- frontend/CampCameleonXfront/src/admin/components/modals/RoleCreateModal.vue -->
<template>
  <div class="modal-overlay" @click.self="$emit('close')">
    <div class="modal-container modal-lg">
      <div class="modal-header">
        <h3>
          <AppIcon name="circle-plus" />
          Créer un nouveau rôle
        </h3>
        <button @click="$emit('close')" class="btn-close">
          <AppIcon name="x" />
        </button>
      </div>

      <form @submit.prevent="handleSubmit" class="modal-body">
        <!-- Informations du rôle -->
        <div class="form-section">
          <h4>
            <AppIcon name="info" />
            Informations générales
          </h4>

          <div class="form-group">
            <label for="name">Nom du rôle *</label>
            <input id="name" v-model="form.name" type="text" class="form-control" placeholder="Ex: Gestionnaire"
              :class="{ 'is-invalid': formErrors.name }" required />
            <div v-if="formErrors.name" class="invalid-feedback">{{ formErrors.name[0] }}</div>
          </div>

          <div class="form-group">
            <label for="slug">Identifiant (slug)</label>
            <input id="slug" v-model="form.slug" type="text" class="form-control" placeholder="Ex: manager"
              :class="{ 'is-invalid': formErrors.slug }" />
            <small class="form-text">Laissez vide pour génération automatique</small>
            <div v-if="formErrors.slug" class="invalid-feedback">{{ formErrors.slug[0] }}</div>
          </div>

          <div class="form-group">
            <label for="description">Description</label>
            <textarea id="description" v-model="form.description" class="form-control" rows="3"
              placeholder="Description du rôle et de ses responsabilités"
              :class="{ 'is-invalid': formErrors.description }"></textarea>
            <div v-if="formErrors.description" class="invalid-feedback">{{ formErrors.description[0] }}</div>
          </div>
        </div>

        <!-- Permissions -->
        <div class="form-section">
          <h4>
            <AppIcon name="key" />
            Permissions
            <span class="text-muted">({{ selectedPermissions.length }})</span>
          </h4>

          <!-- Loading des permissions -->
          <LoadingState v-if="loadingPermissions" state="loading" variant="inline"
            loading-text="Chargement des permissions..." />

          <!-- Erreur de chargement -->
          <div v-else-if="loadError" class="error-state">
            <AppIcon name="triangle-alert" />
            <p>Impossible de charger les permissions</p>
            <button type="button" @click="loadPermissions" class="btn btn-outline btn-sm">
              <AppIcon name="rotate-cw" />
              Réessayer
            </button>
          </div>

          <!-- Accordéon des permissions -->
          <div v-else-if="allPermissions.length > 0">
            <PermissionsAccordion :permissions="allPermissions" :selected-permissions="selectedPermissions"
              :original-permissions="originalPermissions" mode="editable" :show-actions="true"
              :default-open-categories="['users']" @update:selected-permissions="selectedPermissions = $event"
              @permission-changed="handlePermissionChanged" />
          </div>

          <!-- Aucune permission -->
          <div v-else class="form-note">
            <AppIcon name="info" />
            Aucune permission disponible.
          </div>
        </div>

        <!-- Actions -->
        <div class="modal-footer">
          <button type="button" @click="$emit('close')" class="btn btn-secondary btn-sm">
            Annuler
          </button>
          <button type="submit" class="btn btn-primary btn-sm" :disabled="!isFormValid || submitting">
            <AppIcon name="loader-circle" :spin="true" v-if="submitting" />
            <AppIcon name="save" v-else />
            {{ submitting ? 'Création...' : 'Créer le rôle' }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script>
import RolesApi from '@/services/RolesApi'
import PermissionsAccordion from '@/admin/components/ui/PermissionsAccordion.vue'
import LoadingState from '@/admin/components/ui/LoadingState.vue'

export default {
  name: 'RoleCreateModal',

  components: {
    PermissionsAccordion,
    LoadingState
  },

  emits: ['close', 'created'],

  data() {
    return {
      form: {
        name: '',
        slug: '',
        description: ''
      },
      selectedPermissions: [],
      originalPermissions: [], // vide pour création
      allPermissions: [],
      formErrors: {},
      submitting: false,
      loadingPermissions: false,
      loadError: false,
    }
  },

  computed: {
    isFormValid() {
      return (this.form.name || '').trim().length > 0
    },
  },

  async mounted() {
    await this.loadPermissions()
  },

  methods: {
    async loadPermissions() {
      this.loadingPermissions = true
      this.loadError = false
      try {
        const { useRolesStore } = await import('@/shared/stores/roles')
        const rolesStore = useRolesStore(this.$pinia)
        await rolesStore.ensurePermissions()

        // Conversion du format store vers format PermissionsAccordion
        this.allPermissions = this.flattenPermissions(rolesStore.availablePermissions.categories)

      } catch (e) {
        console.error('Erreur chargement permissions:', e)
        this.loadError = true
      } finally {
        this.loadingPermissions = false
      }
    },

    // Convertit le format { categories: [{ permissions: [] }] } vers un array plat
    flattenPermissions(categories) {
      const permissions = []
      categories.forEach(category => {
        category.permissions.forEach(permission => {
          permissions.push({
            ...permission,
            category: category.key || category.name
          })
        })
      })
      return permissions
    },

    handlePermissionChanged(event) {
      // Optionnel : logger les changements
      console.log('Permission changed:', event)
    },

    async handleSubmit() {
      if (!this.isFormValid || this.submitting) return

      this.submitting = true
      this.formErrors = {}

      try {
        const payload = {
          ...this.form,
          permissions: this.selectedPermissions
        }

        const response = await RolesApi.create(payload)

        this.$emit('created', response)
        this.$emit('close')
      } catch (error) {
        console.error('Erreur lors de la création:', error)

        if (error.response?.status === 422) {
          this.formErrors = error.response.data.errors || {}
        } else {
          alert('Erreur lors de la création du rôle')
        }
      } finally {
        this.submitting = false
      }
    },
  }
}
</script>