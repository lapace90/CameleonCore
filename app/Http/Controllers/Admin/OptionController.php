<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOptionRequest;
use App\Http\Requests\UpdateOptionRequest;
use App\Models\Option;

class OptionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Afficher la liste des options
        return response()->json(Option::all());
        // Ou retourner une vue avec la liste des options
        // return view('admin.options.index', ['options' => Option::all()]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // Afficher le formulaire de création d'une option
        return view('admin.options.create');
        // Ou retourner une réponse JSON
        // return response()->json(['message' => 'Display create option form']);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreOptionRequest $request)
    {
        // Validation des données
        $validatedData = $request->validated();

        // Création de l'option
        $option = Option::create($validatedData);

        // Optionnel : redirection ou réponse JSON
        return response()->json($option, 201);
        // Ou redirection vers une autre page
        // return redirect()->route('admin.options.index')->with('success', 'Option créée avec succès.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Option $option)
    {
        // Afficher les détails d'une option spécifique
        return response()->json($option);
        // Ou retourner une vue avec les détails de l'option
        // return view('admin.options.show', ['option' => $option]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Option $option)
    {
        // Afficher le formulaire d'édition pour une option spécifique
        return view('admin.options.edit', compact('option'));
        // Ou retourner une réponse JSON
        // return response()->json(['message' => 'Display edit option form']);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateOptionRequest $request, Option $option)
    {
        // Validation des données
        $validatedData = $request->validated();

        // Mise à jour de l'option
        $option->update($validatedData);

        // Optionnel : redirection ou réponse JSON
        return response()->json($option, 200);
        // Ou redirection vers une autre page
        // return redirect()->route('admin.options.index')->with('success', 'Option mise à jour avec succès.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Option $option)
    {
        // Suppression de l'option
        $option->delete();

        // Optionnel : redirection ou réponse JSON
        return response()->json(['message' => 'Option deleted successfully'], 204);
        // Ou redirection vers une autre page
        // return redirect()->route('admin.options.index')->with('success', 'Option supprimée avec succès.');
    }
}
