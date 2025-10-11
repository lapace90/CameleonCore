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
                    <div v-if="currentStep === 1" class="step-content step-dates">
                        <h3 class="step-title">Choisissez vos dates de séjour</h3>

                        <!-- Layout en 2 colonnes sur desktop -->
                        <div class="dates-layout">
                            <!-- Colonne gauche : Calendrier -->
                            <div class="calendar-section">
                                <FullCalendar ref="fc" :options="fcOptions" />
                            </div>

                            <!-- Colonne droite : Informations -->
                            <div class="booking-info">
                                <!-- Dates sélectionnées -->
                                <div class="selected-dates" v-if="datesOK">
                                    <div class="dates-display">
                                        <div class="date-item">
                                            <i class="fas fa-calendar-plus"></i>
                                            <div>
                                                <label>Arrivée</label>
                                                <span>{{ formatDate(selectedDates.start) }}</span>
                                            </div>
                                        </div>

                                        <div class="date-separator">
                                            <i class="fas fa-arrow-right"></i>
                                        </div>

                                        <div class="date-item">
                                            <i class="fas fa-calendar-minus"></i>
                                            <div>
                                                <label>Départ</label>
                                                <span>{{ formatDate(displayEndInclusive(selectedDates.endExclusive))
                                                }}</span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="nights-display">
                                        <i class="fas fa-moon"></i>
                                        <span>{{ nights }} nuit{{ nights > 1 ? 's' : '' }}</span>
                                    </div>
                                </div>

                                <!-- Sélecteur d'invités amélioré -->
                                <div class="guests-selector">
                                    <label class="guests-label">
                                        <i class="fas fa-users"></i>
                                        Nombre d'invités
                                    </label>

                                    <div class="guests-controls">
                                        <button type="button" @click="decreaseGuests"
                                            :disabled="selectedDates.guests <= 1" class="btn-guest-control">
                                            <i class="fas fa-minus"></i>
                                        </button>

                                        <div class="guests-display">
                                            <span class="guests-number">{{ selectedDates.guests }}</span>
                                            <span class="guests-text">personne{{ selectedDates.guests > 1 ? 's' : ''
                                            }}</span>
                                        </div>

                                        <button type="button" @click="increaseGuests"
                                            :disabled="selectedDates.guests >= 20" class="btn-guest-control">
                                            <i class="fas fa-plus"></i>
                                        </button>
                                    </div>

                                    <small class="guests-hint">Maximum 20 personnes</small>
                                </div>

                                <!-- Instructions -->
                                <div class="step-instructions">
                                    <div class="instruction-item">
                                        <i class="fas fa-click"></i>
                                        <span>Sélectionnez vos dates sur le calendrier</span>
                                    </div>
                                    <div class="instruction-item">
                                        <i class="fas fa-info-circle"></i>
                                        <span>Minimum 1 nuit - Réservation à partir de demain</span>
                                    </div>
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
                            <!-- ACTIVITÉS avec contrôles améliorés -->
                            <div v-if="pricing.lines.some(l => l.type === 'activity')" class="summary-section">
                                <h4><i class="fas fa-hiking"></i> Activités</h4>
                                <div v-for="l in pricing.lines.filter(l => l.type === 'activity')" :key="'a-' + l.id"
                                    class="summary-item enhanced">
                                    <div class="item-info">
                                        <span class="item-name">{{ l.name }}</span>
                                        <span class="item-unit-price">{{ l.unit }}€/unité</span>
                                    </div>

                                    <div class="item-controls">
                                        <label class="qty-label">Quantité:</label>
                                        <select class="qty-select" :value="getQty('activity', l.id, l.qty)"
                                            @change="setQty('activity', l.id, $event.target.value)">
                                            <option value="0">Supprimer</option>
                                            <option v-for="n in getMaxQuantity()" :key="n" :value="n">{{ n }}
                                            </option>
                                        </select>
                                    </div>

                                    <div class="item-total">
                                        <strong>{{ formatPrice(l.unit * getQty('activity', l.id, l.qty)) }}</strong>
                                    </div>
                                </div>
                            </div>

                            <!-- MENUS avec contrôles améliorés -->
                            <div v-if="pricing.lines.some(l => l.type === 'menu')" class="summary-section">
                                <h4><i class="fas fa-utensils"></i> Menus</h4>
                                <div v-for="l in pricing.lines.filter(l => l.type === 'menu')" :key="'m-' + l.id"
                                    class="summary-item enhanced">
                                    <div class="item-info">
                                        <span class="item-name">{{ l.name }}</span>
                                        <span class="item-unit-price">{{ l.unit }}€/unité</span>
                                    </div>

                                    <div class="item-controls">
                                        <label class="qty-label">Quantité:</label>
                                        <select class="qty-select" :value="getQty('menu', l.id, l.qty)"
                                            @change="setQty('menu', l.id, $event.target.value)">
                                            <option value="0">Supprimer</option>
                                            <option v-for="n in getMaxQuantity()" :key="n" :value="n">{{ n }}
                                            </option>
                                        </select>
                                    </div>

                                    <div class="item-total">
                                        <strong>{{ formatPrice(l.unit * getQty('menu', l.id, l.qty)) }}</strong>
                                    </div>
                                </div>
                            </div>

                            <!-- HÉBERGEMENT (pas de contrôle quantité) -->
                            <div v-if="selectedItems.room" class="summary-section">
                                <h4><i class="fas fa-bed"></i> Hébergement</h4>
                                <div v-for="l in pricing.lines.filter(x => x.type === 'room')" :key="l.id"
                                    class="summary-item enhanced">
                                    <div class="item-info">
                                        <span class="item-name">{{ l.name }} ({{ pricing.nights }} nuit{{ pricing.nights
                                            > 1 ? 's' : '' }})</span>
                                        <span class="item-unit-price">{{ l.unit }}€/nuit</span>
                                    </div>

                                    <div class="item-controls">
                                        <span class="qty-fixed">× {{ l.qty }}</span>
                                    </div>

                                    <div class="item-total">
                                        <strong>{{ formatPrice(l.lineTotal) }}</strong>
                                    </div>
                                </div>
                            </div>

                            <!-- DÉTAILS DU SÉJOUR -->
                            <div class="summary-section">
                                <h4><i class="fas fa-calendar"></i> Détails du séjour</h4>
                                <div class="summary-item">
                                    <span>Du {{ formatDate(selectedDates.start) }} au {{
                                        formatDate(displayEndInclusive(selectedDates.endExclusive)) }}</span>
                                </div>
                                <div class="summary-item">
                                    <span>{{ selectedDates.guests }} personne{{ selectedDates.guests > 1 ? 's' : ''
                                    }}</span>
                                </div>
                            </div>

                            <!-- TOTAL -->
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
    <!-- Modal de validation email -->
    <EmailValidationModal :show="showEmailValidation" :quote-reference="validationQuote.reference"
        :email="validationQuote.email" @close="showEmailValidation = false" />
