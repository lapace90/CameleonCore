<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <title>Facture {{ $invoice_number }}</title>
    <style>
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }

        .email-container {
            max-width: 600px;
            margin: 20px auto;
            background: #ffffff;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .header {
            background: linear-gradient(135deg, #2c5f2d 0%, #3a7c3b 100%);
            color: white;
            padding: 40px 30px;
            text-align: center;
        }

        .header h1 {
            margin: 0;
            font-size: 28px;
            font-weight: 600;
        }

        .header p {
            margin: 10px 0 0;
            font-size: 14px;
            opacity: 0.9;
        }

        .content {
            padding: 40px 30px;
        }

        .greeting {
            font-size: 18px;
            color: #2c5f2d;
            margin-bottom: 20px;
        }

        .message {
            font-size: 15px;
            color: #555;
            margin-bottom: 30px;
            line-height: 1.8;
        }

        .invoice-summary {
            background: #f8f9fa;
            border-left: 4px solid #2c5f2d;
            padding: 20px;
            margin: 30px 0;
            border-radius: 5px;
        }

        .invoice-summary h2 {
            margin: 0 0 15px;
            font-size: 16px;
            color: #2c5f2d;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #e9ecef;
        }

        .summary-row:last-child {
            border-bottom: none;
        }

        .summary-label {
            font-size: 14px;
            color: #666;
        }

        .summary-value {
            font-size: 14px;
            font-weight: 600;
            color: #333;
        }

        .total-row {
            background: #2c5f2d;
            color: white;
            margin: 15px -20px -20px;
            padding: 15px 20px;
            border-radius: 0 0 5px 5px;
        }

        .total-row .summary-label,
        .total-row .summary-value {
            color: white;
            font-size: 16px;
        }

        .reservation-info {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin: 20px 0;
            border-radius: 5px;
        }

        .reservation-info h3 {
            margin: 0 0 10px;
            font-size: 14px;
            color: #856404;
        }

        .reservation-info p {
            margin: 5px 0;
            font-size: 13px;
            color: #856404;
        }

        .cta-button {
            display: inline-block;
            background: #2c5f2d;
            color: white;
            padding: 15px 40px;
            text-decoration: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: 600;
            margin: 20px 0;
            transition: background 0.3s;
        }

        .cta-button:hover {
            background: #1f4621;
        }

        .footer {
            background: #f8f9fa;
            padding: 30px;
            text-align: center;
            border-top: 1px solid #e9ecef;
        }

        .footer p {
            margin: 5px 0;
            font-size: 13px;
            color: #6c757d;
        }

        .footer a {
            color: #2c5f2d;
            text-decoration: none;
        }

        .status-badge {
            display: inline-block;
            padding: 6px 15px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            margin-top: 10px;
        }

        .status-paid {
            background: #d4edda;
            color: #155724;
        }

        .status-unpaid {
            background: #fff3cd;
            color: #856404;
        }

        .status-overdue {
            background: #f8d7da;
            color: #721c24;
        }

        .attachment-notice {
            background: #d1ecf1;
            border-left: 4px solid #0c5460;
            padding: 15px;
            margin: 20px 0;
            border-radius: 5px;
        }

        .attachment-notice p {
            margin: 0;
            font-size: 13px;
            color: #0c5460;
        }

        .help-section {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e9ecef;
        }

        .help-section h3 {
            font-size: 14px;
            color: #2c5f2d;
            margin-bottom: 10px;
        }

        .help-section p {
            font-size: 13px;
            color: #666;
            margin: 5px 0;
        }
    </style>
</head>

<body>
    <div class="email-container">
        <!-- Header -->
        <div class="header">
            <h1><i class="fas fa-file-lines"></i> Votre Facture CampCameleonX</h1>
            <p>{{ $invoice_number }}</p>
        </div>

        <!-- Content -->
        <div class="content">
            <div class="greeting">
                Bonjour {{ $customer['name'] }} {{ $customer['last_name'] ?? '' }},
            </div>

            <div class="message">
                <p>Nous vous remercions pour votre confiance et votre séjour à CampCameleonX.</p>
                <p>Veuillez trouver ci-joint votre facture détaillée.</p>
            </div>

            <!-- Attachment Notice -->
            <div class="attachment-notice">
                <p><i class="fa-solid fa-paperclip" style="padding: .5rem;"></i> <strong>Pièce jointe :</strong> Votre facture au format PDF est jointe à cet email.</p>
            </div>
            <!-- Download Button -->
            <div style="text-align: center; margin: 30px 0;">
                <a href="{{ config('app.url') }}/api/invoices/{{ $invoice_number }}/download" class="cta-button">
                    <i class="fas fa-download"></i> Télécharger la facture PDF
                </a>
            </div>

            <!-- Invoice Summary -->
            <div class="invoice-summary">
                <h2>Récapitulatif de la facture</h2>

                <div class="summary-row">
                    <span class="summary-label">Numéro de facture</span>
                    <span class="summary-value">{{ $invoice_number }}</span>
                </div>

                <div class="summary-row">
                    <span class="summary-label">Date d'émission</span>
                    <span class="summary-value">{{ $dates['issue_date'] }}</span>
                </div>

                <div class="summary-row">
                    <span class="summary-label">Date d'échéance</span>
                    <span class="summary-value">{{ $dates['due_date'] }}</span>
                </div>

                <div class="summary-row">
                    <span class="summary-label">Statut</span>
                    <span class="summary-value">
                        @if($status['code'] === 'paid')
                        <span class="status-badge status-paid">✓ Payée</span>
                        @elseif($status['code'] === 'overdue')
                        <span class="status-badge status-overdue">! En retard</span>
                        @else
                        <span class="status-badge status-unpaid">En attente</span>
                        @endif
                    </span>
                </div>

                <div class="total-row">
                    <div class="summary-row">
                        <span class="summary-label">MONTANT TOTAL</span>
                        <span class="summary-value">{{ $amount['formatted'] }}</span>
                    </div>
                </div>
            </div>

            <!-- Reservation Info (if available) -->
            @if($reservation)
            <div class="reservation-info">
                <h3><i class="fas fa-campground" style="padding: .5rem;"></i> Détails de votre séjour</h3>
                <p><strong>Hébergement :</strong> {{ $reservation['product'] }}</p>
                <p><strong>Arrivée :</strong> {{ $reservation['checkin'] }}</p>
                <p><strong>Départ :</strong> {{ $reservation['checkout'] }}</p>
            </div>
            @endif

            <!-- Help Section -->
            <div class="help-section">
                <h3>Besoin d'aide ?</h3>
                <p>Si vous avez des questions concernant cette facture, n'hésitez pas à nous contacter :</p>
                <p><i class="fas fa-envelope" style="padding: .5rem;"></i> Email : contact@campcameleonx.com</p>
                <p><i class="fas fa-phone" style="padding: .5rem;"></i> Téléphone : +212 XXX XXX XXX</p>
            </div>

            <div class="message" style="margin-top: 30px;">
                <p>Nous espérons vous revoir très bientôt dans le désert marocain !</p>
                <p style="color: #2c5f2d; font-weight: 600;">À bientôt,<br>L'équipe CampCameleonX 🦎</p>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p><strong>🦎 CampCameleonX</strong></p>
            <p>Désert du Maroc | Merzouga 52202</p>
            <p>
                <a href="mailto:contact@campcameleonx.com">contact@campcameleonx.com</a> |
                <a href="https://www.campcameleonx.com">www.campcameleonx.com</a>
            </p>
            <p style="margin-top: 15px; font-size: 11px; color: #999;">
                Cet email a été envoyé automatiquement. Merci de ne pas y répondre directement.
            </p>
        </div>
    </div>
</body>

</html>