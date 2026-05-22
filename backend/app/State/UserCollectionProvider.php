<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Models\User;
use App\Data\UserOutputData;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class UserCollectionProvider implements ProviderInterface
{
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): iterable
    {
        $currentUser = auth('sanctum')->user();
        if (!$currentUser) {
            throw new UnauthorizedHttpException('Bearer', 'Token d\'authentification requis');
        }

        try {
            // Détecter le SGBD pour LIKE case-insensitive
            $driver = DB::getDriverName(); // 'pgsql', 'mysql', 'sqlite', ...
            $likeOp = $driver === 'pgsql' ? 'ILIKE' : 'LIKE';

            // Colonnes existantes sur "roles"
            $roleSelect = ['id', 'name', 'slug'];
            if (Schema::hasColumn('roles', 'description')) {
                $roleSelect[] = 'description';
            }

            $query = User::with([
                'role:' . implode(',', $roleSelect),
                'roles:' . implode(',', $roleSelect),
            ])->withCount(['roles']);

            $request = request();

            // Recherche nom/email
            if ($search = $request->get('name')) {
                $query->where(function ($q) use ($search, $likeOp) {
                    $q->where('name', $likeOp, "%{$search}%")
                        ->orWhere('email', $likeOp, "%{$search}%");
                });
            }

            // Filtre statut
            if (($status = $request->get('status')) !== null && $status !== '') {
                $query->where('status', $status);
            }

            // Filtre rôle principal
            if ($roleId = $request->get('role_id')) {
                $query->where('role_id', $roleId);
            }

            // Tri
            $orderBy        = $request->get('order', 'created_at');
            $orderDirection = strtolower($request->get('order_direction', 'desc')) === 'asc' ? 'asc' : 'desc';
            if (in_array($orderBy, ['id', 'name', 'email', 'created_at', 'last_login_at'], true)) {
                $query->orderBy($orderBy, $orderDirection);
            }

            // Pagination
            $page  = max(1, (int) $request->get('page', 1));
            $limit = max(1, min(100, (int) $request->get('limit', 25)));

            $users = $query->paginate($limit, ['*'], 'page', $page);

            return $users->getCollection()->map(function (User $user) {
                return UserOutputData::fromUser($user)->toArray();
            });
        } catch (\Throwable $e) {
            Log::error('UserCollectionProvider - erreur', [
                'type' => get_class($e),
                'msg'  => $e->getMessage(),
            ]);
            throw $e; // laisse Laravel renvoyer la 500 avec le détail en local
        }
    }
}
