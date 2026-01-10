<template>
  <div class="admin-filter-bar" :class="layoutClass">
    <div class="filters-row">
      <!-- Recherche -->
      <div v-if="hasSearch" class="search-field">
        <div class="search-box">
          <i class="fas fa-search search-icon"></i>
          <input
            :id="searchId"
            class="search-input"
            type="text"
            :placeholder="searchPlaceholder"
            :value="localFilters[searchKey] || ''"
            @input="onSearchInput"
          >
        </div>
      </div>

      <!-- Champs de filtres -->
      <div
        v-for="field in normalizedFields"
        :key="field.key"
        class="filter-field"
        :class="field.class"
      >
        <select
          v-if="field.type === 'select'"
          class="filter-select"
          :id="field.id"
          :value="localFilters[field.key] ?? ''"
          @change="event => onFieldChange(field, event.target.value)"
        >
          <option
            v-if="field.placeholder !== undefined"
            :value="field.placeholderValue"
          >
            {{ field.placeholder }}
          </option>
          <option
            v-for="option in field.options"
            :key="option.value"
            :value="option.value"
          >
            {{ option.label }}
          </option>
        </select>

        <input
          v-else
          class="filter-input"
          :id="field.id"
          :type="field.inputType"
          :value="localFilters[field.key] ?? ''"
          @input="event => onFieldChange(field, event.target.value)"
        >
      </div>

      <!-- Actions (slot pour view switcher etc) -->
      <div v-if="$slots.actions" class="filters-actions">
        <slot name="actions" />
      </div>

      <!-- Bouton reset -->
      <div v-if="showReset && activeFilterCount > 0" class="reset-control">
        <button
          type="button"
          class="btn btn-outline btn-sm"
          @click="resetFilters"
        >
          <i class="fas fa-times"></i>
          {{ resetLabel }}
        </button>
      </div>

      <!-- Infos résultats (inline) -->
      <div class="results-inline">
        <slot name="results" :active-count="activeFilterCount">
          <span class="results-info">
            <i class="fas fa-filter"></i>
            <span v-if="activeFilterCount > 0">
              {{ activeFilterCount }} filtre(s) actif(s)
            </span>
          </span>
        </slot>
      </div>
    </div>

    <!-- Footer si besoin -->
    <div v-if="$slots.footer" class="filters-footer">
      <slot name="footer" :filters="localFilters" :active-count="activeFilterCount" />
    </div>
  </div>
</template>

<script>
export default {
  name: 'AdminFilterBar',
  
  props: {
    modelValue: { type: Object, required: true },
    defaultFilters: { type: Object, default: () => ({}) },
    fields: { type: Array, default: () => [] },
    searchKey: { type: String, default: 'search' },
    showSearch: { type: Boolean, default: true },
    searchLabel: { type: String, default: '' },
    searchPlaceholder: { type: String, default: 'Rechercher...' },
    searchDebounce: { type: Number, default: 400 },
    autoApply: { type: Boolean, default: true },
    showReset: { type: Boolean, default: true },
    showActiveCount: { type: Boolean, default: true },
    resetLabel: { type: String, default: 'Reset' },
    layout: {
      type: String,
      default: 'inline',
      validator: value => ['inline', 'grid'].includes(value)
    }
  },
  
  emits: ['update:modelValue', 'apply', 'reset', 'change'],
  
  data() {
    return {
      localFilters: this.buildInitialFilters(),
      searchTimeout: null,
      // Générer un ID unique au montage du composant
      componentId: `filter-${Date.now()}-${Math.random().toString(36).substr(2, 9)}`
    }
  },
  
  computed: {
    layoutClass() {
      return this.layout === 'grid'
        ? 'admin-filter-bar--grid'
        : 'admin-filter-bar--inline'
    },
    
    hasSearch() {
      return this.showSearch && this.searchKey
    },
    
    // Utiliser componentId au lieu de _uid
    searchId() {
      return `${this.componentId}-search`
    },
    
    normalizedFields() {
      return this.fields.map((field, index) => {
        // Utiliser componentId au lieu de _uid
        const id = field.id || `${this.componentId}-field-${field.key || index}`
        
        const options = (field.options || []).map(option => ({
          label: option.label ?? option.name ?? option.value ?? option,
          value: option.value ?? option.id ?? option
        }))
        
        return {
          ...field,
          id,
          type: field.type || 'select',
          inputType: field.type === 'date' ? 'date' : (field.inputType || 'text'),
          options,
          placeholder: field.placeholder,
          placeholderValue: field.placeholderValue ?? '',
          slotName: field.slotName || field.slot,
          class: field.class || ''
        }
      })
    },
    
    activeFilterCount() {
      const defaults = this.defaultFilters || {}
      return Object.entries(this.localFilters).reduce((count, [key, value]) => {
        const current = Array.isArray(value) ? value : String(value ?? '').trim()
        const defaultValue = defaults[key]
        const normalizedDefault = Array.isArray(defaultValue)
          ? defaultValue
          : String(defaultValue ?? '').trim()
        
        const hasValue = Array.isArray(value)
          ? value.length > 0
          : current.length > 0
        
        const differs = Array.isArray(value)
          ? JSON.stringify(value) !== JSON.stringify(normalizedDefault)
          : current !== normalizedDefault
        
        const shouldCount = hasValue && differs && (key !== this.searchKey || this.showSearch)
        
        return shouldCount ? count + 1 : count
      }, 0)
    }
  },
  
  watch: {
    modelValue: {
      deep: true,
      handler() {
        this.localFilters = this.buildInitialFilters()
      }
    }
  },
  
  methods: {
    buildInitialFilters() {
      const base = { ...(this.defaultFilters || {}), ...(this.modelValue || {}) }
      if (this.hasSearch && !(this.searchKey in base)) {
        base[this.searchKey] = ''
      }
      return base
    },
    
    buildInitialReset() {
      const defaults = { ...(this.defaultFilters || {}) }
      if (this.hasSearch && !(this.searchKey in defaults)) {
        defaults[this.searchKey] = ''
      }
      return defaults
    },
    
    onSearchInput(event) {
      const value = event?.target ? event.target.value : event
      this.updateFilter(this.searchKey, value, { debounce: true })
    },
    
    onFieldChange(field, value) {
      this.updateFilter(field.key, value)
    },
    
    updateFilter(key, value, { debounce = false } = {}) {
      this.localFilters = { ...this.localFilters, [key]: value }
      this.emitUpdate()
      
      if (debounce && this.searchDebounce > 0) {
        clearTimeout(this.searchTimeout)
        this.searchTimeout = setTimeout(() => {
          if (this.autoApply) this.emitApply()
        }, this.searchDebounce)
      } else if (this.autoApply) {
        this.emitApply()
      }
    },
    
    emitUpdate() {
      const payload = { ...this.localFilters }
      this.$emit('update:modelValue', payload)
      this.$emit('change', payload)
    },
    
    emitApply() {
      this.$emit('apply', { ...this.localFilters })
    },
    
    resetFilters() {
      const reset = this.buildInitialReset()
      this.localFilters = reset
      this.emitUpdate()
      this.$emit('reset', { ...this.localFilters })
      if (this.autoApply) this.emitApply()
    }
  },
  
  beforeUnmount() {
    if (this.searchTimeout) {
      clearTimeout(this.searchTimeout)
    }
  }
}
</script>

