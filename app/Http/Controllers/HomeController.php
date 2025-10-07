<?php

namespace App\Http\Controllers;

use App\Models\Bien;
use App\Models\Categorie;
use Illuminate\Http\Request;
use Inertia\Inertia;

class HomeController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $biensQuery = Bien::with(['category', 'mandat'])
            ->where('status', 'disponible');

        // Calcul des rôles de l'utilisateur
        $ventesAsAcheteur = \App\Models\Vente::where('acheteur_id', $user->id)->exists();
        $ventesAsProprietaire = \App\Models\Vente::whereHas('bien', function($q) use ($user) {
            $q->where('proprietaire_id', $user->id);
        })->exists();

        // Appliquer les filtres de recherche si présents
        if ($request->filled('city')) {
            $biensQuery->where('city', 'like', '%' . $request->city . '%');
        }

        // ✅ CORRECTION : Utiliser minPrice et maxPrice au lieu de min_price et max_price
        if ($request->filled('minPrice')) {
            $biensQuery->where('price', '>=', $request->minPrice);
        }

        if ($request->filled('maxPrice')) {
            $biensQuery->where('price', '<=', $request->maxPrice);
        }

        if ($request->filled('address')) {
            $biensQuery->where('address', 'like', '%' . $request->address . '%');
        }

        if ($request->filled('category')) {
            $biensQuery->whereHas('category', function($query) use ($request) {
                $query->where('name', 'like', '%' . $request->category . '%');
            });
        }

        if ($request->filled('rooms')) {
            if ($request->rooms === '5' || $request->rooms >= 5) {
                $biensQuery->where('rooms', '>=', 5);
            } else {
                $biensQuery->where('rooms', '>=', $request->rooms);
            }
        }

        if ($request->filled('bathrooms')) {
            if ($request->bathrooms === '4' || $request->bathrooms >= 4) {
                $biensQuery->where('bathrooms', '>=', 4);
            } else {
                $biensQuery->where('bathrooms', '>=', $request->bathrooms);
            }
        }

        if ($request->filled('floors')) {
            $biensQuery->where('floors', '>=', $request->floors);
        }

        // Récupérer tous les biens correspondants aux critères
        $biens = $biensQuery->get();

        // Statistiques pour les compteurs
        $stats = $this->getBiensStats($biens);

        // Récupérer toutes les catégories pour les filtres
        $categories = Categorie::orderBy('name')->get();

        // Récupérer les villes uniques pour les suggestions
        $cities = Bien::where('status', 'disponible')
            ->distinct()
            ->pluck('city')
            ->sort()
            ->values();

        return Inertia::render('Home', [
            'biens' => $biens,
            'userHasMultipleRoles' => $ventesAsAcheteur && $ventesAsProprietaire,
            'userIsOnlyBuyer' => $ventesAsAcheteur && !$ventesAsProprietaire,
            'totalBiens' => $biens->count(),
            'stats' => $stats,
            'categories' => $categories,
            'cities' => $cities,
            'filters' => $request->only([
                'city', 'minPrice', 'maxPrice', 'address', 'category', 'rooms', 'bathrooms', 'floors'
            ])
        ]);
    }
    /**
     * Calculer les statistiques des biens
     */
    private function getBiensStats($biens)
    {
        $stats = [
            'total' => $biens->count(),
            'maisons' => 0,
            'appartements' => 0,
            'luxury' => 0,
            'recent' => 0
        ];

        foreach ($biens as $bien) {
            // Compter les maisons
            if ($bien->category && stripos($bien->category->name, 'maison') !== false) {
                $stats['maisons']++;
            }

            // Compter les appartements
            if ($bien->category && stripos($bien->category->name, 'appartement') !== false) {
                $stats['appartements']++;
            }

            // Compter les biens de luxe (prix >= 50M FCFA)
            if ($bien->price >= 50000000) {
                $stats['luxury']++;
            }

            // Compter les biens récents (moins de 30 jours)
            if ($bien->created_at && $bien->created_at->diffInDays(now()) <= 30) {
                $stats['recent']++;
            }
        }

        return $stats;
    }

    /**
     * Recherche AJAX pour l'autocomplétion
     */
    public function search(Request $request)
    {
        $query = $request->get('q', '');

        if (strlen($query) < 2) {
            return response()->json([]);
        }

        $suggestions = Bien::where('status', 'disponible')
            ->where(function($q) use ($query) {
                $q->where('city', 'like', "%{$query}%")
                    ->orWhere('address', 'like', "%{$query}%")
                    ->orWhere('title', 'like', "%{$query}%");
            })
            ->limit(5)
            ->get(['id', 'title', 'city', 'address'])
            ->map(function($bien) {
                return [
                    'id' => $bien->id,
                    'label' => $bien->title . ' - ' . $bien->city,
                    'city' => $bien->city,
                    'address' => $bien->address
                ];
            });

        return response()->json($suggestions);
    }
}
