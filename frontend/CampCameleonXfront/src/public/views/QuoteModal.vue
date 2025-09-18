<template>
    <transition name="modal-fade">
        <div v-if="show" class="modal-overlay" @click="closeModal" aria-modal="true" role="dialog">
            <div class="quote-modal" @click.stop>
                <!-- Header -->
                <div class="modal-header">
                    <h2 class="modal-title">
                        <i class="fas fa-calculator"></i>
                        Créer mon devis personnalisé
                    </h2>
                    <button @click="closeModal" class="btn-close" aria-label="Fermer">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <!-- Contenu -->
                <div class="modal-body">
                    <!-- Étapes -->
                    <div class="progress-steps">
                        <div class="step" :class="{ active: currentStep === 1, completed: currentStep > 1 }">
                            <div class="step-number">1</div><span>Dates & personnes</span>
                        </div>
                        <div class="step" :class="{ active: currentStep === 2, completed: currentStep > 2 }">
                            <div class="step-number">2</div><span>Activités</span>
                        </div>
                        <div class="step" :class="{ active: currentStep === 3, completed: currentStep > 3 }">
                            <div class="step-number">3</div><span>Menus</span>
                        </div>
                        <div class="step" :class="{ active: currentStep === 4, completed: currentStep > 4 }">
                            <div class="step-number">4</div><span>Hébergement</span>
                        </div>
                        <div class="step" :class="{ active: currentStep === 5 }">
                            <div class="step-number">5</div><span>Récapitulatif</span>
                        </div>
                    </div>

                    <!-- STEP 1 -->
                    <div v-if="currentStep === 1" class="step-content">
                        <h3 class="step-title">Choisissez vos dates</h3>
                        <p class="step-description">Sélectionnez directement dans le calendrier (plage continue).</p>
                        <div class="step-help" :class="{ 'help-warning': currentStep === 4 && !selectedItems.room }">
                            <i class="fas fa-info-circle"></i>
                            {{ stepHelpText }}
                        </div>

                        <div class="dates-layout">
                            <!-- Calendrier simplifié -->
                            <div class="calendar-container">
                                <div class="calendar-header">
                                    <h4 class="calendar-title">
                                        <i class="fas fa-calendar-alt"></i> Sélectionnez vos dates
                                    </h4>
                                </div>
                                <div class="calendar calendar--compact">
                                    <FullCalendar :options="fcOptions" ref="fc" />
                                </div>

                                <div class="range-info" v-if="selectedDates.start && selectedDates.endExclusive">
                                    <i class="fas fa-calendar-check"></i>
                                    Séjour : {{ formatDate(selectedDates.start) }} →
                                    {{ formatDate(displayEndInclusive(selectedDates.endExclusive)) }}
                                </div>
                            </div>

                            <!-- Personnes -->
                            <div class="date-controls">
                                <div class="field-group">
                                    <label class="field-label">Nombre de personnes</label>
                                    <div class="qty">
                                        <button type="button" class="qty-btn" @click="decreaseGuests"
                                            aria-label="Diminuer">−</button>
                                        <span class="qty-value" aria-live="polite">{{ selectedDates.guests }}</span>
                                        <button type="button" class="qty-btn" @click="increaseGuests"
                                            aria-label="Augmenter">+</button>
                                    </div>
                                </div>

                                <div v-if="nights > 0" class="duration">
                                    <i class="fas fa-moon"></i>
                                    {{ nights }} nuit{{ nights > 1 ? 's' : '' }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- STEP 2: Activités -->
                    <div v-if="currentStep === 2" class="step-content">
                        <h3 class="step-title">Choisissez vos activités</h3>
                        <p class="step-description">Sélectionnez les expériences qui vous tentent.</p>

                        <div v-if="loading" class="loading-state">
                            <div class="spinner"></div>
                            <p>Chargement des activités...</p>
                        </div>

                        <div v-else class="mini-grid">
                            <article v-for="activity in availableActivities" :key="activity.id" class="mini-card"
                                :class="{ selected: selectedItems.activities.some(a => a.id === activity.id) }"
                                @click="toggleActivity(activity)">
                                <img class="mini-thumb" :src="activity.image" :alt="activity.name" loading="lazy"
                                    decoding="async" />
                                <div class="mini-info">
                                    <h5 class="mini-title">{{ activity.name }}</h5>
                                    <div class="mini-meta">
                                        <span class="mini-price">{{ activity.formatted_price }}</span>
                                    </div>
                                    <p class="mini-desc">
                                        {{ (activity.description || '').slice(0, 90) }}<span
                                            v-if="(activity.description || '').length > 90">…</span>
                                    </p>
                                </div>
                                <div class="mini-check">
                                    <i class="fas"
                                        :class="selectedItems.activities.some(a => a.id === activity.id) ? 'fa-check' : 'fa-plus'"></i>
                                </div>
                            </article>
                        </div>
                    </div>

                    <!-- STEP 3: Menus -->
                    <div v-if="currentStep === 3" class="step-content">
                        <h3 class="step-title">Choisissez vos menus</h3>
                        <p class="step-description">Sélectionnez les repas qui vous intéressent.</p>

                        <div v-if="loading" class="loading-state">
                            <div class="spinner"></div>
                            <p>Chargement des menus...</p>
                        </div>

                        <div v-else class="mini-grid">
                            <article v-for="menu in availableMenus" :key="menu.id" class="mini-card"
                                :class="{ selected: selectedItems.menus.some(m => m.id === menu.id) }"
                                @click="toggleMenu(menu)">
                                <img class="mini-thumb" :src="menu.image" :alt="menu.name" loading="lazy"
                                    decoding="async" />
                                <div class="mini-info">
                                    <h5 class="mini-title">{{ menu.name }}</h5>
                                    <div class="mini-meta">
                                        <span class="mini-price">{{ menu.formatted_price }}</span>
                                    </div>
                                    <p class="mini-desc">
                                        {{ (menu.description || menu.short_description || '').slice(0, 90) }}<span
                                            v-if="(menu.description || menu.short_description || '').length > 90">…</span>
                                    </p>
                                </div>
                                <div class="mini-check">
                                    <i class="fas"
                                        :class="selectedItems.menus.some(m => m.id === menu.id) ? 'fa-check' : 'fa-plus'"></i>
                                </div>
                            </article>
                        </div>
                    </div>

                    <!-- STEP 4: Hébergement -->
                    <div v-if="currentStep === 4" class="step-content">
                        <h3 class="step-title">Choisissez votre hébergement</h3>
                        <p class="step-description">Sélectionnez votre logement.</p>

                        <div v-if="loading" class="loading-state">
                            <div class="spinner"></div>
                            <p>Chargement des hébergements...</p>
                        </div>

                        <div v-else class="mini-grid">
                            <article v-for="room in availableRooms" :key="room.id" class="mini-card"
                                :class="{ selected: selectedItems.room?.id === room.id }" @click="selectRoom(room)">
                                <img class="mini-thumb" :src="room.image" :alt="room.name" loading="lazy"
                                    decoding="async" />
                                <div class="mini-info">
                                    <h5 class="mini-title">{{ room.name }}</h5>
                                    <div class="mini-meta">
                                        <span class="mini-price">{{ room.formatted_price }}/nuit</span>
                                        <!-- ✅ SEULE INFO PRODUCTABLE : Capacité -->
                                        <span v-if="room.productable_data?.capacity || room.productableData?.capacity"
                                            class="mini-pill">
                                            <i class="fas fa-users"></i>
                                            {{ room.productable_data?.capacity || room.productableData?.capacity }}
                                            pers. max
                                        </span>
                                    </div>
                                    <p class="mini-desc">
                                        {{ (room.description || room.short_description || '').slice(0, 90) }}<span
                                            v-if="(room.description || room.short_description || '').length > 90">…</span>
                                    </p>
                                </div>
                                <div class="mini-check">
                                    <i class="fas"
                                        :class="selectedItems.room?.id === room.id ? 'fa-check' : 'fa-plus'"></i>
                                </div>
                            </article>
                        </div>
                    </div>

                    <!-- STEP 5: Récap -->
                    <div v-if="currentStep === 5" class="step-content">
                        <h3 class="step-title">Récapitulatif de votre devis</h3>

                        <div class="quote-summary">
                            <div v-if="selectedItems.activities.length" class="summary-section">
                                <h4><i class="fas fa-hiking"></i> Activités</h4>
                                <div v-for="a in selectedItems.activities" :key="a.id" class="summary-item">
                                    <span>{{ a.name }}</span><span>{{ a.formatted_price }}</span>
                                </div>
                            </div>

                            <div v-if="selectedItems.menus.length" class="summary-section">
                                <h4><i class="fas fa-utensils"></i> Menus</h4>
                                <div v-for="m in selectedItems.menus" :key="m.id" class="summary-item">
                                    <span>{{ m.name }}</span><span>{{ m.formatted_price }}</span>
                                </div>
                            </div>

                            <div v-if="selectedItems.room" class="summary-section">
                                <h4><i class="fas fa-bed"></i> Hébergement</h4>
                                <div class="summary-item">
                                    <span>{{ selectedItems.room.name }} ({{ nights }} nuit{{ nights > 1
                                        ? 's' : '' }})</span>
                                    <span>{{ formatPrice(selectedItems.room.price * nights) }}</span>
                                </div>
                            </div>

                            <div class="summary-section">
                                <h4><i class="fas fa-calendar"></i> Détails du séjour</h4>
                                <div class="summary-item">
                                    <span>Du {{ formatDate(selectedDates.start) }} au {{
                                        formatDate(displayEndInclusive(selectedDates.endExclusive)) }}
                                    </span>
                                </div>
                                <div class="summary-item">
                                    <span>{{ selectedDates.guests }} personne{{ selectedDates.guests > 1 ? 's' : ''
                                    }}</span>
                                </div>
                            </div>

                            <div class="summary-total">
                                <div class="total-line">
                                    <span>Total estimé</span>
                                    <strong>{{ formatPrice(totalPrice) }}</strong>
                                </div>
                            </div>
                        </div>

                        <!-- Coordonnées -->
                        <div class="contact-form">
                            <h4>Vos coordonnées</h4>
                            <div class="form-row">
                                <input type="text" v-model="contactInfo.name" placeholder="Prenom" class="form-input" />
                                <input type="text" v-model="contactInfo.last_name" placeholder="Nom"
                                    class="form-input" />
                            </div>
                            <div class="form-row">
                                <input type="email" v-model="contactInfo.email" placeholder="Email"
                                    class="form-input" />
                                <input type="tel" v-model="contactInfo.phone" placeholder="Téléphone"
                                    class="form-input" />
                            </div>
                            <textarea v-model="contactInfo.message" placeholder="Message (optionnel)"
                                class="form-textarea"></textarea>
                        </div>
                    </div>
                </div>
                <!-- Footer -->
                <div class="modal-footer">
                    <div class="footer-left">
                        <button v-if="currentStep > 1" @click="previousStep" class="btn btn-outline btn-sm">
                            <i class="fas fa-arrow-left"></i> Précédent
                        </button>
                    </div>
                    <div class="footer-right">
                        <button v-if="currentStep < 5" @click="nextStep" class="btn btn-primary btn-sm"
                            :disabled="!canProceed">
                            Suivant <i class="fas fa-arrow-right"></i>
                        </button>

                        <!-- NOUVELLE SECTION: 3 actions pour l'étape 5 -->
                        <div v-else class="quote-final-step">
                            <!-- Explication des options -->
                            <div class="actions-explanation">
                                <p class="explanation-text">
                                    <i class="fas fa-info-circle"></i>
                                    Choisissez comment finaliser votre demande :
                                </p>
                            </div>

                            <div class="quote-actions">
                                <!-- Action 1: Réserver et payer -->
                                <button @click="createReservationAndPay" class="btn btn-success btn-sm"
                                    :disabled="isSubmitting || !canSubmit">
                                    <i v-if="isSubmitting === 'booking'" class="fas fa-spinner fa-spin"></i>
                                    <i v-else class="fas fa-credit-card"></i>
                                    {{ isSubmitting === 'booking' ? 'Traitement...' : 'Réserver & Payer' }}
                                </button>

                                <!-- Action 2: Sauvegarder et voir contacts -->
                                <button @click="saveQuoteAndShowContacts" class="btn btn-primary btn-sm"
                                    :disabled="isSubmitting || !canSubmit">
                                    <i v-if="isSubmitting === 'saving'" class="fas fa-spinner fa-spin"></i>
                                    <i v-else class="fas fa-bookmark"></i>
                                    {{ isSubmitting === 'saving' ? 'Sauvegarde...' : 'Sauvegarder le devis' }}
                                </button>

                                <!-- Action 3: Conseil personnalisé -->
                                <button @click="requestAdvice" class="btn btn-outline btn-sm"
                                    :disabled="isSubmitting || !canSubmit">
                                    <i v-if="isSubmitting === 'advice'" class="fas fa-spinner fa-spin"></i>
                                    <i v-else class="fas fa-user-tie"></i>
                                    {{ isSubmitting === 'advice' ? 'Envoi...' : 'Conseil personnalisé' }}
                                </button>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </transition>
</template>

<script>
import { nextTick } from 'vue'
import { publicApi } from '@/services/PublicApi'
import FullCalendar from '@fullcalendar/vue3'
import dayGridPlugin from '@fullcalendar/daygrid'
import interactionPlugin from '@fullcalendar/interaction'
import frLocale from '@fullcalendar/core/locales/fr'

export default {
    name: 'QuoteModal',
    components: { FullCalendar },

    props: { show: { type: Boolean, default: false } },

    data() {
        return {
            currentStep: 1,
            loading: false,
            isSubmitting: false,

            // ✅ SIMPLIFIÉ : Une seule liste au lieu de 3
            allProducts: [], // Tous les produits depuis /api/products

            selectedItems: { activities: [], room: null, menus: [] },
            selectedDates: { start: '', endExclusive: '', guests: 2 },
            contactInfo: { name: '', email: '', phone: '', message: '' },
        }
    },

    computed: {
        // Filtrer seulement les types utiles pour les devis
        relevantProducts() {
            return this.allProducts.filter(p => {
                const label = p.typeConfig?.label
                return ['Activités', 'Menus', 'Hébergements', 'Rooms'].includes(label)
            })
        },

        availableActivities() {
            return this.relevantProducts.filter(p =>
                p.typeConfig?.label === 'Activités'
            )
        },

        availableMenus() {
            return this.relevantProducts.filter(p =>
                p.typeConfig?.label === 'Menus'
            )
        },

        availableRooms() {
            return this.relevantProducts.filter(p =>
                p.typeConfig?.label === 'Hébergements' || p.typeConfig?.label === 'Rooms'
            ).filter(room => {
                const capacity = room.productable_data?.capacity || room.productableData?.capacity
                if (!capacity) return true
                return capacity >= this.selectedDates.guests
            })
        },

        calendarApi() { return this.$refs.fc?.getApi?.() },
        stepHelpText() {
            switch (this.currentStep) {
                case 1: return this.datesOK && this.selectedDates.guests >= 1
                    ? 'Parfait ! Continuez vers les activités.'
                    : 'Sélectionnez vos dates et le nombre de personnes pour continuer.'
                case 2: return 'Les activités sont optionnelles, vous pouvez passer à l\'étape suivante.'
                case 3: return 'Les menus sont optionnels, vous pouvez passer à l\'étape suivante.'
                case 4: return this.selectedItems.room
                    ? 'Parfait ! Votre hébergement est sélectionné.'
                    : 'Veuillez choisir un hébergement pour continuer.'
                case 5: return this.canSubmit
                    ? 'Vérifiez vos informations et choisissez votre action.'
                    : 'Complétez vos coordonnées pour finaliser.'
                default: return ''
            }
        },
        minDate() {
            const d = new Date()
            d.setDate(d.getDate() + 1)
            return d.toISOString().split('T')[0]
        },

        durationInDays() {
            const { start, endExclusive } = this.selectedDates
            return this.daysBetween(start, endExclusive)
        },
        nights() {
            const d = this.durationInDays
            return d > 0 ? d - 1 : 0
        },

        totalPrice() {
            let total = 0
            for (const a of this.selectedItems.activities) total += Number(a.price || 0)
            for (const m of this.selectedItems.menus) total += Number(m.price || 0)
            if (this.selectedItems.room && this.durationInDays > 0) {
                total += Number(this.selectedItems.room.price || 0) * this.durationInDays
            }
            return total
        },

        datesOK() {
            return !!this.selectedDates.start && !!this.selectedDates.endExclusive && this.durationInDays > 0
        },

        canProceed() {
            switch (this.currentStep) {
                case 1: return this.datesOK && this.selectedDates.guests >= 1
                case 2: return true // activités optionnelles
                case 3: return true // menus optionnels
                case 4: return !!this.selectedItems.room // hébergement OBLIGATOIRE
                default: return true
            }
        },

        canSubmit() {
            const c = this.contactInfo
            return !!c.name && !!c.email && !!c.phone && this.totalPrice > 0
        },

        fcOptions() {
            return {
                plugins: [dayGridPlugin, interactionPlugin],
                locales: [frLocale],
                locale: 'fr',
                initialView: 'dayGridMonth',
                firstDay: 1,

                // ✅ plus compact
                height: 'auto',
                contentHeight: 260,   // 240–300 si tu veux
                aspectRatio: 1.6,
                handleWindowResize: false,
                headerToolbar: { left: 'prev,next today', center: 'title', right: '' },
                dayHeaderFormat: { weekday: 'short' },
                titleFormat: { year: 'numeric', month: 'long' },
                selectable: true,
                selectMirror: true,
                fixedWeekCount: false,
                validRange: { start: this.minDate },

                // navigation natif
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: ''
                },

                select: this.fcOnSelect,
            }
        }

    },

    watch: {
        show(val) { if (val) this.initializeModal() }
    },

    methods: {
        async initializeModal() {
            this.currentStep = 1
            this.resetSelections()
            await this.loadAllProducts()
            await nextTick()
        },

        async loadAllProducts() {
            this.loading = true
            console.log('🔍 Chargement de tous les produits...')

            try {
                // UN SEUL APPEL API pour tous les produits actifs
                const response = await publicApi.getProducts({
                    status: 'active',
                    per_page: 100 // Assez pour avoir tous les produits actifs
                })
                console.log('🔍 Structure premier produit:', this.allProducts[0]) // AJOUTER CETTE LIGNE

                this.allProducts = response?.data || []

                console.log('✅ Produits chargés:', {
                    total: this.allProducts.length,
                    activities: this.availableActivities.length,
                    menus: this.availableMenus.length,
                    rooms: this.availableRooms.length,
                    sample_product: this.allProducts[0] // Voir la structure
                })

            } catch (error) {
                console.error('❌ Erreur chargement produits:', error)
                this.allProducts = []
            } finally {
                this.loading = false
            }
        },

        // Sélections simplifiées
        toggleActivity(activity) {
            const i = this.selectedItems.activities.findIndex(a => a.id === activity.id)
            if (i > -1) this.selectedItems.activities.splice(i, 1)
            else this.selectedItems.activities.push(activity)
        },

        toggleMenu(menu) {
            const i = this.selectedItems.menus.findIndex(m => m.id === menu.id)
            if (i > -1) {
                this.selectedItems.menus.splice(i, 1)
            } else {
                this.selectedItems.menus.push(menu)
            }
        },

        selectRoom(room) {
            this.selectedItems.room = room
        },

        increaseGuests() { if (this.selectedDates.guests < 20) this.selectedDates.guests++ },
        decreaseGuests() { if (this.selectedDates.guests > 1) this.selectedDates.guests-- },

        nextStep() { if (this.canProceed && this.currentStep < 5) this.currentStep++ },
        previousStep() { if (this.currentStep > 1) this.currentStep-- },

        closeModal() { this.$emit('close') },

        resetSelections() {
            this.selectedItems = { activities: [], room: null, menus: [] }
            this.selectedDates = { start: '', endExclusive: '', guests: 2 }
            this.contactInfo = { name: '', email: '', phone: '', message: '' }
        },
        toUTCDateParts(yyyyMmDd) {
            const [y, m, d] = (yyyyMmDd || '').split('-').map(Number)
            return isNaN(y) ? null : Date.UTC(y, (m || 1) - 1, d || 1)
        },
        daysBetween(startYmd, endExclusiveYmd) {
            const s = this.toUTCDateParts(startYmd)
            const e = this.toUTCDateParts(endExclusiveYmd)
            if (s == null || e == null) return 0
            const diff = (e - s) / (1000 * 60 * 60 * 24)
            return diff > 0 ? diff : 0
        },
        displayEndInclusive(endExclusiveYmd) {
            const e = this.toUTCDateParts(endExclusiveYmd)
            if (e == null) return ''
            const d = new Date(e - 24 * 60 * 60 * 1000)
            const yyyy = d.getUTCFullYear()
            const mm = String(d.getUTCMonth() + 1).padStart(2, '0')
            const dd = String(d.getUTCDate()).padStart(2, '0')
            return `${yyyy}-${mm}-${dd}`
        },

        formatPrice(n) {
            return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(Number(n || 0))
        },

        formatDate(s) {
            return s ? new Date(s).toLocaleDateString('fr-FR', { day: 'numeric', month: 'long', year: 'numeric' }) : ''
        },

        // FullCalendar simplifié
        fcOnSelect(info) {
            // FullCalendar: start inclusif, end exclusif
            this.selectedDates.start = info.startStr            // YYYY-MM-DD
            this.selectedDates.endExclusive = info.endStr       // YYYY-MM-DD (checkout)
        },
        // Action 1: Créer réservation directe + paiement
        async createReservationAndPay() {
            if (!this.canSubmit || this.isSubmitting) return;

            this.isSubmitting = 'booking';

            try {
                console.log('💳 Début processus paiement Stripe');

                // 1. Vérifier qu'on a bien un hébergement (obligatoire)
                if (!this.selectedItems.room) {
                    throw new Error('Veuillez sélectionner un hébergement avant de procéder au paiement.');
                }

                // 2. Créer le devis avec la méthode corrigée
                const quoteResponse = await this.saveQuote();

                if (!quoteResponse.success) {
                    throw new Error(quoteResponse.message || 'Impossible de créer le devis');
                }

                const quote = quoteResponse.quote_request;
                console.log('✅ Devis créé:', {
                    id: quote.id,
                    reference: quote.quote_reference,
                    status: quote.status,
                    product_ids: quote.selected_product_ids
                });

                // 3. Vérifier le statut du devis pour Stripe
                if (quote.status === 'draft') {
                    // Devis pas encore validé par email
                    this.showEmailValidationRequired(quote);
                    return;
                }

                // 4. Créer la session Stripe
                const paymentResponse = await this.createStripeSession(quote.id);

                if (!paymentResponse.success) {
                    throw new Error(paymentResponse.error || 'Impossible de créer la session de paiement');
                }

                console.log('🚀 Redirection vers Stripe:', paymentResponse.checkout_url);

                // 5. Rediriger vers Stripe Checkout
                window.location.href = paymentResponse.checkout_url;

            } catch (error) {
                console.error('❌ Erreur processus paiement:', error);

                if (error.message.includes('hébergement')) {
                    alert('🏕️ Hébergement obligatoire\n\nVeuillez sélectionner un hébergement à l\'étape 4 avant de procéder au paiement.');
                    this.currentStep = 4; // Retour à l'étape hébergement
                } else if (error.message.includes('validé par email')) {
                    alert('⚠️ Validation email requise\n\nVotre devis doit être validé par email avant le paiement.\nVérifiez votre boîte mail.');
                } else {
                    alert('❌ Erreur\n\n' + error.message);
                }

            } finally {
                this.isSubmitting = false;
            }
        },

        /**
         * Afficher le modal pour validation email requise
         */
        showEmailValidationRequired(quote) {
            this.closeModal();

            // Message personnalisé pour validation email
            const message = `
        📧 Validation email requise
        
        Votre devis ${quote.quote_reference} a été créé !
        
        Pour procéder au paiement, vous devez d'abord :
        1. Vérifier votre boîte email (${quote.email || this.contactInfo.email})
        2. Cliquer sur le lien de validation
        3. Revenir ici pour effectuer le paiement
        
        Le lien est valable 48h.
    `;

            alert(message);

            // Optionnel : rediriger vers une page dédiée
            // window.location.href = '/email-validation-pending?quote=' + quote.reference;
        },
        /**
         * Créer une session Stripe depuis un devis validé
         */
        async createStripeSession(quoteId) {
            try {
                const response = await fetch(`${import.meta.env.VITE_API_URL}/stripe/create-payment-session`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        quote_id: quoteId
                    })
                });

                const data = await response.json();

                if (!response.ok) {
                    console.error('❌ Erreur API Stripe:', {
                        status: response.status,
                        data: data
                    });
                    throw new Error(data.error || 'Erreur lors de la création de la session de paiement');
                }

                console.log('✅ Session Stripe créée:', {
                    session_id: data.session_id,
                    quote_reference: data.quote_reference
                });

                return data;

            } catch (error) {
                console.error('❌ Erreur createStripeSession:', error);
                throw error;
            }
        },

        // Action 2: Sauvegarder devis + afficher contacts
        async saveQuoteAndShowContacts() {
            if (!this.canSubmit || this.isSubmitting) return;

            this.isSubmitting = 'saving';

            try {
                console.log('💾 Sauvegarde devis pour validation email');

                // ✅ Vérifier qu'on a au moins un hébergement
                if (!this.selectedItems.room) {
                    throw new Error('Veuillez sélectionner un hébergement avant de sauvegarder le devis.');
                }

                // ✅ Utiliser la même méthode saveQuote() pour la cohérence
                const result = await this.saveQuote();

                if (result.success) {
                    // Afficher le message de succès
                    this.showSuccessModal({
                        title: '📧 Email de validation envoyé !',
                        message: `Votre devis ${result.quote_request.quote_reference} a été créé.
                         
                         Vérifiez votre boîte email et cliquez sur le lien pour recevoir votre devis détaillé.
                         
                         Le lien est valable 48h.`,
                        showContacts: true
                    });

                    // Émettre l'événement pour le composant parent
                    this.$emit('quote-saved', {
                        quote: result.quote_request,
                        type: 'email_validation_required'
                    });

                } else {
                    throw new Error(result.message || 'Erreur lors de la sauvegarde');
                }

            } catch (error) {
                console.error('❌ Erreur sauvegarde devis:', error);

                if (error.message.includes('hébergement')) {
                    alert('🏕️ Hébergement obligatoire\n\nVeuillez sélectionner un hébergement à l\'étape 4 avant de sauvegarder le devis.');
                    this.currentStep = 4; // Retour à l'étape hébergement
                } else {
                    alert('❌ Erreur sauvegarde\n\n' + error.message);
                }
            } finally {
                this.isSubmitting = false;
            }
        },

        // Action 3: Demande de conseil personnalisé
        async requestAdvice() {
            if (!this.canSubmit || this.isSubmitting) return;

            this.isSubmitting = 'advice';

            try {
                // Préparer les données pour l'email de demande de conseil
                const adviceData = {
                    type: 'advice_request',
                    email: this.contactInfo.email,
                    contact: this.contactInfo,
                    dates: this.selectedDates,
                    selected_products: this.getSelectedProducts(),
                    total_price: this.totalPrice,
                    message: this.contactInfo.message || 'Demande de conseil personnalisé'
                };

                // Appel API pour conseil personnalisé
                const response = await publicApi.requestAdvice(adviceData);

                if (response.success) {
                    this.showSuccessModal({
                        title: '👨‍💼 Demande de conseil envoyée !',
                        message: `Merci ${this.contactInfo.name}, votre demande de conseil a été transmise à nos experts.
                                 Nous vous contacterons dans les plus brefs délais pour vous proposer un séjour sur mesure.`,
                        showContacts: true
                    });
                } else {
                    throw new Error(response.message || 'Erreur lors de l\'envoi');
                }

            } catch (error) {
                console.error('❌ Erreur demande conseil:', error);
                alert('Erreur lors de l\'envoi de la demande: ' + error.message);
            } finally {
                this.isSubmitting = false;
            }
        },

        // Helpers
        generateQuoteReference() {
            const timestamp = Date.now().toString(36)
            const random = Math.random().toString(36).substr(2, 5)
            return `DEVIS-${timestamp}-${random}`.toUpperCase()
        },

        showContactsSection() {
            // Scroll vers la section footer avec les contacts
            setTimeout(() => {
                const contactSection = document.querySelector('.footer-section:last-child')
                if (contactSection) {
                    contactSection.scrollIntoView({ behavior: 'smooth', block: 'center' })
                    // Highlight temporaire
                    contactSection.style.border = '2px solid var(--terracotta)'
                    setTimeout(() => {
                        contactSection.style.border = 'none'
                    }, 3000)
                }
            }, 500)
        },

        showSuccessMessage(message) {
            // Simple alert pour l'instant (à remplacer par votre système de notifications)
            alert(message)
        },

        showErrorMessage(message) {
            alert(message)
        },

        async saveQuote() {
            console.log('💾 DEBUG - État des sélections:', {
                activities: this.selectedItems.activities,
                room: this.selectedItems.room,
                menus: this.selectedItems.menus,
                activitiesIds: this.selectedItems.activities.map(a => a.id),
                roomId: this.selectedItems.room?.id,
                menusIds: this.selectedItems.menus.map(m => m.id)
            });

            // ✅ Collecter TOUS les IDs des produits sélectionnés
            const productIds = [];

            // Ajouter les activités
            this.selectedItems.activities.forEach(activity => {
                if (activity.id) {
                    productIds.push(activity.id);
                }
            });

            // Ajouter l'hébergement (obligatoire)
            if (this.selectedItems.room && this.selectedItems.room.id) {
                productIds.push(this.selectedItems.room.id);
            }

            // Ajouter les menus
            this.selectedItems.menus.forEach(menu => {
                if (menu.id) {
                    productIds.push(menu.id);
                }
            });

            console.log('🎯 Product IDs collectés:', productIds);

            if (productIds.length === 0) {
                console.warn('⚠️ Aucun produit sélectionné !');
                throw new Error('Veuillez sélectionner au moins un hébergement.');
            }

            // ✅ Structure compatible avec ton API existante
            const quoteData = {
                email: this.contactInfo.email,
                contact: {
                    name: this.contactInfo.name,
                    last_name: this.contactInfo.last_name,
                    phone: this.contactInfo.phone,
                    message: this.contactInfo.message
                },
                product_ids: productIds, // ← Voilà le problème résolu !
                dates: {
                    checkin: this.selectedDates.start,
                    endExclusive: this.selectedDates.endExclusive,
                    guests: this.selectedDates.guests
                },
                total_price: this.totalPrice
            };

            console.log('📤 Données envoyées à l\'API:', quoteData);

            return await publicApi.saveQuote(quoteData);
        },

        getSelectedProductIds() {
            // Récupérer les IDs des produits sélectionnés
            const ids = [];

            // Activités
            this.selectedItems.activities.forEach(activity => {
                ids.push(activity.id);
            });

            // Hébergement
            if (this.selectedItems.room) {
                ids.push(this.selectedItems.room.id);
            }

            // Menus
            this.selectedItems.menus.forEach(menu => {
                ids.push(menu.id);
            });

            return ids;
        },

        getSelectedProducts() {
            // Version détaillée pour les emails
            return {
                activities: this.selectedItems.activities,
                room: this.selectedItems.room,
                menus: this.selectedItems.menus
            };
        },

        showSuccessModal({ title, message, showContacts = false }) {
            // Fermer la modal actuelle
            this.closeModal();

            // Afficher une notification ou modal de succès
            // À adapter selon votre système de notifications
            alert(`${title}\n\n${message}`);

            if (showContacts) {
                // Optionnel: afficher les coordonnées de contact
                console.log('📞 Contacts affichés');
            }
        }
    }
}
</script>

