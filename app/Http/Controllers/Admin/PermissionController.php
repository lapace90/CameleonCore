<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePermissionRequest;
use App\Http\Requests\UpdatePermissionRequest;
use App\Models\Permission;

class PermissionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Afficher la liste des permissions
        return response()->json(Permission::all());
        // Ou retourner une vue avec la liste des permissions
        // return view('admin.permissions.index', ['permissions' => Permission::all()]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // Afficher le formulaire de création d'une permission
        return view('admin.permissions.create');
        // Ou retourner une réponse JSON
        // return response()->json(['message' => 'Display create permission form']);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePermissionRequest $request)
    {
        // Validation des données
        $validatedData = $request->validated();

        // Création de la permission
        $permission = Permission::create($validatedData);

        // Optionnel : redirection ou réponse JSON
        return response()->json($permission, 201);
        // Ou redirection vers une autre page
        // return redirect()->route('admin.permissions.index')->with('success', 'Permission créée avec succès.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Permission $permission)
    {
        // Afficher les détails d'une permission spécifique
        return response()->json($permission);
        // Ou retourner une vue avec les détails de la permission
        // return view('admin.permissions.show', ['permission' => $permission]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Permission $permission)
    {
        // Afficher le formulaire d'édition pour une permission spécifique
        return view('admin.permissions.edit', ['permission' => $permission]);
        // Ou retourner une réponse JSON
        // return response()->json(['message' => 'Display edit permission form']);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePermissionRequest $request, Permission $permission)
    {
        // Validation des données
        $validatedData = $request->validated();

        // Mise à jour de la permission
        $permission->update($validatedData);

        // Optionnel : redirection ou réponse JSON
        return response()->json($permission);
        // Ou redirection vers une autre page
        // return redirect()->route('admin.permissions.index')->with('success', 'Permission mise à jour avec succès.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Permission $permission)
    {
        // Suppression de la permission
        $permission->delete();

        // Optionnel : redirection ou réponse JSON
        return response()->json(['message' => 'Permission supprimée avec succès.'], 204);
        // Ou redirection vers une autre page
        // return redirect()->route('admin.permissions.index')->with('success', 'Permission supprimée avec succès.');
    }
}
