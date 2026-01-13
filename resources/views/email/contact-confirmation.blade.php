<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <title>Message bien reçu</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #c17c4a; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
        .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 8px 8px; }
        .success-box { background: #d4edda; border: 1px solid #c3e6cb; padding: 20px; border-radius: 5px; margin: 20px 0; text-align: center; }
        .footer { text-align: center; margin-top: 30px; font-size: 12px; color: #666; }
    </style>
</head>
<body>
    <div class="header">
        <h1>🦎 CampCameleonX</h1>
        <p>Confirmation de réception</p>
    </div>
    
    <div class="content">
        <h2>Bonjour {{ $fullName }} !</h2>
        
        <div class="success-box">
            <h3 style="color: #155724; margin: 0;"><i class="fas fa-check-circle" style="padding: .5rem;"></i> Message bien reçu</h3>
        </div>
        
        <p>Nous avons bien reçu votre message et nous vous en remercions.</p>
        
        <p>Notre équipe vous répondra dans les plus brefs délais, généralement sous 24h.</p>
        
        <p>En attendant, n'hésitez pas à consulter notre site pour découvrir nos offres et activités :</p>
        
        <p style="text-align: center;">
            <a href="{{ env('APP_FRONTEND_URL', 'http://localhost:5173') }}" 
               style="display: inline-block; background: #c17c4a; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; margin: 10px 0;">
                Visiter notre site
            </a>
        </p>
        
        <div class="footer">
            <p>🦎 CampCameleonX - Votre évasion dans le désert marocain<br>
            <i class="fas fa-phone"  style="padding: .5rem;"></i> +212 6 12 34 56 78 | <i class="fas fa-envelope"></i>contact@campcameleonx.com<br>
            Route du Sahara, Merzouga, Maroc</p>
        </div>
    </div>
</body>
</html>