<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <title>Nouvelle Réservation - CampCameleonX</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f8f9fa;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 12px 12px 0 0;
            text-align: center;
        }
        
        .header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 600;
        }
        
        .content {
            background: white;
            padding: 30px;
            border-radius: 0 0 12px 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .highlight-box {
            background: #e3f2fd;
            border-left: 4px solid #2196f3;
            padding: 20px;
            margin: 20px 0;
            border-radius: 0 8px 8px 0;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin: 20px 0;
        }
        
        .info-item {
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
            border: 1px solid #e9ecef;
        }
        
        .info-label {
            font-weight: 600;
            color: #495057;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 5px;
        }
        
        .info-value {
            font-size: 16px;
            color: #212529;
            font-weight: 500;
        }
        
        .amount {
            font-size: 24px;
            font-weight: 700;
            color: #28a745;
        }
        
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .status-confirmed {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .status-pending {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }
        
        .actions {
            text-align: center;
            margin: 30px 0;
        }
        
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            margin: 0 10px;
        }
        
        .btn:hover {
            background: #0056b3;
        }
        
        .btn-secondary {
            background: #6c757d;
        }
        
        .btn-secondary:hover {
            background: #545b62;
        }
        
        .footer {
            text-align: center;
            margin-top: 30px;
            padding: 20px;
            color: #6c757d;
            font-size: 14px;
            border-top: 1px solid #e9ecef;
        }
        
        @media (max-width: 600px) {
            .info-grid {
                grid-template-columns: 1fr;
            }
            
            .actions .btn {
                display: block;
                margin: 10px 0;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1><i class="fas fa-clipboard-list" style="padding: .5rem;"></i> Nouvelle Réservation Reçue !</h1>
        <p>Une réservation vient d'être confirmée et payée</p>
    </div>
    
    <div class="content">
        <div class="highlight-box">
            <h3 style="margin-top: 0; color: #1976d2;"><i class="fas fa-clipboard-list" style="padding: .5rem;"></i> Réservation #{{ $reservation->id }}</h3>
            <p><strong>Référence devis :</strong> {{ $reservation->quote_reference ?? 'N/A' }}</p>
            <p><strong>Numéro de facture :</strong> {{ $reservation->invoice_number }}</p>
        </div>
        
        <h3><i class="fas fa-user" style="padding: .5rem;"></i> Informations Client</h3>
        <div class="info-grid">
            <div class="info-item">
                <div class="info-label">Nom</div>
                <div class="info-value">{{ $customer->name ?? 'Non renseigné' }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">Email</div>
                <div class="info-value">{{ $customer->email ?? 'Non renseigné' }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">Téléphone</div>
                <div class="info-value">{{ $customer->phone ?? 'Non renseigné' }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">Statut</div>
                <div class="info-value">
                    <span class="status-badge status-{{ $reservation->status }}">
                        {{ ucfirst($reservation->status) }}
                    </span>
                </div>
            </div>
        </div>
        
        <h3><i class="fas fa-campground" style="padding: .5rem;"> Détails du Séjour</h3>
        <div class="info-grid">
            <div class="info-item">
                <div class="info-label">Arrivée</div>
                <div class="info-value">{{ $reservation->checkin?->format('d/m/Y à H:i') ?? 'Non définie' }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">Départ</div>
                <div class="info-value">{{ $reservation->checkout?->format('d/m/Y à H:i') ?? 'Non définie' }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">Durée</div>
                <div class="info-value">
                    @if($reservation->checkin && $reservation->checkout)
                        {{ $reservation->checkin->diffInDays($reservation->checkout) }} nuit(s)
                    @else
                        Non calculée
                    @endif
                </div>
            </div>
            <div class="info-item">
                <div class="info-label">Personnes</div>
                <div class="info-value">
                    {{ $reservation->number_of_adults ?? 0 }} adulte(s), 
                    {{ $reservation->number_of_children ?? 0 }} enfant(s)
                </div>
            </div>
        </div>
        
        <h3><i class="fas fa-hand-holding-dollar" style="padding: .5rem;"></i> Informations Financières</h3>
        <div class="highlight-box" style="text-align: center;">
            <div class="info-label">Montant Total Payé</div>
            <div class="amount">{{ number_format($reservation->amount, 2) }} €</div>
            <div style="margin-top: 10px;">
                <span class="status-badge status-confirmed"><i class="fas fa-check-circle" style="padding: .5rem;"></i> {{ ucfirst($reservation->payment_status) }}</span>
            </div>
        </div>
        
        @if($reservation->comment)
        <h3><i class="fas fa-comment" style="padding: .5rem;"></i> Commentaire Client</h3>
        <div class="info-item">
            <p style="margin: 0; font-style: italic;">"{{ $reservation->comment }}"</p>
        </div>
        @endif
        
        <div class="actions">
            <a href="{{ config('app.url') }}/admin/reservations/{{ $reservation->id }}" class="btn">
                <i class="fas fa-clipboard-list" style="padding: .5rem;"></i> Voir la Réservation
            </a>
            <a href="{{ config('app.url') }}/admin/calendar" class="btn btn-secondary">
                <i class="fas fa-calendar-alt" style="padding: .5rem;"></i> Ouvrir le Calendrier
            </a>
        </div>
        
        <div style="margin-top: 30px; padding: 15px; background: #f8f9fa; border-radius: 8px; font-size: 14px;">
            <strong><i class="fas fa-file-lines" style="padding: .5rem;"></i> Actions recommandées :</strong>
            <ul style="margin: 10px 0; padding-left: 20px;">
                <li>Vérifier la disponibilité des équipements</li>
                <li>Préparer les clés et l'accueil</li>
                <li>Ajouter au planning d'équipe si nécessaire</li>
                <li>Contacter le client si questions spécifiques</li>
            </ul>
        </div>
    </div>
    
    <div class="footer">
        <p><strong>🦎 CampCameleonX</strong> - Administration</p>
        <p>Reçu le {{ now()->format('d/m/Y à H:i') }}</p>
        <p><i class="fas fa-user" style="padding: .5rem;"></i> Destinataire : {{ $admin->name }} ({{ $admin->email }})</p>
    </div>
</body>
</html>