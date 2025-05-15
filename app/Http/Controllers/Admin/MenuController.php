<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMenuRequest;
use App\Http\Requests\UpdateMenuRequest;
use App\Models\Menu;

class MenuController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Afficher la liste des menus
        return response()->json(Menu::all());
        // Ou retourner une vue avec la liste des menus
        // return view('admin.menus.index', ['menus' => Menu::all()]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // Afficher le formulaire de création d'un menu
        return view('admin.menus.create');
        // Ou retourner une réponse JSON
        // return response()->json(['message' => 'Display create menu form']);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreMenuRequest $request)
    {
        // Validation des données
        $validatedData = $request->validated();

        // Création du menu
        $menu = Menu::create($validatedData);

        // Optionnel : redirection ou réponse JSON
        return response()->json($menu, 201);
        // Ou redirection vers une autre page
        // return redirect()->route('admin.menus.index')->with('success', 'Menu créé avec succès.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Menu $menu)
    {
        // Afficher les détails d'un menu spécifique
        return response()->json($menu);
        // Ou retourner une vue avec les détails du menu
        // return view('admin.menus.show', ['menu' => $menu]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Menu $menu)
    {
        // Afficher le formulaire d'édition pour un menu spécifique
        return view('admin.menus.edit', ['menu' => $menu]);
        // Ou retourner une réponse JSON
        // return response()->json(['message' => 'Display edit menu form']);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateMenuRequest $request, Menu $menu)
    {
        // Validation des données
        $validatedData = $request->validated();

        // Mise à jour du menu
        $menu->update($validatedData);

        // Optionnel : redirection ou réponse JSON
        return response()->json($menu);
        // Ou redirection vers une autre page
        // return redirect()->route('admin.menus.index')->with('success', 'Menu mis à jour avec succès.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Menu $menu)
    {
        // Suppression du menu
        $menu->delete();

        // Optionnel : redirection ou réponse JSON
        return response()->json(['message' => 'Menu deleted successfully'], 204);
        // Ou redirection vers une autre page
        // return redirect()->route('admin.menus.index')->with('success', 'Menu supprimé avec succès.');
    }
}
