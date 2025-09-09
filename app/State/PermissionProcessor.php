<?php
// app/State/PermissionProcessor.php - Pattern conforme à UserProcessor

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\State\ProcessorInterface;
use App\Models\Permission;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class PermissionProcessor implements ProcessorInterface
{
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        // 🔐 SÉCURITÉ SANCTUM (comme UserProcessor)
        $currentUser = auth('sanctum')->user();

        if (!$currentUser) {
            throw new UnauthorizedHttpException('Bearer', 'Token d\'authentification requis');
        }

        // 🔐 AUTORISATION (comme UserProcessor)
        if (!$this->canManagePermissions($currentUser)) {
            throw new AccessDeniedHttpException('Permissions insuffisantes pour gérer les permissions');
        }

        // 🎯 PATTERN identique à UserProcessor
        try {
            switch (true) {
                case $operation instanceof Post:
                    return $this->createPermission($data, $context, $currentUser);

                case $operation instanceof Put:
                case $operation instanceof Patch:
                    return $this->updatePermission($data, (int) $uriVariables['id'], $context, $currentUser);

                case $operation instanceof Delete:
                    $this->deletePermission((int) $uriVariables['id'], $currentUser);
                    return null;

                default:
                    throw new \InvalidArgumentException('Opération non supportée: ' . get_class($operation));
            }
        } catch (ValidationException $e) {
            Log::error('Erreur de validation PermissionProcessor', [
                'errors' => $e->errors(),
                'user' => $currentUser->name
            ]);
            throw $e;
        } catch (\Throwable $e) {
            Log::error('Erreur dans PermissionProcessor', [
                'message' => $e->getMessage(),
                'operation' => get_class($operation),
                'user' => $currentUser->name
            ]);
            throw $e;
        }
    }

    /**
     * 🧠 VALIDATION Laravel pour création
     */
    private function createPermission(mixed $data, array $context, $currentUser): Permission
    {
        $payload = $this->getDataFromRequest($context);

        Log::info('PermissionProcessor - Création permission', [
            'payload' => $payload,
            'created_by' => $currentUser->name
        ]);

        // 🧠 VALIDATION Laravel avec support category
        $validator = Validator::make($payload, [
            'name' => 'required|string|max:255|unique:permissions,name',
            'action' => 'required|string|max:100|regex:/^[a-z0-9-]+$/|unique:permissions,action',
            'category' => 'nullable|string|max:50|in:system,users,accommodations,activities,bookings,reception,customers,restaurant,finance,analytics,communication,other'
        ], [
            'action.regex' => 'L\'action ne peut contenir que des lettres minuscules, chiffres et des tirets',
            'action.unique' => 'Une permission avec cette action existe déjà',
            'name.unique' => 'Une permission avec ce nom existe déjà',
            'category.in' => 'La catégorie doit être une catégorie valide'
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        // 🧠 LOGIQUE MÉTIER
        $payload['action'] = Permission::normalizeAction($payload['action']);

        if (empty($payload['name'])) {
            $payload['name'] = Permission::generateNameFromAction($payload['action']);
        }

        $permission = Permission::create($payload);

        Log::info('Permission créée', [
            'permission_id' => $permission->id,
            'action' => $permission->action,
            'category' => $permission->category,
            'created_by' => $currentUser->name
        ]);

        return $permission;
    }

    /**
     * 🧠 MISE À JOUR avec support category
     */
    private function updatePermission(mixed $data, int $permissionId, array $context, $currentUser): Permission
    {
        $permission = Permission::find($permissionId);

        if (!$permission) {
            throw new NotFoundHttpException("Permission avec l'ID {$permissionId} non trouvée");
        }

        $payload = $this->getDataFromRequest($context);

        Log::info('PermissionProcessor - Mise à jour permission', [
            'permission_id' => $permissionId,
            'payload' => $payload,
            'updated_by' => $currentUser->name
        ]);

        // 🧠 VALIDATION pour update avec support category
        $validator = Validator::make($payload, [
            'name' => "required|string|max:255|unique:permissions,name,{$permissionId}",
            'action' => "required|string|max:100|regex:/^[a-z0-9-]+$/|unique:permissions,action,{$permissionId}",
            'category' => 'nullable|string|max:50|in:system,users,accommodations,activities,bookings,reception,customers,restaurant,finance,analytics,communication,other'
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        // 🧠 LOGIQUE MÉTIER
        if (isset($payload['action'])) {
            $payload['action'] = Permission::normalizeAction($payload['action']);
        }

        if (isset($payload['name']) && empty($payload['name'])) {
            $payload['name'] = Permission::generateNameFromAction($payload['action']);
        }

        // 🧠 LOG pour permissions critiques
        if ($permission->isCritical() && isset($payload['action'])) {
            Log::warning('Modification permission critique', [
                'permission_id' => $permission->id,
                'old_action' => $permission->action,
                'new_action' => $payload['action'],
                'modified_by' => $currentUser->name
            ]);
        }

        $permission->update($payload);

        Log::info('Permission mise à jour', [
            'permission_id' => $permission->id,
            'category' => $permission->category,
            'updated_by' => $currentUser->name
        ]);

        return $permission->fresh();
    }

    /**
     * 🧠 SUPPRESSION
     */
    private function deletePermission(int $permissionId, $currentUser): void
    {
        $permission = Permission::find($permissionId);

        if (!$permission) {
            throw new NotFoundHttpException("Permission avec l'ID {$permissionId} non trouvée");
        }

        // 🧠 VÉRIFICATIONS MÉTIER
        if (!$permission->canBeDeleted()) {
            throw new \RuntimeException("Impossible de supprimer cette permission car elle est utilisée par {$permission->roles()->count()} rôle(s)");
        }

        if ($permission->isCritical()) {
            throw new \RuntimeException("Impossible de supprimer cette permission critique pour des raisons de sécurité");
        }

        Log::warning('Permission supprimée', [
            'permission_id' => $permission->id,
            'action' => $permission->action,
            'deleted_by' => $currentUser->name
        ]);

        $permission->delete();
    }

    // ===========================
    // MÉTHODES UTILITAIRES (pattern UserProcessor)
    // ===========================

    private function canManagePermissions($user): bool
    {
        // On affinera les autorisations plus tard quand le système sera en place
        return $user->isAdmin();
    }

    /**
     * 📥 Pattern identique à UserProcessor::getDataFromRequest()
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
