<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <title>Nouveau message de contact</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            background: #c17c4a;
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 8px 8px 0 0;
        }

        .content {
            background: #f9f9f9;
            padding: 30px;
            border-radius: 0 0 8px 8px;
        }

        .info-box {
            background: white;
            padding: 20px;
            border-radius: 5px;
            margin: 20px 0;
            border-left: 4px solid #c17c4a;
        }

        .message-box {
            background: white;
            padding: 20px;
            border-radius: 5px;
            margin: 20px 0;
        }

        .label {
            font-weight: bold;
            color: #c17c4a;
        }

        .footer {
            text-align: center;
            margin-top: 30px;
            font-size: 12px;
            color: #666;
        }
    </style>
</head>

<body>
    <div class="header">
        <h1>🏕️ CampCameleonX</h1>
        <p>Nouveau message de contact</p>
    </div>

    <div class="content">
        <h2>Nouveau message reçu</h2>

        <div class="info-box">
            <p><span class="label">De :</span> {{ $fullName }}</p>
            <p><span class="label">Email :</span> <a href="mailto:{{ $email }}">{{ $email }}</a></p>
            <p><span class="label">Téléphone :</span> {{ $phone }}</p>
            <p><span class="label">Sujet :</span> {{ $subject }}</p>
            @if($newsletter)
            <p><span class="label"><i class="fas fa-envelope"  style="padding: .5rem;"></i> Newsletter :</span> Souhaite recevoir la newsletter</p>
            @endif
        </div>

        <div class="message-box">
            <p><span class="label">Message :</span></p>
            <p>{{ $messageContent }}</p>
        </div>

        <p><small><i class="fas fa-lightbulb"  style="padding: .5rem;"></i> Répondez directement en cliquant sur "Répondre" dans votre client email.</small></p>

        <div class="footer">
            <p>CampCameleonX - Système de contact<br>
                <i class="fas fa-envelope"  style="padding: .5rem;"></i> Ce message a été envoyé depuis le formulaire de contact du site</p>
        </div>
    </div>
</body>

</html>