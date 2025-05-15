<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRoomRequest;
use App\Http\Requests\UpdateRoomRequest;
use App\Models\Room;

class RoomController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Afficher la liste des chambres
        return response()->json(Room::all());
        // Ou retourner une vue avec la liste des chambres
        // return view('admin.rooms.index', ['rooms' => Room::all()]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // Afficher le formulaire de création d'une chambre
        return view('admin.rooms.create');
        // Ou retourner une réponse JSON
        // return response()->json(['message' => 'Display create room form']);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreRoomRequest $request)
    {
        // Validation des données
        $validatedData = $request->validated();

        // Création de la chambre
        $room = Room::create($validatedData);

        // Optionnel : redirection ou réponse JSON
        return response()->json($room, 201);
        // Ou redirection vers une autre page
        // return redirect()->route('admin.rooms.index')->with('success', 'Chambre créée avec succès.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Room $room)
    {
        // Afficher les détails d'une chambre spécifique
        return response()->json($room);
        // Ou retourner une vue avec les détails de la chambre
        // return view('admin.rooms.show', ['room' => $room]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Room $room)
    {
        // Afficher le formulaire d'édition pour une chambre spécifique
        return view('admin.rooms.edit', ['room' => $room]);
        // Ou retourner une réponse JSON
        // return response()->json(['message' => 'Display edit room form']);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateRoomRequest $request, Room $room)
    {
        // Validation des données
        $validatedData = $request->validated();

        // Mise à jour de la chambre
        $room->update($validatedData);

        // Optionnel : redirection ou réponse JSON
        return response()->json($room);
        // Ou redirection vers une autre page
        // return redirect()->route('admin.rooms.index')->with('success', 'Chambre mise à jour avec succès.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Room $room)
    {
        // Suppression de la chambre
        $room->delete();

        // Optionnel : redirection ou réponse JSON
        return response()->json(['message' => 'Chambre supprimée avec succès.']);
        // Ou redirection vers une autre page
        // return redirect()->route('admin.rooms.index')->with('success', 'Chambre supprimée avec succès.');
    }
}
