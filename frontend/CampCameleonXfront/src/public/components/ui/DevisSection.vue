<template>
    <section class="devis-section" id="devis-section">
        <div class="container">
            <!-- Éléments décoratifs subtils -->
            <div class="decorative-elements">
                <div class="shape shape-1"></div>
                <div class="shape shape-2"></div>
                <div class="shape shape-3"></div>
                <div class="floating-icon icon-1">
                    <i class="fas fa-compass"></i>
                </div>
                <div class="floating-icon icon-2">
                    <i class="fas fa-map"></i>
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
                            <i v-if="isLoading" class="fas fa-spinner fa-spin"></i>
                            <i v-else class="fas fa-calculator"></i>
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
                                <i class="fas fa-magic"></i>
                            </div>
                            <span>Personnalisable</span>
                        </div>
                        <div class="feature-item">
                            <div class="feature-icon">
                                <i class="fas fa-clock"></i>
                            </div>
                            <span>Instantané</span>
                        </div>
                        <div class="feature-item">
                            <div class="feature-icon">
                                <i class="fas fa-shield-alt"></i>
                            </div>
                            <span>Sans engagement</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <QuoteModal :show="showQuote" @close="showQuote = false" @quote-saved="onBooking" />
</template>

<script>
import QuoteModal from '@/public/views/QuoteModal.vue'
export default {
    name: 'DevisSection',
    components: { QuoteModal },
    data() {
        return {
            showQuote: false,
            isLoading: false  
        }
    },
    methods: {
        onBooking(data) {
            console.log('Réservation:', data)
        }
    }
}
</script>
