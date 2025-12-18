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

        // Filtre par prix - UTILISATION DE whereBetween pour inclure les bornes
        if ($request->filled('minPrice') && $request->filled('maxPrice')) {
            $biensQuery->whereBetween('price', [
                (float) $request->minPrice,
                (float) $request->maxPrice
            ]);
        } elseif ($request->filled('minPrice')) {
            $biensQuery->where('price', '>=', (float) $request->minPrice);
        } elseif ($request->filled('maxPrice')) {
            $biensQuery->where('price', '<=', (float) $request->maxPrice);
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
            $rooms = $request->rooms;
            if ($rooms === '5' || $rooms == '5+' || $rooms >= 5) {
                $biensQuery->where('rooms', '>=', 5);
            } else {
                $biensQuery->where('rooms', '>=', (int) $rooms);
            }
        }

        // Filtre par nombre de salles de bain
        if ($request->filled('bathrooms')) {
            $bathrooms = $request->bathrooms;
            if ($bathrooms === '4' || $bathrooms == '4+' || $bathrooms >= 4) {
                $biensQuery->where('bathrooms', '>=', 4);
            } else {
                $biensQuery->where('bathrooms', '>=', (int) $bathrooms);
            }
        }

        // Filtre par nombre d'étages
        if ($request->filled('floors')) {
            $floors = $request->floors;
            if ($floors === '4' || $floors == '4+' || $floors >= 4) {
                $biensQuery->where('floors', '>=', 4);
            } else {
                $biensQuery->where('floors', '>=', (int) $floors);
            }
        }

        // ==================== RÉCUPÉRATION DES DONNÉES ====================

        // Récupérer tous les biens correspondants aux critères
        $biens = $biensQuery->get();

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
                        'url' => asset('storage/' . $image->chemin_image),
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
            ->filter()
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
            'terrains' => 0,
            'studios' => 0,
            'vente' => 0,
            'location' => 0,
        ];

        foreach ($biens as $bien) {
            // Compter par catégorie
            if ($bien->category) {
                $categoryName = strtolower($bien->category->name);

                if (stripos($categoryName, 'maison') !== false) {
                    $stats['maisons']++;
                }

                if (stripos($categoryName, 'appartement') !== false) {
                    $stats['appartements']++;
                }

                if (stripos($categoryName, 'terrain') !== false) {
                    $stats['terrains']++;
                }

                if (stripos($categoryName, 'studio') !== false) {
                    $stats['studios']++;
                }
            }

            // Compter par type de mandat
            if ($bien->mandat) {
                if ($bien->mandat->type_mandat === 'vente') {
                    $stats['vente']++;
                } elseif ($bien->mandat->type_mandat === 'gestion_locative') {
                    $stats['location']++;
                }
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
