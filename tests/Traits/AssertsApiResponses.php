<?php
// tests/Traits/AssertsApiResponses.php

namespace Tests\Traits;

use Illuminate\Testing\TestResponse;

trait AssertsApiResponses
{
    /**
     * Assert que la réponse est une collection API Platform
     */
    protected function assertApiPlatformCollection(TestResponse $response, ?int $expectedCount = null): void
    {
        $response->assertStatus(200)
            ->assertJsonStructure([
                'hydra:member' => [],
                'hydra:totalItems'
            ]);

        if ($expectedCount !== null) {
            $response->assertJson(['hydra:totalItems' => $expectedCount]);
        }
    }

    /**
     * Assert qu'une réponse utilisateur contient tous les champs requis
     */
    protected function assertUserResponse(TestResponse $response): void
    {
        $response->assertJsonStructure([
            'id', 'name', 'email', 'status',
            'phone', 'address', 'city', 'postal_code', 'avatar',
            'role' => ['id', 'name', 'slug'],
            'additional_roles',
            'all_permissions',
            'last_login_at', 'last_login_ip',
            'is_admin', 'can_access_admin',
            'created_at', 'updated_at'
        ]);
    }

    /**
     * Assert qu'une réponse d'erreur de validation est correcte
     */
    protected function assertValidationError(TestResponse $response, array $expectedFields = []): void
    {
        $response->assertStatus(422);

        if (!empty($expectedFields)) {
            $response->assertJsonValidationErrors($expectedFields);
        }
    }

    /**
     * Assert qu'une réponse d'accès refusé est correcte
     */
    protected function assertAccessDenied(TestResponse $response): void
    {
        $this->assertContains($response->getStatusCode(), [401, 403]);
    }

    /**
     * Assert qu'un utilisateur a les bonnes permissions dans la réponse
     */
    protected function assertUserHasPermissions(TestResponse $response, array $expectedPermissions): void
    {
        $permissions = collect($response->json('all_permissions'))->pluck('action')->toArray();
        
        foreach ($expectedPermissions as $expectedPermission) {
            $this->assertContains($expectedPermission, $permissions, 
                "L'utilisateur devrait avoir la permission: {$expectedPermission}");
        }
    }

    /**
     * Assert qu'un utilisateur n'a pas certaines permissions
     */
    protected function assertUserDoesNotHavePermissions(TestResponse $response, array $forbiddenPermissions): void
    {
        $permissions = collect($response->json('all_permissions'))->pluck('action')->toArray();
        
        foreach ($forbiddenPermissions as $forbiddenPermission) {
            $this->assertNotContains($forbiddenPermission, $permissions,
                "L'utilisateur ne devrait PAS avoir la permission: {$forbiddenPermission}");
        }
    }

    /**
     * Assert qu'une réponse de produit contient les données polymorphes
     */
    protected function assertProductResponse(TestResponse $response): void
    {
        $response->assertJsonStructure([
            'id', 'name', 'description', 'price', 'formatted_price',
            'status', 'status_label', 'productable_type',
            'type_config', 'productable_detail'
        ]);
    }

    /**
     * Assert qu'une collection peut être paginée et triée
     */
    protected function assertPaginatedCollection(TestResponse $response): void
    {
        $response->assertJsonStructure([
            'hydra:member',
            'hydra:totalItems',
            'hydra:view' => [
                'hydra:first',
                'hydra:last'
            ]
        ]);
    }
}