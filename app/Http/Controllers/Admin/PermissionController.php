<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PermissionController extends Controller
{
    /**
     * Liste des permissions avec leurs rôles
     */
    public function index(Request $request)
    {
        $query = Permission::with(['roles'])
            ->withCount(['roles']);

        // Filtres
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                    ->orWhere('action', 'LIKE', "%{$search}%");
            });
        }

        if ($request->has('action')) {
            $query->where('action', 'LIKE', "%{$request->action}%");
        }

        // Tri
        $sortField = $request->get('sort', 'name');
        $sortDirection = $request->get('direction', 'asc');
        $query->orderBy($sortField, $sortDirection);

        // Pagination ou tout
        if ($request->get('per_page') === 'all') {
            $permissions = $query->get();
        } else {
            $perPage = $request->get('per_page', 50);
            $permissions = $query->paginate($perPage);
        }

        // Transformer les données
        $permissions->transform(function ($permission) {
            return [
                'id' => $permission->id,
                'name' => $permission->name,
                'action' => $permission->action,
                'resource' => $this->extractResource($permission->name),
                'roles' => $permission->roles->map(function ($role) {
                    return [
                        'id' => $role->id,
                        'name' => $role->name,
                        'slug' => $role->slug
                    ];
                }),
                'roles_count' => $permission->roles_count,
                'category' => $this->getPermissionCategory($permission->action),
                'created_at' => $permission->created_at,
                'updated_at' => $permission->updated_at
            ];
        });

        return response()->json($permissions);
    }

    /**
     * Afficher une permission spécifique
     */
    public function show(Permission $permission)
    {
        $permission->load(['roles.users']);

        return response()->json([
            'id' => $permission->id,
            'name' => $permission->name,
            'action' => $permission->action,
            'resource' => $this->extractResource($permission->name),
            'category' => $this->getPermissionCategory($permission->action),
            'roles' => $permission->roles->map(function ($role) {
                return [
                    'id' => $role->id,
                    'name' => $role->name,
                    'slug' => $role->slug,
                    'description' => $role->description,
                    'users_count' => $role->users->count()
                ];
            }),
            'total_users_affected' => $permission->roles->sum(function ($role) {
                return $role->users->count();
            }),
            'created_at' => $permission->created_at,
            'updated_at' => $permission->updated_at
        ]);
    }

    /**
     * Créer une nouvelle permission
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:permissions',
            'action' => 'required|string|max:100',
            'resource' => 'nullable|string|max:100'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Erreurs de validation',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Créer la permission
            $permission = Permission::create([
                'name' => $request->name,
                'action' => strtolower($request->action)
            ]);

            return response()->json([
                'message' => 'Permission créée avec succès',
                'permission' => [
                    'id' => $permission->id,
                    'name' => $permission->name,
                    'action' => $permission->action,
                    'resource' => $this->extractResource($permission->name),
                    'category' => $this->getPermissionCategory($permission->action),
                    'roles_count' => 0,
                    'created_at' => $permission->created_at
                ]
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de la création de la permission',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mettre à jour une permission
     */
    public function update(Request $request, Permission $permission)
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255', Rule::unique('permissions')->ignore($permission->id)],
            'action' => 'required|string|max:100',
            'resource' => 'nullable|string|max:100'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Erreurs de validation',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Mettre à jour la permission
            $permission->update([
                'name' => $request->name,
                'action' => strtolower($request->action)
            ]);

            return response()->json([
                'message' => 'Permission mise à jour avec succès',
                'permission' => [
                    'id' => $permission->id,
                    'name' => $permission->name,
                    'action' => $permission->action,
                    'resource' => $this->extractResource($permission->name),
                    'category' => $this->getPermissionCategory($permission->action),
                    'updated_at' => $permission->updated_at
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de la mise à jour de la permission',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Supprimer une permission
     */
    public function destroy(Permission $permission)
    {
        // Vérifier si la permission est utilisée par des rôles
        $rolesCount = $permission->roles()->count();

        if ($rolesCount > 0) {
            return response()->json([
                'message' => 'Impossible de supprimer une permission utilisée par des rôles',
                'roles_count' => $rolesCount
            ], 403);
        }

        try {
            DB::beginTransaction();

            // Détacher de tous les rôles (par sécurité)
            $permission->roles()->detach();

            // Supprimer la permission
            $permission->delete();

            DB::commit();

            return response()->json([
                'message' => 'Permission supprimée avec succès'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Erreur lors de la suppression de la permission',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Générer les permissions CRUD standards
     */
    public function generateStandardPermissions(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'resources' => 'nullable|array',
            'resources.*' => 'string|max:50'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Erreurs de validation',
                'errors' => $validator->errors()
            ], 422);
        }

        // Ressources par défaut si aucune n'est spécifiée
        $resources = $request->get('resources', [
            'users',
            'roles',
            'permissions',
            'products',
            'categories',
            'tags',
            'orders',
            'customers',
            'analytics',
            'settings'
        ]);

        // Actions CRUD standard
        $actions = [
            'create' => 'Créer',
            'read' => 'Voir',
            'update' => 'Modifier',
            'delete' => 'Supprimer'
        ];

        $createdPermissions = [];
        $skippedPermissions = [];

        try {
            DB::beginTransaction();

            foreach ($resources as $resource) {
                foreach ($actions as $action => $actionLabel) {
                    $permissionName = "{$actionLabel} les {$resource}";

                    // Vérifier si la permission existe déjà
                    if (Permission::where('name', $permissionName)->exists()) {
                        $skippedPermissions[] = $permissionName;
                        continue;
                    }

                    $permission = Permission::create([
                        'name' => $permissionName,
                        'action' => $action
                    ]);

                    $createdPermissions[] = [
                        'id' => $permission->id,
                        'name' => $permission->name,
                        'action' => $permission->action,
                        'resource' => $resource
                    ];
                }
            }

            DB::commit();

            return response()->json([
                'message' => 'Permissions générées avec succès',
                'created_count' => count($createdPermissions),
                'skipped_count' => count($skippedPermissions),
                'created_permissions' => $createdPermissions,
                'skipped_permissions' => $skippedPermissions
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Erreur lors de la génération des permissions',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtenir les permissions groupées par catégorie
     */
    public function categorized()
    {
        $permissions = Permission::with(['roles'])->get();

        $categorized = $permissions->groupBy(function ($permission) {
            return $this->getPermissionCategory($permission->action);
        });

        $result = [];
        foreach ($categorized as $category => $perms) {
            $result[$category] = [
                'label' => $this->getCategoryLabel($category),
                'icon' => $this->getCategoryIcon($category),
                'permissions' => $perms->map(function ($permission) {
                    return [
                        'id' => $permission->id,
                        'name' => $permission->name,
                        'action' => $permission->action,
                        'resource' => $this->extractResource($permission->name),
                        'roles_count' => $permission->roles->count()
                    ];
                })->values()
            ];
        }

        return response()->json($result);
    }

    /**
     * Obtenir les rôles qui ont une permission spécifique
     */
    public function roles(Permission $permission)
    {
        $roles = $permission->roles()->with(['users'])->get();

        return response()->json([
            'permission' => [
                'id' => $permission->id,
                'name' => $permission->name,
                'action' => $permission->action
            ],
            'roles' => $roles->map(function ($role) {
                return [
                    'id' => $role->id,
                    'name' => $role->name,
                    'slug' => $role->slug,
                    'description' => $role->description,
                    'users_count' => $role->users->count()
                ];
            }),
            'total_users_affected' => $roles->sum(function ($role) {
                return $role->users->count();
            })
        ]);
    }

    /**
     * Statistiques des permissions pour la sidebar
     */
    public function stats()
    {
        $stats = [
            'total_permissions' => Permission::count(),
            'used_permissions' => Permission::has('roles')->count(),
            'unused_permissions' => Permission::doesntHave('roles')->count(),
            'permissions_by_action' => Permission::select('action', DB::raw('count(*) as count'))
                ->groupBy('action')
                ->orderBy('count', 'desc')
                ->get(),
            'most_used_permissions' => Permission::withCount('roles')
                ->orderBy('roles_count', 'desc')
                ->limit(10)
                ->get(['name', 'action', 'roles_count']),
            'permissions_by_category' => $this->getPermissionsByCategoryStats()
        ];

        return response()->json($stats);
    }

    /**
     * Extraire la ressource à partir du nom de la permission
     */
    private function extractResource($permissionName)
    {
        // Logique simple pour extraire la ressource
        // Par exemple "Créer les utilisateurs" -> "utilisateurs"
        if (preg_match('/les? (.+)$/', $permissionName, $matches)) {
            return $matches[1];
        }
        return null;
    }

    /**
     * Obtenir la catégorie d'une permission basée sur son action
     */
    private function getPermissionCategory($action)
    {
        $action = strtolower($action);

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
    }

    /**
     * Obtenir le label d'une catégorie
     */
    private function getCategoryLabel($category)
    {
        $labels = [
            'create' => 'Création',
            'read' => 'Lecture / Consultation',
            'update' => 'Modification',
            'delete' => 'Suppression',
            'admin' => 'Administration',
            'other' => 'Autres actions'
        ];

        return $labels[$category] ?? $category;
    }

    /**
     * Obtenir l'icône d'une catégorie
     */
    private function getCategoryIcon($category)
    {
        $icons = [
            'create' => 'fas fa-plus',
            'read' => 'fas fa-eye',
            'update' => 'fas fa-edit',
            'delete' => 'fas fa-trash',
            'admin' => 'fas fa-cog',
            'other' => 'fas fa-star'
        ];

        return $icons[$category] ?? 'fas fa-key';
    }

    /**
     * Statistiques des permissions par catégorie
     */
    private function getPermissionsByCategoryStats()
    {
        $permissions = Permission::all();
        $categories = [];

        foreach ($permissions as $permission) {
            $category = $this->getPermissionCategory($permission->action);

            if (!isset($categories[$category])) {
                $categories[$category] = [
                    'label' => $this->getCategoryLabel($category),
                    'count' => 0
                ];
            }

            $categories[$category]['count']++;
        }

        return $categories;
    }

    /**
     * Importer des permissions depuis un fichier JSON
     */
    public function import(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'permissions' => 'required|array',
            'permissions.*.name' => 'required|string|max:255',
            'permissions.*.action' => 'required|string|max:100'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Erreurs de validation',
                'errors' => $validator->errors()
            ], 422);
        }

        $imported = 0;
        $skipped = 0;
        $errors = [];

        try {
            DB::beginTransaction();

            foreach ($request->permissions as $permissionData) {
                // Vérifier si la permission existe déjà
                if (Permission::where('name', $permissionData['name'])->exists()) {
                    $skipped++;
                    continue;
                }

                try {
                    Permission::create([
                        'name' => $permissionData['name'],
                        'action' => strtolower($permissionData['action'])
                    ]);
                    $imported++;
                } catch (\Exception $e) {
                    $errors[] = "Erreur pour '{$permissionData['name']}': {$e->getMessage()}";
                }
            }

            DB::commit();

            return response()->json([
                'message' => 'Import terminé',
                'imported' => $imported,
                'skipped' => $skipped,
                'errors' => $errors
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Erreur lors de l\'import',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Exporter les permissions vers un format JSON
     */
    public function export()
    {
        $permissions = Permission::with(['roles'])->get();

        $export = $permissions->map(function ($permission) {
            return [
                'name' => $permission->name,
                'action' => $permission->action,
                'roles' => $permission->roles->pluck('name')->toArray(),
                'created_at' => $permission->created_at->toISOString()
            ];
        });

        return response()->json([
            'permissions' => $export,
            'exported_at' => now()->toISOString(),
            'total_count' => $permissions->count()
        ]);
    }
}
