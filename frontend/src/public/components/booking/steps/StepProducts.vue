<template>
  <div class="step-content">
    <h3 class="step-title">{{ title }}</h3>
    <p class="step-description">{{ description }}</p>

    <Loading v-if="loading" :text="`Chargement des ${title.toLowerCase()}...`" size="sm" />

    <div v-else-if="filteredProducts.length" class="mini-grid">
      <article
        v-for="product in filteredProducts"
        :key="product.id"
        class="mini-card"
        :class="{ selected: isSelected(product) }"
        @click="toggle(product)"
      >
        <img
          class="mini-thumb"
          :src="product.image"
          :alt="product.name"
          loading="lazy"
          decoding="async"
        />
        <div class="mini-info">
          <h5 class="mini-title">{{ product.name }}</h5>
          <div class="mini-meta">
            <span class="mini-price">
              {{ product.formatted_price }}{{ priceUnit }}
            </span>
            <span
              v-if="showCapacity(product)"
              class="mini-pill"
            >
              <AppIcon name="users" />
              {{ getCapacity(product) }} pers. max
            </span>
          </div>
          <p class="mini-desc">
            {{ truncate(product.description || product.short_description, 90) }}
          </p>
        </div>
        <div class="mini-check">
          <AppIcon :name="isSelected(product) ? 'check' : 'plus'" />
        </div>
      </article>
    </div>

    <div v-else class="empty-state">
      <AppIcon name="package-open" />
      <p>Aucun {{ title.toLowerCase() }} disponible pour le moment.</p>
    </div>
  </div>
</template>

<script>
import { PRODUCT_CONFIGS } from '@/shared/configs/productConfigs'
import Loading from '@/shared/components/ui/Loading.vue'

export default {
  name: 'StepProducts',
  components: { Loading },

  props: {
    productType: {
      type: String,
      required: true
      // 'activity', 'menu', 'room'
    },
    products: {
      type: Array,
      default: () => []
    },
    selection: {
      type: [Array, Object, null],
      default: () => []
    },
    guests: {
      type: Number,
      default: 1
    },
    loading: {
      type: Boolean,
      default: false
    }
  },

  emits: ['update:selection'],

  computed: {
    config() {
      return PRODUCT_CONFIGS[this.productType] || {}
    },

    title() {
      return this.config.label || this.productType
    },

    description() {
      const descriptions = {
        activity: 'Sélectionnez les expériences qui vous tentent.',
        menu: 'Sélectionnez les repas qui vous intéressent.',
        room: 'Sélectionnez votre hébergement.'
      }
      return descriptions[this.productType] || `Choisissez vos ${this.title.toLowerCase()}.`
    },

    priceUnit() {
      if (this.productType === 'room') return '/nuit'
      return ''
    },

    isSingleSelect() {
      return this.productType === 'room'
    },

    filteredProducts() {
      const label = this.config.label
      let filtered = this.products.filter(p => {
        const pLabel = p.typeConfig?.label
        return pLabel === label || pLabel === this.config.labelAlt
      })

      // Filtrer par capacité pour les hébergements
      if (this.productType === 'room') {
        filtered = filtered.filter(p => {
          const capacity = p.productable_data?.capacity || p.productableData?.capacity
          return !capacity || capacity >= this.guests
        })
      }

      return filtered
    },

    isValid() {
      if (this.productType === 'room') {
        return this.selection !== null && this.selection !== undefined
      }
      // Activités et menus sont optionnels
      return true
    }
  },

  methods: {
    isSelected(product) {
      if (this.isSingleSelect) {
        return this.selection?.id === product.id
      }
      return Array.isArray(this.selection) && this.selection.some(p => p.id === product.id)
    },

    toggle(product) {
      if (this.isSingleSelect) {
        this.$emit('update:selection', product)
        return
      }

      const current = Array.isArray(this.selection) ? [...this.selection] : []
      const index = current.findIndex(p => p.id === product.id)

      if (index > -1) {
        current.splice(index, 1)
      } else {
        current.push(product)
      }

      this.$emit('update:selection', current)
    },

    showCapacity(product) {
      if (this.productType !== 'room') return false
      return product.productable_data?.capacity || product.productableData?.capacity
    },

    getCapacity(product) {
      return product.productable_data?.capacity || product.productableData?.capacity
    },

    truncate(text, length) {
      if (!text) return ''
      return text.length > length ? text.slice(0, length) + '…' : text
    }
  }
}
</script>
