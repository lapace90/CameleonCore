<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Erreur de paiement - CampCameleonX</title>
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
        .error-box { 
            background: white; 
            border-radius: 10px; 
            padding: 40px 30px; 
            text-align: center;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .error-icon {
            font-size: 4rem;
            color: #dc3545;
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
        .button.danger {
            background: #dc3545;
        }
        .button.danger:hover {
            background: #c82333;
        }
        .error-details {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
            border-radius: 8px;
            padding: 15px;
            margin: 20px 0;
            font-size: 0.9rem;
        }
        .help-section {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin: 25px 0;
            text-align: left;
        }
    </style>
</head>
<body>
    <div class="error-box">
        <span class="error-icon">❌</span>
        <h1>Erreur de paiement</h1>
        <p>Une erreur s'est produite lors du traitement de votre paiement.</p>
        
        @if(isset($message))
        <div class="error-details">
            <strong>Détail :</strong> {{ $message }}
        </div>
        @endif

        <div class="help-section">
            <h3>💡 Que faire ?</h3>
            <ul>
                <li><strong>Vérifiez votre carte bancaire</strong> (solde, limite, date d'expiration)</li>
                <li><strong>Réessayez</strong> dans quelques minutes</li>
                <li><strong>Contactez votre banque</strong> si le problème persiste</li>
                <li><strong>Contactez-nous</strong> pour une assistance personnalisée</li>
            </ul>
        </div>

        <div style="margin-top: 30px;">
            <a href="{{ env('APP_FRONTEND_URL', '/') }}" class="button">
                🏠 Retour à l'accueil
            </a>
            <a href="mailto:contact@campcameleonx.com?subject=Problème paiement" class="button danger">
                🆘 Signaler le problème
            </a>
        </div>

        <div style="color: #6c757d; font-size: 0.9rem; margin-top: 25px;">
            <p><strong>Support technique :</strong><br>
            📞 +33 X XX XX XX XX | ✉️ contact@campcameleonx.com<br>
            Disponible 7j/7 pour vous aider</p>
        </div>
    </div>
</body>
</html>