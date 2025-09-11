<?php
// app/State/RoleProcessor.php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\State\ProcessorInterface;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class RoleProcessor implements ProcessorInterface
{
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        // 🔐 Auth Sanctum
        $currentUser = auth('sanctum')->user();
        if (!$currentUser) {
            throw new UnauthorizedHttpException('Bearer', 'Token d\'authentification requis');
        }

        // 🔐 Autorisation
        if (!$this->canManageRoles($currentUser)) {
            throw new AccessDeniedHttpException('Permissions insuffisantes pour gérer les rôles');
        }

        try {
            switch (true) {
                case $operation instanceof Post:
                    return $this->createRole($data, $context, $currentUser);

                case $operation instanceof Put:
                case $operation instanceof Patch:
                    return $this->updateRole($data, (int) ($uriVariables['id'] ?? 0), $context, $currentUser);

                case $operation instanceof Delete:
                    $this->deleteRole((int) ($uriVariables['id'] ?? 0), $currentUser);
                    return null;

                default:
                    throw new \InvalidArgumentException('Opération non supportée: ' . get_class($operation));
            }
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Erreur RoleProcessor', [
                'operation' => get_class($operation),
                'user_id' => $currentUser->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    // ===========================
    // CRUD
    // ===========================

    private function createRole(mixed $data, array $context, $currentUser): Role
    {
        $payload = $this->getDataFromRequest($context);

        // --- NORMALISATION SLUG (si présent)
        if (array_key_exists('slug', $payload)) {
            if ($payload['slug'] === null || $payload['slug'] === '') {
                // si fourni vide/null, on génère depuis name
                $payload['slug'] = Role::generateSlugFromName($payload['name'] ?? '');
            } else {
                $payload['slug'] = Role::normalizeSlug((string)$payload['slug']);
            }
        } else {
            // champ absent => on peut auto-générer si tu le souhaites
            $payload['slug'] = Role::generateSlugFromName($payload['name'] ?? '');
        }

        // --- CONVERSION PERMISSIONS: IRIs / objets / ints -> IDs
        $permIds = $this->normalizePermissionIds($payload['permissions'] ?? []);

        // --- VALIDATION (note: plus de "permissions.* integer")
        $validator = Validator::make($payload, [
            'name' => 'required|string|max:255|unique:roles,name',
            'description' => 'nullable|string|max:1000',
            'slug' => 'required|string|max:100|regex:/^[a-z0-9-]+$/|unique:roles,slug',
            'permissions' => 'nullable|array',
        ]);
        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        // --- VALIDATION existence permissions après conversion
        if (!empty($permIds)) {
            $count = Permission::whereIn('id', $permIds)->count();
            if ($count !== count($permIds)) {
                throw ValidationException::withMessages([
                    'permissions' => ['Une ou plusieurs permissions sont invalides.']
                ]);
            }
        }

        // --- CREATE + SYNC (transaction)
        $role = DB::transaction(function () use ($payload, $permIds) {
            $role = Role::create([
                'name' => $payload['name'],
                'description' => $payload['description'] ?? null,
                'slug' => $payload['slug'],
            ]);
            if (!empty($permIds)) {
                $role->permissions()->sync($permIds);
            }
            return $role;
        });

        $role->load(['permissions', 'primaryUsers', 'users']);
        return $role;
    }

    private function updateRole(mixed $data, int $roleId, array $context, $currentUser): Role
    {
        $role = Role::find($roleId);
        if (!$role) {
            throw new NotFoundHttpException("Rôle avec l'ID {$roleId} non trouvé");
        }

        // récup body brut
        $payload = $this->getDataFromRequest($context);

        // rôles critiques: pas de changement de slug
        if ($role->isCritical() && array_key_exists('slug', $payload)) {
            unset($payload['slug']);
        }

        // normalisation slug si présent
        if (array_key_exists('slug', $payload)) {
            $payload['slug'] = ($payload['slug'] === null || $payload['slug'] === '')
                ? $role->slug // ou regénération si tu veux
                : Role::normalizeSlug((string)$payload['slug']);
        }

        // conversion permissions -> IDs
        $permIds = null;
        if (array_key_exists('permissions', $payload)) {
            $permIds = $this->normalizePermissionIds($payload['permissions'] ?? []);
        }

        // validation (sans "permissions.* integer")
        $validator = Validator::make($payload, [
            'name' => "required|string|max:255|unique:roles,name,{$roleId}",
            'description' => 'nullable|string|max:1000',
            'slug' => "nullable|string|max:100|regex:/^[a-z0-9-]+$/|unique:roles,slug,{$roleId}",
            'permissions' => 'nullable|array',
        ]);
        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        if (is_array($permIds)) {
            if (!empty($permIds)) {
                $count = Permission::whereIn('id', $permIds)->count();
                if ($count !== count($permIds)) {
                    throw ValidationException::withMessages([
                        'permissions' => ['Une ou plusieurs permissions sont invalides.']
                    ]);
                }
            }
        }

        DB::transaction(function () use ($role, $payload, $permIds) {
            $role->update([
                'name' => $payload['name'],
                'description' => array_key_exists('description', $payload) ? ($payload['description'] ?? null) : $role->description,
                'slug' => array_key_exists('slug', $payload) ? ($payload['slug'] ?? $role->slug) : $role->slug,
            ]);
            if (is_array($permIds)) {
                $role->permissions()->sync($permIds);
            }
        });

        $role->load(['permissions', 'primaryUsers', 'users']);
        return $role;
    }
    /**
     * Accepte:
     *  - int ou string numérique: 3 / "3"
     *  - IRI string: "/api/permissions/3"
     *  - objet: { id: 3 } (au cas où)
     */
    private function normalizePermissionIds($value): array
    {
        if (!is_array($value)) return [];
        $ids = [];
        foreach ($value as $v) {
            if (is_int($v) || (is_string($v) && ctype_digit($v))) {
                $ids[] = (int)$v;
                continue;
            }
            if (is_string($v)) {
                if (preg_match('#/api/permissions/(\d+)#', $v, $m)) {
                    $ids[] = (int)$m[1];
                    continue;
                }
            }
            if (is_array($v) && isset($v['id']) && (is_int($v['id']) || ctype_digit((string)$v['id']))) {
                $ids[] = (int)$v['id'];
                continue;
            }
        }
        return array_values(array_unique($ids));
    }

    private function deleteRole(int $roleId, $currentUser): void
    {
        $role = Role::find($roleId);
        if (!$role) {
            throw new NotFoundHttpException("Rôle avec l'ID {$roleId} non trouvé");
        }

        if ($role->isCritical()) {
            throw new AccessDeniedHttpException('Impossible de supprimer un rôle critique du système');
        }

        if (!$role->canBeDeleted()) {
            throw new AccessDeniedHttpException('Ce rôle ne peut pas être supprimé car il est assigné à des utilisateurs');
        }

        Log::info('Suppression rôle', [
            'role_id' => $role->id,
            'name' => $role->name,
            'deleted_by' => $currentUser->name,
        ]);

        $role->permissions()->detach();
        $role->delete();
    }

    // ===========================
    // UTILITAIRES
    // ===========================

    private function canManageRoles($user): bool
    {
        $adminSlugs = ['owner', 'super-admin', 'admin', 'system-admin'];
        $errors = [];

        // 1) Méthode isAdmin()
        if (method_exists($user, 'isAdmin')) {
            try {
                if ($user->isAdmin()) {
                    return true;
                }
            } catch (\Throwable $e) {
                $errors[] = 'isAdmin() a levé: ' . $e->getMessage();
            }
        }

        // 2) Rôle principal (users.role)
        if (method_exists($user, 'role')) {
            try {
                // essaye d’abord la propriété (si relation chargée), sinon requête
                $slug = $user->role->slug ?? ($user->role()->value('slug'));
                if ($slug && in_array($slug, $adminSlugs, true)) {
                    return true;
                }
            } catch (\Throwable $e) {
                $errors[] = 'role()->value("slug") a levé: ' . $e->getMessage();
            }
        }

        // 3) Rôles additionnels (pivot role_user)
        if (method_exists($user, 'roles')) {
            try {
                $slugs = $user->roles()->pluck('slug')->all();
                if (!empty(array_intersect($slugs, $adminSlugs))) {
                    return true;
                }
            } catch (\Throwable $e) {
                $errors[] = 'roles()->pluck("slug") a levé: ' . $e->getMessage();
            }
        }

        // Log non bloquant mais utile en debug si quelque chose a raté en chemin
        if (!empty($errors)) {
            Log::debug('canManageRoles: tous les checks ont échoué', [
                'user_id' => $user->id ?? null,
                'errors'  => $errors,
            ]);
        }

        return false;
    }


    private function getDataFromRequest(array $context): array
    {
        if (isset($context['request']) && $context['request'] instanceof Request) {
            $request = $context['request'];

            $content = $request->getContent();
            if ($content) {
                $decoded = json_decode($content, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    return $decoded;
                }
            }

            $requestData = $request->all();
            if (!empty($requestData)) {
                return $requestData;
            }
        }

        $request = app(Request::class);
        if ($request) {
            $content = $request->getContent();
            if ($content) {
                $decoded = json_decode($content, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    return $decoded;
                }
            }

            $requestData = $request->all();
            if (!empty($requestData)) {
                return $requestData;
            }
        }

        throw new \InvalidArgumentException('Impossible de récupérer les données de la request HTTP');
    }
}
