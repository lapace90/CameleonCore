<template>
  <div class="form-section" v-if="hasRelations">
    <h3>{{ getRelationTitle() }}</h3>

    <!-- ✅ SIMPLIFIER : Utiliser la même logique que ProductDetails -->

    <!-- Plats du menu (comme dans ProductDetails) -->
    <div v-if="menuDishes && menuDishes.length > 0" class="selection-area">
      <h4>
        <i class="fas fa-utensils" style="color: #f97316; margin-right: 8px;"></i>
        Plats du menu ({{ menuDishes.length }})
      </h4>
      <div class="current-relations">
        <div v-for="dish in menuDishes" :key="dish.id" class="relation-item">
          <div class="relation-header">
            <span class="relation-name">{{ dish.name || dish.product?.name }}</span>
            <span class="relation-price">{{ dish.formatted_price }}</span>
          </div>
          <button type="button" @click="removeDish(dish.id)" class="btn-remove" title="Retirer ce plat du menu">
            <i class="fas fa-times"></i>
          </button>
        </div>
      </div>
      <button type="button" @click="showAvailableDishes = !showAvailableDishes" class="btn btn-outline">
        <i class="fas fa-plus" style="margin-right: 6px;"></i>
        {{ showAvailableDishes ? 'Masquer les plats disponibles' : 'Ajouter des plats' }}
      </button>
    </div>

    <!-- Ajouter des plats disponibles -->
    <div v-if="showAvailableDishes && config.hasRelation === 'dishes'" class="selection-area">
      <h4>
        <i class="fas fa-search" style="color: #6b7280; margin-right: 8px;"></i>
        Plats disponibles
      </h4>
      <div class="search-box">
        <i class="fas fa-search"></i>
        <input v-model="searchTerm" type="text" placeholder="Rechercher un plat par nom..." />
      </div>
      <div class="available-items">
        <div v-for="dish in filteredAvailableDishes" :key="dish.id" class="selectable-item" @click="addDish(dish)"
          title="Cliquer pour ajouter ce plat au menu">
          <div class="item-info">
            <span class="item-name">{{ dish.name }}</span>
            <span class="item-price">{{ dish.formatted_price }}</span>
          </div>
          <button class="btn-add">
            <i class="fas fa-plus"></i>
          </button>
        </div>
        <div v-if="filteredAvailableDishes.length === 0" class="no-relations">
          <p>Aucun plat trouvé{{ searchTerm ? ` pour "${searchTerm}"` : '' }}</p>
        </div>
      </div>
    </div>

    <!-- Ingrédients du plat (comme dans ProductDetails) -->
    <div v-if="dishIngredients && dishIngredients.length > 0" class="selection-area">
      <h4>
        <i class="fas fa-seedling" style="color: #22c55e; margin-right: 8px;"></i>
        Ingrédients du plat ({{ dishIngredients.length }})
      </h4>
      <div class="current-relations">
        <div v-for="ingredient in dishIngredients" :key="ingredient.id" class="relation-item">
          <div class="relation-header">
            <span class="relation-name">{{ ingredient.name || ingredient.product?.name }}</span>
            <span class="relation-stock">Stock: {{ ingredient.stock || 'N/A' }}</span>
          </div>
          <button type="button" @click="removeIngredient(ingredient.id)" class="btn-remove"
            title="Retirer cet ingrédient du plat">
            <i class="fas fa-times"></i>
          </button>
        </div>
      </div>
      <button type="button" @click="showAvailableIngredients = !showAvailableIngredients" class="btn btn-outline">
        <i class="fas fa-plus" style="margin-right: 6px;"></i>
        {{ showAvailableIngredients ? 'Masquer les ingrédients disponibles' : 'Ajouter des ingrédients' }}
      </button>
    </div>

    <!-- Ajouter des ingrédients disponibles -->
    <div v-if="showAvailableIngredients && config.hasRelation === 'ingredients'" class="selection-area">
      <h4>
        <i class="fas fa-search" style="color: #6b7280; margin-right: 8px;"></i>
        Ingrédients disponibles
      </h4>
      <div class="search-box">
        <i class="fas fa-search"></i>
        <input v-model="searchTerm" type="text" placeholder="Rechercher un ingrédient par nom..." />
      </div>
      <div class="available-items">
        <div v-for="ingredient in filteredAvailableIngredients" :key="ingredient.id" class="selectable-item"
          @click="addIngredient(ingredient)" title="Cliquer pour ajouter cet ingrédient au plat">
          <div class="item-info">
            <span class="item-name">{{ ingredient.name }}</span>
            <span class="item-stock">Stock: {{ ingredient.stock || 'N/A' }}</span>
          </div>
          <button class="btn-add">
            <i class="fas fa-plus"></i>
          </button>
        </div>
        <div v-if="filteredAvailableIngredients.length === 0" class="no-relations">
          <p>Aucun ingrédient trouvé{{ searchTerm ? ` pour "${searchTerm}"` : '' }}</p>
        </div>
      </div>
    </div>

    <!-- Message si pas de relations -->
    <div v-if="!hasRelations" class="no-relations">
      <p>Aucune relation pour ce {{ config.singular?.toLowerCase() }}.</p>
    </div>
  </div>
