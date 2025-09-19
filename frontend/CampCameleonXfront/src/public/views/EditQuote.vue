<template>
    <div class="edit-quote-page">
        <!-- Loading state -->
        <div v-if="isLoading" class="loading-container">
            <div class="loading-spinner"></div>
            <h2>⏳ Chargement de votre devis...</h2>
        </div>

        <!-- Error state -->
        <div v-else-if="error" class="error-container">
            <div class="error-box">
                <h2>❌ Erreur</h2>
                <p>{{ error }}</p>
                <div class="error-actions">
                    <button @click="retryLoad" class="btn btn-primary">🔄 Réessayer</button>
                    <a href="mailto:contact@campcameleonx.com" class="btn btn-secondary">📧 Nous contacter</a>
                </div>
            </div>
        </div>

        <!-- Edit form -->
        <div v-else-if="quote" class="edit-quote-container">
            <!-- Header -->
            <div class="edit-header">
                <h1>📝 Modifier votre devis</h1>
                <div class="quote-info">
                    <span class="quote-ref">{{ quote.quote_reference }}</span>
                    <span class="quote-status" :class="statusClass">{{ statusText }}</span>
                    <!-- validé?  -->
                </div>
            </div>

            <!-- Important notice -->
            <div class="notice-box">
                <i class="fas fa-info-circle"></i>
                <div>
                    <p><strong>Modification de votre séjour</strong></p>
                    <p>Vous pouvez ajuster vos sélections. Si vous modifiez les prestations ou dates, un nouvel email de
                        validation vous sera envoyé.</p>
                </div>
            </div>

            <!-- Reuse QuoteModal logic but in full page format -->
            <div class="quote-form-wrapper">
                <!-- Step 1: Dates -->
                <div class="form-section">
                    <h3>📅 Dates de votre séjour</h3>
                    <div class="date-picker-group">
                        <div class="form-group">
                            <label>Date d'arrivée</label>
                            <input type="date" v-model="formData.dates.checkin" class="form-input" :min="minDate" />
                        </div>
                        <div class="form-group">
                            <label>Date de départ</label>
                            <input type="date" v-model="formData.dates.checkout" class="form-input"
                                :min="formData.dates.checkin" />
                        </div>
                        <div class="form-group">
                            <label>Nombre de personnes</label>
                            <select v-model="formData.dates.guests" class="form-input">
                                <option v-for="n in 12" :key="n" :value="n">{{ n }} personne(s)</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Step 2: Activities -->
                <div class="form-section">
                    <h3>🏃‍♂️ Activités</h3>
                    <div v-if="activitiesLoading">Chargement des activités...</div>
                    <div v-else class="products-grid">
                        <div v-for="activity in activities" :key="activity.id" class="product-card"
                            :class="{ 'selected': isProductSelected('activity', activity.id) }"
                            @click="toggleProduct('activity', activity)">
                            <img :src="activity.image || '/default-activity.jpg'" :alt="activity.name" />
                            <h4>{{ activity.name }}</h4>
                            <p class="price">{{ activity.price }}€</p>
                            <p class="description">{{ activity.description }}</p>
                        </div>
                    </div>
                </div>

                <!-- Step 3: Meals -->
                <div class="form-section">
                    <h3>🍽️ Restauration</h3>
                    <div v-if="menusLoading">Chargement des menus...</div>
                    <div v-else class="products-grid">
                        <div v-for="menu in menus" :key="menu.id" class="product-card"
                            :class="{ 'selected': isProductSelected('menu', menu.id) }"
                            @click="toggleProduct('menu', menu)">
                            <img :src="menu.image || '/default-menu.jpg'" :alt="menu.name" />
                            <h4>{{ menu.name }}</h4>
                            <p class="price">{{ menu.price }}€</p>
                            <p class="description">{{ menu.description }}</p>
                        </div>
                    </div>
                </div>

                <!-- Step 4: Accommodation -->
                <div class="form-section">
                    <h3>🏕️ Hébergement</h3>
                    <div v-if="roomsLoading">Chargement des hébergements...</div>
                    <div v-else class="products-grid">
                        <div v-for="room in rooms" :key="room.id" class="product-card"
                            :class="{ 'selected': isProductSelected('room', room.id) }"
                            @click="toggleProduct('room', room)">
                            <img :src="room.image || '/default-room.jpg'" :alt="room.name" />
                            <h4>{{ room.name }}</h4>
                            <p class="price">{{ room.price }}€</p>
                            <p class="description">{{ room.description }}</p>
                            <div v-if="room.productable" class="room-details">
                                <span>Capacité: {{ room.productable.capacity }} pers.</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Step 5: Contact & Message -->
                <div class="form-section">
                    <h3>💬 Message (optionnel)</h3>
                    <div class="form-group">
                        <label>Informations complémentaires</label>
                        <textarea v-model="formData.message" class="form-textarea" rows="4"
                            placeholder="Régimes alimentaires, demandes spéciales, questions..."></textarea>
                    </div>
                </div>

                <!-- Summary & Actions -->
                <div class="quote-summary">
                    <h3>📋 Récapitulatif</h3>

                    <!-- Si on a des produits avec quantités du backend -->
                    <div v-if="quote?.products_with_quantities?.length" class="summary-items">
                        <div v-for="item in selectedItemsForDisplay" :key="item.id" class="summary-item enhanced">
                            <div class="item-info">
                                <span class="item-name">{{ item.name }}</span>
                                <span class="item-unit-price">{{ item.unitPrice }}€/unité</span>
                            </div>

                            <div class="item-controls">
                                <label class="qty-label">Quantité:</label>
                                <select class="qty-select" :value="item.quantity"
                                    @change="setProductQuantity(item.product_id, $event.target.value)">
                                    <option value="0">Supprimer</option>
                                    <option v-for="n in getMaxQuantity(item.product_id)" :key="n" :value="n">{{ n }}
                                    </option>
                                </select>
                            </div>

                            <div class="item-total">
                                <strong>{{ item.price }}€</strong>
                            </div>
                        </div>
                    </div>

                    <!-- Fallback pour l'ancien système -->
                    <div v-else class="summary-items">
                        <div v-for="item in selectedItemsForDisplay" :key="item.id" class="summary-item">
                            <span>{{ item.name }}</span>
                            <span>{{ item.price }}€</span>
                        </div>
                    </div>

                    <div class="summary-total">
                        <strong>Total: {{ totalPrice }}€</strong>
                        <small v-if="Object.keys(formData.quantityOverrides).length > 0" class="modified-notice">
                            * Quantités personnalisées appliquées
                        </small>
                    </div>
                </div>

                <!-- Action buttons -->
                <div class="form-actions">
                    <button @click="saveChanges" :disabled="isSubmitting || !hasChanges" class="btn btn-primary">
                        <i v-if="isSubmitting" class="fas fa-spinner fa-spin"></i>
                        <i v-else class="fas fa-save"></i>
                        {{ isSubmitting ? 'Sauvegarde...' : 'Sauvegarder les modifications' }}
                    </button>

                    <button @click="cancelEdit" class="btn btn-secondary">
                        <i class="fas fa-times"></i>
                        Annuler
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { ref, reactive, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import PublicApi from '@/services/PublicApi'
import { computeQuoteTotal } from '@/shared/composables/useQuotePricing'

export default {
    name: 'EditQuote',

    setup() {
        const route = useRoute()
        const router = useRouter()

        // --- ÉTAT
        const isLoading = ref(true)
        const error = ref(null)
        const quote = ref(null)
        const isSubmitting = ref(false)

        // --- FORM DATA
        const formData = reactive({
            dates: { checkin: '', checkout: '', guests: 2 },
            selectedItems: { activity: [], menu: [], room: [] },
            message: '',
            quantityOverrides: {} // { productId: quantity }
        })

        // --- CATALOGUES
        const activities = ref([])
        const menus = ref([])
        const rooms = ref([])
        const activitiesLoading = ref(false)
        const menusLoading = ref(false)
        const roomsLoading = ref(false)

        // --- API
        const api = new PublicApi()

        // --- HELPERS
        const toLocalDateInput = (iso) => {
            if (!iso) return ''
            const d = new Date(iso)
            const y = d.getFullYear()
            const m = String(d.getMonth() + 1).padStart(2, '0')
            const day = String(d.getDate()).padStart(2, '0')
            return `${y}-${m}-${day}`
        }
        const dateOnly = (s) => (s ? String(s).split('T')[0] : '')
        // Obtenir la quantité effective d'un produit
        const getProductQuantity = (productId) => {
            // Si override personnalisé défini, l'utiliser
            if (formData.quantityOverrides[productId] !== undefined) {
                return formData.quantityOverrides[productId]
            }

            // Sinon utiliser les données du backend
            const productWithQty = quote.value?.products_with_quantities?.find(p => p.product_id === productId)
            if (productWithQty) {
                return productWithQty.quantity
            }

            // Fallback : compter dans selected_product_ids
            return (quote.value?.selected_product_ids || []).filter(id => id === productId).length
        }

        // Ajouter cette méthode pour synchroniser selectedItems avec les overrides
        const syncSelectedItemsWithOverrides = () => {
            if (!quote.value?.products_with_quantities) return

            // Vider les sélections actuelles
            formData.selectedItems.activity = []
            formData.selectedItems.menu = []
            formData.selectedItems.room = []

            // Reconstruire selon les quantités effectives
            for (const item of quote.value.products_with_quantities) {
                const quantity = getProductQuantity(item.product_id)

                if (quantity > 0) {
                    // Répéter l'ID selon la quantité pour le système de calcul
                    for (let i = 0; i < quantity; i++) {
                        if (activities.value.some(a => a.id === item.product_id)) {
                            formData.selectedItems.activity.push(item.product_id)
                        } else if (menus.value.some(m => m.id === item.product_id)) {
                            formData.selectedItems.menu.push(item.product_id)
                        } else if (rooms.value.some(r => r.id === item.product_id)) {
                            formData.selectedItems.room.push(item.product_id)
                        }
                    }
                }
            }
        }

        // Modifier setProductQuantity pour synchroniser après chaque changement
        const setProductQuantity = (productId, quantity) => {
            const qty = Math.max(0, parseInt(quantity) || 0)

            if (qty === 0) {
                delete formData.quantityOverrides[productId]
            } else {
                formData.quantityOverrides[productId] = qty
            }

            // Synchroniser selectedItems pour que computeQuoteTotal voie les changements
            syncSelectedItemsWithOverrides()
        }

        // Obtenir la quantité max autorisée pour un produit
        const getMaxQuantity = (productId) => {
            // Pour les activités/menus, limiter au nombre d'invités × nombre de nuits
            const currentNights = nights.value
            const currentGuests = Math.max(1, formData.dates.guests || 1)
            return currentGuests * currentNights
        }

        // --- COMPUTED
        const minDate = computed(() => {
            const tomorrow = new Date()
            tomorrow.setDate(tomorrow.getDate() + 1)
            return tomorrow.toISOString().split('T')[0]
        })

        // Calcul centralisé (composable)
        const pricing = computed(() =>
            computeQuoteTotal({
                selected: formData.selectedItems,
                catalog: { activities: activities.value, menus: menus.value, rooms: rooms.value },
                dates: formData.dates,
                rules: {
                    roomPerNight: true,
                    menuPerGuest: true,
                    activityParGuest: true
                }
            })
        )

        const totalPrice = computed(() => {
            if (quote.value?.products_with_quantities && Object.keys(formData.quantityOverrides).length > 0) {
                // Recalcul avec les overrides
                let total = 0
                for (const item of quote.value.products_with_quantities) {
                    const quantity = getProductQuantity(item.product_id)
                    const unitPrice = item.product?.price || 0
                    total += unitPrice * quantity
                }
                return total
            }

            if (quote.value?.total_amount) {
                return parseFloat(quote.value.total_amount)
            }

            return pricing.value.total
        })

        const nights = computed(() => pricing.value.nights)

        const selectedItemsForDisplay = computed(() => {
            if (quote.value?.products_with_quantities && Array.isArray(quote.value.products_with_quantities)) {
                return quote.value.products_with_quantities.map(item => {
                    const quantity = getProductQuantity(item.product_id)
                    const unitPrice = item.product?.price || 0

                    return {
                        id: `product-${item.product_id}`,
                        product_id: item.product_id,
                        name: item.product?.name || `Produit #${item.product_id}`,
                        quantity: quantity,
                        unitPrice: unitPrice,
                        price: unitPrice * quantity
                        // ✅ SUPPRIMÉ: type qui causait des erreurs
                    }
                }).filter(item => item.quantity > 0)
            }

            // Fallback
            return pricing.value.lines.map((l) => ({
                id: `${l.type}-${l.id}`,
                name: l.name + (l.qty > 1 ? ` × ${l.qty}` : ''),
                price: l.lineTotal
            }))
        })

        const statusClass = computed(() => ({
            'status-draft': quote.value?.status === 'draft',
            'status-sent': quote.value?.status === 'sent',
            'status-validated': !!quote.value?.email_verified_at
        }))

        const statusText = computed(() => {
            if (!quote.value) return ''
            if (quote.value.email_verified_at) return 'Validé'
            return quote.value.status === 'sent' ? 'Envoyé' : 'Brouillon'
        })

        const hasChanges = computed(() => {
            if (!quote.value) return false
            const originalIds = quote.value.selected_product_ids || []
            const currentIds = Object.values(formData.selectedItems).flat()

            return (
                dateOnly(formData.dates.checkin) !== dateOnly(quote.value.checkin_date) ||
                dateOnly(formData.dates.checkout) !== dateOnly(quote.value.checkout_date) ||
                Number(formData.dates.guests) !== Number(quote.value.guests || 2) ||
                formData.message !== (quote.value.message || '') ||
                JSON.stringify([...originalIds].sort()) !== JSON.stringify([...currentIds].sort())
            )
        })

        // --- LOADERS
        const loadQuote = async () => {
            try {
                isLoading.value = true
                error.value = null

                const { quoteId, editToken } = route.params
                const result = await api.getQuoteForEdit(quoteId, editToken)
                quote.value = result.quote

                // 1) produits
                await loadProducts()

                // 2) pré-remplir form
                formData.dates.checkin = toLocalDateInput(quote.value.checkin_date)
                formData.dates.checkout = toLocalDateInput(quote.value.checkout_date)
                formData.dates.guests = quote.value.guests || 2
                formData.message = quote.value.message || ''

                // 3) sélections
                populateSelectedItems()
            } catch (err) {
                error.value = err.message || 'Erreur lors du chargement du devis.'
            } finally {
                isLoading.value = false
            }
        }

        const loadProducts = async () => {
            activitiesLoading.value = true
            menusLoading.value = true
            roomsLoading.value = true
            try {
                const [acts, mens, rms] = await Promise.all([
                    api.getActivities({ per_page: 20 }),
                    api.getMenus({ per_page: 20 }),
                    api.getRooms({ per_page: 20 })
                ])

                activities.value = acts.data ?? []
                menus.value = mens.data ?? []
                rooms.value = rms.data ?? []

                // Si certains IDs sélectionnés ne sont pas dans la page chargée
                await ensureProductsForSelectedIds()
            } catch (err) {
                console.error('❌ Erreur chargement produits:', err)
            } finally {
                activitiesLoading.value = false
                menusLoading.value = false
                roomsLoading.value = false
            }
        }

        // Charge les produits manquants (hors page) via /products/:id
        const ensureProductsForSelectedIds = async () => {
            if (!quote.value?.selected_product_ids?.length) return

            const present = new Set([
                ...activities.value.map((p) => p.id),
                ...menus.value.map((p) => p.id),
                ...rooms.value.map((p) => p.id)
            ])

            const missing = quote.value.selected_product_ids.filter((id) => !present.has(id))
            if (!missing.length) return

            const results = await Promise.allSettled(missing.map((id) => api.getProduct(id)))
            for (const r of results) {
                if (r.status === 'fulfilled') {
                    const p = r.value
                    const cls = p.productable_type || p.productableType || ''
                    if (cls.includes('Activity')) activities.value.push(p)
                    else if (cls.includes('Menu')) menus.value.push(p)
                    else if (cls.includes('Room')) rooms.value.push(p)
                }
            }
        }

        // --- SELECTIONS
        const populateSelectedItems = () => {
            if (!quote.value?.selected_product_ids) return

            // reset pour éviter doublons au retry
            formData.selectedItems.activity = []
            formData.selectedItems.menu = []
            formData.selectedItems.room = []

            for (const productId of quote.value.selected_product_ids) {
                if (activities.value.some((a) => a.id === productId)) {
                    formData.selectedItems.activity.push(productId)
                } else if (menus.value.some((m) => m.id === productId)) {
                    formData.selectedItems.menu.push(productId)
                } else if (rooms.value.some((r) => r.id === productId)) {
                    formData.selectedItems.room.push(productId)
                }
            }
        }

        const isProductSelected = (type, productId) => formData.selectedItems[type].includes(productId)

        const toggleProduct = (type, product) => {
            const selectedList = formData.selectedItems[type]
            const idx = selectedList.indexOf(product.id)

            if (idx > -1) {
                selectedList.splice(idx, 1)
            } else {
                if (type === 'room') {
                    formData.selectedItems.room = [product.id] // une chambre max
                } else {
                    selectedList.push(product.id)
                }
            }
        }

        // --- ACTIONS
        const saveChanges = async () => {
            if (isSubmitting.value || !hasChanges.value) return
            try {
                isSubmitting.value = true

                // ✅ Utiliser votre logique existante pour vérifier les hébergements
                if (formData.selectedItems.room.length === 0) {
                    throw new Error('Veuillez sélectionner un hébergement')
                }

                // Construire product_ids en "dépliant" les quantités
                const productIds = []
                for (const item of selectedItemsForDisplay.value) {
                    for (let i = 0; i < item.quantity; i++) {
                        productIds.push(item.product_id)
                    }
                }

                const updateData = {
                    product_ids: productIds,
                    total_price: totalPrice.value,
                    dates: formData.dates,
                    message: formData.message
                }

                const { quoteId, editToken } = route.params
                const result = await api.updateQuote(quoteId, editToken, updateData)

                alert('✅ Modifications sauvegardées !')
                router.push('/')
            } catch (err) {
                console.error('❌ Erreur sauvegarde:', err)
                alert('❌ Erreur\n\n' + (err.message || 'Sauvegarde impossible'))
            } finally {
                isSubmitting.value = false
            }
        }

        const cancelEdit = () => {
            if (hasChanges.value && !confirm('Abandonner les modifications ?')) return
            router.push('/')
        }

        const retryLoad = () => loadQuote()

        // --- LIFECYCLE
        onMounted(loadQuote)

        // --- RETURN
        return {
            // état
            isLoading, error, quote, isSubmitting,
            // form/catalogues  
            formData, activities, menus, rooms,
            activitiesLoading, menusLoading, roomsLoading,
            // computed
            minDate, statusClass, statusText,
            selectedItemsForDisplay, totalPrice, nights, hasChanges,
            // méthodes existantes
            isProductSelected, toggleProduct, saveChanges, cancelEdit, retryLoad,
            // méthodes pour les quantités
            getProductQuantity, setProductQuantity, getMaxQuantity
        }
    }
}
</script>


<style scoped>
.edit-quote-page {
    min-height: 100vh;
    background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
    padding: 20px;
}

.edit-quote-container {
    max-width: 1200px;
    margin: 0 auto;
    background: white;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
}

.edit-header {
    background: linear-gradient(135deg, #c17c4a 0%, #8b5a3c 100%);
    color: white;
    padding: 30px;
    text-align: center;
}

.quote-info {
    margin-top: 15px;
    display: flex;
    justify-content: center;
    gap: 20px;
    align-items: center;
}

.quote-ref {
    background: rgba(255, 255, 255, 0.2);
    padding: 5px 15px;
    border-radius: 15px;
    font-weight: bold;
}

.quote-status {
    padding: 5px 15px;
    border-radius: 15px;
    font-size: 0.9em;
}

.status-validated {
    background: #d4edda;
    color: #155724;
}

.status-sent {
    background: #fff3cd;
    color: #856404;
}

.status-draft {
    background: #f8d7da;
    color: #721c24;
}

.notice-box {
    background: #e7f3ff;
    border: 1px solid #b3d9ff;
    border-radius: 8px;
    padding: 20px;
    margin: 20px;
    display: flex;
    gap: 15px;
    align-items: flex-start;
}

.notice-box i {
    color: #0066cc;
    font-size: 1.2em;
    margin-top: 2px;
}

.quote-form-wrapper {
    padding: 30px;
}

.form-section {
    margin-bottom: 40px;
}

.form-section h3 {
    color: #c17c4a;
    margin-bottom: 20px;
    font-size: 1.3em;
}

.date-picker-group {
    display: grid;
    grid-template-columns: 1fr 1fr 1fr;
    gap: 20px;
}

@media (max-width: 768px) {
    .date-picker-group {
        grid-template-columns: 1fr;
    }
}

.products-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 20px;
}

.product-card {
    border: 2px solid #e9ecef;
    border-radius: 8px;
    padding: 15px;
    cursor: pointer;
    transition: all 0.3s ease;
}

.product-card:hover {
    border-color: #c17c4a;
}

.product-card.selected {
    border-color: #28a745;
    background: #f8fff8;
}

.product-card img {
    width: 100%;
    height: 120px;
    object-fit: cover;
    border-radius: 5px;
    margin-bottom: 10px;
}

.product-card h4 {
    margin: 10px 0 5px 0;
    color: #333;
}

.product-card .price {
    font-weight: bold;
    color: #c17c4a;
    font-size: 1.1em;
    margin: 5px 0;
}

.product-card .description {
    font-size: 0.9em;
    color: #666;
    margin: 5px 0;
}

.quote-summary {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 25px;
    margin: 30px 0;
    border-left: 4px solid #c17c4a;
}

.summary-items {
    margin: 15px 0;
}

.summary-item {
    display: flex;
    justify-content: space-between;
    padding: 8px 0;
    border-bottom: 1px solid #dee2e6;
}

.summary-total {
    margin-top: 15px;
    padding-top: 15px;
    border-top: 2px solid #c17c4a;
    font-size: 1.2em;
    text-align: right;
}

.form-actions {
    display: flex;
    gap: 15px;
    justify-content: center;
    margin-top: 30px;
}

.btn {
    padding: 12px 30px;
}

.btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.loading-container,
.error-container {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    min-height: 60vh;
    text-align: center;
}

.loading-spinner {
    width: 50px;
    height: 50px;
    border: 4px solid #f3f3f3;
    border-top: 4px solid #c17c4a;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin-bottom: 20px;
}

@keyframes spin {
    0% {
        transform: rotate(0deg);
    }

    100% {
        transform: rotate(360deg);
    }
}

.error-box {
    background: #f8d7da;
    border: 1px solid #f5c6cb;
    color: #721c24;
    padding: 30px;
    border-radius: 8px;
    max-width: 500px;
}

.error-actions {
    display: flex;
    gap: 15px;
    justify-content: center;
    margin-top: 20px;
}

.summary-item.enhanced {
    display: grid;
    grid-template-columns: 1fr auto auto;
    gap: 15px;
    align-items: center;
    padding: 15px 0;
    border-bottom: 1px solid #dee2e6;
}

.item-info {
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.item-name {
    font-weight: 500;
    color: #333;
}

.item-unit-price {
    font-size: 0.9em;
    color: #666;
}

.item-controls {
    display: flex;
    align-items: center;
    gap: 8px;
}

.qty-label {
    font-size: 0.9em;
    color: #666;
    white-space: nowrap;
}

.qty-select {
    padding: 5px 8px;
    border: 1px solid #ddd;
    border-radius: 4px;
    background: white;
    min-width: 70px;
    font-size: 0.9em;
}

.qty-select:focus {
    border-color: #c17c4a;
    outline: none;
    box-shadow: 0 0 0 2px rgba(193, 124, 74, 0.2);
}

.item-total {
    text-align: right;
    color: #c17c4a;
    font-weight: bold;
}

.modified-notice {
    display: block;
    margin-top: 5px;
    color: #856404;
    font-style: italic;
}

/* Responsive */
@media (max-width: 768px) {
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
}

/* Animation pour les changements de quantité */
.summary-item.enhanced {
    transition: background-color 0.3s ease;
}

.summary-item.enhanced:hover {
    background-color: rgba(193, 124, 74, 0.05);
}
</style>