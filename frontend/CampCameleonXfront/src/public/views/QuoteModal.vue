<template>
    <transition name="modal-fade">
        <div v-if="show" class="modal-overlay" @click="closeModal">
            <div class="quote-modal" @click.stop>
                <!-- Header -->
                <div class="modal-header">
                    <h2 class="modal-title">
                        <i class="fas fa-calculator"></i>
                        Créer mon devis personnalisé
                    </h2>
                    <button @click="closeModal" class="btn-close">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <!-- Contenu -->
                <div class="modal-content">
                    <!-- Étapes de progression -->
                    <div class="progress-steps">
                        <div class="step" :class="{ active: currentStep === 1, completed: currentStep > 1 }">
                            <div class="step-number">1</div>
                            <span>Activités</span>
                        </div>
                        <div class="step" :class="{ active: currentStep === 2, completed: currentStep > 2 }">
                            <div class="step-number">2</div>
                            <span>Hébergement</span>
                        </div>
                        <div class="step" :class="{ active: currentStep === 3, completed: currentStep > 3 }">
                            <div class="step-number">3</div>
                            <span>Dates</span>
                        </div>
                        <div class="step" :class="{ active: currentStep === 4 }">
                            <div class="step-number">4</div>
                            <span>Récapitulatif</span>
                        </div>
                    </div>

                    <!-- Step 1: Activités -->
                    <div v-if="currentStep === 1" class="step-content">
                        <h3 class="step-title">Choisissez vos activités</h3>
                        <p class="step-description">Sélectionnez les expériences qui vous tentent dans le désert
                            marocain</p>

                        <div v-if="loadingActivities" class="loading-state">
                            <div class="spinner"></div>
                            <p>Chargement des activités...</p>
                        </div>

                        <div v-else class="products-grid">
                            <div v-for="activity in availableActivities" :key="activity.id" class="product-card"
                                :class="{ selected: selectedItems.activities.some(a => a.id === activity.id) }"
                                @click="toggleActivity(activity)">
                                <div class="product-image">
                                    <img :src="activity.image" :alt="activity.name" />
                                    <div class="product-overlay">
                                        <i class="fas fa-check"
                                            v-if="selectedItems.activities.some(a => a.id === activity.id)"></i>
                                        <i class="fas fa-plus" v-else></i>
                                    </div>
                                </div>
                                <div class="product-info">
                                    <h4>{{ activity.name }}</h4>
                                    <p class="price">{{ activity.formatted_price }}</p>
                                    <p class="description">{{ activity.short_description }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Step 2: Hébergements -->
                    <div v-if="currentStep === 2" class="step-content">
                        <h3 class="step-title">Choisissez votre hébergement</h3>
                        <p class="step-description">Sélectionnez le type de logement pour votre séjour</p>

                        <div v-if="loadingRooms" class="loading-state">
                            <div class="spinner"></div>
                            <p>Chargement des hébergements...</p>
                        </div>

                        <div v-else class="products-grid">
                            <div v-for="room in availableRooms" :key="room.id" class="product-card"
                                :class="{ selected: selectedItems.room?.id === room.id }" @click="selectRoom(room)">
                                <div class="product-image">
                                    <img :src="room.image" :alt="room.name" />
                                    <div class="product-overlay">
                                        <i class="fas fa-check" v-if="selectedItems.room?.id === room.id"></i>
                                        <i class="fas fa-plus" v-else></i>
                                    </div>
                                </div>
                                <div class="product-info">
                                    <h4>{{ room.name }}</h4>
                                    <p class="price">{{ room.formatted_price }}/nuit</p>
                                    <p class="description">{{ room.short_description }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Step 3: Dates -->
                    <div v-if="currentStep === 3" class="step-content">
                        <h3 class="step-title">Choisissez vos dates</h3>
                        <p class="step-description">Sélectionnez la période de votre séjour</p>

                        <div class="date-selection">
                            <div class="date-row">
                                <div class="date-field">
                                    <label>Date d'arrivée</label>
                                    <input type="date" v-model="selectedDates.start" :min="minDate"
                                        class="date-input" />
                                </div>
                                <div class="date-field">
                                    <label>Date de départ</label>
                                    <input type="date" v-model="selectedDates.end" :min="selectedDates.start"
                                        class="date-input" />
                                </div>
                            </div>

                            <div class="guests-field">
                                <label>Nombre de personnes</label>
                                <div class="quantity-selector">
                                    <button @click="decreaseGuests" type="button" class="quantity-btn">-</button>
                                    <span class="quantity-value">{{ selectedDates.guests }}</span>
                                    <button @click="increaseGuests" type="button" class="quantity-btn">+</button>
                                </div>
                            </div>

                            <div v-if="durationInDays > 0" class="duration-info">
                                <i class="fas fa-calendar"></i>
                                Durée du séjour : {{ durationInDays }} nuit{{ durationInDays > 1 ? 's' : '' }}
                            </div>
                        </div>
                    </div>

                    <!-- Step 4: Récapitulatif -->
                    <div v-if="currentStep === 4" class="step-content">
                        <h3 class="step-title">Récapitulatif de votre devis</h3>

                        <div class="quote-summary">
                            <!-- Activités sélectionnées -->
                            <div v-if="selectedItems.activities.length > 0" class="summary-section">
                                <h4><i class="fas fa-hiking"></i> Activités</h4>
                                <div v-for="activity in selectedItems.activities" :key="activity.id"
                                    class="summary-item">
                                    <span>{{ activity.name }}</span>
                                    <span>{{ activity.formatted_price }}</span>
                                </div>
                            </div>

                            <!-- Hébergement sélectionné -->
                            <div v-if="selectedItems.room" class="summary-section">
                                <h4><i class="fas fa-bed"></i> Hébergement</h4>
                                <div class="summary-item">
                                    <span>{{ selectedItems.room.name }} ({{ durationInDays }} nuit{{ durationInDays > 1
                                        ? 's' : '' }})</span>
                                    <span>{{ formatPrice(selectedItems.room.price * durationInDays) }}</span>
                                </div>
                            </div>

                            <!-- Dates et invités -->
                            <div class="summary-section">
                                <h4><i class="fas fa-calendar"></i> Détails du séjour</h4>
                                <div class="summary-item">
                                    <span>Du {{ formatDate(selectedDates.start) }} au {{ formatDate(selectedDates.end)
                                        }}</span>
                                </div>
                                <div class="summary-item">
                                    <span>{{ selectedDates.guests }} personne{{ selectedDates.guests > 1 ? 's' : ''
                                        }}</span>
                                </div>
                            </div>

                            <!-- Total -->
                            <div class="summary-total">
                                <div class="total-line">
                                    <span>Total estimé</span>
                                    <strong>{{ formatPrice(totalPrice) }}</strong>
                                </div>
                            </div>
                        </div>

                        <!-- Formulaire contact -->
                        <div class="contact-form">
                            <h4>Vos coordonnées</h4>
                            <div class="form-row">
                                <input type="text" v-model="contactInfo.name" placeholder="Votre nom complet"
                                    class="form-input" />
                                <input type="email" v-model="contactInfo.email" placeholder="Votre email"
                                    class="form-input" />
                            </div>
                            <input type="tel" v-model="contactInfo.phone" placeholder="Votre téléphone"
                                class="form-input" />
                            <textarea v-model="contactInfo.message"
                                placeholder="Message ou demandes particulières (optionnel)"
                                class="form-textarea"></textarea>
                        </div>
                    </div>
                </div>

                <!-- Footer avec actions -->
                <div class="modal-footer">
                    <div class="footer-left">
                        <button v-if="currentStep > 1" @click="previousStep" class="btn btn-outline">
                            <i class="fas fa-arrow-left"></i>
                            Précédent
                        </button>
                    </div>

                    <div class="footer-right">
                        <button v-if="currentStep < 4" @click="nextStep" class="btn btn-primary"
                            :disabled="!canProceed">
                            Suivant
                            <i class="fas fa-arrow-right"></i>
                        </button>

                        <button v-if="currentStep === 4" @click="submitQuote" class="btn btn-success"
                            :disabled="isSubmitting || !canSubmit">
                            <i v-if="isSubmitting" class="fas fa-spinner fa-spin"></i>
                            <i v-else class="fas fa-paper-plane"></i>
                            {{ isSubmitting ? 'Envoi...' : 'Envoyer ma demande' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </transition>
</template>

<script>
import { publicApi } from '@/services/PublicApi'

export default {
    name: 'QuoteModal',
    props: {
        show: {
            type: Boolean,
            default: false
        }
    },
    data() {
        return {
            currentStep: 1,
            loadingActivities: false,
            loadingRooms: false,
            isSubmitting: false,

            // Produits disponibles
            availableActivities: [],
            availableRooms: [],

            // Sélections utilisateur
            selectedItems: {
                activities: [],
                room: null
            },

            selectedDates: {
                start: '',
                end: '',
                guests: 2
            },

            contactInfo: {
                name: '',
                email: '',
                phone: '',
                message: ''
            }
        }
    },

    computed: {
        minDate() {
            const tomorrow = new Date()
            tomorrow.setDate(tomorrow.getDate() + 1)
            return tomorrow.toISOString().split('T')[0]
        },

        durationInDays() {
            if (!this.selectedDates.start || !this.selectedDates.end) return 0

            const start = new Date(this.selectedDates.start)
            const end = new Date(this.selectedDates.end)
            const diffTime = Math.abs(end - start)
            return Math.ceil(diffTime / (1000 * 60 * 60 * 24))
        },

        totalPrice() {
            let total = 0

            // Prix des activités
            this.selectedItems.activities.forEach(activity => {
                total += activity.price
            })

            // Prix de l'hébergement
            if (this.selectedItems.room && this.durationInDays > 0) {
                total += this.selectedItems.room.price * this.durationInDays
            }

            return total
        },

        canProceed() {
            switch (this.currentStep) {
                case 1: return this.selectedItems.activities.length > 0
                case 2: return this.selectedItems.room !== null
                case 3: return this.selectedDates.start && this.selectedDates.end && this.durationInDays > 0
                default: return true
            }
        },

        canSubmit() {
            return this.contactInfo.name &&
                this.contactInfo.email &&
                this.contactInfo.phone &&
                this.totalPrice > 0
        }
    },

    watch: {
        show(newValue) {
            if (newValue) {
                this.initializeModal()
            }
        }
    },

    methods: {
        async initializeModal() {
            this.currentStep = 1
            this.resetSelections()
            await this.loadProducts()
        },

        async loadProducts() {
            // Charger activités
            this.loadingActivities = true
            try {
                const activitiesResponse = await publicApi.getActivities({ per_page: 20 })
                this.availableActivities = activitiesResponse.data || []
            } catch (error) {
                console.error('Erreur chargement activités:', error)
            } finally {
                this.loadingActivities = false
            }

            // Charger hébergements
            this.loadingRooms = true
            try {
                const roomsResponse = await publicApi.getRooms({ per_page: 20 })
                this.availableRooms = roomsResponse.data || []
            } catch (error) {
                console.error('Erreur chargement hébergements:', error)
            } finally {
                this.loadingRooms = false
            }
        },

        toggleActivity(activity) {
            const index = this.selectedItems.activities.findIndex(a => a.id === activity.id)
            if (index > -1) {
                this.selectedItems.activities.splice(index, 1)
            } else {
                this.selectedItems.activities.push(activity)
            }
        },

        selectRoom(room) {
            this.selectedItems.room = room
        },

        increaseGuests() {
            if (this.selectedDates.guests < 10) {
                this.selectedDates.guests++
            }
        },

        decreaseGuests() {
            if (this.selectedDates.guests > 1) {
                this.selectedDates.guests--
            }
        },

        nextStep() {
            if (this.canProceed && this.currentStep < 4) {
                this.currentStep++
            }
        },

        previousStep() {
            if (this.currentStep > 1) {
                this.currentStep--
            }
        },

        async submitQuote() {
            if (!this.canSubmit) return

            this.isSubmitting = true

            try {
                const quoteData = {
                    activities: this.selectedItems.activities.map(a => a.id),
                    room: this.selectedItems.room?.id,
                    dates: this.selectedDates,
                    contact: this.contactInfo,
                    total_price: this.totalPrice
                }

                await publicApi.createQuoteRequest(quoteData)

                // Succès
                this.$emit('quote-submitted', quoteData)
                this.closeModal()

                // Notification de succès (vous pouvez adapter selon votre système)
                alert('Votre demande de devis a été envoyée avec succès ! Nous vous recontacterons rapidement.')

            } catch (error) {
                console.error('Erreur envoi devis:', error)
                alert('Une erreur est survenue. Veuillez réessayer.')
            } finally {
                this.isSubmitting = false
            }
        },

        closeModal() {
            this.$emit('close')
        },

        resetSelections() {
            this.selectedItems = {
                activities: [],
                room: null
            }
            this.selectedDates = {
                start: '',
                end: '',
                guests: 2
            }
            this.contactInfo = {
                name: '',
                email: '',
                phone: '',
                message: ''
            }
        },

        formatPrice(price) {
            return new Intl.NumberFormat('fr-FR', {
                style: 'currency',
                currency: 'EUR'
            }).format(price)
        },

        formatDate(dateString) {
            return new Date(dateString).toLocaleDateString('fr-FR', {
                day: 'numeric',
                month: 'long',
                year: 'numeric'
            })
        }
    }
}
</script>

<style lang="scss" scoped>
// Variables reprises de votre design system
$primary: #5e72e4;
$success: #2dce89;
$warning: #fb6340;
$danger: #f5365c;
$terracotta: #c17c4a;

.quote-modal {
    background: white;
    border-radius: 12px;
    width: 100%;
    max-width: 900px;
    max-height: 90vh;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    box-shadow: 0 25px 50px rgba(0, 0, 0, 0.25);
}

.progress-steps {
    display: flex;
    justify-content: center;
    gap: 16px;
    margin-bottom: 32px;

    .step {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 8px 16px;
        border-radius: 8px;
        font-size: 14px;
        color: #6b7280;
        transition: all 0.3s;

        &.active {
            background: rgba($primary, 0.1);
            color: $primary;
            font-weight: 600;
        }

        &.completed {
            color: $success;
        }

        .step-number {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            background: #e5e7eb;
            color: #6b7280;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: 600;
        }

        &.active .step-number {
            background: $primary;
            color: white;
        }

        &.completed .step-number {
            background: $success;
            color: white;
        }
    }
}

.step-content {
    .step-title {
        font-size: 1.5rem;
        margin-bottom: 8px;
        color: #1f2937;
    }

    .step-description {
        color: #6b7280;
        margin-bottom: 24px;
    }
}

.products-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 20px;
}

.product-card {
    border: 2px solid #e5e7eb;
    border-radius: 12px;
    overflow: hidden;
    cursor: pointer;
    transition: all 0.3s;
    background: white;

    &:hover {
        border-color: $primary;
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
    }

    &.selected {
        border-color: $success;
        background: rgba($success, 0.05);
    }

    .product-image {
        position: relative;
        height: 160px;
        overflow: hidden;

        img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .product-overlay {
            position: absolute;
            top: 12px;
            right: 12px;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.9);
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s;
        }
    }

    &.selected .product-overlay {
        background: $success;
        color: white;
    }

    .product-info {
        padding: 16px;

        .price {
            font-weight: 600;
            color: $terracotta;
            margin: 4px 0;
        }

        .description {
            font-size: 14px;
            color: #6b7280;
            margin: 0;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
    }
}

.date-selection {
    max-width: 500px;
    margin: 0 auto;

    .date-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
        margin-bottom: 20px;
    }

    .date-field {
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #374151;
        }

        .date-input {
            width: 100%;
            padding: 12px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s;

            &:focus {
                outline: none;
                border-color: $primary;
            }
        }
    }

    .guests-field {
        margin-bottom: 20px;

        .quantity-selector {
            display: flex;
            align-items: center;
            gap: 16px;
            justify-content: center;

            .quantity-btn {
                width: 40px;
                height: 40px;
                border-radius: 50%;
                border: 2px solid $primary;
                background: white;
                color: $primary;
                font-size: 18px;
                font-weight: 600;
                cursor: pointer;
                transition: all 0.3s;

                &:hover {
                    background: $primary;
                    color: white;
                }
            }

            .quantity-value {
                font-size: 1.2rem;
                font-weight: 600;
                min-width: 60px;
                text-align: center;
            }
        }
    }

    .duration-info {
        text-align: center;
        color: $primary;
        font-weight: 600;
        background: rgba($primary, 0.1);
        padding: 12px;
        border-radius: 8px;

        i {
            margin-right: 8px;
        }
    }
}

.quote-summary {
    background: #f9fafb;
    border-radius: 12px;
    padding: 24px;
    margin-bottom: 24px;

    .summary-section {
        margin-bottom: 20px;

        &:last-child {
            margin-bottom: 0;
        }

        h4 {
            color: #374151;
            margin-bottom: 12px;

            i {
                margin-right: 8px;
                color: $primary;
            }
        }

        .summary-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
            border-bottom: 1px solid #e5e7eb;

            &:last-child {
                border-bottom: none;
            }
        }
    }

    .summary-total {
        border-top: 2px solid #e5e7eb;
        padding-top: 16px;
        margin-top: 20px;

        .total-line {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 1.2rem;

            strong {
                color: $success;
                font-size: 1.4rem;
            }
        }
    }
}