</template>

<script>
import { nextTick } from 'vue'
import { publicApi } from '@/services/PublicApi'
import FullCalendar from '@fullcalendar/vue3'
import dayGridPlugin from '@fullcalendar/daygrid'
import interactionPlugin from '@fullcalendar/interaction'
import frLocale from '@fullcalendar/core/locales/fr'
import { computeQuoteTotal } from '@/shared/composables/useQuotePricing'
import EmailValidationModal from '@/public/components/EmailValidationModal.vue'

export default {
    name: 'QuoteModal',
    components: { FullCalendar, EmailValidationModal },
    emits: ['close', 'quote-saved'],

    props: { show: { type: Boolean, default: false } },

    data() {
        return {
            currentStep: 1,
            loading: false,
            isSubmitting: false,

            // Pour modal validation email
            showEmailValidation: false,
            validationQuote: { reference: '', email: '' },

            // Quantités ajustées au récap
            qtyOverrides: { activity: {}, menu: {} },

            // Catalogues (on charge tout d’un coup)
            allProducts: [],

            // Sélections
            selectedItems: { activities: [], room: null, menus: [] },
            selectedDates: { start: '', endExclusive: '', guests: 2 },
            contactInfo: { name: '', last_name: '', email: '', phone: '', message: '' },
        }
    },

    computed: {
        // --- Filtres produits ---
        relevantProducts() {
            return this.allProducts.filter(p => {
                const label = p.typeConfig?.label
                return ['Activités', 'Menus', 'Hébergements', 'Rooms'].includes(label)
            })
        },
        availableActivities() {
            return this.relevantProducts.filter(p => p.typeConfig?.label === 'Activités')
        },
        availableMenus() {
            return this.relevantProducts.filter(p => p.typeConfig?.label === 'Menus')
        },
        availableRooms() {
            return this.relevantProducts
                .filter(p => p.typeConfig?.label === 'Hébergements' || p.typeConfig?.label === 'Rooms')
                .filter(room => {
                    const capacity = room.productable_data?.capacity || room.productableData?.capacity
                    return !capacity || capacity >= this.selectedDates.guests
                })
        },

        calendarApi() { return this.$refs.fc?.getApi?.() },

        stepHelpText() {
            switch (this.currentStep) {
                case 1:
                    return this.datesOK && this.selectedDates.guests >= 1
                        ? 'Parfait ! Continuez vers les activités.'
                        : 'Sélectionnez vos dates et le nombre de personnes pour continuer.'
                case 2: return 'Les activités sont optionnelles, vous pouvez passer à l’étape suivante.'
                case 3: return 'Les menus sont optionnels, vous pouvez passer à l’étape suivante.'
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

        // --- Pricing centralisé ---
        pricing() {
            const selected = {
                activity: this.selectedItems.activities.map(a => a.id),
                menu: this.selectedItems.menus.map(m => m.id),
                room: this.selectedItems.room?.id ? [this.selectedItems.room.id] : []
            }
            const catalog = {
                activities: this.availableActivities,
                menus: this.availableMenus,
                rooms: this.availableRooms
            }
            const dates = {
                checkin: this.selectedDates.start,
                // FullCalendar fournit end EXCLUSIF → on convertit en checkout inclusif
                checkout: this.displayEndInclusive(this.selectedDates.endExclusive),
                guests: this.selectedDates.guests
            }
            return computeQuoteTotal({
                selected, catalog, dates,
                rules: {
                    roomPerNight: true,
                    // activités & menus par personne (pas par nuit)
                    activityPerGuest: true,
                    activityPerGuestPerNight: false,
                    menuPerGuest: true,
                    menuPerGuestPerNight: false,
                    capQtyToGuests: true
                },
                overrides: this.qtyOverrides
            })
        },

        totalPrice() { return this.pricing.total },
        nights() { return this.pricing.nights },

        datesOK() {
            // nuit(s) > 0 suffit puisque pricing.nights est la source de vérité
            return this.nights > 0
        },

        canProceed() {
            switch (this.currentStep) {
                case 1: return this.datesOK && this.selectedDates.guests >= 1
                case 2: return true
                case 3: return true
                case 4: return !!this.selectedItems.room
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
                height: 380,
                contentHeight: 280,
                aspectRatio: 1.6,
                handleWindowResize: true,
                dayHeaderFormat: { weekday: 'short' },
                titleFormat: { year: 'numeric', month: 'short' },

                // Configuration de sélection améliorée
                selectable: true,
                selectMirror: true,
                selectOverlap: true,           // Permet la sélection sans conflit
                unselectAuto: false,           // Garde la sélection visible
                unselectCancel: '.booking-info', // Ne pas désélectionner si on clique sur la zone d'infos

                // Amélioration pour mobile/tactile
                selectLongPressDelay: 50,     // Très court
                selectMinDistance: 0,         // Pas de distance minimale
                longPressDelay: 50,          // Ajout global

                fixedWeekCount: false,
                validRange: { start: this.minDate },
                headerToolbar: {
                    left: 'prev,next',
                    center: 'title',
                    right: 'today'
                },
                select: this.fcOnSelect,

                eventDisplay: 'block',
                dayMaxEvents: 2
            }
        }
    },

    // 4️⃣ Dans QuoteModal.vue, ajoutez ce watcher dans la section watch: {}

    watch: {
        show(val) {
            if (val) this.initializeModal()
        },

        // clamp des overrides si le nb d'invités baisse
        'selectedDates.guests'(g) {
            const max = Math.max(1, Number(g || 1))
            for (const t of ['activity', 'menu']) {
                for (const id in this.qtyOverrides[t]) {
                    const v = Number(this.qtyOverrides[t][id] || 0)
                    if (v > max) this.qtyOverrides[t][id] = max
                }
            }
        }
    },

    methods: {
        // --- Init & chargement ---
        async initializeModal() {
            this.currentStep = 1
            this.resetSelections()
            await this.loadAllProducts()
            await nextTick()
        },

        async loadAllProducts() {
            this.loading = true
            try {
                const response = await publicApi.getProducts({ status: 'active', per_page: 100 })
                this.allProducts = response?.data || []
                console.log('✅ Produits chargés:', {
                    total: this.allProducts.length,
                    activities: this.availableActivities.length,
                    menus: this.availableMenus.length,
                    rooms: this.availableRooms.length,
                    sample_product: (response?.data || [])[0]
                })
            } catch (error) {
                console.error('❌ Erreur chargement produits:', error)
                this.allProducts = []
            } finally {
                this.loading = false
            }
        },

        // --- Sélections ---
        toggleActivity(activity) {
            const i = this.selectedItems.activities.findIndex(a => a.id === activity.id)
            if (i > -1) this.selectedItems.activities.splice(i, 1)
            else this.selectedItems.activities.push(activity)
            // reset override par défaut = autoset (composable)
            delete this.qtyOverrides.activity[activity.id]
        },

        toggleMenu(menu) {
            const i = this.selectedItems.menus.findIndex(m => m.id === menu.id)
            if (i > -1) this.selectedItems.menus.splice(i, 1)
            else this.selectedItems.menus.push(menu)
            delete this.qtyOverrides.menu[menu.id]
        },

        selectRoom(room) {
            this.selectedItems.room = room
        },
        getLineMaxQty() {
            const guests = Math.max(1, this.selectedDates.guests || 1)
            const nights = Math.max(1, this.nights || 1)
            return guests * nights  // guests × nights au lieu de juste guests
        },
        getQty(type, id, fallback) {
            const m = this.qtyOverrides?.[type] || {}
            const v = m[id]
            return Number.isFinite(Number(v)) ? Number(v) : (fallback ?? 1)
        },
        setQty(type, id, value) {
            const v = Math.max(0, Math.floor(Number(value || 0)))
            if (!this.qtyOverrides[type]) this.qtyOverrides[type] = {}
            this.qtyOverrides[type][id] = v
        },

        // --- Navigation ---
        increaseGuests() { if (this.selectedDates.guests < 20) this.selectedDates.guests++ },
        decreaseGuests() { if (this.selectedDates.guests > 1) this.selectedDates.guests-- },
        nextStep() { if (this.canProceed && this.currentStep < 5) this.currentStep++ },
        previousStep() { if (this.currentStep > 1) this.currentStep-- },
        closeModal() { this.$emit('close') },

        resetSelections() {
            this.selectedItems = { activities: [], room: null, menus: [] }
            this.selectedDates = { start: '', endExclusive: '', guests: 2 }
            this.contactInfo = { name: '', last_name: '', email: '', phone: '', message: '' }
            this.qtyOverrides = { activity: {}, menu: {} }
        },

        // --- Dates utils ---
        toUTCDateParts(yyyyMmDd) {
            const [y, m, d] = (yyyyMmDd || '').split('-').map(Number)
            return isNaN(y) ? null : Date.UTC(y, (m || 1) - 1, d || 1)
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

        // --- Format utils ---
        getMaxQuantity() {
            const guests = Math.max(1, this.selectedDates.guests || 1)
            const nights = Math.max(1, this.pricing.nights || 1)
            return guests * nights
        },

        formatPrice(amount) {
            return new Intl.NumberFormat('fr-FR', {
                style: 'currency',
                currency: 'EUR'
            }).format(amount || 0)
        },

        formatDate(s) {
            return s ? new Date(s).toLocaleDateString('fr-FR', { day: 'numeric', month: 'long', year: 'numeric' }) : ''
        },

        // --- FullCalendar ---
        fcOnSelect(info) {
            // start inclusif / end exclusif
            this.selectedDates.start = info.startStr
            this.selectedDates.endExclusive = info.endStr
        },

        // --- Actions finales ---
        async createReservationAndPay() {
            if (!this.canSubmit || this.isSubmitting) return
            this.isSubmitting = 'booking'
            try {
                if (!this.selectedItems.room) {
                    throw new Error('Veuillez sélectionner un hébergement avant de procéder au paiement.')
                }

                // ✅ UTILISER LA MÊME LOGIQUE QUE saveQuote()
                const quoteResponse = await this.saveQuote()

                if (!quoteResponse.success) {
                    throw new Error(quoteResponse.message || 'Impossible de créer le devis')
                }

                const quote = quoteResponse.quote_request

                // ✅ VÉRIFIER next_step
                if (quoteResponse.next_step === 'validation_email') {
                    this.showEmailValidationRequired(quote)
                    return
                }

                // Si next_step === 'payment', créer session Stripe
                const paymentResponse = await this.createStripeSession(quote.id)
                if (!paymentResponse.success) {
                    throw new Error(paymentResponse.error || 'Impossible de créer la session de paiement')
                }

                window.location.href = paymentResponse.checkout_url

            } catch (e) {
                console.error('❌ Erreur paiement:', e)
                alert('❌ ' + (e.message || 'Erreur inconnue'))
            } finally {
                this.isSubmitting = false
            }
        },

        showEmailValidationRequired(quote) {
            console.log('🔍 Quote reçu:', quote)

            this.closeModal()

            // ✅ L'API retourne { success: true, quote_request: {...} }
            const quoteData = quote.quote_request || quote

            // ✅ Extraire la référence et l'email
            const reference = quoteData.quote_reference
                || quoteData.quoteReference
                || quoteData.emailData?.reference
                || quoteData.reference
                || 'REF-INCONNU'

            const email = this.contactInfo.email ||
                quoteData.email ||
                quoteData.customer?.email ||
                'votre email'

            console.log('✅ Données extraites:', { reference, email })

            this.validationQuote = {
                reference: reference,
                email: email
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
            if (!response.ok) throw new Error(data.error || 'Erreur lors de la création de la session de paiement')
            return data
        },

        async saveQuoteAndShowContacts() {
            if (!this.canSubmit || this.isSubmitting) return
            this.isSubmitting = 'saving'
            try {
                if (!this.selectedItems.room) throw new Error('Veuillez sélectionner un hébergement avant de sauvegarder le devis.')
                const result = await this.saveQuote()
                if (!result.success) throw new Error(result.message || 'Erreur lors de la sauvegarde')
                this.showSuccessModal({
                    title: '📧 Email de validation envoyé !',
                    message: `Votre devis ${result.quote_request.quote_reference} a été créé.
Vérifiez votre boîte email et validez-le (lien valable 48h).`,
                    showContacts: true
                })
                this.$emit('quote-saved', { quote: result.quote_request, type: 'email_validation_required' })
            } catch (e) {
                console.error('❌ Erreur sauvegarde:', e)
                alert('❌ ' + (e.message || 'Erreur inconnue'))
            } finally {
                this.isSubmitting = false
            }
        },

        async requestAdvice() {
            if (!this.canSubmit || this.isSubmitting) return
            this.isSubmitting = 'advice'
            try {
                const adviceData = {
                    type: 'advice_request',
                    email: this.contactInfo.email,
                    contact: this.contactInfo,
                    dates: this.selectedDates,
                    selected_products: this.getSelectedProducts(),
                    total_price: this.totalPrice,
                    message: this.contactInfo.message || 'Demande de conseil personnalisé'
                }
                const response = await publicApi.requestAdvice(adviceData)
                if (!response.success) throw new Error(response.message || 'Erreur lors de l’envoi')
                this.showSuccessModal({
                    title: '👨‍💼 Demande de conseil envoyée !',
                    message: `Merci ${this.contactInfo.name}, un expert vous recontacte très vite.`,
                    showContacts: true
                })
            } catch (e) {
                console.error('❌ Erreur conseil:', e)
                alert('❌ ' + (e.message || 'Erreur inconnue'))
            } finally {
                this.isSubmitting = false
            }
        },

        // --- Sauvegarde (aligne les quantités du récap) ---
        async saveQuote() {
            const items = (this.pricing?.lines || [])
                .map(l => ({ product_id: l.id, quantity: Math.max(0, Math.floor(Number(l.qty || 0))) }))
                .filter(it => it.quantity > 0)

            if (!this.selectedItems.room) throw new Error('Veuillez sélectionner un hébergement.')
            const product_ids = []
            for (const it of items) for (let i = 0; i < it.quantity; i++) product_ids.push(it.product_id)
            if (!product_ids.length) throw new Error('Aucun produit sélectionné.')

            const quoteData = {
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
                total_price: this.totalPrice,
                product_ids,
                // items,                  // décommente si le backend accepte
                // include_line_items: true
            }

            console.log('📤 Données envoyées à l’API:', { ...quoteData, product_ids_count: product_ids.length })
            return await publicApi.saveQuote(quoteData)
        },

        // --- Divers / legacy helpers ---
        getSelectedProductIds() {
            const ids = []
            this.selectedItems.activities.forEach(a => ids.push(a.id))
            if (this.selectedItems.room) ids.push(this.selectedItems.room.id)
            this.selectedItems.menus.forEach(m => ids.push(m.id))
            return ids
        },
        getSelectedProducts() {
            return { activities: this.selectedItems.activities, room: this.selectedItems.room, menus: this.selectedItems.menus }
        },
        showSuccessModal({ title, message }) {
            this.closeModal()
            alert(`${title}\n\n${message}`)
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

.mini-card.selected .mini-check {
    background: $terracotta;
}

/* Affichage des dates sélectionnées */
.date-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    flex: 1;

    i {
        color: $terracotta;
        font-size: 1.1rem;
    }

    div {
        display: flex;
        flex-direction: column;

        label {
            font-size: 0.75rem;
            color: #666;
            margin: 0;
        }

        span {
            font-weight: 600;
            color: #333;
            font-size: 0.9rem;
        }
    }
}

.nights-display {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    justify-content: center;
    background: $terracotta;
    color: white;
    padding: 0.5rem;
    border-radius: 6px;
    font-weight: 600;
    font-size: 0.9rem;

    i {
        font-size: 0.8rem;
    }
}

/* Sélecteur d'invités amélioré */
.guests-selector {
    background: white;
    border: 1px solid #ddd;
    border-radius: 12px;
    padding: 1rem;
}

.guests-label {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-weight: 600;
    color: #333;
    margin-bottom: 1rem;
    font-size: 0.9rem;

    i {
        color: $terracotta;
    }
}

.guests-controls {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 1rem;
    margin-bottom: 0.5rem;
}

.btn-guest-control {
    width: 36px;
    height: 36px;
    border: 2px solid $terracotta;
    background: white;
    color: $terracotta;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.3s ease;

    &:hover:not(:disabled) {
        background: $terracotta;
        color: white;
    }

    &:disabled {
        opacity: 0.4;
        cursor: not-allowed;
    }
}

.guests-display {
    display: flex;
    flex-direction: column;
    align-items: center;
    min-width: 80px;
}

.guests-number {
    font-size: 1.5rem;
    font-weight: bold;
    color: $terracotta;
    line-height: 1;
}

.guests-text {
    font-size: 0.8rem;
    color: #666;
}

.guests-hint {
    text-align: center;
    color: #666;
    font-size: 0.75rem;
}

/* Instructions */
.step-instructions {
    background: #e7f3ff;
    border-radius: 8px;
    padding: 1rem;
    border-left: 4px solid #0066cc;
}

.instruction-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 0.5rem;
    font-size: 0.85rem;

    &:last-child {
        margin-bottom: 0;
    }

    i {
        color: #0066cc;
        width: 16px;
        text-align: center;
    }
}

/* Responsive mobile */
@media (max-width: 768px) {
    .dates-display {
        flex-direction: column;
        gap: 0.75rem;
    }

    .date-separator {
        transform: rotate(90deg);
    }

    .calendar-section .fc {
        font-size: 0.8rem;
    }
}

/* Dates pendant la sélection (avec selectMirror) */
:deep(.fc-highlight) {
    background-color: rgba(193, 124, 74, 0.2) !important;
    /* Utilise la couleur terracotta avec transparence */
}

/* Dates sélectionnées après validation */
:deep(.fc-day.fc-day-selected) {
    background-color: rgba(193, 124, 74, 0.15) !important;
    position: relative;
}

/* Bordure pour les dates sélectionnées */
:deep(.fc-day.fc-day-selected::after) {
    content: '';
    position: absolute;
    inset: 2px;
    border: 2px solid #c17c4a;
    border-radius: 4px;
    pointer-events: none;
}

/* Numéro du jour pour les dates sélectionnées */
:deep(.fc-day-selected .fc-daygrid-day-number) {
    color: #c17c4a;
    font-weight: 700;
    background-color: rgba(193, 124, 74, 0.1);
    padding: 4px 8px;
    border-radius: 4px;
}

/* Amélioration du feedback tactile sur mobile */
:deep(.fc-day:active) {
    background-color: rgba(193, 124, 74, 0.1) !important;
}

/* Jour aujourd'hui avec sélection */
:deep(.fc-day-today.fc-day-selected) {
    background-color: rgba(193, 124, 74, 0.25) !important;
}

/* Animation au survol (desktop uniquement) */
@media (hover: hover) {
    :deep(.fc-day:hover) {
        background-color: rgba(193, 124, 74, 0.08);
        cursor: pointer;
    }
}

/* ===== AMÉLIORATION DE LA ZONE DE SÉLECTION ===== */

/* Empêcher la désélection quand on clique sur certains éléments */
.calendar-section {
    -webkit-user-select: none;
    user-select: none;
}

/* Améliorer la zone cliquable sur mobile */
:deep(.fc-daygrid-day-frame) {
    min-height: 40px;
    /* Plus facile à toucher sur mobile */
}

/* Touch feedback pour iOS */
:deep(.fc-day) {
    -webkit-tap-highlight-color: rgba(193, 124, 74, 0.2);
}

/* ===== RÉCAP SUMMARY ===== */
.step-title {
    color: $terracotta;
    font-size: 1.3rem;
    font-weight: 600;
    margin-bottom: 1.5rem;
    text-align: center;
}

// Et pour l'étape 1 spécifiquement :
.step-dates .step-title {
    margin-bottom: 1rem;
}

/* Responsive pour mobile */
@media (max-width: 640px) {
    .summary-item.enhanced {
        grid-template-columns: 1fr;
        gap: 10px;
        text-align: left;
    }

    .item-controls {
        justify-content: flex-start;
    }

    .item-total {
        text-align: left;
    }

    .fc-toolbar-chunk>h2 {
        font-size: 1rem;
        margin: 0;
    }

    ::v-deep h2.fc-toolbar-title {
        font-size: 1rem !important;
    }
}

/* Animation hover */
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

.muted {
    color: #777;
    margin-left: .25rem;
}

// ce qui doit rester
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
</style>