<style lang="scss" scoped>
/* Styles identiques à l'original - pas de changement */
$primary: #5e72e4;
$success: #2dce89;
$warning: #fb6340;
$danger: #f5365c;
$terracotta: #c17c4a;

/* ===== MODAL BASE ===== */
.modal-content {
    display: block !important;
    padding: 1rem 1.25rem;
}

.quote-modal .modal-body {
    max-height: 70vh;
    overflow: auto;
}

.quote-modal {
    max-width: 900px;
    width: 95%;
    background: #fff;
    border-radius: 12px;
    overflow: hidden;
    display: flex;
    flex-direction: column;
}

/* ===== ÉTAPES PROGRESS ===== */
.progress-steps {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
    gap: 1rem;
}

.progress-steps .step {
    flex: 1;
    text-align: center;
    position: relative;
    font-size: 0.9rem;
    color: #666;
}

.progress-steps .step-number {
    width: 28px;
    height: 28px;
    margin: 0 auto 0.25rem;
    border-radius: 50%;
    background: #e5e7eb;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
}

.progress-steps .step.active .step-number {
    background: #6366f1;
    color: #fff;
}

.progress-steps .step.completed .step-number {
    background: #10b981;
    color: #fff;
}

/* ===== CONTENU STEPS ===== */
.step-content {
    width: 100% !important;
    max-width: none !important;
    margin: 0 auto !important;
    border: 0 !important;
    box-shadow: none !important;
    background: transparent;
}

