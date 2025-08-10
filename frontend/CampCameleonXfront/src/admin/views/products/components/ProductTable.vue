<template>
  <div class="table-container">
    <table class="table">
      <thead>
        <tr>
          <th class="checkbox-col">
            <input type="checkbox" :checked="allSelected" @change="$emit('select-all')" />
          </th>
          <th class="image-col">Image</th>
          <th @click="$emit('sort', 'name')" class="sortable">
            Nom
            <i class="fas fa-sort"></i>
          </th>
          <th>Catégorie</th>
          <th v-for="column in visibleColumns" :key="column" 
            @click="$emit('sort', column)" class="sortable">
            {{ getColumnLabel(column) }}
            <i class="fas fa-sort"></i>
          </th>
          <th @click="$emit('sort', 'price')" class="sortable">
            Prix
            <i class="fas fa-sort"></i>
          </th>
          <th>Statut</th>
          <th class="actions-col">Actions</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="product in products" :key="product.id" 
          :class="{ selected: selected.includes(product.id) }">
          <td class="checkbox-col">
            <input type="checkbox" :checked="selected.includes(product.id)" 
              @change="$emit('select', product.id)" />
          </td>
          <td class="image-col">
            <div class="table-image">
              <img :src="product.image" :alt="product.name" />
            </div>
          </td>
          <td>
            <div class="product-name-cell">
              <strong>{{ product.name }}</strong>
              <p v-if="product.description" class="product-description-mini">
                {{ truncateText(product.description, 50) }}
              </p>
            </div>
          </td>
          <td>
            <span v-if="product.category" class="category-badge">
              {{ product.category.name }}
            </span>
            <span v-else class="text-muted">-</span>
          </td>
          <td v-for="column in visibleColumns" :key="column">
            {{ getFieldValue(product, column) }}
          </td>
          <td>
            <span class="price-value">{{ product.formatted_price }}</span>
          </td>
          <td>
            <span class="status-badge" :class="product.status_class">
              {{ product.status_label }}
            </span>
          </td>
          <td class="actions-col">
            <div class="table-actions">
              <button @click="$emit('view', product)" class="btn-icon" title="Voir">
                <i class="fas fa-eye"></i>
              </button>
              <button @click="$emit('edit', product)" class="btn-icon" title="Modifier">
                <i class="fas fa-edit"></i>
              </button>
              <button @click="$emit('duplicate', product)" class="btn-icon" title="Dupliquer">
                <i class="fas fa-copy"></i>
              </button>
              <button @click="$emit('delete', product)" class="btn-icon text-danger" title="Supprimer">
                <i class="fas fa-trash"></i>
              </button>
            </div>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<script>
export default {
  name: 'ProductTable',
  props: {
    products: { type: Array, required: true },
    typeConfig: { type: Object, required: true },
    selected: { type: Array, default: () => [] }
  },
  computed: {
    allSelected() {
      return this.products.length > 0 && this.selected.length === this.products.length
    },
    visibleColumns() {
      // Colonnes spécifiques au type si définies
      return this.typeConfig.listColumns || []
    }
  },
  methods: {
    truncateText(text, length) {
      if (!text) return ''
      return text.length > length ? text.substring(0, length) + '...' : text
    },
    getColumnLabel(column) {
      const labels = {
        duration: 'Durée',
        capacity: 'Capacité',
        stock: 'Stock',
        is_vegetarian: 'Végétarien',
        availability: 'Disponibilité'
      }
      return labels[column] || column
    },
    getFieldValue(product, column) {
      if (product.list_fields && product.list_fields[column]) {
        return product.list_fields[column].value
      }
      return '-'
    }
  }
}
</script>

<style scoped>
.table-container {
  overflow-x: auto;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
}

.table {
  width: 100%;
  border-collapse: collapse;
  background: white;
}

.table th,
.table td {
  padding: 12px 16px;
  text-align: left;
  border-bottom: 1px solid #e5e7eb;
}

.table th {
  background: #f9fafb;
  font-weight: 600;
  color: #374151;
  position: sticky;
  top: 0;
  z-index: 10;
}

.table tr:hover {
  background: #f9fafb;
}

.table tr.selected {
  background: #eff6ff;
}

.sortable {
  cursor: pointer;
  user-select: none;
  position: relative;
}

.sortable:hover {
  background: #f3f4f6;
}

.sortable i {
  opacity: 0.5;
  margin-left: 4px;
}

.checkbox-col {
  width: 40px;
}

.image-col {
  width: 80px;
}

.actions-col {
  width: 140px;
}

.table-image {
  width: 50px;
  height: 50px;
  border-radius: 8px;
  overflow: hidden;
}

.table-image img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.product-name-cell strong {
  display: block;
  color: #111827;
  margin-bottom: 2px;
}

.product-description-mini {
  font-size: 12px;
  color: #6b7280;
  margin: 0;
}

.category-badge {
  padding: 2px 8px;
  background: #f3f4f6;
  border-radius: 12px;
  font-size: 12px;
  color: #374151;
}

.text-muted {
  color: #9ca3af;
}

.price-value {
  font-weight: 600;
  color: #059669;
}

.status-badge {
  padding: 2px 8px;
  border-radius: 12px;
  font-size: 11px;
  font-weight: 500;
}

.table-actions {
  display: flex;
  gap: 4px;
}

.btn-icon {
  width: 28px;
  height: 28px;
  border: none;
  border-radius: 4px;
  background: transparent;
  color: #6b7280;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-icon:hover {
  background: #f3f4f6;
}

.text-danger {
  color: #ef4444 !important;
}
</style>