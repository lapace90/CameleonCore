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

                        <div class="dates-layout">
                            <!-- Calendrier simplifié -->
                            <div class="calendar-container">
                                <div class="calendar-header">
                                    <h4 class="calendar-title">
                                        <i class="fas fa-calendar-alt"></i> Sélectionnez vos dates
                                    </h4>
                                </div>

                                <FullCalendar class="calendar" :options="fcOptions" ref="fc" />

                                <div class="range-info" v-if="selectedDates.start && selectedDates.end">
                                    <i class="fas fa-calendar-check"></i>
                                    Séjour : {{ formatDate(selectedDates.start) }} → {{
                                        formatDate(displayEndInclusive(selectedDates.start, selectedDates.endExclusive)) }}
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

                                <div v-if="durationInDays > 0" class="duration">
                                    <i class="fas fa-moon"></i>
                                    {{ (durationInDays - 1) }} nuit{{ (durationInDays - 1) > 1 ? 's' : '' }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- STEP 2: Activités -->
                    <div v-if="currentStep === 2" class="step-content">
                        <h3 class="step-title">Choisissez vos activités</h3>
                        <p class="step-description">Sélectionnez les expériences qui vous tentent.</p>

                        <div v-if="loadingActivities" class="loading-state">
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
                                        <span v-if="activity.productableData?.duration" class="mini-pill">
                                            <i class="fas fa-clock"></i>
                                            {{ activity.productableData.duration }} min
                                        </span>
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

                        <div v-if="loadingMenus" class="loading-state">
                            <div class="spinner"></div>
                            <p>Chargement des menus...</p>
                        </div>

                        <div v-else class="products-grid">
                            <div v-for="menu in availableMenus" :key="menu.id" class="product-card"
                                :class="{ selected: selectedItems.menus.some(m => m.id === menu.id) }"
                                @click="toggleMenu(menu)">
                                <div class="product-image">
                                    <img :src="menu.image" :alt="menu.name" />
                                    <div class="product-overlay">
                                        <i class="fas"
                                            :class="selectedItems.menus.some(m => m.id === menu.id) ? 'fa-check' : 'fa-plus'"></i>
                                    </div>
                                </div>
                                <div class="product-info">
                                    <h4>{{ menu.name }}</h4>
                                    <p class="price">{{ menu.formatted_price }}</p>
                                    <p class="description">{{ menu.short_description }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- STEP 4: Hébergement -->
                    <div v-if="currentStep === 4" class="step-content">
                        <h3 class="step-title">Choisissez votre hébergement</h3>
                        <p class="step-description">Sélectionnez votre logement.</p>

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
                                        <i class="fas"
                                            :class="selectedItems.room?.id === room.id ? 'fa-check' : 'fa-plus'"></i>
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
                                    <span>{{ selectedItems.room.name }} ({{ durationInDays }} nuit{{ durationInDays > 1
                                        ? 's' : '' }})</span>
                                    <span>{{ formatPrice(selectedItems.room.price * durationInDays) }}</span>
                                </div>
                            </div>

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
                                <input type="text" v-model="contactInfo.name" placeholder="Nom complet"
                                    class="form-input" />
                                <input type="email" v-model="contactInfo.email" placeholder="Email"
                                    class="form-input" />
                            </div>
                            <input type="tel" v-model="contactInfo.phone" placeholder="Téléphone" class="form-input" />
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
                        <button v-else @click="submitQuote" class="btn btn-success btn-sm"
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
            loadingActivities: false,
            loadingRooms: false,
            loadingMenus: false,
            isSubmitting: false,

            availableActivities: [],
            availableRooms: [],
            availableMenus: [],

            selectedItems: { activities: [], room: null, menus: [] },
            selectedDates: { start: '', end: '', guests: 2 },
            contactInfo: { name: '', email: '', phone: '', message: '' },
        }
    },

    computed: {
        calendarApi() { return this.$refs.fc?.getApi?.() },

        minDate() {
            const d = new Date()
            d.setDate(d.getDate() + 1)
            return d.toISOString().split('T')[0]
        },

        durationInDays() {
            const { start, endExclusive } = this.selectedDates
            return this.daysBetween(start, endExclusive)
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
                case 4: return true // hébergement optionnel  
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
                height: 'auto',
                fixedWeekCount: false,
                headerToolbar: false,
                selectable: true,
                selectMirror: true,
                validRange: { start: this.minDate },
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
            await Promise.all([this.loadActivities(), this.loadRooms(), this.loadMenus()])
            await nextTick()
        },

        async loadActivities() {
            this.loadingActivities = true
            console.log('🔍 Début loadActivities')
            try {
                const res = await publicApi.getActivities({ per_page: 20, status: 'active' })
                console.log('🔍 Réponse activités complète:', res)
                console.log('🔍 res.data:', res?.data)
                console.log('🔍 Type de res.data:', typeof res?.data)

                this.availableActivities = res?.data || []
                console.log('🔍 Nombre d\'activités chargées:', this.availableActivities.length)
            } catch (e) {
                console.error('❌ Erreur chargement activités:', e)
                console.error('❌ Détails erreur:', e.response?.data)
                this.availableActivities = []
            } finally {
                this.loadingActivities = false
            }
        },

        async loadMenus() {
            this.loadingMenus = true
            console.log('🔍 Début loadMenus')
            try {
                const res = await publicApi.getMenus({ per_page: 20, status: 'active' })
                console.log('🔍 Réponse menus:', res)
                this.availableMenus = res?.data || []
                console.log('🔍 Nombre de menus chargés:', this.availableMenus.length)
            } catch (e) {
                console.error('❌ Erreur chargement menus:', e)
                this.availableMenus = []
            } finally {
                this.loadingMenus = false
            }
        },

        async loadRooms() {
            this.loadingRooms = true
            try {
                const res = await publicApi.getRooms({ per_page: 20, status: 'active' })
                this.availableRooms = res?.data || []
            } catch (e) {
                console.error('Erreur chargement hébergements:', e)
                this.availableRooms = []
            } finally {
                this.loadingRooms = false
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

        async submitQuote() {
            if (!this.canSubmit) return
            this.isSubmitting = true
            try {
                const payload = {
                    activities: this.selectedItems.activities.map(a => a.id),
                    menus: this.selectedItems.menus.map(m => m.id),
                    room: this.selectedItems.room?.id || null,
                    dates: { ...this.selectedDates },
                    contact: { ...this.contactInfo },
                    total_price: this.totalPrice
                }
                await publicApi.createQuoteRequest(payload)
                this.$emit('quote-submitted', payload)
                this.closeModal()
                alert('Votre demande de devis a été envoyée avec succès !')
            } catch (e) {
                console.error('Erreur envoi devis:', e)
                alert('Une erreur est survenue. Veuillez réessayer.')
            } finally {
                this.isSubmitting = false
            }
        },

        closeModal() { this.$emit('close') },

        resetSelections() {
            this.selectedItems = { activities: [], room: null, menus: [] }
            this.selectedDates = { start: '', end: '', guests: 2 }
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
        displayEndInclusive(startYmd, endExclusiveYmd) {
            const e = this.toUTCDateParts(endExclusiveYmd)
            if (e == null) return ''
            // endExclusive - 1 jour (affichage)
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
        }
    }
}
</script>

<style lang="scss" scoped>
$primary: #5e72e4;
$success: #2dce89;
$warning: #fb6340;
$danger: #f5365c;
$terracotta: #c17c4a;

.modal-content {
    display: block !important;
    padding: 1rem 1.25rem;
}

/* Corps scrollable pour ne pas étirer la modale */
.quote-modal .modal-body { max-height: 70vh; overflow: auto; }

/* Grille compacte : 2 colonnes desktop, 1 mobile */
.mini-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 12px;
}
@media (max-width: 640px) {
  .mini-grid { grid-template-columns: 1fr; }
}

/* Carte compacte */
.mini-card {
  position: relative;
  display: grid;
  grid-template-columns: 96px 1fr;
  gap: 10px;
  padding: 10px;
  border-radius: 12px;
  background: var(--bg-glass-mid, rgba(255,255,255,0.06));
  border: 1px solid var(--glass-border, rgba(255,255,255,0.12));
  cursor: pointer;
  transition: transform .12s ease, box-shadow .12s ease, border-color .12s ease;
}
.mini-card:hover { transform: translateY(-1px); box-shadow: 0 6px 18px rgba(0,0,0,.2); }
.mini-card.selected { outline: 2px solid var(--neon-blue, #38bdf8); }

/* Vignette */
.mini-thumb {
  width: 96px; height: 96px; border-radius: 10px; object-fit: cover; display: block;
}

/* Infos */
.mini-info { display: grid; gap: 6px; align-content: start; }
.mini-title { margin: 0; font-size: .95rem; line-height: 1.2; }
.mini-meta { display: flex; gap: 8px; align-items: center; }
.mini-price { font-weight: 700; font-size: .9rem; }
.mini-pill {
  display: inline-flex; align-items: center; gap: 6px;
  font-size: .75rem; opacity: .85; padding: 2px 8px; border-radius: 999px;
  border: 1px solid var(--glass-border, rgba(255,255,255,.15));
}
.mini-desc { margin: 0; font-size: .8rem; opacity: .9; }

/* Bouton +/✓ */
.mini-check {
  position: absolute; right: 8px; bottom: 8px;
  background: rgba(0,0,0,.45); border-radius: 10px; padding: 6px 8px;
}


.step-content {
    width: 100% !important;
    max-width: none !important;
    margin: 0 auto !important;
    border: 0 !important;
    box-shadow: none !important;
    background: transparent;
}

.dates-layout {
    display: grid;
    grid-template-columns: 1fr;
    gap: 1.25rem 1.5rem;
    align-items: start;
}

.calendar {
    width: 100%;
    max-width: 850px;
    margin: 0 auto 1rem;
    border-radius: 12px;
    overflow: hidden;
    background: var(--bg-glass, rgba(255, 255, 255, 0.6));
    backdrop-filter: blur(8px);
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

.calendar-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: .5rem;
}

.range-info,
.duration {
    margin-top: .5rem;
    display: flex;
    align-items: center;
    gap: .5rem;
}

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
</style>