/* ===== GRILLE HARMONISÉE ===== */
.mini-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 12px;
}

@media (max-width: 640px) {
    .mini-grid {
        grid-template-columns: 1fr;
    }
}

/* ===== CARTES HARMONISÉES ===== */
.mini-card {
    position: relative;
    display: grid;
    grid-template-columns: 96px 1fr;
    gap: 10px;
    padding: 10px;
    border-radius: 12px;
    background: rgba(255, 255, 255, 0.9);
    border: 1px solid rgba(0, 0, 0, 0.1);
    cursor: pointer;
    transition: transform .12s ease, box-shadow .12s ease, border-color .12s ease;
}

.mini-card:hover {
    transform: translateY(-1px);
    box-shadow: 0 6px 18px rgba(0, 0, 0, .15);
}

.mini-card.selected {
    border-color: $terracotta;
    background: rgba(193, 124, 74, 0.05);
}

.mini-thumb {
    width: 96px;
    height: 96px;
    border-radius: 10px;
    object-fit: cover;
    display: block;
}

.step-help {
    background: rgba(94, 114, 228, 0.1);
    border-left: 4px solid #5e72e4;
    padding: 0.75rem 1rem;
    margin: 1rem 0;
    border-radius: 4px;
    font-size: 0.9rem;
    color: #5e72e4;

    i {
        margin-right: 0.5rem;
    }

    &.help-warning {
        background: rgba(251, 99, 64, 0.1);
        border-left-color: #fb6340;
        color: #fb6340;
    }
}

