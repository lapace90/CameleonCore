<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRoleRequest;
use App\Http\Requests\UpdateRoleRequest;
use App\Models\Role;

class RoleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Afficher la liste des rôles
        return response()->json(Role::all());
        // Ou retourner une vue avec la liste des rôles
        // return view('admin.roles.index', ['roles' => Role::all()]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // Afficher le formulaire de création d'un rôle
        return view('admin.roles.create');
        // Ou retourner une réponse JSON
        // return response()->json(['message' => 'Display create role form']);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreRoleRequest $request)
    {
        // Validation des données
        $validatedData = $request->validated();

        // Création du rôle
        $role = Role::create($validatedData);

        // Optionnel : redirection ou réponse JSON
        return response()->json($role, 201);
        // Ou redirection vers une autre page
        // return redirect()->route('admin.roles.index')->with('success', 'Rôle créé avec succès.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Role $role)
    {
        // Afficher les détails d'un rôle spécifique
        return response()->json($role);
        // Ou retourner une vue avec les détails du rôle
        // return view('admin.roles.show', ['role' => $role]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Role $role)
    {
        // Afficher le formulaire d'édition pour un rôle spécifique
        return view('admin.roles.edit', ['role' => $role]);
        // Ou retourner une réponse JSON
        // return response()->json(['message' => 'Display edit role form']);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateRoleRequest $request, Role $role)
    {
        // Validation des données
        $validatedData = $request->validated();

        // Mise à jour du rôle
        $role->update($validatedData);

        // Optionnel : redirection ou réponse JSON
        return response()->json($role);
        // Ou redirection vers une autre page
        // return redirect()->route('admin.roles.index')->with('success', 'Rôle mis à jour avec succès.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Role $role)
    {
        // Suppression du rôle
        $role->delete();

        // Optionnel : redirection ou réponse JSON
        return response()->json(['message' => 'Rôle supprimé avec succès.']);
        // Ou redirection vers une autre page
        // return redirect()->route('admin.roles.index')->with('success', 'Rôle supprimé avec succès.');
    }
}
