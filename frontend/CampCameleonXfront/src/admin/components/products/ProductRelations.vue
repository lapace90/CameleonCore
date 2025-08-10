<template>
  <div class="form-section">
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
    productId: { type: [String, Number], required: true },
    modelValue: { type: Object, default: () => ({}) }
  },

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

  async created() {
    await this.fetchRelationData()
  },

  methods: {
    async fetchRelationData() {
      this.loading = true
      
      try {
        if (this.config.hasRelation === 'ingredients') {
          const response = await axios.get('/api/products?type=App%5CModels%5CIngredient')
          this.availableIngredients = response.data.member || []
        } else if (this.config.hasRelation === 'dishes') {
          const response = await axios.get('/api/products?type=App%5CModels%5CDish')
          this.availableDishes = response.data.member || []
        }
      } catch (error) {
        console.error('Erreur lors du chargement des relations:', error)
      } finally {
        this.loading = false
      }
    },

    getRelationTitle() {
      if (this.config.hasRelation === 'ingredients') {
        return 'Ingrédients du plat'
      }
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
    }
  }
}
</script>

<style scoped>
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
  justify-content: between;
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