.mini-info {
    display: grid;
    gap: 6px;
    align-content: start;
}

.mini-title {
    margin: 0;
    font-size: .95rem;
    line-height: 1.2;
    color: #333;
    font-weight: 600;
}

.mini-meta {
    display: flex;
    gap: 8px;
    align-items: center;
    flex-wrap: wrap;
}

.mini-price {
    font-weight: 700;
    font-size: .9rem;
    color: $terracotta;
}

.mini-pill {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    font-size: .75rem;
    opacity: .8;
    padding: 2px 6px;
    border-radius: 999px;
    background: rgba(0, 0, 0, 0.05);
    border: 1px solid rgba(0, 0, 0, 0.1);
}

.mini-desc {
    margin: 0;
    font-size: .8rem;
    opacity: .8;
    color: #666;
    line-height: 1.3;
}

.mini-check {
    position: absolute;
    right: 8px;
    bottom: 8px;
    background: rgba(0, 0, 0, .6);
    color: white;
    border-radius: 50%;
    width: 24px;
    height: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
}

.mini-card.selected .mini-check {
    background: $terracotta;
}

/* ===== CALENDRIER ===== */
.dates-layout {
    display: grid;
    grid-template-columns: 1fr;
    gap: 1.25rem 1.5rem;
    align-items: start;
}

