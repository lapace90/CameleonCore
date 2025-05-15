<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreInvoiceRequest;
use App\Http\Requests\UpdateInvoiceRequest;
use App\Models\Invoice;

class InvoiceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Afficher la liste des factures
        return response()->json(Invoice::all());
        // Ou retourner une vue avec la liste des factures
        // return view('admin.invoices.index', ['invoices' => Invoice::all()]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // Afficher le formulaire de création d'une facture
        return view('admin.invoices.create');
        // Ou retourner une réponse JSON
        // return response()->json(['message' => 'Display create invoice form']);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreInvoiceRequest $request)
    {
        // Validation des données
        $validatedData = $request->validated();

        // Création de la facture
        $invoice = Invoice::create($validatedData);

        // Optionnel : redirection ou réponse JSON
        return response()->json($invoice, 201);
        // Ou redirection vers une autre page
        // return redirect()->route('admin.invoices.index')->with('success', 'Facture créée avec succès.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Invoice $invoice)
    {
        // Afficher les détails d'une facture spécifique
        return response()->json($invoice);
        // Ou retourner une vue avec les détails de la facture
        // return view('admin.invoices.show', ['invoice' => $invoice]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Invoice $invoice)
    {
        // Afficher le formulaire d'édition pour une facture spécifique
        return view('admin.invoices.edit', ['invoice' => $invoice]);
        // Ou retourner une réponse JSON
        // return response()->json(['message' => 'Display edit invoice form']);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateInvoiceRequest $request, Invoice $invoice)
    {
        // Validation des données
        $validatedData = $request->validated();

        // Mise à jour de la facture
        $invoice->update($validatedData);

        // Optionnel : redirection ou réponse JSON
        return response()->json($invoice);
        // Ou redirection vers une autre page
        // return redirect()->route('admin.invoices.index')->with('success', 'Facture mise à jour avec succès.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Invoice $invoice)
    {
        // Suppression de la facture
        $invoice->delete();

        // Optionnel : redirection ou réponse JSON
        return response()->json(['message' => 'Facture supprimée avec succès'], 204);
        // Ou redirection vers une autre page
        // return redirect()->route('admin.invoices.index')->with('success', 'Facture supprimée avec succès.');
    }
}
