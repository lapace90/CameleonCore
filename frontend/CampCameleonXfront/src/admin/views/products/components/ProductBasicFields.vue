<template>
  <div class="basic-fields">
    <h3>Informations générales</h3>
    <div class="form-grid">
      <div class="form-group">
        <label class="form-label required">Nom</label>
        <input v-model="localValue.name" type="text" class="form-input" placeholder="Nom du produit" required
          :class="{ 'error': errors.name }" />
        <span v-if="errors.name" class="error-message">{{ errors.name }}</span>
      </div>

      <div class="form-group">
        <label class="form-label required">Prix</label>
        <div class="input-group">
          <input v-model.number="localValue.price" type="number" step="0.01" min="0" class="form-input"
            placeholder="0.00" required :class="{ 'error': errors.price }" />
          <span class="input-addon">€</span>
        </div>
        <span v-if="errors.price" class="error-message">{{ errors.price }}</span>
      </div>

      <div class="form-group">
        <label class="form-label">Statut</label>
        <div class="radio-group">
          <label class="radio-item">
            <input type="radio" v-model="localValue.status" :value="true" name="status" />
            <span class="radio-label">Actif</span>
          </label>
          <label class="radio-item">
            <input type="radio" v-model="localValue.status" :value="false" name="status" />
            <span class="radio-label">Inactif</span>
          </label>
        </div>
      </div>

      <!-- Catégories (pastilles cliquables, multi) -->
      <div class="form-group">
        <label class="form-label">Catégories</label>

        <!-- Liste des options en pastilles -->
        <div class="badges-container" style="gap:.5rem;">
          <button v-for="c in filteredCategories" :key="c.id" type="button" class="badge"
            :class="{ 'badge-active': selectedSet.has(c.id) }" @click="toggleCategory(c.id)"
            :aria-pressed="selectedSet.has(c.id)">
            <i class="fas fa-tag"></i> {{ c.name }}
          </button>
        </div>
        <!-- Actions -->
        <div class="flex text-center items-center gap-2 mt-2" style="border: 1px solid var(--sand); padding: 1rem .4rem; border-radius: 8px; ">
          <router-link class="btn btn-primary btn-sm" :to="categoriesManageTo" target="_blank"
            title="Gérer les catégories">
            <i class="fas fa-folder-tree"></i>
          </router-link>

          <button type="button" class="btn btn-light mx-2 btn-sm" @click="fetchCategories" :disabled="loadingCategories"
            title="Rafraîchir">
            <i class="fas fa-rotate"></i>
          </button>

          <button type="button" class="btn btn-light btn-sm" @click="clearCategories"
            :disabled="!selectedCategories.length" title="Vider">
            <i class="fas fa-xmark"></i>
          </button>
        </div>
        <!-- Résumé sélection (tes pastilles existantes) -->
        <div v-if="selectedCategories.length" class="badges-container mt-2">
          <span v-for="c in selectedCategories" :key="c.id" class="badge">
            <i class="fas fa-tag"></i> {{ c.name }}
          </span>
        </div>

        <span v-if="errors.category" class="error-message">{{ errors.category }}</span>
      </div>


      <div class="form-group full-width">
        <label class="form-label">Description</label>
        <textarea v-model="localValue.description" class="form-textarea" rows="3"
          placeholder="Description du produit..."></textarea>
      </div>
    </div>
  </div>
</template>

<script>
import ProductsApi from '@/services/ProductsApi'

// Cache module-scoped pour accélérer (un seul fetch pour toute la session)
let CACHED_CATEGORIES = null
let CATEGORIES_PROMISE = null

export default {
  name: 'ProductBasicFields',
  props: {
    type: { type: String, required: true },
    modelValue: { type: Object, default: () => ({}) },
    errors: { type: Object, default: () => ({}) },
    categoriesManageTo: { type: [String, Object], default: () => '/admin/categories' },
  },
  emits: ['update:modelValue'],
  data() {
    return {
      categories: [],
      loadingCategories: false,
    }
  },
  computed: {
    // Pont v-model (⚠️ pas de watch profond qui ré-émet)
    localValue: {
      get() { return this.modelValue },
      set(v) { this.$emit('update:modelValue', v) }
    },
    filteredCategories() {
      const map = { activity: 'Activity', menu: 'Menu', dish: 'Dish', room: 'Room', ingredient: 'Ingredient' }
      const wanted = map[String(this.type || '').toLowerCase()]
      return this.categories.filter(c => !wanted || c.type === wanted)
    },
    selectedSet() {
      const arr = Array.isArray(this.modelValue.categoryIds) ? this.modelValue.categoryIds : []
      return new Set(arr.map(n => Number(n)))
    },
    selectedCategories() {
      const set = this.selectedSet
      return this.filteredCategories.filter(c => set.has(Number(c.id)))
    }
  },
  mounted() {
    // Normalise le champ en array (compat ancien single)
    if (!Array.isArray(this.modelValue.categoryIds)) {
      const single = this.modelValue.categoryId != null ? Number(this.modelValue.categoryId) : null
      this.$emit('update:modelValue', { ...this.modelValue, categoryIds: single ? [single] : [] })
    }
    this.fetchCategories()
  },
  methods: {
    async fetchCategories() {
      this.loadingCategories = true
      try {
        if (CACHED_CATEGORIES) { this.categories = CACHED_CATEGORIES; return }
        if (!CATEGORIES_PROMISE) {
          CATEGORIES_PROMISE = ProductsApi.getCategories()
            .then(list => { CACHED_CATEGORIES = Array.isArray(list) ? list : []; return CACHED_CATEGORIES })
            .catch(err => { CATEGORIES_PROMISE = null; throw err })
        }
        this.categories = await CATEGORIES_PROMISE
      } catch (e) {
        console.error('fetchCategories error', e)
        this.categories = []
      } finally {
        this.loadingCategories = false
      }
    },

    toggleCategory(id) {
      const idNum = Number(id)
      const curr = Array.isArray(this.modelValue.categoryIds) ? this.modelValue.categoryIds.map(Number) : []
      const has = curr.includes(idNum)
      const next = has ? curr.filter(x => x !== idNum) : [...curr, idNum]
      // Émet un NOUVEL objet → pas de mutation en place, pas de boucle
      this.$emit('update:modelValue', { ...this.modelValue, categoryIds: next })
    },

    clearCategories() {
      if (!this.modelValue.categoryIds?.length) return
      this.$emit('update:modelValue', { ...this.modelValue, categoryIds: [] })
    }
  }
}
</script>
