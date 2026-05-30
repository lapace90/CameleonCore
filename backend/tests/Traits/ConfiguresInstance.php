<?php

namespace Tests\Traits;

trait ConfiguresInstance
{
    /**
     * Configure l'instance avec tous les modules et productables activés.
     * Utilisé par défaut dans les tests pour ne rien bloquer.
     */
    protected function withFullInstance(): void
    {
        config([
            'instance.name' => 'Test Instance',
            'instance.type' => 'hotel',
            'instance.country' => 'FR',
            'instance.modules' => [
                'booking' => true,
                'invoicing' => true,
                'calendar' => true,
                'reviews' => true,
                'analytics' => true,
                'rbac' => true,
                'staff' => true,
                'quote_builder' => true,
            ],
            'instance.productables' => ['room', 'activity', 'menu', 'dish', 'ingredient'],
            'instance.features' => [
                'deposit_payment' => true,
                'deposit_percentage' => 30,
                'seasonal_availability' => true,
                'e_invoicing' => false,
                'guest_count' => true,
                'checkin_checkout' => true,
            ],
            'instance.factpulse' => [
                'enabled' => false,
                'api_key' => null,
                'sandbox' => true,
            ],
        ]);
    }

    /**
     * Configure une instance traiteur (comme Lamine).
     */
    protected function withTraiteurInstance(): void
    {
        config([
            'instance.name' => 'Test Traiteur',
            'instance.type' => 'traiteur',
            'instance.country' => 'FR',
            'instance.modules' => [
                'booking' => true,
                'invoicing' => true,
                'calendar' => true,
                'reviews' => false,
                'analytics' => false,
                'rbac' => false,
                'staff' => false,
                'quote_builder' => false,
            ],
            'instance.productables' => ['menu', 'dish'],
            'instance.features' => [
                'deposit_payment' => true,
                'deposit_percentage' => 30,
                'seasonal_availability' => true,
                'e_invoicing' => false,
                'guest_count' => true,
                'checkin_checkout' => false,
            ],
        ]);
    }

    /**
     * Configure uniquement les productables actifs.
     */
    protected function withProductables(array $types): void
    {
        config(['instance.productables' => $types]);
    }

    /**
     * Active/désactive un module.
     */
    protected function withModule(string $module, bool $enabled = true): void
    {
        config(["instance.modules.{$module}" => $enabled]);
    }

    /**
     * Active/désactive une feature.
     */
    protected function withFeature(string $feature, $value = true): void
    {
        config(["instance.features.{$feature}" => $value]);
    }
}