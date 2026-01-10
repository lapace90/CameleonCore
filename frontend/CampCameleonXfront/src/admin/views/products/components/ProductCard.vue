<template>
  <div class="product-card" :class="{ selected: selected }">
    <div class="card-header">
      <input type="checkbox" :checked="selected" @change="$emit('select')" class="product-checkbox" />
      <div class="product-status" :class="product.status_class">
        {{ product.status_label }}
      </div>
    </div>

    <div class="card-image">
      <img :src="product.image" :alt="product.name" />
      <div class="image-overlay">
        <button @click="$emit('view', product)" class="overlay-btn" title="Voir">
          <i class="fas fa-eye"></i>
        </button>
        <button @click="$emit('edit', product)" class="overlay-btn" title="Modifier">
          <i class="fas fa-edit"></i>
        </button>
      </div>
    </div>

    <div class="card-content">
      <h3 class="product-name">{{ product.name }}</h3>
      <CategoryBadge v-if="product.category" :category="product.category" class="mb-2" />
      
      <p v-if="product.description" class="product-description">
        {{ truncateText(product.description, 80) }}
      </p>

      <!-- Champs spécifiques -->
      <div v-if="product.list_fields && Object.keys(product.list_fields).length > 0" class="product-fields">
        <div v-for="(field, key) in product.list_fields" :key="key" class="field-item">
          <span class="field-label">{{ field.label }}:</span>
          <span class="field-value">{{ field.value }}</span>
        </div>
      </div>

      <div v-if="hasProductTags" class="product-tags">
        <span v-for="tag in limitedTags" :key="tag.id" class="tag">
          <i v-if="tag.icon" :class="tag.icon"></i>
          {{ getDisplayName(tag.name) }}
        </span>
      </div>
      <div v-if="totalTagsCount > 3" class="more-tag">
        +{{ totalTagsCount - 3 }} autres
      </div>

      <div class="card-footer">
        <div class="product-price">{{ product.formatted_price }}</div>
        <div class="card-actions">
          <button @click="$emit('duplicate', product)" class="btn-icon" title="Dupliquer">
            <i class="fas fa-copy"></i>
          </button>
          <button @click="$emit('toggle-status', product)" class="btn-icon"
            :title="product.status ? 'Désactiver' : 'Activer'">
            <i :class="product.status ? 'fas fa-pause' : 'fas fa-play'"></i>
          </button>
          <button @click="$emit('delete', product)" class="btn-icon text-danger" title="Supprimer">
            <i class="fas fa-trash"></i>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import CategoryBadge from './CategoryBadge.vue'

export default {
  name: 'ProductCard',
  props: {
    product: { type: Object, required: true },
    selected: { type: Boolean, default: false }
  },
  components: {
    CategoryBadge
  },
    mounted() {
    // TEMPORAIRE : pour déboguer
    console.log('🔍 Product data:', this.product)
    console.log('🔍 GlobalTags:', this.product.globalTags)
    console.log('🔍 SpecificTags:', this.product.specificTags)
  },
  methods: {
    truncateText(text, length) {
      if (!text) return ''
      return text.length > length ? text.substring(0, length) + '...' : text
    },

    getDisplayName(tagName) {
      const names = {
        'vegetarian': 'Végé',
        'vegan': 'Végan',
        'spicy': 'Épicé',
        'gluten_free': 'Sans gluten',
        'lactose_free': 'Sans lactose',
        'extreme': 'Extrême',
        'couple': 'Couple',
        'family': 'Famille',
        'budget': 'Pas cher',
        'premium': 'Premium'
      }
      return names[tagName] || tagName
    }
  },
  composants: {
    CategoryBadge
  },
  
  computed: {
    hasProductTags() {
      return (this.product.globalTags && this.product.globalTags.length > 0) ||
        (this.product.specificTags && this.product.specificTags.length > 0)
    },

    limitedTags() {
      let tags = []

      // Tags globaux
      if (this.product.globalTags) {
        tags = [...tags, ...this.product.globalTags]
      }

      // Tags spécifiques  
      if (this.product.specificTags) {
        tags = [...tags, ...this.product.specificTags]
      }

      // Limiter à 3 tags max
      return tags.slice(0, 3)
    },
    totalTagsCount() {
      let count = 0
      if (this.product.globalTags) count += this.product.globalTags.length
      if (this.product.specificTags) count += this.product.specificTags.length
      return count
    }
  },
}
</script>

<style scoped>
.product-card {
  background: white;
  border: 1px solid #e5e7eb;
  border-radius: 12px;
  overflow: hidden;
  transition: all 0.3s;
  cursor: pointer;
}

.product-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
}

.product-card.selected {
  border-color: #3b82f6;
  box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.2);
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px;
}

.product-status {
  padding: 2px 8px;
  border-radius: 12px;
  font-size: 11px;
  font-weight: 500;
}

.status-active {
  background: #dcfce7;
  color: #166534;
}

.status-inactive {
  background: #fee2e2;
  color: #991b1b;
}

.status-draft {
  background: #fef3c7;
  color: #92400e;
}

.card-image {
  position: relative;
  height: 200px;
  overflow: hidden;
}

.card-image img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.image-overlay {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.7);
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 12px;
  opacity: 0;
  transition: opacity 0.3s;
}

.card-image:hover .image-overlay {
  opacity: 1;
}

.overlay-btn {
  width: 36px;
  height: 36px;
  border: none;
  border-radius: 50%;
  background: white;
  color: #374151;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: transform 0.2s;
}

.overlay-btn:hover {
  transform: scale(1.1);
}

.card-content {
  padding: 16px;
}

.product-name {
  font-size: 16px;
  font-weight: 600;
  margin: 0 0 4px 0;
  color: #111827;
}

.product-category {
  font-size: 12px;
  color: #6b7280;
  margin: 0 0 8px 0;
}

.product-description {
  font-size: 14px;
  color: #6b7280;
  margin: 0 0 12px 0;
  line-height: 1.4;
}

.product-fields {
  margin-bottom: 16px;
}

.field-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-size: 12px;
  margin-bottom: 4px;
}

.field-label {
  color: #6b7280;
}

.field-value {
  color: #111827;
  font-weight: 500;
}

.card-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.product-price {
  font-size: 18px;
  font-weight: 700;
  color: #059669;
}

.card-actions {
  display: flex;
  gap: 8px;
}

.btn-icon {
  width: 32px;
  height: 32px;
  border: none;
  border-radius: 6px;
  background: #f9fafb;
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

.text-danger:hover {
  background: #fee2e2 !important;
}

.product-tags {
  margin: 8px 0;
  display: flex;
  flex-wrap: wrap;
  gap: 4px;
}

.tag {
  display: inline-flex;
  align-items: center;
  gap: 3px;
  padding: 2px 6px;
  border-radius: 10px;
  font-size: 9px;
  font-weight: 500;
  background-color: rgba(59, 130, 246, 0.1);
  color: #3b82f6;
}

.tag i {
  font-size: 8px;
}
</style>