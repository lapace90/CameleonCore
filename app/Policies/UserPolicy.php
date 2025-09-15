<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Support\Facades\Log;

class UserPolicy
{
    public function update(User $auth, User $target): bool
    {
        if ($auth->id === $target->id) {
            Log::info('🔍 UserPolicy - Auto-édition autorisée');
            return true;
        }
        
        $canManage = method_exists($auth, 'canManageUsers') ? $auth->canManageUsers() : false;
        Log::info('🔍 UserPolicy - Gestion utilisateurs', ['can_manage' => $canManage]);
        
        return $canManage;
    }
}