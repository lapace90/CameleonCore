<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email vérifié - CampCameleonX</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 50px auto;
            padding: 20px;
            background-color: #f5f5f5;
        }

        .success-box {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
            padding: 30px;
            border-radius: 8px;
            text-align: center;
        }

        .payment-section {
            background: white;
            border: 2px solid #28a745;
            padding: 25px;
            border-radius: 8px;
            margin: 20px 0;
            text-align: center;
        }

        .button-primary {
            display: inline-block;
            background: #28a745;
            color: white;
            padding: 15px 40px;
            text-decoration: none;
            border-radius: 5px;
            margin: 15px 10px;
            font-weight: bold;
            font-size: 18px;
            box-shadow: 0 4px 8px rgba(40, 167, 69, 0.3);
            transition: all 0.3s;
            cursor: pointer;
            border: none;
        }

        .button-primary:hover {
            background: #218838;
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(40, 167, 69, 0.4);
        }

        .button-secondary {
            display: inline-block;
            background: #6c757d;
            color: white;
            padding: 12px 30px;
            text-decoration: none;
            border-radius: 5px;
            margin: 10px;
        }

        .quote-summary {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 5px;
            border-left: 4px solid #28a745;
            margin: 20px 0;
            text-align: left;
        }

        .loading-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
            color: white;
            font-size: 18px;
        }

        .loading-overlay>div {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 15px;
        }

        .spinner {
            width: 50px;
            height: 50px;
            border: 4px solid rgba(255, 255, 255, 0.3);
            border-top: 4px solid #ffffff;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }
    </style>
</head>

<body>
    <!-- Loading overlay -->
    <div id="loadingOverlay" class="loading-overlay">
        <div>
            <div class="spinner"></div>
            Préparation du paiement...
        </div>
    </div>

    <div class="success-box">
        <h1><i class="fas fa-check-circle" style="padding: .5rem;"></i> Email vérifié avec succès !</h1>
        <p>Merci {{ $quote->name ?? 'Client' }}, votre adresse email a été vérifiée.</p>
    </div>

    <!-- SECTION PAIEMENT -->
    <div class="payment-section">
        <h2><i class="fas fa-credit-card" style="padding: .5rem;"></i> Procéder au paiement</h2>
        <p>Votre devis <strong>{{ $quote->quote_reference }}</strong> est validé !</p>

        <div class="quote-summary">
            <h3><i class="fas fa-clipboard-list" style="padding: .5rem;"></i> Récapitulatif :</h3>
            <ul>
                <li><strong>Référence :</strong> {{ $quote->quote_reference }}</li>
                @if($quote->checkin_date && $quote->checkout_date)
                <li><strong>Dates :</strong> Du {{ $quote->checkin_date->format('d/m/Y') }} au {{ $quote->checkout_date->format('d/m/Y') }}</li>
                @endif
                @if($quote->guests)
                <li><strong>Personnes :</strong> {{ $quote->guests }}</li>
                @endif
                <li><strong>Total :</strong> {{ number_format($quote->total_amount, 2) }}€</li>
            </ul>
        </div>

        <!-- BOUTON PAIEMENT PRINCIPAL -->
        <button id="payButton"
            data-quote-id="{{ $quote->id }}"
            class="button-primary">
            <i class="fas fa-credit-card" style="padding: .5rem;"></i> Payer maintenant - {{ number_format($quote->total_amount, 2) }}€
        </button>

        <br><br>
        <!-- BOUTON POUR REVENIR MODIFIER LE DEVIS -->
        <a href="{{ $editUrl }}" class="button-secondary" id="editQuoteBtn">
            <i class="fas fa-edit" style="padding: .5rem;"></i> Modifier mon devis
        </a>

        <a href="mailto:contact@campcameleonx.com?subject=Question devis {{ $quote->quote_reference }}"
            class="button-secondary">
            <i class="fas fa-envelope"  style="padding: .5rem;"></i>Une question ?
        </a>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const payButton = document.getElementById('payButton');
            const editButton = document.getElementById('editQuoteBtn');

            // Bouton paiement (existant)
            payButton.addEventListener('click', function() {
                const quoteId = parseInt(this.dataset.quoteId);
                proceedToPayment(quoteId);
            });

            //  Bouton édition
            editButton.addEventListener('click', function(event) {
                event.preventDefault();

                // URL du frontend Vue configurée
                const frontendUrl = '{{ env("APP_FRONTEND_URL", "http://localhost:5173") }}';
                const editUrl = `${frontendUrl}/edit-quote/{{ $quote->id }}/{{ $quote->validation_token }}`;

                console.log('📝 Redirection vers édition:', editUrl);

                // Redirection vers le SPA Vue
                window.location.href = editUrl;
            });
        });

        async function proceedToPayment(quoteId) {
            const payButton = document.getElementById('payButton');
            const loadingOverlay = document.getElementById('loadingOverlay');

            try {
                console.log('🚀 Création session paiement pour devis:', quoteId);

                // Afficher loading
                const originalText = payButton.innerHTML;
                payButton.innerHTML = '⏳ Préparation...';
                payButton.disabled = true;
                loadingOverlay.style.display = 'flex';

                // Construire l'URL API - ATTENTION aux variables config
                const apiUrl = '{{ config("app.api_url", config("app.url")) }}';
                const url = `${apiUrl}/api/stripe/create-payment-session`;

                console.log('📡 URL API:', url);

                // Appel API Stripe
                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        quote_id: quoteId
                    })
                });

                console.log('📡 Status response:', response.status);

                if (!response.ok) {
                    const errorData = await response.json().catch(() => ({}));
                    console.error('❌ Erreur API:', errorData);

                    let errorMessage = 'Erreur lors de la création de la session de paiement';
                    if (errorData.error) {
                        errorMessage = errorData.error;
                    }

                    throw new Error(errorMessage);
                }

                const data = await response.json();
                console.log('✅ Response data:', data);

                if (!data.success || !data.checkout_url) {
                    throw new Error(data.error || 'URL de paiement manquante');
                }

                console.log('✅ Session créée, redirection vers:', data.checkout_url);

                // Redirection vers Stripe Checkout
                window.location.href = data.checkout_url;

            } catch (error) {
                console.error('❌ Erreur paiement:', error);

                // Masquer loading
                loadingOverlay.style.display = 'none';

                // Restaurer le bouton
                payButton.innerHTML = '<i class="fas fa-credit-card" style="padding: .5rem;"></i> Payer maintenant - {{ number_format($quote->total_amount, 2) }}€';
                payButton.disabled = false;

                // Afficher erreur utilisateur
                let userMessage = '<i class="fas fa-times-circle" style="padding: .5rem;"></i> Erreur de paiement\n\n';

                if (error.message.includes('validé par email')) {
                    userMessage += 'Ce devis doit être validé par email avant le paiement.';
                } else if (error.message.includes('fetch')) {
                    userMessage += 'Problème de connexion. Vérifiez votre réseau et réessayez.';
                } else {
                    userMessage += error.message;
                }

                userMessage += '\n\nVeuillez réessayer ou nous contacter si le problème persiste.';

                alert(userMessage);
            }
        }

        // Log pour debug
        console.log('🎯 Page paiement chargée pour devis:', '{{ $quote->quote_reference }}');
        console.log('💰 Montant:', '{{ $quote->total_amount }}€');
    </script>

    <div style="text-align: center; margin-top: 30px; color: #666; font-size: 12px;">
        <p>CampCameleonX - Votre évasion dans le désert marocain<br>
            <i class="fas fa-phone" style="padding: .5rem;"></i> +33 X XX XX XX XX | <i class="fas fa-envelope" style="padding: .5rem;"></i>contact@campcameleonx.com</p>
    </div>
</body>

</html>