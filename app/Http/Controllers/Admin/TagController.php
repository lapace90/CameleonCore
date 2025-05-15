<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTagRequest;
use App\Http\Requests\UpdateTagRequest;
use App\Models\Tag;

class TagController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Afficher la liste des tags
        return response()->json(Tag::all());
        // Ou retourner une vue avec la liste des tags
        // return view('admin.tags.index', ['tags' => Tag::all()]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // Afficher le formulaire de création d'un tag
        return view('admin.tags.create');
        // Ou retourner une réponse JSON
        // return response()->json(['message' => 'Display create tag form']);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreTagRequest $request)
    {
        // Validation des données
        $validatedData = $request->validated();

        // Création du tag
        $tag = Tag::create($validatedData);

        // Optionnel : redirection ou réponse JSON
        return response()->json($tag, 201);
        // Ou redirection vers une autre page
        // return redirect()->route('admin.tags.index')->with('success', 'Tag créé avec succès.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Tag $tag)
    {
        // Afficher les détails d'un tag spécifique
        return response()->json($tag);
        // Ou retourner une vue avec les détails du tag
        // return view('admin.tags.show', ['tag' => $tag]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Tag $tag)
    {
        // Afficher le formulaire d'édition pour un tag spécifique
        return view('admin.tags.edit', ['tag' => $tag]);
        // Ou retourner une réponse JSON
        // return response()->json(['message' => 'Display edit tag form']);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateTagRequest $request, Tag $tag)
    {
        // Validation des données
        $validatedData = $request->validated();

        // Mise à jour du tag
        $tag->update($validatedData);

        // Optionnel : redirection ou réponse JSON
        return response()->json($tag);
        // Ou redirection vers une autre page
        // return redirect()->route('admin.tags.index')->with('success', 'Tag mis à jour avec succès.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Tag $tag)
    {
        // Suppression du tag
        $tag->delete();

        // Optionnel : redirection ou réponse JSON
        return response()->json(['message' => 'Tag supprimé avec succès.']);
        // Ou redirection vers une autre page
        // return redirect()->route('admin.tags.index')->with('success', 'Tag supprimé avec succès.');
    }
}
