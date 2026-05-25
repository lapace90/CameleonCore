<template>
  <transition name="modal-fade">
    <div v-if="show" class="modal-overlay" @click="closeModal" aria-modal="true" role="dialog">
      <div class="quote-modal" @click.stop>
        <!-- Header -->
        <div class="modal-header">
          <h2 class="modal-title">
            <AppIcon name="calculator" />
            {{ modalTitle }}
          </h2>
          <button @click="closeModal" class="btn-close" aria-label="Fermer">
            <AppIcon name="x" />
          </button>
        </div>

        <!-- Contenu -->
        <div class="modal-body">
          <!-- Barre de progression dynamique -->
          <div class="progress-steps">
            <div
              v-for="(step, i) in steps"
              :key="step.key"
              class="step"
              :class="{ active: currentStep === i + 1, completed: currentStep > i + 1 }"
            >
              <div class="step-number">{{ i + 1 }}</div>
              <span>{{ step.label }}</span>
            </div>
          </div>

          <!-- Étape dates -->
          <StepDates
            v-if="currentStepKey === 'dates'"
            v-model:dates="selectedDates"
          />

          <!-- Étapes produits (dynamiques) -->
          <StepProducts
            v-for="step in productSteps"
            v-show="currentStepKey === step.key"
            :key="step.key"
            :product-type="step.key"
            :products="allProducts"
            :selection="getSelection(step.key)"
            :guests="selectedDates.guests"
            :loading="loading"
            @update:selection="setSelection(step.key, $event)"
          />

          <!-- Étape récap -->
          <StepRecap
            v-if="currentStepKey === 'recap'"
            :pricing="pricing"
            :dates="selectedDates"
            :contact="contactInfo"
            :qty-overrides="qtyOverrides"
            @update:contact="contactInfo = $event"
            @update:qty-overrides="qtyOverrides = $event"
          />
        </div>

        <!-- Footer -->
        <div class="modal-footer">
          <div class="footer-left">
            <button v-if="currentStep > 1" @click="previousStep" class="btn btn-outline btn-sm">
              <AppIcon name="arrow-left" /> Précédent
            </button>
          </div>
          <div class="footer-right">
            <!-- Suivant -->
            <button
              v-if="currentStep < steps.length"
              @click="nextStep"
              class="btn btn-primary btn-sm"
              :disabled="!canProceed"
            >
              Suivant <AppIcon name="arrow-right" />
            </button>

            <!-- Actions finales (récap) -->
            <div v-else class="quote-final-step">
              <div class="actions-explanation">
                <p class="explanation-text">
                  <AppIcon name="info" />
                  Choisissez comment finaliser votre demande :
                </p>
              </div>
              <div class="quote-actions">
                <button
                  @click="submitAndPay"
                  class="btn btn-success btn-sm"
                  :disabled="isSubmitting || !canSubmit"
                >
                  <AppIcon :name="isSubmitting === 'booking' ? 'loader-circle' : 'credit-card'" :spin="isSubmitting === 'booking'" />
                  {{ isSubmitting === 'booking' ? 'Traitement...' : 'Réserver & Payer' }}
                </button>
                <button
                  @click="submitAndSave"
                  class="btn btn-primary btn-sm"
                  :disabled="isSubmitting || !canSubmit"
                >
                  <AppIcon :name="isSubmitting === 'saving' ? 'loader-circle' : 'bookmark'" :spin="isSubmitting === 'saving'" />
                  {{ isSubmitting === 'saving' ? 'Sauvegarde...' : 'Sauvegarder le devis' }}
                </button>
                <button
                  v-if="instance.hasModule('quote_builder')"
                  @click="submitAdvice"
                  class="btn btn-outline btn-sm"
                  :disabled="isSubmitting || !canSubmit"
                >
                  <AppIcon :name="isSubmitting === 'advice' ? 'loader-circle' : 'user'" :spin="isSubmitting === 'advice'" />
                  {{ isSubmitting === 'advice' ? 'Envoi...' : 'Conseil personnalisé' }}
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </transition>

  <EmailValidationModal
    :show="showEmailValidation"
    :quote-reference="validationQuote.reference"
    :email="validationQuote.email"
    @close="showEmailValidation = false"
  />
</template>

<script>
import { nextTick } from 'vue'
import { useInstanceStore } from '@/shared/stores/instance'
import { PRODUCT_CONFIGS } from '@/shared/configs/productConfigs'
import { computeQuoteTotal } from '@/shared/composables/useQuotePricing'
import { publicApi } from '@/services/PublicApi'
import StepDates from './steps/StepDates.vue'
import StepProducts from './steps/StepProducts.vue'
import StepRecap from './steps/StepRecap.vue'
import EmailValidationModal from '@/public/components/EmailValidationModal.vue'

