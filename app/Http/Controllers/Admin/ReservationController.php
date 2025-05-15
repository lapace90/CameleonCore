<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreReservationRequest;
use App\Http\Requests\UpdateReservationRequest;
use App\Models\Reservation;

class ReservationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Afficher la liste des réservations
        return response()->json(Reservation::all());
        // Ou retourner une vue avec la liste des réservations
        // return view('admin.reservations.index', ['reservations' => Reservation::all()]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // Afficher le formulaire de création d'une réservation
        return view('admin.reservations.create');
        // Ou retourner une réponse JSON
        // return response()->json(['message' => 'Display create reservation form']);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreReservationRequest $request)
    {
        // Validation des données
        $validatedData = $request->validated();

        // Création de la réservation
        $reservation = Reservation::create($validatedData);

        // Optionnel : redirection ou réponse JSON
        return response()->json($reservation, 201);
        // Ou redirection vers une autre page
        // return redirect()->route('admin.reservations.index')->with('success', 'Réservation créée avec succès.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Reservation $reservation)
    {
        // Afficher les détails d'une réservation spécifique
        return response()->json($reservation);
        // Ou retourner une vue avec les détails de la réservation
        // return view('admin.reservations.show', ['reservation' => $reservation]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Reservation $reservation)
    {
        // Afficher le formulaire d'édition pour une réservation spécifique
        return view('admin.reservations.edit', ['reservation' => $reservation]);
        // Ou retourner une réponse JSON
        // return response()->json(['message' => 'Display edit reservation form']);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateReservationRequest $request, Reservation $reservation)
    {
        // Validation des données
        $validatedData = $request->validated();

        // Mise à jour de la réservation
        $reservation->update($validatedData);

        // Optionnel : redirection ou réponse JSON
        return response()->json($reservation);
        // Ou redirection vers une autre page
        // return redirect()->route('admin.reservations.index')->with('success', 'Réservation mise à jour avec succès.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Reservation $reservation)
    {
        // Suppression de la réservation
        $reservation->delete();

        // Optionnel : redirection ou réponse JSON
        return response()->json(['message' => 'Réservation supprimée avec succès.'], 204);
        // Ou redirection vers une autre page
        // return redirect()->route('admin.reservations.index')->with('success', 'Réservation supprimée avec succès.');
    }
}
