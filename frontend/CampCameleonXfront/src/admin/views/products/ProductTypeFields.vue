<template>
  <div class="form-section" v-if="config.fields && config.fields.length > 0">
    <h3>{{ sectionTitle }}</h3>
    
    <div class="form-grid">
      <!-- Champs pour Activity -->
      <template v-if="type === 'activity'">
        <div class="form-group">
          <label class="form-label">Guide</label>
          <input 
            type="text" 
            class="form-input" 
            v-model="localValue.guide"
            placeholder="Nom du guide"
          />
        </div>
        
        <div class="form-group">
          <label class="form-label">Durée (minutes)</label>
          <input 
            type="number" 
            class="form-input" 
            v-model.number="localValue.duration"
            placeholder="120"
            min="0"
          />
        </div>
        
        <div class="form-group">
          <label class="form-label">Point de rendez-vous</label>
          <input 
            type="text" 
            class="form-input" 
            v-model="localValue.meeting_point"
            placeholder="Lieu de rendez-vous"
          />
        </div>
        
        <div class="form-group">
          <label class="form-label">Nombre max de personnes</label>
          <input 
            type="number" 
            class="form-input" 
            v-model.number="localValue.max_people"
            placeholder="10"
            min="1"
          />
        </div>
        
        <div class="form-group">
          <label class="form-label">Niveau de difficulté</label>
          <select class="form-input" v-model="difficultyProxy">
            <option value="">Sélectionner...</option>
            <option value="easy">Facile</option>
            <option value="medium">Moyen</option>
            <option value="hard">Difficile</option>
          </select>
        </div>
      </template>
      
      <!-- Champs pour Room -->
      <template v-if="type === 'room'">
        <div class="form-group">
          <label class="form-label">Capacité</label>
          <input 
            type="number" 
            class="form-input" 
            v-model.number="localValue.capacity"
            placeholder="4"
            min="1"
          />
        </div>
        
        <div class="form-group full-width">
          <label class="form-label">
            <input 
              type="checkbox" 
              v-model="localValue.availability"
              class="form-checkbox"
            />
            Disponible
          </label>
        </div>
      </template>
      
      <!-- Champs pour Dish/Ingredient -->
      <template v-if="['dish', 'ingredient'].includes(type)">
        <div class="form-group full-width">
          <label class="form-label">Propriétés alimentaires</label>
          <div class="checkbox-grid">
            <label class="checkbox-item">
              <input type="checkbox" v-model="localValue.is_vegetarian" />
              <span>Végétarien</span>
            </label>
            <label class="checkbox-item">
              <input type="checkbox" v-model="localValue.is_vegan" />
              <span>Végan</span>
            </label>
            <label class="checkbox-item">
              <input type="checkbox" v-model="localValue.is_spicy" />
              <span>Épicé</span>
            </label>
            <label class="checkbox-item">
              <input type="checkbox" v-model="localValue.is_gluten_free" />
              <span>Sans gluten</span>
            </label>
            <label class="checkbox-item">
              <input type="checkbox" v-model="localValue.is_lactose_free" />
              <span>Sans lactose</span>
            </label>
            <label class="checkbox-item">
              <input type="checkbox" v-model="localValue.is_nut_free" />
              <span>Sans noix</span>
            </label>
          </div>
        </div>
      </template>
      
      <!-- Pour Menu - pas de champs spécifiques pour l'instant -->
      <template v-if="type === 'menu'">
        <div class="form-note">
          <i class="fas fa-info-circle"></i>
          Les plats du menu peuvent être ajoutés via l'onglet Relations.
        </div>
      </template>
    </div>
  </div>
</template>

<script>
export default {
  name: 'ProductTypeFields',
  props: {
    type: { type: String, required: true },
    config: { type: Object, required: true },
    modelValue: { type: Object, default: () => ({}) }
  },

  emits: ['update:modelValue'],

  computed: {
    localValue: {
      get() {
        return this.modelValue || {}
      },
      set(value) {
        this.$emit('update:modelValue', value)
      }
    },

    sectionTitle() {
      const titles = {
        activity: 'Détails de l\'activité',
        room: 'Détails de l\'hébergement',
        dish: 'Propriétés du plat',
        ingredient: 'Propriétés de l\'ingrédient',
        menu: 'Détails du menu'
      }
      return titles[this.type] || 'Détails spécifiques'
    },

    // Proxy pour gérer la difficulté (1/2/3 ↔ easy/medium/hard)
    difficultyProxy: {
      get() {
        const DIFF_MAP = { 1: 'easy', 2: 'medium', 3: 'hard' }
        const v = this.localValue?.difficulty_level
        if (typeof v === 'number') return DIFF_MAP[v] ?? ''
        if (v === null || v === undefined) return ''
        return String(v)
      },
      set(val) {
        const DIFF_UNMAP = { easy: 1, medium: 2, hard: 3 }
        const next = { ...(this.localValue || {}) }
        next.difficulty_level = typeof val === 'string' ? (DIFF_UNMAP[val] ?? val) : val
        this.localValue = next
      }
    }
  },

  watch: {
    localValue: {
      handler(newValue) {
        this.$emit('update:modelValue', newValue)
      },
      deep: true
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

.form-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 20px;
}

.form-group.full-width {
  grid-column: 1 / -1;
}

.form-label {
  display: block;
  margin-bottom: 6px;
  font-weight: 500;
  color: #374151;
}

.form-input {
  width: 100%;
  padding: 10px 12px;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  font-size: 14px;
  transition: border-color 0.2s;
}

.form-input:focus {
  outline: none;
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.form-checkbox {
  margin-right: 8px;
}

.checkbox-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 12px;
  margin-top: 8px;
}

.checkbox-item {
  display: flex;
  align-items: center;
  gap: 8px;
  cursor: pointer;
  padding: 8px;
  border-radius: 4px;
  transition: background-color 0.2s;
}

.checkbox-item:hover {
  background-color: #f9fafb;
}

.form-note {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 12px;
  background-color: #f0f9ff;
  border: 1px solid #bae6fd;
  border-radius: 6px;
  color: #0369a1;
  font-size: 14px;
}

@media (max-width: 768px) {
  .form-grid {
    grid-template-columns: 1fr;
  }
  
  .checkbox-grid {
    grid-template-columns: 1fr;
  }
}
</style>