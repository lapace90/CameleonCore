<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Controllers\Controller;
use App\Models\User;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Afficher la liste des utilisateurs
        return response()->json(User::all());
        // Ou retourner une vue avec la liste des utilisateurs
        // return view('admin.users.index', ['users' => User::all()]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // Afficher le formulaire de création d'un utilisateur
        return view('admin.users.create');
        // Ou retourner une réponse JSON
        // return response()->json(['message' => 'Display create user form']);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreUserRequest $request)
    {
        // Validation des données
        $validatedData = $request->validated();

        // Création de l'utilisateur
        $user = User::create($validatedData);

        // Optionnel : redirection ou réponse JSON
        return response()->json($user, 201);
        // Ou redirection vers une autre page
        // return redirect()->route('admin.users.index')->with('success', 'Utilisateur créé avec succès.');
    }

    /**
     * Display the specified resource.
     */
    public function show(User $user)
    {
        // Afficher les détails d'un utilisateur spécifique
        return response()->json($user);
        // Ou retourner une vue avec les détails de l'utilisateur
        // return view('admin.users.show', ['user' => $user]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(User $user)
    {
        // Afficher le formulaire d'édition pour un utilisateur spécifique
        return view('admin.users.edit', ['user' => $user]);
        // Ou retourner une réponse JSON
        // return response()->json(['message' => 'Display edit user form']);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateUserRequest $request, User $user)
    {
        // Validation des données
        $validatedData = $request->validated();

        // Mise à jour de l'utilisateur
        $user->update($validatedData);

        // Optionnel : redirection ou réponse JSON
        return response()->json($user);
        // Ou redirection vers une autre page
        // return redirect()->route('admin.users.index')->with('success', 'Utilisateur mis à jour avec succès.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user)
    {
        // Suppression de l'utilisateur
        $user->delete();

        // Optionnel : redirection ou réponse JSON
        return response()->json(['message' => 'Utilisateur supprimé avec succès.']);
        // Ou redirection vers une autre page
        // return redirect()->route('admin.users.index')->with('success', 'Utilisateur supprimé avec succès.');
    }
}
