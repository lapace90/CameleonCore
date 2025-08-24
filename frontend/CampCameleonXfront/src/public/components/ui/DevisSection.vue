<template>
    <section class="devis-section">
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
                        <button @click="openQuoteModal" class="quote-btn" :disabled="isLoading">
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
</template>

<script>
export default {
    name: 'DevisSection',
    data() {
        return {
            isLoading: false
        }
    },
    methods: {
        openQuoteModal() {
            this.isLoading = true;

            // Simulation de chargement
            setTimeout(() => {
                this.isLoading = false;
                // Émet l'événement pour ouvrir la modal
                this.$emit('open-quote-modal');

                // Optionnel : tracking analytics
                console.log('🧮 Quote modal requested');
            }, 500);
        }
    }
}
</script>

