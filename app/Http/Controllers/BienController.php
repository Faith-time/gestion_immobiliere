<?php

namespace App\Http\Controllers;

use App\Models\Bien;
use App\Models\Categorie;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class BienController extends Controller
{
    // GET /biens
    public function index()
    {
        $biens = Bien::with('category')->get();
        return Inertia::render('Biens/Index', [
            'biens' => $biens,
        ]);
    }

    // GET /biens/create
    public function create()
    {
        $categories = Categorie::all();
        return Inertia::render('Biens/Create', [
            'categories' => $categories
        ]);
    }

    // POST /biens
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'property_title' => 'required|file',
            'description' => 'required|string',
            'image' => 'required|image',
            'rooms' => 'required|integer',
            'floors' => 'required|integer',
            'bathrooms' => 'required|integer',
            'city' => 'required|string|max:255',
            'address' => 'required|string|max:255',
            'superficy' => 'required|numeric',
            'price' => 'required|numeric',
            'status' => 'required|in:disponible,loue,vendu,reserve',
            'categorie_id' => 'required|exists:categories,id',
        ]);

        $validated['proprietaire_id'] = auth()->id();

        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image')->store('biens', 'public');
        }

        $validated['property_title'] = $request->file('property_title')->store('documents', 'public');

        Bien::create($validated);

        return redirect()->route('biens.index')->with('success', 'Bien immobilier créé avec succès');
    }

    // GET /biens/{bien}
    public function show(Bien $bien)
    {
        $categories = Categorie::all();
        return Inertia::render('Biens/Show', [
            'bien' => $bien->load('category'),
            'categories' => $categories
        ]);
    }

    // GET /biens/{bien}/edit
    public function edit(Bien $bien)
    {
        // Vérification des permissions
        if ($bien->proprietaire_id !== auth()->id() && !auth()->user()->hasRole('admin')) {
            abort(403, 'Vous n\'êtes pas autorisé à modifier ce bien.');
        }

        $categories = Categorie::all();

        return Inertia::render('Biens/Edit', [
            'bien' => $bien->load('category'),
            'categories' => $categories
        ]);
    }

    // PUT /biens/{bien} - C'est cette méthode qui sera appelée
    public function update(Request $request, Bien $bien)
    {
        // Vérification des permissions
        if ($bien->proprietaire_id !== auth()->id() && !auth()->user()->hasRole('admin')) {
            abort(403, 'Vous n\'êtes pas autorisé à modifier ce bien.');
        }

        // ✅ CORRECTION : Règles de validation avec des champs requis appropriés
        $validated = $request->validate([
            'title' => 'required|string|max:255',              // ✅ Required
            'property_title' => 'nullable|file',
            'description' => 'nullable|string',
            'image' => 'nullable|image',
            'rooms' => 'nullable|integer|min:0',
            'floors' => 'nullable|integer|min:0',
            'bathrooms' => 'nullable|integer|min:0',
            'city' => 'required|string|max:255',               // ✅ Required
            'address' => 'required|string|max:255',            // ✅ Required
            'superficy' => 'required|numeric|min:1',           // ✅ Required
            'price' => 'required|numeric|min:1',               // ✅ Required
            'status' => 'required|in:disponible,loue,vendu,reserve', // ✅ Required
            'categorie_id' => 'required|exists:categories,id', // ✅ Required
        ]);

        // ✅ CORRECTION : Utiliser les valeurs existantes du bien comme fallback
        $data = [
            'title' => $validated['title'],
            'description' => $validated['description'] ?? $bien->description,
            'rooms' => $validated['rooms'] ?? $bien->rooms,
            'floors' => $validated['floors'] ?? $bien->floors,
            'bathrooms' => $validated['bathrooms'] ?? $bien->bathrooms,
            'city' => $validated['city'],
            'address' => $validated['address'],
            'superficy' => $validated['superficy'],
            'price' => $validated['price'],
            'status' => $validated['status'],
            'categorie_id' => $validated['categorie_id'],
        ];

        // ✅ Gestion séparée du document
        if ($request->hasFile('property_title')) {
            // Supprimer l'ancien fichier
            if ($bien->property_title && Storage::disk('public')->exists($bien->property_title)) {
                Storage::disk('public')->delete($bien->property_title);
            }
            // Ajouter le nouveau fichier
            $data['property_title'] = $request->file('property_title')->store('documents', 'public');
        }

        // ✅ Gestion séparée de l'image
        if ($request->hasFile('image')) {
            // Supprimer l'ancienne image
            if ($bien->image && Storage::disk('public')->exists($bien->image)) {
                Storage::disk('public')->delete($bien->image);
            }
            // Ajouter la nouvelle image
            $data['image'] = $request->file('image')->store('biens', 'public');
        }

        // ✅ DEBUG : Log des données avant mise à jour
        \Log::info('Données à mettre à jour:', $data);
        \Log::info('Données de validation reçues:', $validated);

        $bien->update($data);

        return redirect()->route('biens.index')->with('success', 'Bien immobilier modifié avec succès');
    }    // DELETE /biens/{bien}
    public function destroy(Bien $bien)
    {
        if ($bien->proprietaire_id !== auth()->id() && !auth()->user()->hasRole('admin')) {
            abort(403, 'Vous n\'êtes pas autorisé à supprimer ce bien.');
        }

        if ($bien->image && Storage::disk('public')->exists($bien->image)) {
            Storage::disk('public')->delete($bien->image);
        }

        if ($bien->property_title && Storage::disk('public')->exists($bien->property_title)) {
            Storage::disk('public')->delete($bien->property_title);
        }

        $bien->delete();

        return redirect()->route('biens.index')->with('success', 'Bien immobilier supprimé avec succès');
    }
}
