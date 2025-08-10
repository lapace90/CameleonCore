<template>
  <div class="form-section">
    <h3>Détails {{ config.singular.toLowerCase() }}</h3>
    <div class="form-grid">
      
      <!-- Champs pour les Activités -->
      <template v-if="type === 'activity'">
        <div class="form-group">
          <label class="form-label required">Guide</label>
          <input v-model="localValue.guide" type="text" class="form-input" 
            placeholder="Nom du guide" required />
        </div>
        <div class="form-group">
          <label class="form-label required">Durée</label>
          <div class="input-group">
            <input v-model.number="localValue.duration" type="number" 
              class="form-input" placeholder="120" min="1" required />
            <span class="input-addon">min</span>
          </div>
        </div>
        <div class="form-group full-width">
          <label class="form-label required">Point de rendez-vous</label>
          <input v-model="localValue.meeting_point" type="text" class="form-input" 
            placeholder="Lieu de rendez-vous" required />
        </div>
        <div class="form-group">
          <label class="form-label required">Capacité maximum</label>
          <div class="input-group">
            <input v-model.number="localValue.max_people" type="number" 
              class="form-input" placeholder="10" min="1" required />
            <span class="input-addon">pers.</span>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label required">Niveau de difficulté</label>
          <select v-model="localValue.difficulty_level" class="form-select" required>
            <option value="">Choisir</option>
            <option value="easy">Facile</option>
            <option value="medium">Moyen</option>
            <option value="hard">Difficile</option>
          </select>
        </div>
      </template>

      <!-- Champs pour les Hébergements -->
      <template v-if="type === 'room'">
        <div class="form-group">
          <label class="form-label required">Capacité</label>
          <div class="input-group">
            <input v-model.number="localValue.capacity" type="number" 
              class="form-input" placeholder="4" min="1" required />
            <span class="input-addon">pers.</span>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Disponibilité</label>
          <div class="radio-group">
            <label class="radio-item">
              <input type="radio" v-model="localValue.availability" :value="true" />
              <span>Disponible</span>
            </label>
            <label class="radio-item">
              <input type="radio" v-model="localValue.availability" :value="false" />
              <span>Non disponible</span>
            </label>
          </div>
        </div>
      </template>

      <!-- Champs pour les Ingrédients -->
      <template v-if="type === 'ingredient'">
        <div class="form-group">
          <label class="form-label">Stock</label>
          <div class="input-group">
            <input v-model.number="localValue.stock" type="number" 
              class="form-input" placeholder="100" min="0" />
            <span class="input-addon">unités</span>
          </div>
        </div>
        <div class="form-group full-width">
          <label class="form-label">Propriétés diététiques</label>
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

  computed: {
    localValue: {
      get() {
        return this.modelValue
      },
      set(value) {
        this.$emit('update:modelValue', value)
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
.form-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 20px;
}

.form-group.full-width {
  grid-column: 1 / -1;
}

.checkbox-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 12px;
}

.checkbox-item {
  display: flex;
  align-items: center;
  gap: 8px;
  cursor: pointer;
}

.radio-group {
  display: flex;
  gap: 16px;
}

.radio-item {
  display: flex;
  align-items: center;
  gap: 6px;
  cursor: pointer;
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