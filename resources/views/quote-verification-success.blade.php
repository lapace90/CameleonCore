<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email vérifié - CampCameleonX</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 50px auto; padding: 20px; }
        .success-box { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 30px; border-radius: 8px; text-align: center; }
        .button { display: inline-block; background: #28a745; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; margin: 20px 0; }
    </style>
</head>
<body>
    <div class="success-box">
        <h1>✅ Email vérifié avec succès !</h1>
        <p>Merci {{ $quote->customer_name }}, votre adresse email a été vérifiée.</p>
        <p>Votre devis détaillé <strong>{{ $quote->reference }}</strong> vous a été envoyé par email.</p>
        
        <a href="mailto:contact@campcameleonx.com" class="button">
            📧 Nous contacter
        </a>
        
        <p><small>Vous pouvez fermer cette page en toute sécurité.</small></p>
    </div>
</body>
</html>
