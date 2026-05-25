<template>
  <section class="devis-section" id="devis-section">
    <div class="container">
      <!-- Éléments décoratifs subtils -->
      <div class="decorative-elements">
        <div class="shape shape-1"></div>
        <div class="shape shape-2"></div>
        <div class="shape shape-3"></div>
        <div class="floating-icon icon-1">
          <AppIcon name="compass" />
        </div>
        <div class="floating-icon icon-2">
          <AppIcon name="map" />
        </div>
      </div>

      <div class="devis-content">
        <!-- Contenu centré -->
        <div class="text-content">
          <div class="main-text">
            <h2 class="title">
              Votre expérience, votre budget:
            </h2>
          </div>

          <!-- Bouton CTA principal -->
          <div class="cta-container">
            <button @click="showQuote = true" class="quote-btn" :disabled="isLoading">
              <AppIcon name="loader-circle" :spin="true" v-if="isLoading" />
              <AppIcon name="calculator" v-else />
              <span>{{ isLoading ? 'Chargement...' : 'Créer mon devis maintenant !' }}</span>
            </button>
          </div>

          <!-- Description -->
          <div class="description">
            <p>
              Choisissez vos activités et votre hébergement pour obtenir un
              devis adapté à vos envies.
            </p>
          </div>

          <!-- Points forts EN LIGNE -->
          <div class="features">
            <div class="feature-item">
              <div class="feature-icon">
                <AppIcon name="wand-2" />
              </div>
              <span>Personnalisable</span>
            </div>
            <div class="feature-item">
              <div class="feature-icon">
                <AppIcon name="clock" />
              </div>
              <span>Instantané</span>
            </div>
            <div class="feature-item">
              <div class="feature-icon">
                <AppIcon name="shield" />
              </div>
              <span>Sans engagement</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
  <BookingModal :show="showQuote" @close="showQuote = false" @quote-saved="onBooking" />
</template>

<script>
import BookingModal from '@/public/components/booking/BookingModal.vue'

export default {
  name: 'DevisSection',
  components: { BookingModal },

  data() {
    return {
      showQuote: false,
      isLoading: false
    }
  },

  methods: {
    onBooking(data) {
      console.log('Réservation:', data)
    },
    openQuoteFromDemo() {
      this.showQuote = true
    }
  },

  mounted() {
    // Écoute l'event du bouton démo
    window.addEventListener('demo:open-quote-modal', this.openQuoteFromDemo)
  },

  beforeUnmount() {
    window.removeEventListener('demo:open-quote-modal', this.openQuoteFromDemo)
  }
}
</script>
