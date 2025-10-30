<?php

namespace App\Http\Controllers;

use App\Models\Appartement;
use App\Models\Bien;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class AppartementController extends Controller
{
    /**
     * Liste des appartements d'un bien
     */
    public function index(Bien $bien)
    {
        $user = auth()->user();

        if ($bien->proprietaire_id !== $user->id && !$user->hasRole('admin')) {
            abort(403);
        }

        $appartements = $bien->appartements()
            ->with(['locationActive.client'])
            ->get();

        return Inertia::render('Appartements/Index', [
            'bien' => $bien->load('category'),
            'appartements' => $appartements,
            'stats' => $bien->getOccupationStats(),
        ]);
    }
    /**
     * Mettre à jour un appartement
     */
    public function update(Request $request, Bien $bien, Appartement $appartement)
    {
        $user = auth()->user();

        // Vérification des permissions
        if ($bien->proprietaire_id !== $user->id && !$user->hasRole('admin')) {
            abort(403, 'Action non autorisée');
        }

        // Vérifier que l'appartement appartient bien à ce bien
        if ($appartement->bien_id !== $bien->id) {
            abort(404, 'Appartement non trouvé pour ce bien');
        }

        // Validation des données
        $validated = $request->validate([
            'numero' => 'required|string|max:50',
            'etage' => 'required|integer|min:0|max:' . $bien->floors,
            'superficie' => 'nullable|numeric|min:0',
            'statut' => 'required|in:disponible,loue,reserve,maintenance',
            'salons' => 'nullable|integer|min:0',
            'chambres' => 'nullable|integer|min:0',
            'salles_bain' => 'nullable|integer|min:0',
            'cuisines' => 'nullable|integer|min:0',
            'description' => 'nullable|string|max:1000',
        ], [
            'numero.required' => 'Le numéro d\'appartement est requis',
            'etage.required' => 'L\'étage est requis',
            'etage.max' => 'L\'étage ne peut pas dépasser le nombre d\'étages du bien',
            'statut.required' => 'Le statut est requis',
            'statut.in' => 'Statut invalide',
        ]);

        // Vérifier l'unicité du numéro pour ce bien (sauf pour l'appartement actuel)
        $existingAppartement = Appartement::where('bien_id', $bien->id)
            ->where('numero', $validated['numero'])
            ->where('id', '!=', $appartement->id)
            ->first();

        if ($existingAppartement) {
            return back()->withErrors([
                'numero' => 'Ce numéro d\'appartement existe déjà pour ce bien'
            ])->withInput();
        }

        try {
            DB::beginTransaction();

            // Mise à jour de l'appartement
            $appartement->update([
                'numero' => $validated['numero'],
                'etage' => $validated['etage'],
                'superficie' => $validated['superficie'],
                'statut' => $validated['statut'],
                'salons' => $validated['salons'] ?? 0,
                'chambres' => $validated['chambres'] ?? 0,
                'salles_bain' => $validated['salles_bain'] ?? 0,
                'cuisines' => $validated['cuisines'] ?? 0,
                'description' => $validated['description'],
            ]);

            // Mettre à jour le statut global du bien
            $bien->updateStatutGlobal();

            DB::commit();

            return redirect()->route('appartements.index', $bien->id)
                ->with('success', 'Appartement modifié avec succès');

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Erreur lors de la mise à jour de l\'appartement: ' . $e->getMessage());

            return back()
                ->withErrors(['error' => 'Une erreur est survenue lors de la modification'])
                ->withInput();
        }
    }    /**
     * Supprimer un appartement
     */
    public function destroy(Bien $bien, Appartement $appartement)
    {
        $user = auth()->user();

        if ($bien->proprietaire_id !== $user->id && !$user->hasRole('admin')) {
            abort(403);
        }

        if ($appartement->bien_id !== $bien->id) {
            abort(404);
        }

        // Empêcher la suppression si l'appartement est loué
        if ($appartement->isLoue()) {
            return back()->with('error', 'Impossible de supprimer un appartement loué');
        }

        $appartement->delete();
        $bien->updateStatutGlobal();

        return redirect()->route('appartements.index', $bien->id)
            ->with('success', 'Appartement supprimé avec succès');
    }

    /**
     * Afficher le formulaire d'édition d'un appartement
     */
    public function edit(Bien $bien, Appartement $appartement)
    {
        $user = auth()->user();

        if ($bien->proprietaire_id !== $user->id && !$user->hasRole('admin')) {
            abort(403);
        }

        if ($appartement->bien_id !== $bien->id) {
            abort(404);
        }

        return Inertia::render('Appartements/Edit', [
            'bien' => $bien,
            'appartement' => $appartement,
            'dernier_etage' => $bien->floors,
        ]);
    }
}
