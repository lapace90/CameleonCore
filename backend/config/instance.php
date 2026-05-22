<?php

return [

    // Identité
    'name' => env('INSTANCE_NAME', 'Mon Activité'),
    'type' => env('INSTANCE_TYPE', 'hotel'),
    'logo' => env('INSTANCE_LOGO', '/images/logo.png'),
    'country' => env('INSTANCE_COUNTRY', 'FR'),

    'contact' => [
        'email' => env('INSTANCE_EMAIL'),
        'phone' => env('INSTANCE_PHONE'),
        'address' => env('INSTANCE_ADDRESS'),
        'siret' => env('INSTANCE_SIRET'),
    ],

    // Modules activés
    'modules' => [
        'booking' => env('MODULE_BOOKING', true),
        'invoicing' => env('MODULE_INVOICING', true),
        'calendar' => env('MODULE_CALENDAR', true),
        'reviews' => env('MODULE_REVIEWS', false),
        'analytics' => env('MODULE_ANALYTICS', false),
        'rbac' => env('MODULE_RBAC', false),
        'staff' => env('MODULE_STAFF', false),
        'quote_builder' => env('MODULE_QUOTE', false),
    ],

    // Productables activés
    'productables' => explode(',', env('INSTANCE_PRODUCTABLES', 'menu,dish')),

    // Features spécifiques
    'features' => [
        'deposit_payment' => env('FEATURE_DEPOSIT', true),
        'deposit_percentage' => env('FEATURE_DEPOSIT_PCT', 30),
        'seasonal_availability' => env('FEATURE_SEASONAL', true),
        'e_invoicing' => env('FEATURE_E_INVOICING', false),
        'guest_count' => env('FEATURE_GUEST_COUNT', true),
        'checkin_checkout' => env('FEATURE_CHECKIN', false),
    ],

    // Stripe
    'stripe' => [
        'mode' => env('STRIPE_MODE', 'deposit'),
    ],

    // FactPulse (facturation électronique)
    'factpulse' => [
        'enabled' => env('FACTPULSE_ENABLED', false),
        'api_key' => env('FACTPULSE_API_KEY'),
        'sandbox' => env('FACTPULSE_SANDBOX', true),
    ],

];