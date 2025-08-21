<template>
  <div class="form-section" v-if="config.hasRelation">
    <h3>{{ getRelationTitle() }}</h3>
    
    <!-- Loading -->
    <div v-if="loading" class="loading-relations">
      <i class="fas fa-spinner fa-spin"></i>
      Chargement...
    </div>

    <!-- Sélection d'ingrédients pour un plat -->
    <div v-else-if="config.hasRelation === 'ingredients'" class="selection-area">
      <div class="search-box">
        <i class="fas fa-search"></i>
        <input v-model="searchTerm" type="text" placeholder="Rechercher un ingrédient..." />
      </div>
      
      <div class="available-items">
        <div v-for="ingredient in filteredIngredients" :key="ingredient.id" 
          class="selectable-item" :class="{ 'selected': isSelected(ingredient.id) }">
          <label class="item-checkbox">
            <input type="checkbox" :value="ingredient.id" 
              :checked="isSelected(ingredient.id)" @change="toggleSelection(ingredient.id)" />
            <div class="item-info">
              <span class="item-name">{{ ingredient.name }}</span>
              <span class="item-price">{{ ingredient.formatted_price }}</span>
            </div>
          </label>
        </div>
      </div>
      
      <div class="selection-summary">
        {{ selectedCount }} ingrédient(s) sélectionné(s)
      </div>
    </div>

    <!-- Sélection de plats pour un menu -->
    <div v-else-if="config.hasRelation === 'dishes'" class="selection-area">
      <div class="search-box">
        <i class="fas fa-search"></i>
        <input v-model="searchTerm" type="text" placeholder="Rechercher un plat..." />
      </div>
      
      <div class="available-items">
        <div v-for="dish in filteredDishes" :key="dish.id" 
          class="selectable-item" :class="{ 'selected': isSelected(dish.id) }">
          <label class="item-checkbox">
            <input type="checkbox" :value="dish.id" 
              :checked="isSelected(dish.id)" @change="toggleSelection(dish.id)" />
            <div class="item-info">
              <span class="item-name">{{ dish.name }}</span>
              <span class="item-price">{{ dish.formatted_price }}</span>
            </div>
          </label>
        </div>
      </div>
      
      <div class="selection-summary">
        {{ selectedCount }} plat(s) sélectionné(s)
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios'

export default {
  name: 'ProductRelations',
  props: {
    type: { type: String, required: true },
    config: { type: Object, required: true },
    productId: { type: [String, Number], default: null },
    modelValue: { type: Object, default: () => ({}) }
  },

  emits: ['update:modelValue'],

  data() {
    return {
      loading: false,
      searchTerm: '',
      availableIngredients: [],
      availableDishes: []
    }
  },

  computed: {
    localValue: {
      get() {
        return this.modelValue
      },
      set(value) {
        this.$emit('update:modelValue', value)
      }
    },

    selectedIds() {
      if (this.config.hasRelation === 'ingredients') {
        return this.localValue.ingredients || []
      }
      if (this.config.hasRelation === 'dishes') {
        return this.localValue.dishes || []
      }
      return []
    },

    selectedCount() {
      return this.selectedIds.length
    },

    // ✅ CORRECTION : Ajout des computed manquants
    filteredIngredients() {
      if (!this.searchTerm) return this.availableIngredients
      return this.availableIngredients.filter(ingredient =>
        ingredient.name.toLowerCase().includes(this.searchTerm.toLowerCase())
      )
    },

    filteredDishes() {
      if (!this.searchTerm) return this.availableDishes
      return this.availableDishes.filter(dish =>
        dish.name.toLowerCase().includes(this.searchTerm.toLowerCase())
      )
    }
  },

  async mounted() {
    await this.loadRelationData()
  },

  methods: {
    getRelationTitle() {
      if (this.config.hasRelation === 'ingredients') return 'Ingrédients'
      if (this.config.hasRelation === 'dishes') {
        return this.type === 'ingredient' 
          ? 'Plats utilisant cet ingrédient' 
          : 'Plats du menu'
      }
      return 'Relations'
    },

    isSelected(id) {
      return this.selectedIds.includes(id)
    },

    toggleSelection(id) {
      const currentIds = [...this.selectedIds]
      const index = currentIds.indexOf(id)
      
      if (index > -1) {
        currentIds.splice(index, 1)
      } else {
        currentIds.push(id)
      }
      
      const relationKey = this.config.hasRelation
      this.localValue = {
        ...this.localValue,
        [relationKey]: currentIds
      }
    },

    // ✅ CORRECTION : Implémentation de loadRelationData
    async loadRelationData() {
      this.loading = true
      try {
        if (this.config.hasRelation === 'ingredients') {
          await this.loadIngredients()
        } else if (this.config.hasRelation === 'dishes') {
          await this.loadDishes()
        }
      } catch (error) {
        console.error('Erreur lors du chargement des relations:', error)
      } finally {
        this.loading = false
      }
    },

    async loadIngredients() {
      try {
        const response = await axios.get('/api/ingredients')
        this.availableIngredients = response.data.map(ingredient => ({
          ...ingredient,
          formatted_price: this.formatPrice(ingredient.price)
        }))
      } catch (error) {
        console.error('Erreur lors du chargement des ingrédients:', error)
      }
    },

    async loadDishes() {
      try {
        const response = await axios.get('/api/dishes')
        this.availableDishes = response.data.map(dish => ({
          ...dish,
          formatted_price: this.formatPrice(dish.price)
        }))
      } catch (error) {
        console.error('Erreur lors du chargement des plats:', error)
      }
    },

    formatPrice(price) {
      return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'EUR'
      }).format(price)
    }
  }
}
</script>

<style scoped>
.form-section {
  margin-bottom: 24px;
}

.form-section h3 {
  margin-bottom: 16px;
  color: #374151;
  font-weight: 600;
}

.loading-relations {
  text-align: center;
  padding: 20px;
  color: #6b7280;
}

.search-box {
  position: relative;
  margin-bottom: 16px;
}

.search-box i {
  position: absolute;
  left: 12px;
  top: 50%;
  transform: translateY(-50%);
  color: #6b7280;
}

.search-box input {
  width: 100%;
  padding: 10px 12px 10px 40px;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  font-size: 14px;
}

.search-box input:focus {
  outline: none;
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.available-items {
  max-height: 400px;
  overflow-y: auto;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  margin-bottom: 16px;
}

.selectable-item {
  border-bottom: 1px solid #e5e7eb;
  transition: background-color 0.2s;
}

.selectable-item:last-child {
  border-bottom: none;
}

.selectable-item:hover {
  background-color: #f9fafb;
}

.selectable-item.selected {
  background-color: #eff6ff;
  border-color: #3b82f6;
}

.item-checkbox {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px 16px;
  cursor: pointer;
  width: 100%;
}

.item-info {
  flex: 1;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.item-name {
  font-weight: 500;
  color: #111827;
}

.item-price {
  color: #059669;
  font-weight: 500;
  font-size: 14px;
}

.selection-summary {
  font-size: 14px;
  color: #6b7280;
  text-align: center;
  font-style: italic;
}
</style>