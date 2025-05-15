<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDishRequest;
use App\Http\Requests\UpdateDishRequest;
use App\Models\Dish;

class DishController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Récupérer tous les plats
        $dishes = Dish::all();

        // Retourner une réponse JSON avec les plats
        return response()->json($dishes);
        // Ou retourner une vue avec les plats
        // return view('dishes.index', compact('dishes'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // Afficher le formulaire de création d'un plat
        return view('dishes.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreDishRequest $request)
    {
        // Validation des données
        $validatedData = $request->validated();

        // Création du plat
        $dish = Dish::create($validatedData);

        // Optionnel : redirection ou réponse JSON
        return response()->json($dish, 201);
        // Ou redirection vers une autre page
        // return redirect()->route('dishes.index')->with('success', 'Plat créé avec succès.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Dish $dish)
    {
        // Afficher les détails d'un plat spécifique
        return response()->json($dish);
        // Ou retourner une vue avec les détails du plat
        // return view('dishes.show', compact('dish'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Dish $dish)
    {
        // Afficher le formulaire d'édition pour un plat spécifique
        return view('dishes.edit', compact('dish'));
        // Ou retourner une réponse JSON
        // return response()->json($dish);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateDishRequest $request, Dish $dish)
    {
        // Validation des données
        $validatedData = $request->validated();

        // Mise à jour du plat
        $dish->update($validatedData);

        // Optionnel : redirection ou réponse JSON
        return response()->json($dish);
        // Ou redirection vers une autre page
        // return redirect()->route('dishes.index')->with('success', 'Plat mis à jour avec succès.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Dish $dish)
    {
        // Suppression du plat
        $dish->delete();

        // Optionnel : redirection ou réponse JSON
        return response()->json(['message' => 'Plat supprimé avec succès'], 204);
        // Ou redirection vers une autre page
        // return redirect()->route('dishes.index')->with('success', 'Plat supprimé avec succès.');
    }
}