<style scoped>
.admin-filter-bar {
  width: 100%;
  background: #ffffff;
  border-radius: 12px;
  padding: 1.5rem;
  margin-bottom: 1.5rem;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
  border: 1px solid #e9ecef;
}

.filters-row {
  display: flex;
  align-items: center;
  gap: 1rem;
  flex-wrap: wrap;
}

/* RECHERCHE À GAUCHE */
.search-field {
  flex: 1;
  min-width: 280px;
  max-width: 500px;
  margin-right: auto; /* Pousse tout le reste à droite */
}

.search-box {
  position: relative;
  width: 100%;
}

.search-icon {
  position: absolute;
  left: 1rem;
  top: 50%;
  transform: translateY(-50%);
  color: #8898aa;
  font-size: 0.875rem;
  pointer-events: none;
  z-index: 2;
}

.search-input {
  width: 100%;
  padding: 0.75rem 1rem 0.75rem 2.75rem;
  border: 1px solid #dee2e6;
  border-radius: 8px;
  font-size: 0.875rem;
  background: #f8f9fa;
  transition: all 0.2s;
}

.search-input:focus {
  outline: none;
  border-color: #5e72e4;
  background: #ffffff;
  box-shadow: 0 0 0 3px rgba(94, 114, 228, 0.1);
}

/* FILTRES À DROITE - Groupe compact */
.filter-field {
  min-width: 160px;
  flex-shrink: 0;
}

.filter-select,
.filter-input {
  width: 100%;
  padding: 0.75rem 1rem;
  border: 1px solid #dee2e6;
  border-radius: 8px;
  font-size: 0.875rem;
  background: #f8f9fa;
  color: #525f7f;
  transition: all 0.2s;
  cursor: pointer;
}

.filter-select:focus,
.filter-input:focus {
  outline: none;
  border-color: #5e72e4;
  background: #ffffff;
  box-shadow: 0 0 0 3px rgba(94, 114, 228, 0.1);
}

.filter-select:hover,
.filter-input:hover {
  border-color: #adb5bd;
}

/* ACTIONS À DROITE */
.filters-actions {
  display: flex;
  gap: 0.75rem;
  align-items: center;
  flex-shrink: 0;
}

/* Reset à droite */
.reset-control {
  display: flex;
  align-items: center;
  flex-shrink: 0;
}

/* Infos inline à droite */
.results-inline {
  display: flex;
  align-items: center;
  font-size: 0.875rem;
  color: #8898aa;
  font-weight: 500;
  white-space: nowrap;
  flex-shrink: 0;
}

.results-info {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

/* Footer */
.filters-footer {
  margin-top: 1rem;
  padding-top: 1rem;
  border-top: 1px solid #e9ecef;
}

/* RESPONSIVE - Adaptation intelligente */
@media (max-width: 1200px) {
  .search-field {
    max-width: 400px;
  }
}

@media (max-width: 1024px) {
  .filters-row {
    gap: 0.75rem;
  }

  .search-field {
    flex: 1 1 100%;
    max-width: 100%;
    margin-right: 0;
    margin-bottom: 0.5rem;
  }

  /* Sur tablet/mobile, les filtres restent groupés mais passent en dessous */
  .filter-field,
  .filters-actions,
  .reset-control,
  .results-inline {
    flex: 0 1 auto;
  }
}

@media (max-width: 768px) {
  .filters-row {
    gap: 0.5rem;
  }

  .filter-field {
    min-width: calc(50% - 0.25rem);
  }
}

@media (max-width: 640px) {
  .filter-field {
    min-width: 100%;
  }

  .filters-actions,
  .reset-control,
  .results-inline {
    width: 100%;
    justify-content: flex-start;
  }
}
</style>
