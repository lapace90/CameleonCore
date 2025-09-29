<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Facture {{ $invoice->invoice_number }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 12px;
            color: #333;
            line-height: 1.6;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 40px;
        }
        .header {
            display: table;
            width: 100%;
            margin-bottom: 40px;
            border-bottom: 3px solid #2c5f2d;
            padding-bottom: 20px;
        }
        .header-left {
            display: table-cell;
            width: 50%;
            vertical-align: top;
        }
        .header-right {
            display: table-cell;
            width: 50%;
            text-align: right;
            vertical-align: top;
        }
        .company-name {
            font-size: 24px;
            font-weight: bold;
            color: #2c5f2d;
            margin-bottom: 10px;
        }
        .company-info {
            font-size: 10px;
            color: #666;
            line-height: 1.4;
        }
        .invoice-title {
            font-size: 32px;
            font-weight: bold;
            color: #2c5f2d;
            margin-bottom: 5px;
        }
        .invoice-number {
            font-size: 14px;
            color: #666;
        }
        .info-section {
            display: table;
            width: 100%;
            margin-bottom: 30px;
        }
        .info-left, .info-right {
            display: table-cell;
            width: 50%;
            vertical-align: top;
        }
        .info-box {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-right: 10px;
        }
        .info-box h3 {
            font-size: 12px;
            color: #2c5f2d;
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .info-box p {
            margin: 5px 0;
            font-size: 11px;
        }
        .dates-box {
            background: #fff3cd;
            padding: 15px;
            border-radius: 5px;
            border-left: 4px solid #ffc107;
        }
        .dates-box p {
            margin: 5px 0;
            font-size: 11px;
        }
        .dates-box strong {
            color: #856404;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        thead {
            background: #2c5f2d;
            color: white;
        }
        thead th {
            padding: 12px;
            text-align: left;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        tbody td {
            padding: 12px;
            border-bottom: 1px solid #e9ecef;
            font-size: 11px;
        }
        tbody tr:last-child td {
            border-bottom: 2px solid #2c5f2d;
        }
        .item-description {
            font-weight: bold;
            color: #2c5f2d;
        }
        .item-details {
            font-size: 10px;
            color: #666;
            margin-top: 5px;
        }
        .text-right {
            text-align: right;
        }
        .totals {
            margin-left: auto;
            width: 300px;
        }
        .totals-row {
            display: table;
            width: 100%;
            padding: 8px 0;
        }
        .totals-label {
            display: table-cell;
            font-size: 12px;
            color: #666;
        }
        .totals-value {
            display: table-cell;
            text-align: right;
            font-size: 12px;
            font-weight: bold;
        }
        .total-final {
            background: #2c5f2d;
            color: white;
            padding: 15px;
            border-radius: 5px;
            margin-top: 10px;
        }
        .total-final .totals-label {
            font-size: 14px;
            color: white;
        }
        .total-final .totals-value {
            font-size: 20px;
            color: white;
        }
        .notes {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-top: 30px;
            border-left: 4px solid #2c5f2d;
        }
        .notes h3 {
            font-size: 12px;
            color: #2c5f2d;
            margin-bottom: 10px;
        }
        .notes p {
            font-size: 10px;
            color: #666;
            line-height: 1.5;
        }
        .status-badge {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
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
        .footer {
            margin-top: 50px;
            padding-top: 20px;
            border-top: 1px solid #dee2e6;
            text-align: center;
            font-size: 10px;
            color: #6c757d;
        }
        .footer p {
            margin: 5px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="header-left">
                <div class="company-name">{{ $company['name'] }}</div>
                <div class="company-info">
                    {{ $company['address'] }}<br>
                    {{ $company['postal_code'] }} {{ $company['city'] }}, {{ $company['country'] }}<br>
                    Tél : {{ $company['phone'] }}<br>
                    Email : {{ $company['email'] }}<br>
                    Web : {{ $company['website'] }}
                </div>
            </div>
            <div class="header-right">
                <div class="invoice-title">FACTURE</div>
                <div class="invoice-number">{{ $invoice->invoice_number }}</div>
                <div style="margin-top: 10px;">
                    @if($invoice->status === 'paid')
                        <span class="status-badge status-paid">✓ Payée</span>
                    @elseif($invoice->status === 'overdue')
                        <span class="status-badge status-overdue">! En retard</span>
                    @else
                        <span class="status-badge status-unpaid">En attente</span>
                    @endif
                </div>
            </div>
        </div>

        <!-- Client & Dates Info -->
        <div class="info-section">
            <div class="info-left">
                <div class="info-box">
                    <h3>Facturé à</h3>
                    <p><strong>{{ $customer->name }} {{ $customer->last_name ?? '' }}</strong></p>
                    @if($customer->address)
                        <p>{{ $customer->address }}</p>
                    @endif
                    @if($customer->email)
                        <p>Email : {{ $customer->email }}</p>
                    @endif
                    @if($customer->phone)
                        <p>Tél : {{ $customer->phone }}</p>
                    @endif
                </div>
            </div>
            <div class="info-right">
                <div class="dates-box">
                    <p><strong>Date d'émission :</strong> {{ $invoice->issue_date->format('d/m/Y') }}</p>
                    <p><strong>Date d'échéance :</strong> {{ $invoice->due_date->format('d/m/Y') }}</p>
                    @if($invoice->payment_date)
                        <p><strong>Date de paiement :</strong> {{ $invoice->payment_date->format('d/m/Y') }}</p>
                    @endif
                    @if($reservation)
                        <p style="margin-top: 10px; padding-top: 10px; border-top: 1px solid #ffc107;">
                            <strong>Séjour :</strong><br>
                            Du {{ $reservation->checkin->format('d/m/Y') }}<br>
                            Au {{ $reservation->checkout->format('d/m/Y') }}
                        </p>
                    @endif
                </div>
            </div>
        </div>

        <!-- Items Table -->
        <table>
            <thead>
                <tr>
                    <th>Description</th>
                    <th style="width: 80px;" class="text-right">Quantité</th>
                    <th style="width: 120px;" class="text-right">Prix unitaire</th>
                    <th style="width: 120px;" class="text-right">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($items as $item)
                <tr>
                    <td>
                        <div class="item-description">{{ $item['description'] }}</div>
                        @if(isset($item['details']) && $item['details'])
                            <div class="item-details">{{ $item['details'] }}</div>
                        @endif
                    </td>
                    <td class="text-right">{{ $item['quantity'] }}</td>
                    <td class="text-right">{{ number_format($item['unit_price'], 2, ',', ' ') }} €</td>
                    <td class="text-right">{{ number_format($item['total'], 2, ',', ' ') }} €</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Totals -->
        <div class="totals">
            <div class="totals-row">
                <div class="totals-label">Sous-total HT</div>
                <div class="totals-value">{{ number_format($invoice->amount, 2, ',', ' ') }} €</div>
            </div>
            <div class="totals-row total-final">
                <div class="totals-label">TOTAL TTC</div>
                <div class="totals-value">{{ number_format($invoice->amount, 2, ',', ' ') }} €</div>
            </div>
        </div>

        <!-- Notes -->
        @if($invoice->notes)
        <div class="notes">
            <h3>Notes</h3>
            <p>{{ $invoice->notes }}</p>
        </div>
        @endif

        <!-- Footer -->
        <div class="footer">
            <p><strong>{{ $company['name'] }}</strong></p>
            <p>SIRET : {{ $company['siret'] ?? 'XXX XXX XXX XXXXX' }} | TVA : {{ $company['tva'] ?? 'N/A' }}</p>
            <p>{{ $company['email'] }} | {{ $company['phone'] }} | {{ $company['website'] }}</p>
            <p style="margin-top: 15px; font-size: 9px;">
                Merci de votre confiance ! Pour toute question, n'hésitez pas à nous contacter.
            </p>
        </div>
    </div>
</body>
</html>