/* ===== CALENDRIER COMPACT ===== */
.calendar--compact :deep(.fc) {
    width: 100%;
    max-width: 520px;
    /* ajuste 480–600 si tu veux */
    margin: 0 auto 1rem;
    /* centre le bloc */
}

.calendar--compact :deep(.fc-daygrid-day-number) {
    padding: 4px 6px;
    font-size: 0.85rem;
}

.calendar--compact :deep(.fc-daygrid-day-frame) {
    min-height: 30px; // cellules moins hautes
}

/* Header + nav boutons */
.calendar-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: .5rem;
    margin-bottom: .5rem;
}

.calendar-nav {
    display: inline-flex;
    gap: .25rem;
}

.nav-btn {
    border: 1px solid #ddd;
    background: #fff;
    border-radius: 8px;

    display: inline-flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
}

.nav-btn:hover {
    background: #f8f8f8;
}

.nav-btn:disabled {
    opacity: .5;
    cursor: not-allowed;
}

/* accessibilité */
.sr-only {
    position: absolute;
    width: 1px;
    height: 1px;
    padding: 0;
    margin: -1px;
    overflow: hidden;
    clip: rect(0, 0, 0, 0);
    border: 0;
}

.range-info,
.duration {
    margin-top: .5rem;
    display: flex;
    align-items: center;
    gap: .5rem;
    color: $terracotta;
    font-weight: 500;
}

