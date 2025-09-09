<?php
// app/Models/Permission.php - Version CRUD Laravel + API Platform

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
        // ✅ Route existante - grouped
        new GetCollection(
            uriTemplate: '/admin/permissions/grouped',
            provider: PermissionCollectionProvider::class,
        ),
        // ✅ Routes standard API Platform
        new Get(),
        new GetCollection(),
        // ✅ CRUD à ajouter
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
    ];

    // ===========================
    // RELATIONS (existantes)
    // ===========================
    public function roles()
    {
        return $this->belongsToMany(Role::class, 'permission_role');
    }

    // ===========================
    // 🧠 BUSINESS LOGIC pour le processor
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
            'system-admin', 'delete-users', 'manage-permissions', 
            'manage-roles', 'admin-access', 'super-admin'
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