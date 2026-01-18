<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Role;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Delete;
use App\State\PermissionCollectionProvider;
use App\State\PermissionProcessor;

#[ApiResource(
    operations: [
        // Route grouped
        new GetCollection(
            uriTemplate: '/admin/permissions/grouped',
            provider: PermissionCollectionProvider::class,
        ),
        // Routes standard API Platform
        new Get(),
        new GetCollection(),
        new Post(
            processor: PermissionProcessor::class,
        ),
        new Put(
            processor: PermissionProcessor::class,
        ),
        new Delete(
            processor: PermissionProcessor::class,
        )
    ]
)]
class Permission extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'action',
        'category' // Nouveau champ
    ];

    // Ajouter un accessor pour la catégorie avec fallback automatique
    public function getCategoryAttribute($value)
    {
        // Si une catégorie est explicitement définie, l'utiliser
        if ($value) {
            return $value;
        }

        // Sinon, auto-déterminer la catégorie basée sur l'action
        return $this->determineCategory($this->action);
    }

    /**
     * Déterminer la catégorie d'une permission basée sur son action
     * (même logique que dans PermissionCollectionProvider)
     */
    private function determineCategory(string $action): string
    {
        $categories = [
            'system' => ['system-admin', 'admin-access', 'maintenance-mode', 'clear-cache', 'view-logs'],
            'users' => ['users-read', 'users-create', 'users-update', 'users-delete', 'roles-manage'],
            'accommodations' => ['accommodations-read', 'accommodations-manage', 'rooms-status'],
            'activities' => ['activities-read', 'activities-manage'],
            'bookings' => ['bookings-read-all', 'bookings-create', 'bookings-update', 'bookings-cancel', 'bookings-confirm', 'planning-manage'],
            'reception' => ['checkin', 'checkout', 'arrivals-today', 'departures-today', 'keys-manage'],
            'customers' => ['customers-read', 'customers-create', 'customers-update', 'customers-history'],
            'restaurant' => ['menus-read', 'menus-manage', 'dishes-manage', 'orders-take', 'orders-manage'],
            'finance' => ['payments-read', 'payments-collect', 'invoicing-manage', 'finance-stats'],
            'analytics' => ['dashboard-view', 'occupancy-stats', 'revenue-reports', 'data-export'],
            'communication' => ['messages-customers', 'notifications-team']
        ];

        foreach ($categories as $category => $actions) {
            if (in_array($action, $actions)) {
                return $category;
            }
        }

        return 'other';
    }

    // ===========================
    // RELATIONS (existantes)
    // ===========================
    public function roles()
    {
        return $this->belongsToMany(Role::class, 'permission_role');
    }

    // ===========================
    //  BUSINESS LOGIC pour le processor
    // ===========================

    /**
     * Vérifier si la permission peut être supprimée
     */
    public function canBeDeleted(): bool
    {
        return $this->roles()->count() === 0;
    }

    /**
     * Vérifier si c'est une permission critique
     */
    public function isCritical(): bool
    {
        $criticalActions = [
            'system-admin',
            'delete-users',
            'manage-permissions',
            'manage-roles',
            'admin-access',
            'super-admin'
        ];

        return in_array($this->action, $criticalActions);
    }

    /**
     * Normaliser l'action
     */
    public static function normalizeAction(string $action): string
    {
        return strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '-', $action), '-'));
    }

    /**
     * Générer le nom depuis l'action
     */
    public static function generateNameFromAction(string $action): string
    {
        return ucwords(str_replace('-', ' ', $action));
    }
}
