<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paiement réussi - CampCameleonX</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            line-height: 1.6; 
            color: #333; 
            max-width: 700px; 
            margin: 50px auto; 
            padding: 20px; 
            background-color: #f8f9fa;
        }
        .success-container { 
            background: white; 
            border-radius: 10px; 
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .success-header {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            padding: 40px 30px;
            text-align: center;
        }
        .success-icon {
            font-size: 4rem;
            margin-bottom: 20px;
            display: block;
        }
        .success-body {
            padding: 40px 30px;
        }
        .payment-details {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 25px;
            margin: 25px 0;
            border-left: 5px solid #28a745;
        }
        .detail-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 12px 0;
            padding: 8px 0;
            border-bottom: 1px solid #e9ecef;
        }
        .detail-row:last-child {
            border-bottom: none;
            font-weight: bold;
            font-size: 1.1rem;
            color: #28a745;
        }
        .button {
            display: inline-block;
            background: #c17c4a;
            color: white;
            padding: 15px 30px;
            text-decoration: none;
            border-radius: 8px;
            margin: 10px 10px 10px 0;
            font-weight: bold;
            transition: background 0.3s;
        }
        .button:hover {
            background: #a66a3e;
        }
        .button.secondary {
            background: #6c757d;
        }
        .button.secondary:hover {
            background: #5a6268;
        }
        .next-steps {
            background: #e7f3ff;
            border: 1px solid #b3d9ff;
            border-radius: 8px;
            padding: 25px;
            margin: 30px 0;
        }
        .contact-info {
            text-align: center;
            color: #6c757d;
            font-size: 0.9rem;
            margin-top: 30px;
        }
    </style>
</head>
<body>
    <div class="success-container">
        <div class="success-header">
            <span class="success-icon">🎉</span>
            <h1>Paiement réussi !</h1>
            <p>Votre réservation est confirmée</p>
        </div>

        <div class="success-body">
            <p><strong>Merci {{ $quote->customer?->name }} !</strong></p>
            <p>Votre paiement a été traité avec succès. Vous recevrez un email de confirmation dans quelques minutes avec tous les détails de votre séjour.</p>

            <div class="payment-details">
                <h3>📋 Détails de votre commande</h3>
                <div class="detail-row">
                    <span>Référence :</span>
                    <strong>{{ $quote->quote_reference }}</strong>
                </div>
                @if($quote->checkin_date && $quote->checkout_date)
                <div class="detail-row">
                    <span>Séjour :</span>
                    <span>Du {{ $quote->checkin_date->format('d/m/Y') }} au {{ $quote->checkout_date->format('d/m/Y') }}</span>
                </div>
                @endif
                <div class="detail-row">
                    <span>Personnes :</span>
                    <span>{{ $quote->guests }}</span>
                </div>
                <div class="detail-row">
                    <span>Produits :</span>
                    <span>{{ count($quote->selected_product_ids) }} sélection(s)</span>
                </div>
                <div class="detail-row">
                    <span>Montant payé :</span>
                    <strong>{{ number_format($amount_paid, 2, ',', ' ') }} €</strong>
                </div>
            </div>

            <div class="next-steps">
                <h3>📬 Prochaines étapes</h3>
                <ul>
                    <li>✅ <strong>Email de confirmation</strong> : Vous le recevrez sous 5 minutes</li>
                    <li>📄 <strong>Facture</strong> : Incluse dans l'email de confirmation</li>
                    <li>📞 <strong>Contact avant séjour</strong> : Nous vous appellerons 48h avant pour finaliser les détails</li>
                    <li>🏕️ <strong>Séjour inoubliable</strong> : Préparez-vous à vivre une expérience unique !</li>
                </ul>
            </div>

            <div style="text-align: center; margin-top: 30px;">
                <a href="mailto:contact@campcameleonx.com?subject=Commande {{ $quote->quote_reference }}" class="button">
                    📧 Nous contacter
                </a>
                <a href="{{ config('app.frontend_url', '/') }}" class="button secondary">
                    🏠 Retour à l'accueil
                </a>
            </div>

            <div class="contact-info">
                <p><strong>Besoin d'aide ?</strong><br>
                📞 +33 X XX XX XX XX | ✉️ contact@campcameleonx.com<br>
                Horaires : Lundi-Vendredi 9h-18h, Samedi 9h-16h</p>
            </div>
        </div>
    </div>

    <script>
        // Optionnel : Analytics ou tracking de conversion
        console.log('💳 Paiement confirmé:', {
            reference: '{{ $quote->quote_reference }}',
            amount: '{{ $amount_paid }}',
            customer: '{{ $quote->email }}'
        });
    </script>
</body>
</html>