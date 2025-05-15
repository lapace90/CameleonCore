<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreIngredientRequest;
use App\Http\Requests\UpdateIngredientRequest;
use App\Models\Ingredient;

class IngredientController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // Afficher le formulaire de création d'un ingrédient
        return view('admin.ingredients.create');
        // Ou retourner une réponse JSON
        // return response()->json(['message' => 'Display create ingredient form']);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreIngredientRequest $request)
    {
        // Validation des données
        $validatedData = $request->validated();

        // Création de l'ingrédient
        $ingredient = Ingredient::create($validatedData);

        // Optionnel : redirection ou réponse JSON
        return response()->json($ingredient, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Ingredient $ingredient)
    {
        // Afficher les détails d'un ingrédient spécifique
        return response()->json($ingredient);
        // Ou retourner une vue avec les détails de l'ingrédient
        // return view('admin.ingredients.show', compact('ingredient'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Ingredient $ingredient)
    {
        // Récupérer l'ingrédient à modifier
        $ingredient = Ingredient::findOrFail($ingredient->id);

        // Ou retourner une réponse JSON
        return response()->json($ingredient);
        // Ou retourner une vue d'édition
        // return view('admin.ingredients.edit', compact('ingredient'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateIngredientRequest $request, Ingredient $ingredient)
    {
        // Validation des données
        $validatedData = $request->validated();

        // Mise à jour de l'ingrédient
        $ingredient->update($validatedData);

        // Optionnel : redirection ou réponse JSON
        return response()->json($ingredient);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Ingredient $ingredient)
    {
        // Suppression de l'ingrédient
        $ingredient->delete();

        // Optionnel : redirection ou réponse JSON
        return response()->json(['message' => 'Ingredient deleted successfully'], 204);
        // Ou redirection vers une autre page
        // return redirect()->route('ingredients.index')->with('success', 'Ingredient deleted successfully');
    }
}
