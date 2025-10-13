<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vérification email - CampCameleonX</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #c17c4a; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
        .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 8px 8px; }
        .button { display: inline-block; background: #c17c4a; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; margin: 20px 0; }
        .button:hover { background: #a66a3e; }
        .quote-summary { background: white; padding: 20px; border-radius: 5px; margin: 20px 0; border-left: 4px solid #c17c4a; }
        .footer { text-align: center; margin-top: 30px; font-size: 12px; color: #666; }
    </style>
</head>
<body>
    <div class="header">
        <h1>🏕️ CampCameleonX</h1>
        <p>Vérification de votre adresse email</p>
    </div>
    
    <div class="content">
        {{--  Utiliser $customer_name au lieu de $quote->customer --}}
        <h2>Bonjour {{ $customer_name }} !</h2>
        
        <p>Merci pour votre demande de devis <strong>{{ $reference }}</strong>.</p>
        
        <p>Pour finaliser votre demande et recevoir votre devis détaillé, veuillez cliquer sur le bouton ci-dessous :</p>
        
        <div style="text-align: center;">
            <a href="{{ $verificationUrl }}" class="button">
                ✅ Vérifier mon email
            </a>
        </div>
        
        <div class="quote-summary">
            <h3>📋 Récapitulatif de votre sélection :</h3>
            <ul>
                {{--  Gestion sécurisée des dates nulles --}}
                <li><strong>Séjour :</strong> 
                    @if($quote->checkin_date && $quote->checkout_date)
                        Du {{ $quote->checkin_date->format('d/m/Y') }} au {{ $quote->checkout_date->format('d/m/Y') }}
                    @else
                        Dates à confirmer
                    @endif
                </li>
                <li><strong>Personnes :</strong> {{ $quote->guests ?? 'À préciser' }}</li>
                <li><strong>Total estimé :</strong> {{ number_format($quote->total_amount, 2) }}€</li>
            </ul>
        </div>
        
        <p><small>Ce lien est valable pendant 48h. Si vous n'arrivez pas à cliquer, copiez-collez cette adresse dans votre navigateur :<br>
        <code>{{ $verificationUrl }}</code></small></p>
        
        <div class="footer">
            <p>CampCameleonX - Votre évasion dans le désert marocain<br>
            📞 +33 X XX XX XX XX | ✉️ contact@campcameleonx.com</p>
        </div>
    </div>
</body>
</html>