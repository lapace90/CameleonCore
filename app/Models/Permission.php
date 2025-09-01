<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Role;
use App\Models\User;
use ApiPlatform\Metadata\ApiResource;

#[ApiResource(
    normalizationContext: ['groups' => ['permission:read']]
)]

class Permission extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'action',
    ];

    // Si une permission appartient à plusieurs rôles
    public function roles()
    {
        return $this->belongsToMany(Role::class);
    }

    // Si une permission est attribuée directement à des utilisateurs
    public function users()
    {
        return $this->belongsToMany(User::class);
    }
}
