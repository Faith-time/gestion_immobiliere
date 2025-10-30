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

        $biensQuery = Bien::with(['category', 'mandat', 'images'])
            ->where('status', 'disponible');

        // Calcul des rôles de l'utilisateur
        $ventesAsAcheteur = \App\Models\Vente::where('acheteur_id', $user->id)->exists();
        $ventesAsProprietaire = \App\Models\Vente::whereHas('bien', function($q) use ($user) {
            $q->where('proprietaire_id', $user->id);
        })->exists();

        // ==================== FILTRES DE RECHERCHE ====================

        // Filtre par ville
        if ($request->filled('city')) {
            $biensQuery->where('city', 'like', '%' . $request->city . '%');
        }

        // Filtre par prix minimum
        if ($request->filled('minPrice')) {
            $biensQuery->where('price', '>=', $request->minPrice);
        }

        // Filtre par prix maximum
        if ($request->filled('maxPrice')) {
            $biensQuery->where('price', '<=', $request->maxPrice);
        }

        // Filtre par adresse
        if ($request->filled('address')) {
            $biensQuery->where('address', 'like', '%' . $request->address . '%');
        }

        // Filtre par catégorie
        if ($request->filled('category')) {
            $biensQuery->whereHas('category', function($query) use ($request) {
                $query->where('name', 'like', '%' . $request->category . '%');
            });
        }

        // Filtre par nombre de chambres
        if ($request->filled('rooms')) {
            if ($request->rooms === '5+' || $request->rooms >= 5) {
                $biensQuery->where('rooms', '>=', 5);
            } else {
                $biensQuery->where('rooms', '>=', $request->rooms);
            }
        }

        // Filtre par nombre de salles de bain
        if ($request->filled('bathrooms')) {
            if ($request->bathrooms === '4+' || $request->bathrooms >= 4) {
                $biensQuery->where('bathrooms', '>=', 4);
            } else {
                $biensQuery->where('bathrooms', '>=', $request->bathrooms);
            }
        }

        // Filtre par nombre d'étages
        if ($request->filled('floors')) {
            $biensQuery->where('floors', '>=', $request->floors);
        }

        // ==================== RÉCUPÉRATION DES DONNÉES ====================

        // Récupérer tous les biens correspondants aux critères
        $biens = $biensQuery->get();

        // 🔍 Debug : Vérifier les images du premier bien
        if ($biens->isNotEmpty() && app()->environment('local')) {
            \Log::info('Debug Home - Premier bien:', [
                'id' => $biens[0]->id,
                'title' => $biens[0]->title,
                'images_count' => $biens[0]->images->count(),
                'first_image' => $biens[0]->images->first()
                    ? [
                        'id' => $biens[0]->images->first()->id,
                        'chemin_image' => $biens[0]->images->first()->chemin_image,
                        'url' => $biens[0]->images->first()->url
                    ]
                    : null
            ]);
        }

        // Transformer les biens pour s'assurer que les images sont bien sérialisées
        $biensFormatted = $biens->map(function($bien) {
            return [
                'id' => $bien->id,
                'title' => $bien->title,
                'description' => $bien->description,
                'rooms' => $bien->rooms,
                'floors' => $bien->floors,
                'bathrooms' => $bien->bathrooms,
                'city' => $bien->city,
                'address' => $bien->address,
                'superficy' => $bien->superficy,
                'price' => $bien->price,
                'status' => $bien->status,
                'created_at' => $bien->created_at,
                'updated_at' => $bien->updated_at,
                'category' => $bien->category ? [
                    'id' => $bien->category->id,
                    'name' => $bien->category->name,
                ] : null,
                'mandat' => $bien->mandat ? [
                    'id' => $bien->mandat->id,
                    'type_mandat' => $bien->mandat->type_mandat,
                    'statut' => $bien->mandat->statut,
                ] : null,
                'images' => $bien->images->map(function($image) {
                    return [
                        'id' => $image->id,
                        'libelle' => $image->libelle,
                        'chemin_image' => $image->chemin_image,
                        'url' => asset('storage/' . $image->chemin_image), // Utilise asset() pour une URL complète
                    ];
                }),
            ];
        });

        // Statistiques pour les compteurs
        $stats = $this->getBiensStats($biens);

        // Récupérer toutes les catégories pour les filtres
        $categories = Categorie::orderBy('name')->get();

        // Récupérer les villes uniques pour les suggestions
        $cities = Bien::where('status', 'disponible')
            ->distinct()
            ->pluck('city')
            ->filter() // Enlever les valeurs null
            ->sort()
            ->values();

        // ==================== RETOUR INERTIA ====================

        return Inertia::render('Home', [
            'biens' => $biensFormatted,
            'userHasMultipleRoles' => $ventesAsAcheteur && $ventesAsProprietaire,
            'userIsOnlyBuyer' => $ventesAsAcheteur && !$ventesAsProprietaire,
            'totalBiens' => $biens->count(),
            'stats' => $stats,
            'categories' => $categories,
            'cities' => $cities,
            'filters' => $request->only([
                'city', 'minPrice', 'maxPrice', 'address', 'category', 'rooms', 'bathrooms', 'floors'
            ]),

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