export default {
  name: 'BookingModal',
  components: { StepDates, StepProducts, StepRecap, EmailValidationModal },
  emits: ['close', 'quote-saved'],

  props: {
    show: { type: Boolean, default: false }
  },

  data() {
    return {
      currentStep: 1,
      loading: false,
      isSubmitting: false,
      allProducts: [],
      qtyOverrides: { activity: {}, menu: {} },
      showEmailValidation: false,
      validationQuote: { reference: '', email: '' },

      // État centralisé
      selectedDates: { start: '', endExclusive: '', guests: 2 },
      selections: { activity: [], menu: [], room: null },
      contactInfo: { name: '', last_name: '', email: '', phone: '', message: '' }
    }
  },

  computed: {
    instance() {
      return useInstanceStore()
    },

    modalTitle() {
      return this.instance.hasModule('quote_builder')
        ? 'Créer mon devis personnalisé'
        : 'Réserver'
    },

    // ===========================
    // STEPS DYNAMIQUES
    // ===========================
    steps() {
      const steps = [
        { key: 'dates', label: 'Dates & convives' }
      ]

      const bookableTypes = ['activity', 'menu', 'room']
      for (const type of bookableTypes) {
        if (this.instance.hasProductable(type)) {
          const config = PRODUCT_CONFIGS[type] || {}
          steps.push({
            key: type,
            label: config.label || type
          })
        }
      }

      steps.push({ key: 'recap', label: 'Récapitulatif' })
      return steps
    },

    productSteps() {
      return this.steps.filter(s => s.key !== 'dates' && s.key !== 'recap')
    },

    currentStepConfig() {
      return this.steps[this.currentStep - 1]
    },

    currentStepKey() {
      return this.currentStepConfig?.key
    },

    // ===========================
    // PRICING
    // ===========================
    pricing() {
      const selected = {
        activity: this.selections.activity.map(a => a.id),
        menu: this.selections.menu.map(m => m.id),
        room: this.selections.room?.id ? [this.selections.room.id] : []
      }

      const catalog = {
        activities: this.allProducts.filter(p => p.typeConfig?.label === 'Activités'),
        menus: this.allProducts.filter(p => p.typeConfig?.label === 'Menus'),
        rooms: this.allProducts.filter(p =>
          p.typeConfig?.label === 'Hébergements' || p.typeConfig?.label === 'Rooms'
        )
      }

      const endInclusive = this.selectedDates.endExclusive
        ? (() => {
            const e = new Date(this.selectedDates.endExclusive)
            e.setDate(e.getDate() - 1)
            return e.toISOString().split('T')[0]
          })()
        : ''

      return computeQuoteTotal({
        selected,
        catalog,
        dates: {
          checkin: this.selectedDates.start,
          checkout: endInclusive,
          guests: this.selectedDates.guests
        },
        rules: {
          roomPerNight: true,
          activityPerGuest: true,
          activityPerGuestPerNight: false,
          menuPerGuest: true,
          menuPerGuestPerNight: false,
          capQtyToGuests: true
        },
        overrides: this.qtyOverrides
      })
    },

    // ===========================
    // NAVIGATION
    // ===========================
    canProceed() {
      switch (this.currentStepKey) {
        case 'dates':
          return this.selectedDates.start && this.selectedDates.endExclusive && this.selectedDates.guests >= 1
        case 'room':
          return !!this.selections.room
        default:
          // Activités et menus sont optionnels
          return true
      }
    },

    canSubmit() {
      const c = this.contactInfo
      return !!c.name && !!c.email && !!c.phone && this.pricing.total > 0
    }
  },

  watch: {
    show(val) {
      if (val) this.initializeModal()
    },

    'selectedDates.guests'(g) {
      const max = Math.max(1, Number(g || 1))
      for (const t of ['activity', 'menu']) {
        for (const id in this.qtyOverrides[t]) {
          if (Number(this.qtyOverrides[t][id] || 0) > max) {
            this.qtyOverrides[t][id] = max
          }
        }
      }
    }
  },

  methods: {
    // ===========================
    // INIT
    // ===========================
    async initializeModal() {
      this.currentStep = 1
      this.resetSelections()
      await this.loadProducts()
    },

    async loadProducts() {
      this.loading = true
      try {
        const response = await publicApi.getProducts({ status: 'active', per_page: 100 })
        this.allProducts = response?.data || []
      } catch (error) {
        console.error('❌ Erreur chargement produits:', error)
        this.allProducts = []
      } finally {
        this.loading = false
      }
    },

    resetSelections() {
      this.selectedDates = { start: '', endExclusive: '', guests: 2 }
      this.selections = { activity: [], menu: [], room: null }
      this.contactInfo = { name: '', last_name: '', email: '', phone: '', message: '' }
      this.qtyOverrides = { activity: {}, menu: {} }
    },

    // ===========================
    // SÉLECTIONS
    // ===========================
    getSelection(type) {
      return this.selections[type] ?? (type === 'room' ? null : [])
    },

    setSelection(type, value) {
      this.selections[type] = value
      // Reset l'override quand on change la sélection
      if (type !== 'room' && this.qtyOverrides[type]) {
        const activeIds = (Array.isArray(value) ? value : []).map(p => p.id)
        for (const id of Object.keys(this.qtyOverrides[type])) {
          if (!activeIds.includes(Number(id))) {
            delete this.qtyOverrides[type][id]
          }
        }
      }
    },

    // ===========================
    // NAVIGATION
    // ===========================
    nextStep() {
      if (this.canProceed && this.currentStep < this.steps.length) {
        this.currentStep++
      }
    },

    previousStep() {
      if (this.currentStep > 1) this.currentStep--
    },

    closeModal() {
      this.$emit('close')
    },

    // ===========================
    // SOUMISSION
    // ===========================
    buildQuoteData() {
      const items = (this.pricing?.lines || [])
        .map(l => ({ product_id: l.id, quantity: Math.max(0, Math.floor(Number(l.qty || 0))) }))
        .filter(it => it.quantity > 0)

      const product_ids = []
      for (const it of items) {
        for (let i = 0; i < it.quantity; i++) product_ids.push(it.product_id)
      }

      return {
        email: this.contactInfo.email,
        contact: {
          name: this.contactInfo.name,
          last_name: this.contactInfo.last_name,
          phone: this.contactInfo.phone,
          message: this.contactInfo.message
        },
        dates: {
          checkin: this.selectedDates.start,
          endExclusive: this.selectedDates.endExclusive,
          guests: this.selectedDates.guests
        },
        total_price: this.pricing.total,
        product_ids
      }
    },

    async saveQuote() {
      const quoteData = this.buildQuoteData()
      if (!quoteData.product_ids.length) throw new Error('Aucun produit sélectionné.')
      return await publicApi.saveQuote(quoteData)
    },

    async submitAndPay() {
      if (!this.canSubmit || this.isSubmitting) return
      this.isSubmitting = 'booking'
      try {
        const result = await this.saveQuote()
        if (!result.success) throw new Error(result.message || 'Impossible de créer le devis')

        const quote = result.quote_request

        if (result.next_step === 'validation_email') {
          this.showEmailValidationRequired(quote)
          return
        }

        const paymentResponse = await this.createStripeSession(quote.id)
        if (!paymentResponse.success) throw new Error(paymentResponse.error || 'Erreur paiement')

        window.location.href = paymentResponse.checkout_url
      } catch (e) {
        console.error('❌ Erreur paiement:', e)
        alert(e.message || 'Erreur inconnue')
      } finally {
        this.isSubmitting = false
      }
    },

    async submitAndSave() {
      if (!this.canSubmit || this.isSubmitting) return
      this.isSubmitting = 'saving'
      try {
        const result = await this.saveQuote()
        if (!result.success) throw new Error(result.message || 'Erreur sauvegarde')

        this.showEmailValidationRequired(result)
        this.$emit('quote-saved', { quote: result.quote_request, type: 'email_validation_required' })
      } catch (e) {
        console.error('❌ Erreur sauvegarde:', e)
        alert(e.message || 'Erreur inconnue')
      } finally {
        this.isSubmitting = false
      }
    },

    async submitAdvice() {
      if (!this.canSubmit || this.isSubmitting) return
      this.isSubmitting = 'advice'
      try {
        const response = await publicApi.requestAdvice({
          type: 'advice_request',
          email: this.contactInfo.email,
          contact: this.contactInfo,
          dates: this.selectedDates,
          selected_products: this.selections,
          total_price: this.pricing.total,
          message: this.contactInfo.message || 'Demande de conseil personnalisé'
        })
        if (!response.success) throw new Error(response.message || 'Erreur envoi')
        this.closeModal()
        alert(`Merci ${this.contactInfo.name}, un expert vous recontacte très vite.`)
      } catch (e) {
        console.error('❌ Erreur conseil:', e)
        alert(e.message || 'Erreur inconnue')
      } finally {
        this.isSubmitting = false
      }
    },

    // ===========================
    // HELPERS
    // ===========================
    showEmailValidationRequired(quote) {
      this.closeModal()
      const quoteData = quote.quote_request || quote
      this.validationQuote = {
        reference: quoteData.quote_reference || quoteData.quoteReference || 'N/A',
        email: this.contactInfo.email || quoteData.email || ''
      }
      this.showEmailValidation = true
    },

    async createStripeSession(quoteId) {
      const response = await fetch(`${import.meta.env.VITE_API_URL}/stripe/create-payment-session`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body: JSON.stringify({ quote_id: quoteId })
      })
      const data = await response.json()
      if (!response.ok) throw new Error(data.error || 'Erreur session de paiement')
      return data
    }
  }
}
</script>