.contact-form {
    h4 {
        margin-bottom: 16px;
        color: #374151;
    }

    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 16px;
        margin-bottom: 16px;
    }

    .form-input,
    .form-textarea {
        width: 100%;
        padding: 12px;
        border: 2px solid #e5e7eb;
        border-radius: 8px;
        font-size: 16px;
        transition: border-color 0.3s;

        &:focus {
            outline: none;
            border-color: $primary;
        }
    }

    .form-input {
        margin-bottom: 16px;
    }

    .form-textarea {
        min-height: 80px;
        resize: vertical;
    }
}

.modal-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 24px;
    border-top: 1px solid #e5e7eb;
    background: #f9fafb;

}

.loading-state {
    text-align: center;
    padding: 40px;
    color: #6b7280;
}

@keyframes spin {
    0% {
        transform: rotate(0deg);
    }

    100% {
        transform: rotate(360deg);
    }
}

// Animations
.modal-fade-enter-active,
.modal-fade-leave-active {
    transition: opacity 0.3s;
}

.modal-fade-enter-from,
.modal-fade-leave-to {
    opacity: 0;
}

// Responsive
@media (max-width: 768px) {
    .modal-overlay {
        padding: 10px;
    }

    .quote-modal {
        max-height: 95vh;
    }

    .products-grid {
        grid-template-columns: 1fr;
    }

    .date-row {
        grid-template-columns: 1fr !important;
    }

    .form-row {
        grid-template-columns: 1fr !important;
    }

    .progress-steps {
        flex-wrap: wrap;
        gap: 8px;

        .step {
            padding: 6px 12px;
            font-size: 12px;

            span {
                display: none;
            }
        }
    }
}
</style>