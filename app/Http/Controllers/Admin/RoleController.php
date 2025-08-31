<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\Permission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class RoleController extends Controller
{
    /**
     * Liste des rôles avec leurs permissions et utilisateurs
     */
    public function index(Request $request)
    {
        $query = Role::with(['permissions', 'users'])
            ->withCount(['permissions', 'users']);

        // Filtres
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                    ->orWhere('slug', 'LIKE', "%{$search}%")
                    ->orWhere('description', 'LIKE', "%{$search}%");
            });
        }

        if ($request->has('permission')) {
            $query->whereHas('permissions', function ($q) use ($request) {
                $q->where('action', 'LIKE', "%{$request->permission}%");
            });
        }

        // Tri
        $sortField = $request->get('sort', 'name');
        $sortDirection = $request->get('direction', 'asc');
        $query->orderBy($sortField, $sortDirection);

        // Pagination ou tout
        if ($request->get('per_page') === 'all') {
            $roles = $query->get();
        } else {
            $perPage = $request->get('per_page', 15);
            $roles = $query->paginate($perPage);
        }

        // Transformer les données
        $roles->transform(function ($role) {
            return [
                'id' => $role->id,
                'name' => $role->name,
                'slug' => $role->slug,
                'description' => $role->description,
                'permissions' => $role->permissions->map(function ($permission) {
                    return [
                        'id' => $permission->id,
                        'name' => $permission->name,
                        'action' => $permission->action
                    ];
                }),
                'users' => $role->users->map(function ($user) {
                    return [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email
                    ];
                }),
                'permissions_count' => $role->permissions_count,
                'users_count' => $role->users_count,
                'created_at' => $role->created_at,
                'updated_at' => $role->updated_at
            ];
        });

        return response()->json($roles);
    }

    /**
     * Afficher un rôle spécifique
     */
    public function show(Role $role)
    {
        $role->load(['permissions', 'users']);

        return response()->json([
            'id' => $role->id,
            'name' => $role->name,
            'slug' => $role->slug,
            'description' => $role->description,
            'permissions' => $role->permissions->map(function ($permission) {
                return [
                    'id' => $permission->id,
                    'name' => $permission->name,
                    'action' => $permission->action
                ];
            }),
            'users' => $role->users->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'status' => $user->status ?? 'active'
                ];
            }),
            'permissions_count' => $role->permissions->count(),
            'users_count' => $role->users->count(),
            'created_at' => $role->created_at,
            'updated_at' => $role->updated_at
        ]);
    }

    /**
     * Créer un nouveau rôle
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:roles',
            'slug' => 'nullable|string|max:255|unique:roles',
            'description' => 'nullable|string|max:1000',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Erreurs de validation',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        try {
            // Générer le slug s'il n'est pas fourni
            $slug = $request->slug ?: $this->generateSlug($request->name);

            // Vérifier que le slug est unique
            while (Role::where('slug', $slug)->exists()) {
                $slug .= '-' . rand(1000, 9999);
            }

            // Créer le rôle
            $role = Role::create([
                'name' => $request->name,
                'slug' => $slug,
                'description' => $request->description
            ]);

            // Attribuer les permissions
            if ($request->has('permissions') && is_array($request->permissions)) {
                $role->permissions()->attach($request->permissions);
            }

            DB::commit();

            // Charger les relations pour la réponse
            $role->load(['permissions', 'users']);

            return response()->json([
                'message' => 'Rôle créé avec succès',
                'role' => $role
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Erreur lors de la création du rôle',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mettre à jour un rôle
     */
    public function update(Request $request, Role $role)
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255', Rule::unique('roles')->ignore($role->id)],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('roles')->ignore($role->id)],
            'description' => 'nullable|string|max:1000',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Erreurs de validation',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        try {
            // Mettre à jour les informations de base
            $updateData = [
                'name' => $request->name,
                'description' => $request->description
            ];

            // Mettre à jour le slug s'il est fourni
            if ($request->filled('slug')) {
                $updateData['slug'] = $request->slug;
            } elseif ($role->name !== $request->name) {
                // Regénérer le slug si le nom a changé
                $updateData['slug'] = $this->generateSlug($request->name);
            }

            $role->update($updateData);

            // Mettre à jour les permissions
            if ($request->has('permissions')) {
                $permissions = is_array($request->permissions) ? $request->permissions : [];
                $role->permissions()->sync($permissions);
            }

            DB::commit();

            // Charger les relations pour la réponse
            $role->load(['permissions', 'users']);

            return response()->json([
                'message' => 'Rôle mis à jour avec succès',
                'role' => $role
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Erreur lors de la mise à jour du rôle',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Supprimer un rôle
     */
    public function destroy(Role $role)
    {
        // Vérifier si le rôle est utilisé par des utilisateurs
        $usersCount = $role->users()->count();
        $additionalUsersCount = $role->usersByPivot()->count();

        if ($usersCount > 0 || $additionalUsersCount > 0) {
            return response()->json([
                'message' => 'Impossible de supprimer un rôle assigné à des utilisateurs',
                'users_count' => $usersCount + $additionalUsersCount
            ], 403);
        }

        try {
            DB::beginTransaction();

            // Détacher toutes les permissions
            $role->permissions()->detach();

            // Supprimer le rôle
            $role->delete();

            DB::commit();

            return response()->json([
                'message' => 'Rôle supprimé avec succès'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Erreur lors de la suppression du rôle',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Dupliquer un rôle
     */
    public function duplicate(Role $role)
    {
        try {
            DB::beginTransaction();

            // Créer une copie du rôle
            $newRole = Role::create([
                'name' => $role->name . ' (Copie)',
                'slug' => $this->generateUniqueSlug($role->slug . '-copy'),
                'description' => $role->description
            ]);

            // Copier les permissions
            $permissionIds = $role->permissions->pluck('id')->toArray();
            $newRole->permissions()->attach($permissionIds);

            DB::commit();

            // Charger les relations pour la réponse
            $newRole->load(['permissions', 'users']);

            return response()->json([
                'message' => 'Rôle dupliqué avec succès',
                'role' => $newRole
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Erreur lors de la duplication du rôle',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Assigner/retirer des permissions à un rôle
     */
    public function syncPermissions(Request $request, Role $role)
    {
        $validator = Validator::make($request->all(), [
            'permissions' => 'required|array',
            'permissions.*' => 'exists:permissions,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Erreurs de validation',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $role->permissions()->sync($request->permissions);

            return response()->json([
                'message' => 'Permissions mises à jour avec succès',
                'permissions_count' => count($request->permissions)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de la mise à jour des permissions',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtenir les utilisateurs d'un rôle avec pagination
     */
    public function users(Request $request, Role $role)
    {
        $perPage = $request->get('per_page', 15);

        // Utilisateurs avec ce rôle principal
        $primaryUsers = $role->users()->with(['roles', 'permissions'])->paginate($perPage);

        // Utilisateurs avec ce rôle comme rôle additionnel
        $additionalUsers = $role->usersByPivot()
            ->with(['role', 'roles', 'permissions'])
            ->paginate($perPage);

        return response()->json([
            'primary_users' => $primaryUsers,
            'additional_users' => $additionalUsers,
            'total_users' => $role->users()->count() + $role->usersByPivot()->count()
        ]);
    }

    /**
     * Statistiques des rôles pour la sidebar
     */
    public function stats()
    {
        $stats = [
            'total_roles' => Role::count(),
            'roles_with_users' => Role::has('users')->count(),
            'roles_without_users' => Role::doesntHave('users')->count(),
            'most_used_roles' => Role::withCount('users')
                ->orderBy('users_count', 'desc')
                ->limit(5)
                ->get(['name', 'users_count']),
            'permissions_distribution' => Role::withCount('permissions')
                ->get(['name', 'permissions_count'])
                ->sortByDesc('permissions_count')
                ->take(10)
                ->values()
        ];

        return response()->json($stats);
    }

    /**
     * Générer un slug à partir d'une chaîne
     */
    private function generateSlug($string)
    {
        return Str::slug($string, '-');
    }

    /**
     * Générer un slug unique
     */
    private function generateUniqueSlug($baseSlug)
    {
        $slug = $this->generateSlug($baseSlug);
        $counter = 1;

        while (Role::where('slug', $slug)->exists()) {
            $slug = $this->generateSlug($baseSlug) . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    /**
     * Vérifier si un rôle a une permission spécifique
     */
    public function hasPermission(Role $role, $permissionAction)
    {
        $hasPermission = $role->permissions()->where('action', $permissionAction)->exists();

        return response()->json([
            'role' => $role->name,
            'permission' => $permissionAction,
            'has_permission' => $hasPermission
        ]);
    }

    /**
     * Obtenir toutes les permissions disponibles pour la création/édition de rôles
     */
    public function availablePermissions()
    {
        $permissions = Permission::all()->groupBy(function ($permission) {
            // Grouper par type d'action
            $action = strtolower($permission->action);

            if (in_array($action, ['create', 'add', 'store'])) {
                return 'create';
            } elseif (in_array($action, ['read', 'view', 'show', 'list', 'index'])) {
                return 'read';
            } elseif (in_array($action, ['update', 'edit', 'modify'])) {
                return 'update';
            } elseif (in_array($action, ['delete', 'destroy', 'remove'])) {
                return 'delete';
            } elseif (in_array($action, ['manage', 'admin', 'control'])) {
                return 'admin';
            } else {
                return 'other';
            }
        });

        return response()->json($permissions);
    }
}
