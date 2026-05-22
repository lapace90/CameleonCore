<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <title>Erreur validation - CampCameleonX</title>
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
        .error-box { 
            background: #f8d7da; 
            border: 1px solid #f5c6cb; 
            color: #721c24; 
            padding: 30px; 
            border-radius: 8px; 
            text-align: center; 
        }
        .button { 
            display: inline-block; 
            background: #c17c4a; 
            color: white; 
            padding: 12px 30px; 
            text-decoration: none; 
            border-radius: 5px; 
            margin: 20px 10px; 
        }
        .button:hover { 
            background: #a66a3e; 
        }
        .help-section {
            background: white;
            padding: 20px;
            border-radius: 5px;
            margin-top: 20px;
            text-align: left;
        }
    </style>
</head>
<body>
    <div class="error-box">
        <h1><i class="fas fa-times-circle" style="padding: .5rem;"></i> Validation impossible</h1>
        <p>{{ $message ?? 'Le lien de validation est invalide ou a expiré.' }}</p>
        
        <div style="margin-top: 30px;">
            <a href="mailto:contact@campcameleonx.com?subject=Problème validation devis" class="button">
                <i class="fas fa-envelope"  style="padding: .5rem;"></i>Nous contacter
            </a>
            <a href="{{ env('APP_FRONTEND_URL', '/') }}" class="button">
                <i class="fas fa-house" style="padding: .5rem;"> Retour à l'accueil
            </a>
        </div>
    </div>
    
    <div class="help-section">
        <h3><i class="fas fa-lightbulb" style="padding: .5rem;"> Que faire ?</h3>
        <ul>
            <li><strong>Lien expiré</strong> : Les liens de validation sont valables 48h. Vous pouvez refaire une demande de devis.</li>
            <li><strong>Email utilisé plusieurs fois</strong> : Vérifiez vos emails, vous avez peut-être déjà validé ce devis.</li>
            <li><strong>Problème technique</strong> : Contactez-nous directement par email ou téléphone.</li>
        </ul>
        
        <h3><i class="fas fa-phone" style="padding: .5rem;"> Contact</h3>
        <p>
            <strong>Email :</strong> contact@campcameleonx.com<br>
            <strong>Téléphone :</strong> +33 X XX XX XX XX<br>
            <strong>Horaires :</strong> Lundi-Vendredi 9h-18h
        </p>
    </div>
</body>
</html>