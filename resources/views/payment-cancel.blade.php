<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paiement annulé - CampCameleonX</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            line-height: 1.6; 
            color: #333; 
            max-width: 600px; 
            margin: 50px auto; 
            padding: 20px; 
            background-color: #f8f9fa;
        }
        .cancel-box { 
            background: white; 
            border-radius: 10px; 
            padding: 40px 30px; 
            text-align: center;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .cancel-icon {
            font-size: 4rem;
            color: #ffc107;
            margin-bottom: 20px;
            display: block;
        }
        .button { 
            display: inline-block; 
            background: #c17c4a; 
            color: white; 
            padding: 15px 30px; 
            text-decoration: none; 
            border-radius: 8px; 
            margin: 10px; 
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
        .quote-info {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
            text-align: left;
        }
        .help-text {
            color: #6c757d;
            font-size: 0.9rem;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="cancel-box">
        <span class="cancel-icon">⏸️</span>
        <h1>Paiement annulé</h1>
        <p>Vous avez annulé le processus de paiement. Aucune transaction n'a été effectuée.</p>
        
        @if($quote)
        <div class="quote-info">
            <h3>📋 Votre devis reste disponible :</h3>
            <p><strong>Référence :</strong> {{ $quote->quote_reference }}</p>
            <p><strong>Montant :</strong> {{ number_format($quote->total_amount, 2, ',', ' ') }} €</p>
            @if($quote->checkin_date)
            <p><strong>Séjour :</strong> {{ $quote->checkin_date->format('d/m/Y') }} @if($quote->checkout_date) au {{ $quote->checkout_date->format('d/m/Y') }} @endif</p>
            @endif
        </div>
        
        <p>Votre devis est toujours valide. Vous pouvez procéder au paiement plus tard ou nous contacter pour toute question.</p>
        @endif

        <div style="margin-top: 30px;">
            <a href="{{ env('APP_FRONTEND_URL', '/') }}" class="button">
                🏠 Retour à l'accueil
            </a>
            <a href="mailto:contact@campcameleonx.com@if($quote)?subject=Question devis {{ $quote->quote_reference }}@endif" class="button secondary">
                📧 Nous contacter
            </a>
        </div>

        <div class="help-text">
            <p><strong>Besoin d'aide pour finaliser votre réservation ?</strong><br>
            📞 +33 X XX XX XX XX | ✉️ contact@campcameleonx.com</p>
        </div>
    </div>
</body>
</html>