<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /**
     * Un utilisateur peut mettre à jour SON profil,
     * ou n'importe qui s'il a la capacité "manage users".
     */
    public function update(User $auth, User $target): bool
    {
        if ($auth->id === $target->id) {
            return true;
        }
        // ton helper/méthode RBAC
        return method_exists($auth, 'canManageUsers') ? $auth->canManageUsers() : false;
    }
}
