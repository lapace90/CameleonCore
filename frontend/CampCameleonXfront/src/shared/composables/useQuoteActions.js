// ===========================
// USEQUE ACTIONS - Actions finales (paiement, sauvegarde, conseil)
// ===========================

import { ref } from 'vue'
import { publicApi } from '@/services/PublicApi'

export function useQuoteActions(selectedItems, selectedDates, contactInfo, totalPrice, emit) {
    const isSubmitting = ref(false)

    // Sauvegarde de base (exactement comme dans QuoteModal)
    const saveQuote = async () => {
        const items = []

        // Construire les items avec quantités
        selectedItems.activities.forEach(activity => {
            items.push({ product_id: activity.id, quantity: 1 })
        })

        selectedItems.menus.forEach(menu => {
            items.push({ product_id: menu.id, quantity: 1 })
        })

        if (selectedItems.room) {
            items.push({ product_id: selectedItems.room.id, quantity: 1 })
        }

        // Construire product_ids pour compatibilité backend
        const product_ids = []
        for (const it of items) {
            for (let i = 0; i < it.quantity; i++) {
                product_ids.push(it.product_id)
            }
        }

        if (!product_ids.length) {
            throw new Error('Aucun produit sélectionné.')
        }

        const quoteData = {
            email: contactInfo.email,
            contact: {
                name: contactInfo.name,
                last_name: contactInfo.last_name,
                phone: contactInfo.phone,
                message: contactInfo.message
            },
            dates: {
                checkin: selectedDates.start,
                endExclusive: selectedDates.endExclusive,
                guests: selectedDates.guests
            },
            total_price: totalPrice.value,
            product_ids
        }

        // console.log('📤 Données envoyées à l'API:', { 
        //   ...quoteData, 
        //   product_ids_count: product_ids.length 
        // })

        return await publicApi.saveQuote(quoteData)
    }

    // Créer session Stripe (exactement comme dans QuoteModal)
    const createStripeSession = async (quoteId) => {
        const response = await fetch(`${import.meta.env.VITE_API_URL}/stripe/create-payment-session`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ quote_id: quoteId })
        })

        const data = await response.json()

        if (!response.ok) {
            throw new Error(data.error || 'Erreur lors de la création de la session de paiement')
        }

        return data
    }

    // Affichage validation email (exactement comme dans QuoteModal)
    const showEmailValidationRequired = (quote) => {
        emit('close')
        alert(`📧 Validation email requise

Votre devis ${quote.quote_reference} a été créé !
1) Vérifiez votre boîte (${quote.email || contactInfo.email})
2) Cliquez sur le lien de validation
3) Reprenez le paiement ici`)
    }

    // Modal de succès (exactement comme dans QuoteModal)
    const showSuccessModal = ({ title, message }) => {
        emit('close')
        alert(`${title}\n\n${message}`)
    }

    // ACTION 1: Réserver et payer (exactement comme dans QuoteModal)
    const createReservationAndPay = async () => {
        if (isSubmitting.value) return

        isSubmitting.value = 'booking'
        try {
            if (!selectedItems.room) {
                throw new Error('Veuillez sélectionner un hébergement avant de procéder au paiement.')
            }

            const quoteResponse = await saveQuote()
            if (!quoteResponse.success) {
                throw new Error(quoteResponse.message || 'Impossible de créer le devis')
            }

            const quote = quoteResponse.quote_request

            if (quote.status === 'draft') {
                showEmailValidationRequired(quote)
                return
            }

            const paymentResponse = await createStripeSession(quote.id)
            if (!paymentResponse.success) {
                throw new Error(paymentResponse.error || 'Impossible de créer la session de paiement')
            }

            window.location.href = paymentResponse.checkout_url

        } catch (e) {
            console.error('❌ Erreur paiement:', e)
            alert('❌ ' + (e.message || 'Erreur inconnue'))
        } finally {
            isSubmitting.value = false
        }
    }

    // ACTION 2: Sauvegarder et voir contacts (exactement comme dans QuoteModal)
    const saveQuoteAndShowContacts = async () => {
        if (isSubmitting.value) return

        isSubmitting.value = 'saving'
        try {
            if (!selectedItems.room) {
                throw new Error('Veuillez sélectionner un hébergement avant de sauvegarder le devis.')
            }

            const result = await saveQuote()
            if (!result.success) {
                throw new Error(result.message || 'Erreur lors de la sauvegarde')
            }

            showSuccessModal({
                title: '📧 Email de validation envoyé !',
                message: `Votre devis ${result.quote_request.quote_reference} a été créé.
Vérifiez votre boîte email et validez-le (lien valable 48h).`
            })

            emit('quote-saved', {
                quote: result.quote_request,
                type: 'email_validation_required'
            })

        } catch (e) {
            console.error('❌ Erreur sauvegarde:', e)
            alert('❌ ' + (e.message || 'Erreur inconnue'))
        } finally {
            isSubmitting.value = false
        }
    }

    // ACTION 3: Demander conseil (exactement comme dans QuoteModal)
    const requestAdvice = async () => {
        if (isSubmitting.value) return

        isSubmitting.value = 'advice'
        try {
            const adviceData = {
                type: 'advice_request',
                email: contactInfo.email,
                contact: contactInfo,
                dates: selectedDates,
                selected_products: {
                    activities: selectedItems.activities,
                    room: selectedItems.room,
                    menus: selectedItems.menus
                },
                total_price: totalPrice.value,
                message: contactInfo.message || 'Demande de conseil personnalisé'
            }

            const response = await publicApi.requestAdvice(adviceData)
            if (!response.success) {
                throw new Error(response.message || 'Erreur lors de l\'envoi')
            }

            showSuccessModal({
                title: '👨‍💼 Demande de conseil envoyée !',
                message: 'Nous vous contacterons sous 24h pour un conseil personnalisé.'
            })

        } catch (e) {
            console.error('❌ Erreur demande conseil:', e)
            alert('❌ ' + (e.message || 'Erreur inconnue'))
        } finally {
            isSubmitting.value = false
        }
    }

    return {
        isSubmitting,
        createReservationAndPay,
        saveQuoteAndShowContacts,
        requestAdvice,
        saveQuote,
        createStripeSession,
        showEmailValidationRequired,
        showSuccessModal
    }
}
