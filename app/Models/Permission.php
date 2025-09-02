<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Role;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;

#[ApiResource(
    operations: [
        new Get(),
        new GetCollection()
    ]
)]

class Permission extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'name',
        'action',
    ];

    // ===========================
    // RELATIONS
    // ===========================

    /**
     * Les rôles qui ont cette permission
     */
    public function roles()
    {
        return $this->belongsToMany(Role::class, 'permission_role');
    }

    // ===========================
    // MÉTHODES UTILITAIRES
    // ===========================

    /**
     * Obtenir tous les utilisateurs ayant cette permission (via leurs rôles)
     */
    public function getUsersViaRoles()
    {
        $users = collect();
        
        foreach ($this->roles as $role) {
            // Utilisateurs avec ce rôle principal
            $users = $users->merge($role->users()->where('role_id', $role->id)->get());
            
            // Utilisateurs avec ce rôle additionnel
            $users = $users->merge($role->users);
        }
        
        return $users->unique('id');
    }

    /**
     * Vérifier si cette permission appartient à une catégorie
     */
    public function belongsToCategory(string $category): bool
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

        return isset($categories[$category]) && in_array($this->action, $categories[$category]);
    }
}