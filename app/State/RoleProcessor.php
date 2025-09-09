<?php
// app/State/RoleProcessor.php - Pattern conforme à PermissionProcessor

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\State\ProcessorInterface;
use App\Models\Role;
use App\Models\Permission;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class RoleProcessor implements ProcessorInterface
{
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        // 🔐 SÉCURITÉ SANCTUM (même pattern que PermissionProcessor)
        $currentUser = auth('sanctum')->user();

        if (!$currentUser) {
            throw new UnauthorizedHttpException('Bearer', 'Token d\'authentification requis');
        }

        // 🔐 AUTORISATION
        if (!$this->canManageRoles($currentUser)) {
            throw new AccessDeniedHttpException('Permissions insuffisantes pour gérer les rôles');
        }

        // 🎯 PATTERN identique à PermissionProcessor
        try {
            switch (true) {
                case $operation instanceof Post:
                    return $this->createRole($data, $context, $currentUser);

                case $operation instanceof Put:
                case $operation instanceof Patch:
                    return $this->updateRole($data, (int) $uriVariables['id'], $context, $currentUser);

                case $operation instanceof Delete:
                    $this->deleteRole((int) $uriVariables['id'], $currentUser);
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
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    // ===========================
    // MÉTHODES CRUD
    // ===========================

    /**
     * 🧠 CRÉATION avec validation métier
     */
    private function createRole(mixed $data, array $context, $currentUser): Role
    {
        $payload = $this->getDataFromRequest($context);

        Log::info('RoleProcessor - Création rôle', [
            'payload' => $payload,
            'created_by' => $currentUser->name
        ]);

        // 🧠 VALIDATION pour création
        $validator = Validator::make($payload, [
            'name' => 'required|string|max:255|unique:roles,name',
            'description' => 'nullable|string|max:1000',
            'slug' => 'nullable|string|max:100|regex:/^[a-z0-9-]+$/|unique:roles,slug',
            'permissions' => 'nullable|array',
            'permissions.*' => 'integer|exists:permissions,id'
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        // 🧠 LOGIQUE MÉTIER - Auto-génération slug
        if (empty($payload['slug'])) {
            $payload['slug'] = Role::generateSlugFromName($payload['name']);
        } else {
            $payload['slug'] = Role::normalizeSlug($payload['slug']);
        }

        // Créer le rôle
        $role = Role::create([
            'name' => $payload['name'],
            'description' => $payload['description'] ?? null,
            'slug' => $payload['slug'],
        ]);

        // 🧠 ASSIGNATION PERMISSIONS si fournies
        if (isset($payload['permissions']) && is_array($payload['permissions'])) {
            $role->permissions()->sync($payload['permissions']);
        }

        // Recharger avec relations pour la réponse
        $role->load(['permissions', 'primaryUsers', 'users']);

        Log::info('Rôle créé', [
            'role_id' => $role->id,
            'name' => $role->name,
            'slug' => $role->slug,
            'permissions_count' => count($payload['permissions'] ?? []),
            'created_by' => $currentUser->name
        ]);

        return $role;
    }

    /**
     * 🧠 MISE À JOUR avec gestion permissions
     */
    private function updateRole(mixed $data, int $roleId, array $context, $currentUser): Role
    {
        $role = Role::find($roleId);

        if (!$role) {
            throw new NotFoundHttpException("Rôle avec l'ID {$roleId} non trouvé");
        }

        // 🔐 Protection rôles critiques
        if ($role->isCritical() && !$currentUser->isSuper()) {
            throw new AccessDeniedHttpException('Impossible de modifier un rôle critique du système');
        }

        $payload = $this->getDataFromRequest($context);

        Log::info('RoleProcessor - Mise à jour rôle', [
            'role_id' => $roleId,
            'payload' => $payload,
            'updated_by' => $currentUser->name
        ]);

        // 🧠 VALIDATION pour mise à jour
        $validator = Validator::make($payload, [
            'name' => "required|string|max:255|unique:roles,name,{$roleId}",
            'description' => 'nullable|string|max:1000',
            'slug' => "nullable|string|max:100|regex:/^[a-z0-9-]+$/|unique:roles,slug,{$roleId}",
            'permissions' => 'nullable|array',
            'permissions.*' => 'integer|exists:permissions,id'
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        // 🧠 LOGIQUE MÉTIER
        if (isset($payload['slug'])) {
            $payload['slug'] = Role::normalizeSlug($payload['slug']);
        }

        // Mise à jour des données de base
        $role->update([
            'name' => $payload['name'],
            'description' => $payload['description'] ?? $role->description,
            'slug' => $payload['slug'] ?? $role->slug,
        ]);

        // 🧠 SYNCHRONISATION PERMISSIONS
        if (isset($payload['permissions'])) {
            $role->permissions()->sync($payload['permissions']);
        }

        // Recharger avec relations
        $role->load(['permissions', 'primaryUsers', 'users']);

        Log::info('Rôle mis à jour', [
            'role_id' => $role->id,
            'name' => $role->name,
            'permissions_count' => $role->permissions->count(),
            'updated_by' => $currentUser->name
        ]);

        return $role;
    }

    /**
     * 🧠 SUPPRESSION avec vérifications
     */
    private function deleteRole(int $roleId, $currentUser): void
    {
        $role = Role::find($roleId);

        if (!$role) {
            throw new NotFoundHttpException("Rôle avec l'ID {$roleId} non trouvé");
        }

        // 🔐 Protection rôles critiques
        if ($role->isCritical()) {
            throw new AccessDeniedHttpException('Impossible de supprimer un rôle critique du système');
        }

        // 🧠 VÉRIFICATION MÉTIER
        if (!$role->canBeDeleted()) {
            throw new AccessDeniedHttpException('Ce rôle ne peut pas être supprimé car il est assigné à des utilisateurs');
        }

        Log::info('Suppression rôle', [
            'role_id' => $role->id,
            'name' => $role->name,
            'deleted_by' => $currentUser->name
        ]);

        // Détacher toutes les permissions avant suppression
        $role->permissions()->detach();
        
        $role->delete();
    }

    // ===========================
    // MÉTHODES UTILITAIRES
    // ===========================

    private function canManageRoles($user): bool
    {
        // Pour l'instant, même logique que permissions
        // On affinera plus tard avec le système RBAC complet
        return $user->isAdmin();
    }

    /**
     * 📥 Pattern identique à PermissionProcessor::getDataFromRequest()
     */
    private function getDataFromRequest(array $context): array
    {
        // Méthode 1: Depuis le contexte API Platform
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

        // Méthode 2: Depuis la request globale
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