/* ===== SECTION RÉCAP FINALE ===== */
.quote-final-step {
    display: flex;
    flex-direction: column;
    gap: 1rem;
    margin-top: 1rem;
}

.actions-explanation {
    text-align: center;
    margin-bottom: 0.5rem;
    padding: 0.75rem;
    background: rgba(193, 124, 74, 0.1);
    border-radius: 8px;
    border-left: 4px solid $terracotta;

    .explanation-text {
        color: #666;
        font-size: 0.9rem;
        margin: 0;

        i {
            color: $terracotta;
            margin-right: 0.5rem;
        }
    }
}

.quote-actions {
    display: flex;
    gap: 0.75rem;
    flex-wrap: wrap;

    .btn {
        flex: 1;
        min-width: 140px;
        white-space: nowrap;
        font-size: 0.9rem;

        &.btn-success {
            background: $success;
            border-color: $success;

            &:hover {
                background: darken($success, 10%);
            }
        }

        &.btn-primary {
            background: $primary;
            border-color: $primary;

            &:hover {
                background: darken($primary, 10%);
            }
        }

        &.btn-outline {
            background: transparent;
            border: 2px solid $terracotta;
            color: $terracotta;

            &:hover {
                background: $terracotta;
                color: white;
            }
        }

        @media (max-width: 768px) {
            min-width: 120px;
            font-size: 0.85rem;
            padding: 0.5rem 0.75rem;
        }
    }

    @media (max-width: 640px) {
        flex-direction: column;

        .btn {
            flex: none;
            width: 100%;
            min-width: auto;
        }
    }
}

