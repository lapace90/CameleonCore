<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * Liste des utilisateurs avec leurs rôles et permissions
     */
    public function index(Request $request)
    {
        $query = User::with(['role', 'roles', 'permissions'])
            ->withCount(['roles', 'permissions']);

        // Filtres
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                    ->orWhere('email', 'LIKE', "%{$search}%");
            });
        }

        if ($request->has('role_id')) {
            $query->where('role_id', $request->role_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Tri
        $sortField = $request->get('sort', 'name');
        $sortDirection = $request->get('direction', 'asc');
        $query->orderBy($sortField, $sortDirection);

        // Pagination ou tout
        if ($request->get('per_page') === 'all') {
            $users = $query->get();
        } else {
            $perPage = $request->get('per_page', 15);
            $users = $query->paginate($perPage);
        }

        // Transformer les données pour inclure des informations additionnelles
        $users->transform(function ($user) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'email_verified_at' => $user->email_verified_at,
                'status' => $user->status ?? 'active',
                'role_id' => $user->role_id,
                'role' => $user->role ? [
                    'id' => $user->role->id,
                    'name' => $user->role->name,
                    'slug' => $user->role->slug
                ] : null,
                'additional_roles' => $user->roles->map(function ($role) {
                    return [
                        'id' => $role->id,
                        'name' => $role->name,
                        'slug' => $role->slug
                    ];
                }),
                'permissions' => $user->permissions->map(function ($permission) {
                    return [
                        'id' => $permission->id,
                        'name' => $permission->name,
                        'action' => $permission->action
                    ];
                }),
                'roles_count' => $user->roles_count,
                'permissions_count' => $user->permissions_count,
                'created_at' => $user->created_at,
                'updated_at' => $user->updated_at,
                'last_login_at' => $user->last_login_at ?? null
            ];
        });

        return response()->json($users);
    }

    /**
     * Afficher un utilisateur spécifique
     */
    public function show(User $user)
    {
        $user->load(['role', 'roles.permissions', 'permissions']);

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'email_verified_at' => $user->email_verified_at,
            'status' => $user->status ?? 'active',
            'role_id' => $user->role_id,
            'role' => $user->role ? [
                'id' => $user->role->id,
                'name' => $user->role->name,
                'slug' => $user->role->slug,
                'description' => $user->role->description,
                'permissions' => $user->role->permissions->map(function ($permission) {
                    return [
                        'id' => $permission->id,
                        'name' => $permission->name,
                        'action' => $permission->action
                    ];
                })
            ] : null,
            'additional_roles' => $user->roles->map(function ($role) {
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
                    })
                ];
            }),
            'direct_permissions' => $user->permissions->map(function ($permission) {
                return [
                    'id' => $permission->id,
                    'name' => $permission->name,
                    'action' => $permission->action
                ];
            }),
            'all_permissions' => $this->getAllUserPermissions($user),
            'created_at' => $user->created_at,
            'updated_at' => $user->updated_at,
            'last_login_at' => $user->last_login_at ?? null
        ]);
    }

    /**
     * Créer un nouvel utilisateur
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'role_id' => 'nullable|exists:roles,id',
            'additional_roles' => 'nullable|array',
            'additional_roles.*' => 'exists:roles,id',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,id',
            'status' => 'nullable|in:active,inactive,blocked'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Erreurs de validation',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        try {
            // Créer l'utilisateur
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role_id' => $request->role_id,
                'status' => $request->status ?? 'active',
                'email_verified_at' => now() // Auto-vérification pour les admins
            ]);

            // Attribuer les rôles additionnels
            if ($request->has('additional_roles') && is_array($request->additional_roles)) {
                $user->roles()->attach($request->additional_roles);
            }

            // Attribuer les permissions directes
            if ($request->has('permissions') && is_array($request->permissions)) {
                $user->permissions()->attach($request->permissions);
            }

            DB::commit();

            // Charger les relations pour la réponse
            $user->load(['role', 'roles', 'permissions']);

            return response()->json([
                'message' => 'Utilisateur créé avec succès',
                'user' => $user
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Erreur lors de la création de l\'utilisateur',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mettre à jour un utilisateur
     */
    public function update(Request $request, User $user)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'password' => 'nullable|string|min:8|confirmed',
            'role_id' => 'nullable|exists:roles,id',
            'additional_roles' => 'nullable|array',
            'additional_roles.*' => 'exists:roles,id',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,id',
            'status' => 'nullable|in:active,inactive,blocked',
            'reset_password' => 'nullable|boolean'
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
                'email' => $request->email,
                'role_id' => $request->role_id,
                'status' => $request->status ?? $user->status ?? 'active'
            ];

            // Mettre à jour le mot de passe si fourni
            if ($request->filled('password')) {
                $updateData['password'] = Hash::make($request->password);
            }

            // Forcer la réinitialisation du mot de passe
            if ($request->reset_password) {
                $updateData['password_reset_required'] = true;
            }

            $user->update($updateData);

            // Mettre à jour les rôles additionnels
            if ($request->has('additional_roles')) {
                $roles = is_array($request->additional_roles) ? $request->additional_roles : [];
                $user->roles()->sync($roles);
            }

            // Mettre à jour les permissions directes
            if ($request->has('permissions')) {
                $permissions = is_array($request->permissions) ? $request->permissions : [];
                $user->permissions()->sync($permissions);
            }

            DB::commit();

            // Charger les relations pour la réponse
            $user->load(['role', 'roles', 'permissions']);

            return response()->json([
                'message' => 'Utilisateur mis à jour avec succès',
                'user' => $user
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Erreur lors de la mise à jour de l\'utilisateur',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Changer le statut d'un utilisateur
     */
    public function updateStatus(Request $request, User $user)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:active,inactive,blocked'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Statut invalide',
                'errors' => $validator->errors()
            ], 422);
        }

        $user->update(['status' => $request->status]);

        return response()->json([
            'message' => 'Statut utilisateur mis à jour avec succès',
            'user' => $user
        ]);
    }

    /**
     * Supprimer un utilisateur
     */
    public function destroy(User $user)
    {
        try {
            DB::beginTransaction();

            // Détacher tous les rôles et permissions
            $user->roles()->detach();
            $user->permissions()->detach();

            // Supprimer l'utilisateur
            $user->delete();

            DB::commit();

            return response()->json([
                'message' => 'Utilisateur supprimé avec succès'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Erreur lors de la suppression de l\'utilisateur',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Actions en masse sur les utilisateurs
     */
    public function bulkAction(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'action' => 'required|in:activate,suspend,assign-role,export',
            'user_ids' => 'required|array|min:1',
            'user_ids.*' => 'exists:users,id',
            'role_id' => 'required_if:action,assign-role|exists:roles,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Erreurs de validation',
                'errors' => $validator->errors()
            ], 422);
        }

        $userIds = $request->user_ids;
        $action = $request->action;

        try {
            DB::beginTransaction();

            switch ($action) {
                case 'activate':
                    User::whereIn('id', $userIds)->update(['status' => 'active']);
                    $message = 'Utilisateurs activés avec succès';
                    break;

                case 'suspend':
                    User::whereIn('id', $userIds)->update(['status' => 'inactive']);
                    $message = 'Utilisateurs suspendus avec succès';
                    break;

                case 'assign-role':
                    User::whereIn('id', $userIds)->update(['role_id' => $request->role_id]);
                    $message = 'Rôle assigné aux utilisateurs avec succès';
                    break;

                case 'export':
                    // Logique d'export (à implémenter selon vos besoins)
                    $users = User::with(['role', 'roles'])
                        ->whereIn('id', $userIds)
                        ->get();

                    // Retourner les données pour l'export
                    return response()->json([
                        'message' => 'Données exportées avec succès',
                        'data' => $users
                    ]);

                default:
                    return response()->json([
                        'message' => 'Action non supportée'
                    ], 400);
            }

            DB::commit();

            return response()->json([
                'message' => $message,
                'affected_count' => count($userIds)
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Erreur lors de l\'action en masse',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtenir toutes les permissions d'un utilisateur (rôles + directes)
     */
    private function getAllUserPermissions(User $user)
    {
        $permissions = collect();

        // Permissions du rôle principal
        if ($user->role) {
            $permissions = $permissions->merge($user->role->permissions);
        }

        // Permissions des rôles additionnels
        foreach ($user->roles as $role) {
            $permissions = $permissions->merge($role->permissions);
        }

        // Permissions directes
        $permissions = $permissions->merge($user->permissions);

        // Supprimer les doublons et formater
        return $permissions->unique('id')->map(function ($permission) {
            return [
                'id' => $permission->id,
                'name' => $permission->name,
                'action' => $permission->action
            ];
        })->values();
    }

    /**
     * Vérifier si un utilisateur a une permission spécifique
     */
    public function checkPermission(User $user, $permission)
    {
        $userPermissions = $this->getAllUserPermissions($user);

        return response()->json([
            'has_permission' => $userPermissions->contains('action', $permission)
        ]);
    }
    
    /**
     * Statistiques des utilisateurs pour la sidebar
     */
    public function stats()
    {
        $stats = [
            'total_users' => User::count(),
            'active_users' => User::where('status', 'active')->count(),
            'inactive_users' => User::where('status', 'inactive')->count(),
            'blocked_users' => User::where('status', 'blocked')->count(),
            'verified_users' => User::whereNotNull('email_verified_at')->count(),
            'unverified_users' => User::whereNull('email_verified_at')->count(),
            'recent_users' => User::where('created_at', '>=', now()->subWeek())->count(),
            'users_by_role' => User::with('role')
                ->get()
                ->groupBy(function ($user) {
                    return $user->role ? $user->role->name : 'Sans rôle';
                })
                ->map->count()
        ];

        return response()->json($stats);
    }
}