</template>

<script>
import ProductsApi from '@/services/ProductsApi'

export default {
  name: 'ProductRelations',
  props: {
    type: { type: String, required: true },
    config: { type: Object, required: true },
    // Utiliser directement les relations du produit
    product: { type: Object, required: true },
    // Mode édition vs affichage
    editMode: { type: Boolean, default: false }
  },

  emits: ['relations-updated', 'relations-changed'],

  data() {
    return {
      searchTerm: '',
      showAvailableDishes: false,
      showAvailableIngredients: false,
      availableDishes: [],
      availableIngredients: [],
      // Relations locales pour l'édition
      localDishes: [],
      localIngredients: []
    }
  },

  mounted() {
    this.initializeLocalRelations()
  },

  computed: {
    // Utiliser les relations locales en mode édition
    hasRelations() {
      return this.menuDishes.length > 0 || this.dishIngredients.length > 0
    },

    menuDishes() {
      return this.editMode ? this.localDishes : (this.product.relations?.dishes || [])
    },

    dishIngredients() {
      return this.editMode ? this.localIngredients : (this.product.relations?.ingredients || [])
    },

    filteredAvailableDishes() {
      if (!this.searchTerm) return this.availableDishes
      return this.availableDishes.filter(dish =>
        dish.name.toLowerCase().includes(this.searchTerm.toLowerCase()) &&
        !this.localDishes.some(existing => existing.id === dish.id)
      )
    },

    filteredAvailableIngredients() {
      if (!this.searchTerm) return this.availableIngredients
      return this.availableIngredients.filter(ingredient =>
        ingredient.name.toLowerCase().includes(this.searchTerm.toLowerCase()) &&
        !this.localIngredients.some(existing => existing.id === ingredient.id)
      )
    }
  },

  methods: {
    getRelationTitle() {
      if (this.config.hasRelation === 'ingredients') return 'Ingrédients'
      if (this.config.hasRelation === 'dishes') {
        return this.type === 'ingredient' ? 'Plats utilisant cet ingrédient' : 'Plats du menu'
      }
      return 'Relations'
    },

    // ✅ AJOUTER : Initialiser les relations locales
    initializeLocalRelations() {
      this.localDishes = [...(this.product.relations?.dishes || [])]
      this.localIngredients = [...(this.product.relations?.ingredients || [])]
    },

    async loadAvailableDishes() {
      if (this.availableDishes.length > 0) return

      try {
        const { data } = await ProductsApi.getRelationProducts('dishes')
        this.availableDishes = data
      } catch (error) {
        console.error('Erreur lors du chargement des plats disponibles:', error)
      }
    },

    async loadAvailableIngredients() {
      if (this.availableIngredients.length > 0) return

      try {
        const { data } = await ProductsApi.getRelationProducts('ingredients')
        this.availableIngredients = data
      } catch (error) {
        console.error('Erreur lors du chargement des ingrédients disponibles:', error)
      }
    },

    // ✅ MODIFIER : Mode édition 
    async addDish(dish) {
      if (this.editMode) {
        // Mode édition : ajouter localement
        this.localDishes.push(dish)
        this.emitRelationsChanged()
      } 
      
    },

    async removeDish(dishId) {
      if (this.editMode) {
        // Mode édition : supprimer localement
        this.localDishes = this.localDishes.filter(d => d.id !== dishId)
        this.emitRelationsChanged()
      
      }
    },

    async addIngredient(ingredient) {
      if (this.editMode) {
        // Mode édition : ajouter localement
        this.localIngredients.push(ingredient)
        this.emitRelationsChanged()
      
      }
    },

    async removeIngredient(ingredientId) {
      if (this.editMode) {
        // Mode édition : supprimer localement
        this.localIngredients = this.localIngredients.filter(i => i.id !== ingredientId)
        this.emitRelationsChanged()
      } 
    },

    // Émettre les changements vers le parent
    emitRelationsChanged() {
      this.$emit('relations-changed', {
        dishes: this.localDishes.map(d => d.id),
        ingredients: this.localIngredients.map(i => i.id)
      })
    },

    // Méthode publique pour récupérer les relations
    getLocalRelations() {
      return {
        dishes: this.localDishes.map(d => d.id),
        ingredients: this.localIngredients.map(i => i.id)
      }
    }
  },

  watch: {
    showAvailableDishes(show) {
      if (show) this.loadAvailableDishes()
    },
    showAvailableIngredients(show) {
      if (show) this.loadAvailableIngredients()
    }
  }
}
</script>