/* ===== LOADING STATE ===== */
.loading-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 1rem;
    padding: 2rem;
    color: #666;
}

.spinner {
    width: 24px;
    height: 24px;
    border: 2px solid #f3f3f3;
    border-top: 2px solid $terracotta;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% {
        transform: rotate(0deg);
    }

    100% {
        transform: rotate(360deg);
    }
}

/* ===== RÉCAP SUMMARY ===== */
.quote-summary {
    background: rgba(255, 255, 255, 0.95);
    border-radius: 12px;
    padding: 1.5rem;
    margin-bottom: 1.5rem;
    border: 1px solid rgba(0, 0, 0, 0.1);
}

.summary-section {
    margin-bottom: 1.5rem;

    &:last-child {
        margin-bottom: 0;
    }

    h4 {
        color: $terracotta;
        font-size: 1.1rem;
        margin-bottom: 0.75rem;
        font-weight: 600;

        i {
            margin-right: 0.5rem;
        }
    }
}

.summary-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.5rem 0;
    border-bottom: 1px solid rgba(0, 0, 0, 0.05);

    &:last-child {
        border-bottom: none;
    }

    span:first-child {
        color: #333;
        font-weight: 500;
    }

    span:last-child {
        color: $terracotta;
        font-weight: 600;
    }
}

.summary-total {
    border-top: 2px solid $terracotta;
    padding-top: 1rem;
    margin-top: 1rem;
}

.total-line {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 1.2rem;

    span {
        color: #333;
    }

    strong {
        color: $terracotta;
        font-size: 1.4rem;
    }
}

/* ===== FORMULAIRE CONTACT ===== */
.contact-form {
    background: rgba(255, 255, 255, 0.95);
    border-radius: 12px;
    padding: 1.5rem;
    border: 1px solid rgba(0, 0, 0, 0.1);

    h4 {
        color: $terracotta;
        margin-bottom: 1rem;
        font-weight: 600;
    }
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
    margin-bottom: 1rem;

    @media (max-width: 640px) {
        grid-template-columns: 1fr;
    }
}

.form-input,
.form-textarea {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid #ddd;
    border-radius: 8px;
    font-size: 0.9rem;
    margin-bottom: 1rem;

    &:focus {
        outline: none;
        border-color: $terracotta;
        box-shadow: 0 0 0 2px rgba(193, 124, 74, 0.1);
    }
}

.form-textarea {
    min-height: 100px;
    resize: vertical;
}
</style>