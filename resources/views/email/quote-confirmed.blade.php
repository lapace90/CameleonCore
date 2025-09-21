<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Votre devis CampCameleonX</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: linear-gradient(135deg, #c17c4a 0%, #d6b190 100%); color: white; padding: 30px; text-align: center; border-radius: 8px 8px 0 0; }
        .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 8px 8px; }
        .product-section { background: white; padding: 20px; margin: 15px 0; border-radius: 5px; border-left: 4px solid #c17c4a; }
        .total-section { background: #c17c4a; color: white; padding: 20px; margin: 20px 0; border-radius: 5px; text-align: center; }
        .contact-section { background: #e9ecef; padding: 20px; border-radius: 5px; margin: 20px 0; }
        .button { display: inline-block; background: #28a745; color: white; padding: 15px 30px; text-decoration: none; border-radius: 5px; margin: 10px; font-weight: bold; }
    </style>
</head>
<body>
    <div class="header">
        <h1>🏕️ CampCameleonX</h1>
        <h2>Votre devis personnalisé</h2>
        <p>Référence : <strong>{{ $quote->reference }}</strong></p>
    </div>
    
    <div class="content">
        <h2>Bonjour {{ $quote->customer_name }} !</h2>
        
        <p>Voici votre devis détaillé pour votre séjour du <strong>{{ $quote->checkin_date->format('d/m/Y') }}</strong> au <strong>{{ $quote->checkout_date->format('d/m/Y') }}</strong> pour <strong>{{ $quote->guests }} personne(s)</strong>.</p>
        
        @php
            $products = $quote->selected_products;
            $activities = collect($products)->where('type', 'activity');
            $menus = collect($products)->where('type', 'menu');
            $rooms = collect($products)->where('type', 'room');
        @endphp
        
        @if($activities->isNotEmpty())
        <div class="product-section">
            <h3>🥾 Activités sélectionnées</h3>
            <ul>
                @foreach($activities as $activity)
                <li>Activité #{{ $activity['id'] }}</li>
                @endforeach
            </ul>
        </div>
        @endif
        
        @if($menus->isNotEmpty())
        <div class="product-section">
            <h3>🍽️ Menus sélectionnés</h3>
            <ul>
                @foreach($menus as $menu)
                <li>Menu #{{ $menu['id'] }}</li>
                @endforeach
            </ul>
        </div>
        @endif
        
        @if($rooms->isNotEmpty())
        <div class="product-section">
            <h3>🏠 Hébergement sélectionné</h3>
            <ul>
                @foreach($rooms as $room)
                <li>Hébergement #{{ $room['id'] }}</li>
                @endforeach
            </ul>
        </div>
        @endif
        
        <div class="total-section">
            <h2>Total de votre séjour : {{ number_format($quote->total_amount, 2) }}€</h2>
            <p>Ce tarif est valable pendant 30 jours</p>
        </div>
        
        <div style="text-align: center;">
            <a href="tel:+33XXXXXXXXX" class="button">📞 Réserver par téléphone</a>
            <a href="mailto:contact@campcameleonx.com?subject=Réservation {{ $quote->reference }}" class="button">✉️ Réserver par email</a>
        </div>
        
        <div class="contact-section">
            <h3>📞 Pour réserver ou obtenir des informations :</h3>
            <ul>
                <li><strong>Téléphone :</strong> +33 X XX XX XX XX</li>
                <li><strong>Email :</strong> contact@campcameleonx.com</li>
                <li><strong>Horaires :</strong> Lundi-Vendredi 9h-18h, Samedi 9h-16h</li>
            </ul>
        </div>
        
        @if($quote->customer_message)
        <div class="product-section">
            <h3>💬 Votre message :</h3>
            <p><em>"{{ $quote->customer_message }}"</em></p>
        </div>
        @endif
        
        <p><small>Devis valable jusqu'au {{ $quote->expires_at->format('d/m/Y') }}.<br>
        Référence : {{ $quote->reference }}</small></p>
    </div>
</body>
</html>