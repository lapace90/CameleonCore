<template>
  <div class="step-content">
    <h3 class="step-title">Récapitulatif</h3>

    <div class="quote-summary">
      <!-- Sections par type de produit -->
      <div
        v-for="section in productSections"
        :key="section.type"
        class="summary-section"
      >
        <h4>
          <AppIcon :name="section.icon" />
          {{ section.label }}
        </h4>
        <div
          v-for="line in section.lines"
          :key="line.id"
          class="summary-item enhanced"
        >
          <div class="item-info">
            <span class="item-name">
              {{ line.name }}
              <template v-if="line.type === 'room'">
                ({{ pricing.nights }} nuit{{ pricing.nights > 1 ? 's' : '' }})
              </template>
            </span>
            <span class="item-unit-price">{{ formatPrice(line.unit) }}€/{{ line.type === 'room' ? 'nuit' : 'unité' }}</span>
          </div>

          <div class="item-controls">
            <!-- Room : quantité fixe (nuits) -->
            <template v-if="line.type === 'room'">
              <span class="qty-fixed">× {{ line.qty }}</span>
            </template>
            <!-- Activités & menus : quantité ajustable -->
            <template v-else>
              <label class="qty-label">Quantité:</label>
              <select
                class="qty-select"
                :value="getQty(line.type, line.id, line.qty)"
                @change="setQty(line.type, line.id, $event.target.value)"
              >
                <option value="0">Supprimer</option>
                <option v-for="n in maxQty" :key="n" :value="n">{{ n }}</option>
              </select>
            </template>
          </div>

          <div class="item-total">
            <strong>{{ formatPrice(line.lineTotal) }}</strong>
          </div>
        </div>
      </div>

      <!-- Détails du séjour -->
      <div class="summary-section">
        <h4><AppIcon name="calendar" /> Détails</h4>
        <div class="summary-item">
          <span>
            Du {{ formatDate(dates.start) }}
            au {{ formatDate(displayEndDate) }}
          </span>
        </div>
        <div class="summary-item">
          <span>{{ dates.guests }} personne{{ dates.guests > 1 ? 's' : '' }}</span>
        </div>
      </div>

      <!-- Total -->
      <div class="summary-total">
        <div class="total-line">
          <span>Total estimé</span>
          <strong>{{ formatPrice(pricing.total) }}</strong>
        </div>
        <div v-if="depositEnabled" class="total-line deposit-line">
          <span>Acompte à régler ({{ depositPercentage }}%)</span>
          <strong>{{ formatPrice(depositAmount) }}</strong>
        </div>
        <p v-if="depositEnabled" class="deposit-note">
          Le solde de {{ formatPrice(pricing.total - depositAmount) }} sera à régler ultérieurement.
        </p>
      </div>
    </div>

    <!-- Coordonnées -->
    <div class="contact-form">
      <h4>Vos coordonnées *</h4>
      <div class="form-row">
        <input
          type="text"
          :value="contact.name"
          @input="updateContact('name', $event.target.value)"
          placeholder="Prénom"
          class="form-input"
        />
        <input
          type="text"
          :value="contact.last_name"
          @input="updateContact('last_name', $event.target.value)"
          placeholder="Nom"
          class="form-input"
        />
      </div>
      <div class="form-row">
        <input
          type="email"
          :value="contact.email"
          @input="updateContact('email', $event.target.value)"
          placeholder="Email"
          class="form-input"
        />
        <input
          type="tel"
          :value="contact.phone"
          @input="updateContact('phone', $event.target.value)"
          placeholder="Téléphone"
          class="form-input"
        />
      </div>
      <textarea
        :value="contact.message"
        @input="updateContact('message', $event.target.value)"
        placeholder="Message (optionnel)"
        class="form-textarea"
      ></textarea>
    </div>
  </div>
</template>

<script>
import { useInstanceStore } from '@/shared/stores/instance'

export default {
  name: 'StepRecap',

  props: {
    pricing: {
      type: Object,
      required: true
      // { lines: [], total: 0, nights: 0 }
    },
    dates: {
      type: Object,
      required: true
    },
    contact: {
      type: Object,
      required: true
      // { name, last_name, email, phone, message }
    },
    qtyOverrides: {
      type: Object,
      default: () => ({ activity: {}, menu: {} })
    }
  },

  emits: ['update:contact', 'update:qtyOverrides'],

  computed: {
    instance() {
      return useInstanceStore()
    },

    depositEnabled() {
      return this.instance.hasFeature('deposit_payment')
    },

    depositPercentage() {
      return this.instance.features.deposit_percentage ?? 30
    },

    depositAmount() {
      return Math.round(this.pricing.total * this.depositPercentage / 100 * 100) / 100
    },

    displayEndDate() {
      if (!this.dates.endExclusive) return ''
      const e = new Date(this.dates.endExclusive)
      e.setDate(e.getDate() - 1)
      return e.toISOString().split('T')[0]
    },

    maxQty() {
      return Math.max(1, this.dates.guests || 1) * Math.max(1, this.pricing.nights || 1)
    },

    productSections() {
      const sections = []
      const linesByType = {}

      for (const line of this.pricing.lines) {
        if (!linesByType[line.type]) linesByType[line.type] = []
        linesByType[line.type].push(line)
      }

      const typeConfig = {
        activity: { label: 'Activités', icon: 'footprints' },
        menu: { label: 'Menus', icon: 'utensils' },
        room: { label: 'Hébergement', icon: 'bed' }
      }

      for (const [type, lines] of Object.entries(linesByType)) {
        const config = typeConfig[type] || { label: type, icon: 'package' }
        sections.push({ type, ...config, lines })
      }

      return sections
    },

    isValid() {
      const c = this.contact
      return !!c.name && !!c.email && !!c.phone && this.pricing.total > 0
    }
  },

  methods: {
    getQty(type, id, fallback) {
      const v = this.qtyOverrides?.[type]?.[id]
      return Number.isFinite(Number(v)) ? Number(v) : (fallback ?? 1)
    },

    setQty(type, id, value) {
      const v = Math.max(0, Math.floor(Number(value || 0)))
      const updated = { ...this.qtyOverrides }
      if (!updated[type]) updated[type] = {}
      updated[type] = { ...updated[type], [id]: v }
      this.$emit('update:qtyOverrides', updated)
    },

    updateContact(field, value) {
      this.$emit('update:contact', { ...this.contact, [field]: value })
    },

    formatPrice(amount) {
      return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'EUR'
      }).format(amount || 0)
    },

    formatDate(s) {
      return s
        ? new Date(s).toLocaleDateString('fr-FR', { day: 'numeric', month: 'long', year: 'numeric' })
        : ''
    }
  }
}
</script>