<style scoped>


.form-section h4 {
  margin-bottom: 12px;
  color: #6b7280;
}

.selection-area {
  margin-bottom: 20px;
}

/* ✅ Styles pour les relations actuelles - comme ProductDetails */
.current-relations {
  display: flex;
  flex-direction: column;
  gap: 12px;
  margin-bottom: 16px;
}

.relation-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 16px;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  background-color: #ffffff;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  transition: all 0.2s ease;
}

.relation-item:hover {
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  border-color: #d1d5db;
}

.relation-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  width: 100%;
  margin-right: 12px;
}

.relation-name {
  font-weight: 600;
  color: #111827;
  font-size: 1rem;
}

.relation-price {
  font-weight: 600;
  color: #059669;
  font-size: 0.95rem;
  background-color: #ecfdf5;
  padding: 4px 8px;
  border-radius: 6px;
}

.relation-stock {
  font-weight: 500;
  color: #7c3aed;
  font-size: 0.875rem;
  background-color: #f3f4f6;
  padding: 4px 8px;
  border-radius: 6px;
}

/* ✅ Boutons d'action jolis */
.btn-remove {
  background: linear-gradient(135deg, #ef4444, #dc2626);
  color: white;
  border: none;
  border-radius: 6px;
  padding: 8px 12px;
  cursor: pointer;
  font-size: 12px;
  transition: all 0.2s ease;
  display: flex;
  align-items: center;
  gap: 4px;
}

.btn-remove:hover {
  background: linear-gradient(135deg, #dc2626, #b91c1c);
  transform: translateY(-1px);
}

.btn-add {
  background: linear-gradient(135deg, #10b981, #059669);
  color: white;
  border: none;
  border-radius: 6px;
  padding: 8px 12px;
  cursor: pointer;
  font-size: 12px;
  transition: all 0.2s ease;
  display: flex;
  align-items: center;
  gap: 4px;
}

.btn-add:hover {
  background: linear-gradient(135deg, #059669, #047857);
  transform: translateY(-1px);
}

/* ✅ Recherche élégante */
.search-box {
  position: relative;
  margin-bottom: 16px;
}


/* ✅ Liste des éléments disponibles */
.available-items {
  max-height: 320px;
  overflow-y: auto;
  border: 2px solid #e5e7eb;
  border-radius: 8px;
  background-color: #ffffff;
}

.selectable-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 16px;
  border-bottom: 1px solid #f3f4f6;
  cursor: pointer;
  transition: all 0.2s ease;
}

.selectable-item:hover {
  background-color: #f8fafc;
  border-left: 4px solid #3b82f6;
}

.selectable-item:last-child {
  border-bottom: none;
}

.item-info {
  display: flex;
  align-items: center;
  justify-content: space-between;
  width: 100%;
  margin-right: 12px;
}

.item-name {
  font-weight: 500;
  color: #111827;
  font-size: 0.95rem;
}

.item-price {
  font-weight: 500;
  color: #059669;
  font-size: 0.875rem;
  background-color: #ecfdf5;
  padding: 4px 8px;
  border-radius: 6px;
}

.item-stock {
  font-weight: 500;
  color: #7c3aed;
  font-size: 0.875rem;
  background-color: #f3f4f6;
  padding: 4px 8px;
  border-radius: 6px;
}

/* ✅ Message vide élégant */
.no-relations {
  text-align: center;
  padding: 32px;
  color: #6b7280;
  font-style: italic;
  background-color: #f9fafb;
  border: 2px dashed #d1d5db;
  border-radius: 8px;
}

/* ✅ Scrollbar personnalisée */
.available-items::-webkit-scrollbar {
  width: 6px;
}

.available-items::-webkit-scrollbar-track {
  background: #f1f5f9;
  border-radius: 3px;
}

.available-items::-webkit-scrollbar-thumb {
  background: #cbd5e1;
  border-radius: 3px;
}

.available-items::-webkit-scrollbar-thumb:hover {
  background: #94a3b8;
}

/* ✅ Animation d'entrée */
.relation-item {
  animation: slideIn 0.3s ease-out;
}

@keyframes slideIn {
  from {
    opacity: 0;
    transform: translateY(10px);
  }

  to {
    opacity: 1;
    transform: translateY(0);
  }
}
</style>